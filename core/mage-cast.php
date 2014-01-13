<?php
/*
Mage Cast
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
define('MAGECAST', plugins_url('/',__FILE__));
add_action('init', 'summon_core');
function summon_core(){	
	if (current_user_can('edit_theme_options')) {
		add_action('admin_menu', 'summon_mage_dashboard');			
	} 		
}
function summon_mage_dashboard(){
	global $themename, $shortname, $submenu, $menu, $mage;
	$mage_options_page = add_menu_page('Mage Cast','Mage Cast','manage_options','mage_cast', 'mage_page',MAGECAST.'images/icon.png','26.9');	
	add_submenu_page('mage_cast','Dashboard','Dashboard','manage_options','mage_cast','mage_page');	
	add_action('admin_print_scripts-'.$mage_options_page, 'mage_load_scripts');			
	add_action('admin_print_styles-'.$mage_options_page, 'mage_load_styles' );	
	$submenu['mage_cast'][0][0] = 'Dashboard';
}
function mage_page() {
	global $craft;
?>
<div id="mage-wrap">
<?php settings_errors(); ?>
<div id="container" class="row">  
    <form id="mage-form" method="post" class="form-horizontal" action="options.php">
		<?php settings_fields('magecast'); ?>
		<div id="magecast-content" class="magecast-content tab-content"><?php fields($craft->options(),'cast'); ?></div>        
    </form>
</div>
</div><?php
}
function mage_verify_image($id='', $src=''){
	$check = false;
	if (empty($id) || empty($src)) return $check;
	$comp = wp_get_attachment_image_src($id,'full');
	if (!$comp) return $check;
	if ($comp[0] == $src)return true;
	return $check;
}
function mage_post_type_options($unset=array(),$args=array(),$set=array('post','page')){
	$options=array();
	$types = mage_get_post_types($args,'names','and',$unset);
	$types = $types + $set;
	foreach ($types as $type) {
		$name = get_post_type_object($type)->labels->singular_name;
		$slug = get_post_type_object($type)->name;
		$options[$slug] = $name;		
	}
	return $options;
}
function replace_mage_upload_text($translated_text, $text, $domain) { 
    if ('Insert into Post' == $text) { 
        $referer = wp_get_referer(); 
        if ( $referer != '' ) {return __('Use This Image', 'magecast'); } 
    }  
    return $translated_text;  
}
function mage_load_styles() {
	wp_enqueue_style('thickbox'); 
	wp_enqueue_style('bootstrap_style_full',MAGECAST.'css/bootstrap.full.min.css');
	wp_enqueue_style('icons',MAGECAST.'css/glyphicons.min.css');		
	wp_enqueue_style('mage-fonts','http://fonts.googleapis.com/css?family=Lato:400,700|Philosopher:400,700');
	wp_enqueue_style('mage-options',MAGECAST.'css/magecast.css');
}
function mage_load_scripts() {		
	wp_enqueue_script('jquery');
	wp_enqueue_script('thickbox');       
    wp_enqueue_script('media-upload'); 	
	wp_enqueue_script('mage_admin_js',MAGECAST.'js/magecast.js');
	wp_enqueue_script('mage-components',MAGECAST.'js/bootstrap.min.js');
	wp_print_scripts( array( 'sack' ));
		?>
			<script>
				function mage_img_delete(id){					
					var mysack = new sack("<?php echo admin_url('admin-ajax.php'); ?>" );
				  	mysack.execute = 1;
				  	mysack.method = 'POST';
				  	mysack.setVar( "action", "mage_img_delete" );
				  	mysack.setVar( "id",id);
				  	mysack.encVar( "cookie", document.cookie, false );
				  	mysack.onError = function() { alert('Error Deleting Image.' )};
				  	mysack.runAJAX();
					return true;
				}		
				
			</script>
		<?php

}
function mage_sanitize_multicheck( $input, $option ) {
	$output = array();
	if ( is_array( $input ) && !empty($input) ) {
		return $input;
		foreach( $input as $key) {
			if ( array_key_exists( $key, $option['options'] )) {
				$output[] = $key;
			}
		}
	}
	return $output;
}
function mage_sanitize_checkbox( $input ) {
	return $input? '1' : false;
}
function mage_sanitize_upload( $input ) {	
	if (is_array($input)){
		$output = array('src'=>'', 'width'=>'','height'=>'','id'=>'');
		$filetype = wp_check_filetype($input['src']);
		if ( $filetype["ext"] ) $output = array('src'=>$input['src'], 'width'=>$input['width'],'height'=>$input['height'],'id'=>$input['id']);
	} else {
		$output = '';
		$filetype = wp_check_filetype($input);
		if ( $filetype["ext"] ) $output = $input;
	}
	return $output;
}
/* Check that the key value sent is valid */
function mage_sanitize_enum( $input, $option ) {
	$output = '';
	foreach ($option['options'] as $keys => $vals){
		if (is_array($vals)){
			if ( array_key_exists( $input, $vals) ) {
				$output = $input;
			}
		}
	}
	if ( array_key_exists( $input, $option['options'] ) ) {
		$output = $input;
	}
	return $output;
}
function mage_setdefaults($from='') {
	global $craft;
	$from = empty($from)?'mage':'mage_'.$from;
	$mage_settings = get_option($from);
	$option_name = $mage_settings['id'];	
	if ( isset($mage_settings[$option_name.'_defaults']) ) {
		$defaults =  $mage_settings[$option_name.'_defaults'];
		if ( !in_array($option_name, $defaults) ) {
			array_push( $defaults, $option_name );
			$mage_settings[$option_name.'_defaults'] = $defaults;
			update_option($from, $mage_settings);
		}
	} else {
		$newoptionname = array($option_name);
		$mage_settings[$option_name.'_defaults'] = $newoptionname;
		update_option($from, $mage_settings);
	}	
}
function mage_get_option($from='',$name, $default = false ) {
	$from = empty($from)?'mage':'mage_'.$from;
	$config = get_option($from);
	if (!isset( $config['id'])) return $default;
	$options = get_option( $config['id'] );
	if (isset( $options[$name])) return $options[$name];
	return $default;
}
if (!function_exists('magex')){
function magex($in, $pre='',$aft='', $default='') {
	if (!isset($in))return $default;
	$in = trim($in);
	if (!empty($in))return $pre.$in.$aft;
	return $default;
}
}
function cog($string='') {
	if (!empty($string)){
		$string = trim($string);
		$string = preg_replace('/[^A-Za-z0-9_-\s]/', '', $string);
		$string = strtolower(preg_replace('/\s+/', '-', $string));
	}
	return $string;
}
function mage_get_post_types($args=array(),$output ='names',$operator='and',$unset=array('forum','topic','reply')) {
	$defaults =array('_builtin' => false);
	$args = empty($args) ? $defaults : $args;
	$builtin = apply_filters('mage_builtin_post_types',array());
	$unset = array_unique(array_merge($unset, $builtin));
    $post_types = get_post_types($args,$output,$operator);
    foreach ($post_types as $key => $val) {
        if (in_array($val,$unset)) {
            unset( $post_types[$key] );
        }
    }
    return $post_types;
}
function mage_taxonomy_options($unset=array(),$args=array(),$set=array()){
	$options=array();
	$types = mage_get_taxonomies($args,'names','and',$unset);
	$types = $types + $set;
	foreach ($types as $type) {
		$name = get_taxonomy($type)->labels->singular_name;
		$options[$type] = $name;		
	}
	return $options;
}
function fields($page='',$opt_group='') {
	global $allowedtags;
	$mage_settings = get_option('mage_'.$opt_group);
	if (isset($mage_settings['id']))$option_name = $mage_settings['id'];
	else $option_name = 'mage_'.$opt_group;
	$settings = get_option($option_name);
	$options = $page;
	$counter = 0;
	$subcounter = 0;
	$menu = $collapse = '';
	$submenus = array();	
	foreach($options as $value) {
		$counter++;
		$val = $select_value = $checked = $output = $active = $pre = $pre2 = $selected = $tigger = $slider = $prepend = $append = $attributes = '';	
		$div = false;
		$explain = isset( $value['desc'])? $value['desc']:'';
		if (!in_array($value['type'],array('heading','subheading','function','legend'))) {
			$id = cog($value['id']);
			$shortcode = isset($value['shortcode'])?' <code>'.$value['shortcode'].'</code>':'';
			$val = isset($value['std'])? $value['std']:'';
			$ph = isset($value['ph']) && !empty($value['ph'])? 'placeholder="'.$value['ph'].'"' : '';
			$class = ' form-group section-'. $value['type'].' ';
			$class .= isset($value['class']) && !empty($value['class'])? $value['class'] : '';	
			if(isset($settings[$value['id']])) {
				$val = $settings[$value['id']];				
				if (!is_array($val))$val = stripslashes($val);
			}			
			if (isset($value['pre']) && !empty($value['pre'])){
				$pre = '<div class="input-group"><span class="input-group-addon">'.$value['pre'].'</span>';
				$pre2 = '</div>';
			}
			$output .= '<div id="mage-' .$id.'" class="'.esc_attr( $class ).'">';
			if (!in_array($value['type'],array('textarea','checkbox','radio'))){
				if (!isset($value['inline'])){
					if (!isset($value['label-col'])) $value['label-col'] = 2;		
					if (isset($value['name']))$output .= '<label class="col-lg-'.$value['label-col'].' control-label" for="' .$value['id']. '">' .$value['name'].$shortcode.'</label>';
				} else {
					if (isset($value['name']))$output .= '<label for="' .$value['id']. '">' .$value['name'].$shortcode.'</label>';		}
			} 
		}			
		switch ($value['type']) {
		case 'text': 
			$text = (isset($value['pw']))? 'password' : 'text';		
			if (!isset($value['col'])) $value['col'] = 6;
			$output .= $prepend.'<div class="col-lg-'.$value['col'].'">';
			$output .= $pre.'<input id="' . esc_attr( $value['id'] ) . '" name="' . esc_attr($option_name.'['.$value['id'].']').'" type="'.$text.'" class="form-control" value="' . esc_attr( $val ) . '" '.$ph.' />'.$pre2.'</div>'.$append.$slider;					
		break;
		case 'textarea':
			$rows = isset($value['rows'])? $value['rows'] : '6';
			$val = stripslashes( $val );
			$output .= '<label class="col-lg-2 control-label" for="' .$value['id']. '">' .$value['name'].$shortcode.'</label>';
			$output .= '<div class="col-lg-6"><textarea id="' . esc_attr( $value['id'] ) . '" class="form-control mage-textarea" name="' . esc_attr( $option_name . '[' . $value['id'] . ']' ) . '" rows="' . $rows . '">' . esc_textarea( $val ) . '</textarea></div>';
		break;
		case 'select':			
			if (!isset($value['col'])) $value['col'] = 6;
			if ($value['col'] != 0) $output .= '<div class="col-lg-'.$value['col'].'">';
			$output .= '<select class="form-control mage-select" name="' . esc_attr( $option_name . '[' . $value['id'] . ']' ) . '" id="' . esc_attr( $value['id'] ) . '">';
			foreach ($value['options'] as $key => $option ) {				
				if (is_array($option)){
					$output .= '<optgroup label="' . esc_attr( $key ) . '">';
					foreach ($option as $opt => $op) {	
						if (!empty($val))$selected = ($val==$opt)? ' selected="selected"':''; 
						$output .= '<option'. $selected .' value="' . esc_attr($opt ) . '">' . esc_html( $op ) . '</option>';
					}
					$output .= '</optgroup>';
				} else {
					if (!empty($val))$selected = ($val==$key)? ' selected="selected"':''; 
					$output .= '<option'. $selected .' value="' . esc_attr( $key ) . '">' . esc_html( $option ) . '</option>';
				}
				
			}
			$output .= '</select>';
			if ($value['col'] != 0) $output .= '</div>';
		break;
		case 'radio':		
			$append = isset($value['name']) && isset($value['label-col'])? '</div>' : '';
			$output .= isset($value['name']) && isset($value['label-col'])? '<label class="col-lg-'.$value['label-col'].' control-label" for="' .$value['id']. '">' .$value['name'].$shortcode.'</label><div class="col-lg-10">':'<label for="' .$value['id']. '">' .$value['name'].$shortcode.'</label>';
			$btn = isset($value['btn'])? $value['btn'] : '';
			$output .= '<div class="btn-group" data-toggle="buttons">';
			foreach ($value['options'] as $key => $option) {	
				$checked=($val== $key)? 'checked="checked"' :'';		
				$active =( $val == $key ) ? ' active' :'';			
				$output .= '<button type="button" class="options btn'.$btn.$active.'" for="' . $value['id'] . '"><input class="mage-input mage-radio" type="radio" name="' . esc_attr($option_name .'['. $value['id'] .']') . '" id="' . esc_attr($option_name . '-' . cog($value['id']) .'-'. $key) . '" value="'. esc_attr( $key ) . '" '.$checked.' />' . $option  . '</button>';
			}
			$output .= '</div>'.$append;
		break;
		case "checkbox":
			$active = ($val)? 'active' : '';
			$output .= '<label class="col-lg-2 control-label" for="' . esc_attr( $value['id'] ) . '">' .$value['name'].$shortcode.'</label>
			<div class="col-lg-6"><label class="btn-activator '.$active.'" for="' . esc_attr( $value['id'] ) . '">
			<input id="' . esc_attr( $value['id'] ) . '" class="checkbox activator" type="checkbox" name="' . esc_attr( $option_name . '[' . $value['id'] . ']' ) . '" '. checked( $val, 1, false) .' /><span></span></label><span class="help-block">'.$explain.'</span></div>';
		break;
		case "multicheck":
			$output .= '<div class="col-lg-6"><div class="btn-group" data-toggle="buttons">';
			foreach ($value['options'] as $key => $option) {
				$id = $option_name . '-' . $value['id'] . '-'. $key;
				$name = $option_name . '[' . $value['id'] . '][]';
				$checked = is_array($val) && in_array($key,$val)? 'checked="checked"' : '';
				$active = is_array($val) && in_array($key,$val)? ' active' : '';
				$output .= '<label  class="checkbox inline btn btn-primary'.$active.'" for="' . esc_attr($name) . '"><input id="' . esc_attr( $id ) . '" class="checkbox" type="checkbox" name="' . esc_attr( $name ) . '" ' . $checked . ' value="'.$key.'" /> ' . esc_html( $option ) . '</label>';
			
			}
			$output .= '</div></div>';
		break;		
		case "color":
			$default_color = '';
			if(isset($value['std']))$default_color=($val != $value['std'])?' data-default-color="' .$value['std'] . '" ':'';
			$input = '<input name="' . esc_attr( $option_name . '[' . $value['id'] . ']' ) . '" id="' . esc_attr( $value['id'] ) . '" class="form-control mage-color"  type="text" value="' . esc_attr( $val ) . '"' . $default_color .' />';
			$output .= isset($value['span'])?'<div class="col-lg-10">'.$input.'</div>' : $input; 	
		break;
		case "upload":
			$var_data = is_array($val)? $val: array('src'=>$val);
			$var_data = wp_parse_args($var_data,array('src'=>'','width'=>'','height'=>'','id'=>''));
			$upload_id = $option_name.'['.esc_attr($value['id']).']';			
			$output .= '<div class="col-lg-6"><div class="input-group">
				<input id="' . esc_attr($value['id']) . '" class="form-control upload" type="text" name="'.$upload_id.'[src]" value="' . esc_attr($var_data['src']) . '" />
				<span class="input-group-btn">
					<button id="upload_'.esc_attr($value['id']).'" class="btn btn-success btn-background" type="button">' . __( 'Upload', 'magecast' ) . '</button>';
				if (mage_verify_image($var_data['id'],$var_data['src'])){
					$output .= '<a onclick="return mage_remove_image(\''.$var_data['id'].'\',\'' . esc_attr($value['id']) . '\');" name="'.$upload_id.'[delete]" id="delete_' . esc_attr($value['id']) . '" class="btn btn-danger trash" ><i class="halflings-icon white trash"></i></a>';
				}
      			$output .= '</span>			
			</div><input class="form-control upload_w hide" type="text" name="'.$upload_id.'[width]" value="' . esc_attr($var_data['width']) . '" />
				<input class="form-control upload_h hide" type="text" name="'.$upload_id.'[height]" value="' . esc_attr($var_data['height']) . '" />
				<input class="form-control upload_id hide" type="text" name="'.$upload_id.'[id]" value="' . esc_attr($var_data['id']) . '" />';	
			$output .= '</div>';	
			if(!empty($var_data['src']))$output .= '<div class="col-lg-1"><div class="mage-brand pull-right"><img rel="popover" data-title="Preview '. $value['name'] .'" src="'. esc_attr($var_data['src']) .'" style="max-height:40px;" alt="preview" /></div></div>';
			$output .= '<script>
			function mage_remove_image(id,field){
				mage_img_delete(id);
				jQuery("#"+field).attr("value", "");
				jQuery("#mage-"+field).find(".mage-brand").remove();
				return false;
			}
		</script>
       ';
		break;
		case "heading":
			$div = isset($value['div'])? $value['div'] : false;
			if ($counter != 1) {
				$output .= '</div></div>';
				$subcounter = 0;
			}
			if ($counter == 1)$output .= '<div class="mage-settings-page" id="step-' . cog($value['name']).'"><div class="content"><div class="scroller">';
			else $output .= '</div></div></div><div class="mage-settings-page" id="step-' . cog($value['name']) . '"><div class="content"><div class="scroller">';
			$output .= '<div class="page-header"><h2 class="heading">' . esc_html( $value['name'] ) . '</h2></div>' . "\n";
			$submenu = subtabs(cog($value['name']),$page,$opt_group);
			$output .=(!empty($submenu))?$submenu : '';
		break;
		case "subheading":			
			$subcounter++;				
			if ($subcounter == 1)$output .= '<div class="mage-tab-content tab-content"><div class="tab-pane fade in active " id="step-' . cog($value['name']). '">';
			else $output .= '</div><div class="tab-pane fade" id="step-' . cog($value['name']) . '">';			
		break;
		case "legend":		
				$output .= '<div class="panel panel-default"><div class="panel-heading">';
				
				$output .= '<legend class="panel-title"><a class="accordion-toggle" data-toggle="collapse" href="#cast-'.cog($value['name']).'">'.$value['name'].'</a></legend>';	
		break;
		}		
		if (!in_array($value['type'], array('heading','subheading','function'))) {
			if (!in_array($value['type'], array('checkbox','legend')) && !empty($explain)) {
				$output .= '<div class="poppos"><a class="pop halflings question-sign" data-placement="left" rel="popover" data-content="' . wp_kses( $explain, $allowedtags) . '" data-title="'. wp_kses(  $value['name'], $allowedtags ).'"><i></i></a></div>';
			}
			if ($value['type'] == 'legend') {
					$output .= '</div><div id="cast-'.cog($value['name']).'" class="panel-collapse collapse"><div class="panel-body">';
					if (isset($value['desc']))$output .= $value['desc'];
					$output .= '</div></div></div><div class="clear"></div>';
			} else {
				$output .= '</div><hr />';
			}		
		}
		echo $output;
	}
	echo '</div></div></div></div></div>';
}
function subtabs($subtabs='',$page='',$opt_group='') {	
	$mage_settings = get_option('mage_'.$opt_group);	
	$options = $page;
	$menu =''; $i=0;
	foreach ($options as $value) {		
	if ($value['type'] == "subheading") {
		if ($value['parent'] == $subtabs) {
			if ($i==0) { 
				$first = 'class="subpage active"';
			} else {
				$first = 'class="subpage"';
			}
			$jquery_click_hook = cog($value['name']);
			$jquery_click_hook = "step-" . $jquery_click_hook;			
			$menu .= '<li '.$first.'><a href="' . esc_attr( '#'.  $jquery_click_hook ) . '" data-toggle="tab">' .esc_html( $value['name'] ) . '</a></li>'; $i++;
		}
	}
	}
	$menu = (!empty($menu)) ? '<ul class="nav nav-tabs mage-cast-nav">'.$menu.'</ul>' : '';
	return $menu;
}
class MageCraft {  
    public function __construct(){  	
		add_action('wp_ajax_mage_img_delete',array($this,'mage_img_delete'));
	}  
	public function mage_img_delete() {
        $attach_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
		$test = wp_delete_attachment( $attach_id, true );
        if ($test !== false) { 
			die('jQuery("#update").trigger("click");'); 
		} else {
			die("alert('Error Deleting Image.');");
		}
	}
	public function options() {
		$options = array();				
		$options[] = array('name' => __('Dashboard','magecast'),'icon' => 'tasks','type' => 'heading');		
		$options[] = array('name' => __('Plugins','magecast'),'parent' => 'dashboard','type' => 'subheading');
		$options = apply_filters('mage_options_dashboard',$options);
		return $options;	
	}
}  
global $craft;
$craft = new MageCraft();

function mage_get_meta_keys($post_type = array()){
    global $wpdb;
	if (!empty($post_type)){
   		$query = "
        	SELECT DISTINCT($wpdb->postmeta.meta_key) 
       		FROM $wpdb->posts 
        	LEFT JOIN $wpdb->postmeta 
       		ON $wpdb->posts.ID = $wpdb->postmeta.post_id 
        	WHERE $wpdb->posts.post_type = '%s' 
        	AND $wpdb->postmeta.meta_key != '' 
        	AND $wpdb->postmeta.meta_key NOT RegExp '(^[_0-9].+$)' 
        	AND $wpdb->postmeta.meta_key NOT RegExp '(^[0-9]+$)'
    	";
    	$meta_keys = $wpdb->get_col($wpdb->prepare($query, $post_type));
	} else {
		$meta_keys = array();
	}
    set_transient('mage_meta_keys', $meta_keys, 60*60*24);
    return $meta_keys;
}
function mage_select_meta_keys($types=array(),$add = array()){
    $cache = get_transient('mage_meta_keys');
    $meta_keys = $cache ? $cache : mage_get_meta_keys($types);
	$new_keys = array();
	foreach ($meta_keys as $key => $name)$new_keys[$name]=$name;
	$meta_keys = $add + $new_keys;
    return $meta_keys;
}