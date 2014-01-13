<?php
/*
Mage Google Maps
*/
/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

/* Basic plugin definitions */
/*
 * @level 		Casting
 * @author		Mage Cast 
 * @url			http://magecast.com
 * @license   	http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
 */
?>
<?php
if (!defined('MAGECAST_MAPS')) exit;
add_action('init', 'summon_magecast_maps');
add_filter('mage_options_dashboard','mage_maps_dashboard_update');
function mage_maps_dashboard_update($options){
	$options[] = array(
		'name' => __('Mage Google Maps', 'magecast'),
		'desc' => __('<p><strong>Current Version:</strong> 1.0.1</p>
						<p><strong>Features in Future Updates:</strong></p>
						<ul>
						<li><span class="glyphicon glyphicon-time"></span> Custom Marker Icon Upload & Selection</li>
						<li><span class="glyphicon glyphicon-time"></span> Mage Google Map Widget with Unique Settings</li>
						<li><span class="glyphicon glyphicon-time"></span> Option to Display Custom Markers based on Taxonomy</li>
						<li><span class="glyphicon glyphicon-time"></span> Marker Windows & Display Options</li>
						<li><span class="glyphicon glyphicon-time"></span> and Much More</li>
						</ul>', 'magecast'),
		'type' => 'legend');
	return $options;
}
function summon_magecast_maps(){	
	if (current_user_can('switch_themes')) {	
		add_action('admin_init', 'mage_maps_init' );	
		add_action('admin_menu', 'summon_magecast_maps_dashboard');				
	}
}
function summon_magecast_maps_dashboard(){
	global $themename, $shortname, $submenu, $menu;
	$mage_options_page = add_submenu_page('mage_cast','Maps','Maps','manage_options','mage_maps','mage_maps_page');	
	add_action('admin_print_scripts-'.$mage_options_page, 'mage_load_scripts');			
	add_action('admin_print_styles-'.$mage_options_page, 'mage_load_styles' );	
}
function mage_maps_init() {			
	global $pagenow;	
	if('media-upload.php' == $pagenow || 'async-upload.php' == $pagenow )add_filter( 'gettext', 'replace_mage_upload_text',1,3);
	add_filter( 'mage_sanitize_text', 'sanitize_text_field' );
	add_filter( 'mage_sanitize_select', 'mage_sanitize_enum', 10, 2);
	add_filter( 'mage_sanitize_radio', 'mage_sanitize_enum', 10, 2);
	add_filter( 'mage_sanitize_legend', 'mage_sanitize_enum', 10, 2);
	add_filter( 'mage_sanitize_images', 'mage_sanitize_enum', 10, 2);
	add_filter( 'mage_sanitize_checkbox', 'mage_sanitize_checkbox' );
	add_filter( 'mage_sanitize_multicheck', 'mage_sanitize_multicheck', 10, 2 );
	add_filter( 'mage_sanitize_upload', 'mage_sanitize_upload' );
	$mage_settings = get_option('mage_maps');
	$id = 'magecast_maps';
	if (isset($mage_settings['id'])){
		if ($mage_settings['id'] !== $id) { 
			$mage_settings['id'] = $id;
			update_option('mage_maps',$mage_settings);		
		}
	} else { 
		$mage_settings['id'] = $id;
		update_option('mage_maps' ,$mage_settings);
	}
	if (get_option($mage_settings['id']) === false) mage_setdefaults('maps');
	register_setting('mage_maps' ,$mage_settings['id'],'mage_maps_validate' );
}
function mage_maps_page() {
	global $craft;
?>
<div id="mage-wrap">
<?php settings_errors(); ?>
<div id="container" class="row">  
    <form id="mage-form" method="post" class="form-horizontal" action="options.php">
		<?php settings_fields('mage_maps'); ?>
		<div id="magecast-content" class="magecast-content tab-content"><?php fields(mage_maps_options(),'maps'); ?></div>
	<!-- Footer Navbar and Submit -->         
		<div class="navbar navbar-static-bottom">
            	<input type="submit" class="btn btn-brown" name="update" id="update" value="<?php esc_attr_e( 'Save Options', 'mage_maps' ); ?>" />        	
 		</div>
    </form>
</div>
</div><?php
}
function mage_maps_validate($input) {
	global $craft;
	if (!current_user_can('manage_options'))die('Insufficient Permissions');
	$clean = array();
	$options = mage_maps_options();	
	foreach ($options as $option ){
		if (!isset($option['id']))continue;
		if (!isset($option['type']))continue;
		$id = cog($option['id']);
		if (!isset($input[$id])) {
			if (in_array($option['type'], array('text','textarea','select','radio','color','upload')))$input[$id] = isset($option['std'])? $option['std']:'';		
			if ('checkbox' == $option['type'])$input[$id] = false;				
			if ('multicheck' == $option['type'])$input[$id] = array();					
		}
		if (has_filter('mage_sanitize_' .$option['type'])){				
			$clean[$id] = apply_filters('mage_sanitize_' . $option['type'], $input[$id], $option);	
		} 
	}
	add_settings_error('mage_maps','save_options', __( 'Options saved.', 'magecast' ), 'updated modal fade in' );
	return $clean;
} 
function mage_map_post_type($check){
	$post_type = get_post_type($check);
	if (!$post_type) return false;
	//$post_type = $check->post_type;
	$types = mage_get_option('maps','post_type_maps');
	if (is_array($types) && in_array($post_type,$types)) {
  		return true;
	}
	return false;
}
function mage_maps_options(){
	$options = array();			
	$types = mage_get_option('maps','post_type_maps');	
	$options[] = array('name' => __('Maps','magecast'),'icon' => 'map-marker','type' => 'heading');		
	$options[] = array('name' => __('Map Design', 'paladin'),'parent' => 'maps','type' => 'subheading');
	$options[] = array(
		'name' => __('Map Width', 'paladin'),
		'desc' => __('Default Map width.', 'paladin'),
		'id' => 'mage_maps_width',
		'std' => '100%',
		'type' => 'text');	
	$options[] = array(
		'name' => __('Map Height', 'paladin'),
		'desc' => __('Default Map Height', 'paladin'),
		'id' => 'mage_maps_height',
		'std' => '300px',
		'type' => 'text');
	$options[] = array(
		'name' => __('Default Address', 'paladin'),
		'desc' => __('The region to display if no location is identified.', 'paladin'),
		'id' => 'mage_maps_region',
		'std' => '',
		'type' => 'text');	
	$options[] = array(
		'name' => __('Default Zoom', 'paladin'),
		'desc' => __('The region to display if no location is identified.', 'paladin'),
		'id' => 'mage_maps_zoom',
		'std' => '14',
		'type' => 'select',
		'options'=>mage_number_select(1,20));	
	$options[] = array(
		'name' => __('Toggle User Interface', 'paladin'),
		'desc' => __('Activate to hide the default Google Maps UI.', 'paladin'),
		'id' => 'mage_maps_ui',
		'type' => 'checkbox',
		'std' => '1');	
		/*
	$options[] = array(
		'name' => __('Default Map Marker', 'paladin'),
		'desc' => __('The region to display if no location is identified.', 'paladin'),
		'id' => 'mage_maps_marker',
		'std' => MAGECAST_MAPS_URL.'img/marker.png',
		'type' => 'upload');
		*/
	// Auto Display
	$options[] = array('name' => __('Auto Display', 'paladin'),'parent' => 'maps','type' => 'subheading');
	$keys = mage_select_meta_keys($types, array(0=>'None'/*,'b'=>'Post Title','c'=>'Post Excerpt'*/));
	$options[] = array(
		'name' => __('Attach to Post Types', 'magecast'),
		'desc' => __('Choose the maximum rating that a user can rate content with.', 'magecast'),
		'id' => 'post_type_maps',		
		'type' => 'multicheck',
		'options'=>mage_post_type_options());
	$options[] = array(
		'name' => __('Address 1', 'magecast'),
		'id' => 'mage_map_key_address_1',
		'std' => '',
		'type' => 'select',
		'options'=>$keys);
	$options[] = array(
		'name' => __('Address 2', 'magecast'),
		'id' => 'mage_map_key_address_2',
		'std' => '',
		'type' => 'select',
		'options'=>$keys);
	$options[] = array(
		'name' => __('City', 'magecast'),
		'id' => 'mage_map_key_city',
		'std' => '',
		'type' => 'select',
		'options'=>$keys);
	$options[] = array(
		'name' => __('State', 'magecast'),
		'id' => 'mage_map_key_state',
		'std' => '',
		'type' => 'select',
		'options'=>$keys);
	$options[] = array(
		'name' => __('Zip Code', 'magecast'),
		'id' => 'mage_map_key_zip',
		'std' => '',
		'type' => 'select',
		'options'=>$keys);
	$options[] = array(
		'name' => __('Country', 'magecast'),
		'id' => 'mage_map_key_country',
		'std' => '',
		'type' => 'select',
		'options'=>$keys);
	/*
	$options[] = array(
		'name' => __('Map Marker', 'magecast'),
		'id' => 'mage_map_key_marker',
		'std' => '',
		'type' => 'select',
		'options'=>$keys);
		/*
	$options[] = array(
		'name' => __('Taxonomy Markers', 'paladin'),
		'id' => 'mage_marker_taxonomy',
		'type' => 'select',
		'options'=>mage_taxonomy_options());
	if (mage_get_option('maps','mage_marker_taxonomy')){
		$terms = get_terms(mage_get_option('maps','mage_marker_taxonomy'),array('parent'=>0,'hierarchical'=>false,'hide_empty'=>1));
		foreach ($terms as $term){			
			$options[] = array(
				'name' => $term->name.' Map Marker',
				'id' => 'mage_marker_'.$term->slug,
				'type' => 'upload');
		}
	}
	*/
	$options[] = array('name' => __('Help', 'magecast'),'parent' => 'maps','type' => 'subheading');	
	$options[] = array(
		'name' => __('Static Map Display', 'magecast'),
		'desc' => __('<p>The fastest way to display a map is with the <code>[map]</code> shortcode, with the <code>address=""</code> parameter, for example <code>[map address="Los Angeles, CA"]</code>. If you dont add an address at all, the default address listed in <code>Map Design</code>-><code>DEFAULT ADDRESS</code>.</p>', 'magecast'),
		'type' => 'legend');
	$options[] = array(
		'name' => __('Auto Map Display & Multi Address', 'magecast'),
		'desc' => __('<p><strong>1.</strong> - To activate Automatic Address Quesies, you first have to choose which post types you want to display maps on under <code>Auto Display</code>-><code>Attach To Post Types</code>.</p>
		<p><strong>2.</strong> - Select the custom fields for each address component, or you can leave all except <strong>Address 1</strong> disabled, if you wish to use only one field to provide the complete address. If you are not given enough options, you can simply create more custom fields in the desired post type, and they will appear in the "Auto Display" options thereafter. <a href="http://codex.wordpress.org/Custom_Fields" target="_blank">More Info on Custom Fields</a>.</p>
		<p><strong>3.</strong> - After you complete the previous two steps, you can simply place the <code>[map]</code> Shortcode anywhere you wish to present the map. Typically, <code>[map]</code> will attempt to show <strong>All</strong> address markers when in an <strong>Archive</strong> and only one marker when on a single page post type. This works only if the custom fields are present with the correct Address data of course. You can overwrite the default shortcode behaviour via its parameters (See below).</p>', 'magecast'),
		'type' => 'legend');
		
	$options[] = array(
		'name' => __('Shortcode [map] Parameters', 'magecast'),
		'desc' => __('<table class="table">
          <thead>
            <tr>
              <th>Parameter</th>
              <th>Type</th>
              <th>Examples / Default</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><code>width</code></td>
              <td><div class="label label-success">string</div></td>
              <td>"100%"</td>
            </tr>
            <tr>
              <td><code>height</code></td>
              <td><div class="label label-success">string</div></td>
              <td>"300px"</td>
            </tr>
            <tr>
              <td><code>zoom</code></td>
              <td><div class="label label-primary">int</div></td>
              <td>14</td>
            </tr>
			<tr>
              <td><code>address</code></td>
              <td><div class="label label-success">string</div></td>
              <td>"Los Angeles, CA"</td>
            </tr>
			<tr>
              <td><code>ui</code></td>
              <td><div class="label label-warning">bool</div></td>
              <td>0 (1 to activate)</td>
            </tr>
			<tr>
              <td><code>title</code></td>
              <td><div class="label label-success">string</div></td>
              <td>"Post Title"</td>
            </tr>
			<tr>
              <td><code>show</code></td>
              <td><div class="label label-success">string</div></td>
              <td>"all" or "current" (defaults to auto)</td>
            </tr>
			<tr>
              <td><code>class</code></td>
              <td><div class="label label-success">string</div></td>
              <td>"css-class names"</td>
            </tr>
			<tr>
              <td><code>style</code></td>
              <td><div class="label label-success">string</div></td>
              <td>"css:rules here;"</td>
            </tr>
          </tbody>
        </table><p><strong>Usage:</strong><br /><code>[map address="1600 Amphitheatre Pkwy, Mountain View, CA 94043" width="250px" ui=0 zoom=10]</code></p>', 'magecast'),
		'type' => 'legend');	
	$wp_support = 'http://wordpress.org/support/plugin/mage-reviews';
	$mb_article = 'http://blog.maximusbusiness.com/2013/11/wordpress-review-rating-plugin/';
	//$options[] = array(
		//'name' => __('Support', 'magecast'),
		//'desc' => __('<p>Please refer to <a href="'.$mb_article.'" target="_blank">this article</a> and to <a href="'.$wp_support.'" target="_blank">WordPress Support Forums</a> if you need help, find any bugs, have suggestions or would like help us with a rating :) .</p>', 'magecast'),
		//'type' => 'legend');
	return $options;	
}
function mage_number_select($start=0,$end=10){
$opt = array();
for($i = $start; $i<=$end; $i++) $opt[$i]=$i;
return $opt;
}

