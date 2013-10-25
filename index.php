<?php
/*
  Plugin Name: Event Espresso Template - Masonry Grid
  Plugin URI: http://www.eventespresso.com
  Description: Masonry is a JavaScript grid layout library. It works by placing elements in optimal position based on available vertical space, sort of like a mason fitting stones in a wall. You've probably seen it in use all over the Internet. [EVENT_CUSTOM_VIEW template_name="masonry-grid"]
  Version: 1.0
  Author: Event Espresso
  Author URI: http://www.eventespresso.com
  Copyright 2013 Event Espresso (email : support@eventespresso.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA02110-1301USA

*/

add_action('action_hook_espresso_custom_template_masonry-grid','espresso_masonry_grid', 10, 1 );

if (!function_exists('espresso_masonry_grid')) {
	function espresso_masonry_grid(){

		global $org_options, $this_event_id, $events, $ee_attributes; 
		
			wp_enqueue_script( 'jquery-masonry');
			wp_register_style( 'espresso_masonry_grid', WP_PLUGIN_URL. "/".plugin_basename(dirname(__FILE__)).'/style.css' );
			wp_enqueue_style( 'espresso_masonry_grid');
	
		if(isset($ee_attributes['default_image'])) { 
			$default_image = $ee_attributes['default_image']; 
		}
		
		echo '<div id="espresso_masonry" class="masonry js-masonry">';
	
		foreach ($events as $event){
	
				$this_event_id		= $event->id;
				$member_only		= !empty($event->member_only) ? $event->member_only : '';
				$event_meta			= unserialize($event->event_meta);
				$externalURL 		= $event->externalURL;
				$registration_url 	= !empty($externalURL) ? $externalURL : espresso_reg_url($event->id);
				$event_status 		= event_espresso_get_status($event->id);
				$link_text 			= __('Register Now!', 'event_espresso');
				$open_spots			= get_number_of_attendees_reg_limit($event->id, 'number_available_spaces');
	
				//use the wordpress date format.
				$date_format = get_option('date_format');
	
				$att_num = get_number_of_attendees_reg_limit($event->id, 'num_attendees');
				//Uncomment the below line to hide an event if it is maxed out
				//if ( $att_num >= $event->reg_limit  ) { continue; $live_button = 'Closed';  }
				if($open_spots < 1 && $event->allow_overflow == 'N') {
					$link_text = __('Sold Out', 'event_espresso');
				} else if ($open_spots < 1 && $event->allow_overflow == 'Y'){
					$registration_url = espresso_reg_url($event->overflow_event_id);
					$link_text = !empty($event->overflow_event_id) ? __('Join Wait List', 'event_espresso') : __('Sold Out', 'event_espresso');
				}
				
				if ( $event_status == 'NOT_ACTIVE' ) {
					$link_text = __('Closed', 'event_espresso');
				}
				
				if ( function_exists('espresso_members_installed') && espresso_members_installed() == true && !is_user_logged_in() && ($member_only == 'Y' || $member_options['member_only_all'] == 'Y') ){
					$link_text 		= __('Member Only', 'event_espresso'); 
				}
		
				if(!isset($default_image)) { $default_image = WP_PLUGIN_URL. "/".plugin_basename(dirname(__FILE__)) . '/default.jpg';}
				$image = isset($event_meta['event_thumbnail_url']) ? $event_meta['event_thumbnail_url'] : $default_image;
	
				//uncomment this and comment out the above line if you want to use the Organisation logo
				//if($image == '') { $image = $org_options['default_logo_url']; }
	
				echo '<div class="ee_masonry">';
				echo '<a id="a_register_link-' . $event->id . '" href="' . $registration_url . '" class="darken">';
				echo '<img src="' . $image . '" /><h2>'.stripslashes($event->event_name).'</h2></a>';
				echo !empty($event->event_desc) ? '<p class="event_desc">'.$event->event_desc.'</p>' : '';
				echo '<p class="event-cost">Cost: ';
				echo $event->event_cost === "0.00" ? __('FREE', 'event_espresso') : $org_options['currency_symbol'] . $event->event_cost;
				echo '</p>';
				echo '<p class="event-date">'.date($date_format, strtotime($event->start_date)).'</p>';
				echo '<p class="event-status"><a id="register_link-' . $event->id . '" href="' . $registration_url . '" class="button darken">' . $link_text. '</a></p>';
				echo '</div>';
			}
		
		?>
		<script>
		jQuery( document ).ready( function( $ ) {
			$( '#espresso_masonry' ).masonry( {
				columnWidth: 240,
				itemSelector: '.ee_masonry',
				isAnimated: true
			} );
		} );
		</script>
	<?php
	}
}


/**
 * hook into PUE updates
 */
//Update notifications
add_action('action_hook_espresso_template_masonry_grid_update_api', 'espresso_template_masonry_grid_load_pue_update');
function espresso_template_masonry_grid_load_pue_update() {
	global $org_options, $espresso_check_for_updates;
	if ( $espresso_check_for_updates == false )
		return;
		
	if (file_exists(EVENT_ESPRESSO_PLUGINFULLPATH . 'class/pue/pue-client.php')) { //include the file 
		require(EVENT_ESPRESSO_PLUGINFULLPATH . 'class/pue/pue-client.php' );
		$api_key = $org_options['site_license_key'];
		$host_server_url = 'http://eventespresso.com';
		$plugin_slug = array(
			'premium' => array('p'=> 'espresso-template-masonry-grid'),
			'prerelease' => array('b'=> 'espresso-template-masonry-grid-pr')
			);
		$options = array(
			'apikey' => $api_key,
			'lang_domain' => 'event_espresso',
			'checkPeriod' => '24',
			'option_key' => 'site_license_key',
			'options_page_slug' => 'event_espresso',
			'plugin_basename' => plugin_basename(__FILE__),
			'use_wp_update' => FALSE
		);
		$check_for_updates = new PluginUpdateEngineChecker($host_server_url, $plugin_slug, $options); //initiate the class and start the plugin update engine!
	}
}