<?php
require_once("oauth/oauth_client.php");
require_once("oauth/http.php");

class bsOAuthClient extends oauth_client_class {

  function StoreAccessToken($access_token){
    echo "<pre>";
    print_r($access_token);
    print_r($_SESSION);
  }
}

?>
