<?php
namespace ElementorExtras\Modules\CustomFields;

// Elementor Extras Classes
use ElementorExtras\Base\Module_Base;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * \Modules\CustomFields\Module
 *
 * @since  2.1.0
 */
class Module extends Module_Base {

	/**
	 * Constructor
	 *
	 * @access public
	 * @since  2.1.0
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct();

		// ACF 5 and up
		if ( class_exists( '\acf' ) && function_exists( 'acf_get_field_groups' ) ) {
			$this->add_component( 'acf', new Fields\Acf() );
		}

		if ( function_exists( 'wpcf_admin_fields_get_groups' ) ) {
			$this->add_component( 'toolset', new Fields\Toolset() );
		}

		if ( function_exists( 'pods' ) ) {
			$this->add_component( 'pods', new Fields\Pods() );
		}
	}

	/**
	 * Get Name
	 * 
	 * Get the name of the module
	 *
	 * @since  2.1.0
	 * @return string
	 */
	public function get_name() {
		return 'custom-fields';
	}

	/**
	 * Get Field Types
	 * 
	 * Fetches available custom fields types
	 *
	 * @since 2.0.0
	 */
	public function get_field_types() {

		$field_types = [];

		foreach( $this->get_components() as $name => $component ) {
			$field_types[ $component->get_name() ] = $component->get_title();
		}

		return $field_types;
	}
}
