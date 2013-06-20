<?php
/**
 * WPNuke Twitterium - WPNT OAuth
 *
 * @package		WPNuke
 * @subpackage	WPNuke_Twitterium/OAuth
 * @copyright	Copyright (c) 2013, MasEDI.Net
 * @license		GNU Public License - http://opensource.org/licenses/gpl-2.0.php 
 * @since		1.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WPNuke Twitterium OAuth requires tmhOAuth Library by @themattharis
 */
//include_once( WPNUKE_INC_DIR . '/wpnuke-twitterium/includes/tmhOAuth/tmhOAuth.php' );

if ( class_exists( 'tmhOAuth' ) ) {

class WPNT_OAuth extends tmhOAuth {

	// some configurable variable, you need to create new Twitter app to get variable below
/* 	protected $consumer_key = 'YOUR_CONSUMER_KEY';
	protected $consumer_secret = 'YOUR_CONSUMER_SECRET';
	protected $token = 'A_USER_TOKEN';
	protected $secret = 'A_USER_SECRET';
	protected $bearer = 'YOUR_OAUTH2_TOKEN';
 */	
	public function __construct($config = array()) {

		$this->config = array_merge(
			array(
				// change the values below to ones for your application
				'consumer_key'		=> '',
				'consumer_secret'	=> '',
				'token'				=> '',
				'secret'			=> '',
				'bearer'			=> '',
				'curl_cainfo'		=> WPNT_TMHOAUTH_DIR . 'cacert.pem',
				'curl_capath'		=> WPNT_TMHOAUTH_DIR,
				'user_agent'		=> WPNT_USER_AGENT,
			),
			$config
		);

		parent::__construct($this->config);
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
		
		if ( !is_object( $tweet ) ) return false;
		
		// Convert tweet text to array of one-character strings
		$characters = str_split($tweet->text);

		// Insert starting and closing link tags at indices...

		// For @user_mentions
		$user_mentions = $tweet->entities->user_mentions;
		if ( !empty( $user_mentions ) ) {
			foreach ( $user_mentions as $entity ) {
				$link = "https://twitter.com/" . $entity->screen_name;          
				$characters[$entity->indices[0]] = "<a href=\"$link\" target=\"_blank\" rel=\"nofollow\">" . $characters[$entity->indices[0]];
				$characters[$entity->indices[1] - 1] .= "</a>";         
			}               
		}
		
		// For #hashtags
		$hashtags = $tweet->entities->hashtags;
		if ( !empty( $hashtags ) ) {
			foreach ( $hashtags as $entity ) {
				$link = "https://twitter.com/search?src=hash&amp;q=%23" . $entity->text;         
				$characters[$entity->indices[0]] = "<a href=\"$link\" target=\"_blank\" rel=\"nofollow\">" . $characters[$entity->indices[0]];
				$characters[$entity->indices[1] - 1] .= "</a>";
			}
		}

		// For url link
		$urls = $tweet->entities->urls;
		if ( !empty( $urls ) ) {
			foreach ( $urls as $entity ) {
				$link = $entity->expanded_url;          
				$characters[$entity->indices[0]] = "<a href=\"$link\" target=\"_blank\" rel=\"nofollow\">" . $characters[$entity->indices[0]];
				$characters[$entity->indices[1] - 1] .= "</a>";         
			}
		}

		// For media
		$media = isset( $tweet->entities->media ) ? $tweet->entities->media : array();
		if ( !empty( $media ) ) {
			foreach ( $media as $entity ) {
				$link = $entity->expanded_url;          
				$characters[$entity->indices[0]] = "<a href=\"$link\" target=\"_blank\" rel=\"nofollow\">" . $characters[$entity->indices[0]];
				$characters[$entity->indices[1] - 1] .= "</a>";         
			}
		}

		// Convert array back to string
		$formatted_tweet = implode('', $characters);
		
		return $formatted_tweet;
	}
}

}

?>