<?php
/**
 * Plugin Name: Mage Google Maps
 * Plugin URI:  http://www.magecast.com
 * Description: WordPress Google Maps plugin with automatic single/multi address display via custom meta & shortcode.
 * Author:      Mage Cast
 * Author URI:  http://magecast.com
 * Version:     1.0.2
 * Text Domain: magecast
 * Domain Path: /lang/
 * License:     GPLv2 or later (license.txt)
 */
?>
<?php
if (!defined('ABSPATH')) exit;
define('MAGECAST_MAPS', dirname( __FILE__ ). '/');
define('MAGECAST_MAPS_URL',plugins_url('/',__FILE__));
define('MAGECAST_MAPS_SOURCE',MAGECAST_MAPS_URL.'source/');
add_action('after_setup_theme','load_magecast_maps');
function load_magecast_maps(){
	if (!defined('MAGECAST'))require_once MAGECAST_MAPS.'core/mage-cast.php';
	require_once MAGECAST_MAPS.'cast/mage-cast.php';
	require_once MAGECAST_MAPS.'cast/mage-maps.php';
	add_filter( 'plugin_action_links_'.plugin_basename( __FILE__ ), 'mage_google_maps_settings_link' );
}
function mage_google_maps_settings_link( $links ) {
   $links[] = '<a href="'. get_admin_url(null, 'admin.php?page=mage_maps') .'">Settings</a>';
   return $links;
}