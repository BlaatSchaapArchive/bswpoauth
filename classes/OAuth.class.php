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
  // what to do with this function? remove it or rewrite it to use data?
  public function getRegisterData($service_id){
      return AuthStatus::Error;
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
    //$table_name = $wpdb->prefix . "bs_oauth_services_configured";
    
    // TODO :: merge with getServices()
  $tables =      $wpdb->prefix . "bs_oauth_services_configured
                JOIN ". $wpdb->prefix . "bs_login_generic_options 
               ON ".$wpdb->prefix . "bs_oauth_services_configured.login_options_id = ". $wpdb->prefix . "bs_login_generic_options.login_options_id"  ;


 
    $results = $wpdb->get_results("select * from $tables where enabled=1 ",
                   ARRAY_A);
    $buttons = array();    
    foreach ($results as $result) {
      $button = array();

      if($result['custom_icon_enabled']) {
        $button['icon']= $result['custom_icon_url'];
      } elseif($result['default_icon']) {
        $button['icon']=  plugin_dir_url(__FILE__) . "../icons/" . $result['default_icon'];
      }

      $button['order']        = $result['display_order'];
      $button['plugin']       = "blaat_oauth";
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


      $tables =      $wpdb->prefix . "bs_oauth_services_configured ".
          " JOIN  " . $wpdb->prefix . "bs_oauth_accounts" .
" on " . $wpdb->prefix . "bs_oauth_accounts.service_id=" . $wpdb->prefix . "bs_oauth_services_configured.service_id";

      $user_id    = $user->ID;
      $query = $wpdb->prepare("SELECT ".$wpdb->prefix ."bs_oauth_services_configured.service_id AS service_id FROM $tables WHERE `wordpress_id` = %d",$user_id);



      $linked_services = $wpdb->get_results($query,ARRAY_A);
       
      $table_name = $wpdb->prefix . "bs_oauth_services_configured
                JOIN ". $wpdb->prefix . "bs_login_generic_options 
               ON ".$wpdb->prefix . "bs_oauth_services_configured.login_options_id = ". $wpdb->prefix . "bs_login_generic_options.login_options_id"  ;

      $query = "SELECT * FROM $table_name where enabled=1";
      $available_services = $wpdb->get_results($query,ARRAY_A);




      $linked = Array();
      foreach ($linked_services as $linked_service) {
        $linked[]=$linked_service['service_id'];
      }  


      foreach ($available_services as $available_service) {
        $button = array();


      if($available_service['custom_icon_enabled']) {
        $button['icon']= $available_service['custom_icon_url'];
      } elseif($available_service['default_icon']) {
        $button['icon']=  plugin_dir_url(__FILE__) . "../icons/" . $available_service['default_icon'];
      }


      $button['order']   = $available_service['display_order'];
      $button['plugin']  = "blaat_oauth";
      $button['id']      = $available_service['service_id'];
      //$button['service'] = $service;

      $button['display_name'] = $available_service['display_name'];


      if (in_array($available_service['service_id'],$linked)) { 
        $buttons['linked'][]=$button;
      } else {
        $buttons['unlinked'][]=$button;
      }


    }
    return $buttons;
  }
//------------------------------------------------------------------------------
  public function getConfig($service_id){
    // Rewrite to object (Remove ARRAY_A ?
    global $wpdb;
    $table_name =      $wpdb->prefix . "bs_oauth_services_configured 
               JOIN ". $wpdb->prefix . "bs_login_generic_options 
               ON ".$wpdb->prefix . "bs_oauth_services_configured.login_options_id = ". $wpdb->prefix . "bs_login_generic_options.login_options_id"  ;
    $query = $wpdb->prepare("SELECT *, \"blaat_oauth\" as plugin_id FROM $table_name  WHERE service_id = %d", $service_id);
    return $wpdb->get_row($query,ARRAY_A);
  }
//------------------------------------------------------------------------------
  public function setConfig(){
    global $wpdb;
    $service_id = $_POST['service_id'];
    $table_name = $wpdb->prefix . "bs_oauth_services_configured";
    // as $wpdb escapes the values, using $_POST directly does not pose
    // a security breach.
    $query = $wpdb->update($table_name, $_POST, array("service_id" => $service_id) );
  }
//------------------------------------------------------------------------------
  public function addConfig() {
    global $wpdb;
    $table_name = $wpdb->prefix . "bs_oauth_services_configured";
    // as $wpdb escapes the values, using $_POST directly does not pose
    // a security breach.
    $query = $wpdb->insert($table_name, $_POST);
  }

//------------------------------------------------------------------------------
  public function getPreConfiguredServices(){
  global $wpdb;
  $query = 'SELECT "blaat_oauth" as plugin_id , `service_known_id` as service_id, 
                  `service_name` as display_name, 0, `default_icon` as icon 
            FROM `' . $wpdb->prefix . 'bs_oauth_services_known` ';
  return $wpdb->get_results($query);
  }
//------------------------------------------------------------------------------
  public function process($function, $service_id, $params=NULL, $scope=NULL){

    global $wpdb; // Database functions
  
      if (isset($_REQUEST['bsoauth_id'])) $_SESSION['bsoauth_id']=$_REQUEST['bsoauth_id'];

      $table_name =      $wpdb->prefix . "bs_oauth_services_configured
                JOIN ". $wpdb->prefix . "bs_login_generic_options 
               ON ".$wpdb->prefix . "bs_oauth_services_configured.login_options_id = ". $wpdb->prefix . "bs_login_generic_options.login_options_id"  ;
      $query = $wpdb->prepare("SELECT * FROM $table_name  WHERE service_id = %d", $service_id);
      $result = $wpdb->get_row($query,ARRAY_A);

     // echo "<pre>$query\n\n"; var_dump($result); die();

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
      //$_SESSION['bsauth_fetch_data'] = $result['fetch_data'];
      //$_SESSION['bsauth_register_auto'] = $result['auto_register'];  


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
            $exception = new OAuthException(__("OAuth error: processing error","blaat_auth"), 3); //??
            $exception->libraryError = $client->error;
            $client->Finalize($success);
            throw $exception;

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
            $client->Finalize($success);
            throw $exception;
        }
      } else {
        $exception = new OAuthException(__("OAuth error: initialisation error","blaat_auth"), 2);
        $exception->libraryError = $client->error;
        $client->Finalize($success);
        throw $exception;
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
                KEY (token(256)),
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
                `request_method` ENUM('GET', 'POST') DEFAULT 'GET',
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


      $table_name = $wpdb->prefix . "bs_oauth_services_configured";
      $query = "CREATE TABLE $table_name (
                `service_id` INT NOT NULL AUTO_INCREMENT  ,
                `service_known_id` INT DEFAULT 0,
                `oauth_version` ENUM('1.0','1.0a','2.0') DEFAULT '2.0',
                `request_token_url` TEXT NULL DEFAULT NULL,
                `dialog_url` TEXT NOT NULL,
                `access_token_url` TEXT NOT NULL,
                `userinfo_url` TEXT NOT NULL,
                `url_parameters` BOOLEAN DEFAULT FALSE,
                `authorization_header` BOOLEAN DEFAULT TRUE,
                `append_state_to_redirect_uri` TEXT NULL DEFAULT NULL,
                `pin_dialog_url` TEXT NULL DEFAULT NULL,
                `offline_dialog_url` TEXT NULL DEFAULT NULL,
                `client_id` TEXT NOT NULL ,
                `client_secret` TEXT NOT NULL,
                `fixed_redirect_url` BOOLEAN NOT NULL DEFAULT TRUE ,
                `override_redirect_url` TEXT NULL DEFAULT NULL,
                `request_method` ENUM('GET', 'POST') DEFAULT 'GET',
                `data_format` ENUM('FORM','JSON','XML') DEFAULT 'JSON',
                `external_id` TEXT NULL DEFAULT NULL,
                `first_name`  TEXT NULL DEFAULT NULL,
                `last_name`  TEXT NULL DEFAULT NULL,
                `user_email`  TEXT NULL DEFAULT NULL,
                `user_url`  TEXT NULL DEFAULT NULL,
                `user_nicename`  TEXT NULL DEFAULT NULL,
                `user_login`  TEXT NULL DEFAULT NULL,
                `scope`  TEXT NULL DEFAULT NULL,
                `email_verified`  TEXT NULL DEFAULT NULL,
                `login_options_id` INT NOT NULL, 
                PRIMARY KEY  (service_id)
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

    $options = array('FailOnAccessError'=>true, 'DecodeXMLResponse'=>'simplexml');
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
  

      /*
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
      */


      $data=array();
      $data['wordpress_id']=$user_id;
      $data['service_id']=$result['service_id'];
      if ((int)$external_id)  {
        $data['external_id_int']=$external_id;
      } else {
        $data['external_id_text']=$external_id;
      }
      $wpdb->insert($table_name, $data);


      if (strlen($result['display_name'])) {
        $_SESSION['display_name']=$result['display_name'];
      } else {
        $_SESSION['display_name']=$result['service_name'];
      }

      //$_SESSION['display_name']=$service;
      //return true;
      return AuthStatus::LinkSuccess;
      // printf( __("Your %s account has been linked", "blaat_auth"), $service );
      //unset($_SESSION['bsauth_link']);


  }




  public function Unlink ($id) {

    global $wpdb;    
    $table_name = $wpdb->prefix . "bs_oauth_accounts";
    $table_name2 = $wpdb->prefix . "bs_oauth_services_configured                JOIN ". $wpdb->prefix . "bs_login_generic_options 
               ON ".$wpdb->prefix . "bs_oauth_services_configured.login_options_id = ". $wpdb->prefix . "bs_login_generic_options.login_options_id"  ;

//
    $query2 = $wpdb->prepare("Select display_name from $table_name2 where service_id = %d", $id );
/*
    $service_name = $wpdb->get_results($query2,ARRAY_A);
    //$service = $service_name[0]['display_name'];
    $_SESSION['display_name'] = $service_name[0]['display_name'];
*/
    $_SESSION['display_name'] = $wpdb->get_var($query2);
    $query = $wpdb->prepare ("Delete from $table_name where wordpress_id = %d AND service_id = %d", get_current_user_id(), $id );
    $wpdb->query($query);

    /*

    TODO make display_name like the linking
         return values  
      if (strlen($result['display_name']) {
        $_SESSION['display_name']=$result['display_name'];
      } else {
        $_SESSION['display_name']=$result['service_name'];
      }

    */
      
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

      //TODO test status from callapi sucess

      $external_id = $userinfo->{$result['external_id']};

      //echo "<pre>"; var_dump($userinfo); echo "\n\n"; var_dump($external_id); echo "\n\n"; var_dump($result); die($client->error);
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
        return AuthStatus::LoginSuccess;
      } else { 
        return AuthStatus::LoginMustRegister;
      }
      
    }



//------------------------------------------------------------------------------
  public function getConfigOptions() {
    // sort the options in tabs
    // general / oauth / api / hidden
    // TODO: possibly hide preconfigured values for preconfigures services

    $options=array();  

    $tabs=array();  


    // HIDDEN FIELDS
    $HiddenTab = new BlaatConfigTab("hidden","",true);
    $HiddenTab->addOption(new BlaatConfigOption("service_id",
                  __("Service ID","blaat_auth")));

    $HiddenTab->addOption(new BlaatConfigOption("plugin_id",
                  __("Plugin ID","blaat_auth"),"text", true,"blaat_oauth"));

   
    $HiddenTab->addOption(new BlaatConfigOption("login_options_id",
                  __("Login Options ID","blaat_auth")));


    $tabs[]=$HiddenTab;


    // GENERIC FIELDS // TODO move to BlaatLogin
    $GenericTab = new BlaatConfigTab("generic", 
                    __("Generic configuration","blaat_oauth"));
    $tabs[]=$GenericTab;

    $GenericTab->addOption(new BlaatConfigOption("display_name",
                    __("Display name","blaat_auth"),
                    "text",true));

    $GenericTab->addOption(new BlaatConfigOption("enabled",
                    __("Enabled","blaat_auth"),
                    "checkbox",false,true));


    $GenericTab->addOption(new BlaatConfigOption("auto_register",
                    __("Auto Register","blaat_auth"),
                    "checkbox",false,true));



    // OAUTH FIELDS

    $OAuthTab = new BlaatConfigTab("oauth", 
                    __("OAuth configuration","blaat_oauth"));
    $tabs[]=$OAuthTab;



    $configoption = new BlaatConfigOption("oauth_version",
                    __("OAuth version","blaat_auth"),
                    "select",true, "2.0");
    $configoption->addOption(new BlaatConfigSelectOption("2.0","2.0"));
    $configoption->addOption(new BlaatConfigSelectOption("1.0","1.0"));
    $configoption->addOption(new BlaatConfigSelectOption("1.0a","1.0a"));
    $OAuthTab->addOption($configoption);

    $OAuthTab->addOption(new BlaatConfigOption("request_token_url",
                  __("Request Token URL (1.0 and 1.0a only)","blaat_auth"),
                  "url",false));
    // TODO: how to define value dependencies? required-if-oauth-version-is-not-2.0

    $OAuthTab->addOption(new BlaatConfigOption("dialog_url",
                  __("Dialog URL","blaat_auth"),
                  "url",true));

    $OAuthTab->addOption(new BlaatConfigOption("access_token_url",
                  __("Access Token URL","blaat_auth"),
                  "url",true));

    $OAuthTab->addOption(new BlaatConfigOption("client_id",
                  __("Client ID","blaat_auth"),
                  "text",true));

    $OAuthTab->addOption(new BlaatConfigOption("client_secret",
                  __("Client Secret","blaat_auth"),
                  "text",true));

    $OAuthTab->addOption(new BlaatConfigOption("url_parameters",
                  "url_parameters" /* !! */,
                  "checkbox"));

    $OAuthTab->addOption(new BlaatConfigOption("authorization_header",
                  "authorization_header" /* !! */,
                  "checkbox",false,true)); // default is set to true
                                                // so why does it not show up?

  // OPTIONAL FIELDS
    $OAuthTab->addOption(new BlaatConfigOption("pin_dialog_url",
                  __("Pin Dialog URL","blaat_auth"),
                  "url",false));

    $OAuthTab->addOption(new BlaatConfigOption("offline_dialog_url",
                  __("Offline Dialog URL","blaat_auth"),
                  "url",false));


    // API FIELDS

    $APITab = new BlaatConfigTab("api", 
                    __("API configuration","blaat_oauth"));
    $tabs[]=$APITab;


    // API FIELDS
      
    $configoption = new BlaatConfigOption("request_method",
                    __("Request Method","blaat_auth"),
                    "select",true, "GET");
    $configoption->addOption(new BlaatConfigSelectOption("GET","GET"));
    $configoption->addOption(new BlaatConfigSelectOption("POST","POST"));
    $APITab->addOption($configoption);


    $configoption = new BlaatConfigOption("data_format",
                    __("Data format","blaat_auth"),
                    "select",true, "JSON");
    $configoption->addOption(new BlaatConfigSelectOption("JSON","JSON"));
    $configoption->addOption(new BlaatConfigSelectOption("XML","XML"));
    $configoption->addOption(new BlaatConfigSelectOption("FORM","Form-encoded"));
    $APITab->addOption($configoption);


    $APITab->addOption(new BlaatConfigOption("external_id",
                  __("External Identifier Field","blaat_auth"),
                  "text",true));


    $APITab->addOption(new BlaatConfigOption("scope",
                  __("Required OAuth Scope","blaat_auth"),
                  "text",true));


    $APITab->addOption(new BlaatConfigOption("user_email",
                  __("E-mail Field","blaat_auth")));

    $APITab->addOption(new BlaatConfigOption("first_name",
                  __("First Name Field","blaat_auth")));

    $APITab->addOption(new BlaatConfigOption("last_name",
                  __("Last Name Field","blaat_auth")));


    $APITab->addOption(new BlaatConfigOption("user_nicename",
                  __("Display Name Field","blaat_auth")));

    $APITab->addOption(new BlaatConfigOption("user_url",
                  __("User URL Field","blaat_auth")));


    $APITab->addOption(new BlaatConfigOption("email_verified",
                  __("Email Verified Field","blaat_auth")));


  return $tabs;
  }
//------------------------------------------------------------------------------
  public function addPreconfiguredService($service_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . "bs_oauth_services_known" ;
    $query = $wpdb->prepare("SELECT service_name as display_name, default_icon FROM $table_name 
      WHERE service_known_id = %d ", $service_id);
    $results = $wpdb->get_results($query, ARRAY_A);
    $login_options_id=BlaatLogin::addConfig($results[0]);


    $query = $wpdb->prepare(
    "INSERT INTO `" . $wpdb->prefix . "bs_oauth_services_configured`
    ( `service_known_id`,
    `oauth_version`,        `request_token_url`, 
    `dialog_url`,           `access_token_url`, 
    `userinfo_url`,         `url_parameters`, 
    `authorization_header`, `append_state_to_redirect_uri`, 
    `pin_dialog_url`,       `offline_dialog_url`, 
    `request_method`,       `data_format`, 
    `external_id`,          `first_name`, 
    `last_name`,            `user_email`, 
    `user_url`,             `user_nicename`, 
    `user_login`,           `scope`, 
    `email_verified`, `login_options_id`
    ) ( SELECT               `service_known_id`, `oauth_version`, 
    `request_token_url`,    `dialog_url`, 
    `access_token_url`,     `userinfo_url`, 
    `url_parameters`,       `authorization_header`, 
    `append_state_to_redirect_uri`, 
    `pin_dialog_url`,       `offline_dialog_url`, 
    `request_method`,       `data_format`, 
    `external_id`,          `first_name`, 
    `last_name`,            `user_email`, 
    `user_url`,             `user_nicename`, 
    `user_login`,           `scope`, 
    `email_verified` , %d
    FROM `" . $wpdb->prefix . "bs_oauth_services_known` 
    JOIN `" . $wpdb->prefix . "bs_oauth_userinfo_api_known` ON " . 
    $wpdb->prefix . "bs_oauth_userinfo_api_known.userinfo_api_known_id=" . 
    $wpdb->prefix . "bs_oauth_services_known.userinfo_api_known_id 
    WHERE service_known_id = %d )", $login_options_id, $service_id);

    $wpdb->query($query);
    return $wpdb->insert_id;
  }

//------------------------------------------------------------------------------
  //public function getName(){ return "blaat_oauth"; }

  public function getServices($enabled=true){
    global $wpdb;
    $table_name =      $wpdb->prefix . "bs_oauth_services_configured 
               JOIN ". $wpdb->prefix . "bs_login_generic_options 
               ON ".$wpdb->prefix . "bs_oauth_services_configured.login_options_id = ". $wpdb->prefix . "bs_login_generic_options.login_options_id"  ;

    $query = "select * from $table_name ";
    if ($enabled) $query .= " where enabled=1 ";
    
    $results = $wpdb->get_results($query);
    $services = array();    
    foreach ($results as $result) {
      $icon = NULL; // if no icon is set, the icon for previous service
                   // is used if we don't set the $icon to NULL
      if($result->custom_icon_enabled) {
        $icon= $result->custom_icon_url;
      } elseif($result->default_icon) {
        $icon=  plugin_dir_url(__FILE__) . "../icons/" . $result->default_icon;
      }
      $services[] = new BlaatLoginService("blaat_oauth",
                                       $result->service_id, 
                                       $result->display_name, 
                                       $result->sortorder, 
                                       $icon, $result->enabled,
                                       $result->login_options_id);
    }
    return $services;
  }


}
?>
