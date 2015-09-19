<?php
/**
 * Plugin Name: BadgeOS Gravity Forms Add-On
 * Plugin URI: http://www.bethadele.com/badgeos-gravityforms
 * Description: This BadgeOS add-on integrates BadgeOS features with Gravity Forms
 * Tags: badgeos, gravityforms
 * Author: Beth Adele Long
 * Version: 0.0.1
 * Author URI: https://bethadele.com/
 * License:
 * License URI:
 */

class BadgeOS_GravityForms {

	/**
	 * Plugin Basename
	 *
	 * @var string
	 */
	public $basename = '';

	/**
	 * Plugin Directory Path
	 *
	 * @var string
	 */
	public $directory_path = '';

	/**
	 * Plugin Directory URL
	 *
	 * @var string
	 */
	public $directory_url = '';

	/**
	 * BadgeOS LearnDash Triggers
	 *
	 * @var array
	 */
	public $triggers = array();

	/**
	 * Actions to forward for splitting an action up
	 *
	 * @var array
	 */
	public $actions = array();

	/**
	 *
	 */
	function __construct() {

		// Define plugin constants
		$this->basename = plugin_basename( __FILE__ );
		$this->directory_path = plugin_dir_path( __FILE__ );
		$this->directory_url = plugin_dir_url( __FILE__ );

		// If BadgeOS is unavailable, deactivate our plugin
		add_action( 'admin_notices', array( $this, 'maybe_disable_plugin' ) );

		// Gravity Forms Action Hooks
		$this->triggers = array(
			'gform_after_submission' => __('Complete a GF form', 'badgeos-gravityforms')
			//'badgeos_gravityforms_form_completed_specific' => __( 'Complete a GF form with specific field data', 'badgeos-gravityforms' )
			// 'learndash_course_completed' => __( 'Completed Course', 'badgeos-learndash' )
		);

    add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ), 11 );

	}

	/**
	 * Check if BadgeOS is available
	 *
	 * @since  1.0.0
	 * @return bool True if BadgeOS and Gravity Forms are available, false otherwise
	 */
	public static function meets_requirements() {

		if ( !class_exists( 'BadgeOS' ) || !function_exists( 'badgeos_get_user_earned_achievement_types' ) ) {
			return false;
		}
		elseif ( !class_exists( 'GFForms' ) ) {
			return false;
		}

		return true;

	}

	/**
	 * Generate a custom error message and deactivates the plugin if we don't meet requirements
	 *
	 * @since 1.0.0
	 */
	public function maybe_disable_plugin() {

		if ( !$this->meets_requirements() ) {
			// Display our error
			echo '<div id="message" class="error">';

			if ( !class_exists( 'BadgeOS' ) || !function_exists( 'badgeos_get_user_earned_achievement_types' ) ) {
				echo '<p>' . sprintf( __( 'BadgeOS Gravity Forms Add-On requires BadgeOS and has been <a href="%s">deactivated</a>. Please install and activate BadgeOS and then reactivate this plugin.', 'badgeos-gravityforms' ), admin_url( 'plugins.php' ) ) . '</p>';
			}

			if ( !class_exists( 'GFForms' ) ) {
				echo '<p>' . sprintf( __( 'BadgeOS Gravity Forms Add-On requires Gravity Forms and has been <a href="%s">deactivated</a>. Please install and activate Gravity Forms and then reactivate this plugin.', 'badgeos-gravityforms' ), admin_url( 'plugins.php' ) ) . '</p>';
			}

			echo '</div>';

			// Deactivate our plugin
			deactivate_plugins( $this->basename );
		}
	}

	/**
	 * Load the plugin textdomain and include files if plugin meets requirements
	 *
	 * @since 1.0.0
	 */
	public function plugins_loaded() {
		// Load translations
		// load_plugin_textdomain( 'badgeos-gravityforms', false, dirname( $this->basename ) . '/languages/' );

		if ( $this->meets_requirements() ) {
			require_once( $this->directory_path . '/includes/rules-engine.php' );
			require_once( $this->directory_path . '/includes/steps-ui.php' );
			require_once( $this->directory_path . '/includes/activity.php' );

			$this->action_forwarding();
		}
	}

	/**
	 * Forward WP actions into a new set of actions
	 *
	 * @since 1.0.0
	 */
	public function action_forwarding() {
		foreach ( $this->actions as $action => $args ) {
			$priority = 10;
			$accepted_args = 20;

			if ( is_array( $args ) ) {
				if ( isset( $args[ 'priority' ] ) ) {
					$priority = $args[ 'priority' ];
				}

				if ( isset( $args[ 'accepted_args' ] ) ) {
					$accepted_args = $args[ 'accepted_args' ];
				}
			}

			add_action( $action, array( $this, 'action_forward' ), $priority, $accepted_args );
		}
	}

	/**
	 * Forward a specific WP action into a new set of actions
	 *
	 * @return mixed Action return
	 *
	 * @since 1.0.0
	 */
	public function action_forward() {
		$action = current_filter();
		$args = func_get_args();

		if ( isset( $this->actions[ $action ] ) ) {
			if ( is_array( $this->actions[ $action ] )
				 && isset( $this->actions[ $action ][ 'actions' ] ) && is_array( $this->actions[ $action ][ 'actions' ] )
				 && !empty( $this->actions[ $action ][ 'actions' ] ) ) {
				foreach ( $this->actions[ $action ][ 'actions' ] as $new_action ) {
					if ( 0 !== strpos( $new_action, strtolower( __CLASS__ ) . '_' ) ) {
						$new_action = strtolower( __CLASS__ ) . '_' . $new_action;
					}

					$action_args = $args;

					array_unshift( $action_args, $new_action );

					call_user_func_array( 'do_action', $action_args );
				}

				return null;
			}
			elseif ( is_string( $this->actions[ $action ] ) ) {
				$action =  $this->actions[ $action ];
			}
		}

		if ( 0 !== strpos( $action, strtolower( __CLASS__ ) . '_' ) ) {
			$action = strtolower( __CLASS__ ) . '_' . $action;
		}

		array_unshift( $args, $action );

		return call_user_func_array( 'do_action', $args );
	}

}

$GLOBALS[ 'badgeos_gravityforms' ] = new BadgeOS_GravityForms();
