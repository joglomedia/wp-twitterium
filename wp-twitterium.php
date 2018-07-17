<?php
/**
 * Plugin Name: WP Twitterium
 * Plugin URI: http://x.eslabs.id/wordpress-plugin-wp-twitterium.html
 * Description: A super simple WordPress plugin to handles twitter feeds, including parsing @username, #hashtag, and URLs into links. Add latest tweet widgets and shortcodes to WordPress. Thanks to @themattharis for his <a href="https://github.com/themattharris/tmhOAuth">tmhOAuth</a> lib.
 * Author: MasEDI
 * Author URI: http://x.eslabs.id/
 * Version: 1.6.0
 * Text Domain: twitterium
 * License: GPL2 or later
 * Domain Path: /
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Main WP Twitterium Class
 *
 * @since WP Twitterium 1.0
 */
final class WPTwitterium {

    /**
     * @var $instance The one true WPTwitterium
     */
    private static $instance;

    /**
     * Main WP Twitterium Instance.
     *
     * Ensures that only one instance of WP Twitterium exists in memory at any one time.
     * Also prevents needing to define globals all over the place.
     *
     * @since WP Twitterium 1.0
     *
     * @return The one true WP Twitterium
     */
    public static function instance() {
        if ( ! isset ( self::$instance ) ) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * Start your engines.
     *
     * @since WP Twitterium 1.0
     *
     * @return void
     */
    public function __construct() {
        $this->setup_globals();
        $this->include_files();
        $this->setup_hooks();

        // Plugin activation hook.
        //register_activation_hook(basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ ), array( $this, 'plugin_activation' ));
    }

    /** Private Methods **/

    /**
     * Set some smart defaults to class variables.
     * Allow some of them to be filtered to allow for early overriding.
     *
     * @since WP Twitterium 1.0
     *
     * @return void
     */
    private function setup_globals() {
        /** Versions **/

        $this->version      = '1.5.1';
        $this->db_version   = '1';

        /** Paths **/

        $this->file         = __FILE__;
        $this->basename     = apply_filters( 'wptwitterium_plugin_basenname', plugin_basename( $this->file ) );
        $this->plugin_dir   = apply_filters( 'wptwitterium_plugin_dir_path', plugin_dir_path( $this->file ) );
        $this->plugin_url   = apply_filters( 'wptwitterium_plugin_dir_url', plugin_dir_url( $this->file ) );

        // Includes dir.
        $this->includes_dir = apply_filters( 'wptwitterium_includes_dir', trailingslashit( $this->plugin_dir . 'includes' ) );
        $this->includes_url = apply_filters( 'wptwitterium_includes_url', trailingslashit( $this->plugin_url . 'includes' ) );

        // Modules dir.
        $this->modules_dir  = apply_filters( 'wptwitterium_modules_dir', trailingslashit( $this->plugin_dir . 'modules' ) );
        $this->modules_url  = apply_filters( 'wptwitterium_modules_url', trailingslashit( $this->plugin_url . 'modules' ) );

        // Languages dir.
        $this->lang_dir     = apply_filters( 'wptwitterium_lang_dir', trailingslashit( $this->plugin_dir . 'languages' ) );

        /** Misc **/

        // Plugin domain.
        $this->domain       = 'wp-twitterium';

        // tmhOAuth lib dir.
        $this->tmhoauth_dir = apply_filters( 'wptwitterium_includes_dir', trailingslashit( $this->includes_dir . 'tmhOAuth' ) );

        /** Constants. For used outside of this class. **/

        // Plugin dir.
        define( 'WPTWT_PLUGIN_DIR', $this->plugin_dir );

        // Includes dir.
        define( 'WPTWT_INCLUDES_DIR', $this->includes_dir );
        define( 'WPTWT_INCLUDES_URL', $this->includes_url );

        // Modules dir.
        define( 'WPTWT_MODULES_DIR', $this->includes_dir );
        define( 'WPTWT_MODULES_URL', $this->includes_url );

        // tmhOAuth dir.
        define( 'WPTWT_TMHOAUTH_DIR', $this->tmhoauth_dir );

        // User agent.
        define( 'WPTWT_USER_AGENT', 'WordPress/' . get_bloginfo( 'version' ) . ' (WPTwitterium ' . $this->version . ')' );

        // Plugin settings name.
        define( 'WPTWT_SETTINGS_NAME', 'wptwitterium_settings' );

        // Some default settings value.
        define( 'WPTWT_SCREEN_NAME', 'joglomedia' );
        define( 'WPTWT_TWEET_COUNT', 10 );
        define( 'WPTWT_CACHE_TIME', 600 );
        define( 'WPTWT_FOLLOWME_TEXT', __( 'Follow Me on Twitter', 'wp-twitterium' ) );
    }

    /**
     * Include required files.
     *
     * @since WP Twitterium 1.0
     *
     * @return void
     */
    private function include_files() {
        // Load library files.
        include_once( $this->tmhoauth_dir . 'tmhOAuth.php' );
        include_once( $this->includes_dir . 'TwitterTextFormatter.php' );
        include_once( $this->includes_dir . 'twitteriumOAuth.php' );

        // Load available module.
        $this->load_modules();

        do_action( 'wptwitterium_include_files' );

        if ( ! is_admin() ) {
            return;
        }

        do_action( 'wptwitterium_include_admin_files' );
    }

    /**
     * Setup the default hooks (actions and filters)
     *
     * @since WP Twitterium 1.0
     *
     * @return void
     */
    private function setup_hooks() {
        add_action( 'wp_head', array( $this, 'custom_scripts_styles' ) );

        do_action( 'wptwitterium_hooks' );

        if ( ! is_admin() ) {
            return;
        }

        add_action( 'admin_init', array( $this, 'is_curl_loaded' ), 1 );

        do_action( 'wptwitterium_admin_hooks' );

        $this->load_textdomain();
    }

    /**
     * Check PHP Curl module.
     *
     * @since WP Twitterium 1.0
     *
     * @return void
     */
    public function is_curl_loaded() {
        if ( !function_exists( 'curl_init' ) || !in_array( 'curl', get_loaded_extensions() ) ) {
            if ( is_plugin_active( $this->basename ) ) {
                deactivate_plugins( $this->basename );
                unset( $_GET[ 'activate' ] ); // Ghetto

                add_action( 'admin_notices', array( $this, 'curl_error_notice' ) );
            }
        }
    }

    /**
     * Admin notice.
     *
     * @since WP Twitterium 1.0
     *
     * @return void
     */
    public function curl_error_notice() {
    ?>
        <div class="updated">
            <p>
            <?php
                printf(
                    __( '<strong>Notice:</strong> in order to function properly, WP Twitterium requires <em>cURL module</em> enabled on your server side. If you need web hosting with cURL module enabled <a href="%s" target="_blank">please check here</a>!', 'wp-twitterium' ), 'https://my.hawkhost.com/aff.php?aff=1594'
                );
            ?>
            </p>
        </div>
    <?php
    }

    /**
     * Load any existing modules that allow this plugin to work alongside other plugins.
     *
     * @since WP Twitterium 1.0
     *
     * @return void
     */
    public function load_modules() {
        $dir = $this->modules_dir;
        if ( $handle = opendir( $dir ) ) {
            while ( false !== ( $filename = readdir( $handle ) ) ) {
                $file = $dir . $filename;
                if ( @is_file( $file ) && preg_match( '/\.php$/', $file ) ) {
                    include_once( $file );
                }
            }
            closedir( $handle );
        }
    }

    /**
     * Loads the plugin language files.
     *
     * @since WP Twitterium 1.0
     *
     * @return mix
     */
    public function load_textdomain() {
        // Traditional WordPress plugin locale filter.
        $locale = apply_filters( 'plugin_locale', get_locale(), $this->domain );
        $mofile = sprintf( '%1$s-%2$s.mo', $this->domain, $locale );

        // Setup paths to current locale file.
        $mofile_local  = $this->lang_dir . $mofile;
        $mofile_global = WP_LANG_DIR . '/' . $this->domain . '/' . $mofile;

        // Look in global /wp-content/languages/twitterium folder.
        if ( file_exists( $mofile_global ) ) {
            return load_textdomain( $this->domain, $mofile_global );

        // Look in local /wp-content/plugins/wp_twitterium/languages/ folder.
        } elseif ( file_exists( $mofile_local ) ) {
            return load_textdomain( $this->domain, $mofile_local );
        }

        return false;
    }

    public function custom_scripts_styles()
    {
        $settings = get_option( WPTWT_SETTINGS_NAME );

        $stylesheet = "
            ul.wptwitterium_widget li {
                list-style: none;
                margin-bottom: 1em;
                line-height: 1.41575em;
            }
        ";

        $custom_style = '<style type="text/css">';

        if ( $settings['wptwitterium_widget_css_enabled'] == 1 ) {
            $custom_style .= ! empty( $settings['wptwitterium_widget_css'] ) ? $settings['wptwitterium_widget_css'] : '';
        } else {
            $custom_style .= $stylesheet;
        }

        $custom_style .= '</style>' . "\r\n";

        echo $custom_style;
    }
}

/**
 * WP Twitterium bootstrap function.
 *
 * The main function responsible for returning the one true WP Twitterium instance to functions everywhere.
 * Use this function like you would a global variable, except without needing to declare the global.
 * Example: <?php $wp_twitterium = init_twitterium(); ?>
 *
 * @since WP Twitterium 1.0
 *
 * @return The one true WP Twitterium instance
 */
function init_wptwitterium() {
    return WPTwitterium::instance();
}

$wptwitterium = init_wptwitterium();
