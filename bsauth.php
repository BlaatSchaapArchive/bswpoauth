<?php


if (!isset($BSAUTH_SERVICES)) $BSAUTH_SERVICES = array();
//------------------------------------------------------------------------------
function bsauth_register_options(){
  register_setting( 'bs_auth_pages', 'login_page' );
  register_setting( 'bs_auth_pages', 'register_page' );
  register_setting( 'bs_auth_pages', 'link_page' );
  register_setting( 'bs_auth_pages', 'logout_frontpage' );
  register_setting( 'bs_auth_pages', 'bsauth_custom_button' );
}
//------------------------------------------------------------------------------
    function bsauth_buttons_sort($a, $b) {
            return $a["display_order"] < $b["display_order"];
    }
//------------------------------------------------------------------------------
function bsauth_login_display(){
  global $BSAUTH_SERVICES;
  
  foreach ($BSAUTH_SERVICES as $service) {
    if (call_user_func($service['trigger_login'])) 
                                        call_user_func($service['do_login']);
  }
  if ( is_user_logged_in() ) {
    if (isset($_SESSION['bsauth_registered'])) 
      _e("Registered","blaat_auth");  
    else
      _e("Logged in","blaat_auth"); 
  } else {

    echo "<div id='bsauth_local'>";
    echo "<p>" .  __("Log in with a local account","blaat_auth") . "</p>" ; 
    wp_login_form();
    echo "</div>";

    echo "<div id='bsauth_buttons'>";
    echo "<p>" . __("Log in with","blaat_auth") . "</p>";

    $ACTION=site_url("/".get_option("login_page"));
    echo "<form>";

    $buttons = array();
    foreach ($BSAUTH_SERVICES as $service) {
      $buttons_new = array_merge ( $buttons , 
        call_user_func($service['buttons']) );
      $buttons=$buttons_new;
      echo "</pre>";
    }

    usort($buttons, "bsauth_buttons_sort"); 

    foreach ($buttons as $button) {
      echo $button['button'];
      if (isset($button['css'])) echo $button['css'];
    }

    echo "</form>";
    echo "</div>";

    echo "<style>" . htmlspecialchars(get_option("bsauth_custom_button")) . "</style>";
  }
}
//------------------------------------------------------------------------------
function bsauth_register_display() {
  if (is_user_logged_in()) {
    _e("You cannot register a new account since you are already logged in.","blaat_auth");
  } else {
    session_start();
    if (isset($_SESSION['bsauth_registering'])) {

      $service = $_SESSION['oauth_display'];
      printf( __("You are authenticated to %s","blaat_auth") , $service );
      echo "<br>";
      if (isset($_POST['username']) && isset($_POST['email'])) {
        $user_id = wp_create_user( $_POST['username'], $random_password, $_POST['email'] ) ;
        if (is_numeric($user_id)) {
          $reg_ok=true;
          $_SESSION['bsauth_registered']=1;
          unset($_SESSION['bsauth_registering']);
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
          $_SESSION['bsauth_registered']=1;
          wp_set_current_user ($user_id);
          wp_set_auth_cookie($user_id);
          header("Location: ".site_url("/".get_option("login_page")));         
        } else {
          $reg_ok=false;
          $error = __($user_id->get_error_message());
        }
      } else {
        $error= __("Some data is missing. You need to fill out all fields.","bsauth");
      } 
      if($reg_ok){
      } else {
        echo "<div id='bsauth_local'>";
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
        echo "<div id='bsauth_buttons'>";
        echo "<p>" . __("Sign up with","blaat_auth") . "</p>";
        $action=htmlspecialchars(get_option("login_page"));
        echo "<form action='$action'>";        
        global $BSAUTH_SERVICES;

        $buttons = array();
        foreach ($BSAUTH_SERVICES as $service) {
          $buttons_new = array_merge ( $buttons , 
            call_user_func($service['buttons']) );
          $buttons=$buttons_new;
          echo "</pre>";
        }

        usort($buttons, "bsauth_buttons_sort"); 

        foreach ($buttons as $button) {
          echo $button['button'];
          if (isset($button['css'])) echo $button['css'];
        }



        echo "</form>";
        echo "</div>";
        echo "<style>" . htmlspecialchars(get_option("bsauth_custom_button")) . "</style>";
      }
    } 
  }
}
//------------------------------------------------------------------------------
function bsauth_link_display(){
  session_start();
  global $wpdb;
  echo "<style>" . htmlspecialchars(get_option("bsauth_custom_button")) . "</style>";
  if (is_user_logged_in()) {
    if (isset($_POST['bsoauth_link'])){
        $_SESSION['bsoauth_link']=$_POST['bsoauth_link'];
        bsoauth_do_login($user);
      }


//-- unlink begin
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
//-- unlink end


//-- begin link
    $user = wp_get_current_user();
    if (isset($_SESSION['bsoauth_id'])     && isset($_SESSION['oauth_token']) &&
        isset($_SESSION['oauth_expiry']) && isset($_SESSION['oauth_scope']) ){
      $user_id    = $user->ID;
      $service_id = $_SESSION['bsoauth_id'];
      $token      = $_SESSION['oauth_token'];
      $expiry     = $_SESSION['oauth_expiry'];
      $scope      = $_SESSION['oauth_scope'];
      $service    = $_SESSION['oauth_display'];
      $table_name = $wpdb->prefix . "bs_oauth_sessions";
      $query = $wpdb->prepare("INSERT INTO $table_name (`user_id`, `service_id`, `token`, `expiry`, `scope` )
                                       VALUES      ( %d      ,  %d         ,  %s    , %s      , %s      )",
                                                    $user_id , $service_id , $token , $expiry , $scope  );
      $wpdb->query($query);
      unset($_SESSION['bsoauth_id']);
      unset($_SESSION['bsoauth_link']);
      unset($_SESSION['oauth_token']);
      unset($_SESSION['oauth_expiry']);
      unset($_SESSION['oauth_scope']);
      unset($_SESSION['oauth_display']);
      printf( __("Your %s account has been linked", "blaat_auth"), $service );
    } 
//-- end link


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

      if(!$available_service['customlogo_enabled'])
        $service=strtolower($available_service['client_name']);
      else {
        $service="custom-".$available_service['id'];
        echo "<style>.bs-auth-btn-logo-".$service." {background-image:url('" .$available_service['customlogo_url']."');}</style>";
      }


      if (in_array($available_service['id'],$linked)) {
        $unlinkHTML .= "<button class='bs-auth-btn' name=oauth_unlink type=submit value='".$available_service['id']."'><span class='bs-auth-btn-logo bs-auth-btn-logo-$service'></span><span class='bs-auth-btn-text'>". $available_service['display_name']."</span></button>";
      } else {
        $linkHTML .="<button class='bs-auth-btn' name=bsoauth_link type=submit value='".$available_service['id']."'><span class='bs-auth-btn-logo bs-auth-btn-logo-$service'></span><span class='bs-auth-btn-text'>". $available_service['display_name']."</span></button>";
      }
      unset($_SESSION['bsoauth_id']);
      unset($_SESSION['bsoauth_link']);
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
    if (isset($_SESSION['bsoauth_id'])     && isset($_SESSION['oauth_token']) &&
        isset($_SESSION['oauth_expiry']) && isset($_SESSION['oauth_scope']) ){
        $service_id = $_SESSION['bsoauth_id'];
        $token      = $_SESSION['oauth_token'];
        $expiry     = $_SESSION['oauth_expiry'];
        $scope      = $_SESSION['oauth_scope'];
        $service    = $_SESSION['oauth_display'];
        echo "<div id='bsauth_local'>";
        printf(  "<p>" .  __("Please provide a local account to link to %s","blaat_auth") . "</p>" , $service);
        wp_login_form();
        echo "</div>";
      } else {
      printf(  "<p>" .  __("You need to be logged in to use this feature","blaat_auth") . "</p>");        
    } 
  }
}
//------------------------------------------------------------------------------
function bsauth_display($content) {
  $login_page    = get_option('login_page');
  $link_page     = get_option('link_page');
  $register_page = get_option('register_page');

  switch ($GLOBALS['post']->post_name) {
    case $login_page :
      bsauth_login_display();
      break;
    case $link_page :
      bsauth_link_display();
      break;
    case $register_page :
     bsauth_register_display();
      break;
    default : 
      return $content;
  }
}



?>
