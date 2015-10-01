<?php
/**
 * Custom Achievement Steps UI
 *
 * @package BadgeOS GravityForms
 * @subpackage Achievements
 * @author Beth Adele Long
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://bethadele.com
 */

/**
 * Update badgeos_get_step_requirements to include our custom requirements
 *
 * @since  0.0.1
 *
 * @param  array $requirements The current step requirements
 * @param  integer $step_id      The given step's post ID
 *
 * @return array                 The updated step requirements
 */
function badgeos_gravityforms_step_requirements( $requirements, $step_id ) {

	// Add our new requirements to the list
	$requirements[ 'gravityforms_trigger' ] = get_post_meta( $step_id, '_badgeos_gravityforms_trigger', true );
	$requirements[ 'gravityforms_object_id' ] = (int) get_post_meta( $step_id, '_badgeos_gravityforms_object_id', true );
	$requirements[ 'gravityforms_object_arg1' ] = (int) get_post_meta( $step_id, '_badgeos_gravityforms_object_arg1', true );
	$requirements[ 'gravityforms_object_arg2' ] = get_post_meta( $step_id, '_badgeos_gravityforms_object_arg2', true );

	// Return the requirements array
	return $requirements;

}

add_filter( 'badgeos_get_step_requirements', 'badgeos_gravityforms_step_requirements', 10, 2 );

/**
 * Filter the BadgeOS Triggers selector with our own options
 *
 * @since  1.0.0
 *
 * @param  array $triggers The existing triggers array
 *
 * @return array           The updated triggers array
 */
function badgeos_gravityforms_activity_triggers( $triggers ) {

	$triggers[ 'gravityforms_trigger' ] = __( 'GravityForms Activity', 'badgeos-gravityforms' );

	return $triggers;

}

add_filter( 'badgeos_activity_triggers', 'badgeos_gravityforms_activity_triggers' );

/**
 * Add GravityForms Triggers selector to the Steps UI
 *
 * @since 1.0.0
 *
 * @param integer $step_id The given step's post ID
 * @param integer $post_id The given parent post's post ID
 */
function badgeos_gravityforms_step_gravityforms_trigger_select( $step_id, $post_id ) {

	// Setup our select input
	echo '<select name="gravityforms_trigger" class="select-gravityforms-trigger">';
	echo '<option value="">' . __( 'Select a Form Trigger', 'badgeos-gravityforms' ) . '</option>';

	// Loop through all of our GravityForms trigger groups
	$current_trigger = get_post_meta( $step_id, '_badgeos_gravityforms_trigger', true );

	$gravityforms_triggers = $GLOBALS[ 'badgeos_gravityforms' ]->triggers;

	if ( !empty( $gravityforms_triggers ) ) {
		foreach ( $gravityforms_triggers as $trigger => $trigger_label ) {
			if ( is_array( $trigger_label ) ) {
				$optgroup_name = $trigger;
				$triggers = $trigger_label;

				echo '<optgroup label="' . esc_attr( $optgroup_name ) . '">';
				// Loop through each trigger in the group
				foreach ( $triggers as $trigger_hook => $trigger_name ) {
					echo '<option' . selected( $current_trigger, $trigger_hook, false ) . ' value="' . esc_attr( $trigger_hook ) . '">' . esc_html( $trigger_name ) . '</option>';
				}
				echo '</optgroup>';
			}
			else {
				echo '<option' . selected( $current_trigger, $trigger, false ) . ' value="' . esc_attr( $trigger ) . '">' . esc_html( $trigger_label ) . '</option>';
			}
		}
	}

	echo '</select>';

}

add_action( 'badgeos_steps_ui_html_after_trigger_type', 'badgeos_gravityforms_step_gravityforms_trigger_select', 10, 2 );

/**
 * Add a BuddyPress group selector to the Steps UI
 *
 * @since 1.0.0
 *
 * @param integer $step_id The given step's post ID
 * @param integer $post_id The given parent post's post ID
 */
function badgeos_gravityforms_step_etc_select( $step_id, $post_id ) {

	$current_trigger = get_post_meta( $step_id, '_badgeos_gravityforms_trigger', true );
	$current_object_id = (int) get_post_meta( $step_id, '_badgeos_gravityforms_object_id', true );
	$current_object_arg1 = (int) get_post_meta( $step_id, '_badgeos_gravityforms_object_arg1', true );
	$current_object_arg2 = get_post_meta( $step_id, '_badgeos_gravityforms_object_arg2', true );

	$current_object_arg1 = empty($current_object_arg1)
		? ''
		: $current_object_arg1;
	$current_object_arg2 = empty($current_object_arg2)
		? ''
		: $current_object_arg2;

	// Forms
	echo '<select name="badgeos_gravityforms_form_id" class="select-form-id">';
	echo '<option value="">' . __( 'Any Form', 'badgeos-gravityforms' ) . '</option>';

	// Loop through all objects
	$objects = GFAPI::get_forms();
	$field_select = '';
	if ( !empty( $objects ) ) {
		foreach ( $objects as $object ) {
			$selected = '';

			if ( in_array( $current_trigger, array( 'gform_after_submission' ) ) ) {
				$selected = selected( $current_object_id, $object['id'], false );
			}

			echo '<option' . $selected . ' value="' . $object['id'] . '">' . esc_html( $object['title'] ) . '</option>';
		}
	}

	echo '</select>';
	echo '<input name="badgeos_gravityforms_field_id" class="input-field-id" placeholder="Field ID" value="' . $current_object_arg1 . '" />';
	echo '<input name="badgeos_gravityforms_field_value" class="input-field-value" placeholder="Field Value" value="' . $current_object_arg2 . '" />';

	// $field_select = '<select name="badgeos_gravityforms_field_id" class="select-field-id">'
	// 							. '<option value="">' . __( 'Any Field', 'badgeos-gravityforms' )
	// 							. $field_select
	// 							. '</select>';

	// $field_input = '<input name="badgeos_gravityforms_field_value" class="input-field-value">';

	// if ( in_array( $current_trigger, array( 'badgeos_gravityforms_form_completed_specific' ) ) )
	// 	$gffield = (int) $current_object_arg1;

	// if ( empty( $gffield ) )
	// 	$gffield = '';

	// echo '<span><input name="badgeos_gravityforms_form_field" class="input-form-field" type="text" value="' . $gffield . '" size="3" maxlength="3" placeholder="100" />%</span>';

	// // Lessons
	// echo '<select name="badgeos_gravityforms_lesson_id" class="select-lesson-id">';
	// echo '<option value="">' . __( 'Any Lesson', 'badgeos-gravityforms' ) . '</option>';

	// // Loop through all objects
	// $objects = get_posts( array(
	// 	'post_type' => 'gfform-entry',
	// 	'post_status' => 'publish',
	// 	'posts_per_page' => -1
	// ) );

	// if ( !empty( $objects ) ) {
	// 	foreach ( $objects as $object ) {
	// 		$selected = '';

	// 		if ( in_array( $current_trigger, array( 'gravityforms_form_completed' ) ) )
	// 			$selected = selected( $current_object_id, $object->ID, false );

	// 		echo '<option' . $selected . ' value="' . $object->ID . '">' . esc_html( get_the_title( $object->ID ) ) . '</option>';
	// 	}
	// }

	// echo '</select>';

	// // Topics
	// echo '<select name="badgeos_gravityforms_topic_id" class="select-topic-id">';
	// echo '<option value="">' . __( 'Any Topic', 'badgeos-gravityforms' ) . '</option>';

	// // Loop through all objects
	// $objects = get_posts( array(
	// 	'post_type' => 'sfwd-topic',
	// 	'post_status' => 'publish',
	// 	'posts_per_page' => -1
	// ) );

	// if ( !empty( $objects ) ) {
	// 	foreach ( $objects as $object ) {
	// 		$selected = '';

	// 		if ( in_array( $current_trigger, array( 'gravityforms_topic_completed' ) ) )
	// 			$selected = selected( $current_object_id, $object->ID, false );

	// 		echo '<option' . $selected . ' value="' . $object->ID . '">' . esc_html( get_the_title( $object->ID ) ) . '</option>';
	// 	}
	// }

	// echo '</select>';

	// // Courses
	// echo '<select name="badgeos_gravityforms_course_id" class="select-course-id">';
	// echo '<option value="">' . __( 'Any Course', 'badgeos-gravityforms' ) . '</option>';

	// // Loop through all objects
	// $objects = get_posts( array(
	// 	'post_type' => 'sfwd-courses',
	// 	'post_status' => 'publish',
	// 	'posts_per_page' => -1
	// ) );

	// if ( !empty( $objects ) ) {
	// 	foreach ( $objects as $object ) {
	// 		$selected = '';

	// 		if ( in_array( $current_trigger, array( 'gravityforms_course_completed' ) ) )
	// 			$selected = selected( $current_object_id, $object->ID, false );

	// 		echo '<option' . $selected . ' value="' . $object->ID . '">' . esc_html( get_the_title( $object->ID ) ) . '</option>';
	// 	}
	// }

	// echo '</select>';

	// // Course Category
	// echo '<select name="badgeos_gravityforms_course_category_id" class="select-course-category-id">';
	// echo '<option value="">' . __( 'Any Form Tag', 'badgeos-gravityforms' ) . '</option>';

	// // Loop through all objects
	// $objects = get_terms( 'post_tag', array(
	// 	'hide_empty' => false
	// ) );

	// if ( !empty( $objects ) ) {
	// 	foreach ( $objects as $object ) {
	// 		$selected = '';

	// 		if ( in_array( $current_trigger, array( 'badgeos_gravityforms_course_completed_tag' ) ) )
	// 			$selected = selected( $current_object_id, $object->term_id, false );

	// 		echo '<option' . $selected . ' value="' . $object->term_id . '">' . esc_html( $object->name ) . '</option>';
	// 	}
	// }

	// echo '</select>';

}

add_action( 'badgeos_steps_ui_html_after_trigger_type', 'badgeos_gravityforms_step_etc_select', 10, 2 );

/**
 * AJAX Handler for saving all steps
 *
 * @since  1.0.0
 *
 * @param  string $title     The original title for our step
 * @param  integer $step_id   The given step's post ID
 * @param  array $step_data Our array of all available step data
 *
 * @return string             Our potentially updated step title
 */
function badgeos_gravityforms_save_step( $title, $step_id, $step_data ) {

	// If we're working on a GravityForms trigger
	if ( 'gravityforms_trigger' == $step_data[ 'trigger_type' ] ) {

		// Update our GravityForms trigger post meta
		update_post_meta( $step_id, '_badgeos_gravityforms_trigger', $step_data[ 'gravityforms_trigger' ] );

		// Rewrite the step title
		$title = $step_data[ 'gravityforms_trigger_label' ];

		$object_id = 0;
		$object_arg1 = 0;
		$object_arg2 = 0;

		// Form specific (pass)
		if ( 'gform_after_submission' == $step_data[ 'gravityforms_trigger' ] ) {
			// Get Object ID
			$object_id = (int) $step_data[ 'gravityforms_form_id' ];
			$object_arg1 = (int) $step_data[ 'gravityforms_field_id' ];
			$object_arg2 = $step_data[ 'gravityforms_field_value' ];

			$form = GFAPI::get_form($object_id);

			// Set new step title
			if ( empty( $object_id ) ) {
				$title = __( 'Completed any form', 'badgeos-gravityforms' );
			}
			else {
				if ( !empty( $object_arg2 ) ) {
					$title = sprintf( __( 'Completed form "%s" with %s', 'badgeos-gravityforms' ), $form['title'], $object_arg2 );
				} else {
					$title = sprintf( __( 'Completed form "%s"', 'badgeos-gravityforms' ), $form['title'] );
				}
			}
		}

		// Store our Form Data in meta
		update_post_meta( $step_id, '_badgeos_gravityforms_object_id', $object_id );
		update_post_meta( $step_id, '_badgeos_gravityforms_object_arg1', $object_arg1 );
		update_post_meta( $step_id, '_badgeos_gravityforms_object_arg2', $object_arg2 );
	}

	// Send back our custom title
	return $title;

}

add_filter( 'badgeos_save_step', 'badgeos_gravityforms_save_step', 10, 3 );

/**
 * Include custom JS for the BadgeOS Steps UI
 *
 * @since 1.0.0
 */
function badgeos_gravityforms_step_js() {

	?>
	<script type="text/javascript">
		jQuery( document ).ready( function ( $ ) {

			// Listen for our change to our trigger type selector
			$( document ).on( 'change', '.select-trigger-type', function () {

				var trigger_type = $( this );

				// Show our group selector if we're awarding based on a specific group
				if ( 'gravityforms_trigger' == trigger_type.val() ) {
					trigger_type.siblings( '.select-gravityforms-trigger' ).show().change();
				}
				else {
					trigger_type.siblings( '.select-gravityforms-trigger' ).hide().change();
				}

			} );

			// Listen for our change to our trigger type selector
			$( document ).on( 'change', '.select-gravityforms-trigger,' +
										'.select-form-id,' +
										'.input-field-id', function () {

				badgeos_gravityforms_step_change( $( this ) );

			} );

			// Trigger a change so we properly show/hide our GravityForms menus
			$( '.select-trigger-type' ).change();

			// Inject our custom step details into the update step action
			$( document ).on( 'update_step_data', function ( event, step_details, step ) {
				step_details.gravityforms_trigger = $( '.select-gravityforms-trigger', step ).val();
				step_details.gravityforms_trigger_label = $( '.select-gravityforms-trigger option', step ).filter( ':selected' ).text();

				step_details.gravityforms_form_id = $( '.select-form-id', step ).val();
				step_details.gravityforms_field_id = $( '.input-field-id', step ).val();
				step_details.gravityforms_field_value = $( '.input-field-value', step ).val();
			} );

		} );

		function badgeos_gravityforms_step_change( $this ) {
				var trigger_parent = $this.parent(),
					trigger_value = trigger_parent.find( '.select-gravityforms-trigger' ).val();

				// Form specific
				trigger_parent.find( '.select-form-id' )
					.toggle(
						( 'gform_after_submission' == trigger_value )
					);

				trigger_parent.find( '.input-field-id' )
					.toggle( 'gform_after_submission' == trigger_value );
				trigger_parent.find( '.input-field-value' )
					.toggle( 'gform_after_submission' == trigger_value );

				if ( ( 'gform_after_submission' == trigger_value
					   && '' != trigger_parent.find( '.select-form-id' ).val() ) ) {
					trigger_parent.find( '.required-count' )
						.val( '1' )
						.prop( 'disabled', true );
				}
		}
	</script>
<?php
}

add_action( 'admin_footer', 'badgeos_gravityforms_step_js' );
