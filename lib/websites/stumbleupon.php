<?php
/*
Copyright 2012-2013 - Jean-Sebastien Morisset - http://surniaulula.com/

This script is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation; either version 3 of the License, or (at your option) any later
version.

This script is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE. See the GNU General Public License for more details at
http://www.gnu.org/licenses/.
*/

if ( ! defined( 'ABSPATH' ) ) 
	die( 'Sorry, you cannot call this webpage directly.' );

if ( ! class_exists( 'ngfbAdminStumbleUpon' ) && class_exists( 'ngfbAdmin' ) ) {

	class ngfbAdminStumbleUpon extends ngfbAdmin {

		protected $ngfb;

		public function __construct( &$ngfb_plugin ) {
			$this->ngfb =& $ngfb_plugin;
			$this->ngfb->debug->lognew();
		}

		public function get_rows() {
			$badge = '<style type="text/css">
					.badge { 
						display:block;
						background: url("http://b9.sustatic.com/7ca234_0mUVfxHFR0NAk1g") no-repeat transparent; 
						width:130px;
						margin:0 0 10px 0;
					}
					.badge-col-left { display:inline-block; float:left; }
					.badge-col-right { display:inline-block; }
					#badge-1 { height:60px; background-position:50% 0px; }
					#badge-2 { height:30px; background-position:50% -100px; }
					#badge-3 { height:20px; background-position:50% -200px; }
					#badge-4 { height:60px; background-position:50% -300px; }
					#badge-5 { height:30px; background-position:50% -400px; }
					#badge-6 { height:20px; background-position:50% -500px; }
				</style>
			';

			foreach ( range( 1, 6 ) as $i ) {
				switch ( $i ) {
					case '1' : 
						$badge .= '<div class="badge-col-left">' . "\n"; 
						break;
					case '4' : 
						$badge .= '</div><div class="badge-col-right">' . "\n"; 
						break;
				}
				$badge .= '<div class="badge" id="badge-' . $i . '">' . "\n";
				$badge .= '<input type="radio" 
					name="' . NGFB_OPTIONS_NAME . '[stumble_badge]" 
					value="' . $i . '" ' . 
					checked( $i, $this->ngfb->options['stumble_badge'], false ) . '/>' . "\n";
				$badge .= '</div>' . "\n";
				switch ( $i ) { 
					case '6' : 
						$badge .= '</div>' . "\n"; 
						break;
				}
			}

			return array(
				'<th colspan="2" class="social">StumbleUpon</th>',
				'<td colspan="2" style="height:5px;"></td>',
				'<th>Add to Excerpt Text</th><td>' . $this->ngfb->admin->form->get_checkbox( 'stumble_on_the_excerpt' ) . '</td>',
				'<th>Add to Content Text</th><td>' . $this->ngfb->admin->form->get_checkbox( 'stumble_on_the_content' ) . '</td>',
				'<th>Preferred Order</th><td>' . $this->ngfb->admin->form->get_select( 'stumble_order', range( 1, count( $this->ngfb->social_prefix ) ), 'short' ) . '</td>',
				'<th>JavaScript in</th><td>' . $this->ngfb->admin->form->get_select( 'stumble_js_loc', $this->js_locations ) . '</td>',
				'<th rowspan="5">Button Style</th><td rowspan="5">' . $badge . '</td>',
			);
		}

	}
}

if ( ! class_exists( 'ngfbSocialStumbleUpon' ) && class_exists( 'ngfbSocial' ) ) {

	class ngfbSocialStumbleUpon extends ngfbSocial {

		protected $ngfb;

		public function __construct( &$ngfb_plugin ) {
			$this->ngfb =& $ngfb_plugin;
			$this->ngfb->debug->lognew();
		}

		public function get_html( $atts = array() ) {
			global $post; 
			$html = '';
			$use_post = empty( $atts['is_widget'] ) || is_singular() ? true : false;
			if ( empty( $atts['url'] ) ) $atts['url'] = $this->ngfb->util->get_sharing_url( 'notrack', null, $use_post );
			if ( empty( $atts['stumble_badge'] ) ) $atts['stumble_badge'] = $this->ngfb->options['stumble_badge'];
			$html = '
				<!-- StumbleUpon Button -->
				<div ' . $this->get_css( 'stumbleupon', $atts, 'stumble-button' ) . '><su:badge 
					layout="' . $atts['stumble_badge'] . '" location="' . $atts['url'] . '"></su:badge></div>
			';
			$this->ngfb->debug->log( 'returning html (' . strlen( $html ) . ' chars)' );
			return $html;
		}

		public function get_js( $pos = 'id' ) {
			return '<script type="text/javascript" id="stumbleupon-script-' . $pos . '">
				ngfb_header_js( "stumbleupon-script-' . $pos . '", "' . $this->ngfb->util->get_cache_url( 'https://platform.stumbleupon.com/1/widgets.js' ) . '" );
			</script>' . "\n";
		}

	}

}
?>