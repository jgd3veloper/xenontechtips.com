<?php

namespace ElementorExtras\Base;

use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

abstract class Extras_Widget extends Widget_Base {

	/**
	 * Wether or not we are in edit mode
	 *
	 * Used for the add_helper_render_attribute method which needs to
	 * add attributes only in edit mode
	 *
	 * @access protected
	 *
	 * @var bool
	 */
	protected $_is_edit_mode = false;

	/**
	 * Get Categories
	 * 
	 * Get the categories in which this widget can be found
	 *
	 * @since  1.6.0
	 * @return array
	 */
	public function get_categories() {
		return [ 'elementor-extras' ];
	}

	/**
	 * Widget base constructor.
	 *
	 * Initializing the widget base class.
	 *
	 * @since 1.6.0
	 * @access public
	 *
	 * @param array       $data Widget data. Default is an empty array.
	 * @param array|null  $args Optional. Widget default arguments. Default is null.
	 */
	public function __construct( $data = [], $args = null ) {

		parent::__construct( $data, $args );

		// Set edit mode
		$this->_is_edit_mode = \Elementor\Plugin::instance()->editor->is_edit_mode();
	}

	/**
	 * Method for adding editor helper attributes
	 *
	 * Adds attributes that enable a display of a label for a specific html element
	 *
	 * @access public
	 * @since 1.6.0
	 * @return void
	 */
	public function add_helper_render_attribute( $key, $name = '' ) {

		if ( ! $this->_is_edit_mode )
			return;

		$this->add_render_attribute( $key, [
			'data-ee-helper' 	=> $name,
			'class'				=> 'ee-editor-helper',
		] );
	}

	/**
	 * Method for adding a placeholder for the widget in the preview area
	 *
	 * @access public
	 * @since 2.0.0
	 * @return void
	 */
	public function render_placeholder( $args ) {

		if ( ! $this->_is_edit_mode )
			return;

		$defaults = [
			'title_tag' => 'h4',
			'title' => $this->get_title(),
			'body' 	=> __( 'This is a placeholder for this widget and will not shown on the page.', 'elementor-extras' ),
		];

		$args = wp_parse_args( $args, $defaults );

		$this->add_render_attribute([
			'ee-placeholder' => [
				'class' => 'ee-editor-placeholder',
			],
			'ee-placeholder-title' => [
				'class' => 'ee-editor-placeholder__title',
			],
			'ee-placeholder-body' => [
				'class' => 'ee-editor-placeholder__body',
			],
		]);

		?><div <?php echo $this->get_render_attribute_string( 'ee-placeholder' ); ?>>
			<<?php echo $args['title_tag']; ?> <?php echo $this->get_render_attribute_string( 'ee-placeholder-title' ); ?>>
				<?php echo $args['title']; ?>
			</<?php echo $args['title_tag']; ?>>
			<div <?php echo $this->get_render_attribute_string( 'ee-placeholder-body' ); ?>>
				<?php echo $args['body']; ?>
			</div>
		</div><?php
	}

	/**
	 * Method for setting widget dependancy on Elementor Pro plugin
	 *
	 * When returning true it doesn't allow the widget to be registered
	 *
	 * @access public
	 * @since 1.6.0
	 * @return bool
	 */
	public static function requires_elementor_pro() {
		return false;
	}

	/**
	 * Get skin setting
	 *
	 * Retrieves the current skin setting
	 *
	 * @access protected
	 * @since 2.1.0
	 * @return mixed
	 */
	protected function get_skin_setting( $setting_key ) {
		if ( ! $setting_key )
			return false;

		return $this->get_current_skin()->get_instance_value( $setting_key );
	}

}
