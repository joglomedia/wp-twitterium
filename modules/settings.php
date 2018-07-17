<?php
/**
 * WP Twitterium - Settings Panel
 *
 * @package		WP_Twitterium
 * @subpackage	WP_Twitterium/Modules/Settings
 * @copyright	Copyright (c) 2013, MasEDI.Net
 * @license		GNU Public License - http://opensource.org/licenses/gpl-2.0.php
 * @since		1.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WPTwitterium_Settings {

	/**
	 * @var $settings_group The WP Twitterium Setting API group
	 */
	private $settings_group = 'wp_twitterium';

	/**
	 * @var $settings_name The WP Twitterium Setting API name
	 */
	private $settings_name = WPTWT_SETTINGS_NAME;

	/**
	 * Start WPTwitterium Settings.
	 *
	 * @since WP Twitterium 1.0
	 *
	 * @return void
	 */
	public function __construct() {
		//$this->settings_group = 'wp_twitterium';

		if ( ! empty ( $GLOBALS['pagenow'] )
			and ( 'options-general.php' === $GLOBALS['pagenow']
			or 'options.php' === $GLOBALS['pagenow'] )
		)
		{
			add_action( 'admin_init', array( $this, 'register_settings' ) );
			add_filter( 'admin_footer', array( $this, 'register_scripts' ) );
		}

		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_filter( 'plugin_action_links', array( $this, 'plugin_action_links' ), 10, 2 );
	}

	public function WPTwitterium_Settings() {
		$this->__construct();
	}

	/**
	 * add settings page.
	 *
	 * @since WP Twitterium 1.0
	 *
	 * @return void
	 */
	public function add_settings_page() {
		// This page will be under "Settings"
		add_options_page('WP Twitterium', 'WP Twitterium', 'manage_options', 'wp_twitterium', array($this, 'render_settings_page'));
	}

	/**
	 * add plugin action link.
	 *
	 * @since WP Twitterium 1.0
	 *
	 * @return $link text
	 */
	public function plugin_action_links( $links, $file ) {
		if ( $file == plugin_basename( dirname( dirname( __FILE__) ) . '/twitterium.php' ) ) {
			$links[] = '<a href="' . admin_url( 'options-general.php?page=wp_twitterium' ) . '">'.__( 'Settings' ).'</a>';
		}

		return $links;
	}

	/**
	 * init default settings option.
	 *
	 * @since WP Twitterium 1.0
	 *
	 * @return void
	 */
	protected function init_settings() {
		$this->default_settings = apply_filters( 'wptwitterium_settings',
			array(
				'wptwitterium_general' => array(
					__( 'General Settings', 'wp-twitterium' ),
					array(
						array(
							'name'			=> 'wptwitterium_screen_name',
							'type'			=> 'input',
							'std'			=> '',
							'placeholder'	=> '',
							'label'			=> __( 'Screen Name', 'wp-twitterium' ),
							'desc'			=> __( 'Default Twitter username (without \'@\')', 'wp-twitterium' ),
							'attributes'	=> array()
						),
						array(
							'name'			=> 'wptwitterium_tweet_count',
							'std'			=> '10',
							'label'			=> __( 'Maximum Tweet Count', 'wp-twitterium' ),
							'desc'			=> __( 'The maximum tweets to retrieve.', 'wp-twitterium' ),
							'type'			=> 'input',
							'attributes'	=> array(),
							'validation'	=> 'absint'
						),
						array(
							'name'			=> 'wptwitterium_cache_time',
							'std'			=> '1800',
							'label'			=> __( 'Cache Expiration Time', 'wp-twitterium' ),
							'desc'			=> __( 'How long your tweet stored on cache (in second).', 'wp-twitterium' ),
							'type'			=> 'input',
							'attributes'	=> array(),
							'validation'	=> 'absint'
						),
					),
				),
				'wptwitterium_oauth' => array(
					__( 'Twitter API Settings', 'wp-twitterium' ),
					array(
						array(
							'name'       => 'wptwitterium_consumer_key',
							'std'        => '',
							'label'      => __( 'Consumer Key', 'wp-twitterium' ),
							'desc'       => __( 'Your Twitter app consumer key.', 'wp-twitterium' ),
							'type'       => 'input',
							'attributes' => array()
						),
						array(
							'name'       => 'wptwitterium_consumer_secret',
							'std'        => '',
							'label'      => __( 'Consumer Secret', 'wp-twitterium' ),
							'desc'       => __( 'Your Twitter app consumer secret.', 'wp-twitterium' ),
							'type'       => 'input',
							'attributes' => array()
						),
						array(
							'name'       => 'wptwitterium_access_token',
							'std'        => '',
							'label'      => __( 'Access Token', 'wp-twitterium' ),
							'desc'       => __( 'Your Twitter app access token.', 'wp-twitterium' ),
							'type'       => 'input',
							'attributes' => array()
						),
						array(
							'name'       => 'wptwitterium_access_token_secret',
							'std'        => '',
							'label'      => __( 'Access Token Secret', 'wp-twitterium' ),
							'desc'       => __( 'Your Twitter app access token secret.', 'wp-twitterium' ),
							'type'		 => 'input',
							'attributes' => array()
						),
						array(
							'name'		=> 'wptwitterium_access_token_bearer',
							'std'		=> '',
							'label' 	=> __( 'Access Token Bearer', 'wp-twitterium' ),
							'desc'		=> __( 'Your Twitter API token bearer (Optional).', 'wp-twitterium' ),
							'type'      => 'input',
							'attributes' => array()
						),
					)
				),
				'wptwitterium_style' => array(
					__( 'Display Settings', 'wp-twitterium' ),
					array(
						array(
							'name'		=> 'wptwitterium_widget_css_enabled',
							'std'		=> false,
							'label' 	=> __( 'Enable Custom Widget CSS', 'wp-twitterium' ),
							'cb_label'	=> __( 'Check to enable custom widget stylesheet.', 'wp-twitterium' ),
							'desc'		=> '',
							'type'      => 'checkbox'
						),
						array(
							'name'		=> 'wptwitterium_widget_css',
							'std'		=> '',
							'label'		=> __( 'Custom Widget CSS', 'wp-twitterium' ),
							'desc'		=> __( 'Your custom style sheet for Twitterium widget.', 'wp-twitterium' ),
							'type'		=> 'textarea',
							'attributes'=> array(
								'cols'		=> 50,
								'rows'		=> 10,
							),
							'class'		=> 'large-text indent-tab'
						),
					)
				),
			)
		);
	}

	/**
	 * register settings.
	 *
	 * @since WP Twitterium 1.0
	 *
	 * @return void
	 */
	public function register_settings() {
		$this->init_settings();

		// Fetch existing options value.
		$option_values = get_option( $this->settings_name );

		// Setup default options value.
		$default_values = array();

		foreach ( $this->default_settings as $section ) {
			foreach ( $section[1] as $option ) {
				$default_values[ $option['name'] ] = isset( $option['std'] ) ? $option['std'] : '';
			}
		}

		// Parse option values into predefined keys, throw the rest away.
		$values = wp_parse_args( $option_values, $default_values );

		if ( ! $option_values )
			add_option( $this->settings_name, $values );

		register_setting( $this->settings_group, $this->settings_name, array( $this, 'sanitize_settings' ) );
	}

	/**
	 * sanitize & validate settings value.
	 *
	 * @since WP Twitterium 1.0
	 *
	 * @return void
	 */
	public function sanitize_settings( $input ) {
		$this->init_settings();

		$default_values = get_option( $this->settings_name );

		if ( ! is_array( $input ) ) // some bogus data
			return $default_values;

		$new_values = array();

		// Validation process.
		foreach ( $this->default_settings as $section ) {
			foreach ( $section[1] as $option ) {

				if ( isset( $option['validation'] ) ) {

					switch( $option['validation'] ) {

						case 'absint':

							if ( ! is_numeric( $input[ $option['name'] ] ) ) {
								add_settings_error(
									$this->settings_group,
									'value-not-absint',
									'Error: <em>' . $option['name'] . '</em> value must be an integer.'
								);
								$new_values[ $option['name'] ] = $default_values[ $option['name'] ];
							}
							else {
								$new_values[ $option['name'] ] = $input[ $option['name'] ];
							}

						break;

						default:
							$new_values[ $option['name'] ] = $input[ $option['name'] ];
						break;

					}
				} else {
					$new_values[ $option['name'] ] = $input[ $option['name'] ];
				}

			}
		}

		$new_settings = wp_parse_args( $new_values, $default_values );

		return $new_settings;
	}

	/**
	 * Render settings page.
	 *
	 * @since WP Twitterium 1.0
	 *
	 * @return void
	 */
	public function render_settings_page() {
		$this->init_settings();
		?>
		<div class="wrap">

			<h1><?php _e( 'WP Twitterium Settings' , 'wp-twitterium' ); ?></h1>

			<form method="post" action="options.php" enctype="multipart/form-data">
				<?php settings_fields( $this->settings_group ); ?>
				<?php //do_settings_sections( $this->settings_group ); ?>
				<?php screen_icon(); ?>

			    <h2 class="nav-tab-wrapper">
			    	<?php
			    		foreach ( $this->default_settings as $section ) {
			    			echo '<a href="#settings-' . sanitize_title( $section[0] ) . '" class="nav-tab">' . esc_html( $section[0] ) . '</a>';
			    		}
			    	?>
			    </h2>

				<?php
					// Get saved option settings.
					$settings = (array) get_option( $this->settings_name );

					foreach ( $this->default_settings as $section ) {

						echo '<div id="settings-' . sanitize_title( $section[0] ) . '" class="settings_panel">';

						echo '<table class="form-table">';

						foreach ( $section[1] as $option ) {

							$placeholder    = isset( $option['placeholder'] ) ? 'placeholder="' . $option['placeholder'] . '"' : '';
							$class          = isset( $option['class'] ) ? $option['class'] : '';
							$option['std']	= isset( $option['std'] ) ? $option['std'] : '';
							$value          = isset( $settings[ $option['name'] ] ) ? $settings[ $option['name'] ] : '';
							$option['type'] = isset( $option['type'] ) ? $option['type'] : '';
							$attributes     = array();
							if ( isset( $option['attributes'] ) && is_array( $option['attributes'] ) ) {
								foreach ( $option['attributes'] as $attribute_name => $attribute_value ) {
									$attributes[] = esc_attr( $attribute_name ) . '="' . esc_attr( $attribute_value ) . '"';
								}
							}
							// Field options (for select field, etc)
							$field_ops		= array();
							if ( isset( $option['options'] ) )
								$field_ops = is_array( $option['options'] ) ? $option['options'] : array();

							echo '<tr valign="top" class="' . $class . '"><th scope="row"><label for="setting-' . $option['name'] . '">' . $option['label'] . '</a></th><td>';

							switch ( $option['type'] ) {

								case "info" :
									?>
									<p><?php echo $option['desc']; ?></p>
									<?php
								break;

								case "checkbox" :

									?><label><input id="setting-<?php echo $option['name']; ?>" name="<?php echo $this->settings_name . '[' . $option['name'] . ']'; ?>" type="checkbox" value="1" <?php echo implode( ' ', $attributes ); ?> <?php checked( '1', $value ); ?> /> <?php echo $option['cb_label']; ?></label><?php

									if ( $option['desc'] )
										echo ' <p class="description">' . $option['desc'] . '</p>';

								break;

								case "textarea" :

									?><textarea id="setting-<?php echo $option['name']; ?>" name="<?php echo $this->settings_name . '[' . $option['name'] .']'; ?>" <?php echo implode( ' ', $attributes ); ?> <?php echo $placeholder; ?> class="<?php echo (!empty( $class ) ? $class : "large-text"); ?>"><?php echo esc_textarea( $value ); ?></textarea><?php

									if ( $option['desc'] )
										echo ' <p class="description">' . $option['desc'] . '</p>';

								break;

								case "select" :

									?><select id="setting-<?php echo $option['name']; ?>" class="regular-text" name="<?php echo $this->settings_name . '[' . $option['name'] .']'; ?>" <?php echo implode( ' ', $attributes ); ?>><?php
										foreach( $option['options'] as $key => $name )
											echo '<option value="' . esc_attr( $key ) . '" ' . selected( $value, $key, false ) . '>' . esc_html( $name ) . '</option>';
									?></select><?php

									if ( $option['desc'] )
										echo ' <p class="description">' . $option['desc'] . '</p>';

								break;

								case "input":
									?><input id="setting-<?php echo $option['name']; ?>" class="regular-text" type="text" name="<?php echo $this->settings_name . '[' . $option['name'] . ']'; ?>" value="<?php esc_attr_e( $value ); ?>" <?php echo implode( ' ', $attributes ); ?> <?php echo $placeholder; ?> /><?php

									if ( $option['desc'] )
										echo ' <p class="description">' . $option['desc'] . '</p>';

								break;

								case "editor":
									$editor_settings = array(
										'textarea_name' => $option['name'],
										'editor_class' => $class,
										'wpautop' => (isset($field_ops['autop'])) ? $field_ops['autop'] : true
									);
									wp_editor($value, $option['name'], $editor_settings );
								break;

								default:
									// do nothing.
								break;

							}

							echo '</td></tr>';
						}

						echo '</table></div>';

					}
				?>
				<p>
				<?php printf( __('Doesn\'t have Twitter API OAuth key?  Create once %s, it is free.', 'wpnuke'), '<a href="https://dev.twitter.com/apps/" target="_blank">here</a>' ) ?>
				</p>
				<p class="submit">
					<input type="submit" class="button-primary" value="<?php _e( 'Save Changes', 'wp-twitterium' ); ?>" />
				</p>
		    </form>
		</div>
		<?php
	}

	/**
	 * Register Scripts.
	 *
	 * @since WP Twitterium 1.5.0
	 *
	 * @return void
	 */
	function register_scripts() {

		// Frontend scripts should be here...

		if ( !is_admin() )
			return;

		// Admin scripts goes here...
		?>
		<script type="text/javascript">
			jQuery('.nav-tab-wrapper a').click(function() {
				jQuery('.settings_panel').hide();
				jQuery('.nav-tab-active').removeClass('nav-tab-active');
				jQuery( jQuery(this).attr('href') ).show();
				jQuery(this).addClass('nav-tab-active');
				return false;
			});
			jQuery('.nav-tab-wrapper a:first').click();

			/** Tab in Textarea **/
			jQuery(function($) {
				$('textarea.indent-tab').keydown(function(e){
					if( e.keyCode != 9 )
						return;
					e.preventDefault();
					var
					textarea = $(this)[0],
					start = textarea.selectionStart,
					before = textarea.value.substring(0, start),
					after = textarea.value.substring(start, textarea.value.length);
					textarea.value = before + "\t" + after;
					textarea.setSelectionRange(start+1,start+1);
				});
			});
		</script>
		<?php
	}

	/**
	 * Register Styles.
	 *
	 * @since WP Twitterium 1.5.0
	 *
	 * @return void
	 */
	function register_styles() {

		// Frontend styles should be here...

		if ( !is_admin() )
			return;

		// Admin styles goes here...

	}

}

$WPTwitterium_Settings = new WPTwitterium_Settings();
