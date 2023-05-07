<?php

$setting_args = array(
    'type' => 'string',
    'sanitize_callback' => 'sanitize_text_field',
    'default' => null,
);

register_setting( 'epsilon-sap-sync', 'sap_sync_credentials__sap_username',array('type' => 'string','sanitize_callback' => 'sanitize_text_field'));

register_setting( 'epsilon-sap-sync', 'sap_sync_credentials__sap_password',array('type' => 'string','sanitize_callback' => 'sanitize_text_field'));

register_setting( 'epsilon-sap-sync', 'sap_sync_credentials__sap_db',array('type' => 'string','sanitize_callback' => 'sanitize_text_field'));


//add settings section
add_settings_section(
   'sap_sync_credentials_section',
   'SAP Credentials',
   'sap_sync_credentials_section_callback',
   'epsilon-sap-sync'
);

//add settings field
add_settings_field(
   'sap_sync_credentials__sap_username',
   'SAP Username',
   'sap_sync_credentials__sap_username_callback',
   'epsilon-sap-sync',
   'sap_sync_credentials_section'
);

//add settings field
add_settings_field(
   'sap_sync_credentials__sap_password',
   'SAP Password',
   'sap_sync_credentials__sap_password_callback',
   'epsilon-sap-sync',
   'sap_sync_credentials_section'
);

//add settings field
add_settings_field(
   'sap_sync_credentials__sap_db',
   'SAP DB',
   'sap_sync_credentials__sap_db_callback',
   'epsilon-sap-sync',
   'sap_sync_credentials_section'
);

function sap_sync_credentials_section_callback() {
   echo '<p>Enter your SAP credentials</p>';
}

function sap_sync_credentials__sap_username_callback() {
   $option = get_option( 'sap_sync_credentials__sap_username' );
   echo '<input type="text" name="sap_sync_credentials__sap_username" value="' . $option . '" />';
}

function sap_sync_credentials__sap_password_callback() {
   $option = get_option( 'sap_sync_credentials__sap_password' );
   echo '<input type="password" name="sap_sync_credentials__sap_password" value="' . $option . '" />';
}
function sap_sync_credentials__sap_db_callback() {
   $option = get_option( 'sap_sync_credentials__sap_db' );
   echo '<input type="text" name="sap_sync_credentials__sap_db" value="' . $option . '" />';
}
