<?php



class OAuth implements AuthService {

  public function canLogin(){
    return ($_SESSION['bsoauth_id'] || $_REQUEST['bsoauth_id'] ||
          $_SESSION['bsoauth_link'] || $_REQUEST['bsoauth_link']);
  }

  public function Login(){
    //$this->process('$this->process_login');
    self::process('self::process_login');
  }

  public function getButtons(){
    global $wpdb;
    $table_name = $wpdb->prefix . "bs_oauth_services";
    $results = $wpdb->get_results("select * from $table_name where enabled=1 ",ARRAY_A);
    $buttons = array();    
    foreach ($results as $result) {
      $button = array();
      if(!$result['customlogo_enabled']) 
        $service=strtolower($result['client_name']); 
      else {
        $service="custom-".$result['id'];
        $button['css']="<style>.bs-auth-btn-logo-".$service." {background-image:url('" .$result['customlogo_url']."');}</style>"; 
      }
      $button['button']="<button class='bs-auth-btn' name=bsoauth_id type=submit value='".$result['id']."'><span class='bs-auth-btn-logo bs-auth-btn-logo-$service'></span><span class='bs-auth-btn-text'>". $result['display_name']."</span></button>";
      $button['order']=$result['display_order'];
      $buttons[]=$button;
    }
    return $buttons;
  }

  public function process($function){
    global $wpdb; // Database functions

    if ($_POST['bsoauth_link']) {
      $_REQUEST['bsoauth_id'] = $_POST['bsoauth_link'];
      $_SESSION['bsoauth_link'] = $_POST['bsoauth_link'];
    }

    if ($_REQUEST['bsoauth_id'] ||  $_REQUEST['code'] || $_REQUEST['oauth_token'] ) {
      if ($_REQUEST['bsoauth_id']) $_SESSION['bsoauth_id']=$_REQUEST['bsoauth_id'];


      $table_name = $wpdb->prefix . "bs_oauth_services";
      $query = $wpdb->prepare("SELECT * FROM $table_name  WHERE id = %d", $_SESSION['bsoauth_id']);

      $results = $wpdb->get_results($query,ARRAY_A);
      $result = $results[0];
     
      $client = new oauth_client_class;
      $client->configuration_file = plugin_dir_path(__FILE__) . '/oauth/oauth_configuration.json';
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
            call_user_func($function, $client, $result['display_name']);
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
  }

  private function process_login($client, $displayname){
  global $wpdb;

  $_SESSION['oauth_display'] = $displayname;

  if ( is_user_logged_in() ) { 
      $_SESSION['oauth_token']   = $client->access_token;
      $_SESSION['oauth_expiry']  = $client->access_token_expiry;
      $_SESSION['oauth_scope']   = $client->scope;
      header("Location: ".site_url("/".get_option("link_page")). '?' . $_SERVER['QUERY_STRING']);     
  } else {
    
    $service_id = $_SESSION['bsoauth_id'];
    $token = $client->access_token;
    $table_name = $wpdb->prefix . "bs_oauth_sessions";

    $query = $wpdb->prepare("SELECT `user_id` FROM $table_name WHERE `service_id` = %d AND `token` = %d",$service_id,$token);  
    $results = $wpdb->get_results($query,ARRAY_A);
    $result = $results[0];

    if ($result) {
      unset ($_SESSION['bsoauth_id']);  
      wp_set_current_user ($result['user_id']);
      wp_set_auth_cookie($result['user_id']);
      header("Location: ".site_url("/".get_option("login_page")));     
    } else {
      $_SESSION['bsauth_registering'] = 1;
      $_SESSION['oauth_signup']  = 1;
      $_SESSION['oauth_token']   = $client->access_token;
      $_SESSION['oauth_expiry']  = $client->access_token_expiry;
      $_SESSION['oauth_scope']   = $client->scope;
      header("Location: ".site_url("/".get_option("register_page")));
    }
  }
}

}
?>
