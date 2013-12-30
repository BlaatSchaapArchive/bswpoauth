<?php
/*
Plugin Name: BlaatSchaap OAuth 
Plugin URI: http://code.blaatschaap.be
Description: Log in with an OAuth Provider
Version: 0.1
Author: André van Schoubroeck
Author URI: http://andre.blaatschaap.be
License: BSD
*/

//------------------------------------------------------------------------------
require_once("oauth/oauth_client.php");
require_once("oauth/http.php");
require_once("bs_oauth_config.php");
//------------------------------------------------------------------------------
session_start();
ob_start();
//------------------------------------------------------------------------------
load_plugin_textdomain('blaat_auth', false, basename( dirname( __FILE__ ) ) . '/languages' );
//------------------------------------------------------------------------------
function blaat_register_pageoptions(){
  register_setting( 'blaat_auth_pages', 'login_page' );
  register_setting( 'blaat_auth_pages', 'register_page' );
  register_setting( 'blaat_auth_pages', 'link_page' );
  register_setting( 'blaat_auth_pages', 'logout_frontpage' );
}
//------------------------------------------------------------------------------
if (!function_exists("blaat_page_select")) {
  function blaat_page_select($item){
    $pages = get_pages();
    $blaat = "<select id='$item' name='$item'>";
    foreach ( $pages as $page ) {
      $pagename = $page->post_name;
      $selected = (get_option($item)==$pagename) ? "selected='selected'" : "";
      $option = "<option value='$pagename' $selected>";
      $option .= $page->post_title;
      $option .= "</option>";
      $blaat .= $option;
    }
    $blaat .= "</select>";
    return $blaat;  
  }
}
//------------------------------------------------------------------------------
if (!function_exists("blaat_plugins_page")) {
  function blaat_plugins_page(){
    e_("BlaatSchaap Plugins","blaat_auth");
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
    settings_fields( 'blaat_auth_pages' ); 

    echo '<table class="form-table">';

    echo '<tr><td>'. __("Login page","blaat_auth") .'</td><td>';
    echo blaat_page_select("login_page");
    echo '</td></tr>';
    
    echo '<tr><td>'. __("Register page","blaat_auth") .'</td><td>';
    echo blaat_page_select("register_page");
    echo '</td></tr>';

    echo '<tr><td>'. __("Link page","blaat_auth") .'</td><td>';
    echo blaat_page_select("link_page");
    echo '</td></tr>';

    echo '<tr><td>';
    _e("Redirect to frontpage after logout", "blaat_auth") ;
    echo "</td><td>";
    $checked = get_option('logout_frontpage') ? "checked" : "";
    echo "<input type=checkbox name='logout_frontpage' value='1' $checked>";
    echo "</td></tr>";
     

    echo '</table><input name="Submit" type="submit" value="';
    echo  esc_attr_e('Save Changes') ;
    echo '" ></form></div>';

  }
}
//------------------------------------------------------------------------------
function  blaat_oauth_install() {
  global $wpdb;
  global $bs_oauth_plugin;

  $table_name = $wpdb->prefix . "bs_oauth_sessions";
  if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
    $query = "CREATE TABLE $table_name (
              `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
              `user_id` INT NOT NULL DEFAULT 0,
              `service_id` TEXT NOT NULL ,
              `token` TEXT NOT NULL ,
              `authorized` BOOLEAN NOT NULL ,
              `expiry` DATETIME NULL DEFAULT NULL ,
              `type` TEXT NULL DEFAULT NULL ,
              `refresh` TEXT NULL DEFAULT NULL,
              `scope` TEXT NOT NULL DEFAULT ''
              ) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci COMMENT = 'OAuth Sessions';";
    $result = $wpdb->query($query);
  }

 
  $table_name = $wpdb->prefix . "bs_oauth_services";
  if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
    $query = "CREATE TABLE $table_name (
              `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
              `enabled` BOOLEAN NOT NULL DEFAULT FALSE ,
              `display_name` TEXT NOT NULL ,
              `client_name` TEXT NULL DEFAULT NULL ,
              `custom_id` INT NULL DEFAULT NULL ,
              `client_id` TEXT NOT NULL ,
              `client_secret` TEXT NOT NULL,
              `default_scope` TEXT NOT NULL DEFAULT ''
              ) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci COMMENT = 'OAuth Services';";
    $result = $wpdb->query($query);
  }


  $table_name = $wpdb->prefix . "bs_oauth_custom";
  if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
    $query = "CREATE TABLE $table_name (
              `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
              `oauth_version` ENUM('1.0','1.0a','2.0') DEFAULT '2.0',
              `request_token_url` TEXT NULL DEFAULT NULL,
              `dialog_url` TEXT NOT NULL,
              `access_token_url` TEXT NOT NULL,
              `url_parameters` BOOLEAN DEFAULT FALSE,
              `authorization_header` BOOLEAN DEFAULT TRUE,
              `offline_dialog_url` TEXT NULL DEFAULT NULL,
              `append_state_to_redirect_uri` TEXT NULL DEFAULT NULL
              ) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci COMMENT = 'OAuth Custom Services';";
    $result = $wpdb->query($query);
  }
}
//------------------------------------------------------------------------------
function blaat_oauth_menu() {
  add_menu_page('BlaatSchaap', 'BlaatSchaap', 'manage_options', 'blaat_plugins', 'blaat_plugins_page');
  add_submenu_page('blaat_plugins', "" , "" , 'manage_options', 'blaat_plugins', 'blaat_plugins_page');



  add_submenu_page('blaat_plugins',   __('Auth Pages',"blaat_auth") , 
                                      __("Auth pages","blaat_auth") , 
                                      'manage_options', 
                                      'blaat_auth_pages_plugins', 
                                       'blaat_plugins_auth_page');
  add_submenu_page('blaat_plugins' ,  __('OAuth Configuration',"blaat_auth"), 
                                      __('OAuth Configuration',"blaat_auth"), 
                                      'manage_options', 
                                      'blaat_oauth_services', 
                                      'blaat_oauth_config_page' );
  add_submenu_page('blaat_plugins' ,  __('OAuth Add Service',"blaat_auth"),   
                                      __('OAuth Add',"blaat_auth"), 
                                      'manage_options', 
                                      'blaat_oauth_add', 
                                      'blaat_oauth_add_page' );
  add_submenu_page('blaat_plugins' ,  __('OAuth Add Custom Service',"blaat_auth"),   
                                      __('OAuth Add Custom',"blaat_auth"), 
                                      'manage_options', 
                                      'blaat_oauth_custom', 
                                      'blaat_oauth_add_custom_page' );
  add_action( 'admin_init', 'blaat_register_pageoptions' );
}
//------------------------------------------------------------------------------
function blaat_oauth_config_page() {
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

        if ($_POST['add_service']) blaat_oauth_add_process();
        if ($_POST['add_custom_service']) blaat_oauth_add_custom_process();
        if ($_POST['delete_service']) blaat_oauth_delete_service();
        if ($_POST['update_service']) blaat_oauth_update_service();
        echo "<h2>"; _e("Configured Services","blaat_auth"); echo "</h2><hr>";
        blaat_oauth_list_services();
        echo '<hr>';

}
//------------------------------------------------------------------------------
function blaat_oauth_do_login(){
  blaat_oauth_process("blaat_oauth_process_login");
}
//------------------------------------------------------------------------------
function blaat_oauth_process_login($client, $displayname){
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
    
    $service_id = $_SESSION['oauth_id'];
    $token = $client->access_token;
    $table_name = $wpdb->prefix . "bs_oauth_sessions";

    $query = $wpdb->prepare("SELECT `user_id` FROM $table_name WHERE `service_id` = %d AND `token` = %d",$service_id,$token);  
    $results = $wpdb->get_results($query,ARRAY_A);
    $result = $results[0];

    if ($result) {
      unset ($_SESSION['oauth_id']);  
      wp_set_current_user ($result['user_id']);
      wp_set_auth_cookie($result['user_id']);
      header("Location: ".site_url("/".get_option("login_page")));     
      
    } else {
      $_SESSION['oauth_signup']  = 1;
      $_SESSION['oauth_token']   = $client->access_token;
      $_SESSION['oauth_expiry']  = $client->access_token_expiry;
      $_SESSION['oauth_scope']   = $client->scope;
      header("Location: ".site_url("/".get_option("register_page")));
    }
  }
}
//------------------------------------------------------------------------------
function blaat_oauth_process($process){
   
   session_start();
  if ($_POST['oauth_link']) {
    $_REQUEST['oauth_id'] = $_POST['oauth_link'];
    $_SESSION['oauth_link'] = $_POST['oauth_link'];
  
  }

  if ( $_REQUEST['oauth_id'] ||  $_REQUEST['code'] || $_REQUEST['oauth_token'] ) {
    if ($_REQUEST['oauth_id']) $_SESSION['oauth_id']=$_REQUEST['oauth_id'];

    global $wpdb;
    $table_name = $wpdb->prefix . "bs_oauth_services";
    $query = $wpdb->prepare("SELECT * FROM $table_name  WHERE id = %d", $_SESSION['oauth_id']);

    $results = $wpdb->get_results($query,ARRAY_A);
    $result = $results[0];
 
    $client = new oauth_client_class;
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
//------------------------------------------------------------------------------
function blaat_auth_login_display(){
  if (!is_user_logged_in()) blaat_oauth_do_login();

  if ( is_user_logged_in() ) {
    if (isset($_POST['oauth_link']) || $_SESSION['oauth_link']) {
      blaat_oauth_do_login();  
    } else 
    _e("Logged in","blaat_auth");
  } else {
    echo "<div id='blaat_auth_local'>";
    echo "<p>" .  __("Log in with a local account","blaat_auth") . "</p>" ; 
    wp_login_form();
    echo "</div>";
    echo "<div id='blaat_auth_buttons'>";
    echo "<p>" . __("Log in with","blaat_auth") . "</p>";
    global $wpdb;
    global $bs_oauth_plugin;
    global $_SERVER;
    $ACTION=site_url("/".get_option("login_page"));
    $table_name = $wpdb->prefix . "bs_oauth_services";
    $results = $wpdb->get_results("select * from $table_name where enabled=1 ",ARRAY_A);
    echo "<form>";
    foreach ($results as $result){
      $class = "btn-auth btn-".strtolower($result['client_name']);
      echo "<button class='$class' name=oauth_id type=submit value='".$result['id']."'>". $result['display_name']."</button>";
    }

    echo "</form>";
    echo "</div>";
  }
}
//------------------------------------------------------------------------------
function blaat_auth_link_display(){
  session_start();
  global $wpdb;
  if (is_user_logged_in()) {
    if (isset($_POST['oauth_link'])){
        $_SESSION['oauth_link']=$_POST['oauth_link'];
        blaat_oauth_do_login($user);
      }

      if (isset($_POST['oauth_unlink'])){
        $table_name = $wpdb->prefix . "bs_oauth_sessions";
        // Not an ideal query... however, the form generation code would need
        // a complete rewrite to support supplying the service id entry.
        $table_name2 = $wpdb->prefix . "bs_oauth_services";
        $query2 = $wpdb->prepare("Select display_name from $table_name2 where id = %d", $_POST['oauth_unlink'] );
        $service_name = $wpdb->get_results($query2,ARRAY_A);
        $service = $service_name[0]['display_name'];
        $query = $wpdb->prepare ("Delete from $table_name where user_id = %d AND service_id = %d", get_current_user_id(), $_POST['oauth_unlink'] );
        $wpdb->query($query);
        printf( __("You are now unlinked from %s.", "blaat_auth"), $service );
      }
    $user = wp_get_current_user();
    if (isset($_SESSION['oauth_id'])     && isset($_SESSION['oauth_token']) &&
        isset($_SESSION['oauth_expiry']) && isset($_SESSION['oauth_scope']) ){
      $user_id    = $user->ID;
      $service_id = $_SESSION['oauth_id'];
      $token      = $_SESSION['oauth_token'];
      $expiry     = $_SESSION['oauth_expiry'];
      $scope      = $_SESSION['oauth_scope'];
      $service    = $_SESSION['oauth_display'];
      $table_name = $wpdb->prefix . "bs_oauth_sessions";
      $query = $wpdb->prepare("INSERT INTO $table_name (`user_id`, `service_id`, `token`, `expiry`, `scope` )
                                       VALUES      ( %d      ,  %d         ,  %s    , %s      , %s      )",
                                                    $user_id , $service_id , $token , $expiry , $scope  );
      $wpdb->query($query);
      unset($_SESSION['oauth_id']);
      unset($_SESSION['oauth_link']);
      unset($_SESSION['oauth_token']);
      unset($_SESSION['oauth_expiry']);
      unset($_SESSION['oauth_scope']);
      unset($_SESSION['oauth_display']);
      printf( __("Your %s account has been linked", "blaat_auth"), $service );
    } 

    $table_name = $wpdb->prefix . "bs_oauth_sessions";
    $user_id    = $user->ID;
    $query = $wpdb->prepare("SELECT service_id FROM $table_name WHERE `user_id` = %d",$user_id);
    $linked_services = $wpdb->get_results($query,ARRAY_A);
     
    $table_name = $wpdb->prefix . "bs_oauth_services";
    $query = "SELECT * FROM $table_name where enabled=1";
    $available_services = $wpdb->get_results($query,ARRAY_A);

    $linked = Array();
    foreach ($linked_services as $linked_service) {
      $linked[]=$linked_service['service_id'];
    }  
    foreach ($available_services as $available_service) {
      $class = "btn-auth btn-".strtolower($available_service['client_name']);

      if (in_array($available_service['id'],$linked)) {
        $unlinkHTML .= "<button class='$class' name='oauth_unlink' type=submit value='".$available_service['id']."'>". $available_service['display_name']."</button>";
      } else {
        $linkHTML .= "<button class='$class' name='oauth_link' type=submit value='".$available_service['id']."'>". $available_service['display_name']."</button>";
      }
      unset($_SESSION['oauth_id']);
      unset($_SESSION['oauth_link']);
    }
    echo "<form method=post action='". site_url("/".get_option("login_page"))  ."'><div class='link authservices'><div class='blocktitle'>".
            __("Link your account to","blaat_auth") .  "</div>".
            $linkHTML . "
         </div></form><form method=post>
         <div class='unlink authservices'><div class='blocktitle'>".
            __("Unlink your account from","blaat_auth") . "</div>".
           $unlinkHTML . "
         </div></form>";
         
  } else {
    // oauth user, no wp-user
    if (isset($_SESSION['oauth_id'])     && isset($_SESSION['oauth_token']) &&
        isset($_SESSION['oauth_expiry']) && isset($_SESSION['oauth_scope']) ){
        $service_id = $_SESSION['oauth_id'];
        $token      = $_SESSION['oauth_token'];
        $expiry     = $_SESSION['oauth_expiry'];
        $scope      = $_SESSION['oauth_scope'];
        $service    = $_SESSION['oauth_display'];
        echo "<div id='blaat_auth_local'>";
        printf(  "<p>" .  __("Please provide a local account to link to %s","blaat_auth") . "</p>" , $service);
        wp_login_form();
        echo "</div>";
      } else {
      printf(  "<p>" .  __("You need to be logged in to use this feature","blaat_auth") . "</p>");        
    } 
  }
}
//------------------------------------------------------------------------------
function blaat_auth_register_display() {
  if (is_user_logged_in()) {
    _e("You cannot register a new account since you are already logged in.","blaat_auth");
  } else {

    session_start();
    if (isset($_SESSION['oauth_id'])     && isset($_SESSION['oauth_token']) &&
        isset($_SESSION['oauth_expiry']) && isset($_SESSION['oauth_scope']) ){

      $service = $_SESSION['oauth_display'];
      printf( __("You are authenticated to %s","blaat_auth") , $service );
      echo "<br>";
      if (isset($_POST['username']) && isset($_POST['email'])) {
        $user_id = wp_create_user( $_POST['username'], $random_password, $_POST['email'] ) ;
        if (is_numeric($user_id)) {
          $reg_ok=true;
          $_SESSION['oauth_registered']=1;
          wp_set_current_user ($user_id);
          wp_set_auth_cookie($user_id);
          header("Location: ".site_url("/".get_option("login_page")));         
        } else {
          $reg_ok=false;
          $error = __($user_id->get_error_message());
        }
      } else {
        $reg_ok=false;
        // no username/password given
      } 
      if ($reg_ok){
      } else {
        if (isset($error)) {
          echo "<div class='error'>$error</div>";
        }
        _e("Please provide a username and e-mail address to complete your signup","blaat_auth");
         ?><form method=post>
          <table>
            <tr><td><?php _e("Username"); ?></td><td><input name='username'></td></tr>
            <tr><td><?php _e("E-mail Address"); ?></td><td><input name='email'></td></tr>
            <tr><td rowspan=2><button type=submit><?php _e("Register"); ?></button></td></tr>
          </table>
        </form>
        <?php
        printf( __("If you already have an account, please click <a href='%s'>here</a> to link it.","blaat_auth") , site_url("/".get_option("link_page")));
      }
    } else {
      if(isset($_POST['username']) && isset($_POST['email']) && isset($_POST['password'])){
        $user_id = wp_create_user( $_POST['username'], $_POST['password'] , $_POST['email'] ) ;
        if (is_numeric($user_id)) {
          $reg_ok=true;
          $_SESSION['oauth_registered']=1;
          wp_set_current_user ($user_id);
          wp_set_auth_cookie($user_id);
          header("Location: ".site_url("/".get_option("login_page")));         
        } else {
          $reg_ok=false;
          $error = __($user_id->get_error_message());
        }
      } else {
        // no username/password/email given
      } 
      if($reg_ok){
      } else {
        echo "<div id='blaat_auth_local'>";
        echo "<p>" .  __("Enter a username, password and e-mail address to sign up","blaat_auth") . "</p>" ; 
        ?>
        <form method=post>
          <table>
            <tr><td><?php _e("Username"); ?></td><td><input name='username'></td></tr>
            <tr><td><?php _e("Password"); ?></td><td><input type='password' name='password'></td></tr>
            <tr><td><?php _e("E-mail Address"); ?></td><td><input name='email'></td></tr>
            <tr><td rowspan=2><button type=submit><?php _e("Register"); ?></button></td></tr>
          </table>
        </form>
        <?php         
        echo "</div>";
        echo "<div id='blaat_auth_buttons'>";
        echo "<p>" . __("Sign up with","blaat_auth") . "</p>";
        global $wpdb;
        global $bs_oauth_plugin;
        global $_SERVER;
        $ACTION=site_url("/".get_option("login_page"));
        $table_name = $wpdb->prefix . "bs_oauth_services";
        $results = $wpdb->get_results("select * from $table_name where enabled=1 ",ARRAY_A);
        echo "<form>";
        foreach ($results as $result){
          $class = "btn-auth btn-".strtolower($result['client_name']);
          echo "<button class='$class' name=oauth_id type=submit value='".$result['id']."'>". $result['display_name']."</button>";
        }
        echo "</form>";
        echo "</div>";
      }
    } 
  }
}
//------------------------------------------------------------------------------
function blaat_auth_display($content) {
  $login_page    = get_option('login_page');
  $link_page     = get_option('link_page');
  $register_page = get_option('register_page');

  switch ($GLOBALS['post']->post_name) {
    case $login_page :
      blaat_auth_login_display();
      break;
    case $link_page :
      blaat_auth_link_display();
      break;
    case $register_page :
     blaat_auth_register_display();
      break;
    default : 
      return $content;
  }
}

wp_register_style('necolas-css3-social-signin-buttons', plugin_dir_url(__FILE__) . 'css/auth-buttons.css');
wp_enqueue_style( 'necolas-css3-social-signin-buttons');

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
//add_filter("login_form",   blaat_oauth_loginform );
//add_filter('authenticate', blaat_oauth_do_login,90  );
//add_action('personal_options_update', blaat_oauth_link_update);
//add_action("personal_options", blaat_oauth_linkform);


add_action("admin_menu", blaat_oauth_menu);
register_activation_hook(__FILE__, 'blaat_oauth_install');
add_filter( 'the_content', 'blaat_auth_display' );



?>
