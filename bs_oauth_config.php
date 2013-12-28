<?php

function blaat_oauth_add_page(){
  global $_SERVER;
  $ACTION="admin.php?page=blaat_oauth_services";
  ?>
  <div class="wrap">
  <?php screen_icon(); ?>
  <h2><?php _e("BlaatSchaap OAuth Configuration","blaat_auth");?></h2>

  <form method='post' action='<?php echo $ACTION; ?>'>
    <table class='form-table'>
      <tr>
        <th><label><?php _e("Service","blaat_auth");?></td>
        <td>
          <select name='service'> 
            <option value='Bitbucket'>Bitbucket</option>
            <option value='Box'>Box</option>
            <option value='Disqus'>Disqus</option>
            <option value='Dropbox'>Dropbox</option>
            <option value='Eventful'>Eventful</option>
            <option value='Evernote'>Evernote</option>
            <option value='Facebook'>Facebook</option>
            <option value='Fitbit'>Fitbit</option>
            <option value='Flickr'>Flickr</option>
            <option value='Foursquare'>Foursquare</option>
            <option value='github'>github</option>
            <option value='Google'>Google</option>
            <option value='Instagram'>Instagram</option>
            <option value='LinkedIn'>LinkedIn</option>
            <option value='Microsoft'>Microsoft</option>
            <option value='RightSignature'>RightSignature</option>
            <option value='Salesforce'>Salesforce</option>
            <option value='Scoop.it'>Scoop.it</option>
            <option value='StockTwits'>StockTwits</option>
            <option value='Tumblr'>Tumblr</option>
            <option value='Twitter'>Twitter</option>
            <option value='XING'>XING</option>
            <option value='Yahoo'>Yahoo</option>
          </select>
        </td>
      </tr>
      <tr>
        <th><label><?php _e("Display name","blaat_auth");?></td>
        <td>
          <input type='text' name='display_name'></input>
        </td>
      </tr>
      <tr>
        <th><label><?php _e("Client ID","blaat_auth");?></td>
        <td>
          <input type='text' name='client_id'></input>
        </td>
      </tr>
      <tr>
        <th><label><?php _e("Client Secret","blaat_auth");?></td>
        <td>
          <input type='text' name='client_secret'></input>
        </td>
      </tr>
      <tr>
        <th><label><?php _e("Default Scope","blaat_auth");?></td>
        <td>
          <input type='text' name='default_scope'></input>
        </td>
      </tr>
      <tr>
        <th><label><?php _e("Enabled","blaat_auth");?></td>
        <td><input type='checkbox' name='client_enabled' value=1></input>
      </tr>
      <tr>
        <td></td>
        <td><input type='submit' name='add_service' value='Add'></input>
      </tr>
    </table>
  </form>
  <?php
}


function blaat_oauth_add_custom_page(){
  $ACTION="admin.php?page=blaat_oauth_services";
  ?>
  <div class="wrap">
  <?php screen_icon(); ?>
  <h2>
  <?php _e("BlaatSchaap OAuth Configuration","blaat_auth"); ?>
  </h2>


  <form method='post' action='<?php echo $ACTION ; ?>'>
    <table class='form-table'>
      <tr>
        <td>Display name:</td>
        <td>
	  <input type='text' name='display_name'></input>
        </td>
      </tr>
      <tr>
        <th><label><?php _e("OAuth version","blaat_auth"); ?> </td>
        <td>
	        <select name='oauth_version'>
	          <option value='1.0'>1.0</option>
	          <option value='1.0a'>1.0a</option>
	          <option value='2.0' selected>2.0</option>
	        </select>
        </td>
      </tr>
      <tr>
        <th><label><?php _e("Request Token URL (1.0 and 1.0a only)","blaat_auth"); ?></label></th>
        <td>
	  <input type='text' name='request_token_url'></input>
        </td>
      </tr>
      <tr>
        <th><label><?php _e("Dialog URL","blaat_auth"); ?></label></th>
        <td>
	  <input type='text' name='dialog_url'></input>
        </td>
      </tr>
      <tr>
        <th><label><?php _e("Access Token URL","blaat_auth"); ?></label></th>
        <td>
	  <input type='text' name='access_token_url'></input>
        </td>
      </tr>
      <tr>
        <th><label><?php _e("Offline Dialog URL (optional)","blaat_auth"); ?></label></th>
        <td>
	  <input type='text' name='offline_dialog_url'></input>
        </td>
      </tr>
      <tr>
        <th><label><?php _e("Append state to redirect (optional)","blaat_auth"); ?></label></th>
        <td>
	  <input type='text' name='append_state_to_redirect_uri'></input>
        </td>
      </tr>
      <tr>
        <th><label><?php _e("URL Parameters","blaat_auth"); ?></label></th>
        <td>
	  <input type='checkbox' name='url_parameters'></input>
        </td>
      </tr>
      <tr>
        <th><label><?php _e("Authorisation Header","blaat_auth"); ?></label></th>
        <td>
	  <input type='checkbox' name='authorization_header' value=1 selected></input>
        </td>
      </tr>
      <tr>
        <th><label><?php _e("Client ID","blaat_auth"); ?></label></th>
        <td>
	  <input type='text' name='client_id'></input>
        </td>
      </tr>
      <tr>
        <th><label><?php _e("Client Secret","blaat_auth"); ?></label></th>
        <td>
	  <input type='text' name='client_secret'></input>
        </td>
      </tr>
      </tr>
        <th><label><?php _e("Default Scope","blaat_auth"); ?></label></th>
        <td>
	  <input type='text' name='default_scope'></input>
        </td>
      </tr>
      <tr>
        <th><label><?php _e("Enabled","blaat_auth"); ?></label></th>
        <td><input type='checkbox' name='client_enabled'></input>
      </tr>
      <tr>
        <td></td>
        <td><input type='submit' name='add_custom_service' value='<?php _e("Add","blaat_auth"); ?>'></input>
      </tr>
    </table>
  </form>
  <?php
}

function blaat_oauth_add_process(){
  global $wpdb;
  global $bs_oauth_plugin;

  $service=$_POST['service'];
  $display_name=$_POST['display_name'];
  $client_id=$_POST['client_id'];
  $client_secret=$_POST['client_secret'];
  $default_scope=$_POST['default_scope'];
  $enabled = (int) $_POST['client_enabled'];
  $table_name = $wpdb->prefix . "bs_oauth_services";
   
 
  $query = $wpdb->prepare( 
	"INSERT INTO $table_name
	(        `enabled` , `display_name` , `client_name` , `client_id` , `client_secret` , `default_scope` )
	VALUES ( %d        ,  %s            ,  %s           , %s          , %s              , %s )", 
                 $enabled  , $display_name   , $service      , $client_id  ,$client_secret   , $default_scope );

  $result = $wpdb->query($query);

}

function blaat_oauth_add_custom_process(){
  global $wpdb;
  global $bs_oauth_plugin;

  $service=$_POST['service'];
  $display_name=$_POST['display_name'];
  $client_id=$_POST['client_id'];
  $client_secret=$_POST['client_secret'];
  $default_scope=$_POST['default_scope'];
  $enabled = (int) $_POST['client_enabled'];
  
  $table_name = $wpdb->prefix . "bs_oauth_custom";
  $oauth_version=$_POST['oauth_version'];
  $request_token_url=$_POST['request_token_url'];
  $dialog_url=$_POST['dialog_url'];
  $access_token_url=$_POST['access_token_url'];
  $url_parameters=(int) $_POST['url_parameters'];
  $authorization_header=(int) $_POST['authorization_header'];
  $offline_dialog_url=$_POST['offline_dialog_url'];
  $append_state_to_redirect_uri=$_POST['append_state_to_redirect_uri'];

  $query = $wpdb->prepare(
        "INSERT INTO $table_name ( `oauth_version`, `request_token_url`, `dialog_url`, `access_token_url`, `url_parameters`, 
                                 `authorization_header`, `offline_dialog_url`, `append_state_to_redirect_uri` )
         VALUES ( %s , %s , %s , %s, %d , %d , %s , %s ) " , 
                                 $oauth_version, $request_token_url, $dialog_url, $access_token_url, $url_parameters,
                                 $authorization_header, $offline_dialog_url, $append_state_to_redirect_uri);

  //print_r($query);
  //die();
  $result = $wpdb->query($query);

  $insert_id = $wpdb->insert_id;
  $table_name = $wpdb->prefix . "bs_oauth_services";
  $query = $wpdb->prepare(
        "INSERT INTO $table_name
        (        `enabled` , `display_name` , `custom_id` , `client_id` , `client_secret` , `default_scope` )
        VALUES ( %d        ,  %s            ,  %d           , %s          , %s              , %s )",
                 $enabled  , $display_name   , $insert_id    , $client_id  ,$client_secret   , $default_scope );
  $result = $wpdb->query($query);


}

function blaat_oauth_delete_service(){
  global $wpdb;
  global $bs_oauth_plugin;

  // If the service to delete is a custom service, delete the custom service entry
  $table_name = $wpdb->prefix . "bs_oauth_services";
  $query = $wpdb->prepare("select custom_id from $table_name   WHERE id = %d", $_POST['id']);  
  $results = $wpdb->get_results($query,ARRAY_A);
  $result = $results[0]['custom_id'];
  if ($result) {
    $table_name = $wpdb->prefix . "bs_oauth_custom";
    $query = $wpdb->prepare("DELETE FROM $table_name  WHERE id = %d", $result);
    $wpdb->query($query);    
  }

  // Delete the service entry
  $table_name = $wpdb->prefix . "bs_oauth_services";
  $query = $wpdb->prepare("DELETE FROM $table_name  WHERE id = %d", $_POST['id']);
  $wpdb->query($query);
}

if (!function_exists("blaat_not_implemented")) {
  function blaat_not_implemented() {
      ?>
      <div class="error">
          <p><?php _e( 'Not Implemented!',"blaat_auth"); ?></p>
      </div>
      <?php
  }
}


function blaat_oauth_update_service(){
  global $wpdb;
  $table_name = $wpdb->prefix . "bs_oauth_services";

  $new_data = array();
  $new_data["display_name"] = $_POST["display_name"];
  $new_data["client_id"] = $_POST["client_id"];
  $new_data["client_secret"] = $_POST["client_secret"];
  $new_data["default_scope"] = $_POST["default_scope"];
  $new_data["enabled"] = $_POST["client_enabled"];

  $data_id = array();
  $data_id['id']  = $_POST['id'];

  $wpdb->update($table_name, $new_data, $data_id);

  if (isset($_POST['custom_id'])){
    $table_name = $wpdb->prefix . "bs_oauth_custom";

    $new_data = array();
    $new_data['oauth_version']=$_POST['oauth_version'];
    $new_data['request_token_url']=$_POST['request_token_url'];
    $new_data['dialog_url']=$_POST['dialog_url'];
    $new_data['access_token_url']=$_POST['access_token_url'];
    $new_data['offline_dialog_url']=$_POST['offline_dialog_url'];
    $new_data['append_state_to_redirect_uri']=$_POST['append_state_to_redirect_uri'];
    $new_data['authorization_header']=$_POST['authorization_header'];
    $new_data['url_parameters']=$_POST['url_parameters'];

    $data_id = array();
    $data_id['id']  = $_POST['custom_id'];
    $wpdb->update($table_name, $new_data, $data_id);
    
  }
   

}

function blaat_oauth_list_services(){
  global $wpdb;
  global $bs_oauth_plugin;
  global $_SERVER;
  $ACTION=htmlspecialchars($_SERVER['REQUEST_URI']);// . '?' . $_SERVER['QUERY_STRING'];
  $table_name  = $wpdb->prefix . "bs_oauth_services";
  $table_name2 = $wpdb->prefix . "bs_oauth_custom";
  /*
  $results = $wpdb->get_results("select * from $table_name
          LEFT OUTER JOIN $table_name2 on ${table_name}.custom_id = ${table_name2}.id",ARRAY_A);
  */

  /* Selecting all rows manually due both tables having a column id
     The WordPress Database Class does not understand it should use
     the first one, the way it would work with for example the old mysql_* 
     database interface
  */
  $results = $wpdb->get_results("select ${table_name}.id id, client_name, 
                    enabled, display_name, custom_id, client_id, client_secret,
                    default_scope, oauth_version, request_token_url, 
                    dialog_url, access_token_url, url_parameters, 
                    authorization_header, offline_dialog_url, 
                    append_state_to_redirect_uri
          from $table_name
          LEFT OUTER JOIN $table_name2 on ${table_name}.custom_id = ${table_name2}.id",ARRAY_A);


  foreach ($results as $result){
    $enabled= $result['enabled'] ? "checked" : "";
    ?>
  </pre>
  <form method='post' action='<?php echo $ACTION ?>'>
    <input type='hidden' name='id' value='<?php echo $result['id']; ?>'>
    <table class='form-table'>
      <tr>
        <th><label><?php _e("Service","blaat_auth"); ?></label></th>
        <?php
        if (!$result['custom_id']) {
          ?><td><?php echo $result['client_name'] ?></td>
        <?php } else { ?>
          <td><?php _e("Custom","blaat_auth"); ?></td> 
            <input type="hidden" name="custom_id" value="<?php 
              echo $result['custom_id']; ?>">
          
        </tr>
        <tr>
          <th><label><?php _e("OAuth version","blaat_auth"); ?></label></th>
          <td>
            <select name='oauth_version'>
              <option value='1.0' 
                <?php if ($result["oauth_version"]=="1.0" ) echo "selected" ;?>              
              >1.0</option>
              <option value='1.0a'
                <?php if ($result["oauth_version"]=="1.0a") echo "selected" ;?> 
              >1.0a</option>
              <option value='2.0' 
                <?php if ($result["oauth_version"]=="2.0" ) echo "selected" ;?> 
              >2.0</option>
            </select>
          </td>
        </tr>
        <tr>
          <th><label><?php _e("Request Token URL (1.0 and 1.0a only)","blaat_auth"); ?></label></th>
          <td>
            <input type='text' name='request_token_url'
              value='<?php echo $result['request_token_url']; ?>'></input>
          </td>
        </tr>
        <tr>
          <th><label><?php _e("Dialog URL","blaat_auth"); ?></label></th>
          <td>
            <input type='text' name='dialog_url' 
                value='<?php echo $result['dialog_url']; ?>'></input>
          </td>
        </tr>
        <tr>
          <th><label><?php _e("Access Token URL","blaat_auth"); ?></label></th>
          <td>
            <input type='text' name='access_token_url'
              value='<?php echo $result['access_token_url']; ?>'></input>
          </td>
        </tr>
        <tr>
          <th><label><?php _e("Offline Dialog URL (optional)","blaat_auth"); ?></label></th>
          <td>
            <input type='text' name='offline_dialog_url'
              value='<?php echo $result['offline_dialog_url']; ?>'></input>
          </td>
        </tr>
        <tr>
          <th><label><?php _e("Append state to redirect (optional)","blaat_auth"); ?></label></th>
          <td>
            <input type='text' name='append_state_to_redirect_uri'
                value='<?php echo $result['append_state_to_redirect_ur']; ?>' ></input>
          </td>
        </tr>
          <th><label><?php _e("URL Parameters","blaat_auth"); ?></label></th>
          <td>
            <input type='checkbox' name='url_parameters' value=1
              <?php  if ($result['url_parameters']) echo "checked"; ?>
             ></input>
          </td>
        </tr>
        <tr>
          <th><label><?php _e("Authorisation Header","blaat_auth"); ?></label></th>
          <td>
            <input type='checkbox' name='authorization_header' value=1 
              <?php  if ($result['authorization_header']) echo "checked"; ?>
            ></input>
          </td>
        <?php     
      }
      ?>
      </tr><tr>
        <th><label><?php _e("Display name","blaat_auth"); ?></label></th>
        <td>
          <input type='text' name='display_name' value='<?php echo $result['display_name']; ?>'></input>
        </td>
      </tr>
      <tr>
        <th><label><?php _e("Client ID","blaat_auth"); ?></label></th>
        <td>
          <input type='text' name='client_id' value='<?php echo $result['client_id']; ?>'></input>
        </td>
      </tr>
      <tr>
        <th><label><?php _e("Client Secret","blaat_auth"); ?></label></th>
        <td>
          <input type='text' name='client_secret' value='<?php echo $result['client_secret']; ?>'></input>
        </td>
      </tr>
      <tr>
        <th><label><?php _e("Default Scope","blaat_auth"); ?></label></th>
        <td>
          <input type='text' name='default_scope' value='<?php echo $result['default_scope']; ?>'></input>
        </td>
      </tr>
      <tr>
        <th><label><?php _e("Enabled","blaat_auth"); ?></label></th>
        <td><input type='checkbox' name='client_enabled' value=1 <?php echo $enabled; ?>></input>
      </tr>
      <tr>
        <td></td><td><input type='submit' name='delete_service' value='Delete'>
        <input type='submit' name='update_service' value='Update'></input>
      </tr>
    </table>
  </form>
  <hr>
  <?php
  }
}


?>
