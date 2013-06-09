<?php

function blaat_oauth_add_form(){
global $_SERVER;
$ACTION=$_SERVER['REQUEST_URI'];// . '?' . $_SERVER['QUERY_STRING'];
echo "
<form method='post' action='$ACTION'>
  <table>
    <tr>
      <td>Service:</td>
      <td>
        <select name='service'> 
          <option value='Bitbucket'>Bitbucket</option>
          <option value='Box'>Box</option>
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
      <td>Display name:</td>
      <td>
        <input type='text' name='displayname'></input>
      </td>
    </tr>
    <tr>
      <td>Client ID:</td>
      <td>
        <input type='text' name='client_id'></input>
      </td>
    </tr>
    <tr>
      <td>Client Secret:</td>
      <td>
        <input type='text' name='client_secret'></input>
      </td>
    </tr>
    <tr>
      <td>    <tr>
      <td>Client Secret:</td>
      <td>
        <input type='text' name='client_secret'></input>
      </td>
    </tr>
    <tr>
      <td>Default Scope:</td>
      <td>
        <input type='text' name='default_scope'></input>
      </td>
    </tr>
    <tr>
      <td>Enabled:</td>
      <td><input type='checkbox' name='client_enabled' value=1></input>
    </tr>
    <tr>
      <td></td>
      <td><input type='submit' name='add_service' value='Add'></input>
    </tr>
  </table>
</form>
";
}


function blaat_oauth_add_custom_form(){
$ACTION=$_SERVER['REQUEST_URI'];// . '?' . $_SERVER['QUERY_STRING'];
echo "
<form method='post' action='$ACTION'>
  <table>
    <tr>
      <td>Display name:</td>
      <td>
        <input type='text' name='displayname'></input>
      </td>
    </tr>
    <tr>
      <td>OAuth version:</td>
      <td>
        <select>
          <option value='1.0'>1.0</option>
          <option value='1.0a'>1.0a</option>
          <option value='2.0' selected>2.0</option>
        </select>
      </td>
    </tr>
    <tr>
      <td>Request Token URL (1.0 and 1.0a only)</td>
      <td>
        <input type='text' name='request_token_url'></input>
      </td>
    </tr>
    <tr>
      <td>Dialog URL</td>
      <td>
        <input type='text' name='dialog_url'></input>
      </td>
    </tr>
    <tr>
      <td>Access Token URL</td>
      <td>
        <input type='text' name='access_token_url'></input>
      </td>
    </tr>
    <tr>
      <td>Offline Dialog URL (optional)</td>
      <td>
        <input type='text' name='offline_dialog_url'></input>
      </td>
    </tr>
    <tr>
      <td>Append state to redirect (optional)</td>
      <td>
        <input type='text' name='append_state_to_redirect_uri'></input>
      </td>
    </tr>
    <tr>
      <td>URL Parameters</td>
      <td>
        <input type='checkbox' name='url_parameters'></input>
      </td>
    </tr>
    <tr>
      <td>Authorisation Header</td>
      <td>
        <input type='checkbox' name='authorization_header' value=1 checked></input>
      </td>
    </tr>
    <tr>
      <td>Client ID:</td>
      <td>
        <input type='text' name='client_id'></input>
      </td>
    </tr>
    <tr>
      <td>Client Secret:</td>
      <td>
        <input type='text' name='client_secret'></input>
      </td>
    </tr>
    </tr>
      <td>Default Scope:</td>
      <td>
        <input type='text' name='default_scope'></input>
      </td>
    </tr>
    <tr>
      <td>Enabled:</td>
      <td><input type='checkbox' name='client_enabled'></input>
    </tr>
    <tr>
      <td></td>
      <td><input type='submit' name='add_custom_service' value='Add'></input>
    </tr>
  </table>
</form>
";

}

function blaat_oauth_add_process(){
  global $wpdb;
  global $bs_oauth_plugin;

  $service=$_POST['service'];
  $displayname=$_POST['displayname'];
  $client_id=$_POST['client_id'];
  $client_secret=$_POST['client_secret'];
  $default_scope=$_POST['default_scope'];
  $enabled = (int) $_POST['client_enabled'];
  $table_name = $wpdb->prefix . "bs_oauth_services";
   
 
  $query = $wpdb->prepare( 
	"INSERT INTO $table_name
	(        `enabled` , `display_name` , `client_name` , `client_id` , `client_secret` , `default_scope` )
	VALUES ( %d        ,  %s            ,  %s           , %s          , %s              , %s )", 
                 $enabled  , $displayname   , $service      , $client_id  ,$client_secret   , $default_scope );

  $result = $wpdb->query($query);

}

function blaat_oauth_delete_service(){
  global $wpdb;
  global $bs_oauth_plugin;
  $table_name = $wpdb->prefix . "bs_oauth_services";

  /*!! TODO: handle custom !!*/

  $query = $wpdb->prepare("DELETE FROM $table_name  WHERE id = %d", $_POST['id']);
  $wpdb->query($query);
}

function blaat_oauth_update_service(){
  // TODO: Implement me!
}

function blaat_oauth_list_services(){
  global $wpdb;
  global $bs_oauth_plugin;
  global $_SERVER;
  $ACTION=$_SERVER['REQUEST_URI'];// . '?' . $_SERVER['QUERY_STRING'];

  $table_name = $wpdb->prefix . "bs_oauth_services";

  $results = $wpdb->get_results("select * from $table_name ",ARRAY_A);

  foreach ($results as $result){
  
    $enabled= $result['enabled'] ? "checked" : "";
    echo"
<form method='post' action='$ACTION'>
  <input type=hidden name=id value='".$result['id']."'>
  <table>
    <tr>
      <td>Service:</td>";
    if (strlen($result['client_name'])) {
      echo " <td> " . $result['client_name']. "</td>";
    } else {
      echo "<td>Custom</td></tr><tr>";
      
      echo "  --NOT YET IMPLEMENTED <br>
      <td>OAuth version:</td>
      <td>
        <select>
          <option value='1.0'>1.0</option>
          <option value='1.0a'>1.0a</option>
          <option value='2.0' selected>2.0</option>
        </select>
      </td>
    </tr>
    <tr>
      <td>Request Token URL (1.0 and 1.0a only)</td>
      <td>
        <input type='text' name='request_token_url'></input>
      </td>
    </tr>
    <tr>
      <td>Dialog URL</td>
      <td>
        <input type='text' name='dialog_url'></input>
      </td>
    </tr>
    <tr>
      <td>Access Token URL</td>
      <td>
        <input type='text' name='access_token_url'></input>
      </td>
    </tr>
    <tr>
      <td>Offline Dialog URL (optional)</td>
      <td>
        <input type='text' name='offline_dialog_url'></input>
      </td>
    </tr>
    <tr>
      <td>Append state to redirect (optional)</td>
      <td>
        <input type='text' name='append_state_to_redirect_uri'></input>
      </td>
    </tr>
      <td>URL Parameters</td>
      <td>
        <input type='checkbox' name='url_parameters'></input>
      </td>
    </tr>
    <tr>
      <td>Authorisation Header</td>
      <td>
        <input type='checkbox' name='authorization_header' value=1 checked></input>
      </td>";
     
    }
    
    echo "</tr><tr>
      <td>Display name:</td>
      <td>
        <input type='text' name='displayname' value='".$result['display_name']."'></input>
      </td>
    </tr>
    <tr>
      <td>Client ID:</td>
      <td>
        <input type='text' name='client_id' value='".$result['client_id']."'></input>
      </td>
    </tr>
    <tr>
      <td>Client Secret:</td>
      <td>
        <input type='text' name='client_secret' value='".$result['client_secret']."'></input>
      </td>
    </tr>
    <tr>
      <td>Default Scope:</td>
      <td>
        <input type='text' name='default_scope' value='".$result['default_scope']."'></input>
      </td>
    </tr>
    <tr>
      <td>Enabled:</td>
      <td><input type='checkbox' name='client_enabled' value=1 $enabled></input>
    </tr>
    <tr>
      <td><input type='submit' name='delete_service' value='Delete'></td>
      <td><input type='submit' name='update_service' value='Update'></input>
    </tr>
  </table>
</form><hr>";

  }

  

}


?>
