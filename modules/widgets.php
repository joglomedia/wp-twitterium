<?php
/**
 * WPNuke Twitterium - Twitter Widget
 *
 * @package	WPNuke
 * @subpackage	WPNuke_Twitterium/Widgets
 * @copyright	Copyright (c) 2013, MasEDI.Net
 * @license	GNU Public License - http://opensource.org/licenses/gpl-2.0.php 
 * @since	1.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class wpnt_tweets_widget extends WP_Widget {
	function wpnt_tweets_widget() {
		/* Widget settings. */
		$widget_ops = array( 'classname' => 'wpnt_tweets_widget', 'description' => __('A widget that displays your latest tweets.', 'wpnt') );
		/* Widget control settings. */
		$control_ops = array( 'width' => 100, 'height' => 200, 'id_base' => 'wpnt_tweets_widget' );
		/* Create the widget. */
		$this->WP_Widget( 'wpnt_tweets_widget', __('WPNuke Twitterium - Latest Tweets', 'wpnt'), $widget_ops, $control_ops );
	}

	// Display the Widget
	function widget( $args, $instance ) {
		extract( $args );

		/* User settings. */
		$title			= apply_filters('widget_title', $instance['title'] );
		$consumer_key		= $instance['consumer_key'];
		$consumer_secret	= $instance['consumer_secret'];
		$token			= $instance['token'];
		$secret			= $instance['secret'];
		$bearer			= $instance['bearer'];
		$screen_name		= $instance['screen_name'];
		$count			= $instance['count'];
		$exclude_replies	= $instance['exclude_replies'];
		$followtext		= $instance['followtext'];
		$cache_time		= $instance['cache_time'];

		/* Before widget (defined by themes). */
		echo $before_widget;

		/* Title of widget (before and after defined by themes). */
		if ( $title ) {
			echo $before_title . $title . $after_title;
		}

		/* Display Latest Tweets. */
		
		global $wp_version;
		
		// Check if Twitter API credential is sets.
		if ( !empty( $consumer_key ) && !empty( $consumer_secret ) && !empty( $token ) && !empty( $secret ) ) {
			
			/** Add WP Transient Implementation **/
			
			// Check if cached tweets exists.
			$tweets = get_transient( 'wpnt_cache_' . $screen_name );
			if ( false === $tweets ) {
				
				// It wasn't there, so regenerate the tweets data and save the transient.
				
				// Prepare tmhOAuth configs.
				$OAconfig = array_merge(
					array(
						'consumer_key'		=> $consumer_key,
						'consumer_secret'	=> $consumer_secret,
						'token'			=> $token,
						'secret'		=> $secret,
						'bearer'		=> $bearer,
						'curl_cainfo'		=> WPNT_TMHOAUTH_DIR . 'cacert.pem',
						'curl_capath'		=> WPNT_TMHOAUTH_DIR,
						'user_agent'		=> WPNT_USER_AGENT,
					)
				);

				// Crate a new instance.
				$wpnt_widget = new WPNT_OAuth( $OAconfig );
				
				// Setup API request parameters.
				$params = array( 
					'screen_name'		=> $screen_name,
					'count'			=> $count,
					'exclude_replies'	=> $exclude_replies
				);
				$apiurl = $wpnt_widget->url('1.1/statuses/user_timeline');
				
				// Send request and retrieve response.
				$code = $wpnt_widget->request( 'GET', $apiurl, $params );
				
				// Validate request response.
				if ( $code == 200 ) {
				
					// Retrieve the response body (tweets object)
					$response = $wpnt_widget->response['response'];
					
					// Parse the tweets object
					$raw_tweets = json_decode( $response );
				
					$tweets = array();
					foreach( $raw_tweets as $raw_tweet ) {
						/* $date = $tweet->created_at;
						$text = $tweet->text;
						$user_mentions = $tweet->entities->user_mentions; */
						
						// Parse and format tweet, than add to array.
						$tweets[] = $wpnt_widget->parse_tweet( $raw_tweet );
					}
					
				} else {
					// Error response.
					//$response = $wpnt_widget->response['response'];
					//$errors = $response->errors;
					//$tweets[] = $errors->message;
					$tweets[] = $wpnt_widget->response['response'];
				}
				
				// Save parsed tweets data.
				set_transient( 'wpnt_cache_' . $screen_name, $tweets, $cache_time );
			}
			
			// Show tweet.
			echo '<ul id="wpnt_tweet_widget" class="wpnt_tweet_widget">';

			foreach( $tweets as $tweet ) {
				echo '<li>' . $tweet . '</li>';
			}
			
			echo '</ul>';
			
		}
		else {
			_e('<p>Please configure your WPNuke Tweets Widget via admin dashboard.</p>', 'wpnt');
		}

		/* After widget (defined by themes). */
		echo $after_widget;
	}

	// Update and save widget
	function update( $new_instance, $old_instance ) {
		$instance			= $old_instance;
		$instance['title']		= strip_tags( $new_instance['title'] );
		$instance['consumer_key']	= strip_tags( $new_instance['consumer_key'] );
		$instance['consumer_secret']	= strip_tags( $new_instance['consumer_secret'] );
		$instance['token']		= strip_tags( $new_instance['token'] );
		$instance['secret']		= strip_tags( $new_instance['secret'] );
		$instance['bearer']		= strip_tags( $new_instance['bearer'] );
		$instance['screen_name']	= strip_tags( $new_instance['screen_name'] );
		$instance['count']		= strip_tags( $new_instance['count'] );
		$instance['exclude_replies']	= strip_tags( $new_instance['exclude_replies'] );
		$instance['followtext']		= strip_tags( $new_instance['followtext'] );
		$instance['cache_time']		= strip_tags( $new_instance['cache_time'] );
		
		// Widget setting updated, also update the saved data (delete transient)
		delete_transient( 'wpnt_cache_' . $instance['screen_name'] );

		return $instance;
	}
	
	// Widget settings
	function form( $instance ) {

		/** Set up some default widget settings.
		retrieve default WPNuke Twitterium settings **/

		$defaults = array(
		'title'			=> 'Latest Tweets',
		'consumer_key'		=> get_option( 'wpnt_consumer_key' ),
		'consumer_secret'	=> get_option( 'wpnt_consumer_secret' ),
		'token'			=> get_option( 'wpnt_access_token' ),
		'secret'		=> get_option( 'wpnt_access_token_secret' ),
		'bearer'		=> '',
		'screen_name'		=> get_option( 'wpnt_screen_name' ),
		'count'			=> get_option( 'wpnt_tweet_count' ),
		'exclude_replies'	=> 'false',
		'followtext'		=> 'WPNuke on Twitter',
		'cache_time'		=> get_option( 'wpnt_cache_time' ), // Cache cache_time time, in seconds
		);
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>

		<!-- Text Input -->
		<p style="margin-bottom:5px;font-weight:bold;">
			<label><?php _e('General Settings:', 'wpnt'); ?></label>
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title:', 'wpnt'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" type="text" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'screen_name' ); ?>"><?php _e('Twitter screen name (username):', 'wpnt'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'screen_name' ); ?>" name="<?php echo $this->get_field_name( 'screen_name' ); ?>" value="<?php echo $instance['screen_name']; ?>" type="text" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'count' ); ?>"><?php _e('Number of tweets to show: ', 'wpnt'); ?></label>
			<input id="<?php echo $this->get_field_id( 'count' ); ?>" name="<?php echo $this->get_field_name( 'count' ); ?>" value="<?php echo $instance['count']; ?>" value="5" size="3" type="text" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'exclude_replies' ); ?>"><?php _e('Exclude replies?', 'wpnt'); ?></label>
			<select id="<?php echo $this->get_field_id( 'exclude_replies' ); ?>" name="<?php echo $this->get_field_name( 'exclude_replies' ); ?>">
			<?php
				$list = '';
				$options = array( 'false', 'true' );
				foreach ( $options as $option ) {
					if( $option == $instance['exclude_replies'] ) {
						$selected = 'selected="selected"';
					} else { $selected = ''; }
					$list .= '<option ' . $selected . ' value="' . $option . '">' . ( ( $option == 'true' ) ? 'Yes' : 'No' ) . '</option>';
				}
				echo $list;
			?>
			</select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'followtext' ); ?>"><?php _e('Follow Me text to show:', 'wpnt'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'followtext' ); ?>" name="<?php echo $this->get_field_name( 'followtext' ); ?>" value="<?php echo $instance['followtext']; ?>" type="text" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'cache_time' ); ?>"><?php _e('Cache cache_time time (in second):', 'wpnt'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'cache_time' ); ?>" name="<?php echo $this->get_field_name( 'cache_time' ); ?>" value="<?php echo $instance['cache_time']; ?>" type="text" />
			<span><small><?php _e('WPNuke Twitter Widget uses caching system to store tweets data. This is useful to reduce the number of API request and server load.', 'wpnt'); ?></small></span>
		</p>
		
		<p style="margin-bottom:5px;font-weight:bold;">
			<label><?php _e('Twitter App API Settings:', 'wpnt'); ?></label>
			<p><?php printf( __('To create an app, visit %s', 'wpnt'), '<a href="https://dev.twitter.com/apps/" target="_blank">here</a>' ); ?></p>
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id( 'consumer_key' ); ?>"><?php _e('Consumer Key:', 'wpnt'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'consumer_key' ); ?>" name="<?php echo $this->get_field_name( 'consumer_key' ); ?>" value="<?php echo $instance['consumer_key']; ?>" type="text" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'consumer_secret' ); ?>"><?php _e('Consumer Secret:', 'wpnt'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'consumer_secret' ); ?>" name="<?php echo $this->get_field_name( 'consumer_secret' ); ?>" value="<?php echo $instance['consumer_secret']; ?>" type="text" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'token' ); ?>"><?php _e('Access Token:', 'wpnt'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'token' ); ?>" name="<?php echo $this->get_field_name( 'token' ); ?>" value="<?php echo $instance['token']; ?>" type="text" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'secret' ); ?>"><?php _e('Access Token Secret:', 'wpnt'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'secret' ); ?>" name="<?php echo $this->get_field_name( 'secret' ); ?>" value="<?php echo $instance['secret']; ?>" type="text" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'bearer' ); ?>"><?php _e('Access Token Bearer (optional):', 'wpnt'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'bearer' ); ?>" name="<?php echo $this->get_field_name( 'bearer' ); ?>" value="<?php echo $instance['bearer']; ?>" type="text" />
			<span><small><?php _e('Your OAuth bearer token should already be URL encoded.', 'wpnt'); ?></small></span>
		</p>
		
	<?php
	}
}

/** Function Helper **/

// Register widgets
function register_widgets() {
	register_widget( 'wpnt_tweets_widget' );
}
add_action( 'widgets_init', 'register_widgets' );
?>
