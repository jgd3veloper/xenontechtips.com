<?php
namespace ElementorExtras\Modules\CustomFields\Fields;

// Elementor Extras Classes
use ElementorExtras\Base\Module_Base;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * \Modules\CustomFields\Fields\Acf
 *
 * @since  2.1.0
 */
class Acf extends Field_Base {

	/**
	 * Get Name
	 * 
	 * Get the name of the field type
	 *
	 * @since  2.1.0
	 * @return string
	 */
	public function get_name() {
		return 'acf';
	}

	/**
	 * Get Title
	 * 
	 * Get the title of the field type
	 *
	 * @since  2.1.0
	 * @return string
	 */
	public function get_title() {
		return __( 'ACF', 'elementor-extras' );
	}

	/**
	 * Get Available Fields
	 *
	 * @since  2.1.0
	 * @param  post_id|int 		The post id
	 * @return array|bool 		The available ACF fields
	 */
	public function get_fields( $post_id ) {
		if ( ! $post_id || ! function_exists( 'get_field_objects' ) )
			return;

		$_fields = [];
		$fields = get_field_objects( $post_id );

		if ( ! $fields )
			return; 

		foreach ( $fields as $name => $object ) {
			if ( 'date_picker' === $object['type'] || 'date_time_picker' === $object['type'] ) {
				$_fields[ $object['key'] ] = $object['label'];
			}
		}

		if ( $_fields )
			return $_fields;

		return false;
	}

	/**
	 * Get Field Value
	 * 
	 * Returns field value given a key and a post
	 *
	 * @since  2.1.0
	 * @param  post_id|int 		The Post ID
	 * @param  key|string 		The key of the field
	 * @return string|bool 		The formatted date or false
	 */
	public function get_field_value( $post_id, $key ) {
		// Fallback to current post
		if ( ! $post_id )
			$post_id = get_the_ID();

		// Double check for key and acf function
		if ( ! $key || ! function_exists( 'get_field_object' ) )
			return;

		// Get field object
		$field_object = get_field_object( $key, $post_id );

		// Check for valid value
		if ( ! $field_object['value'] )
			return;

		// Return the date in the appropriate format
		$date = \DateTime::createFromFormat( $field_object['return_format'], $field_object['value'] );

		if ( false !== $date )
			return $date->format( 'Y-m-d' );

		return false;
	}
}
