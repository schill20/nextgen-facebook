<?php
/*
Plugin Name: NextGEN Facebook
Plugin URI: http://wordpress.org/extend/plugins/nextgen-facebook/
Description: Adds Facebook HTML meta tags to webpage headers, including featured images. Also includes optional Like and Send Facebook buttons.
Version: 1.1
Author: Jean-Sebastien Morisset
Author URI: http://trtms.com/

This plugin is based on the WP Facebook Like Send & Open Graph Meta v1.2.3
plugin by Marvie Pons.

The NextGEN Facebook plugin adds Facebook Open Graph HTML meta tags (admins,
app_id, title, type, image, site_name, description, and url) to all webpage
headers. The featured image, from a NextGEN Gallery or Media Library, in a
Post or Page will be used in it's meta tags. The plugin also includes an
option to add Like and Send Facebook buttons to your Posts and Pages.

Although this plugin was written to retrieve featured image information from a
NextGEN gallery, it also works just as well without it.

The image used in the Open Graph meta tag will be determined in this sequence;
a featured image from a NextGEN Gallery or WordPress Media Library, the first
NextGEN [singlepic] or IMG HTML tag in the content, a default image URL
defined in the plugin settings. If none of these conditions can be satisfied,
then the Open Graph image tag will be left empty.

And example of the meta tags defined by NextGEN Facebook for a WordPress post:

<!-- NextGEN Facebook plugin open graph tags BEGIN -->
<meta property="fb:admins" content="" />
<meta property="fb:app_id" content="" />
<meta property="og:title" content="Title of a WordPress Post" />
<meta property="og:type" content="article" />
<meta property="og:image" content="http://trtms.com/wp-content/gallery/cache/136_crop_200x200_featured-image-filename.jpg" />
<meta property="og:site_name" content="The Road to Myself" />
<meta property="og:description" content="A short amount of text, taken from the excerpt or content, that's used by Facebook when displaying the like or share information box." />
<meta property="og:url" content="http://trtms.com/2012/06/24/title-of-a-wordpress-post/" />
<!-- NextGEN Facebook plugin open graph tags END -->

Copyright 2012 Jean-Sebastien Morisset

This script is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This script is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/

function ngfb_requires_wordpress_version() {
	global $wp_version;
	$plugin = plugin_basename( __FILE__ );
	$plugin_data = get_plugin_data( __FILE__, false );
	if ( version_compare($wp_version, "3.0", "<" ) ) {
		if( is_plugin_active($plugin) ) {
			deactivate_plugins( $plugin );
			wp_die( "'".$plugin_data['Name']."' requires WordPress 3.0 or
			higher and has been deactivated. Please upgrade WordPress and try
			again.<br /><br />Back to <a href='".admin_url()."'>WordPress
			admin</a>." );
		}
	}
}
add_action( 'admin_init', 'ngfb_requires_wordpress_version' );

register_activation_hook(__FILE__, 'ngfb_add_defaults');
register_uninstall_hook(__FILE__, 'ngfb_delete_plugin_options');
add_action('admin_init', 'ngfb_init' );
add_action('admin_menu', 'ngfb_add_options_page');
add_filter('plugin_action_links', 'ngfb_plugin_action_links', 10, 2);

// Delete options table entries ONLY when plugin deactivated AND deleted
function ngfb_delete_plugin_options() {
	delete_option('ngfb_options');
}

// Define default option settings
function ngfb_add_defaults() {
	$tmp = get_option('ngfb_options');
    if(($tmp['ngfb_reset']=='1')||(!is_array($tmp))) {
		delete_option('ngfb_options');
		$arr = array(
			"og_img_size" => "thumbnail",
			"og_def_img" => "",
			"og_def_home" => "",
			"og_desc_len" => "300",
			"og_admins" => "",
			"og_app_id" => "",
			"fb_enable" => "",
			"fb_on_home" => "",
			"fb_send" => "true",
			"fb_layout" => "button_count",
			"fb_colorscheme" => "light",
			"fb_font" => "arial",
			"fb_show_faces" => "false",
			"fb_action" => "like",
		);
		update_option('ngfb_options', $arr);
	}
}

// Init plugin options to white list our options
function ngfb_init(){
	register_setting( 'ngfb_plugin_options', 'ngfb_options', 'ngfb_validate_options' );
}

// Add menu page
function ngfb_add_options_page() {
	add_options_page('NextGEN Facebook Options Page', 'NextGEN Facebook', 'manage_options', 'ngfb', 'ngfb_render_form');
}

// Render the Plugin options form
function ngfb_render_form() {
	?>
	<div class="wrap">
	<div class="icon32" id="icon-options-general"><br></div>
	<h2>NextGEN Facebook Plugin</h2>

	<p>Once enabled, the NextGEN Facebook plugin will add Facebook Open Graph
	meta tags to your webpages. If your Post or Page has a featured image
	defined, it will be included in the meta tags for Facebook's share and
	like features.  All options bellow are optional. You can enable share /
	like buttons, add a default image when there's no featured image defined,
	etc.</p>

	<p>The image used in the Open Graph meta tag will be determined in this
	sequence; a featured image from a NextGEN Gallery or WordPress Media
	Library, the first NextGEN [singlepic] or IMG HTML tag in the content, the
	default image URL defined bellow. If none of these conditions can be
	satisfied, then the Open Graph image tag will be left empty.</p>

	<div class="metabox-holder">
		<div class="postbox">
			<h3>Facebook Open Graph Settings</h3>
			<div class="inside">	
	
	<!-- Beginning of the Plugin Options Form -->
	<form method="post" action="options.php">
		<?php settings_fields('ngfb_plugin_options'); ?>
		<?php $options = get_option('ngfb_options'); ?>

		<table class="form-table">
			<tr>
				<th>Image Size Name</th>
				<td>
					<select name='ngfb_options[og_img_size]'>
					<?php
						global $_wp_additional_image_sizes;
						// Display the sizes in the array
						foreach ( get_intermediate_image_sizes() as $s ) {
							// Don't make or numeric sizes that appear
							if( is_integer( $s ) ) continue;
	
							if ( isset( $_wp_additional_image_sizes[$s]['width'] ) ) // For theme-added sizes
								$width = intval( $_wp_additional_image_sizes[$s]['width'] );
							else                                                     // For default sizes set in options
								$width = get_option( "{$s}_size_w" );
			
							if ( isset( $_wp_additional_image_sizes[$s]['height'] ) ) // For theme-added sizes
								$height = intval( $_wp_additional_image_sizes[$s]['height'] );
							else                                                      // For default sizes set in options
								$height = get_option( "{$s}_size_h" );
			
							if ( isset( $_wp_additional_image_sizes[$s]['crop'] ) )   // For theme-added sizes
								$crop = intval( $_wp_additional_image_sizes[$s]['crop'] );
							else                                                      // For default sizes set in options
								$crop = get_option( "{$s}_crop" );

							echo "<option value='$s' ".(selected($options['og_img_size'], $s)).">$s (${width} x ${height}".($crop ? " cropped" : "").")</option>\n";
						}
					?>
					</select>

					<p>The WordPress Media Library Size Name for the image used
					in the Open Graph meta tag. Generally this would be
					"thumbnail" (currently defined as <?php echo
					get_option('thumbnail_size_w'); ?> x <?php echo
					get_option('thumbnail_size_h'); ?>, <?php echo
					get_option('thumbnail_crop') == "1" ? "" : "not"; ?>
					cropped), or other Size Names like "medium", "large", etc.
					Choose a Size Name that is at least 200px or more in width
					and height, and preferably cropped. You can use the <a
					src="http://wordpress.org/extend/plugins/simple-image-sizes/"
					target="_blank">Simple Image Size</a> plugin (or others)
					to define additional sizes on the Settings-&gt;Media page.
					I would suggest creating a "facebook-thumbnail" Size Name
					of 200 x 200 (or larger) cropped, to control the size of
					Open Graph images independently from those of your theme.</p>
				</td>
			</tr>

			<tr>
				<th scope="row">Default Image URL</th>
				<td><input type="text" name="ngfb_options[og_def_img]" size="100"
					value="<?php echo $options['og_def_img']; ?>" />

					<p>The URL (including the http:// prefix) to your default
					image. It will be used on your homepage and post / pages
					that do not have a featured image, [singlepic] shortcode,
					or IMG HTML tag. It should be at least 200px or more in
					width and height.</p>

					<p>It would be best not to use an image from the NextGEN
					gallery, in case the gallery/cache/ folder is cleaned.
					Instead, upload a image to the WP Media Library. Once the
					image has been uploaded, click on 'Edit' to see the
					thumbnail and image details. Right-click and choose 'View
					Image' on the thumbnail. Use the image URL here. Note:
					<i>This assumes you've defined a thumbnail size of at least
					200x200 in your Settings-&gt;Media options</i>.</p>

					<p>You can also use a cached NextGEN image by using
					NextGEN's image callback URL. Example:
					http://{<b>hostname</b>)/index.php?callback=image&amp;pid={<b>image_id_number</b>}&amp;width=200&amp;height=200&amp;mode=crop.
					Once you've used this URL once, you can refer to the cached
					image URL as your default image. Example:
					http://{<b>hostname</b>}/wp-content/gallery/cache/{<b>image_id_number</b>}_crop_200x200_{<b>image_file_name</b>}.</p>

					<p>If you have no other choice, you can use the callback
					URL as your default image URL here. Note that this will
					require a <i>little</i> more resource from your web server
					than a static image does.<p>
				</td>
			</tr>

			<tr valign="top">
				<th scope="row" nowrap>Use Default on Multi-Entry Pages</th>
				<td><input name="ngfb_options[og_def_home]" type="checkbox" value="1" 
					<?php if (isset($options['og_def_home'])) { checked('1', $options['og_def_home']); } ?> />

					<p>Check this box if you would like to use the default
					image on page types with more than one entry (homepage,
					archives, categories, etc.). If you leave this un-checked,
					the NextGEN Facebook plugin will attempt to use the first
					featured image, [singlepic] shortcode, or IMG HTML tag
					within the list of entries on the page.</p>
				</td>
			</tr>

			<tr>
				<th scope="row">Max Description Length</th>
				<td><input type="text" size="4" name="ngfb_options[og_desc_len]" 
					value="<?php echo $options['og_desc_len']; ?>" />

					<p>The maximum description length, based on your post /
					page excerpt or content, included in the Open Graph meta
					tag. The description length must be 160 characters or more
					(the default is 300).</p>
				</td>
			</tr>

			<tr>
				<th scope="row">Facebook Admin(s)</th>
				<td><input type="text" size="40" name="ngfb_options[og_admins]" 
					value="<?php echo $options['og_admins']; ?>" />

					<p>Enter one of more Facebook account names (generally your
					own), seperated with a comma. When you are viewing your own
					Facebook wall, your account name is located in the URL.
					Example:
					https://www.facebook.com/{<b>account_name</b>}.</p>
				</td>
			</tr>

			<tr>
				<th scope="row">Facebook App ID</th>
				<td><input type="text" size="40" name="ngfb_options[og_app_id]" 
					value="<?php echo $options['og_app_id']; ?>" />

					<p>If you have a Facebook App ID, enter it here.</p>
				</td>
			</tr>
		</table>
			</div>
		</div>
		<div class="postbox">
			<h3>Facebook Button Settings</h3>
			<div class="inside">	
		<table class="form-table">
			<tr valign="top">
				<th scope="row" nowrap>Enable Facebook Button(s)</th>
				<td><input name="ngfb_options[fb_enable]" type="checkbox" value="1" 
					<?php if (isset($options['fb_enable'])) { checked('1', $options['fb_enable']); } ?> />

					<p>Add Facebook "Like" (and optionally "Send") button to
					your posts and pages. The default is not to include the
					Facebook button.</p>
				</td>
			</tr>

			<tr valign="top">
				<th scope="row" nowrap>Include on Homepage</th>
				<td><input name="ngfb_options[fb_on_home]" type="checkbox" value="1"
					<?php if (isset($options['fb_on_home'])) { checked('1', $options['fb_on_home']); } ?> /></td>
			</tr>

			<tr valign="top">
				<th scope="row" nowrap>Add Facebook Send Button</th>
				<td><input name="ngfb_options[fb_send]" type="checkbox" value="true"
					<?php if (isset($options['fb_send'])) { checked('true', $options['fb_send']); } ?> /></td>
			</tr>
			
			<tr>
				<th scope="row">Button Layout Style</th>
				<td>
					<select name='ngfb_options[fb_layout]'>
						<option value='standard' <?php selected($options['fb_layout'], 'standard'); ?>>Standard</option>
						<option value='button_count' <?php selected($options['fb_layout'], 'button_count'); ?>>Button Count</option>
						<option value='box_count' <?php selected($options['fb_layout'], 'box_count'); ?>>Box Count</option>
					</select>
				</td>
			</tr>
			
			<tr>
				<th scope="row">Show Facebook Faces</th>
				<td>
					<select name='ngfb_options[fb_show_faces]'>
						<option value='true' <?php selected($options['fb_show_faces'], 'true'); ?>>Show</option>
						<option value='false' <?php selected($options['fb_show_faces'], 'false'); ?>>Hide</option>
					</select>
				</td>
			</tr>
			
			<tr>
				<th scope="row">Button Font</th>
				<td>
					<select name='ngfb_options[fb_font]'>
						<option value='arial' <?php selected('arial', $options['fb_font']); ?>>Arial</option>
						<option value='lucida grande' <?php selected('lucida grande', $options['fb_font']); ?>>Lucida Grande</option>
						<option value='segoe ui' <?php selected('segoe ui', $options['fb_font']); ?>>Segoe UI</option>
						<option value='tahoma' <?php selected('tahoma', $options['fb_font']); ?>>Tahoma</option>
						<option value='trebuchet ms' <?php selected('trebuchet ms', $options['fb_font']); ?>>Trebuchet MS</option>
						<option value='verdana' <?php selected('verdana', $options['fb_font']); ?>>Verdana</option>
					</select>
				</td>
			</tr>

			<tr>
				<th scope="row">Button Color Scheme</th>
				<td>
					<select name='ngfb_options[fb_colorscheme]'>
						<option value='light' <?php selected('light', $options['fb_colorscheme']); ?>>Light</option>
						<option value='dark' <?php selected('dark', $options['fb_colorscheme']); ?>>Dark</option>
					</select>
				</td>
			</tr>
			
			<tr>
				<th scope="row">Facebook Action Name</th>
				<td>
					<select name='ngfb_options[fb_action]'>
						<option value='like' <?php selected('like', $options['fb_action']); ?>>Like</option>
						<option value='recommend' <?php selected('recommend', $options['fb_action']); ?>>Recommend</option>
					</select>
				</td>
			</tr>				
		</table>
			</div>
		</div>
		<div class="postbox">
			<h3>Plugin Settings</h3>
			<div class="inside">	
		<table class="form-table">
			<tr>
				<th scope="row" nowrap>Reset Settings on Activate</th>
				<td><label><input name="ngfb_options[ngfb_reset]" type="checkbox" value="1" 
					<?php if (isset($options['ngfb_reset'])) { checked('1', $options['ngfb_reset']); } ?> />

					<p>Check this option to reset NextGEN Facebook settings to
					their default values <u>when you deactivate, and then
					reactivate the plugin</u>.</p>
				</td>
			</tr>
		</table>
			</div><!-- .inside -->
		</div><!-- .postbox -->
	</div><!-- .metabox-holder -->

		<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />

	</form>
</div>
	<?php	
}

// Sanitize and validate input
function ngfb_validate_options($input) {

	$input['og_img_size'] = wp_filter_nohtml_kses($input['og_img_size']);
	if (! $input['og_img_size']) $input['og_img_size'] = "thumbnail";

	$input['og_def_img'] = wp_filter_nohtml_kses($input['og_def_img']); // Sanitize textbox input (strip html tags, and escape characters)
	$input['og_admins'] = wp_filter_nohtml_kses($input['og_admins']);
	$input['og_app_id'] = wp_filter_nohtml_kses($input['og_app_id']);

	if (! $input['og_desc_len'] || ! is_numeric($input['og_desc_len']) || ! $input['og_desc_len'] > 160) $input['og_desc_len'] = 160;

	if ( ! isset( $input['og_def_home'] ) ) $input['og_def_home'] = null;
	$input['og_def_home'] = ( $input['og_def_home'] == 1 ? 1 : 0 );
	
	if ( ! isset( $input['fb_enable'] ) ) $input['fb_enable'] = null;
	$input['fb_enable'] = ( $input['fb_enable'] == 1 ? 1 : 0 );
	
	if ( ! isset( $input['fb_on_home'] ) ) $input['fb_on_home'] = null;
	$input['fb_on_home'] = ( $input['fb_on_home'] == 1 ? 1 : 0 );
	
	if ( ! isset( $input['fb_send'] ) ) $input['fb_send'] = null;
	$input['fb_send'] = ( $input['fb_send'] == "true" ? "true" : "false" );

	if ( ! isset( $input['ngfb_reset'] ) ) $input['ngfb_reset'] = null;
	$input['ngfb_reset'] = ( $input['ngfb_reset'] == 1 ? 1 : 0 );
	
	return $input;
}

// Display a Settings link on the main Plugins page
function ngfb_plugin_action_links( $links, $file ) {

	if ( $file == plugin_basename( __FILE__ ) ) {
		$ngfb_links = '<a href="'.get_admin_url().'options-general.php?page=ngfb">'.__('Settings').'</a>';
		// make the 'Settings' link appear first
		array_unshift( $links, $ngfb_links );
	}

	return $links;
}

// ------------------------------------------------------------------------------
// OUR PLUGIN FUNCTIONS:
// ------------------------------------------------------------------------------

function ngfb_facebook_buttons($content){

	$options = get_option('ngfb_options');

	if (! $options['fb_enable']) return $content;

	$fb_send = $options['fb_send'];
	if($fb_send == '') { $fb_send = 'true'; }
	
	$fb_layout = $options['fb_layout'];
	if($fb_layout == '') { $fb_layout = 'button_count'; }
	
	$fb_show_faces = $options['fb_show_faces'];
	if($fb_show_faces == '') { $fb_show_faces = 'false'; }
	
	$fb_colorscheme = $options['fb_colorscheme'];
	if($fb_colorscheme == '') { $fb_colorscheme = 'light'; }
	
	$fb_action = $options['fb_action'];
	if($fb_action == '') { $fb_action = 'like'; }
	
	$fb_font = $options['fb_font'];
	if($fb_font == '') { $fb_font = 'arial'; }
	
	$fb_buttons = '<div id="fb-root"></div><script src="http://connect.facebook.net/en_US/all.js#xfbml=1"></script>';
	$fb_buttons .= '<fb:like href="'.get_permalink($post->ID).'"
		send="'.$fb_send.'" layout="'.$fb_layout.'" width="400"
		show_faces="'.$fb_show_faces.'" font="'.$fb_font.'" action="'.$fb_action.'"
		colorscheme="'.$fb_colorscheme.'"></fb:like>';

	if( !is_feed() && !is_home() ) {
		$content .= $fb_buttons;
	} else if ( isset($options['fb_on_home']) && ( $options['fb_on_home'] != "" ) ) { 
		$content .= $fb_buttons;
	}

	return $content;
}

add_action('the_content', 'ngfb_facebook_buttons');

// Adding the Open Graph in the Language Attributes
function ngfb_add_og_doctype_wp( $output ) {
	return $output . ' xmlns:og="http://opengraphprotocol.org/schema/" xmlns:fb="http://www.facebook.com/2008/fbml"';
}

add_filter('language_attributes', 'ngfb_add_og_doctype_wp');

add_action('wp_head', 'ngfb_add_facebook_og_wp');

// thumbnailID must be 'ngg-#'
function ngfb_ngg_thumbnail_url( $thumbnailID ) {

    if (! method_exists( 'nggdb', 'find_image' ) ) return;

	if ( is_string($thumbnailID) && substr($thumbnailID, 0, 4) == 'ngg-') {

		$thumbnailID = substr($thumbnailID, 4);
		$image = nggdb::find_image($thumbnailID);	// returns an nggImage object

		if ($image != null) {

			$options = get_option('ngfb_options');
			$size = $options['og_img_size'];
			$width = 200; $height = 200; $crop = 1;

			global $_wp_additional_image_sizes;
			$tmp = get_intermediate_image_sizes();
	
			if ( isset( $_wp_additional_image_sizes[$size]['width'] ) )
				$width = intval( $_wp_additional_image_sizes[$size]['width'] );
			else $width = get_option( "{$size}_size_w" );

			if ( isset( $_wp_additional_image_sizes[$size]['height'] ) )
				$height = intval( $_wp_additional_image_sizes[$size]['height'] );
			else $height = get_option( "{$size}_size_h" );

			if ( isset( $_wp_additional_image_sizes[$size]['crop'] ) )
				$crop = intval( $_wp_additional_image_sizes[$size]['crop'] );
			else $crop = get_option( "{$size}_crop" );

			$crop = ( $crop == 1 ? 'crop' : '' );

			// Check to see if the image already exists
			$imageURL = $image->cached_singlepic_file( $width, $height, $crop );

			// If not, then use the dynamic image url
			if (empty($imageURL)) 
				$imageURL = trailingslashit(home_url()).'index.php?callback=image&amp;pid='.$thumbnailID.'&amp;width='.$width.'&amp;height='.$height.'&amp;mode='.$crop;
		}
    }
    return $imageURL;
}

function ngfb_add_facebook_og_wp() {
	global $post;
	
	$options = get_option('ngfb_options');

	if( !is_feed() && !is_home() ) {
		$content .= $fb_buttons;
	} else if ( isset($options['fb_on_home']) && ( $options['fb_on_home'] != "" ) ) { 
		$content .= $fb_buttons;
	}

	if ( is_single() || is_page() || !$options['og_def_home'] ) {

		if ( function_exists('has_post_thumbnail') && has_post_thumbnail( $post->ID ) ) {
	
			$thumbnailID = get_post_thumbnail_id( $post->ID );
	
			// If the post thumbnail id has the form ngg- then it is a NextGEN image.
			if ( is_string($thumbnailID) && substr($thumbnailID, 0, 4) == 'ngg-') {
				$imageURL = ngfb_ngg_thumbnail_url( $thumbnailID );
			} else {
				$out = wp_get_attachment_image_src($thumbnailID, $options['og_img_size']);
				$imageURL = $out[0];
			}
		}
	
		// If there is no featured image or any image, search post for images and display first one.
		if(! $imageURL) {
			$out = preg_match_all( '/\[singlepic[^\]]+id=([0-9]+)/i', $post->post_content, $match);
			if ( $out > 0 ) {
				$thumbnailID = $match[1][0];					
				$imageURL = ngfb_ngg_thumbnail_url( 'ngg-'.$thumbnailID );
			} else {
				$out = preg_match_all( '/<img[^>]+src=[\'"]([^\'"]+)[\'"]/i', $post->post_content, $match);
				if ( $out > 0 ) $imageURL = $match[1][0];					
			}
		}
	}

	// If none exists, then show the default url.
	if(! $imageURL) $imageURL = $options['og_def_img'];

	if (has_excerpt($post->ID)) {
		$excerpt = esc_attr(substr(strip_tags(get_the_excerpt($post->ID)), 0, $options['og_desc_len']));
	} else {
		$excerpt = esc_attr(str_replace("\r\n",' ',substr(strip_tags(strip_shortcodes($post->post_content)), 0, $options['og_desc_len'])));
	}

	$site_description = get_bloginfo( 'description', 'display' );
	$site_title = get_bloginfo( 'name', 'display' );
?>

<!-- NextGEN Facebook plugin open graph tags BEGIN -->
<meta property="fb:admins" content="<?php echo $options['og_admins']; ?>" />
<meta property="fb:app_id" content="<?php echo $options['og_app_id']; ?>" />
<meta property="og:site_name" content="<?php echo $site_title; ?>" />
<meta property="og:title" content="<?php
	global $page, $paged;
	$page_title = trim( wp_title( '|', false, 'right' ), ' |');
	if ($page_title) echo $page_title; else echo $site_title;
	// Add a page number if necessary
	if ( $paged >= 2 || $page >= 2 )
		echo ' | ' . sprintf( 'Page %s', max( $paged, $page ) );
?>" />
<meta property="og:type" content="<?php 
	if ( is_single() || is_page() ) echo "article";
	else echo "website";
?>" />
<meta property="og:image" content="<?php echo $imageURL; ?>" />
<meta property="og:description" content="<?php 
	if ( is_singular() ) echo $excerpt;
	elseif ( is_author() ) {  the_post(); printf( 'Authored by %s', get_the_author() ); }
	elseif ( is_tag() ) printf( 'Tagged with &quot;%s&quot;', single_tag_title('', false) );
	elseif ( is_category() ) printf( '&quot;%s&quot; Category', single_cat_title('', false) );
	elseif ( is_day() ) printf( 'Daily Archives for %s', get_the_date() );
	elseif ( is_month() ) printf( 'Monthly Archives for %s', get_the_date('F Y') );
	elseif ( is_year() ) printf( 'Yearly Archives for %s', get_the_date('Y') );
	else echo $site_description;
?>" />
<meta property="og:url" content="http://<?php echo $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>" />
<!-- NextGEN Facebook plugin open graph tags END -->

<?php
	}
?>
