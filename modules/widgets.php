<?php
/**
 * WP Twitterium - Twitter Widget
 *
 * @package     WPTwitterium
 * @subpackage  WPTwitterium/Widgets
 * @copyright   Copyright (c) 2013, MasEDI.Net
 * @license     GNU Public License - http://opensource.org/licenses/gpl-2.0.php
 * @since       1.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WPTwitterium_Widget extends WP_Widget {

    // Widget Options
    function WPTwitterium_Widget() {
        /* Widget settings. */
        $widget_ops = array(
            'classname' => 'WPTwitterium_Widget',
            'description' => __( 'Use this widget to display your latest tweets.', 'wp-twitterium' )
        );

        /* Widget control settings. */
        $control_ops = array( 'width' => 100, 'height' => 200, 'id_base' => 'wptwitterium_widget' );

        /* Create the widget. */
        $this->WP_Widget( 'WPTwitterium_Widget', __( 'WP Twitterium - Latest Tweets', 'wp-twitterium' ), $widget_ops, $control_ops );
    }

    // Display the Widget
    function widget( $args, $instance ) {
        extract( $args );

        /* User settings. */
        $title          = apply_filters( 'widget_title', $instance['title'] );
        $screen_name    = $instance['screen_name'];
        $count          = $instance['count'];
        $exclude_replies= $instance['exclude_replies'];
        $followtext     = $instance['followtext'];
        $cache_time     = $instance['cache_time'];

        /* Twitter API Settings. */
        $wptwtSettings  = get_option( WPTWT_SETTINGS_NAME );
        $consumer_key   = $wptwtSettings['wptwitterium_consumer_key'];
        $consumer_secret= $wptwtSettings['wptwitterium_consumer_secret'];
        $token          = $wptwtSettings['wptwitterium_access_token'];
        $secret         = $wptwtSettings['wptwitterium_access_token_secret'];
        $bearer         = $wptwtSettings['wptwitterium_access_token_bearer'];

        /* Before widget (defined by themes). */
        echo $before_widget;

        /* Title of widget (before and after defined by themes). */
        if ( $title ) {
            echo $before_title . $title . $after_title;
        }

        /* Display Latest Tweets. */

        // Check whether Twitter API credential is sets.
        if ( ! empty( $consumer_key ) && ! empty( $consumer_secret ) && ! empty( $token ) && ! empty( $secret ) ) {

            // Retrieve cached tweets if exists.
            $cache = get_transient( 'wptwitterium_widget_cache' );

            if ( ! is_array( $cache ) ) {
                $cache = array();
            }

            if ( ! isset( $args['widget_id'] ) ) {
                $args['widget_id'] = $this->id;
            }

            if ( false === $cache || ! isset( $cache[$args['widget_id']] ) || empty( $cache[$args['widget_id']] ) ) {

                /* It wasn't there, so retrieve the tweets data and save to cache. */

                // Prepare tmhOAuth configs.
                $OAConfig = array_merge(
                    array(
                        'consumer_key'      => $consumer_key,
                        'consumer_secret'   => $consumer_secret,
                        'token'             => $token,
                        'secret'            => $secret,
                        'bearer'            => $bearer,
                        'curl_cainfo'       => WPTWT_TMHOAUTH_DIR . 'cacert.pem',
                        'curl_capath'       => WPTWT_TMHOAUTH_DIR,
                        'user_agent'        => WPTWT_USER_AGENT,
                    )
                 );

                // Instantiate new WP Twitterium OAuth object.
                $wptwtWidget = new WPTwitteriumOAuth( $OAConfig );

                // Setup API request parameters.
                $params = array(
                    'screen_name'       => $screen_name,
                    'count'             => $count,
                    'exclude_replies'   => $exclude_replies
                );

                $apiurl = $wptwtWidget->url( '1.1/statuses/user_timeline' );

                // Send request and retrieve response.
                $code = $wptwtWidget->request( 'GET', $apiurl, $params );

                // Validate request response.
                if ( $code == 200 ) {
                    // Retrieve the response body (tweets object)
                    $response = $wptwtWidget->response['response'];

                    // Parse the tweets object
                    $raw_tweets = json_decode( $response );

                    $tweets = array();

                    // Parse and format tweets.
                    foreach ( $raw_tweets as $raw_tweet ) {
                        $tweets[] = $wptwtWidget->parse_tweet($raw_tweet );
                    }
                } else {
                    // Error response.
                    $response = $wptwtWidget->response['response'];
                    $errors = $response->errors;
                    $tweets[] = $errors->message;
                    //$tweets[] = '';
                }

                // Cache parsed tweets data.
                $cache[$args['widget_id']] = $tweets;
                set_transient( 'wptwitterium_widget_cache', $cache, $cache_time );
            }

            // Show tweets.
            echo '<ul id="wptwitterium_widget" class="wptwitterium_widget">';

            foreach ( $cache[$args['widget_id']] as $tweet ) {
                echo '<li class="tweet"><i class="fa fa-twitter"></i> ' . $tweet . '</li>';
            }

            echo '</ul>';

            // Follow button
            /* <iframe data-twttr-rendered="true" title="Twitter Follow Button" style="width: 123px; height: 20px;" class="twitter-follow-button twitter-follow-button" src="http://platform.twitter.com/widgets/follow_button.1371247185.html#_=1372203815418&amp;id=twitter-widget-0&amp;lang=en&amp;screen_name=mymoen&amp;show_count=false&amp;show_screen_name=true&amp;size=m" allowtransparency="true" frameborder="0" scrolling="no"></iframe>
            */
            echo '<div class="twitterium-follow-button"><a href="https://twitter.com/' . $screen_name . '" class="twitter-follow-button" data-show-count="false" data-size="large">' . $followtext . '</a>';
            echo "<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');</script>";
            echo '</div>';
        }
        else {
            _e( '<p>Please configure your WP Twitterium Widget from admin dashboard.</p>', 'wp-twitterium' );
        }

        /* After widget (defined by themes). */
        echo $after_widget;
    }

    // Update and save widget
    function update( $new_instance, $old_instance ) {
        $instance = $old_instance;

        $instance['title']          = strip_tags( $new_instance['title'] );
        $instance['screen_name']    = strip_tags( $new_instance['screen_name'] );
        $instance['count']          = absint( $new_instance['count'] );
        $instance['followtext']     = strip_tags( $new_instance['followtext'] );
        $instance['cache_time']     = absint( $new_instance['cache_time'] );
        $instance['delete_cache']   = isset( $new_instance['delete_cache'] ) ? (bool) $new_instance['delete_cache'] : false;
        $instance['exclude_replies']= isset( $new_instance['exclude_replies'] ) ? (bool) $new_instance['exclude_replies'] : false;

        // Widget setting updated, also update the saved data (delete or keep recent cache)
        if ( $instance['delete_cache'] ) {
            $this->flush_widget_cache();
        }

        return $instance;
    }

    // Flush widget cache
    function flush_widget_cache() {
        delete_transient( 'wptwitterium_widget_cache' );
    }

    // Widget settings
    function form( $instance ) {

        /** Set up some default widget settings. **/
        $twitterium_settings = get_option(WPTWT_SETTINGS_NAME );

        $defaults = array(
            'title'         => __( 'Latest Tweets', 'wp-twitterium' ),
            'screen_name'   => $twitterium_settings['wptwitterium_screen_name'],
            'count'         => $twitterium_settings['wptwitterium_tweet_count'],
            'followtext'    => sprintf(__( '%s on Twitter', 'wp-twitterium' ), $twitterium_settings['wptwitterium_screen_name'] ),
            'cache_time'    => $twitterium_settings['wptwitterium_cache_time'], // Cache cache_time time, in seconds
        );

        $instance = wp_parse_args( (array) $instance, $defaults );

        $exclude_replies = isset( $instance['exclude_replies'] ) ? (bool) $instance['exclude_replies'] : false;
        $delete_cache = isset( $instance['delete_cache'] ) ? (bool) $instance['delete_cache'] : false;
        ?>

        <!-- Text Input -->
        <p style="margin-bottom:5px;font-weight:bold;">
            <label><?php _e( 'General Settings:', 'wp-twitterium' ); ?></label>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'wp-twitterium' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" type="text" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'screen_name' ); ?>"><?php _e( 'Twitter screen name (username):', 'wp-twitterium' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'screen_name' ); ?>" name="<?php echo $this->get_field_name( 'screen_name' ); ?>" value="<?php echo $instance['screen_name']; ?>" type="text" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'count' ); ?>"><?php _e( 'Number of tweets to show: ', 'wp-twitterium' ); ?></label>
            <input id="<?php echo $this->get_field_id( 'count' ); ?>" name="<?php echo $this->get_field_name( 'count' ); ?>" value="<?php echo $instance['count']; ?>" value="5" size="3" type="text" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'followtext' ); ?>"><?php _e( 'Follow Me text to show:', 'wp-twitterium' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'followtext' ); ?>" name="<?php echo $this->get_field_name( 'followtext' ); ?>" value="<?php echo $instance['followtext']; ?>" type="text" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'cache_time' ); ?>"><?php _e( 'Cache expiration time (in second):', 'wp-twitterium' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'cache_time' ); ?>" name="<?php echo $this->get_field_name( 'cache_time' ); ?>" value="<?php echo $instance['cache_time']; ?>" type="text" />
            <span><small><?php _e( 'WPTwitterium Widget uses caching system to store tweets data. This is useful to reduce the number of API request and server load.', 'wp-twitterium' ); ?></small></span>
        </p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked($exclude_replies ); ?> id="<?php echo $this->get_field_id( 'exclude_replies' ); ?>" name="<?php echo $this->get_field_name( 'exclude_replies' ); ?>" />
            <label for="<?php echo $this->get_field_id( 'exclude_replies' ); ?>"><?php _e( 'Exclude replies?', 'wp-twitterium' ); ?></label>
        </p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked($delete_cache ); ?> id="<?php echo $this->get_field_id( 'delete_cache' ); ?>" name="<?php echo $this->get_field_name( 'delete_cache' ); ?>" />
            <label for="<?php echo $this->get_field_id( 'delete_cache' ); ?>"><?php _e( 'Delete cache on save setting?', 'wp-twitterium' ); ?></label>
        </p>
    <?php
    }

}

// Register widgets
function register_widgets() {
    register_widget( 'WPTwitterium_Widget' );
}
add_action( 'widgets_init', 'register_widgets' );
