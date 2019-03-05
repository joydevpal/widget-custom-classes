<?php 
/**
 * Plugin Name: Widget Custom Classes
 * Plugin URI: http://joydevpal.com/plugins/widget-custom-classes
 * Author: Joydev Pal
 * Author URI: http://joydevpal.com
 * Version: 1.0.0
 * Description: Add custom classes to your widgets for custom styling.
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: wcc
 */


// Load widget css front end function on wp load
add_action( 'wp_loaded', 'add_widget_custom_classes_frontend' );

/**
 * Add custom classes hook to front end
 * It must be added after register_sidebars is called
 */
function add_widget_custom_classes_frontend() {
	if ( ! is_admin() ) {
		add_filter( 'dynamic_sidebar_params', 'add_widget_custom_classes' );
	}
}

// Add custom widget classes to widget markup in front end
function add_widget_custom_classes( $params ) {

	global $wp_registered_widgets;

	if ( ! isset( $params[0] ) ) {
		return $params;
	}

	$arr_registered_widgets = wp_get_sidebars_widgets(); // Get an array of ALL registered widgets
	$this_id                = $params[0]['id']; // Get the id for the current sidebar we're processing
	$widget_id              = $params[0]['widget_id'];
	$widget_obj             = $wp_registered_widgets[ $widget_id ];
	$widget_num             = $widget_obj['params'][0]['number'];
	$widget_opt             = get_widget_option( $widget_obj );

	// All classes array.
	$classes = array();

	// Get widget custom classes from admin widget option for front end rendering
	if ( ! empty( $widget_opt[ $widget_num ]['classes'] ) ) {
		
		$custom_classes = explode( ' ', (string) $widget_opt[ $widget_num ]['classes'] );

		// Set multiple classes to an 'classes' array
		foreach ( $custom_classes as $key => $value ) {
			$classes[] = $value;
		}

	}

	$classes = (array) apply_filters( 'widget_css_classes', $classes, $widget_id, $widget_num, $widget_opt, $widget_obj );

	// Only unique, non-empty values, separated by space, escaped for HTML attributes.
	$classes = esc_attr( implode( ' ', array_filter( array_unique( $classes ) ) ) );

	if ( ! empty( $classes ) ) {
		// Add the classes to widget markup
		$params[0]['before_widget'] = add_classes_to_attribute(
			$params[0]['before_widget'],
			'class',
			$classes
		);
	}

	return $params;
}

// Get widget options
function get_widget_option( $widget_obj ) {
	return $widget_opt = get_option( $widget_obj['callback'][0]->option_name );
}

// Append custom classes to html 'class' attribute in front end
function add_classes_to_attribute( $str, $attr, $content_extra, $unique = false ) {

	// Check if attribute has single or double quotes.
	if ( $start = stripos( $str, $attr . '="' ) ) {
		// Double.
		$quote = '"';

	} elseif ( $start = stripos( $str, $attr . "='" ) ) {
		// Single.
		$quote = "'";

	} else {
		// Not found
		return $str;
	}

	// Add quote (for filtering purposes).
	$attr .= '=' . $quote;

	$content_extra = trim( $content_extra );

	if ( $unique ) {

		// Set start pointer to after the quote.
		$start += strlen( $attr );
		// Find first quote after the start pointer.
		$end = strpos( $str, $quote, $start );
		// Get the current content.
		$content = explode( ' ', substr( $str, $start, $end - $start ) );
		// Get our extra content.
		$content_extra = explode( ' ', $content_extra );
		foreach ( $content_extra as $class ) {
			if ( ! empty( $class ) && ! in_array( $class, $content, true ) ) {
				// This one can be added!
				$content[] = $class;
			}
		}
		// Remove duplicates and empty values.
		$content = array_filter( array_unique( $content ) );
		// Convert to space separated string.
		$content = implode( ' ', $content );
		// Get HTML before content.
		$before_content = substr( $str, 0, $start );
		// Get HTML after content.
		$after_content = substr( $str, $end );

		// Combine the string again.
		$str = $before_content . $content . $after_content;

	} else {
		$str = preg_replace(
			'/' . preg_quote( $attr, '/' ) . '/',
			$attr . $content_extra . ' ' ,
			$str,
			1
		);
	} // End if().

	// Return full HTML string.
	return $str;
}

/*========================== End of the Frontend Integration  ========================*/


// Initialize widget in admin
add_action('init', 'custom_widget_classes_init');

function custom_widget_classes_init() {
	if( is_admin() ) {
		add_action( 'in_widget_form', 'extend_widget_form', 10, 3 );
		add_filter( 'widget_update_callback', 'update_widget', 10, 2 );
	}
}

// Update widget custom classes from admin
function update_widget( $instance, $new_instance ) {
	// Set new classes to new instances
	$instance['classes'] = ( ! empty( $new_instance['classes'] ) ) ? sanitize_text_field( $new_instance['classes'] ) : '';

	return $instance;
}

// Extend every widget form by placing custom widget classes
function extend_widget_form( $widget, $return = true, $instance ) {

	$instance = wp_parse_args( $instance, array(
		'classes' => ''
	) );

	$access_class = current_user_can( 'edit_theme_options' );

	$fields = '';

	if ( $access_class ) {
		$fields .= add_class_field( $widget, $instance );
	}

	// Add extra fields to the widget form
	do_action( 'widget_css_classes_form', $fields, $instance );

	echo $fields;
	return $return;

}

// Widget form field in admin
function add_class_field( $widget, $instance ) {
	$random = rand(10,100);
	$field = '';
	$id = 'widget-classes-' . $random;
	$name = $widget->get_field_name( 'classes' );

	// Change the label for the CSS Classes form field.
	$label = apply_filters( 'widget_css_classes_class', esc_html__( 'Custom Classes', 'wcc' ) );
	$field .= '<label for="' . esc_attr( $id ) . '">'.$label.'</label>';
	$field .= "<input type='text' name='{$name}' id='{$id}' value='{$instance['classes']}' class='widefat' />";
	$field = '<p>' . $field . '</p>';

	return $field;
}