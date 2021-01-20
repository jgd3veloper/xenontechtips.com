<?php
/**
 * PA Category Manager.
 */

namespace PremiumAddons\Includes;

use PremiumAddons\Includes\Helper_Functions;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Class Addons_Category.
 */
class Addons_Category {

	/**
	 * Class object
	 *
	 * @var instance
	 */
	private static $instance = null;

	/**
	 * Constructor for the class
	 */
	public function __construct() {

		add_action( 'elementor/elements/categories_registered', array( $this, 'register_widgets_category' ), 9 );
	}

	/**
	 * Register Widgets Category
	 *
	 * Register a new category for Premium Addons widgets
	 *
	 * @since 4.0.0
	 * @access public
	 *
	 * @param object $elements_manager elements manager.
	 */
	public function register_widgets_category( $elements_manager ) {

		$elements_manager->add_category(
			'premium-elements',
			array(
				'title' => Helper_Functions::get_category(),
			),
			1
		);

	}

	/**
	 * Creates and returns an instance of the class
	 *
	 * @since  2.6.8
	 * @access public
	 *
	 * @return object
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance ) ) {

			self::$instance = new self();

		}

		return self::$instance;
	}
}
