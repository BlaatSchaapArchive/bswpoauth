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
    //$table_name = $wpdb->prefix . "bs_oauth_services";
    $table_name = $wpdb->prefix . "bs_oauth_services_configured";
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
      $button['id']           = $result['service_id'];
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
  public function process($function, $service_id, $params=NULL, $scope=NULL){

    global $wpdb; // Database functions
  
      if (isset($_REQUEST['bsoauth_id'])) $_SESSION['bsoauth_id']=$_REQUEST['bsoauth_id'];

      // The new database configuration stores all services, 
      // pre-configured (known) and user-defined (custom) services.
      // As the current implementation requires additional options, the
      // pre-configured options provided by Manuel Lemos are no longer suitable
/*
  we need more complicated joining

      $tables =      $wpdb->prefix . "bs_oauth_services_configured ".
          " LEFT JOIN " . $wpdb->prefix . "bs_oauth_services_known" . 
          " LEFT JOIN " . $wpdb->prefix . "bs_oauth_services_custom";


      // If the scope is not set, we are probably loggin in or linking, as they
      // are the default actions. (nothing else is implemented at this moment)
      // We need to set the scope required for the logging in/getting userinfo
      // process. Therefore we join the tables with the api information as
      // they contain the required scopes.
      if ($scope==NULL) $tables .=
          " LEFT JOIN " . $wpdb->prefix . "bs_oauth_userinfo_api_known" .
          " LEFT JOIN " . $wpdb->prefix . "bs_oauth_userinfo_api_custom" ;
*/

      $tables =      $wpdb->prefix . "bs_oauth_services_configured ".
          " NATURAL LEFT OUTER JOIN  " . $wpdb->prefix . "bs_oauth_services_known" . 
          " NATURAL LEFT OUTER JOIN  " . $wpdb->prefix . "bs_oauth_userinfo_api_known" ;

      $query = $wpdb->prepare("SELECT * FROM $tables  WHERE service_id = %d", $service_id);
      $result = $wpdb->get_row($query,ARRAY_A);

  
      $client = new oauth_client_class;


      // Debugging options for the library by Manuel Lemos
      // The options used here are set by a separate debugging options plugin
      $client->debug      = get_option("bsauth_oauth_debug");
      $client->debug_http = get_option("bsauth_oauth_debug_http");

      
      // The configuration file is no longer used
      // $client->configuration_file = plugin_dir_path(__FILE__) . '../oauth/oauth_configuration.json';
      
      
      if (isset($this->redirect_uri) && strlen($this->redirect_uri)) {
        // allow redirect-uri overrides from code
        $client->redirect_uri  = $this->redirect_uri;
      } else if ($result['fixed_redirect_url']) {
        // old default, loggin in page
        // TODO page migration
        $client->redirect_uri  = site_url("/".get_option("login_page"));
      } else if (strlen($result['override_redirect_url'])) {  
      // allow redirect-uri overrides from database
        $client->redirect_uri  = $result['override_redirect_url'];
      } else { // requesting page, note that this won't work for most services
        $client->redirect_uri  = blaat_get_current_url();
      }
      
      $client->client_id     = $result['client_id'];
      $client->client_secret = $result['client_secret'];


      if ($scope==NULL) {
        // As the scope is now part of the userinfo API we have different 
        // column names in the database.
        //$client->scope         = $result['default_scope'];        
        $client->scope         = $result['scope'];        
      } else {
        $client->scope         = $scope;
      }


      // We are required to fetch certain data as we need to obtain the user id
      // we might differentiate between required and optional data in a later
      // model, (see the scopes above, maybe store a minimum and optional set)
      // So should this remain in a future version?
	    $_SESSION['bsauth_fetch_data'] = $result['fetch_data'];
      $_SESSION['bsauth_register_auto'] = $result['auto_register'];  


      // No longer an if statement, all settings are read from the database.
      // Even if we are using a service pre-configures by Manuel Lemos. We
      // need to store additional information about the services.
      $client->oauth_version                 = $result['oauth_version'];
      $client->request_token_url             = $result['request_token_url'];
      $client->dialog_url                    = $result['dialog_url'];
      $client->access_token_url              = $result['access_token_url'];
      $client->url_parameters                = $result['url_parameters'];
      $client->authorization_header          = $result['authorization_header'];
      $client->offline_dialog_url            = $result['offline_dialog_url'];
      $client->append_state_to_redirect_uri  = $result['append_state_to_redirect_uri'];

      if ($success = $client->Initialize()) {
        if ($success = $client->Process()) {
			    if(strlen($client->authorization_error)){
				    $client->error = $client->authorization_error;
				    $success = false;
			    } elseif(strlen($client->access_token)){
            $result = call_user_func($function, $client, $result, $params );
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
    $dbver = 50;
    $live_dbver = get_option( "bs_oauth_dbversion" );
    
    if (($dbver != $live_dbver) || get_option("bs_debug_updatedb") ) {
      require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
      
      $table_name = $wpdb->prefix . "bs_oauth_tokens";
      $query = "CREATE TABLE $table_name (
                `token_id` INT NOT NULL AUTO_INCREMENT   ,
                `wordpress_id` INT NOT NULL DEFAULT 0,
                `service_id` INT NOT NULL ,
                `token` TEXT NOT NULL ,
                `secret` TEXT NOT NULL ,
                `authorized` BOOLEAN NOT NULL ,
                `expiry` DATETIME NULL DEFAULT NULL ,
                `type` TEXT NULL DEFAULT NULL ,
                `refresh` TEXT NULL DEFAULT NULL,
                `scope` TEXT NOT NULL DEFAULT '',
                KEY token_key (token(32)),
                PRIMARY KEY  (token_id)
                );";
      dbDelta($query);
// Note: we should use KEY in stead of the usual INDEX when using DbDelta

// how big is bigint, is it enough?
      $table_name = $wpdb->prefix . "bs_oauth_accounts";
      $query = "CREATE TABLE $table_name (
                `account_id` INT NOT NULL AUTO_INCREMENT   ,
                `wordpress_id` INT NOT NULL DEFAULT 0,
                `service_id` INT NOT NULL ,
                `external_id_int` BIGINT NULL DEFAULT NULL,
                `external_id_text` TEXT NULL DEFAULT NULL,
                KEY (external_id_int),
                KEY (external_id_text(256)),
                PRIMARY KEY  (account_id)
                );";
      dbDelta($query);


      $table_name = $wpdb->prefix . "bs_oauth_services_configured";
      $query = "CREATE TABLE $table_name (
                `service_id` INT NOT NULL AUTO_INCREMENT  ,
                `enabled` BOOLEAN NOT NULL DEFAULT FALSE ,
                `display_name` TEXT NOT NULL ,
                `display_order` INT NOT NULL DEFAULT 1,
                `service_known_id`  INT NULL DEFAULT NULL ,
                `service_custom_id` INT NULL DEFAULT NULL ,
                `client_id` TEXT NOT NULL ,
                `client_secret` TEXT NOT NULL,
                `customlogo_url` TEXT NULL DEFAULT NULL,
                `customlogo_filename` TEXT NULL DEFAULT NULL,
                `customlogo_enabled` BOOLEAN DEFAULT FALSE,
                `fixed_redirect_url` BOOLEAN NOT NULL DEFAULT TRUE ,
                `override_redirect_url` TEXT NULL DEFAULT NULL,
                `auto_register` BOOL NOT NULL DEFAULT FALSE,
                PRIMARY KEY  (service_id)
                );";
      dbDelta($query);


    $table_name = $wpdb->prefix . "bs_oauth_services_custom";
      $query = "CREATE TABLE $table_name (
                `service_custom_id` INT NOT NULL AUTO_INCREMENT   ,
                `service_name` TEXT NULL DEFAULT NULL ,
                `oauth_version` ENUM('1.0','1.0a','2.0') DEFAULT '2.0',
                `request_token_url` TEXT NULL DEFAULT NULL,
                `dialog_url` TEXT NOT NULL,
                `access_token_url` TEXT NOT NULL,
                `userinfo_url` TEXT NOT NULL,
                `userinfo_api_known_id` INT NULL DEFAULT NULL ,
                `userinfo_api_custom_id` INT NULL DEFAULT NULL ,
                `url_parameters` BOOLEAN DEFAULT FALSE,
                `authorization_header` BOOLEAN DEFAULT TRUE,
                `append_state_to_redirect_uri` TEXT NULL DEFAULT NULL,
                `pin_dialog_url` TEXT NULL DEFAULT NULL,
                `offline_dialog_url` TEXT NULL DEFAULT NULL,
                PRIMARY KEY  (service_custom_id)
                );";      
      dbDelta($query);



      $table_name = $wpdb->prefix . "bs_oauth_services_known";
      $query = "CREATE TABLE $table_name (
                `service_known_id` INT NOT NULL AUTO_INCREMENT   ,
                `service_name` TEXT NULL DEFAULT NULL ,
                `oauth_version` ENUM('1.0','1.0a','2.0') DEFAULT '2.0',
                `request_token_url` TEXT NULL DEFAULT NULL,
                `dialog_url` TEXT NOT NULL,
                `access_token_url` TEXT NOT NULL,
                `userinfo_url` TEXT NOT NULL,
                `userinfo_api_known_id` INT NULL DEFAULT NULL ,
                `userinfo_api_custom_id` INT NULL DEFAULT NULL ,
                `url_parameters` BOOLEAN DEFAULT FALSE,
                `authorization_header` BOOLEAN DEFAULT TRUE,
                `append_state_to_redirect_uri` TEXT NULL DEFAULT NULL,
                `pin_dialog_url` TEXT NULL DEFAULT NULL,
                `offline_dialog_url` TEXT NULL DEFAULT NULL,
                `default_icon`  TEXT NULL DEFAULT NULL,
                `variant`  TEXT NULL DEFAULT NULL,
                KEY (service_name(255)),
                PRIMARY KEY  (service_known_id)
                );";
      dbDelta($query);

      $table_name = $wpdb->prefix . "bs_oauth_userinfo_api_known";
      $query = "CREATE TABLE $table_name (
                `userinfo_api_known_id` INT NOT NULL AUTO_INCREMENT   ,
                `request_method` ENUM('GET', 'POST') DEFAULT 'POST',
                `api_name` TEXT NULL DEFAULT NULL ,
                `data_format` ENUM('FORM','JSON','XML') DEFAULT 'JSON',
                `external_id` TEXT NULL DEFAULT NULL ,
                `first_name`  TEXT NULL DEFAULT NULL ,
                `last_name`  TEXT NULL DEFAULT NULL ,
                `user_email`  TEXT NULL DEFAULT NULL ,
                `user_url`  TEXT NULL DEFAULT NULL ,
                `user_nicename`  TEXT NULL DEFAULT NULL ,
                `user_login`  TEXT NULL DEFAULT NULL ,
                `scope`  TEXT NULL DEFAULT NULL ,
                `email_verified`  TEXT NULL DEFAULT NULL ,
                PRIMARY KEY  (userinfo_api_known_id)
      );";
      dbDelta($query);

      $table_name = $wpdb->prefix . "bs_oauth_userinfo_api_custom";
      $query = "CREATE TABLE $table_name (
                `userinfo_api_custom_id` INT NOT NULL AUTO_INCREMENT   ,
                `api_name` TEXT NULL DEFAULT NULL ,
                `data_format` ENUM('FORM','JSON','XML') DEFAULT 'JSON',
                `external_id` TEXT NULL DEFAULT NULL ,
                `first_name`  TEXT NULL DEFAULT NULL ,
                `last_name`  TEXT NULL DEFAULT NULL ,
                `user_email`  TEXT NULL DEFAULT NULL ,
                `user_url`  TEXT NULL DEFAULT NULL ,
                `user_nicename`  TEXT NULL DEFAULT NULL ,
                `user_login`  TEXT NULL DEFAULT NULL ,
                `scope`  TEXT NULL DEFAULT NULL ,
                `email_verified`  TEXT NULL DEFAULT NULL ,
                PRIMARY KEY  (userinfo_api_custom_id)
      );";
      dbDelta($query);
   
      update_option( "bs_oauth_dbversion" , $dbver);
      update_option( "bs_oauth_dbmigrate50required" , true);
    }

    $dataver = 50;
    $live_dataver = get_option( "bs_oauth_dataversion" );
    if ($dataver != $live_dataver) {

    }


  }
//------------------------------------------------------------------------------
  public function  process_link($client,$result,$params=NULL) {
    global $wpdb;  
    $table_name= $wpdb->prefix . "bs_oauth_accounts";  
    $user = wp_get_current_user();
    $user_id    = $user->ID;


      $client->CallAPI($result['userinfo_url'], $result['request_method'],
                                                  $params, $options, $userinfo); 


      // ok, determine type of result
      // in case of json from server it will be object
      // in case of form from server it will be array
      // in case of xml  from server it will be simplexml


      // for now, assume json as this is what we get from google, other variants
      // will have to be implemented later.

      // TODO : test if already linked, but first we need to see if this new
      // implementation even works.

      $external_id = $userinfo->{$result['external_id']};
  

      if ((int)$external_id)  {
        // no information lost while casting to int, we use the external id 
        // stored as an int in the database as this processes faster
        $query = $wpdb->prepare("INSERT INTO $table_name (`wordpress_id`, `service_id`, `external_id_int`)
                                        VALUES( %d, %d, %d)", $user_id, $result['service_id'], $external_id);

      } else {
        // the external id is not an integer value, thus stored as a string in
        // the database.
        $query = $wpdb->prepare("INSERT INTO $table_name (`wordpress_id`, `service_id`, `external_id_text`)
                                        VALUES( %d, %d, %s)", $user_id, $result['service_id'], $external_id);
      }

      $wpdb->query($query);
      $_SESSION['display_name']=$service;
      //return true;
      return AuthStatus::LinkSuccess;
      // printf( __("Your %s account has been linked", "blaat_auth"), $service );
      //unset($_SESSION['bsauth_link']);


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

  private function process_login($client, $result, $params=NULL){
      global $wpdb;
      $table_name= $wpdb->prefix . "bs_oauth_accounts";

      $options = array('FailOnAccessError'=>true, 'DecodeXMLResponse'=>'simplexml');
      $_SESSION['bsauth_display'] = $display_name;
      // url, method, 
      $client->CallAPI($result['userinfo_url'], $result['request_method'],
                                                  $params, $options, $userinfo); 


      // ok, determine type of result
      // in case of json from server it will be object
      // in case of form from server it will be array
      // in case of xml  from server it will be simplexml


      // for now, assume json as this is what we get from google, other variants
      // will have to be implemented later.



      $external_id = $userinfo->{$result['external_id']};
      if ((int)$external_id)  {
        // no information lost while casting to int, we use the external id 
        // stored as an int in the database as this processes faster
        $query = $wpdb->prepare("SELECT `wordpress_id` FROM $table_name WHERE `service_id` = %d AND `external_id_int` = %d",$result['service_id'],$external_id);  
      } else {
        // the external id is not an integer value, thus stored as a string in
        // the database.
        $query = $wpdb->prepare("SELECT `wordpress_id` FROM $table_name WHERE `service_id` = %d AND `external_id_text` = %s",$result['service_id'],$external_id);  
      }

      $result = $wpdb->get_row($query,ARRAY_A);


      if (NULL!=$result) {
        unset ($_SESSION['bsauth_login']);  
        unset($_SESSION['bsauth_login_id']);
        wp_set_current_user ($result['wordpress_id']);
        wp_set_auth_cookie($result['wordpress_id']);
        //return true;
        return AuthStatus::LoginSuccess;
      } else { 
        //die ($query);
        return AuthStatus::LoginMustRegister;
      }
      
      //bs_oauth_accounts

/*
      $query = $wpdb->prepare("SELECT `user_id` FROM $table_name WHERE 
                        `service_id` = %d AND `token` = %s",$service_id,$token);  

 
      $token = $client->access_token;
      $table_name = $wpdb->prefix . "bs_oauth_sessions";

      $query = $wpdb->prepare("SELECT `user_id` FROM $table_name WHERE `service_id` = %d AND `token` = %s",$service_id,$token);  
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
*/
    }

//------------------------------------------------------------------------------

}
?>
