<?php
/**
 * Custom Achievement Rules
 *
 * @package BadgeOS GravityForms
 * @subpackage Achievements
 * @author Beth Adele Long
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://bethadele.com
 */

/**
 * Load up our GravityForms triggers so we can add actions to them
 *
 * @since 1.0.0
 */
function badgeos_gravityforms_load_triggers() {

	// Grab our GravityForms triggers
	$gravityforms_triggers = $GLOBALS[ 'badgeos_gravityforms' ]->triggers;

	if ( !empty( $gravityforms_triggers ) ) {
		foreach ( $gravityforms_triggers as $trigger => $trigger_label ) {
			if ( is_array( $trigger_label ) ) {
				$triggers = $trigger_label;

				foreach ( $triggers as $trigger_hook => $trigger_name ) {
					add_action( $trigger_hook, 'badgeos_gravityforms_trigger_event', 10, 20 );
				}
			}
			else {
				add_action( $trigger, 'badgeos_gravityforms_trigger_event', 10, 20 );
			}
		}
	}

}

add_action( 'init', 'badgeos_gravityforms_load_triggers' );

/**
 * Handle each of our GravityForms triggers
 *
 * @since 1.0.0
 */
function badgeos_gravityforms_trigger_event() {

	// Setup all our important variables
	global $blog_id, $wpdb;

// error_log('Inside badgeos_gravityforms_trigger_event');

	// Setup args
	$args = func_get_args();

//  error_log('args: ' . print_r($args, true));
	// These $args are ($entry, $form)

	$userID = get_current_user_id();

	if ( is_array( $args ) && isset( $args[ 0 ] ) && isset( $args[ 0 ][ 'created_by' ] ) ) {
		$userID = (int) $args[ 0 ][ 'created_by' ];
	}

	if ( empty( $userID ) ) {
		return;
	}

	$user_data = get_user_by( 'id', $userID );

	if ( empty( $user_data ) ) {
		return;
	}

	// Grab the current trigger
	$this_trigger = current_filter();

	// Update hook count for this user
	$new_count = badgeos_update_user_trigger_count( $userID, $this_trigger, $blog_id );

	// Mark the count in the log entry
	badgeos_post_log_entry( null, $userID, null, sprintf( __( '%1$s triggered %2$s (%3$dx)', 'badgeos' ), $user_data->user_login, $this_trigger, $new_count ) );

	// Now determine if any badges are earned based on this trigger event
	$triggered_achievements = $wpdb->get_results( $wpdb->prepare( "
		SELECT post_id
		FROM   $wpdb->postmeta
		WHERE  meta_key = '_badgeos_gravityforms_trigger'
				AND meta_value = %s
		", $this_trigger ) );

// error_log( 'this_trigger ' . $this_trigger );
// error_log('triggered_achievements: ' . print_r($triggered_achievements, true));

	foreach ( $triggered_achievements as $achievement ) {
// error_log( 'post_id ' . $achievement->post_id );
// error_log( 'userID ' . $userID );
// error_log( 'this_trigger ' . $this_trigger );
// error_log( 'blog_id ' . $blog_id );
// error_log( 'args ' . ' $entry, $form' );
		badgeos_maybe_award_achievement_to_user( $achievement->post_id, $userID, $this_trigger, $blog_id, $args );
	}
}

/**
 * Check if user deserves a GravityForms trigger step
 *
 * @since  1.0.0
 *
 * @param  bool $return         Whether or not the user deserves the step
 * @param  integer $user_id        The given user's ID
 * @param  integer $achievement_id The given achievement's post ID
 * @param  string $trigger        The trigger
 * @param  integer $site_id        The triggered site id
 * @param  array $args        The triggered args
 *
 * @return bool                    True if the user deserves the step, false otherwise
 */
function badgeos_gravityforms_user_deserves_gravityforms_step( $return, $user_id, $achievement_id, $this_trigger = '', $site_id = 1, $args = array() ) {

	// If we're not dealing with a step, bail here
	if ( 'step' != get_post_type( $achievement_id ) ) {
		return $return;
	}

	// Grab our step requirements
	$requirements = badgeos_get_step_requirements( $achievement_id );

	// If the step is triggered by GravityForms actions...
	if ( 'gravityforms_trigger' == $requirements[ 'trigger_type' ] ) {
		// Do not pass go until we say you can
		$return = false;

		// Unsupported trigger
		if ( !isset( $GLOBALS[ 'badgeos_gravityforms' ]->triggers[ $this_trigger ] ) ) {
			return $return;
		}

		// GravityForms requirements not met yet
		$gravityforms_triggered = false;

		// Set our main vars
		$gravityforms_trigger = $requirements[ 'gravityforms_trigger' ];
		$object_id = $requirements[ 'gravityforms_object_id' ];

		// Extra arg handling for further expansion
		$object_arg1 = null;

		if ( isset( $requirements[ 'gravityforms_object_arg1' ] ) )
			$object_arg1 = $requirements[ 'gravityforms_object_arg1' ];

		// $required_field = $requirements['gravityforms_???']

		// Object-specific triggers
		$gravityforms_object_triggers = array(
			'gform_after_submission' => 'form_id'
			// 'badgeos_gravityforms_form_completed_specific' => 'form'
		);

		// Triggered object ID (used in these hooks, generally 2nd arg)
		$triggered_object_id = 0;
		$entry_data = $args[ 0 ];
		$form_data = $args[ 1 ];

		if ( is_array( $entry_data ) && isset( $gravityforms_object_triggers[ $gravityforms_trigger ] ) && isset( $entry_data[ $gravityforms_object_triggers[ $gravityforms_trigger ] ] ) && !empty( $entry_data[ $gravityforms_object_triggers[ $gravityforms_trigger ] ] ) ) {
			$triggered_object_id = (int) $entry_data[ $gravityforms_object_triggers[ $gravityforms_trigger ] ];
		}

		// Use basic trigger logic if no object set
		if ( empty( $object_id ) ) {
			$gravityforms_triggered = true;
		}
		// Object specific
		elseif ( $triggered_object_id == $object_id ) {
			$gravityforms_triggered = true;

			// Forcing count due to BadgeOS bug tracking triggers properly
			$requirements[ 'count' ] = 1;
		}
		// Category specific
		// elseif ( in_array( $gravityforms_trigger, $gravityforms_category_triggers ) && has_term( $object_id, 'post_tag', $triggered_object_id ) ) {
		// 	$gravityforms_triggered = true;

		// 	// Forcing count due to BadgeOS bug tracking triggers properly
		// 	$requirements[ 'count' ] = 1;
		// }error_log('-----------------');

		// TODO: Enable user to optionally specify form values that user must select
		//       in order to trigger this achievement
		if ( $gravityforms_triggered && isset( $gravityforms_object_triggers[ $gravityforms_trigger ] ) && 'form' == $gravityforms_object_triggers[ $gravityforms_trigger ] ) {
			if ( 'gform_after_submission' == $gravityforms_trigger ) {
				$required_field = $object_arg1[ 'field' ];
				$required_value = $object_arg1[ 'value' ];

				if ( $entry_data[ $required_field ] != $required_value ) {
					$gravityforms_triggered = false;
				}
			}
		}

		// GravityForms requirements met
		if ( $gravityforms_triggered ) {
			// Grab the trigger count
			$trigger_count = badgeos_get_user_trigger_count( $user_id, $this_trigger, $site_id );

			// If we meet or exceed the required number of checkins, they deserve the step
			if ( 1 == $requirements[ 'count' ] || $requirements[ 'count' ] <= $trigger_count ) {
				// OK, you can pass go now
				$return = true;
			}
		}

		if ( $gravityforms_triggered && $return ) {
			$user_data = get_userdata( $user_id );

			badgeos_post_log_entry( null, $user_id, null, sprintf( __( '%1$s deserves %2$s', 'badgeos' ), $user_data->user_login, $this_trigger ) );
		}
	}

	return $return;
}

add_filter( 'user_deserves_achievement', 'badgeos_gravityforms_user_deserves_gravityforms_step', 15, 6 );
