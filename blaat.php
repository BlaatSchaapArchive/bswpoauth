<?php
//------------------------------------------------------------------------------
// BlaatSchaap TextDomain
//------------------------------------------------------------------------------
load_plugin_textdomain('blaatschaap', false, basename( dirname( __FILE__ ) ) . '/languages' );
//------------------------------------------------------------------------------
// BlaatSchaap Plugins Page
//------------------------------------------------------------------------------
if (!function_exists("blaat_plugins_page")) {
  function blaat_plugins_page(){
    echo '<div class="wrap">';
    echo '<h2>';
    _e("BlaatSchaap Plugins","blaatschaap");
    echo '</h2>';
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
    echo "<p><table class='blaat_plugins_table'>";
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
    echo "</table></p>";
    ?><table>
        <tr><td>Bitcoin</td><td>1NMv9ETkYrMeg53hN66egrFQ4tnaPLmM29</td></tr>
        <tr><td>Litecoin</td><td>LVPQtPn93GaAeczhUSengQzkQNpe3pZjnT</td></tr>
      </table><?php
    echo '</div>';
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

?>
