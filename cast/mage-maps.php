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
?>
<?php
if (!defined('MAGECAST_MAPS')) exit;
add_action('init', 'summon_mage_maps');
add_shortcode('map', 'mage_map');
add_action( 'widgets_init', 'mage_maps_widget' );
function mage_maps_widget() {
	register_widget('Mage_Google_Maps');
}
class Mage_Google_Maps extends WP_Widget {
	function Mage_Google_Maps() {
		$widget_data = array('classname' => 'widget_mage_maps mage_google_maps', 'description' => __( 'Display a Google Map with custom settings.' ) );
		$this->WP_Widget('mage-google-maps', __('Mage Google Maps'), $widget_data);
		$this->alt_option_name = 'widget_mage_maps';
		add_action( 'wp_insert_post', array(&$this, 'flush_widget_cache'));
		add_action( 'transition_comment_status', array(&$this, 'flush_widget_cache') );
	}	

	function flush_widget_cache() {
		wp_cache_delete('widget_mage_maps', 'widget');
	}

	function widget( $args, $instance ) {
		global $query_string, $post;
		$cache = wp_cache_get('widget_mage_maps', 'widget');
		if (!is_array($cache))$cache = array();
		if (!isset($args['widget_id']))$args['widget_id'] = $this->id;
		if (isset($cache[$args['widget_id']])) {
			echo $cache[$args['widget_id']];
			return;
		}
		extract($args, EXTR_SKIP);
 		$output = '';
		$title = isset( $instance['title']) ? $instance['title'] : '';
		$title = apply_filters('widget_title', $title, $instance, $this->id_base );
		$width = isset($instance['width'])? esc_attr($instance['width']) : mage_get_option('maps','mage_maps_width','100%');
		$height = isset($instance['height'])? esc_attr($instance['height']) : mage_get_option('maps','mage_maps_height','300px');
		$zoom = isset($instance['zoom']) && !empty($instance['zoom'])? absint( $instance['zoom'] ) : mage_get_option('maps','mage_maps_zoom',14);
		$address = isset($instance['address'])? esc_attr($instance['address']) : '';	
		$show = isset($instance['show'])? esc_attr($instance['show']) : '0';	
		$ui = mage_get_option('maps','mage_maps_ui');
		
		$output .= $before_widget;
		if ($title)$output .= $before_title . $title . $after_title;
		if ($show == '0') $show = is_archive()? 'all' : 'current'; 
		$style= 'style="'.magex($width,'width:',';').magex($height,'height:',';').'" ';
		//$mark = isset($mark[0])? 'icon: "'.$mark[0].'"':'';
		$mark = '';
		$ids = array(); 
		$maps = ''; 
		if ($show=='current'){
			if (!is_object($post)) return '';
			$ids[] = $map_id = $post->ID;
		} elseif ($show=='all') {	
			$map_query = new WP_Query( $query_string . '&fields=ids' );
			if(isset($map_query->posts) && !empty($map_query->posts))foreach($map_query->posts as $id) $ids[] = $id;
			$map_id = 'all';
		}
		$fit = ($show=='all')? 'map.fitZoom();' :'';
		if (!empty($ids)){
			foreach($ids as $id) {	
				$title = get_the_title($id);
				if ($show=='all') {
					$post = get_post($id);
					setup_postdata( $post ); 
					$add = empty($address)? mage_map_address_output($id):$address;
					if (mage_get_option('maps','mage_marker_taxonomy')){	
						$terms = get_the_terms($id,mage_get_option('maps','mage_marker_taxonomy'));
						if ($terms && !is_wp_error($terms)){
							$parent = '';
							foreach ($terms as $term) if($term->parent == 0) $parent = $term->slug;
							$mark = mage_get_option('maps','mage_marker_'.$parent)? 'icon: "'.mage_get_option('maps','mage_marker_'.$parent).'"':$mark;	
						}
					}
				} else {
					$add = empty($address)? mage_map_address_output($id):$address;
					$add = !empty($add)? $add : mage_get_option('maps','mage_maps_region');
				}
				$add = !empty($add)? $add : mage_get_option('maps','mage_maps_region');
				$maps .= 'GMaps.geocode({
  					address: \''.$add.'\',
  					callback: function(results, status) {
   						if (status == \'OK\') {
      						var latlng = results[0].geometry.location;
     						map.setCenter(latlng.lat(), latlng.lng());
      						map.addMarker({
        						lat: latlng.lat(),
        						lng: latlng.lng(),
							title: \''.$title.'\',
							'.$mark.'
      					});	  
	  				'.$fit.'
    				}
  				}
			});';
			}
		} else {
			$maps .= 'GMaps.geocode({
  				address: \''.$address.'\',
 				callback: function(results, status) {
    			if (status == \'OK\') {
      				var latlng = results[0].geometry.location;
      				map.setCenter(latlng.lat(), latlng.lng());
      				map.addMarker({
        				lat: latlng.lat(),
        				lng: latlng.lng(),
						'.$mark.'
      					});
    				}
  				}
			});';
		}
	$ui = $ui? 'disableDefaultUI: true,' : '';
	$output = '<div class="mage-map '.$class.' '.$show.'" '.$style.'><div id="map-'.$map_id.'" class="map map-'.$post->ID.'" style="width:100%; height:100%;"></div></div>
	<script type="text/javascript">    
		var bounds = [];
    	jQuery(document).ready(function(){
      	map = new GMaps({
       		div: \'#map-'.$map_id.'\',
			lat: -12.043333,
        	lng: -77.028333,
			'.$ui.'
			zoom: '.$zoom.',		
      	}); 
	  	'.$maps.'	  
		});	  	  
	  </script>';
		$output .= $after_widget;
		echo $output;
		$cache[$args['widget_id']] = $output;
		wp_cache_set('widget_mage_maps', $cache, 'widget');				
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['width'] = strip_tags($new_instance['width']);
		$instance['height'] = strip_tags($new_instance['height']);
		$instance['address'] = strip_tags($new_instance['address']);
		$instance['zoom'] = (int) $new_instance['zoom'];
		$instance['show'] = strip_tags($new_instance['show']);		
		$this->flush_widget_cache();
		$alloptions = wp_cache_get( 'alloptions', 'options' );
		if ( isset($alloptions['widget_mage_maps']) )delete_option('widget_recent_reviews');
		return $instance;
	}

	function form( $instance ) {
		$title = isset($instance['title']) ? esc_attr($instance['title']) : '';
		$width = isset($instance['width'])? esc_attr($instance['width']) : mage_get_option('maps','mage_maps_width','100%');
		$height = isset($instance['height'])? esc_attr($instance['height']) : mage_get_option('maps','mage_maps_height','300px');
		$zoom = isset($instance['zoom']) && !empty($instance['zoom'])? absint( $instance['zoom'] ) : mage_get_option('maps','mage_maps_zoom',14);
		$address = isset($instance['address'])? esc_attr($instance['address']) : '';	
		$show = isset($instance['show'])? esc_attr($instance['show']) : '0';	
		$show_opts = array('0'=>'Automatic','all'=>'All','current'=>'Current');
?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>
		<p><label for="<?php echo $this->get_field_id( 'show' ); ?>"><?php _e( 'Auto-Display From:'); ?></label> 
        <select id="<?php echo $this->get_field_id( 'show' ); ?>" name="<?php echo $this->get_field_name( 'show' ); ?>" class="widefat" style="width:100%;">
        <?php foreach ($show_opts  as $show_opt => $sopt){ ?>
        <option value="<?php echo $show_opt; ?>" <?php if ($show_opt == $show) echo 'selected="selected"'; ?>><?php echo $sopt; ?></option>
        <?php } ?> 
		</select></p>
        <p><label for="<?php echo $this->get_field_id('address'); ?>"><?php _e('Address:'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('address'); ?>" name="<?php echo $this->get_field_name('address'); ?>" type="text" value="<?php echo $address; ?>" /><br /><small><?php _e('Leave Blank for Auto-Display'); ?></small></p>
		<p><label for="<?php echo $this->get_field_id('width'); ?>"><?php _e('Width:'); ?></label>
		<input id="<?php echo $this->get_field_id('width'); ?>" name="<?php echo $this->get_field_name('width'); ?>" type="text" value="<?php echo $width; ?>" size="5" /></p>
        <p><label for="<?php echo $this->get_field_id('height'); ?>"><?php _e('Height:'); ?></label>
		<input id="<?php echo $this->get_field_id('height'); ?>" name="<?php echo $this->get_field_name('height'); ?>" type="text" value="<?php echo $height; ?>" size="5" /></p>
        <p><label for="<?php echo $this->get_field_id('zoom'); ?>"><?php _e('Zoom:'); ?></label>
		<input id="<?php echo $this->get_field_id('zoom'); ?>" name="<?php echo $this->get_field_name('zoom'); ?>" type="text" value="<?php echo $zoom; ?>" size="3" /></p>
<?php
	}
}
function summon_mage_maps(){	
	global $post;
	//if(mage_map_post_type($post)){
		add_action( 'wp_enqueue_scripts', 'add_mage_maps_scripts',12);
		add_action('wp_enqueue_scripts','add_mage_maps_styles',20);
	//}
}
function add_mage_maps_scripts() {
	wp_enqueue_script('maps', 'http://maps.google.com/maps/api/js?sensor=true', $deps=null,'');
	wp_enqueue_script('gmaps', MAGECAST_MAPS_SOURCE.'js/gmaps.js', $deps=null,'0.2.26');
}

function add_mage_maps_styles() {
	wp_enqueue_style('mage-maps', MAGECAST_MAPS_SOURCE.'css/mage-maps.css');
}
function mage_map( $atts, $content = null ) {
	extract(shortcode_atts(array(
	'width' =>mage_get_option('maps','mage_maps_width','100%'),
	'height' =>mage_get_option('maps','mage_maps_height','300px'),
	'zoom'=>mage_get_option('maps','mage_maps_zoom',14),
	'address' => '',
	'ui'=>mage_get_option('maps','mage_maps_ui'),
	'mark'=>mage_get_option('maps','mage_maps_marker'),
	'title' => '',
	'show'=>'',
	'style' => '',
	'class' => ''), $atts));
	if (empty($show)) $show = is_archive()? 'all' : 'current'; 
	$style=magex($style,'style="',magex($width,'width:',';').magex($height,'height:',';').'" ','style="'.magex($width,'width:',';').magex($height,'height:',';').'" ');
	//$mark = isset($mark[0])? 'icon: "'.$mark[0].'"':'';
	$mark = '';
	$ids = array(); 
	$maps = ''; 
	global $query_string, $post;
	$event = magex($event,'"','",');
	if ($show=='current'){
		if (!is_object($post)) return '';
		$ids[] = $map_id = $post->ID;
	} elseif ($show=='all') {	
		$map_query = new WP_Query( $query_string . '&fields=ids' );
		if(isset($map_query->posts) && !empty($map_query->posts))foreach($map_query->posts as $id) $ids[] = $id;
		$map_id = 'all';
	}
	$fit = ($show=='all')? 'map.fitZoom();' :'';
	if (!empty($ids)){
		foreach($ids as $id) {	
			$title = get_the_title($id);
			if ($show=='all') {
				$post = get_post($id);
				setup_postdata( $post ); 
				$add = empty($address)? mage_map_address_output($id):$address;
				if (mage_get_option('maps','mage_marker_taxonomy')){	
					$terms = get_the_terms($id,mage_get_option('maps','mage_marker_taxonomy'));
					if ($terms && !is_wp_error($terms)){
						$parent = '';
						foreach ($terms as $term) if($term->parent == 0) $parent = $term->slug;
						$mark = mage_get_option('maps','mage_marker_'.$parent)? 'icon: "'.mage_get_option('maps','mage_marker_'.$parent).'"':$mark;	
					}
				}
			} else {
				$add = empty($address)? mage_map_address_output($id):$address;
				$add = !empty($add)? $add : mage_get_option('maps','mage_maps_region');
			}
			$add = !empty($add)? $add : mage_get_option('maps','mage_maps_region');
			$maps .= 'GMaps.geocode({
  				address: \''.$add.'\',
  				callback: function(results, status) {
   					if (status == \'OK\') {
      					var latlng = results[0].geometry.location;
     					map.setCenter(latlng.lat(), latlng.lng());
      					map.addMarker({
        					lat: latlng.lat(),
        					lng: latlng.lng(),
						title: \''.$title.'\',
						'.$mark.'
      				});	  
	  			'.$fit.'
    			}
  			}
		});';
		}
	} else {
		$maps .= 'GMaps.geocode({
  			address: \''.$address.'\',
 			callback: function(results, status) {
    		if (status == \'OK\') {
      			var latlng = results[0].geometry.location;
      			map.setCenter(latlng.lat(), latlng.lng());
      			map.addMarker({
        			lat: latlng.lat(),
        			lng: latlng.lng(),
					'.$mark.'
      				});
    			}
  			}
		});';
	}
	$ui = $ui? 'disableDefaultUI: true,' : '';
	$output = '<div class="mage-map '.$class.' '.$show.'" '.$style.'><div id="map-'.$map_id.'" class="map map-'.$post->ID.'" style="width:100%; height:100%;"></div></div>
	<script type="text/javascript">    
		var bounds = [];
    	jQuery(document).ready(function(){
      	map = new GMaps({
       		div: \'#map-'.$map_id.'\',
			lat: -12.043333,
        	lng: -77.028333,
			'.$ui.'
			zoom: '.$zoom.',		
      	}); 
	  	'.$maps.'	  
		});	  	  
	  </script>';
	return $output;
}
function mage_map_address_output($id = null){
	if (is_null($id)) return '';
	$a = mage_get_option('maps','mage_map_key_address_1')? mage_get_option('maps','mage_map_key_address_1') : false;
	$b = mage_get_option('maps','mage_map_key_address_2')? mage_get_option('maps','mage_map_key_address_2') : false;
	$c = mage_get_option('maps','mage_map_key_city')? mage_get_option('maps','mage_map_key_city') : false;
	$d = mage_get_option('maps','mage_map_key_state')? mage_get_option('maps','mage_map_key_state') : false;
	$e = mage_get_option('maps','mage_map_key_zip')? mage_get_option('maps','mage_map_key_zip') : false;
	$f = mage_get_option('maps','mage_map_key_country')? mage_get_option('maps','mage_map_key_country') : false;
	$street = $a && get_post_meta($id,$a,true)? get_post_meta($id,$a,true): '';
	$unit = $b && get_post_meta($id,$b,true)? ' '.get_post_meta($id,$b,true): '';
	$city = $c && get_post_meta($id,$c,true)? get_post_meta($id,$c,true).', ': '';
	$state = $d && get_post_meta($id,$d,true)? get_post_meta($id,$d,true).' ': '';	
	$zip = $e && get_post_meta($id,$e,true)? get_post_meta($id,$e,true).', ': '';
	$country = $f && get_post_meta($id,$f,true)? get_post_meta($id,$f,true): '';	
	$streetaddress = !empty($street)? $street.$unit.', ':  '';
	return $streetaddress.$city.$state.$zip.$country;
}
function mage_custom_map_markers( $atts, $content = null ){
	$attr = shortcode_atts(mage_default_atts(array(),'form'), $atts);
	global $post;
	$output = '';
	$row = 0;
	$request = isset($_REQUEST['pid'])? $_REQUEST['pid']:'';
	//$fields = !empty($request)?(array) maybe_unserialize(get_post_meta($request,'features',true)):'';
	$cast = maybe_unserialize(get_post_meta($request,'features',true));
	if (!empty($cast)){
		foreach($cast as $field => $var){
			if (!empty($field) && mage_prefix($field)){			
				$field = str_replace('mage_','',$field);
				if (!empty($field)){
					$row++;
					$attr['type'] = 'text';
					$attr['name'] = 'custom_field_key_'.$row;
					$attr['value'] = $field;
					$attr['readonly'] = 1;				
					$key = vessel($content,$attr, $post);
					$attr['name'] = 'custom_field_value_'.$row;
					$attr['value'] = $var;
					$attr['readonly'] = 0;
					$val = vessel($content,$attr, $post);
					$output .= $key.':'.$val.'<br />';
				}
			}
		}
		$output .= '<div class="label">'.$request.'</div>';
		$output .= mage_dump($cast, false);
	}
	if (empty($output)) {
		$row = 1;
		$attr['type'] = 'text';
		$attr['value'] = '';
		$attr['readonly'] = 0;
		$attr['name'] = 'custom_field_key_'.$row;
		$key = vessel($content,$attr, $post);
		$attr['name'] = 'custom_field_value_'.$row;
		$val = vessel($content,$attr, $post);
		$output .= $key.':'.$val.'<br />';
	}
	$args = array('onClick'=>'return add_custom_field();','id'=>'add-custom-field','wrap'=>'button','wrap'=>'button', 'data-row'=>$row);
	$button = bind('<i class="icon-plus"></i>',$args, true);
	return '<div id="custom-fields-wrap">'.$output.'</div>'.$button.'
			<script type="text/javascript">
			function add_custom_field(){							
				var custom_fields = jQuery(\'#custom-fields-wrap\');	
				var add_field = jQuery(\'#add-custom-field\');
				var field_num = add_field.data("row")+1;				
				add_field.data("row",field_num);
				add_field.attr("data-row",field_num);
				custom_fields.append(\'<input type="text" id="custom_field_key_\'+field_num+\'" name="custom_field_key_\'+field_num+\'" value="">:<input type="text" id="custom_field_value_\'+field_num+\'" name="custom_field_value_\'+field_num+\'" value=""><br />\');
				return false;
			}</script>';
}
