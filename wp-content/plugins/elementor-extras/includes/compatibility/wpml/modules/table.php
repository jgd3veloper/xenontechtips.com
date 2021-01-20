<?php
namespace ElementorExtras\Compatibility\WPML;

use WPML_Elementor_Module_With_Items;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Table
 *
 * Registers translatable module with items
 *
 * @since 1.8.8
 */
class Table extends WPML_Elementor_Module_With_Items {

	/**
	 * @since 1.8.0
	 * @return string
	 */
	public function get_items_field() {
		return 'rows';
	}

	/**
	 * Retrieve the fields inside the repeater
	 * 
	 * @since 1.8.8
	 *
	 * @return array
	 */
	public function get_fields() {
		return array(
			'cell_text', 					// Row cell content
			'cell_header', 					// Mobile cell header
			'link' => array( 'url' ), 		// Cell link
		);
	}

	/**
	 * Method for setting the title for each translatable field
	 *
	 * @since 1.8.8
	 *
	 * @param string    $field The name of the field
	 * @return string
	 */
	protected function get_title( $field ) {
		if ( 'cell_text' === $field ) {
			return esc_html__( 'Table: Cell Text', 'elementor-extras' );
		}

		if ( 'cell_header' === $field ) {
			return esc_html__( 'Table: Cell Mobile Header', 'elementor-extras' );
		}

		if ( 'url' === $field ) {
			return esc_html__( 'Table: Cell Link', 'elementor-extras' );
		}

		return '';
	}

	/**
	 * Method for determining the editor type for each field
	 * @since 1.8.8
	 *
	 * @param  string    $field Name of the field
	 * @return string
	 */
	protected function get_editor_type( $field ) {

		switch( $field ) {
			case 'cell_text':
			case 'cell_header':
			case 'url':
				return 'LINE';
	 
			default:
				return '';
		 }
	}

}
