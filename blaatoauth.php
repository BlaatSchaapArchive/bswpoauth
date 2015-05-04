<?php
/*
Plugin Name: BlaatLogin: OAuth
Plugin URI: http://code.blaatschaap.be
Description: Log in with an OAuth Provider
Version: 0.4.4
Author: André van Schoubroeck
Author URI: http://www.andrevanschoubroeck.name
License: BSD
*/

//------------------------------------------------------------------------------
// Required files
//------------------------------------------------------------------------------
require_once("oauth/oauth_client.php");
require_once("oauth/http.php");
require_once("bs_oauth_config.php");

//require_once("blaat.php");   //Moved to Separate Plugin
//require_once("bsauth.php");  //Moved to Separate Plugin

//require_once("classes/AuthService.class.php"); // Moved to BlaatLogin Base Plugin
require_once("classes/OAuth.class.php");
require_once("required_plugins.php");
require_once("db.php");


//------------------------------------------------------------------------------
load_plugin_textdomain('blaat_auth', false, basename( dirname( __FILE__ ) ) . '/languages' );
//------------------------------------------------------------------------------
function bsoauth_init(){
  ob_start();
  if (function_exists("blaat_session_start")) {
    blaat_session_start();
    if (class_exists("BlaatOAuth")) {
      $oauth = new BlaatOAuth();
      global $BSLOGIN_PLUGINS;
      if (!isset($BSLOGIN_PLUGINS)) $BSLOGIN_PLUGINS = array();
      $BSLOGIN_PLUGINS["blaat_oauth"]=$oauth;
    } else {
      //missing dependencies blaat login base
    }
  } else {
    // missing dependencies blaat base
  }
}
//------------------------------------------------------------------------------


function bsoauth_styles(){
  wp_register_style("bsauth_btn" , plugin_dir_url(__FILE__) . "css/bs-auth-btn.css");
  wp_enqueue_style( "bsauth_btn");

  wp_register_style("blaat_auth" , plugin_dir_url(__FILE__) . "blaat_auth.css");
  wp_enqueue_style( "blaat_auth");
}



register_activation_hook(__FILE__,"blaatoauth_install_database");

add_action("wp_enqueue_scripts",    "bsoauth_styles" );
add_action("admin_enqueue_scripts", "bsoauth_styles" );

add_action("admin_menu",            "bsoauth_menu");
add_filter("the_content",           "bsauth_display" );
add_action("wp_loaded",             "bsoauth_init" );



?>
