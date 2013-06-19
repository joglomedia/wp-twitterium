<?php
/*
Plugin Name: WPNuke Twitterium
Plugin URI: http://wpnuke.com/plugins/wpnuke-twitterium/
Description: A super simple WordPress plugin to handles twitter feeds, including parsing @username, #hashtag, and URLs into links. Add latest tweet widgets and shortcodes to WordPress. Thanks to @themattharis for his <a href="https://github.com/themattharris/tmhOAuth">tmhOAuth</a> lib.
Author: MasEDI
Author URI: http://masedi.net/
Version: 1.0
Text Domain: wpnt
License: GPL2 or later
Domain Path: /
*/
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Define plugin version.
 */
define( 'WPNT_VERSION', '1.0' );

/**
 * Main WPNuke Twitterium Class
 *
 * @since WPNuke Twitterium 1.0
 */
final class WPNuke_Twitterium {

  /**
	 * @var $instance The one true WPNuke_Twitterium
	 */
	private static $instance;

	/**
	 * Main WPNuke Twitterium Instance.
	 *
	 * Ensures that only one instance of WPNuke Twitterium exists in memory at any one time. 
	 * Also prevents needing to define globals all over the place.
	 *
	 * @since WPNuke Twitterium 1.0
	 *
	 * @return The one true WPNuke Twitterium
	 */
	public static function instance() {
		if ( ! isset ( self::$instance ) ) {
			self::$instance = new WPNuke_Twitterium;
			self::$instance->setup_globals();
			self::$instance->include_files();
			self::$instance->setup_hooks();
		}

		return self::$instance;
	}
	
	/** Private Methods **/

	/**
	 * Set some smart defaults to class variables.
	 * Allow some of them to be filtered to allow for early overriding.
	 *
	 * @since WPNuke Twitterium 1.0
	 *
	 * @return void
	 */
	private function setup_globals() {
	
		/** Versions **/

		$this->version		= WPNT_VERSION;

		/** Paths **/

		$this->file         = __FILE__;
		$this->basename     = apply_filters( 'wpnt_plugin_basenname', plugin_basename( $this->file ) );
		$this->plugin_dir   = apply_filters( 'wpnt_plugin_dir_path', plugin_dir_path( $this->file ) );
		$this->plugin_url   = apply_filters( 'wpnt_plugin_dir_url', plugin_dir_url( $this->file ) );

		// Includes dir
		$this->includes_dir = apply_filters( 'wpnt_includes_dir', trailingslashit( $this->plugin_dir . 'includes' ) );
		$this->includes_url = apply_filters( 'wpnt_includes_url', trailingslashit( $this->plugin_url . 'includes' ) );

		// Languages dir
		$this->lang_dir     = apply_filters( 'wpnt_lang_dir', trailingslashit( $this->plugin_dir . 'languages' ) );

		/** Misc **/

		// Plugin domain
		$this->domain       = 'wpnt';
		
		// tmhOAuth lib
		$this->tmhoauth_dir	= apply_filters( 'wpnt_includes_dir', trailingslashit( $this->includes_dir . 'tmhOAuth' ) );
		
		/** Constants. For used outside of this class. **/
		
		// Includes dir
		define( 'WPNT_INCLUDES_DIR', $this->includes_dir );
		define( 'WPNT_INCLUDES_URL', $this->includes_url );
		
		// tmhOAuth dir
		define( 'WPNT_TMHOAUTH_DIR', $this->tmhoauth_dir );
		
		// User agent
		define( 'WPNT_USER_AGENT', 'WordPress/' . get_bloginfo( 'version' ) . ' (WPNuke_Twitterium ' . WPNT_VERSION . ')' );
		
		// Some default settings
		define( 'WPNT_SCREEN_NAME', 'gombile13' );
		define( 'WPNT_TWEET_COUNT', 10 );
		define( 'WPNT_CACHE_TIME', 600 );
		define( 'WPNT_FOLLOWME_TEXT', 'Follow Me on Twitter' );
	}

	/**
	 * Include required files.
	 *
	 * @since WPNuke Twitterium 1.0
	 *
	 * @return void
	 */
	private function include_files() {
		require( $this->tmhoauth_dir . 'tmhOAuth.php' );
		require( $this->includes_dir . 'wpntOAuth.php' );
		require( $this->includes_dir . 'settings.php' );
		require( $this->includes_dir . 'widgets.php' );
		//require( $this->includes_dir . 'shortcodes.php' );

		do_action( 'wpnt_include_files' );

		if ( ! is_admin() )
			return;

		do_action( 'wpnt_include_admin_files' );
	}
	
	/**
	 * Setup the default hooks (actions and filters)
	 *
	 * @since Appthemer CrowdFunding 0.1-alpha
	 *
	 * @return void
	 */
	private function setup_hooks() {
		add_action( 'admin_init', array( $this, 'is_curl_loaded' ), 1 );
		
		do_action( 'wpnt_setup_actions' );

		$this->load_textdomain();
	}

	/**
	 * Check PHP Curl module.
	 *
	 * @since WPNuke Twitterium 1.0
	 *
	 * @return void
	 */
	function is_curl_loaded() {
		if ( !function_exists( 'curl_init' ) || !in_array( 'curl', get_loaded_extensions() ) ) {
			if ( is_plugin_active( $this->basename ) ) {
				deactivate_plugins( $this->basename );
				unset($_GET[ 'activate' ] ); // Ghetto

				add_action( 'admin_notices', array( $this, 'curl_error_notice' ) );
			}
		}
	}
	
	/**
	 * Admin notice.
	 *
	 * @since WPNuke Twitterium 1.0
	 *
	 * @return void
	 */
	function curl_error_notice() {
	?>
		<div class="updated">
			<p>
			<?php
			printf( 
				__( '<strong>Notice:</strong> WPNuke Twitterium requires <em>cURL module</em> enabled in order to function properly.<br /> 
				If you need web hosting with cURL module enabled please check <a href="%s" target="_blank">Hibiniu Labs</a>.', 'atcf' ), 
				'http://www.hibiniu.com/'
			);
			?>
			</p>
		</div>
	<?php
	}
	
	/**
	 * Loads the plugin language files.
	 *
	 * @since WPNuke Twitterium 1.0
	 */
	public function load_textdomain() {
		// Traditional WordPress plugin locale filter
		$locale        = apply_filters( 'plugin_locale', get_locale(), $this->domain );
		$mofile        = sprintf( '%1$s-%2$s.mo', $this->domain, $locale );

		// Setup paths to current locale file
		$mofile_local  = $this->lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/' . $this->domain . '/' . $mofile;

		// Look in global /wp-content/languages/wpnt folder
		if ( file_exists( $mofile_global ) ) {
			return load_textdomain( $this->domain, $mofile_global );

		// Look in local /wp-content/plugins/wpnuke-twitterium/languages/ folder
		} elseif ( file_exists( $mofile_local ) ) {
			return load_textdomain( $this->domain, $mofile_local );
		}

		return false;
	}
}

/**
 * WPNuke Twitterium bootstrap function.
 *
 * The main function responsible for returning the one true WPNuke Twitterium Instance to functions everywhere.
 * Use this function like you would a global variable, except without needing to declare the global.
 *
 * Example: <?php $wpn_twitterium = wpn_twitterium(); ?>
 *
 * @since WPNuke Twitterium 1.0
 *
 * @return The one true WPNuke Twitterium Instance
 */
function wpn_twitterium() {
	return WPNuke_Twitterium::instance();
}
wpn_twitterium();

// Plugin activation hook.
//register_activation_hook(__FILE__, array( 'WPNuke_Twitterium', 'plugin_activation' ));
//register_deactivation_hook( __FILE__, array( 'WPNuke_Twitterium', 'plugin_deactivation' ) );
?>
