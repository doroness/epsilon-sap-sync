<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://site-pro.co.il
 * @since      1.0.0
 *
 * @package    Epsilon_Sap_Sync
 * @subpackage Epsilon_Sap_Sync/admin/partials
 */

?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="wrap">
      <h1>Sync SAP</h1>
      <form method="post" action="options.php">
         <?php
         settings_fields( 'epsilon-sap-sync' );
         do_settings_sections( 'epsilon-sap-sync' );
         submit_button();
         ?>
      </form>
      <div>
         <button class="button button-primary" id="epsilon-sap-sync__manual-sync"><?= __('Sync Products') ?></button>
         <button class="button button-primary" id="epsilon-sap-sync__login"><?= __('Login') ?></button>
         <button class="button button-primary" id="epsilon-sap-sync__logout"><?= __('Logout') ?></button>
         <div id="sync-loading-status" class="lds-ellipsis"><div></div><div></div><div></div><div></div></div>
         <p id="sync-result__text"></p>
      </div>
</div>
<style>
   .lds-ellipsis {
      position: relative;
      width: auto;
      display:none;
   }
   .lds-ellipsis div {
      position: absolute;
      top: 33px;
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: #000;
      animation-timing-function: cubic-bezier(0, 1, 1, 0);
   }
   .lds-ellipsis div:nth-child(1) {
      left: 8px;
      animation: lds-ellipsis1 0.6s infinite;
   }
   .lds-ellipsis div:nth-child(2) {
      left: 8px;
      animation: lds-ellipsis2 0.6s infinite;
   }
   .lds-ellipsis div:nth-child(3) {
      left: 32px;
      animation: lds-ellipsis2 0.6s infinite;
   }
   .lds-ellipsis div:nth-child(4) {
      left: 56px;
      animation: lds-ellipsis3 0.6s infinite;
   }
   @keyframes lds-ellipsis1 {
      0% {
         transform: scale(0);
      }
      100% {
         transform: scale(1);
      }
   }
   @keyframes lds-ellipsis3 {
      0% {
         transform: scale(1);
      }
      100% {
         transform: scale(0);
      }
      }
      @keyframes lds-ellipsis2 {
      0% {
         transform: translate(0, 0);
      }
      100% {
         transform: translate(24px, 0);
      }
   }
</style>
