<?php
/*
Plugin Name: BlaatSchaap OAuth 
Plugin URI: http://code.blaatschaap.be
Description: Log in with an OAuth Provider
Version: 0.3.5
Author: André van Schoubroeck
Author URI: http://andre.blaatschaap.be
License: BSD
*/

//------------------------------------------------------------------------------
require_once("oauth/oauth_client.php");
require_once("oauth/http.php");
require_once("bs_oauth_config.php");
require_once("blaat.php");
require_once("bsauth.php");
//------------------------------------------------------------------------------
/*
function blaat_autoloader($class) {
    include $class . '.class.php';
}

spl_autoload_register('blaat_autoloader');
*/
require_once("AuthService.class.php");
require_once("OAuth.class.php");
//------------------------------------------------------------------------------

session_start();
ob_start();
//------------------------------------------------------------------------------
load_plugin_textdomain('blaat_auth', false, basename( dirname( __FILE__ ) ) . '/languages' );
//------------------------------------------------------------------------------
function bsoauth_init(){
  $oauth = array( "trigger_login"  =>    "bsoauth_trigger_login",
                  "do_login"       =>    "bsoauth_do_login",
                  "buttons"        =>    "bsoauth_buttons"           
                );
  global $BSAUTH_SERVICES;
  if (!isset($BSAUTH_SERVICES)) $BSAUTH_SERVICES = array();
  $BSAUTH_SERVICES[]=$oauth;
}
//------------------------------------------------------------------------------
function bsoauth_trigger_login(){
	return OAuth::canLogin();
}
//------------------------------------------------------------------------------
function bsoauth_buttons(){
	return OAuth::getButtons();
}

function  bsoauth_install() {
  global $wpdb;
  global $bs_oauth_plugin;
  $dbver = 3;
  $live_dbver = get_option( "bs_oauth_dbversion" );
  $table_name = $wpdb->prefix . "bs_oauth_sessions";

  if ($dbver != live_dbver) {
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    $query = "CREATE TABLE $table_name (
              `id` INT NOT NULL AUTO_INCREMENT  PRIMARY KEY ,
              `user_id` INT NOT NULL DEFAULT 0,
              `service_id` TEXT NOT NULL ,
              `token` TEXT NOT NULL ,
              `authorized` BOOLEAN NOT NULL ,
              `expiry` DATETIME NULL DEFAULT NULL ,
              `type` TEXT NULL DEFAULT NULL ,
              `refresh` TEXT NULL DEFAULT NULL,
              `scope` TEXT NOT NULL DEFAULT ''
              );";
    dbDelta($query);

 
    $table_name = $wpdb->prefix . "bs_oauth_services";
    $query = "CREATE TABLE $table_name (
              `id` INT NOT NULL AUTO_INCREMENT  PRIMARY KEY ,
              `enabled` BOOLEAN NOT NULL DEFAULT FALSE ,
              `display_name` TEXT NOT NULL ,
              `display_order` INT NOT NULL DEFAULT 1,
              `client_name` TEXT NULL DEFAULT NULL ,
              `custom_id` INT NULL DEFAULT NULL ,
              `client_id` TEXT NOT NULL ,
              `client_secret` TEXT NOT NULL,
              `default_scope` TEXT NOT NULL DEFAULT '',
              `customlogo_url` TEXT NULL DEFAULT NULL,
              `customlogo_filename` TEXT NULL DEFAULT NULL,
              `customlogo_enabled` BOOLEAN DEFAULT FALSE
              );";
    dbDelta($query);


  $table_name = $wpdb->prefix . "bs_oauth_custom";
    $query = "CREATE TABLE $table_name (
              `id` INT NOT NULL AUTO_INCREMENT  PRIMARY KEY ,
              `oauth_version` ENUM('1.0','1.0a','2.0') DEFAULT '2.0',
              `request_token_url` TEXT NULL DEFAULT NULL,
              `dialog_url` TEXT NOT NULL,
              `access_token_url` TEXT NOT NULL,
              `url_parameters` BOOLEAN DEFAULT FALSE,
              `authorization_header` BOOLEAN DEFAULT TRUE,
              `offline_dialog_url` TEXT NULL DEFAULT NULL,
              `append_state_to_redirect_uri` TEXT NULL DEFAULT NULL
              );";
    dbDelta($query);
    update_option( "bs_oauth_dbversion" , 2);
  }
}
//------------------------------------------------------------------------------
function bsoauth_menu() {

  if (!blaat_page_registered('blaat_plugins')){
    add_menu_page('BlaatSchaap', 'BlaatSchaap', 'manage_options', 'blaat_plugins', 'blaat_plugins_page');
    //add_submenu_page('blaat_plugins', "" , "" , 'manage_options', 'blaat_plugins', 'blaat_plugins_page');
  }

//  add_menu_page('BlaatSchaap', 'BlaatSchaap', 'manage_options', 'blaat_plugins', 'blaat_plugins_page');
//  add_submenu_page('blaat_plugins', "" , "" , 'manage_options', 'blaat_plugins', 'blaat_plugins_page');



  add_submenu_page('blaat_plugins',   __('General Auth Settings',"blaat_auth") , 
                                      __("General Auth","blaat_auth") , 
                                      'manage_options', 
                                      'bsauth_pages_plugins', 
                                       'blaat_plugins_auth_page');
  add_submenu_page('blaat_plugins' ,  __('OAuth Configuration',"blaat_auth"), 
                                      __('OAuth Configuration',"blaat_auth"), 
                                      'manage_options', 
                                      'bsoauth_services', 
                                      'bsoauth_config_page' );
  add_submenu_page('blaat_plugins' ,  __('OAuth Add Service',"blaat_auth"),   
                                      __('OAuth Add',"blaat_auth"), 
                                      'manage_options', 
                                      'bsoauth_add', 
                                      'bsoauth_add_page' );
  add_submenu_page('blaat_plugins' ,  __('OAuth Add Custom Service',"blaat_auth"),   
                                      __('OAuth Add Custom',"blaat_auth"), 
                                      'manage_options', 
                                      'bsoauth_custom', 
                                      'bsoauth_add_custom_page' );
  add_action( 'admin_init', 'bsauth_register_options' );
}
//------------------------------------------------------------------------------
function bsoauth_config_page() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	echo '<div class="wrap">';
        screen_icon();
        echo "<h2>";
        _e("BlaatSchaap OAuth Configuration","blaat_auth");
        echo "</h2>";
        ?><p><?php  _e("Documentation:","blaat_auth");?>
          <a href="http://code.blaatschaap.be/bscp/oauth-plugin-for-wordpress/" target="_blank">
            http://code.blaatschaap.be/bscp/oauth-plugin-for-wordpress/
          </a>
        </p><?php

        if ($_POST['add_service']) bsoauth_add_process();
        if ($_POST['add_custom_service']) bsoauth_add_custom_process();
        if ($_POST['delete_service']) bsoauth_delete_service();
        if ($_POST['update_service']) bsoauth_update_service();
        echo "<h2>"; _e("Configured Services","blaat_auth"); echo "</h2><hr>";
        bsoauth_list_services();
        echo '<hr>';

}
//------------------------------------------------------------------------------
function bsoauth_do_login(){
  //bsoauth_process("bsoauth_process_login");
  OAuth::Login();
}




  wp_register_style("bsauth_btn" , plugin_dir_url(__FILE__) . "css/bs-auth-btn.css");
  wp_enqueue_style( "bsauth_btn");

wp_register_style("blaat_auth" , plugin_dir_url(__FILE__) . "blaat_auth.css");
wp_enqueue_style( "blaat_auth");

if (get_option("logout_frontpage")) {
  add_action('wp_logout','go_frontpage');
}
//------------------------------------------------------------------------------
function go_frontpage(){
  wp_redirect( home_url() );
  exit();
}
//------------------------------------------------------------------------------




add_action("admin_menu", bsoauth_menu);
register_activation_hook(__FILE__, 'bsoauth_install');
add_filter( 'the_content', 'bsauth_display' );
bsoauth_init();



?>
