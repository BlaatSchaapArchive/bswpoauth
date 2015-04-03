<?php

require_once("OAuthException.class.php");

class OAuth implements AuthService {
//------------------------------------------------------------------------------

  public $redirect_uri; // override the redirect url

//------------------------------------------------------------------------------
  public function Login($service_id){

    try {
      return self::process('self::process_login', $service_id);
    } catch (Exception $e) {
      $_SESSION['bsauth_display_message'] = $e->getMessage();
      $_SESSION['bsauth_library_error'] = $e->libraryError;
      return AuthStatus::Error;
    }
  }
  
//------------------------------------------------------------------------------
  public function Link($service_id){
    try {
      $_SESSION['debug']= "Link($service_id)";
      return self::process('self::process_link',  $service_id);
    } catch (Exception $e) {
      $_SESSION['bsauth_display_message'] = $e->getMessage();
      $_SESSION['bsauth_library_error'] = $e->libraryError;
      return AuthStatus::Error;
    }
  }
  
//------------------------------------------------------------------------------
  public function getRegisterData($service_id){
    global $wpdb;
    $table_name = $wpdb->prefix . "bs_oauth_services";
    $query = $wpdb->prepare("SELECT fetch_userdata_function 
                                 FROM $table_name  WHERE id = %d", $service_id);

/*
    $result = $wpdb->get_row($query,ARRAY_A);

    // DEBUG
    $_SESSION['blaat_debug_userdate_function_array'] = $result;
    if ($result){
      $fetch_userdata_function = $result[0];
    } else {
      $_SESSION['bsauth_display_message'] = "Cannot fetch user data";
      return AuthStatus::Error;
    }
*/
    $fetch_userdata_function = $wpdb->get_var($query);

    if ($fetch_userdata_function==NULL) {
      $_SESSION['blaat_debug_userdate_function_value'] = "got NULL";
      return AuthStatus::Error;
    }

    if (strlen($fetch_userdata_function)==0) {
      $_SESSION['blaat_debug_userdata_function_value'] = "got empty string";
      return AuthStatus::Error;
    }

      $_SESSION['blaat_debug_userdata_function_value'] = $fetch_userdata_function;

    $_SESSION['blaat_debug_userdate_function_value'] = $result;
    try {
      return self::process($fetch_userdata_function, $service_id);
    } catch (Exception $e) {
      $_SESSION['bsauth_display_message'] = $e->getMessage();
      $_SESSION['bsauth_library_error'] = $e->libraryError;
      return AuthStatus::Error;
    }
  }
  
//------------------------------------------------------------------------------
  public function Delete($user_id){
    global $wpdb;
    $table_name = $wpdb->prefix . "bs_oauth_sessions";
    $query = $wpdb->prepare ("Delete from $table_name where user_id = %d", $user_id);
    $wpdb->query($query);
  }

//------------------------------------------------------------------------------
  public function getButtons(){
    global $wpdb;
    $table_name = $wpdb->prefix . "bs_oauth_services";
    $results = $wpdb->get_results("select * from $table_name where enabled=1 ",
                   ARRAY_A);
    $buttons = array();    
    foreach ($results as $result) {
      $button = array();
      if(!$result['customlogo_enabled']) 
        $service=strtolower($result['client_name']); 
      else {
        $service="custom-".$result['id'];

        //deprecation css generation in class
        $button['css']="<style>.bs-auth-btn-logo-".$service.
           " {background-image:url('" .$result['customlogo_url']."');}</style>"; 


        $button['logo']    = $result['customlogo_url'];
      }

      // deprecated html generation inside class
      $button['button']="<button class='bs-auth-btn' name=bsauth_login 
             type=submit value='blaat_oauth-".$result['id']."'><span class='bs-auth-btn-logo 
             bs-auth-btn-logo-$service'></span><span class='bs-auth-btn-text'>".
             $result['display_name']."</span></button>";

      
      $button['order']        = $result['display_order'];
      $button['plugin']       = "blaat_oauth";
      $button['service']      = $service;
      $button['id']           = $result['id'];
      $button['display_name'] = $result['display_name'];

      $buttons[]          = $button;
    }
    return $buttons;
  }


    public function getButtonsLinked($id){
      global $wpdb;
      $buttons = array(); 
      $buttons['linked']= array();
      $buttons['unlinked'] = array();
  
      $user = wp_get_current_user();

      // TODO rewrite as OAuth Class Methods
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
        $button = array();
        //$button['class'] = $class;

        if(!$available_service['customlogo_enabled'])
          $service=strtolower($available_service['client_name']);
        else {
          $service="custom-".$available_service['id'];
          $button['logo']= $available_service['customlogo_url'];
          $button['css'] = "<style>.bs-auth-btn-logo-".$service." {background-image:url('" .$available_service['customlogo_url']."');}</style>";
        }


      $button['order']   = $available_service['display_order'];
      $button['plugin']  = "blaat_oauth";
      $button['id']      = $available_service['id'];
      $button['service'] = $service;

      $button['display_name'] = $available_service['display_name'];


      if (in_array($available_service['id'],$linked)) { 
        $buttons['linked'][]=$button;
      } else {
        $buttons['unlinked'][]=$button;
      }


    }
    return $buttons;
  }
//------------------------------------------------------------------------------
  public function process($function, $service_id, $params=NULL){

    global $wpdb; // Database functions
  
      if (isset($_REQUEST['bsoauth_id'])) $_SESSION['bsoauth_id']=$_REQUEST['bsoauth_id'];


      $table_name = $wpdb->prefix . "bs_oauth_services";
      $query = $wpdb->prepare("SELECT * FROM $table_name  WHERE id = %d", $service_id);
      //$results = $wpdb->get_results($query,ARRAY_A);
      //$result = $results[0];
      $result = $wpdb->get_row($query,ARRAY_A);
      $client = new oauth_client_class;


      // DEBUGGING
      $client->debug=get_option("bsauth_oauth_debug");
      $client->debug_http=get_option("bsauth_oauth_debug_http");

      $client->configuration_file = plugin_dir_path(__FILE__) . '../oauth/oauth_configuration.json';

      
      if (isset($this->redirect_uri) && strlen($this->redirect_uri)) {
        // allow redirect-uri overrides from code
        $client->redirect_uri  = $this->redirect_uri;
      } else if ($result['fixed_redirect_url']) {
        // old default, loggin in page
        $client->redirect_uri  = site_url("/".get_option("login_page"));
        //!! TODO When the page options change, this must be updated as well
      } else if (strlen($result['override_redirect_url'])) {  
      // allow redirect-uri overrides from database
        $client->redirect_uri  = $result['override_redirect_url'];
      } else {
        // new defaulturl of requesting page
        $client->redirect_uri  = blaat_get_current_url();
      }
      

      $client->client_id     = $result['client_id'];
      $client->client_secret = $result['client_secret'];
      $client->scope         = $result['default_scope'];


      // TODO :: better way of settings these session variables
	    $_SESSION['bsauth_fetch_data'] = $result['fetch_data'];
	    $_SESSION['bsauth_register_auto'] = $result['auto_register'];  // names?


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

        //$_SESSION['DEBUG_OAUTH_SERVICE_CUSTOM'] = $custom;



      } else {
        $client->server        = $result['client_name'];
      }
     
      if ($success = $client->Initialize()) {
        if ($success = $client->Process()) {
          if (strlen($client->access_token)) {
            $result = call_user_func($function, $client, $result['display_name'], $service_id, $params);
            $success = $client->Finalize($success);
            // do we need to check for success here?
            return $result;
          } else {
            return AuthStatus::Busy;
          }
        } else {
            $exception = new OAuthException(__("OAuth error: processing error","blaat_auth"), 1);
            $exception->libraryError = $client->error;
            throw $exception;
        }
      } else {
        $exception = new OAuthException(__("OAuth error: initialisation error","blaat_auth"), 2);
        $exception->libraryError = $client->error;
        throw $exception;

        /*
        _e("OAuth error: initialisation error","blaat_auth");
        echo $client->error;
        */
      } 

  }
//------------------------------------------------------------------------------
  public function  install() {
    if (!get_option("bs_auth_signup_user_email")) 
      update_option("bs_auth_signup_user_email","Required");

    global $wpdb;
    global $bs_oauth_plugin;
    // dbver in sync with plugin ver
    $dbver = 43;
    $live_dbver = get_option( "bs_oauth_dbversion" );
    

    if (($dbver != $live_dbver) || get_option("bs_debug_updatedb") ) {
      require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
      
      // !! DEBUG
      $wpdb->show_errors = TRUE;
      $wpdb->suppress_errors = FALSE;

      $table_name = $wpdb->prefix . "bs_oauth_sessions";
      $query = "CREATE TABLE $table_name (
                `id` INT NOT NULL AUTO_INCREMENT   ,
                `user_id` INT NOT NULL DEFAULT 0,
                `service_id` INT NOT NULL ,
                `token` TEXT NOT NULL ,
                `authorized` BOOLEAN NOT NULL ,
                `expiry` DATETIME NULL DEFAULT NULL ,
                `type` TEXT NULL DEFAULT NULL ,
                `refresh` TEXT NULL DEFAULT NULL,
                `scope` TEXT NOT NULL DEFAULT '',
                `external_id` INT NOT NULL DEFAULT 0,
                KEY token_key (token(32)),
                KEY external_id_key (external_id),
                KEY token_external_id_key (token(32),external_id),
                PRIMARY KEY  (id)
                );";
      dbDelta($query);
// Note: we should use KEY in stead of the usual INDEX when using DbDelta
   
      $table_name = $wpdb->prefix . "bs_oauth_services";
      $query = "CREATE TABLE $table_name (
                `id` INT NOT NULL AUTO_INCREMENT  ,
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
                `customlogo_enabled` BOOLEAN DEFAULT FALSE,
                `fixed_redirect_url` BOOLEAN NOT NULL DEFAULT FALSE ,
                `override_redirect_url` TEXT NULL DEFAULT NULL,
                `fetch_userdata_function` TEXT NULL DEFAULT NULL,
                `fetch_data` BOOL NOT NULL DEFAULT FALSE,
                `auto_register` BOOL NOT NULL DEFAULT FALSE,
                PRIMARY KEY  (id)
                );";
      dbDelta($query);
      // note: get_userdata_function is experimental
      //        for now, needed for a customised version
      //        might change in public release

    $table_name = $wpdb->prefix . "bs_oauth_custom";
      $query = "CREATE TABLE $table_name (
                `id` INT NOT NULL AUTO_INCREMENT   ,
                `oauth_version` ENUM('1.0','1.0a','2.0') DEFAULT '2.0',
                `request_token_url` TEXT NULL DEFAULT NULL,
                `dialog_url` TEXT NOT NULL,
                `access_token_url` TEXT NOT NULL,
                `url_parameters` BOOLEAN DEFAULT FALSE,
                `authorization_header` BOOLEAN DEFAULT TRUE,
                `pin_dialog_url` TEXT NULL DEFAULT NULL,
                `offline_dialog_url` TEXT NULL DEFAULT NULL,
                `append_state_to_redirect_uri` TEXT NULL DEFAULT NULL,
                PRIMARY KEY  (id)
                );";
      dbDelta($query);
      update_option( "bs_oauth_dbversion" , $dbver);
    }
  }
//------------------------------------------------------------------------------
  public function  process_link($client,$service,$service_id,$params=NULL) {
    global $wpdb;    
    $user = wp_get_current_user();
    $user_id    = $user->ID;
    $token      = $client->access_token;
    $expiry     = $client->access_token_expiry;
    $scope      = $client->scope;

    // Verifying this account has not been linked to another user
    $table_name = $wpdb->prefix . "bs_oauth_sessions";
    $testQuery = $wpdb->prepare("SELECT * FROM $table_name 
                                 WHERE service_id = %d 
                                 AND   token = %s" , $service_id, $token);
    $testResult = $wpdb->get_results($testQuery,ARRAY_A);

      if (count($testResult)) {
        return AuthStatus::LinkInUse;
      } else {
        // We can continue linking

        $query = $wpdb->prepare("INSERT INTO $table_name (`user_id`, `service_id`, `token`, `expiry`, `scope` )
                                         VALUES      ( %d      ,  %d         ,  %s    , %s      , %s      )",
                                                      $user_id , $service_id , $token , $expiry , $scope  );
        $wpdb->query($query);
        $_SESSION['display_name']=$service;
        //return true;
        return AuthStatus::LinkSuccess;
        // printf( __("Your %s account has been linked", "blaat_auth"), $service );
        //unset($_SESSION['bsauth_link']);
        
      }

  }




  public function Unlink ($id) {
    global $wpdb;    
    $table_name = $wpdb->prefix . "bs_oauth_sessions";
    $table_name2 = $wpdb->prefix . "bs_oauth_services";
    $query2 = $wpdb->prepare("Select display_name from $table_name2 where id = %d", $id );
    $service_name = $wpdb->get_results($query2,ARRAY_A);
    //$service = $service_name[0]['display_name'];
    $_SESSION['display_name'] = $service_name[0]['display_name'];
    $query = $wpdb->prepare ("Delete from $table_name where user_id = %d AND service_id = %d", get_current_user_id(), $id );
    $wpdb->query($query);
      
    return true;

  }

//------------------------------------------------------------------------------

  private function process_login($client,$display_name,$service_id, $params=NULL){
      global $wpdb;

      $_SESSION['bsauth_display'] = $display_name;

      $token = $client->access_token;
      $table_name = $wpdb->prefix . "bs_oauth_sessions";

      $query = $wpdb->prepare("SELECT `user_id` FROM $table_name WHERE `service_id` = %d AND `token` = %d",$service_id,$token);  
      $results = $wpdb->get_results($query,ARRAY_A);
      if (isset($results[0])) {
        $result = $results[0];
      }

      if (isset($result)) {
        unset ($_SESSION['bsauth_login']);  
        unset($_SESSION['bsauth_login_id']);
        wp_set_current_user ($result['user_id']);
        wp_set_auth_cookie($result['user_id']);
        //return true;
        return AuthStatus::LoginSuccess;
      }

      //return false;
      return AuthStatus::LoginMustRegister;  // does this fix the problem?
                                  // if so rewrite to ENUM?
    }

//------------------------------------------------------------------------------

}
?>
