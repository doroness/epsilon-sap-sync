<?php

class Sap_Connector {

   private $end_points = [];

   private $port = '';

   private $host = '';

   private $session_id;

   private $credentials;

   private $is_logged_in;

   public function __construct() {

      $this->is_logged_in = false;

      $base_url = $this->host .':'. $this->port;

      //get options from DB
      $options = get_option('epsilon_sap_sync_settings');

      //set credentials

      if ( !isset($options['username']) || !isset($options['password']) || !isset($options['companydb']) ) {

         return;

      }

      $this->credentials = array (
         'UserName' => $options['username'],
         'Password' => $options['password'],
         'CompanyDB' => $options['companydb'],
      );

      $this->host = $options['host'];

      $this->port = $options['port'];

      $this->end_points = array (
         'login'           => $base_url  .    '/b1s/v1/Login',
         'product-sync'    => $base_url   .    '/b1s/v1/Items',
         'base-endpoint'   => $base_url   .    '/b1s/v1/',
         'order'           => $base_url   .    '/b1s/v1/Orders',
      );
      
   }


   public function login_to_sap () : array {

      $login_response = array (
         'status' => false,
         'cookies' => [],
      );

      //remove B1SESSION cookie from browser
      setcookie('B1SESSION', '', time() - 3600);

      //remove ROUTEID cookie from browser
      setcookie('ROUTEID', '', time() - 3600);

      $body = json_encode($this->credentials);

      //send to SAP
      $args = array (
          'timeout' => 30,
         'method' => 'POST',
         'sslverify' => false,
         'body' =>$body,
      );

      $response = $this->send_http_request($this->end_points['login'], $args);

      //if $response is not an  WP_Error object and the response code is 200 and the response message is OK

      if( is_wp_error($response)) {

         //convert the response to an array
         error_log( $response->get_error_message() );

         return $login_response;

      } else {

         $cookies = wp_remote_retrieve_cookies($response);

         foreach ($cookies as $index => $coockie) {

            $login_response['cookies'][$coockie->name] = (array)$coockie;
            
         }

         $login_response['status'] = true;

         return $login_response;

      }

   }

   /**
    * Check if logged in to SAP. if not logge return false, else return true
    *
    * @return boolean
    */
   public function check_login_status () : bool {

      //send http request to SAP at  https://109.226.40.242:50000/b1s/v1/ to check if logged in 

      if (isset($_COOKIE['B1SESSION']) && isset($_COOKIE['ROUTEID'])) {

         $args = array (
            'method' => 'GET',
            'sslverify' => false,
            'headers' => array (
               'Cookie' => 'B1SESSION=' . $_COOKIE['B1SESSION'] . '; ROUTEID=' . $_COOKIE['ROUTEID']
            )

         );


        $r_body = $this->send_http_request( $this->end_points['base-endpoint'], $args);

         if (isset($r_body->error) && $r_body->error->code == 301) 
            return false;
         else 
            return true;
      }
      
      else  {
         return false;
      }
   }

   /**
    * quary the DB for product post type
    *
    * @return array
    */
   private function get_full_list_of_products () : array {

      //quary the DB for product post type

      $products = [];

      global $wpdb;

      $posts      = $wpdb->posts;

      $postmeta   = $wpdb->postmeta;

      $query = "SELECT $posts.ID, $postmeta.meta_value AS 'SKU' FROM $posts
               JOIN $postmeta ON $posts.ID = $postmeta.post_id 
               WHERE $postmeta.meta_key = '_sku' AND $postmeta.meta_value IS NOT NULL AND $posts.post_status = 'publish'";

      $products = $wpdb->get_results($query);

      return $products;
      
   }

   /**
    * Send order to SAP
    *
    * @param integer $card_code
    * @param Array $products_list
    * @return boolean
   */

   public function send_order_to_sap (int $card_code, Array $products_list, int $order_id) : bool {

      $order = wc_get_order($order_id);

      //get order meta data - sap_order_sent

      if ( get_post_meta($order_id, 'sap_order_sent', true) )
         return false;      

      //login to SAP

      $login_data = $this->login_to_sap();

      if ($login_data['status'] == false) {

         update_post_meta($order_id,'sap_order_sent', false);

         error_log('Unable to login to SAP' . __FILE__ . ' | ' . __LINE__ . ' | ' . PHP_EOL);

         return false;

      }

      $body = array (
         'CardCode'  => $card_code,
         'DocDueDate' =>  date('Y-m-d'),
         'DocumentLines' => $products_list
      );

      $headers = array (
         'Cookie' => 'B1SESSION=' . $login_data['cookies']['B1SESSION']['value']  . '; ROUTEID=' . $login_data['cookies']['ROUTEID']['value'],
      );

      $args = array (
         'method' => 'POST',
         'sslverify' => false,
         'body'      => json_encode($body),
         'headers'   => $headers, 
      );

      $response = $this->send_http_request($this->end_points['order'], $args);

      if (is_wp_error($response)) {

         error_log($response->get_error_message() . __FILE__ . ' | ' . __LINE__ . ' | ' . PHP_EOL);

         update_post_meta($order_id,'sap_order_sent', false);

         return false;

      } 

      $response_message = wp_remote_retrieve_response_message($response );

      if ($response_message != 'Created') {

         error_log('Unable to create order in SAP' . __FILE__ . ' | ' . __LINE__ . ' | ' . PHP_EOL);

         update_post_meta($order_id,'sap_order_sent', false);

         return false;
      }

      $body = json_decode(wp_remote_retrieve_body($response));

      //add meta data to the order to indicate that the order has been sent to SAP
      $docEntry = isset($body->DocEntry) ? $body->DocEntry : 0;   

      update_post_meta($order_id,'sap_order_sent', true);

      update_post_meta($order_id,'sap_order_number', $docEntry);

      return true;

   }

   
   /**
    * Sync the invantory with SAP
    */

   public function sync_full_invantory () : array {

      $message = array (
         'status' => false,
         'message' => 'Sync Done',
         'updated_products' => 0,
         'failed_products' => 0,
      );

      $login_response = array ();

      try {

         $login_response = $this->login_to_sap();

         if ($login_response['status'] == false) {

            //throw new Exception($login_response['message']);

            throw new Exception('Unable To Login');

         }

      } catch (Exception $e) {

         $message['message'] =  $e->getMessage();

         return $message;

      }
         
      $products = $this->get_full_list_of_products();

      $args = array (
         'method' => 'GET',
         'sslverify' => false,
         'headers' => array (
            'Cookie' => 'B1SESSION=' .$login_response['cookies']['B1SESSION']['value'] . '; ROUTEID=' . $login_response['cookies']['ROUTEID']['value']
         )

      );
      
      //for each product
      foreach ($products as $index => $product) {

         try {

            $params = "?\$filter=ItemCode eq '$product->SKU'&\$select=QuantityOnStock";

            $http_response = $this->send_http_request($this->end_points['product-sync'] ,$args, $params);

            if ($http_response['response']['code'] != 200) {

               $code = $http_response['response']['code'];

               $error_message = 'unable to update product: ' . $product->SKU;

               $message['message'] =  $error_message . '| Code: ' . $code;
               
               throw new Exception($error_message);
               
            }

            $body =  json_decode (wp_remote_retrieve_body($http_response) );
               
            if (isset($body->value[0]->QuantityOnStock)) {

               //update woo product stock with the value from SAP

               $stock = $body->value[0]->QuantityOnStock;

               //get the product

               $product = wc_get_product($product->ID);

               $product->set_stock_quantity($stock);
               
               $product->save();

               error_log('product updated: ' . $product->get_sku() . ' | stock: ' . $stock);

               $message['updated_products']++;

            } else {

               $message['failed_products']++;

            }

         } catch (Exception $e) {

            $message['message'] =  $e->getMessage();

            error_log(print_r($message, true));

            return $message;

         }   

         //stop sript for 1 second

         sleep(1);

      } //end of foreach
      
      $message['status']  = true;

      return $message;

   }//end of sync_full_invantory

   /**
    * Get the quantity of a product from SAP
    *
    * @param string $item_code
    * @return integer
    */
   public function get_quantity_data_in_chunks (string $params) : array {

      $response = array (
         'message' => '',
         'status' => false,
         'products' => array ()
      );

      $login_response = array ();

      try {

         $login_response = $this->login_to_sap();

         if ($login_response['status'] == false) {

            //throw new Exception($login_response['message']);

            throw new Exception('Unable To Login');

         }

      } catch (Exception $e) {

         $response['message'] =  $e->getMessage();

         return $message;

      }

      $args = array (
         'method' => 'GET',
         'sslverify' => false,
         'headers' => array (
            'Cookie' => 'B1SESSION=' .$login_response['cookies']['B1SESSION']['value'] . '; ROUTEID=' . $login_response['cookies']['ROUTEID']['value']
         )

      );

      $http_response = $this->send_http_request($this->end_points['product-sync'] ,$args, $params);

      $r_body = json_decode(wp_remote_retrieve_body($http_response), true) ;

      if (isset($r_body['error'])) {

         $response['message'] = $r_body['error']['message']['value'];

         return $response;
      }

      return  $r_body;

   }

   /**
   * Get the request body from SAP
   *
   * @param string $endpoint
   * @param string $params
   * @param array $args
   * @return Array | WP_Error
   */

   private function send_http_request (string $endpoint, array $args, string $params = '') : Array | WP_Error  {

      $response;

      if ($args['method'] === 'POST') {

         $response = wp_remote_post( $endpoint, $args );

      } elseif ($args['method'] === 'GET') {

         $response = wp_remote_get( $endpoint . $params, $args );

      }
      
      return $response;

   }


}

