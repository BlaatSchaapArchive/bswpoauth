<?php
/*
Plugin Name: BlaatSchaap SSO: OAuth Consumer 
Plugin URI: http://code.blaatschaap.be
Description: Log in with an OAuth Provider
Version: 0.4.2-dev2
Author: André van Schoubroeck
Author URI: http://andre.blaatschaap.be
License: BSD
*/

//------------------------------------------------------------------------------
// Required files
//------------------------------------------------------------------------------
require_once("oauth/oauth_client.php");
require_once("oauth/http.php");
require_once("bs_oauth_config.php");
require_once("blaat.php");
require_once("bsauth.php");
require_once("classes/AuthService.class.php");
require_once("classes/OAuth.class.php");
//------------------------------------------------------------------------------
// Starting PHP Session and Output Buffering
//------------------------------------------------------------------------------
ob_start();
blaat_session_start();
//------------------------------------------------------------------------------
load_plugin_textdomain('blaat_auth', false, basename( dirname( __FILE__ ) ) . '/languages' );
//------------------------------------------------------------------------------
function bsoauth_init(){
  $oauth = new OAuth();
  global $BSAUTH_SERVICES;
  if (!isset($BSAUTH_SERVICES)) $BSAUTH_SERVICES = array();
  $BSAUTH_SERVICES["blaat_oauth"]=$oauth;
}
//------------------------------------------------------------------------------


function bsoauth_styles(){
  wp_register_style("bsauth_btn" , plugin_dir_url(__FILE__) . "css/bs-auth-btn.css");
  wp_enqueue_style( "bsauth_btn");

  wp_register_style("blaat_auth" , plugin_dir_url(__FILE__) . "blaat_auth.css");
  wp_enqueue_style( "blaat_auth");
}

add_action("wp_enqueue_scripts", "bsoauth_styles" );
add_action("admin_menu", "bsoauth_menu");
register_activation_hook(__FILE__, 'OAuth::install');
add_filter( 'the_content', 'bsauth_display' );
bsoauth_init();



?>
