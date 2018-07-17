<?php
/**
 * WP Twitterium - OAuth
 *
 * @package     Twitterium
 * @subpackage  Twitterium/OAuth
 * @copyright   Copyright (c) 2013, MasEDI.Net
 * @license     GNU Public License - http://opensource.org/licenses/gpl-2.0.php
 * @since       1.0
 */

// Use the class TwitterTextFormatter
use Netgloo\TwitterTextFormatter;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WP Twitterium OAuth requires tmhOAuth Library by @themattharis
 */
//include_once( WPNUKE_INC_DIR . '/wp_twitterium/includes/tmhOAuth/tmhOAuth.php' );

if ( class_exists( 'tmhOAuth' ) ) :

class WPTwitteriumOAuth extends tmhOAuth {

    /**
     * Some configurable variable, you need to create new Twitter app to get variable below
     * protected $consumer_key = 'YOUR_CONSUMER_KEY';
     * protected $consumer_secret = 'YOUR_CONSUMER_SECRET';
     * protected $token = 'A_USER_TOKEN';
     * protected $secret = 'A_USER_SECRET';
     * protected $bearer = 'YOUR_OAUTH2_TOKEN';
     */
    public function __construct( $config = array() ) {

        $this->config = array_merge(
            array(
                // change the values below to ones for your application
                'consumer_key'      => '',
                'consumer_secret'   => '',
                'token'             => '',
                'secret'            => '',
                'bearer'            => '',
                'curl_cainfo'       => WPTWT_TMHOAUTH_DIR . 'cacert.pem',
                'curl_capath'       => WPTWT_TMHOAUTH_DIR,
                'user_agent'        => WPTWT_USER_AGENT,
            ),
            $config
        );

        parent::__construct( $this->config );
    }

    public function get_latest_tweets( $params = array() ) {
        // no implementation yet
    }

    /**
     * Parse single tweet object.
     *
     * Ref:
     * - http://stackoverflow.com/a/15306910
     *
     * @param object $tweet object
     * @return string $formatted_tweet
     */
    public function parse_tweet( &$tweet ) {
        $formatted_tweet = TwitterTextFormatter::format_text( $tweet );
        return $formatted_tweet;
    }
}

endif;
