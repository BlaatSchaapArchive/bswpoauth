<?php

//------------------------------------------------------------------------------
function blaat_oauth_add_page(){
  global $_SERVER;
  $ACTION="admin.php?page=blaat_oauth_services";
  ?>
  <div class="wrap">
  <?php screen_icon(); ?>
  <h2><?php _e("BlaatSchaap OAuth Configuration","blaat_auth");?></h2>
  <p><?php  _e("Documentation:","blaat_auth");?>
    <a href="http://code.blaatschaap.be/bscp/oauth-plugin-for-wordpress/" target="_blank">
      http://code.blaatschaap.be/bscp/oauth-plugin-for-wordpress/
    </a>
  </p>
  <script>
    function updPreview(){
    document.getElementById("logoPreview").innerHTML="<span class='bs-auth-btn-logo bs-auth-btn-logo-" +
    document.getElementById("service").value.toLowerCase() +"'></span>";
    }
  </script>
  <form method='post' action='<?php echo $ACTION; ?>'>
    <table class='form-table'>
      <tr>
        <th><label><?php _e("Service","blaat_auth");?></td>
        <td>
          <select name='service' id="service" onchange="updPreview();">
          <?php 
            $oauth_servers_file = @file_get_contents(plugin_dir_path(__FILE__) . "oauth/oauth_configuration.json");
            if ($oauth_servers_file) {
              $oauth_servers = @json_decode($oauth_servers_file);
              if ($oauth_servers) {
              // services defined in code  ?>
            <option value='Facebook'>Facebook</option>
            <option value='github'>github</option>
            <option value='Google'>Google</option>
            <option value='LinkedIn'>LinkedIn</option>
            <option value='Microsoft'>Microsoft</option>
            <option value='Twitter'>Twitter</option>
            <option value='Yahoo'>Yahoo</option>

                <?php
                foreach ($oauth_servers->servers as $oauth_server => $oauth_config) {
                  echo "<option value='$oauth_server'>$oauth_server</option>";
                }
              } else $error =  "could not parse file";
            } else $error= "could not open file";
     
            if (isset($error)){?>
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
            <?php } ?> 
          </select> 
          <span id="logoPreview">
          </span>
          <script>
            updPreview();
          </script>
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
//------------------------------------------------------------------------------
function blaat_oauth_add_custom_page(){
  $ACTION="admin.php?page=blaat_oauth_services";
  ?>
  <div class="wrap">
  <?php screen_icon(); ?>
  <h2>
  <?php _e("BlaatSchaap OAuth Configuration","blaat_auth"); ?>
  </h2>
  <p><?php  _e("Documentation:","blaat_auth");?>
    <a href="http://code.blaatschaap.be/bscp/oauth-plugin-for-wordpress/" target="_blank">
      http://code.blaatschaap.be/bscp/oauth-plugin-for-wordpress/
    </a>
  </p>
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
        <td><input type='checkbox' name='client_enabled' value=1></input>
      </tr>
      <tr>
        <td></td>
        <td><input type='submit' name='add_custom_service' value='<?php _e("Add","blaat_auth"); ?>'></input>
      </tr>
    </table>
  </form>
  <?php
}
//------------------------------------------------------------------------------
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
//------------------------------------------------------------------------------
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
//------------------------------------------------------------------------------
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
//------------------------------------------------------------------------------
if (!function_exists("blaat_not_implemented")) {
  function blaat_not_implemented() {
      ?>
      <div class="error">
          <p><?php _e( 'Not Implemented!',"blaat_auth"); ?></p>
      </div>
      <?php
  }
}
//------------------------------------------------------------------------------
function blaat_oauth_update_service(){
  global $wpdb;
  $table_name = $wpdb->prefix . "bs_oauth_services";

  $new_data = array();
  $new_data["display_name"] = $_POST["display_name"];
  $new_data["client_id"] = $_POST["client_id"];
  $new_data["client_secret"] = $_POST["client_secret"];
  $new_data["default_scope"] = $_POST["default_scope"];
  $new_data["enabled"] = $_POST["client_enabled"];
  $new_data["customlogo_enabled"] = $_POST["customlogo_enabled"];

  //How to detect if a file was uploaded??
  if ($_FILES['newlogo']['size']){
    if ( ! function_exists( 'wp_handle_upload' ) ) require_once( ABSPATH . 'wp-admin/includes/file.php' );
    $uploadedfile = $_FILES['newlogo'];
    global $bs_set_filename;
    $bs_set_filename="cstlogo_".$_POST['id'].".png";

    /* delete if file already exists */
    /* PHP 5.4+ supports $path= wp_upload_dir($time)['path']; */
    $php53 = wp_upload_dir($time); $php53_path=$php53['path'];
    $bs_target_path=$php53_path."/$bs_set_filename";
    //echo "<pre>"; print_r(wp_upload_dir($time)); echo "</pre>";
    //echo "test ok: delete $bs_target_path";
    if (file_exists($bs_target_path)) unlink($bs_target_path);  

    $upload_overrides = array( 'test_form' => false, 'unique_filename_callback' => 'bs_upload_filename' );
    $movefile = wp_handle_upload( $uploadedfile, $upload_overrides );
    if (isset($movefile['file'])){
      $imginfo = getimagesize($movefile['file']);
      if ($imginfo) {
        $image  = file_get_contents($movefile['file']);
        $source = imagecreatefromstring($image);
        $target = imagecreatetruecolor(32,32);
        imagecopyresized($target,$source,0,0,0,0,32,32,$imginfo[0],$imginfo[1]); 
        imagepng($target,$movefile['file']);
        imagedestroy($target);
        imagedestroy($source);

        $new_data["customlogo_url"] = $movefile['url'];
        $new_data["customlogo_filename"] = $movefile['file'];
        $new_data["customlogo_enabled"] = 1;

	// TODO :: ERROR MESSAGES HANDLING
	// TODO :: IS IT POSSIBLE TO STORE IT ELSEWHERE? (E.G. WITHOUT DATE IN PATH)
	// TODO :: IF NOT, REMOVE OLD FILE

        //echo "file saved as " .$movefile['file'];        
      } else {echo "image error";}
    } else {echo "upload error";}
  } else {echo "no upload";}

  
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
  global $SROLLPOS;
  $SROLLPOS="<script>location.hash = '#serv-". htmlspecialchars($_POST['id']) ."';</script>";


}
//------------------------------------------------------------------------------
function bs_upload_filename(){
  global $bs_set_filename;
  return $bs_set_filename;
}
//------------------------------------------------------------------------------
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
                    append_state_to_redirect_uri, customlogo_url, customlogo_enabled
          from $table_name
          LEFT OUTER JOIN $table_name2 on ${table_name}.custom_id = ${table_name2}.id",ARRAY_A);


  foreach ($results as $result){
    $enabled= $result['enabled'] ? "checked" : "";
    ?>
  <!--</pre>--> <a name="serv-<?php echo $result['id']; ?>"></a>
  <form method='post'  enctype="multipart/form-data" action='<?php echo $ACTION ?>'>
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
        <th><label><?php _e("Logo:","blaat_auth"); ?></label></th>
        <td>
          <style>.bs-auth-btn-logo-cst<?php echo $result['id'] ?> { background-image:url('<?php echo $result['customlogo_url'];?>'); }</style>
          <?php
          if (!$result['custom_id']) {
            ?>
            <span class='bs-auth-btn-preview bs-auth-btn-logo-<?php echo strtolower($result['client_name']); ?>'></span>
            <input type='radio' name='customlogo_enabled' value='0' <?php if(!$result['customlogo_enabled']) echo "checked"; ?> > 
            <span class='bs-auth-btn-preview bs-auth-btn-logo-cst<?php echo $result['id'] ?>'></span>
            <input type='radio' name='customlogo_enabled' value='1'  <?php if ($result['customlogo_enabled']) echo "checked";?>  >
            <?php } else {?>
              <span class='bs-auth-btn-preview bs-auth-btn-logo-cst<?php echo $result['id'] ?>'></span>
            <?php } ?>
            <input type="file" name="newlogo">
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
  global $SROLLPOS;
  echo $SROLLPOS;
  }
}
//------------------------------------------------------------------------------

?>
