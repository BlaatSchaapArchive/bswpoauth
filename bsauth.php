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

    register_setting( 'bs_auth_pages', 'bs_auth_hide_local' );

    register_setting( 'bs_auth_pages', 'bs_auth_signup_user_url' );
    register_setting( 'bs_auth_pages', 'bs_auth_signup_user_email' );
    register_setting( 'bs_auth_pages', 'bs_auth_signup_display_name' );
    register_setting( 'bs_auth_pages', 'bs_auth_signup_nickname' );
    register_setting( 'bs_auth_pages', 'bs_auth_signup_first_name' );
    register_setting( 'bs_auth_pages', 'bs_auth_signup_last_name' );
    register_setting( 'bs_auth_pages', 'bs_auth_signup_description' );
    register_setting( 'bs_auth_pages', 'bs_auth_signup_jabber' );
    register_setting( 'bs_auth_pages', 'bs_auth_signup_aim' );
    register_setting( 'bs_auth_pages', 'bs_auth_signup_yim' );


  }
}
//------------------------------------------------------------------------------
if (!function_exists("bsauth_buttons_sort")) {
  function bsauth_buttons_sort($a, $b) {
    if ($a["order"] == $b["order"]) return 0;
    return ($a["order"] < $b["order"]) ? -1 : 1;
  }
}
//------------------------------------------------------------------------------
if (!function_exists("bsauth_login_display")) {
  function bsauth_login_display(){
    global $BSAUTH_SERVICES;

      //echo "DEBUG<pre>"; print_r($_SESSION); echo "</pre>";

      if (isset($_SESSION['bsauth_link']) && is_user_logged_in()) {
        // Forwarding the QUERY_STRING to the link page is required for the OAuth->Link() class to function.
        // For now... this will remain in here, however, should be replaced in the future
        // as we're moving to a single page solution, this should be ok for now
        header("Location: ".site_url("/".get_option("link_page")). '?' . $_SERVER['QUERY_STRING']);
        
      }

      if ( !is_user_logged_in() ) {
        if ( isset($_POST['bsauth_login'])){
          $login = explode ("-", $_POST['bsauth_login']);
          $service = $login[0];
          $login_id = $login[1];
          $_SESSION['bsauth_plugin']  = $service;
          $_SESSION['bsauth_login_id'] = $login_id;
        } else {
          if (isset($_SESSION['bsauth_plugin'])) 
            $service  = $_SESSION['bsauth_plugin'];
          if (isset($_SESSION['bsauth_login_id'])) 
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
      }

    if ( is_user_logged_in() ) {
      if (isset($_SESSION['bsauth_registered'])) {
        _e("Registered","blaat_auth");  
        unset ($_SESSION['bsauth_registered']);
        unset( $_SESSION['bsauth_fetch_data']);
        unset( $_SESSION['bsauth_register_auto']);
        unset( $_SESSION['bsauth_plugin']);
        unset( $_SESSION['bsauth_login_id']);
      } else {
          _e("Logged in","blaat_auth"); 
        }
    } else {

      if (!(get_option("bs_auth_hide_local"))) {
        echo "<div id='bsauth_local'>";
        echo "<p>" .  __("Log in with a local account","blaat_auth") . "</p>" ; 
        wp_login_form();
        echo "</div>";
      }

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

    //echo "DEBUG<pre>"; print_r($_SESSION); echo "</pre>";

    global $BSAUTH_SERVICES;

    if (isset($_POST['cancel'])) {
      unset($_SESSION['bsauth_register']);
      unset($_SESSION['bsauth_plugin']);
      unset($_SESSION['bsauth_login_id']);
    }
  

    if (is_user_logged_in()) {
      _e("You cannot register a new account since you are already logged in.","blaat_auth");
    } else {
      blaat_session_start();
      $reg_ok=false;

      if (isset($_SESSION['bsauth_register'])) {

        
        $register = explode ("-", $_SESSION['bsauth_register']);            

        $service = $_SESSION['bsauth_display'];
        printf( __("You are authenticated to %s","blaat_auth") , $service );
        echo "<br>";
        

        if ($_SESSION['bsauth_fetch_data']) {
          $service = $BSAUTH_SERVICES[$register[0]];
          if($service) {
            $new_user = $service->getRegisterData();
          } 
        } 

        if (isset($_POST['username']) && isset($_POST['email'])) {
          if (!isset($new_user)) $new_user = array();
          $new_user['user_login']= $_POST['username'];
          $new_user['user_email']= $_POST['email'];
        }

        //if (isset($_POST['username']) && isset($_POST['email'])) {
        if (isset($new_user) && (isset($new_user['user_login']) && 
            ( isset($new_user['user_email']) || (get_option("bs_auth_signup_user_email")!="Required") )
            )
            && ( $_POST['register'] || $_SESSION['bsauth_register_auto'] )) {
          $new_user['user_pass'] = wp_hash_password(wp_generate_password());
          $user_id = wp_insert_user($new_user);
          if (is_numeric($user_id)) {
            unset($_SESSION['bsauth_register']);
            $reg_ok=true;
            $_SESSION['bsauth_registered']=1;
            wp_set_current_user ($user_id);
            wp_set_auth_cookie($user_id);
            global $BSAUTH_SERVICES;
            $serviceToLink = $BSAUTH_SERVICES[$register[0]];
            if ($serviceToLink) {
              $serviceToLink->Link($register[1]);
              header("Location: ".site_url("/".get_option("login_page")));  
            } else {
              echo "DEBUG:::: Unable to link your account"; // TODO message
            }
            //unset($_SESSION['bsauth_register']); // moved up
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
           ?><form method='post'>
            <table>
              <tr><td><?php _e("Username"); ?></td><td><input name='username' value='<?php if (isset($new_user['user_login'])) echo htmlspecialchars($new_user['user_login']);?>'</td></tr>
              <?php if (get_option("bs_auth_signup_user_email")!="Disabled") { ?>
              <tr><td><?php _e("E-mail Address"); ?></td><td><input name='email' value='<?php if (isset($new_user['user_email'])) echo htmlspecialchars($new_user['user_email']);?>' ></td></tr>
              <?php } ?>
              <tr><td><button name='cancel' type=submit><?php _e("Cancel"); ?></button></td><td><button name='register' value='1' type=submit><?php _e("Register"); ?></button></td></tr>
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
          $error= __("Some data is missing. You need to fill out all fields.","blaat_auth");
        } 
        if($reg_ok){
        } else {
          if (!(get_option("bs_auth_hide_local"))) {
            echo "<div id='bsauth_local'>";
            echo "<p>" .  __("Enter a username, password and e-mail address to sign up","blaat_auth") . "</p>" ; 
            ?>
            <form method=post>
              <table>
                <tr><td><?php _e("Username"); ?></td><td><input name='username'></td></tr>
                <tr><td><?php _e("Password"); ?></td><td><input type='password' name='password'></td></tr>
                <tr><td><?php _e("E-mail Address"); ?></td><td><input name='email'></td></tr>
                <tr><td></td><td><button type=submit><?php _e("Register"); ?></button></td></tr>
              </table>
            </form>
            <?php         
            echo "</div>";
          }

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
      
      if (isset($button['logo']))
        $style="style='background-image:url(\"" .$button['logo']. "\");'";
      else
        $style="";

      return "<button class='bs-auth-btn' name=bsauth_$action 
             type=submit value='".$button['plugin']."-".$button['id']."'>
             <span $style class='bs-auth-btn-logo 
             bs-auth-btn-logo-".$button['plugin']."-".$button['service']."'>
             </span><span class='bs-auth-btn-text'>".
             $button['display_name']."</span></button>";
  }
}


//------------------------------------------------------------------------------
if (!function_exists("bsauth_link_display")) {

  function bsauth_link_display(){
    //blaat_session_start();

    //echo "DEBUG<pre>"; print_r($_SESSION); echo "</pre>";


    global $BSAUTH_SERVICES;
    global $wpdb;
    $user = wp_get_current_user();
    echo "<style>" . htmlspecialchars(get_option("bsauth_custom_button")) . "</style>";
    if (is_user_logged_in()) {




      if (isset($_SESSION['bsauth_link'])) {
        $link = explode ("-", $_SESSION['bsauth_link']);
      }
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
      

      
      foreach ($BSAUTH_SERVICES as $bs_service) {
        $buttons = $bs_service->getButtonsLinked($user->ID);
      
        $buttonsLinked_new = array_merge ( $buttonsLinked , $buttons['linked'] );
        $buttonsUnlinked_new = array_merge ( $buttonsUnlinked , $buttons['unlinked'] );
        $buttonsLinked=$buttonsLinked_new;
        $buttonsUnlinked=$buttonsUnlinked_new;
      }

      usort($buttonsLinked, "bsauth_buttons_sort"); 
      usort($buttonsUnlinked, "bsauth_buttons_sort");           

      $unlinkHTML="";
      $linkHTML="";

      foreach ($buttonsLinked as $linked) {
        $unlinkHTML .= bsauth_generate_button($linked,"unlink");
      }

      foreach ($buttonsUnlinked as $unlinked) {
        $linkHTML .= bsauth_generate_button($unlinked,"link");
      }

      /*
      unset($_SESSION['bsoauth_id']);
      unset($_SESSION['bsauth_link']);
      */

      echo "<form method='post' action='". site_url("/".get_option("link_page")) ."'><div class='link authservices'><div class='blocktitle'>".
              __("Link your account to","blaat_auth") .  "</div>".
              $linkHTML . "
           </div></form><form method=post>
           <div class='unlink authservices'><div class='blocktitle'>".
              __("Unlink your account from","blaat_auth") . "</div>".
             $unlinkHTML . "
           </div></form>";
           
    } else {
      if (!(get_option("bs_auth_hide_local"))) {
        // oauth user, no wp-user
        if (isset($_SESSION['bsauth_register']) ) {

            $service = $_SESSION['bsauth_display'];
            $_SESSION['bsauth_link']=$_SESSION['bsauth_register'] ;
            echo "<div id='bsauth_local'>";
            printf(  "<p>" .  __("Please provide a local account to link to %s","blaat_auth") . "</p>" , $service);
            wp_login_form();
            echo "</div>";
          } else {
          printf(  "<p>" .  __("You need to be logged in to use this feature","blaat_auth") . "</p>");        
        } 
      } else {
        printf(  "<p>" .  __("This feature has been disabled","blaat_auth") . "</p>");        
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
// When a WordPress user is deleted, remove any external linked accounts
if (!function_exists("bsauth_delete_user")) {
  function bsauth_delete_user($user_id) {
    global $BSAUTH_SERVICES;
    // For each service, delete the linked service
    foreach ($BSAUTH_SERVICES as $service) {
      $service->Delete($user_id);
    }
  }
  // Call the delete user function when a WordPress user is deleted.
  add_action( 'deleted_user', 'bsauth_delete_user' );
}

//------------------------------------------------------------------------------
if (!function_exists("bsauth_generate_select_signup_requirement")) {
  function bsauth_generate_select_signup_requirement($option_field){
    $option_value = get_option($option_field);
    echo "<select name='" . htmlspecialchars($option_field) . "'>";

    $selected = ($option_value=="Disabled") ? "selected='selected'" : "";
    echo "<option value='Disabled' $selected>";
    _e("Disabled" , "blaat_auth");
    echo  "</option>";

    $selected = ($option_value=="Optional") ? "selected='selected'" : "";
    echo "<option value='Optional' $selected>";
    _e("Optional" , "blaat_auth");
    echo  "</option>";

    $selected = ($option_value=="Required") ? "selected='selected'" : "";
    echo "<option value='Required' $selected>";
    _e("Required" , "blaat_auth");
    echo  "</option>";

    echo "</select>";
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
    settings_fields( 'bs_auth_pages' ); 

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


    echo '<tr><th>';
    _e("Hide local accounts", "blaat_auth") ;
    echo "</th><td>";
    $checked = get_option('bs_auth_hide_local') ? "checked" : "";
    echo "<input type=checkbox name='bs_auth_hide_local' value='1' $checked>";
    echo "</td></tr>";

    echo '<tr><th>';
    _e("Require e-mail address", "blaat_auth") ;
    echo "</th><td>";
    bsauth_generate_select_signup_requirement("bs_auth_signup_user_email");      
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
//------------------------------------------------------------------------------
if (!function_exists("blaat_plugins_auth_page")) {
  function blaat_plugins_auth_page(){
    echo '<div class="wrap">';
    echo '<h2>';
    _e("BlaatSchaap WordPress Authentication Plugins","blaat_auth");
    echo '</h2>';
    echo '<form method="post" action="options.php">';
    settings_fields( 'bs_auth_pages' ); 

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


    /* The next two options are introduced for the custom plugin VATSIM */

    echo '<tr><th>';
    _e("Hide local accounts", "blaat_auth") ;
    echo "</th><td>";
    $checked = get_option('bs_auth_hide_local') ? "checked" : "";
    echo "<input type=checkbox name='bs_auth_hide_local' value='1' $checked>";
    echo "</td></tr>";

    echo '<tr><th>';
    _e("Require e-mail address", "blaat_auth") ;
    echo "</th><td>";
    bsauth_generate_select_signup_requirement("bs_auth_signup_user_email");      
    echo "</td></tr>";


    echo '<tr><th>'. __("Custom Button CSS","blaat_auth") .'</th><td>';
    echo "<textarea cols=70 rows=15 id='bsauth_custom_button_textarea' name='bsauth_custom_button'>";
    echo htmlspecialchars(get_option("bsauth_custom_button"));
    echo "</textarea>";
    echo '</td></tr>';

    /* 
        Preparations for future support
        Note: string generation cannot be automised
        Meaning, outer code should be moved back here, 
                          inner code done by function.
    blaat_plugins_auth_page_signup_option("user_url");
    blaat_plugins_auth_page_signup_option("user_email");
    blaat_plugins_auth_page_signup_option("display_name");
    blaat_plugins_auth_page_signup_option("nickname");
    blaat_plugins_auth_page_signup_option("first_name");
    blaat_plugins_auth_page_signup_option("last_name");
    blaat_plugins_auth_page_signup_option("description");
    blaat_plugins_auth_page_signup_option("jabber");
    blaat_plugins_auth_page_signup_option("aim");
    blaat_plugins_auth_page_signup_option("yim");
    */

    echo '</table><input name="Submit" type="submit" value="';
    echo  esc_attr_e('Save Changes') ;
    echo '" ></form></div>';

  }
}
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
// go frontpage
// -- general auth related support

if (!function_exists("bsauth_logout_hook")){
  function bsauth_logout_hook() {
    session_destroy();
    if (get_option("logout_frontpage")) go_frontpage();
  }
}


if (!function_exists("go_frontpage")) {
  function go_frontpage(){
    wp_redirect( home_url() );
    exit();
  }
}

  add_action('wp_logout','bsauth_logout_hook');
//------------------------------------------------------------------------------


?>
