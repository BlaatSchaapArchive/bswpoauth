<?php



//------------------------------------------------------------------------------
if (!isset($BSAUTH_SERVICES)) $BSAUTH_SERVICES = array();

//------------------------------------------------------------------------------
if (!function_exists("bsauth_buttons_sort")) {
  function bsauth_buttons_sort($a, $b) {
    if ($a["order"] == $b["order"]) return 0;
    return ($a["order"] < $b["order"]) ? -1 : 1;
  }
}

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
if (!function_exists("bsauth_display")) {
  function bsauth_display($content) {
    $login_page    = get_option('login_page');
    $link_page     = get_option('link_page');
    $register_page = get_option('register_page');

    switch ($GLOBALS['post']->post_name) {
      /*
      case $login_page :
        bsauth_login_display();
        break;
      case $link_page :
        bsauth_link_display();
        break;
      case $register_page :
       bsauth_register_display();
        break;
        */
      case $login_page :
      case $link_page :
      case $register_page :
       bsauth_view();
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
  // use blaat_option_req3_generate() instead?
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
if (!function_exists("bsauth_view")) {
  function bsauth_view(){
    global $BSAUTH_SERVICES;
    global $wpdb;

    if (isset($_SESSION['bsauth_display_message'])) {
      echo "<div class=bsauth_message>".$_SESSION['bsauth_display_message']."</div>";
      unset($_SESSION['bsauth_display_message']);
    }
    $user = wp_get_current_user();

      if (get_option("bs_debug")) {
        echo "DEBUG SESSION<pre>"; print_r($_SESSION); echo "</pre>";
        echo "DEBUG POST<pre>"; print_r($_POST); echo "</pre>";
        echo "DEBUG URL:<pre>" . blaat_get_current_url() . "</pre>";
      }

      $logged    = is_user_logged_in();
      $logging   = isset($_SESSION['bsauth_login'])   || isset($_POST['bsauth_login']);
      $linking   = isset($_SESSION['bsauth_link'])    || isset($_POST['bsauth_link']);
      $regging   = isset($_SESSION['bsauth_register'])|| isset($_POST['bsauth_register']);

      if ($regging) {
        $regging_local = (isset($_POST['bsauth_register']) 
                          && $_POST['bsauth_register']=="local") ||
                          (isset($_SESSION['bsauth_register']) 
                          && $_SESSION['bsauth_register']=="local");

      } else $regging_local = false;

      $unlinking = isset($_POST['bsauth_unlink']);


      // begin not loggedin, logging, linking,regging
      if (! ($logged || $logging || $linking || $regging) ){

        if (!(get_option("bs_auth_hide_local"))) {
          echo "<div id='bsauth_local'>";
          echo "<p>" .  __("Log in with a local account","blaat_auth") . "</p>" ; 
          wp_login_form();
          ?>
          <form method='post' action='<?php echo blaat_get_current_url(); ?>'>
          
          <button type='submit' value='local' name='bsauth_register'><?php
            _e("Register"); ?></button> 
          </form> 
          <?php
          echo "</div>";
        }

        echo "<div id='bsauth_buttons'>";
        echo "<p>" . __("Log in with","blaat_auth") . "</p>";
        echo "<form action='".blaat_get_current_url()."' method='post'>";

        $buttons = array();
        foreach ($BSAUTH_SERVICES as $service) {
          $buttons_new = array_merge ( $buttons , 
            $service->getButtons());
          $buttons=$buttons_new;
        }

        usort($buttons, "bsauth_buttons_sort"); 

        foreach ($buttons as $button) {
          echo bsauth_generate_button($button,"login");
        }

        echo "</form>";
        echo "</div>";
        echo "<style>" . htmlspecialchars(get_option("bsauth_custom_button")) . "</style>";
      }
      // end not loggedin, logging, linking,regging      








      // begin logged in (show linking)
      if ( $logged) {

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

        echo "<form action='".blaat_get_current_url()."' method='post'><div class='link authservices'><div class='blocktitle'>".
                __("Link your account to","blaat_auth") .  "</div>".
                $linkHTML . "
             </div></form><form action='".blaat_get_current_url()."' method=post>
             <div class='unlink authservices'><div class='blocktitle'>".
                __("Unlink your account from","blaat_auth") . "</div>".
               $unlinkHTML . "
             </div></form>";

      }
      // end logged in (show linking)


      if ($regging && !$linking && !$regging_local ) {
        if (isset($_SESSION['new_user'])) $new_user = $_SESSION['new_user'];
        _e("Please provide a username and e-mail address to complete your signup","blaat_auth");
         ?><form action='<?php echo blaat_get_current_url()?>'method='post'>
          <table>
            <tr><td><?php _e("Username"); ?></td><td><input name='username' value='<?php if (isset($new_user['user_login'])) echo htmlspecialchars($new_user['user_login']);?>'</td></tr>
            <?php if (get_option("bs_auth_signup_user_email")!="Disabled") { ?>
            <tr><td><?php _e("E-mail Address"); ?></td><td><input name='email' value='<?php if (isset($new_user['user_email'])) echo htmlspecialchars($new_user['user_email']);?>' ></td></tr>
            <?php } ?>
            <tr><td><button name='cancel' type=submit><?php _e("Cancel"); ?></button></td><td><button name='register' value='1' type=submit><?php _e("Register"); ?></button></td></tr>
            <tr><td></td><td><button name='bsauth_link' value='<?php echo htmlspecialchars($_SESSION['bsauth_register']); ?>' type='submit'><?php _e("Link to existing account","blaat_auth"); ?></button></td></td></tr>
          </table>
        </form>
        <?php
        //printf( __("If you already have an account, please click <a href='%s'>here</a> to link it.","blaat_auth") , site_url("/".get_option("link_page")));
      }


      if ($regging && $linking && !$regging_local) {
        $service = $_SESSION['bsauth_display'];
        echo "<div id='bsauth_local'>";
        printf( "<p>" . __("Please provide a local account to link to %s","blaat_auth") . "</p>" , $service);
        wp_login_form();
        echo "</div>";
      }


      // begin regging 
      if ($regging_local) {

        if (!(get_option("bs_auth_hide_local"))) {
          echo "<div id='bsauth_local'>";
          echo "<p>" .  __("Enter a username, password and e-mail address to sign up","blaat_auth") . "</p>" ; 
          ?>
          <form ection='<?php blaat_get_current_url(); ?>' method=post>
            <table>
              <tr><td><?php _e("Username"); ?></td><td><input name='username'></td></tr>
              <tr><td><?php _e("Password"); ?></td><td><input type='password' name='password'></td></tr>
              <tr><td><?php _e("E-mail Address"); ?></td><td><input name='email'></td></tr>
              <tr><td><button name='cancel' type=submit><?php _e("Cancel"); ?></button></td><td><button name='register'  type='submit'><?php _e("Register"); ?></button></td></tr>
            </table>
          </form>
          <?php         
          echo "</div>";
        }

        echo "<style>" . htmlspecialchars(get_option("bsauth_custom_button")) . "</style>";



      }

      // end regging

    }
}


if (!(function_exists("bsauth_process"))){
  function bsauth_process(){

      // TODO rename $login_id to $service_id
      if (!isset($_SESSION['count'])) $_SESSION['count']=0;
      $_SESSION['count']++;

      global $BSAUTH_SERVICES;
      global $wpdb;
      $user = wp_get_current_user();

      $logged    = is_user_logged_in();
      $logging   = isset($_SESSION['bsauth_login'])   || isset($_POST['bsauth_login']);
      $linking   = isset($_SESSION['bsauth_link'])    || isset($_POST['bsauth_link']);
      $regging   = isset($_SESSION['bsauth_register'])|| isset($_POST['bsauth_register']);
      $unlinking = isset($_POST['bsauth_unlink']);

      if ($regging && isset($_POST['cancel'])) {
        unset($_SESSION['bsauth_register']);
        unset($_SESSION['bsauth_plugin']);
        unset($_SESSION['bsauth_login_id']);
        $regging = false;
      }

      if ($regging && $linking) {
        $_SESSION['bsauth_link']=$_SESSION['bsauth_register'] ;
      }
      
      if ($regging && $logged)  {
          unset($_SESSION['bsauth_register']);
      }

      // begin linking 
      if ( $logged && $linking) {
        if (isset($_SESSION['bsauth_link'])) {
          $link = explode ("-", $_SESSION['bsauth_link']);
          unset($_SESSION['bsauth_link']);
        }
        if (isset($_POST['bsauth_link'])) {
          $link = explode ("-", $_POST['bsauth_link']);
          $_SESSION['bsauth_link']=$_POST['bsauth_link'];
        }

        $plugin_id = $link[0];
        $link_id = $link[1];
        $plugin = $BSAUTH_SERVICES[$plugin_id];

        $status = $plugin->Link($link_id);
        switch ($status) {
          case AuthStatus::LinkSuccess :
            $_SESSION['bsauth_display_message'] = sprintf( __("Your %s account has been linked", "blaat_auth"), $_SESSION['display_name'] );
            if ($regging) unset($_SESSION['bsauth_register']);
            break;
          case AuthStatus::LinkInUse :
            $_SESSION['bsauth_display_message'] = sprintf( __("Your %s account has is already linked to another local account", "blaat_auth"), $_SESSION['display_name'] );
            break;
          default : 
            $_SESSION['bsauth_display_message'] = "Unkown status while attempting to link" . $status;
            //$_SESSION['debug_status'] = $status;
        }

      }
      // end linkin

      // begin unlinking 
      if ( $logged && $unlinking) {
        $unlink = explode ("-", $_POST['bsauth_unlink']);

        $plugin_id = $unlink[0];
        $link_id = $unlink[1];
        $plugin = $BSAUTH_SERVICES[$plugin_id];
        if ($plugin->Unlink($link_id)) {
          $_SESSION['bsauth_display_message'] = sprintf( __("You are now unlinked from %s.", "blaat_auth"), $_SESSION['display_name'] );
        } else {
          // unlink error
        }
        unset($_SESSION['bsauth_unlink']);
      }
      // end unlinking





      // begin loggin in
      if ($logging  && !$logged) {
        if ( isset($_POST['bsauth_login'])){
          $login = explode ("-", $_POST['bsauth_login']);
          $_SESSION['bsauth_login']=$_POST['bsauth_login'];
        } else {
          $login = explode ("-", $_SESSION['bsauth_login']);
        }
        $plugin_id = $login[0];
        $login_id = $login[1];


        if (isset($plugin_id) && isset($login_id)) {
          $service = $BSAUTH_SERVICES[$plugin_id];
          if ($service!=null) {
            $_SESSION['bsauth_display_message'] =$service->Login($login_id);
            $result = $service->Login($login_id);
            switch ($result) {
              case AuthStatus::Busy :
                break;
              case AuthStatus::LoginSuccess :
                //logged in
                unset($_SESSION['bsauth_login']);
                unset($_SESSION['bsauth_plugin']);
                unset($_SESSION['bsauth_login_id']);
                unset($_SESSION['bsauth_register_userinfo']);
                $userinfo = wp_get_current_user();
                if (strlen($userinfo->display_name)) {
                  $display_name = $userinfo->display_name;
                } else {
                  $display_name = $userinfo->display_login;
                }
                $_SESSION['bsauth_display_message'] = sprintf( __("Welcome back, %s.","blaat_auth"), $display_name); 
                break;
              case AuthStatus::LoginMustRegister :
                // does this work now?
                $_SESSION['bsauth_register'] = $_SESSION['bsauth_login'];
                unset($_SESSION['bsauth_login']);
                $_SESSION['bsauth_display_message'] = "TODO:: EXTERNAL SIGNUP"; 
                break;
              false :
                $_SESSION['bsauth_display_message'] = "Unkown error";
              default : 
                $_SESSION['bsauth_display_message'] = "Unkown status while attempting to log in";
                //$_SESSION['debug_status'] = $result;

              }
            
          } else {
            $_SESSION['bsauth_display_message'] = __("Invalid plugin","blaat_auth");
          }
        } else {
          $_SESSION['bsauth_display_message'] = __("Invalid request","blaat_auth");
        }
      }
      // end loggin in



      // begin regging
      if ($regging && !$logged) {
        if (!isset($_SESSION['bsauth_register']) ) {
          $_SESSION['bsauth_register'] = $_POST['bsauth_register'];
        }
        $register = explode ("-", $_SESSION['bsauth_register']);
        
        $plugin_id = $register[0];
        if ($plugin_id=="local") {
          $local=true;
        } else {
          $login_id = $register[1];
          $local=false;
        }

        
        if ($_SESSION['bsauth_fetch_data']) {
          $service = $BSAUTH_SERVICES[$plugin_id];
          if($service) {
            $new_user = $service->getRegisterData($login_id);
          } 
        } 
        
        if (!isset($new_user)) $new_user = array()  ;

        if (isset($_POST['username'])) $new_user['user_login']= $_POST['username'];
        if (isset($_POST['email']))    $new_user['user_email']= $_POST['email'];
        if (isset($_POST['password'])) $new_user['user_pass']= wp_hash_password($_POST['password']);

        if (isset($new_user)) $_SESSION['bsauth_register_userinfo'] = $new_user;

        if (isset($new_user) && (isset($new_user['user_login']) && 
            ( isset($new_user['user_email']) || (get_option("bs_auth_signup_user_email")!="Required") )
            ) && ( isset($_POST['register']) || $_SESSION['bsauth_register_auto'] )) {
          if (!isset($new_user['user_pass'])) $new_user['user_pass'] = wp_hash_password(wp_generate_password());
          $user_id = wp_insert_user($new_user);
          if (is_numeric($user_id)) {
            unset($_SESSION['bsauth_register']);
              $_SESSION['bsauth_registered']=1;
              wp_set_current_user ($user_id);
              wp_set_auth_cookie($user_id);
            if ($local) {
              $_SESSION['bsauth_display_message'] = sprintf( __("Welcome to %s.", "blaat_auth"), get_bloginfo('name') );
            } else {

              global $BSAUTH_SERVICES;
              $serviceToLink = $BSAUTH_SERVICES[$plugin_id];
              if ($serviceToLink) {
                if ($serviceToLink->Link($login_id)) {
                  $_SESSION['bsauth_display_message'] = sprintf( __("Welcome to %s.", "blaat_auth"), get_bloginfo('name') );
                } else {
                  // This should never happen. Cannot sign up with already linked account.
                  // but it did?
                  $_SESSION['bsauth_display_message'] = __("An error occurred while registering your account.", "blaat_auth");
                }
              } else {
                $_SESSION['bsauth_display_message'] = __("Plugin not registered.", "blaat_auth");
              }
            }

          } else {
            $_SESSION['bsauth_display_message'] = __($user_id->get_error_message());
          }
        } 
      }
      // end regging


  } 
}

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

  add_action('wp_loaded','bsauth_process', 11); // do it earlier?

//------------------------------------------------------------------------------


?>
