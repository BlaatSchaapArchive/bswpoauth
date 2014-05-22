<?php
/*
Plugin Name: BlaatSchaap OAuth 
Plugin URI: http://code.blaatschaap.be
Description: Log in with an OAuth Provider
Version: 0.4
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
  return ($_SESSION['bsoauth_id'] || $_REQUEST['bsoauth_id'] ||
          $_SESSION['bsoauth_link'] || $_REQUEST['bsoauth_link']);
}
//------------------------------------------------------------------------------
function bsoauth_buttons(){
    global $wpdb;
    $table_name = $wpdb->prefix . "bs_oauth_services";
    $results = $wpdb->get_results("select * from $table_name where enabled=1 ",ARRAY_A);
    foreach ($results as $result){
      //$class = "btn-auth btn-".strtolower($result['client_name']);
      if(!$result['customlogo_enabled']) 
        $service=strtolower($result['client_name']); 
      else {
        $service="custom-".$result['id'];
        echo "<style>.bs-auth-btn-logo-".$service." {background-image:url('" .$result['customlogo_url']."');}</style>";
      }
      echo "<button class='bs-auth-btn' name=bsoauth_id type=submit value='".$result['id']."'><span class='bs-auth-btn-logo bs-auth-btn-logo-$service'></span><span class='bs-auth-btn-text'>". $result['display_name']."</span></button>";
    }
}

//------------------------------------------------------------------------------
if (!function_exists("blaat_plugins_auth_page")) {
  function blaat_plugins_auth_page(){
    echo '<div class="wrap">';
    echo '<h2>';
    _e("BlaatSchaap WordPress Authentication Plugins","blaat_auth");
    echo '</h2>';
    echo '<form method="post" action="options.php">';
    settings_fields( 'bsauth_pages' ); 

    echo '<table class="form-table">';

    echo '<tr><th>'. __("Login page","blaat_auth") .'</th><td>';
    echo blaat_page_select("login_page");
    echo '</td></tr>';
    
    echo '<tr><th>'. __("Register page","blaat_auth") .'</th><td>';
    echo blaat_page_select("register_page");
    echo '</td></tr>';

    echo '<tr><th>'. __("Link page","blaat_auth") .'</th><td>';
    echo blaat_page_select("link_page");
    echo '</td></tr>';

    echo '<tr><th>';
    _e("Redirect to frontpage after logout", "blaat_auth") ;
    echo "</th><td>";
    $checked = get_option('logout_frontpage') ? "checked" : "";
    echo "<input type=checkbox name='logout_frontpage' value='1' $checked>";
    echo "</td></tr>";

    echo '<tr><th>'. __("Custom Button CSS","blaat_auth") .'</th><td>';
    echo "<textarea cols=70 rows=15 id='bsauth_custom_button_textarea' name='bsauth_custom_button'>";
    echo htmlspecialchars(get_option("bsauth_custom_button"));
    echo "</textarea>";
    echo '</td></tr>';

    echo '</table><input name="Submit" type="submit" value="';
    echo  esc_attr_e('Save Changes') ;
    echo '" ></form></div>';

  }
}
//------------------------------------------------------------------------------
function  bsoauth_install() {
  global $wpdb;
  global $bs_oauth_plugin;
  $dbver = 2;
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
  bsoauth_process("bsoauth_process_login");
}
//------------------------------------------------------------------------------
function bsoauth_process_login($client, $displayname){
  global $wpdb;
  $_SESSION['oauth_display'] = $displayname;

  if ( is_user_logged_in() ) { 
      // Linking not working. Session variables not being set.
      // Looks like this is not executed for some reason???
      $_SESSION['oauth_token']   = $client->access_token;
      $_SESSION['oauth_expiry']  = $client->access_token_expiry;
      $_SESSION['oauth_scope']   = $client->scope;
      //die("link");
      header("Location: ".site_url("/".get_option("link_page")). '?' . $_SERVER['QUERY_STRING']);     
  } else {
    
    $service_id = $_SESSION['bsoauth_id'];
    $token = $client->access_token;
    $table_name = $wpdb->prefix . "bs_oauth_sessions";

    $query = $wpdb->prepare("SELECT `user_id` FROM $table_name WHERE `service_id` = %d AND `token` = %d",$service_id,$token);  
    $results = $wpdb->get_results($query,ARRAY_A);
    $result = $results[0];

    if ($result) {
      unset ($_SESSION['bsoauth_id']);  
      wp_set_current_user ($result['user_id']);
      wp_set_auth_cookie($result['user_id']);
      header("Location: ".site_url("/".get_option("login_page")));     
      
    } else {
      $_SESSION['bsauth_registering'] = 1;
      $_SESSION['oauth_signup']  = 1;
      $_SESSION['oauth_token']   = $client->access_token;
      $_SESSION['oauth_expiry']  = $client->access_token_expiry;
      $_SESSION['oauth_scope']   = $client->scope;
      header("Location: ".site_url("/".get_option("register_page")));
    }
  }
}
//------------------------------------------------------------------------------
function bsoauth_process($process){
   
   session_start();
  if ($_POST['bsoauth_link']) {
    $_REQUEST['bsoauth_id'] = $_POST['bsoauth_link'];
    $_SESSION['bsoauth_link'] = $_POST['bsoauth_link'];
  }

  if ( $_REQUEST['bsoauth_id'] ||  $_REQUEST['code'] || $_REQUEST['oauth_token'] ) {
    if ($_REQUEST['bsoauth_id']) $_SESSION['bsoauth_id']=$_REQUEST['bsoauth_id'];

    global $wpdb;
    $table_name = $wpdb->prefix . "bs_oauth_services";
    $query = $wpdb->prepare("SELECT * FROM $table_name  WHERE id = %d", $_SESSION['bsoauth_id']);

    $results = $wpdb->get_results($query,ARRAY_A);
    $result = $results[0];
 
    $client = new oauth_client_class;
    $client->configuration_file = plugin_dir_path(__FILE__) . '/oauth/oauth_configuration.json';
    $client->redirect_uri  = site_url("/".get_option("login_page"));
    $client->client_id     = $result['client_id'];
    $client->client_secret = $result['client_secret'];
    $client->scope         = $result['default_scope'];

    if ($result['custom_id']) {
      $table_name = $wpdb->prefix . "bs_oauth_custom";
      $query = $wpdb->prepare("SELECT * FROM $table_name  WHERE id = %d", $result['custom_id']);
      $customs = $wpdb->get_results($query,ARRAY_A);
      $custom = $customs[0];

      $client->oauth_version                 = $custom['oauth_version'];
      $client->request_token_url             = $custom['request_token_url'];
      $client->dialog_url                    = $custom['dialog_url'];
      $client->access_token_url              = $custom['access_token_url'];
      $client->url_parameters                = $custom['url_parameters'];
      $client->authorization_header          = $custom['authorization_header'];
      $client->offline_dialog_url            = $custom['offline_dialog_url'];
      $client->append_state_to_redirect_uri  = $custom['append_state_to_redirect_uri'];
    } else {
      $client->server        = $result['client_name'];
    }
 
    if(($success = $client->Initialize())){
      if(($success = $client->Process())){
        if(strlen($client->access_token)){
          call_user_func($process,$client,$result['display_name']);
          $success = $client->Finalize($success);
	      } else {
          _e("OAuth error: the token is missing","blaat_auth");
          echo $client->error;
        }
      } else {
          _e("OAuth error: processing error","blaat_auth");
          echo $client->error;
      }
    } else {
      _e("OAuth error: initialisation error","blaat_auth");
      echo $client->error;
    } 
  } else {
    return $user;
  }
}


//wp_register_style('necolas-css3-social-signin-buttons', plugin_dir_url(__FILE__) . 'css/auth-buttons.css');
//wp_enqueue_style( 'necolas-css3-social-signin-buttons');

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

// just in case we want to add those again, but for now we use our own forms
//add_filter("login_form",   bsoauth_loginform );
//add_filter('authenticate', bsoauth_do_login,90  );
//add_action('personal_options_update', bsoauth_link_update);
//add_action("personal_options", bsoauth_linkform);


add_action("admin_menu", bsoauth_menu);
register_activation_hook(__FILE__, 'bsoauth_install');
add_filter( 'the_content', 'bsauth_display' );
bsoauth_init();



?>
