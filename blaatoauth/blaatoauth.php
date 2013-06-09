<?php
/*
Plugin Name: BlaatSchaap OAuth Plugin
Plugin URI: http://code.blaatschaap.be
Description: Log in with an OAuth Provider
Version: 0.1
Author: André van Schoubroeck
Author URI: http://andre.blaatschaap.be
License: 3 Clause BSD
*/


//require_once("oauth/oauth_client.php");
//require_once("oauth/http.php");
require_once("bs_wp_oauth.php");

require_once("bs_oauth_config.php");

function blaat_oauth_install() {
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


function blaat_oauth_menu() {
  add_options_page( 'BlaatSchaap OAuth Plugin Options', 'OAuth', 'manage_options', 'blaat_oauth_menur', 'blaat_oauth_options' );
}

function blaat_oauth_options() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	echo '<div class="wrap">';
	echo '<p>Here is where the form would go if I actually had options.</p>';
	echo '</div>';
        if ($_POST['add_service']) blaat_oauth_add_process();
        if ($_POST['delete_service']) blaat_oauth_delete_service();
        if ($_POST['update_service']) blaat_oauth_update_service();
        echo '<hr>';
        blaat_oauth_list_services();
        echo '<hr>';
        echo "<table><tr><td valign='top'><h2>Add built-in service:</h2><br>";
        blaat_oauth_add_form();
        echo "</td><td>&nbsp;&nbsp;</td><td valign='top'><h2>Add custom service:</h2><br>";
        blaat_oauth_add_custom_form();
        echo "</td></tr></table>";
}


function blaat_oauth_do_login($user){
//  if ( is_a($user, 'WP_User') ) { return $user; } // check if user is already logged in
  if ( $_POST['oauth_id'] ||/* $_SESSION['oauth_id'] ||*/ $_REQUEST['code']) {



    if ($_POST['oauth_id']) $_SESSION['oauth_id']=$_POST['oauth_id'];

    global $wpdb;
    $table_name = $wpdb->prefix . "bs_oauth_services";
    $query = $wpdb->prepare("SELECT * FROM $table_name  WHERE id = %d", $_SESSION['oauth_id']);
    $results = $wpdb->get_results($query,ARRAY_A);

    $result = $results[0];


    if ($result['custom_id']) {
      echo "custom service";
      
    } else {
      echo "built in service<br>";
      $client = new /* bsOAuthClient; */ oauth_client_class;

      $client->redirect_uri  = site_url('/wp-login.php');
      $client->server        = $result['client_name'];
      $client->client_id     = $result['client_id'];
      $client->client_secret = $result['client_secret'];
      $client->scope         = $result['default_scope'];
      

    }

    if(($success = $client->Initialize())){
      echo "init<br>";
      if(($success = $client->Process())){
        echo "process<br>";
        if(strlen($client->access_token)){
          echo "token: $client->access_token";
          //echo "<pre>"; print_r($client); print_r($_SESSION); die ("</pre>");
          if ( is_a($user, 'WP_User') ) { // we are logged in, link OAuth to user
            $user_id    = $user->ID;
            $service_id = $_SESSION['oauth_id'];
            $token = $client->access_token;
            $expiry = $client->access_token_expiry;            
            $table_name = $wpdb->prefix . "bs_oauth_sessions";
            $scope      = $client->scope;

          
            $query = $wpdb->prepare("INSERT INTO $table_name (`user_id`, `service_id`, `token`, `expiry`, `scope` )
                                                 VALUES      ( %d      ,  %d         ,  %s    , %s      , %s      )",
                                                              $user_id , $service_id , $token , $expiry , $scope  );
            $wpdb->query($query);
            return $user;
            
          } else {
            $service_id = $_SESSION['oauth_id'];
            $token = $client->access_token;
            $table_name = $wpdb->prefix . "bs_oauth_sessions";

            $query = $wpdb->prepare("SELECT `user_id` FROM $table_name WHERE `service_id` = %d AND `token` = %d",$service_id,$token);  
            $results = $wpdb->get_results($query,ARRAY_A);
            $result = $results[0];
            if ($result) {
              return new WP_User( $result['user_id']);
            } else {
              $_SESSION['oauth_signup']=1;
              header("Location: /wp-login.php?action=register&oauth_signup=1");
              //echo "This user is not linked";
            }


          }
          $success = $client->Finalize($success);
	} else {
           echo( "<br>NO TOKEN</br>");
        }
      } else {
         echo ("<br>processing error<br>". $client->error);
      }
    } else echo ("initialisation error");
  } else {
    return $user;
  }
 
}

function blaat_oauth_loginform () {
  echo "<div>";
//  echo "BlaatSchaap OAuth Plugin:<br>";
  global $wpdb;
  global $bs_oauth_plugin;
  global $_SERVER;
  $ACTION=$_SERVER['REQUEST_URI'];// . '?' . $_SERVER['QUERY_STRING'];

  $table_name = $wpdb->prefix . "bs_oauth_services";

  $results = $wpdb->get_results("select * from $table_name where enabled=1 ",ARRAY_A);

  foreach ($results as $result){
    echo "<button name=oauth_id type=submit value='".$result['id']."'>". $result['display_name']."</button>";
  }
 
  echo "</div>";
}


function blaat_oauth_unlinkform(){
  global $wpdb;
  $table_name_sess = $wpdb->prefix . "bs_oauth_sessions";
  $table_name_serv = $wpdb->prefix . "bs_oauth_services";
  $user = wp_get_current_user();
  $user_id = $user->id;
  $query = "select ".$table_name_sess.".id , display_name from $table_name_sess " . 
                     "join $table_name_serv on ". 
                     $table_name_sess. ".service_id = ".
                     $table_name_serv. ".id ".
                     "where `user_id` =  $user_id ";
  $results = $wpdb->get_results($query, ARRAY_A);
  foreach ($results as $result){
    echo "<button name=oauth_unlink type=submit value='".$result['id']."'>". $result['display_name']."</button>";
  }
}

function blaat_oauth_linkform() {
  echo "</table>";
  echo "<h3>BlaatSchaap OAuth options</h3>";
  echo "<table class=form-table>";
  echo "<tr><th>Link your account with</td><td>";
  blaat_oauth_loginform();
  echo "</td></tr>";
  echo "<tr><th>Unlink your account from</td><td>";
  blaat_oauth_unlinkform();
  echo "</td></tr>";
}


add_filter("login_form",   blaat_oauth_loginform );
add_filter('authenticate', blaat_oauth_do_login,90  );


add_action('personal_options_update', blaat_oauth_link_update);
 
function blaat_oauth_link_update($user_id) {
  if ($_POST['oauth_id']) {
    if ( current_user_can('edit_user',$user_id) ) {
      $user=wp_get_current_user();
      blaat_oauth_do_login($user);
    }
  }
  if ($_POST['oauth_unlink']) {
    if ( current_user_can('edit_user',$user_id) ) {
      global $wpdb;
      $table_name =  $wpdb->prefix . "bs_oauth_sessions";
      $query = $wpdb->prepare("Delete from $table_name where id = %d" , $_POST['oauth_unlink']);
      $wpdb->query($query);
    }
  }
}

add_action("admin_menu", blaat_oauth_menu);
add_action("personal_options", blaat_oauth_linkform);


function blaat_oauth_signup_message($message){
    global $wpdb;
    $table_name = $wpdb->prefix . "bs_oauth_services";
    $query = $wpdb->prepare("SELECT display_name FROM $table_name  WHERE id = %d", $_SESSION['oauth_id']);    
    $results = $wpdb->get_results($query, ARRAY_A);  
    $result = $results[0];
    $service = $result['display_name'];
    $signupmessage = "This $service account is not linked to any user. Please sign up by providing a username and email address";
    return '<p class="message register">' . $signupmessage . '</p>';
}

if ($_GET['oauth_signup']) {
  add_action('login_message', 'blaat_oauth_signup_message');
}

register_activation_hook(__FILE__, 'blaat_oauth_install');

?>
