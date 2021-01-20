<?php
namespace ElementorExtras\Modules\Switcher\Skins;

// Elementor Extras Classes
use ElementorExtras\Base\Extras_Widget;

// Elementor Classes
use Elementor\Controls_Manager;
use Elementor\Group_Control_Image_Size;
use Elementor\Skin_Base as Elementor_Skin_Base;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * \Modules\Switcher\Skins
 *
 * @since  1.9.0
 */
abstract class Skin_Base extends Elementor_Skin_Base {

	/**
	 * Register Controls Actions
	 * 
	 * Registers controls at specific points in the Controls Stack
	 *
	 * @since  1.9.0
	 * @return void
	 */
	protected function _register_controls_actions() {
		add_action( 'elementor/element/ee-switcher/section_items/before_section_end', [ $this, 'register_controls' ] );
	}

	/**
	 * Register Controls
	 *
	 * @since  1.9.0
	 * @return void
	 * @param  $widget Extras_Widget
	 */
	public function register_controls( Extras_Widget $widget ) {
		$this->parent 	= $widget;

		$this->register_content_controls();
	}

	/**
	 * Register Content Controls
	 *
	 * @since  1.9.0
	 * @return void
	 */
	public function register_content_controls() {}

	/**
	 * Render
	 * 
	 * Render widget contents on frontend
	 *
	 * @since  1.9.0
	 * @return void
	 */
	public function render() {
		$this->parent->render();
	}

}