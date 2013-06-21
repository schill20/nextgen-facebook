<?php
/*
License: GPLv3
License URI: http://surniaulula.com/wp-content/plugins/nextgen-facebook/license/gpl.txt
Copyright 2012-2013 - Jean-Sebastien Morisset - http://surniaulula.com/
*/

if ( ! defined( 'ABSPATH' ) ) 
	die( 'Sorry, you cannot call this webpage directly.' );

if ( ! class_exists( 'ngfbStyle' ) ) {

	class ngfbStyle {

		public $buttons_css_file;
		public $buttons_css_url;

		private $ngfb;

		public function __construct( &$ngfb_plugin ) {
			$this->ngfb =& $ngfb_plugin;
			$this->ngfb->debug->mark();
			$this->register_styles();

			$updir = wp_upload_dir();
			$this->buttons_css_file = trailingslashit( $updir['basedir'] ) . $this->ngfb->acronym . '-social-buttons.css';
			$this->buttons_css_url = trailingslashit( $updir['baseurl'] ) . $this->ngfb->acronym . '-social-buttons.css';

			add_action( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue_styles' ) );
			add_action( 'wp_enqueue_scripts', array( &$this, 'wp_enqueue_styles' ) );
		}

		public function register_styles() {
			wp_register_style( $this->ngfb->acronym . '_admin_settings_page', NGFB_URLPATH . 'css/admin-settings-page.css', false, $this->ngfb->version );
			wp_register_style( $this->ngfb->acronym . '_table_settings', NGFB_URLPATH . 'css/table-settings.css', false, $this->ngfb->version );
			wp_register_style( $this->ngfb->acronym . '_social_buttons', NGFB_URLPATH . 'css/social-buttons.css', false, $this->ngfb->version );
		}

		public function admin_enqueue_styles( $hook ) {
			switch ( $hook ) {
				case 'post.php' :
				case 'page.php' :
					wp_enqueue_style( $this->ngfb->acronym . '_table_settings' );
					break;
				case ( preg_match( '/_page_' . $this->ngfb->acronym . '-/', $hook ) ? true : false ) :
					wp_enqueue_style( $this->ngfb->acronym . '_admin_settings_page' );
					wp_enqueue_style( $this->ngfb->acronym . '_table_settings' );
					break;
			}
		}

		public function wp_enqueue_styles( $hook ) {
			if ( ! empty( $this->ngfb->options['buttons_link_css'] ) )
				wp_enqueue_style( $this->ngfb->acronym . '_social_buttons' );
		}

	}
}

?>
