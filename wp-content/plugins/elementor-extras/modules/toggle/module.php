<?php
namespace ElementorExtras\Modules\Toggle;

// Elementor Extras Classes
use ElementorExtras\Base\Module_Base;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * \Modules\Toggle\Module
 *
 * @since  2.0.0
 */
class Module extends Module_Base {

	/**
	 * Get Name
	 * 
	 * Get the name of the module
	 *
	 * @since  2.0.0
	 * @return string
	 */
	public function get_name() {
		return 'toggle';
	}

	/**
	 * Get Widgets
	 * 
	 * Get the modules' widgets
	 *
	 * @since  2.0.0
	 * @return array
	 */
	public function get_widgets() {
		return [
			'Toggle_Element',
		];
	}
}
