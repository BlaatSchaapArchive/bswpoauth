<?php



class OAuth implements AuthService {
//------------------------------------------------------------------------------
  public function canLogin(){
    // TODO: better ways of detecting
    
    return ($_SESSION['bsoauth_id'] || $_REQUEST['bsoauth_id'] ||
          $_SESSION['bsauth_link'] || $_REQUEST['bsauth_link'] ||   // TODO change this!!!!
          $_SESSION['bsauth_login'] || $_REQUEST['bsauth_login'] || // TODO change this!!!!
          $_SESSION['bsauth_display'] || $_REQUEST['bsauth_display']||
          $_REQUEST['code']); // detect?
    
  }
//------------------------------------------------------------------------------
  public function Login($service_id){

    try {
      self::process('self::process_login', $service_id);
    } catch (Exception $e) {
      // TODO error handling
        switch ($e->getMessage()) {
          case "missing_token" :
            _e("OAuth error: the token is missing","blaat_auth");
            echo $client->error;
            break;
          case "processing_error":
            _e("OAuth error: processing error","blaat_auth");
            echo $client->error;
            break;
          case "initialisation_error":
            _e("OAuth error: initialisation error","blaat_auth");
            echo $client->error;
            break;
          default:
            _e("Unknown error","blaat_auth");
        }
    }
  }
  
//------------------------------------------------------------------------------
  public function Link($service_id){
    try {
      self::process('self::process_link',  $service_id);
    } catch (Exception $e) {
        switch ($e->getMessage()) {
          case "missing_token" :
            _e("OAuth error: the token is missing","blaat_auth");
            echo $client->error;
            break;
          case "processing_error":
            _e("OAuth error: processing error","blaat_auth");
            echo $client->error;
            break;
          case "initialisation_error":
            _e("OAuth error: initialisation error","blaat_auth");
            echo $client->error;
            break;
          default:
            _e("Unknown error","blaat_auth");
      }
    }
  }
  
//------------------------------------------------------------------------------
  public function getRegisterData(){
    return NULL;
  }
//------------------------------------------------------------------------------
  public function Delete($user_id){
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
  public function process($function, $service_id){

    global $wpdb; // Database functions



    /*
      Is this code still needed? Looks like a fragment of code that no longer
      has any meaning

    if ($_POST['bsauth_display']) {
      $_REQUEST['bsoauth_id'] = $_POST['bsauth_display'];
      $_SESSION['bsauth_display'] = $_POST['bsauth_display'];
    }
    */


  
      if (isset($_REQUEST['bsoauth_id'])) $_SESSION['bsoauth_id']=$_REQUEST['bsoauth_id'];



      $table_name = $wpdb->prefix . "bs_oauth_services";
      $query = $wpdb->prepare("SELECT * FROM $table_name  WHERE id = %d", $service_id);

      $results = $wpdb->get_results($query,ARRAY_A);
      $result = $results[0];
     
      $client = new oauth_client_class;


      // DEBUGGING
      $client->debug=true;


      $client->configuration_file = plugin_dir_path(__FILE__) . '../oauth/oauth_configuration.json';
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
     
      if ($success = $client->Initialize()) {
        if ($success = $client->Process()) {
          if (strlen($client->access_token)) {
            call_user_func($function, $client, $result['display_name'], $service_id);
            $success = $client->Finalize($success);
          } else {
            throw new Exception("missing_token");
            /*            
            _e("OAuth error: the token is missing","blaat_auth");
            echo $client->error;
            */
          }
        } else {

            throw new Exception("processing_error");
            
          /*
          _e("OAuth error: processing error","blaat_auth");
          echo $client->error;
          */
        }
      } else {
        throw new Exception("initialisation_error");
        /*
        _e("OAuth error: initialisation error","blaat_auth");
        echo $client->error;
        */
      } 

  }
//------------------------------------------------------------------------------
  public function  install() {
    global $wpdb;
    global $bs_oauth_plugin;
    $dbver = 4;
    $live_dbver = get_option( "bs_oauth_dbversion" );
    $table_name = $wpdb->prefix . "bs_oauth_sessions";

    if ($dbver != $live_dbver) {
      require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
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
                PRIMARY KEY  (id)
                );";
      dbDelta($query);

   
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
                PRIMARY KEY  (id)
                );";
      dbDelta($query);


    $table_name = $wpdb->prefix . "bs_oauth_custom";
      $query = "CREATE TABLE $table_name (
                `id` INT NOT NULL AUTO_INCREMENT   ,
                `oauth_version` ENUM('1.0','1.0a','2.0') DEFAULT '2.0',
                `request_token_url` TEXT NULL DEFAULT NULL,
                `dialog_url` TEXT NOT NULL,
                `access_token_url` TEXT NOT NULL,
                `url_parameters` BOOLEAN DEFAULT FALSE,
                `authorization_header` BOOLEAN DEFAULT TRUE,
                `offline_dialog_url` TEXT NULL DEFAULT NULL,
                `append_state_to_redirect_uri` TEXT NULL DEFAULT NULL,
                PRIMARY KEY  (id)
                );";
      dbDelta($query);
      update_option( "bs_oauth_dbversion" , $dbver);
    }
  }
//------------------------------------------------------------------------------
  public function  process_link($client,$service,$service_id) {


      unset($_SESSION['bsoauth_id']);
      unset($_SESSION['bsauth_link']);
      unset($_SESSION['oauth_token']);
      unset($_SESSION['oauth_expiry']);
      unset($_SESSION['oauth_scope']);
      unset($_SESSION['bsauth_display']);
      unset($_SESSION['bsauth_link_id']);



    global $wpdb;    
    $user = wp_get_current_user();
    $user_id    = $user->ID;
    $token      = $client->access_token;
    $expiry     = $client->access_token_expiry;
    $scope      = $client->scope;
    //$service    = $_SESSION['bsauth_display'];


    $table_name = $wpdb->prefix . "bs_oauth_sessions";
    // We need to verify the external account is not already linked
    // before we insert!!!
    


    $testQuery = $wpdb->prepare("SELECT * FROM $table_name 
                                 WHERE service_id = %d 
                                 AND   token = %s" , $service_id, $token);
    $testResult = $wpdb->get_results($testQuery,ARRAY_A);



            /*
        What the fuck is happening.... when I run the query manually.... it 
        just answers the existing record.... however, when I run it in wordpress
        it doesn't appear to answer most of the times, but occasionally it answers.


      echo "<pre>testQuery\n$testQuery\n";
      $wpdb->print_error();
      echo "<pre>Service ID: $service_id \nToken:$token \ntestResult: "; print_r($testResult); 


      echo "\n\nQUERY" . $wpdb->last_query ."</pre><br>";
      echo "\n\nERROR" . $wpdb->last_error ."</pre><br>";

    */

      

      if (count($testResult)) {
        printf( __("Your %s account has is already linked to another local account", "blaat_auth"), $service );
      } else {
        

        $query = $wpdb->prepare("INSERT INTO $table_name (`user_id`, `service_id`, `token`, `expiry`, `scope` )
                                         VALUES      ( %d      ,  %d         ,  %s    , %s      , %s      )",
                                                      $user_id , $service_id , $token , $expiry , $scope  );
        $wpdb->query($query);
        printf( __("Your %s account has been linked", "blaat_auth"), $service );
        
      }






  }
  public function Unlink ($id) {
    global $wpdb;    
    $table_name = $wpdb->prefix . "bs_oauth_sessions";
    $table_name2 = $wpdb->prefix . "bs_oauth_services";
    $query2 = $wpdb->prepare("Select display_name from $table_name2 where id = %d", $id );
    $service_name = $wpdb->get_results($query2,ARRAY_A);
    $service = $service_name[0]['display_name'];
    $query = $wpdb->prepare ("Delete from $table_name where user_id = %d AND service_id = %d", get_current_user_id(), $id );
    $wpdb->query($query);
    printf( __("You are now unlinked from %s.", "blaat_auth"), $service );
    unset($_SESSION['bsauth_unlink']);

  }
//------------------------------------------------------------------------------
  private function process_login($client,$display_name,$service_id){


    global $wpdb;
    $_SESSION['bsauth_display'] = $display_name;

    if ( is_user_logged_in() && !$_SESSION['bsauth_registered']) { 
      $_SESSION['oauth_token']   = $client->access_token;
      $_SESSION['oauth_expiry']  = $client->access_token_expiry;
      $_SESSION['oauth_scope']   = $client->scope;
      header("Location: ".site_url("/".get_option("link_page")). '?' . $_SERVER['QUERY_STRING']);
      
    } else {
      


      
      $token = $client->access_token;
      $table_name = $wpdb->prefix . "bs_oauth_sessions";

      $query = $wpdb->prepare("SELECT `user_id` FROM $table_name WHERE `service_id` = %d AND `token` = %d",$service_id,$token);  
      $results = $wpdb->get_results($query,ARRAY_A);
      $result = $results[0];

      if ($result) {
        unset ($_SESSION['bsauth_login']);  
        unset($_SESSION['bsauth_login_id']);
        wp_set_current_user ($result['user_id']);
        wp_set_auth_cookie($result['user_id']);
        header("Location: ".site_url("/".get_option("login_page")));     
      } else {
        $_SESSION['bsauth_register'] = "blaat_oauth-$service_id";
        $_SESSION['oauth_signup']  = 1;
        $_SESSION['oauth_token']   = $client->access_token;
        $_SESSION['oauth_expiry']  = $client->access_token_expiry;
        $_SESSION['oauth_scope']   = $client->scope;
        $_SESSION['bsauth_fetch_data'] = 0;
        $_SESSION['bsauth_register_auto'] = 0;
        header("Location: ".site_url("/".get_option("register_page")));
      }
    }
  }
//------------------------------------------------------------------------------
}
?>
