<?php
//------------------------------------------------------------------------------
if (!isset($BSAUTH_SERVICES)) $BSAUTH_SERVICES = array();
//------------------------------------------------------------------------------
if (!function_exists("bsauth_register_options")) {
  function bsauth_register_options(){
    register_setting( 'bs_auth_pages', 'login_page' );
    register_setting( 'bs_auth_pages', 'register_page' );
    register_setting( 'bs_auth_pages', 'link_page' );
    register_setting( 'bs_auth_pages', 'logout_frontpage' );
    register_setting( 'bs_auth_pages', 'bsauth_custom_button' );
  }
}
//------------------------------------------------------------------------------
if (!function_exists("bsauth_buttons_sort")) {
  function bsauth_buttons_sort($a, $b) {
          return $a["display_order"] < $b["display_order"];
  }
}
//------------------------------------------------------------------------------
if (!function_exists("bsauth_login_display")) {
  function bsauth_login_display(){
    global $BSAUTH_SERVICES;

      if (isset($_SESSION['bsauth_link_id']) && is_user_logged_in()) {
        header("Location: ".site_url("/".get_option("link_page")). '?' . $_SERVER['QUERY_STRING']);
      }

      if (isset($_POST['bsauth_login'])){
        $login = explode ("-", $_POST['bsauth_login']);
        $service = $login[0];
        $login_id = $login[1];
        $_SESSION['bsauth_plugin']  = $service;
        $_SESSION['bsauth_login_id'] = $login_id;
      } else {
        $service  = $_SESSION['bsauth_plugin'];
        $login_id = $_SESSION['bsauth_login_id'];
      }

      if (isset($service) && isset($login_id)) {
        $service = $BSAUTH_SERVICES[$service];
        if ($service!=null) {
          $service->Login($login_id);
        } else {
          _e("Invalid service type","blaat_auth");
        }
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
      echo "<form method='post'>";

      $buttons = array();
      foreach ($BSAUTH_SERVICES as $service) {
        $buttons_new = array_merge ( $buttons , 
          $service->getButtons());
        $buttons=$buttons_new;
      }

      usort($buttons, "bsauth_buttons_sort"); 

      foreach ($buttons as $button) {
        echo bsauth_generate_button($button,"login");
        //echo $button['button'];
        //if (isset($button['css'])) echo $button['css'];
      }

      echo "</form>";
      echo "</div>";

      echo "<style>" . htmlspecialchars(get_option("bsauth_custom_button")) . "</style>";
    }
  }
}
//------------------------------------------------------------------------------
if (!function_exists("bsauth_register_display")) {
  function bsauth_register_display() {
    if (is_user_logged_in()) {
      _e("You cannot register a new account since you are already logged in.","blaat_auth");
    } else {
      session_start();
      if (isset($_SESSION['bsauth_register'])) {

        
        $register = explode ("-", $_SESSION['bsauth_register']);            

        $service = $_SESSION['bsauth_display'];
        printf( __("You are authenticated to %s","blaat_auth") , $service );
        echo "<br>";
        if (isset($_POST['username']) && isset($_POST['email'])) {
          $user_id = wp_create_user( $_POST['username'], $random_password, $_POST['email'] ) ;
          if (is_numeric($user_id)) {
            $reg_ok=true;
            $_SESSION['bsauth_registered']=1;
            echo "<pre>"; print_r($register); 
  
            wp_set_current_user ($user_id);
            wp_set_auth_cookie($user_id);
            global $BSAUTH_SERVICES;
            $serviceToLink = $BSAUTH_SERVICES[$register[0]];
            if ($serviceToLink) {
              $serviceToLink->Link($register[1]);
              header("Location: ".site_url("/".get_option("link_page")));  
              //header("Location: ".site_url("/".get_option("login_page")));  
            } else {
              echo "DEBUG:::: Unable to link your account"; // TODO message
            }
            unset($_SESSION['bsauth_register']);
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
          $action=htmlspecialchars(site_url("/".get_option("login_page")));
          echo "<form action='$action' method='post'>";        
          global $BSAUTH_SERVICES;

          $buttons = array();
          foreach ($BSAUTH_SERVICES as $service) {
            $buttons_new = array_merge ( $buttons , $service->getButtons() );
            $buttons=$buttons_new;
          }

          usort($buttons, "bsauth_buttons_sort"); 

          foreach ($buttons as $button) {
            //echo bsauth_generate_button($button,"register"); // TODO
            echo bsauth_generate_button($button,"login");
            //echo $button['button'];
            //if (isset($button['css'])) echo $button['css'];
          }

          echo "</form>";
          echo "</div>";
          echo "<style>" . htmlspecialchars(get_option("bsauth_custom_button")) . "</style>";
        }
      } 
    }
  }
}
//------------------------------------------------------------------------------
if (!function_exists("bsauth_generate_button")) {
  function bsauth_generate_button($button, $action){
  

      return "<button class='bs-auth-btn' name=bsauth_$action 
             type=submit value='".$button['plugin']."-".$button['id']."'>
             <span class='bs-auth-btn-logo 
             bs-auth-btn-logo-".$button['plugin']."-".$button['service']."'>
             </span><span class='bs-auth-btn-text'>".
             $button['display_name']."</span></button>";
  }
}


//------------------------------------------------------------------------------
if (!function_exists("bsauth_link_display")) {

  function bsauth_link_display(){
    session_start();



    global $BSAUTH_SERVICES;
    global $wpdb;
    $user = wp_get_current_user();
    echo "<style>" . htmlspecialchars(get_option("bsauth_custom_button")) . "</style>";
    if (is_user_logged_in()) {



      if (isset($_POST['bsauth_link'])) {
        $link = explode ("-", $_POST['bsauth_link']);
        $_SESSION['bsauth_link']=$_POST['bsauth_link'];
      }
      if (isset($_POST['bsauth_unlink'])) 
        $link = explode ("-", $_POST['bsauth_unlink']);
      if (isset($link)){
        $service = $link[0];
        $link_id = $link[1];
        $_SESSION['bsauth_plugin']  = $service;
        $_SESSION['bsauth_link_id'] = $link_id;
      }    

      

      if (isset($_SESSION['bsauth_plugin'])) $service = $_SESSION['bsauth_plugin'];
      if (isset($_SESSION['bsauth_link_id'])) $link_id = $_SESSION['bsauth_link_id'];


      if (isset($service) && isset($link_id)) {
        $service = $BSAUTH_SERVICES[$service];
        if ($service!=null) {
          // is SESSION required here?
          if (isset($_SESSION['bsauth_link'])) {
            //echo "link request<br>";
            $service->Link($link_id);
            // not yet...
            //unset($_SESSION['bsauth_link']);
          } else
          if (isset($_POST['bsauth_unlink'])) {
            //echo "link request<br>";
            $service->Unlink($link_id);
            unset($_POST['bsauth_unlink']);
          } //else echo "request not specified!";
        } else {
          // TODO error handling
          echo "service not registered!";     
        }
      } // else echo "no service/link id<br>"; 

 

      $buttonsLinked   = array();      
      $buttonsUnlinked = array();
      

      
      foreach ($BSAUTH_SERVICES as $service) {
        $buttons = $service->getButtonsLinked($user->ID);
      
        $buttonsLinked_new = array_merge ( $buttonsLinked , $buttons['linked'] );
        $buttonsUnlinked_new = array_merge ( $buttonsUnlinked , $buttons['unlinked'] );
        $buttonsLinked=$buttonsLinked_new;
        $buttonsUnlinked=$buttonsUnlinked_new;
      }

      usort($buttonsLinked, "bsauth_buttons_sort"); 
      usort($buttonsUnlinked, "bsauth_buttons_sort");           

      foreach ($buttonsLinked as $linked) {
        $unlinkHTML .= bsauth_generate_button($linked,"unlink");
      }

      foreach ($buttonsUnlinked as $unlinked) {
        $linkHTML .= bsauth_generate_button($unlinked,"link");
      }

      unset($_SESSION['bsoauth_id']);
      unset($_SESSION['bsauth_link']);
    

      echo "<form method=post><div class='link authservices'><div class='blocktitle'>".
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
          $service    = $_SESSION['bsauth_display'];
          echo "<div id='bsauth_local'>";
          printf(  "<p>" .  __("Please provide a local account to link to %s","blaat_auth") . "</p>" , $service);
          wp_login_form();
          echo "</div>";
        } else {
        printf(  "<p>" .  __("You need to be logged in to use this feature","blaat_auth") . "</p>");        
      } 
    }
  }
}
//------------------------------------------------------------------------------
if (!function_exists("bsauth_display")) {
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
}
//------------------------------------------------------------------------------
// go frontpage
// -- general auth related support

if (get_option("logout_frontpage")) {
  add_action('wp_logout','go_frontpage');
}

if (!function_exists("go_frontpage")) {
  function go_frontpage(){
    wp_redirect( home_url() );
    exit();
  }
}
//------------------------------------------------------------------------------


?>
