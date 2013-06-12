<?php
/*
Copyright 2013 - Jean-Sebastien Morisset - http://surniaulula.com/

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

if ( ! class_exists( 'ngfbCache' ) ) {

	class ngfbCache {

		public $base_dir = '';
		public $base_url = '/cache/';
		public $verify_certs = false;
		public $file_expire = 0;
		public $object_expire = 60;
		public $connect_timeout = 5;
		public $ignore_time = 300;
		public $ignore_urls = array();		// offline some URLs for a period of time

		private $ngfb;		// ngfbPlugin

		public function __construct( &$ngfb_plugin ) {
			$this->ngfb =& $ngfb_plugin;
			$this->ngfb->debug->mark();
			$this->base_dir = trailingslashit( NGFB_CACHEDIR );
			$this->base_url = trailingslashit( NGFB_CACHEURL );
			$this->verify_certs = empty( $this->ngfb->options['ngfb_verify_certs'] ) ? 
				false : $this->ngfb->options['ngfb_verify_certs'];
			$this->ignore_urls = get_transient( $this->ngfb->acronym . '_' . md5( 'ignore_urls' ) );
			if ( $this->ignore_urls == false ) $this->ignore_urls = array();
		}

		public function get( $url, $want_this = 'url', $cache_name = 'file', $expire_secs = false ) {

			if ( ! function_exists('curl_init') || 
				( defined( 'NGFB_CURL_DISABLE' ) && NGFB_CURL_DISABLE ) ) 
					return $want_this == 'url' ? $url : '';

			// if we're not using https on the current page, then no need to make our requests using https
			$get_url = empty( $_SERVER['HTTPS'] ) ? preg_replace( '/^https:/', 'http:', $url ) : $url;
			$get_url = preg_replace( '/#.*$/', '', $get_url );
			$url_path = parse_url( $get_url, PHP_URL_PATH );

			$url_ext = pathinfo( $url_path, PATHINFO_EXTENSION );
			if ( ! empty( $url_ext ) ) $url_ext = '.' . $url_ext;

			$url_frag = parse_url( $url, PHP_URL_FRAGMENT );
			if ( ! empty( $url_frag ) ) $url_frag = '#' . $url_frag;

			$cache_salt = __CLASS__ . '(get:' . $get_url . ')';
			$cache_id = md5( $cache_salt );
			$cache_file = $this->base_dir . $cache_id . $url_ext;
			$cache_url = $this->base_url . $cache_id . $url_ext . $url_frag;
			$cache_data = '';

			if ( $want_this == 'raw' ) {
				$cache_data = $this->get_cache_data( $cache_salt, $cache_name, $url_ext, $expire_secs );
				if ( ! empty( $cache_data ) ) {
					$this->ngfb->debug->log( 'cache_data is present - returning ' . strlen( $cache_data ) . ' chars' );
					return $cache_data;
				}
			} elseif ( $want_this == 'url' ) {
				$file_expire = $expire_secs == false ? $this->file_expire : $expire_secs;
				if ( file_exists( $cache_file ) && filemtime( $cache_file ) > time() - $file_expire ) {
					$this->ngfb->debug->log( 'cache_file is current - returning cache url "' . $cache_url . '"' );
					return $cache_url;
				} else $this->ngfb->debug->log( 'cache_file is too old or doesn\'t exist - fetching a new copy' );
			}

			// broken URLs are ignored for $ignore_time seconds
			if ( ! empty( $this->ignore_urls ) && array_key_exists( $get_url, $this->ignore_urls ) ) {
				$time_remaining = $this->ignore_time - ( time() - $this->ignore_urls[$get_url] );
				if ( $time_remaining > 0 ) {
					$this->ngfb->debug->log( 'ignoring URL ' . $get_url . ' for another ' . $time_remaining . ' second(s). ' );
					return $want_this == 'url' ? $url : '';
				} else {
					unset( $this->ignore_urls[$get_url] );
					set_transient( $this->ngfb->acronym . '_' . md5( 'ignore_urls' ), $this->ignore_urls, $this->ignore_time );
				}
			}

			$ch = curl_init();
			curl_setopt( $ch, CURLOPT_URL, $get_url );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $this->connect_timeout );
			curl_setopt( $ch, CURLOPT_USERAGENT, NGFB_CURL_USERAGENT );
			
			if( ini_get('safe_mode') || ini_get('open_basedir') )
				$this->ngfb->debug->log( 'PHP safe_mode or open_basedir defined, cannot use CURLOPT_FOLLOWLOCATION' );
			else {
				curl_setopt( $ch, CURLOPT_MAXREDIRS, 3 );
				curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
			}

			if ( defined( 'NGFB_CURL_PROXY' ) && NGFB_CURL_PROXY ) 
				curl_setopt( $ch, CURLOPT_PROXY, NGFB_CURL_PROXY );

			if ( defined( 'NGFB_CURL_PROXYUSERPWD' ) && NGFB_CURL_PROXYUSERPWD ) 
				curl_setopt( $ch, CURLOPT_PROXYUSERPWD, NGFB_CURL_PROXYUSERPWD );

			if ( empty( $this->verify_certs) ) {
				curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
				curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
			} else {
				curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
				curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 1 );
				curl_setopt( $ch, CURLOPT_CAINFO, NGFB_CURL_CAINFO );
			}

			$this->ngfb->debug->log( 'curl: fetching cache_data from ' . $get_url );
			$cache_data = curl_exec( $ch );
			$http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
			curl_close( $ch );

			$this->ngfb->debug->log( 'curl: http return code = ' . $http_code );
			if ( $http_code == 200 ) {
				if ( empty( $cache_data ) )
					$this->ngfb->debug->log( 'cache_data returned from "' . $get_url . '" is empty' );
				elseif ( $this->save_cache_data( $cache_salt, $cache_data, $cache_name, $url_ext, $expire_secs ) == true ) {
					$this->ngfb->debug->log( 'cache_data sucessfully saved' );

					// return url or data immediately on success
					return $want_this == 'url' ? $cache_url : $cache_data;
				}
			} else {
				if ( is_admin() )
					$this->ngfb->notices->err( 'Error connecting to URL ' . $get_url . ' for caching. 
						Ignoring requests to cache this URL for ' . $this->ignore_time . ' second(s)' );

				$this->ngfb->debug->log( 'error connecting to URL ' . $get_url . ' for caching. ' );
				$this->ngfb->debug->log( 'ignoring requests to cache this URL for ' . $this->ignore_time . ' second(s)' );
				$this->ignore_urls[$get_url] = time();
				set_transient( $this->ngfb->acronym . '_' . md5( 'ignore_urls' ), $this->ignore_urls, $this->ignore_time );
			}

			// return original url or empty data on failure
			return $want_this == 'url' ? $url : '';
		}

		private function get_cache_data( $cache_salt, $cache_name = 'file', $url_ext = '', $expire_secs = false ) {
			$cache_data = '';
			switch ( $cache_name ) {
				case 'wp_cache' :
				case 'transient' :
					$cache_type = 'object cache';
					$cache_id = $this->ngfb->acronym . '_' . md5( $cache_salt );	// add a prefix to the object cache id
					$this->ngfb->debug->log( $cache_type . ': cache_data ' . $cache_name . ' id salt "' . $cache_salt . '"' );
					if ( $cache_name == 'wp_cache' ) 
						$cache_data = wp_cache_get( $cache_id, __CLASS__ );
					elseif ( $cache_name == 'transient' ) 
						$cache_data = get_transient( $cache_id );
					if ( $cache_data !== false ) {
						$this->ngfb->debug->log( $cache_type . ': cache_data retrieved from ' . $cache_name . ' for id "' . $cache_id . '"' );
					}
					break;
				case 'file' :
					$cache_type = 'file cache';
					$cache_id = md5( $cache_salt );
					$cache_file = $this->base_dir . $cache_id . $url_ext;
					$this->ngfb->debug->log( $cache_type . ': filename id salt "' . $cache_salt . '"' );
					$file_expire = $expire_secs === false ? $this->file_expire : $expire_secs;
					if ( file_exists( $cache_file ) ) {
						if ( ! is_readable( $cache_file ) )
							$this->ngfb->notices->err( $cache_file . ' is not readable.' );
						elseif ( filemtime( $cache_file ) > time() - $file_expire && 
							( $this->ngfb->is_avail['aop'] == true || is_admin() ) ) {
							if ( ! $fh = @fopen( $cache_file, 'rb' ) )
								$this->ngfb->notices->err( 'Failed to open ' . $cache_file . ' for reading.' );
							else {
								$cache_data = fread( $fh, filesize( $cache_file ) );
								fclose( $fh );
								if ( ! empty( $cache_data ) )
									$this->ngfb->debug->log( $cache_type . ': cache_data retrieved from "' . $cache_file . '"' );
							}
						}
					}
					break;
				default :
					$this->ngfb->debug->log( 'unknown cache name "' . $cache_name . '"' );
					break;
			}
			return $cache_data;	// return data or empty string
		}

		private function save_cache_data( $cache_salt, $cache_data = '', $cache_name = 'file', $url_ext = '', $expire_secs = false ) {
			if ( empty( $cache_data ) ) return false;
			$ret_status = false;
			switch ( $cache_name ) {
				case 'wp_cache' :
				case 'transient' :
					$cache_type = 'object cache';
					$cache_id = $this->ngfb->acronym . '_' . md5( $cache_salt );	// add a prefix to the object cache id
					$this->ngfb->debug->log( $cache_type . ': cache_data ' . $cache_name . ' id salt "' . $cache_salt . '"' );
					$object_expire = $expire_secs === false ? $this->object_expire : $expire_secs;
					if ( $cache_name == 'wp_cache' ) 
						wp_cache_set( $cache_id, $cache_data, __CLASS__, $object_expire );
					elseif ( $cache_name == 'transient' ) 
						set_transient( $cache_id, $cache_data, $object_expire );
					$this->ngfb->debug->log( $cache_type . ': cache_data saved to ' . $cache_name . ' for id "' . $cache_id . '" (' . $object_expire . ' seconds)' );
					$ret_status = true;	// success
					break;
				case 'file' :
					$cache_type = 'file cache';
					$cache_id = md5( $cache_salt );
					$cache_file = $this->base_dir . $cache_id . $url_ext;
					$this->ngfb->debug->log( $cache_type . ': filename id salt "' . $cache_salt . '"' );
					if ( ! is_dir( $this->base_dir ) ) 
						mkdir( $this->base_dir );
					if ( ! is_writable( $this->base_dir ) )
						$this->ngfb->notices->err( $this->base_dir . ' is not writable.' );
					elseif ( $this->ngfb->is_avail['aop'] == true || is_admin() ) {
						if ( ! $fh = @fopen( $cache_file, 'wb' ) )
							$this->ngfb->notices->err( 'Failed to open ' . $cache_file . ' for writing.' );
						else {
							if ( fwrite( $fh, $cache_data ) ) {
								$this->ngfb->debug->log( $cache_type . ': cache_data saved to "' . $cache_file . '"' );
								$ret_status = true;	// success
							}
							fclose( $fh );
						}
					}
					break;
				default :
					$this->ngfb->debug->log( 'unknown cache name "' . $cache_name . '"' );
					break;
			}
			return $ret_status;	// return true or false
		}
	}
}
?>
