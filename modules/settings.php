<?php
/**
 * WPNuke Twitterium - Settings Panel
 *
 * @package		WPNuke
 * @subpackage	WPNuke_Twitterium/Settings
 * @copyright	Copyright (c) 2013, MasEDI.Net
 * @license		GNU Public License - http://opensource.org/licenses/gpl-2.0.php 
 * @since		1.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WPNT_Settings {

	public function __construct() {
		if(is_admin()){
			add_action('admin_menu', array($this, 'add_plugin_page'));
			add_action('admin_init', array($this, 'page_init'));
		}
	}
	
	public function add_plugin_page() {
		// This page will be under "Settings"
		add_options_page('WPNuke Twitterium', 'WPNuke Twitterium', 'manage_options', 'wpnuke-twitterium', array($this, 'create_admin_page'));
	}

	public function create_admin_page() {
	?>
	<div class="wrap">
		<?php screen_icon(); ?>
		<h2><?php _e( 'WPNuke Twitterium Settings' , 'wpnt' ); ?></h2>			
		<form method="post" action="options.php">
		<?php
		// This prints out all hidden setting fields
		settings_fields('wpnt_option_group');	
		do_settings_sections('wpnuke-twitterium');
		?>
		
		<p>
		<?php
		// Info
		printf( __('Doesn\'t have Twitter API OAuth settings?  Create once %s, it is free.', 'wpnuke'), '<a href="https://dev.twitter.com/apps/" target="_blank">here</a>' );
		?>
		</p>
		
		<?php
		// Submit button
		submit_button();
		?>
		
		</form>
	</div>
	<?php
	}
	
	public function page_init() {
		register_setting('wpnt_option_group', 'wpnt_option_settings', array($this, 'sanitize_wpnt_option_settings'));
		
		/** General Settings **/
		
		add_settings_section(
			'wpnt_general_setting',
			'WPNuke Twitterium Settings',
			array($this, 'wpnt_general_section_info'),
			'wpnuke-twitterium'
		);

		add_settings_field(
			'wpnt_screen_name', 
			'Default Twitter Screen Name', 
			array($this, 'wpnt_screen_name_field'), 
			'wpnuke-twitterium',
			'wpnt_general_setting'
		);
		
		add_settings_field(
			'wpnt_tweet_count', 
			'Maximum Tweet Count', 
			array($this, 'wpnt_tweet_count_field'), 
			'wpnuke-twitterium',
			'wpnt_general_setting'
		);
		
		add_settings_field(
			'wpnt_cache_time', 
			'Cache Expiration Time', 
			array($this, 'wpnt_cache_time_field'), 
			'wpnuke-twitterium',
			'wpnt_general_setting'			
		);
		
		/** Twitter API Settings **/
		
		add_settings_section(
			'wpnt_twitter_api_setting',
			'Twitter API OAuth Settings',
			array($this, 'wpnt_twitter_api_section_info'),
			'wpnuke-twitterium'
		);	
		
		add_settings_field(
			'wpnt_consumer_key', 
			'Consumer Key', 
			array($this, 'wpnt_consumer_key_field'), 
			'wpnuke-twitterium',
			'wpnt_twitter_api_setting'			
		);
		
		add_settings_field(
			'wpnt_consumer_secret', 
			'Consumer Secret', 
			array($this, 'wpnt_consumer_secret_field'), 
			'wpnuke-twitterium',
			'wpnt_twitter_api_setting'			
		);
		
		add_settings_field(
			'wpnt_access_token', 
			'Access Token', 
			array($this, 'wpnt_access_token_field'), 
			'wpnuke-twitterium',
			'wpnt_twitter_api_setting'			
		);
		
		add_settings_field(
			'wpnt_access_token_secret', 
			'Access Token Secret', 
			array($this, 'wpnt_access_token_secret_field'), 
			'wpnuke-twitterium',
			'wpnt_twitter_api_setting'			
		);
		
		add_settings_field(
			'wpnt_access_token_bearer', 
			'Access Token Bearer', 
			array($this, 'wpnt_access_token_bearer_field'), 
			'wpnuke-twitterium',
			'wpnt_twitter_api_setting'			
		);
		
	}
	
	/** Setting Field Validation **/
	
	public function sanitize_wpnt_option_settings($input) {

		update_option( 'wpnt_screen_name', $input['wpnt_screen_name'] );
		update_option( 'wpnt_tweet_count', $input['wpnt_tweet_count'] );
		
		$wpnt_cache_time = intval( $input['wpnt_cache_time'] );
		update_option( 'wpnt_cache_time', $wpnt_cache_time );
		
		update_option( 'wpnt_consumer_key', $input['wpnt_consumer_key'] );
		update_option( 'wpnt_consumer_secret', $input['wpnt_consumer_secret'] );
		update_option( 'wpnt_access_token', $input['wpnt_access_token'] );
		update_option( 'wpnt_access_token_secret', $input['wpnt_access_token_secret'] );
		update_option( 'wpnt_access_token_bearer', $input['wpnt_access_token_bearer'] );
		
		return;
	}
	
	/** Print Section Info **/

	public function wpnt_general_section_info() {
		print 'Enter your WPNuke Twitterium settings below:';
	}

	public function wpnt_twitter_api_section_info() {
		print 'Enter your Twitter API OAuth settings below:';
	}
		
	/** Setting Field**/
	
	public function wpnt_screen_name_field() {
		$screen_name = get_option('wpnt_screen_name');
		if ( $screen_name === false || $screen_name == '' ) { $screen_name = WPNT_SCREEN_NAME; }
	?>
		<input type="text" id="wpnt_screen_name" name="wpnt_option_settings[wpnt_screen_name]" value="<?php echo $screen_name; ?>" />
		<span><?php _e( '(username)', 'wpnt'); ?></span><?php
	}
	
	public function wpnt_tweet_count_field() {
		$tweet_count = get_option('wpnt_tweet_count');
		if ( $tweet_count === false || $tweet_count == '' ) { $tweet_count = WPNT_TWEET_COUNT; }
	?>
		<input type="text" id="wpnt_tweet_count" name="wpnt_option_settings[wpnt_tweet_count]" value="<?php echo $tweet_count; ?>" />
	<?php
	}

	public function wpnt_cache_time_field() {
		$cache_expire = get_option('wpnt_cache_time');
		if ( $cache_expire === false || $cache_expire == '' ) { $cache_expire = WPNT_CACHE_TIME; }
	?>
		<input type="text" id="wpnt_cache_time" name="wpnt_option_settings[wpnt_cache_time]" value="<?php echo $cache_expire; ?>" />
		<span><?php _e( '(in second)', 'wpnt'); ?></span>
	<?php
	}
	
	public function wpnt_consumer_key_field() {
		?><input type="text" id="wpnt_consumer_key" name="wpnt_option_settings[wpnt_consumer_key]" value="<?php echo get_option('wpnt_consumer_key'); ?>" /><?php
	}
	
	public function wpnt_consumer_secret_field() {
		?><input type="text" id="wpnt_consumer_secret" name="wpnt_option_settings[wpnt_consumer_secret]" value="<?php echo get_option('wpnt_consumer_secret'); ?>" /><?php
	}
	
	public function wpnt_access_token_field() {
		?><input type="text" id="wpnt_access_token" name="wpnt_option_settings[wpnt_access_token]" value="<?php echo get_option('wpnt_access_token'); ?>" /><?php
	}
	
	public function wpnt_access_token_secret_field() {
		?><input type="text" id="wpnt_access_token_secret" name="wpnt_option_settings[wpnt_access_token_secret]" value="<?php echo get_option('wpnt_access_token_secret'); ?>" /><?php
	}
	
	public function wpnt_access_token_bearer_field() {
		?><input type="text" id="wpnt_access_token_bearer" name="wpnt_option_settings[wpnt_access_token_bearer]" value="<?php echo get_option('wpnt_access_token_bearer'); ?>" />
		<span><?php _e( '(optional)', 'wpnt' ); ?></span><?php
	}
}

$WPNT_Settings = new WPNT_Settings();
?>