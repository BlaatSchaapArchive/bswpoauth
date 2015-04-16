<?php

//------------------------------------------------------------------------------
// BlaatSchaap Plugins Page
//------------------------------------------------------------------------------
if (!function_exists("blaat_plugins_page")) {
  function blaat_plugins_page(){
    echo '<div class="wrap">';
    echo '<h2>';
    _e("BlaatSchaap Plugins","blaatschaap");
    echo '</h2><div style="text-align:center;">
                         <style>.blaat_plugins_table{text-align:left;}</style>';
    _e("Thank you for using BlaatSchaap plugins.","blaatschaap");

    // ok, we need to detect installed plugins and so
    if ( ! function_exists( 'get_plugins' ) ) {
      require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    $plugins = get_plugins();
    function isBS($name){
      return strpos($name, "BlaatSchaap") === 0;
    }
    echo "<p>";
    _e("Installed BlaatSchaap plugins:","blaatschaap");
    echo "</p>";
    // TODO: bundle logo with plugin, also, resize logo
    // But as all admin pages are going to be rewritten for the 0.5 release
    // this should do it for now.
    echo "<img src='http://www.blaatschaap.be/sheep.png' width=125>";
    echo "<span style='display:inline-block;'><table class='blaat_plugins_table'>";
    echo "<tr><th>";
    _e("Plugin name:","blaatschaap");
    echo "</th><th>";
    _e("Plugin version:","blaatschaap");
    echo "</th><th>";
    _e("Status:","blaatschaap");
    echo "</th></tr>";
    foreach ($plugins as $file => $plugin) {
     if (isBS($plugin['Name'])) {
        echo "<tr><td>".$plugin['Name']."</td><td>".$plugin['Version']."</td><td>";
        echo is_plugin_active($file) ? _e("active","blaatschaap") : _e("inactive","blaatschaap");
        echo "</td></tr>";
      }
    }
    
    echo "</table></span><hr><div style='text-align:center;'>";
    _e("Donations play an important role in supporting open-source projects. We greatly appreciate any donation you can make to help us support further development of free products!","blaatschaap");
    ?>
<br><br>

<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----
">
<input type="image" src="https://www.paypalobjects.com/en_US/NL/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal, de veilige en complete manier van online betalen.">
<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
</form>


      <table style="display:inline-block;">
        <tr><td>Bitcoin</td><td>1NMv9ETkYrMeg53hN66egrFQ4tnaPLmM29</td></tr>
        <tr><td>Litecoin</td><td>LVPQtPn93GaAeczhUSengQzkQNpe3pZjnT</td></tr>
      </table>
    </div>
  </div>
</div>
<?php
  }
}
//------------------------------------------------------------------------------
if (!function_exists("blaat_page_registered")){
  function blaat_page_registered($menu_slug){
    global $_parent_pages;
    return isset($_parent_pages[$menu_slug]) ;
  }
}
//------------------------------------------------------------------------------
if (!function_exists("blaat_page_select")) {
  function blaat_page_select($item){
    $pages = get_pages();
    $blaat = "<select id='$item' name='$item'>";
    foreach ( $pages as $page ) {
      $pagename = $page->post_name;
      $selected = (get_option($item)==$pagename) ? "selected='selected'" : "";
      $option = "<option value='$pagename' $selected>";
      $option .= $page->post_title;
      $option .= "</option>";
      $blaat .= $option;
    }
    $blaat .= "</select>";
    return $blaat;
  }
}
//-----------------------------------------------------------------------------
if (!function_exists("blaat_has_session_started")){
  function blaat_has_session_started(){
    if ( php_sapi_name() !== 'cli' ) {
      if ( version_compare(phpversion(), '5.4.0', '>=') ) {
        return session_status() === PHP_SESSION_ACTIVE;
      } else {
        return strlen(session_id());
      }
    }
    return false;
  }
}
//------------------------------------------------------------------------------

if (!function_exists("blaat_session_start")){
  function blaat_session_start(){
    if(!(blaat_has_session_started())) session_start();
  }
}
//------------------------------------------------------------------------------
if (!function_exists("blaat_session_flush")){
  function blaat_session_flush(){
    if(!(blaat_has_session_started())) {
      session_write_close();
      session_start();
    }
  }
}
//------------------------------------------------------------------------------
if (!function_exists("blaat_get_current_url")){
  function blaat_get_current_url($include_query=false, $strip_trailing_slash=false){
    $is_ssl =  (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off');
    $current_url  = $is_ssl ? "https://" : "http://";
    $default_port = $is_ssl ? 443 : 80  ;
    $current_url .= $_SERVER["SERVER_NAME"];

    if ($_SERVER["SERVER_PORT"] != $default_port) {
      $current_url .= ":" . $_SERVER["SERVER_PORT"];
    }  
    $current_url .= strtok( $_SERVER["REQUEST_URI"], "?");
    if ($strip_trailing_slash) {
      //!! TODO
    }
    if ($include_query) $current_url .= "?" . $_SERVER["QUERY_STRING"];
    return $current_url;
  }
}

//------------------------------------------------------------------------------
// Generate configuration option for the requirements of fields
// (Disabled/Optional/Required)
//------------------------------------------------------------------------------
if (!function_exists("blaat_option_req3_generate")){
  function blaat_option_req3_generate($option_name,$option_title, $echo=true){

    $option_value=get_option($option_name);
    $result = '<tr><th>';
    $result .= $option_title;
    $result .= "</th><td>";
    $result .= "<select name='$option_name'>";

    $selected = ($option_value=="Disabled") ? "selected='selected'" : "";
    $result .= "<option value='Disabled' $selected>";
    $result .= __("Disabled" , "blaatschaap");
    $result .=  "</option>";

    $selected = ($option_value=="Optional") ? "selected='selected'" : "";
    $result .= "<option value='Optional' $selected>";
    $result .= __("Optional" , "blaatschaap");
    $result .=  "</option>";

    $selected = ($option_value=="Required") ? "selected='selected'" : "";
    $result .= "<option value='Required' $selected>";
    $result .=__("Required" , "blaatschaap");
    $result .=  "</option>";

    $result .= "</select>";
    $result .= "</td></tr>";

    if ($echo) echo $result;
    return $result;

  }
}
//------------------------------------------------------------------------------

//------------------------------------------------------------------------------
// Generate configuration option for the requirements of fields
// (Disabled/Optional/Required)
//------------------------------------------------------------------------------
if (!function_exists("blaat_option_yesno_generate")){
  function blaat_option_yesno_generate($option_name,$option_title, $echo=true){

    $option_value=(int) get_option($option_name);
    $result = '<tr><th>';
    $result .= $option_title;
    $result .= "</th><td>";
    $result .= "<select name='$option_name'>";

    $selected = ($option_value) ? "selected='selected'" : "";
    $result .= "<option value='Yes' $selected>";
    $result .= __("Disabled" , "blaatschaap");
    $result .=  "</option>";

    $selected = ($option_value) ?  "": "selected='selected'";
    $result .= "<option value='No' $selected>";
    $result .= __("Optional" , "blaatschaap");
    $result .=  "</option>";

    $result .= "</select>";
    $result .= "</td></tr>";

    if ($echo) echo $result;
    return $result;

  }
}
//------------------------------------------------------------------------------



?>
