<?php

namespace ElementorExtras\Extensions;

// Elementor Extras classes
use ElementorExtras\Utils;
use ElementorExtras\Base\Extension_Base;
use ElementorExtras\Controls\Control_Query as QueryControl;

// Elementor classes
use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Scheme_Typography;
use Elementor\Scheme_Color;
use Elementor\Repeater;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Conditions Extension
 *
 * Adds display conditions to elements
 *
 * @since 2.0.0
 */
class Extension_Display_Conditions extends Extension_Base {

	/**
	 * Is Common Extension
	 *
	 * Defines if the current extension is common for all element types or not
	 *
	 * @since 2.0.0
	 * @access protected
	 *
	 * @var bool
	 */
	protected $is_common = true;

	/**
	 * Display Conditions 
	 *
	 * Holds all the conditions for display on the frontend
	 *
	 * @since 2.0.0
	 * @access protected
	 *
	 * @var bool
	 */
	protected $conditions = [];

	/**
	 * Display Conditions 
	 *
	 * Holds all the conditions for display on the frontend
	 *
	 * @since 2.0.0
	 * @access protected
	 *
	 * @var bool
	 */
	protected $conditions_options = [];

	/**
	 * A list of scripts that the widgets is depended in
	 *
	 * @since 2.0.0
	 **/
	public function get_script_depends() {
		return [];
	}

	/**
	 * The description of the current extension
	 *
	 * @since 2.-.0
	 **/
	public static function get_description() {
		return __( 'Adds display conditions to widgets and sections allowing you to show them depending on authentication, roles, date and time of day.', 'elementor-extras' );
	}

	/**
	 * Is disabled by default
	 *
	 * Return wether or not the extension should be disabled by default,
	 * prior to user actually saving a value in the admin page
	 *
	 * @access public
	 * @since 2.0.0
	 * @return bool
	 */
	public static function is_default_disabled() {
		return true;
	}

	/**
	 * Add common sections
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 */
	protected function add_common_sections_actions() {

		// Activate sections for widgets
		add_action( 'elementor/element/common/section_custom_css/after_section_end', function( $element, $args ) {

			$this->add_common_sections( $element, $args );

		}, 10, 2 );

		// Activate sections for sections
		add_action( 'elementor/element/section/section_custom_css/after_section_end', function( $element, $args ) {

			$this->add_common_sections( $element, $args );

		}, 10, 2 );

		// Activate sections for widgets if elementor pro
		add_action( 'elementor/element/common/section_custom_css_pro/after_section_end', function( $element, $args ) {

			$this->add_common_sections( $element, $args );

		}, 10, 2 );

	}

	/**
	 * Set the Conditions options array
	 *
	 * @since 2.1.0
	 *
	 * @access private
	 */
	private function set_conditions_options() {

		$this->conditions_options = [
			[
				'label'		=> __( 'Visitor', 'elementor-extras' ),
				'options' 	=> [
					'authentication' 	=> __( 'Login Status', 'elementor-extras' ),
					'role' 				=> __( 'User Role', 'elementor-extras' ),
					'os' 				=> __( 'Operating System', 'elementor-extras' ),
					'browser' 			=> __( 'Browser', 'elementor-extras' ),
				],
			],
			[
				'label'			=> __( 'Date & Time', 'elementor-extras' ),
				'options' 		=> [
					'date' 		=> __( 'Current Date', 'elementor-extras' ),
					'time' 		=> __( 'Time of Day', 'elementor-extras' ),
					'day' 		=> __( 'Day of Week', 'elementor-extras' ),
				],
			],
			[
				'label'					=> __( 'Single', 'elementor-extras' ),
				'options' 				=> [
					'page' 				=> __( 'Page', 'elementor-extras' ),
					'post' 				=> __( 'Post', 'elementor-extras' ),
					'static_page' 		=> __( 'Static Page', 'elementor-extras' ),
					'post_type' 		=> __( 'Post Type', 'elementor-extras' ),
				],
			],
			[
				'label'					=> __( 'Archive', 'elementor-extras' ),
				'options' 				=> [
					'taxonomy_archive' 	=> __( 'Taxonomy', 'elementor-extras' ),
					'term_archive' 		=> __( 'Term', 'elementor-extras' ),
					'post_type_archive'	=> __( 'Post Type', 'elementor-extras' ),
					'date_archive'		=> __( 'Date', 'elementor-extras' ),
					'author_archive'	=> __( 'Author', 'elementor-extras' ),
					'search_results'	=> __( 'Search', 'elementor-extras' ),
				],
			],
		];

		// EDD Conditions
		if ( class_exists( 'Easy_Digital_Downloads', false ) ) {
			$this->conditions_options[] = [
				'label'					=> __( 'Easy Digital Downloads', 'elementor-extras' ),
				'options' 				=> [
					'edd_cart' 			=> __( 'Cart', 'elementor-extras' ),
				],
			];
		}
	}

	/**
	 * Add Controls
	 *
	 * @since 2.0.0
	 *
	 * @access private
	 */
	private function add_controls( $element, $args ) {

		global $wp_roles;

		$default_date_start = date( 'Y-m-d', strtotime( '-3 day' ) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) );
		$default_date_end 	= date( 'Y-m-d', strtotime( '+3 day' ) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) );
		$default_interval 	= $default_date_start . ' to ' . $default_date_end;

		$element_type = $element->get_type();

		$element->add_control(
			'ee_display_conditions_enable',
			[
				'label'			=> __( 'Display Conditions', 'elementor-extras' ),
				'type' 			=> Controls_Manager::SWITCHER,
				'default' 		=> '',
				'label_on' 		=> __( 'Yes', 'elementor-extras' ),
				'label_off' 	=> __( 'No', 'elementor-extras' ),
				'return_value' 	=> 'yes',
				'frontend_available'	=> true,
			]
		);

		if ( 'widget' === $element_type ) {
			$element->add_control(
				'ee_display_conditions_output',
				[
					'label'		=> __( 'Output HTML', 'elementor-extras' ),
					'description' => sprintf( __( 'If enabled, the HTML code will exist on the page but the %s will be hidden using CSS.', 'elementor-extras' ), $element_type ),
					'default'	=> 'yes',
					'type' 		=> Controls_Manager::SWITCHER,
					'label_on' 		=> __( 'Yes', 'elementor-extras' ),
					'label_off' 	=> __( 'No', 'elementor-extras' ),
					'return_value' 	=> 'yes',
					'frontend_available' => true,
					'condition'	=> [
						'ee_display_conditions_enable' => 'yes',
					],
				]
			);
		}

		$element->add_control(
			'ee_display_conditions_relation',
			[
				'label'		=> __( 'Display on', 'elementor-extras' ),
				'type' 		=> Controls_Manager::SELECT,
				'default' 	=> 'all',
				'options' 	=> [
					'all' 		=> __( 'All conditions met', 'elementor-extras' ),
					'any' 		=> __( 'Any condition met', 'elementor-extras' ),
				],
				'condition'	=> [
					'ee_display_conditions_enable' => 'yes',
				],
			]
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'ee_condition_key',
			[
				'type' 		=> Controls_Manager::SELECT,
				'default' 	=> 'authentication',
				'label_block' => true,
				'groups' 	=> $this->conditions_options,
			]
		);

		$repeater->add_control(
			'ee_condition_operator',
			[
				'type' 			=> Controls_Manager::SELECT,
				'default' 		=> 'is',
				'label_block' 	=> true,
				'options' 		=> [
					'is' 		=> __( 'Is', 'elementor-extras' ),
					'not' 		=> __( 'Is not', 'elementor-extras' ),
				],
			]
		);

		$repeater->add_control(
			'ee_condition_authentication_value',
			[
				'type' 		=> Controls_Manager::SELECT,
				'default' 	=> 'authenticated',
				'label_block' => true,
				'options' 	=> [
					'authenticated' => __( 'Logged in', 'elementor-extras' ),
				],
				'condition' => [
					'ee_condition_key' => 'authentication',
				],
			]
		);;

		$repeater->add_control(
			'ee_condition_role_value',
			[
				'type' 			=> Controls_Manager::SELECT,
				'description' 	=> __( 'Warning: This condition applies only to logged in visitors.', 'elementor-extras' ),
				'default' 		=> 'subscriber',
				'label_block' 	=> true,
				'options' 		=> $wp_roles->get_names(),
				'condition' 	=> [
					'ee_condition_key' => 'role',
				],
			]
		);

		$repeater->add_control(
			'ee_condition_date_value',
			[
				'label'		=> __( 'In interval', 'elementor-extras' ),
				'type' 		=> \Elementor\Controls_Manager::DATE_TIME,
				'picker_options' => [
					'enableTime'	=> false,
					'mode' 			=> 'range',
				],
				'label_block'	=> true,
				'default' 		=> $default_interval,
				'condition' 	=> [
					'ee_condition_key' => 'date',
				],
			]
		);

		$repeater->add_control(
			'ee_condition_time_value',
			[
				'label'		=> __( 'Before', 'elementor-extras' ),
				'type' 		=> \Elementor\Controls_Manager::DATE_TIME,
				'picker_options' => [
					'dateFormat' 	=> "H:i",
					'enableTime' 	=> true,
					'noCalendar' 	=> true,
				],
				'label_block'	=> true,
				'default' 		=> '',
				'condition' 	=> [
					'ee_condition_key' => 'time',
				],
			]
		);

		$repeater->add_control(
			'ee_condition_day_value',
			[
				'label'			=> __( 'Before', 'elementor-extras' ),
				'type' 			=> Controls_Manager::SELECT2,
				'placeholder'	=> __( 'Any', 'elementor-extras' ),
				'multiple'		=> true,
				'options' => [
					'1' => __( 'Monday', 'elementor-extras' ),
					'2' => __( 'Tuesday', 'elementor-extras' ),
					'3' => __( 'Wednesday', 'elementor-extras' ),
					'4' => __( 'Thursday', 'elementor-extras' ),
					'5' => __( 'Friday', 'elementor-extras' ),
					'6' => __( 'Saturday', 'elementor-extras' ),
					'7' => __( 'Sunday', 'elementor-extras' ),
				],
				'label_block'	=> true,
				'default' 		=> 'Monday',
				'condition' 	=> [
					'ee_condition_key' => 'day',
				],
			]
		);

		$os_options = $this->get_os_options();

		$repeater->add_control(
			'ee_condition_os_value',
			[
				'type' 			=> Controls_Manager::SELECT,
				'default' 		=> array_keys( $os_options )[0],
				'label_block' 	=> true,
				'options' 		=> $os_options,
				'condition' 	=> [
					'ee_condition_key' => 'os',
				],
			]
		);

		$browser_options = $this->get_browser_options();

		$repeater->add_control(
			'ee_condition_browser_value',
			[
				'type' 			=> Controls_Manager::SELECT,
				'default' 		=> array_keys( $browser_options )[0],
				'label_block' 	=> true,
				'options' 		=> $browser_options,
				'condition' 	=> [
					'ee_condition_key' => 'browser',
				],
			]
		);

		$repeater->add_control(
			'ee_condition_page_value',
			[
				'type' 			=> 'ee-query',
				'default' 		=> '',
				'placeholder'	=> __( 'Any', 'elementor-extras' ),
				'description'	=> __( 'Leave blank for any page.', 'elementor-extras' ),
				'label_block' 	=> true,
				'multiple'		=> true,
				'query_type'	=> 'posts',
				'object_type'	=> 'page',
				'condition' 	=> [
					'ee_condition_key' => 'page',
				],
			]
		);

		$repeater->add_control(
			'ee_condition_post_value',
			[
				'type' 			=> 'ee-query',
				'default' 		=> '',
				'placeholder'	=> __( 'Any', 'elementor-extras' ),
				'description'	=> __( 'Leave blank for any post.', 'elementor-extras' ),
				'label_block' 	=> true,
				'multiple'		=> true,
				'query_type'	=> 'posts',
				'object_type'	=> '',
				'condition' 	=> [
					'ee_condition_key' => 'post',
				],
			]
		);

		$repeater->add_control(
			'ee_condition_static_page_value',
			[
				'type' 			=> Controls_Manager::SELECT,
				'default' 		=> 'home',
				'label_block' 	=> true,
				'options' 		=> [
					'home'		=> __( 'Default Homepage', 'elementor-extras' ),
					'static'	=> __( 'Static Homepage', 'elementor-extras' ),
					'blog'		=> __( 'Blog Page', 'elementor-extras' ),
					'404'		=> __( '404 Page', 'elementor-extras' ),
				],
				'condition' 	=> [
					'ee_condition_key' => 'static_page',
				],
			]
		);

		$repeater->add_control(
			'ee_condition_post_type_value',
			[
				'type' 			=> Controls_Manager::SELECT2,
				'default' 		=> '',
				'placeholder'	=> __( 'Any', 'elementor-extras' ),
				'description'	=> __( 'Leave blank or select all for any post type.', 'elementor-extras' ),
				'label_block' 	=> true,
				'multiple'		=> true,
				'options' 		=> Utils::get_public_post_types_options( true ),
				'condition' 	=> [
					'ee_condition_key' => 'post_type',
				],
			]
		);

		$repeater->add_control(
			'ee_condition_taxonomy_archive_value',
			[
				'type' 			=> Controls_Manager::SELECT2,
				'default' 		=> '',
				'placeholder'	=> __( 'Any', 'elementor-extras' ),
				'description'	=> __( 'Leave blank or select all for any taxonomy archive.', 'elementor-extras' ),
				'multiple'		=> true,
				'label_block' 	=> true,
				'options' 		=> Utils::get_taxonomies_options(),
				'condition' 	=> [
					'ee_condition_key' => 'taxonomy_archive',
				],
			]
		);

		$repeater->add_control(
			'ee_condition_term_archive_value',
			[
				'label' 		=> __( 'Term', 'elementor-pro' ),
				'description'	=> __( 'Leave blank or select all for any term archive.', 'elementor-extras' ),
				'type' 			=> 'ee-query',
				'post_type' 	=> '',
				'options' 		=> [],
				'label_block' 	=> true,
				'multiple' 		=> true,
				'query_type' 	=> 'terms',
				'include_type' 	=> true,
				'condition' 	=> [
					'ee_condition_key' => 'term_archive',
				],
			]
		);

		$repeater->add_control(
			'ee_condition_post_type_archive_value',
			[
				'type' 			=> Controls_Manager::SELECT2,
				'default' 		=> '',
				'placeholder'	=> __( 'Any', 'elementor-extras' ),
				'description'	=> __( 'Leave blank or select all for any post type.', 'elementor-extras' ),
				'multiple'		=> true,
				'label_block' 	=> true,
				'options' 		=> Utils::get_public_post_types_options(),
				'condition' 	=> [
					'ee_condition_key' => 'post_type_archive',
				],
			]
		);

		$repeater->add_control(
			'ee_condition_date_archive_value',
			[
				'type' 			=> Controls_Manager::SELECT2,
				'default' 		=> '',
				'placeholder'	=> __( 'Any', 'elementor-extras' ),
				'description'	=> __( 'Leave blank or select all for any date based archive.', 'elementor-extras' ),
				'multiple'		=> true,
				'label_block' 	=> true,
				'options' 		=> [
					'day'		=> __( 'Day', 'elementor-extras' ),
					'month'		=> __( 'Month', 'elementor-extras' ),
					'year'		=> __( 'Year', 'elementor-extras' ),
				],
				'condition' 	=> [
					'ee_condition_key' => 'date_archive',
				],
			]
		);

		$repeater->add_control(
			'ee_condition_author_archive_value',
			[
				'type' 			=> 'ee-query',
				'default' 		=> '',
				'placeholder'	=> __( 'Any', 'elementor-extras' ),
				'description'	=> __( 'Leave blank for all authors.', 'elementor-extras' ),
				'multiple'		=> true,
				'label_block' 	=> true,
				'query_type'	=> 'authors',
				'condition' 	=> [
					'ee_condition_key' => 'author_archive',
				],
			]
		);

		$repeater->add_control(
			'ee_condition_search_results_value',
			[
				'type' 			=> Controls_Manager::TEXT,
				'default' 		=> '',
				'placeholder'	=> __( 'Keywords', 'elementor-extras' ),
				'description'	=> __( 'Enter keywords, separated by commas, to condition the display on specific keywords and leave blank for any.', 'elementor-extras' ),
				'label_block' 	=> true,
				'condition' 	=> [
					'ee_condition_key' => 'search_results',
				],
			]
		);

		if ( class_exists( 'Easy_Digital_Downloads', false ) ) {
			$repeater->add_control(
				'ee_condition_edd_cart_value',
				[
					'type' 			=> Controls_Manager::SELECT,
					'default' 		=> 'empty',
					'label_block' 	=> true,
					'options' 		=> [
						'empty'		=> __( 'Empty', 'elementor-extras' ),
					],
					'condition' 	=> [
						'ee_condition_key' => 'edd_cart',
					],
				]
			);
		}

		$element->add_control(
			'ee_display_conditions',
			[
				'label' 	=> __( 'Conditions', 'elementor-extras' ),
				'type' 		=> Controls_Manager::REPEATER,
				'default' 	=> [
					[
						'ee_condition_key' 					=> 'authentication',
						'ee_condition_operator' 			=> 'is',
						'ee_condition_authentication_value' => 'authenticated',
					],
				],
				'condition'		=> [
					'ee_display_conditions_enable' => 'yes',
				],
				'fields' 		=> array_values( $repeater->get_controls() ),
				'title_field' 	=> 'Condition',
			]
		);

	}

	/**
	 * Get OS options for control
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 */
	protected function get_os_options() {
		return [
			'iphone' 		=> 'iPhone',
			'windows' 		=> 'Windows',
			'open_bsd'		=> 'OpenBSD',
			'sun_os'    	=> 'SunOS',
			'linux'     	=> 'Linux',
			'safari'    	=> 'Safari',
			'mac_os'    	=> 'Mac OS',
			'qnx'       	=> 'QNX',
			'beos'      	=> 'BeOS',
			'os2'       	=> 'OS/2',
			'search_bot'	=> 'Search Bot',
		];
	}

	/**
	 * Get browser options for control
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 */
	protected function get_browser_options() {
		return [
			'ie'			=> 'Internet Explorer',
			'firefox'		=> 'Mozilla Firefox',
			'chrome'		=> 'Google Chrome',
			'opera_mini'	=> 'Opera Mini',
			'opera'			=> 'Opera',
			'safari'		=> 'Safari',
		];
	}

	/**
	 * Add Actions
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 */
	protected function add_actions() {

		$this->set_conditions_options();

		// Activate controls for widgets
		add_action( 'elementor/element/common/section_elementor_extras_advanced/before_section_end', function( $element, $args ) {

			$this->add_controls( $element, $args );

		}, 10, 2 );

		add_action( 'elementor/element/section/section_elementor_extras_advanced/before_section_end', function( $element, $args ) {

			$this->add_controls( $element, $args );

		}, 10, 2 );

		// Conditions for widgets
		add_action( 'elementor/widget/render_content', function( $widget_content, $element ) {

			$settings = $element->get_settings();

			if ( 'yes' === $settings[ 'ee_display_conditions_enable' ] ) {

				// Set the conditions
				$this->set_conditions( $element->get_id(), $settings['ee_display_conditions'] );

				// if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				// 	ob_start();
				// 	$this->render_editor_notice( $settings );
				// 	$widget_content .= ob_get_clean();
				// }

				if ( ! $this->is_visible( $element->get_id(), $settings['ee_display_conditions_relation'] ) ) { // Check the conditions
					if ( 'yes' !== $settings['ee_display_conditions_output'] ) {
						return; // And on frontend we stop the rendering of the widget
					}
				}
			}
   
			return $widget_content;
		
		}, 10, 2 );

		// Conditions for widgets
		add_action( 'elementor/frontend/widget/before_render', function( $element ) {
			
			$settings = $element->get_settings();

			if ( 'yes' === $settings[ 'ee_display_conditions_enable' ] ) {

				// Set the conditions
				$this->set_conditions( $element->get_id(), $settings['ee_display_conditions'] );

				if ( ! $this->is_visible( $element->get_id(), $settings['ee_display_conditions_relation'] ) ) { // Check the conditions
					$element->add_render_attribute( '_wrapper', 'class', 'ee-conditions--hidden' );
				}
			}

		}, 10, 1 );

		// Conditions for sections
		add_action( 'elementor/frontend/section/before_render', function( $element ) {
			
			$settings = $element->get_settings();

			if ( 'yes' === $settings[ 'ee_display_conditions_enable' ] ) {

				// Set the conditions
				$this->set_conditions( $element->get_id(), $settings['ee_display_conditions'] );

				if ( ! $this->is_visible( $element->get_id(), $settings['ee_display_conditions_relation'] ) ) { // Check the conditions
					$element->add_render_attribute( '_wrapper', 'class', 'ee-conditions--hidden' );
				}
			}

		}, 10, 1 );

	}

	protected function render_editor_notice( $settings ) {
		?><span>This widget is displayed conditionally.</span>
		<?php
	}

	/**
	 * Set conditions.
	 *
	 * Sets the conditions property to all conditions comparison values
	 *
	 * @since 2.0.0
	 * @access protected
	 * @static
	 *
	 * @param mixed  $conditions  The conditions from the repeater field control
	 *
	 * @return void
	 */
	protected function set_conditions( $id, $conditions = [] ) {
		if ( ! $conditions )
			return;

		foreach ( $conditions as $index => $condition ) {
			$key 		= $condition['ee_condition_key'];
			$operator 	= $condition['ee_condition_operator'];
			$value 		= $condition['ee_condition_' . $key . '_value'];

			if ( method_exists( $this, 'check_' . $key ) ) {
				$check = call_user_func( [ $this, 'check_' . $key ], $value, $operator );
				$this->conditions[ $id ][ $key . '_' . $condition['_id'] ] = $check;
			}
		}
	}

	/**
	 * Check conditions.
	 *
	 * Checks for all or any conditions and returns true or false
	 * depending on wether the content can be shown or not
	 *
	 * @since 2.0.0
	 * @access protected
	 * @static
	 *
	 * @param mixed  $relation  Required conditions relation
	 *
	 * @return bool
	 */
	protected function is_visible( $id, $relation ) {

		if ( ! array_key_exists( $id, $this->conditions ) )
			return;

		if ( ! \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			if ( 'any' === $relation ) {
				if ( ! in_array( true, $this->conditions[ $id ] ) )
					return false;
			} else {
				if ( in_array( false, $this->conditions[ $id ] ) )
					return false;
			}
		}

		return true;
	}

	/**
	 * Compare conditions.
	 *
	 * Checks two values against an operator
	 *
	 * @since 2.0.0
	 * @access protected
	 * @static
	 *
	 * @param mixed  $left_value  First value to compare.
	 * @param mixed  $right_value Second value to compare.
	 * @param string $operator    Comparison operator.
	 *
	 * @return bool
	 */
	protected static function compare( $left_value, $right_value, $operator ) {
		switch ( $operator ) {
			case 'is':
				return $left_value == $right_value;
			case 'not':
				return $left_value != $right_value;
			default:
				return $left_value === $right_value;
		}
	}

	/**
	 * Check user login status
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 *
	 * @param mixed  $value  The control value to check
	 * @param string  $operator  Comparison operator.
	 */
	protected static function check_authentication( $value, $operator ) {
		return self::compare( is_user_logged_in(), true, $operator );
	}

	/**
	 * Check user role
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 *
	 * @param mixed  $value  The control value to check
	 * @param string  $operator  Comparison operator.
	 */
	protected static function check_role( $value, $operator ) {

		$user = wp_get_current_user();
		return self::compare( is_user_logged_in() && in_array( $value, $user->roles ), true, $operator );
	}

	/**
	 * Check date interval
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 *
	 * @param mixed  $value  The control value to check
	 * @param string  $operator  Comparison operator.
	 */
	protected static function check_date( $value, $operator ) {

		// Split control valur into two dates
		$intervals = explode( 'to' , preg_replace('/\s+/', '', $value ) );

		// Make sure the explode return an array with exactly 2 indexes
		if ( ! is_array( $intervals ) || 2 !== count( $intervals ) ) 
			return;

		// Set start and end dates
		$start 	= $intervals[0];
		$end 	= $intervals[1];
		$today 	= date('Y-m-d');

		// Default returned bool to false
		$show 	= false;

		// Check vars
		if ( \DateTime::createFromFormat( 'Y-m-d', $start ) === false || // Make sure it's a date
			 \DateTime::createFromFormat( 'Y-m-d', $end ) === false ) // Make sure it's a date
			return;

		// Convert to timestamp
		$start_ts 	= strtotime( $start ) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
		$end_ts 	= strtotime( $end ) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
		$today_ts 	= strtotime( $today ) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );

		// Check that user date is between start & end
		$show = ( ($today_ts >= $start_ts ) && ( $today_ts <= $end_ts ) );

		return self::compare( $show, true, $operator );
	}

	/**
	 * Check time of day interval
	 *
	 * Checks wether current time is in given interval
	 * in order to display element
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 *
	 * @param mixed  $value  The control value to check
	 * @param string  $operator  Comparison operator.
	 */
	protected static function check_time( $value, $operator ) {

		// Split control valur into two dates
		$time 	= date( 'H:i', strtotime( preg_replace('/\s+/', '', $value ) ) );
		$now 	= date( 'H:i', strtotime("now") + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) );

		// Default returned bool to false
		$show 	= false;

		// Check vars
		if ( \DateTime::createFromFormat( 'H:i', $time ) === false ) // Make sure it's a valid DateTime format
			return;

		// Convert to timestamp
		$time_ts 	= strtotime( $time );
		$now_ts 	= strtotime( $now );

		// Check that user date is between start & end
		$show = ( $now_ts < $time_ts );

		return self::compare( $show, true, $operator );
	}

	/**
	 * Check day of week
	 *
	 * Checks wether today falls inside a
	 * specified day of the week
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 *
	 * @param mixed  $value  The control value to check
	 * @param string  $operator  Comparison operator.
	 */
	protected static function check_day( $value, $operator ) {

		$show = false;

		if ( is_array( $value ) && ! empty( $value ) ) {
			foreach ( $value as $_key => $_value ) {
				if ( $_value === date( 'w' ) ) {
					$show = true; break;
				}
			}
		} else { $show = $value === date( 'w' ); }

		return self::compare( $show, true, $operator );
	}

	/**
	 * Check operating system of visitor
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 *
	 * @param mixed  $value  The control value to check
	 * @param string $operator  Comparison operator.
	 */
	protected static function check_os( $value, $operator ) {

		$oses = [
			'iphone'            => '(iPhone)',
			'windows' 			=> 'Win16|(Windows 95)|(Win95)|(Windows_95)|(Windows 98)|(Win98)|(Windows NT 5.0)|(Windows 2000)|(Windows NT 5.1)|(Windows XP)|(Windows NT 5.2)|(Windows NT 6.0)|(Windows Vista)|(Windows NT 6.1)|(Windows 7)|(Windows NT 4.0)|(WinNT4.0)|(WinNT)|(Windows NT)|Windows ME',
			'open_bsd'          => 'OpenBSD',
			'sun_os'            => 'SunOS',
			'linux'             => '(Linux)|(X11)',
			'safari'            => '(Safari)',
			'mac_os'            => '(Mac_PowerPC)|(Macintosh)',
			'qnx'               => 'QNX',
			'beos'              => 'BeOS',
			'os2'              	=> 'OS/2',
			'search_bot'        => '(nuhk)|(Googlebot)|(Yammybot)|(Openbot)|(Slurp/cat)|(msnbot)|(ia_archiver)',
		];

		return self::compare( preg_match('@' . $oses[ $value ] . '@', $_SERVER['HTTP_USER_AGENT'] ), true, $operator );
	}

	/**
	 * Check browser of visitor
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 *
	 * @param mixed  $value  The control value to check
	 * @param string $operator  Comparison operator.
	 */
	protected static function check_browser( $value, $operator ) {

		$browsers = [
			'ie'			=> [
				'MSIE',
				'Trident',
			],
			'firefox'		=> 'Firefox',
			'chrome'		=> 'Chrome',
			'opera_mini'	=> 'Opera Mini',
			'opera'			=> 'Opera',
			'safari'		=> 'Safari',
		];

		$show = false;

		if ( 'ie' === $value ) {
			if ( false !== strpos( $_SERVER['HTTP_USER_AGENT'], $browsers[ $value ][0] ) || false !== strpos( $_SERVER['HTTP_USER_AGENT'], $browsers[ $value ][1] ) ) {
				$show = true;
			}
		} else {
			if ( false !== strpos( $_SERVER['HTTP_USER_AGENT'], $browsers[ $value ] ) ) {
				$show = true;

				// Additional check for Chrome that returns Safari
				if ( 'safari' === $value || 'firefox' === $value ) {
					if ( false !== strpos( $_SERVER['HTTP_USER_AGENT'], 'Chrome' ) ) {
						$show = false;
					}
				}
			}
		}
		

		return self::compare( $show, true, $operator );
	}

	/**
	 * Check current page
	 *
	 * @since 2.1.0
	 *
	 * @access protected
	 *
	 * @param mixed  $value  The control value to check
	 * @param string $operator  Comparison operator.
	 */
	protected static function check_page( $value, $operator ) {
		$show = false;

		if ( is_array( $value ) && ! empty( $value ) ) {
			foreach ( $value as $_key => $_value ) {
				if ( is_page( $_value ) ) {
					$show = true; break;
				}
			}
		} else { $show = is_page( $value ); }

		return self::compare( $show, true, $operator );
	}

	/**
	 * Check current post
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 *
	 * @param mixed  $value  The control value to check
	 * @param string $operator  Comparison operator.
	 */
	protected static function check_post( $value, $operator ) {
		$show = false;

		if ( is_array( $value ) && ! empty( $value ) ) {
			foreach ( $value as $_key => $_value ) {
				if ( is_single( $_value ) || is_singular( $_value ) ) {
					$show = true; break;
				}
			}
		} else { $show = is_single( $value ) || is_singular( $value ); }

		return self::compare( $show, true, $operator );
	}

	/**
	 * Check browser of visitor
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 *
	 * @param mixed  $value  The control value to check
	 * @param string $operator  Comparison operator.
	 */
	protected static function check_static_page( $value, $operator ) {

		if ( 'home' === $value ) {
			return self::compare( ( is_front_page() && is_home() ), true, $operator );
		} elseif ( 'static' === $value ) {
			return self::compare( ( is_front_page() && ! is_home() ), true, $operator );
		} elseif ( 'blog' === $value ) {
			return self::compare( ( ! is_front_page() && is_home() ), true, $operator );
		} elseif ( '404' === $value ) {
			return self::compare( is_404(), true, $operator );
		}
	}

	/**
	 * Check current post type
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 *
	 * @param mixed  $value  The control value to check
	 * @param string $operator  Comparison operator.
	 */
	protected static function check_post_type( $value, $operator ) {
		$show = false;

		if ( is_array( $value ) && ! empty( $value ) ) {
			foreach ( $value as $_key => $_value ) {
				if ( is_singular( $_value ) ) {
					$show = true; break;
				}
			}
		} else { $show = is_singular( $value ); }

		return self::compare( $show, true, $operator );
	}

	/**
	 * Check current taxonomy archive
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 *
	 * @param mixed  $value  The control value to check
	 * @param string $operator  Comparison operator.
	 */
	protected static function check_taxonomy_archive( $value, $operator ) {
		$show = false;

		if ( is_array( $value ) && ! empty( $value ) ) {
			foreach ( $value as $_key => $_value ) {

				$show = self::check_taxonomy_archive_type( $_value );

				if ( $show ) break;
			}
		} else { $show = self::check_taxonomy_archive_type( $value ); }

		return self::compare( $show, true, $operator );
	}

	/**
	 * Checks a given taxonomy against the current page template
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 *
	 * @param string  $taxonomy  The taxonomy to check against
	 */
	protected static function check_taxonomy_archive_type( $taxonomy ) {
		if ( 'category' === $taxonomy ) {
			return is_category();
		} else if ( 'post_tag' === $taxonomy ) {
			return is_tag();
		} else if ( '' === $taxonomy || empty( $taxonomy ) ) {
			return is_tax() || is_category() || is_tag();
		} else {
			return is_tax( $taxonomy );
		}

		return false;
	}

	/**
	 * Check current taxonomy term archive
	 *
	 * @since 2.1.2
	 *
	 * @access protected
	 *
	 * @param mixed  $value  The control value to check
	 * @param string $operator  Comparison operator.
	 */
	protected static function check_term_archive( $value, $operator ) {
		$show = false;

		if ( is_array( $value ) && ! empty( $value ) ) {
			foreach ( $value as $_key => $_value ) {

				$show = self::check_term_archive_type( $_value );

				if ( $show ) break;
			}
		} else { $show = self::check_term_archive_type( $value ); }

		return self::compare( $show, true, $operator );
	}

	/**
	 * Checks a given taxonomy term against the current page template
	 *
	 * @since 2.1.2
	 *
	 * @access protected
	 *
	 * @param string  $taxonomy  The taxonomy to check against
	 */
	protected static function check_term_archive_type( $term ) {

		if ( is_category( $term ) ) {
			return true;
		} else if ( is_tag( $term ) ) {
			return true;
		} else if ( is_tax() ) {
			if ( is_tax( get_queried_object()->taxonomy, $term ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check current post type archive
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 *
	 * @param mixed  $value  The control value to check
	 * @param string $operator  Comparison operator.
	 */
	protected static function check_post_type_archive( $value, $operator ) {
		$show = false;

		if ( is_array( $value ) && ! empty( $value ) ) {
			foreach ( $value as $_key => $_value ) {
				if ( is_post_type_archive( $_value ) ) {
					$show = true; break;
				}
			}
		} else { $show = is_post_type_archive( $value ); }

		return self::compare( $show, true, $operator );
	}

	/**
	 * Check current date archive
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 *
	 * @param mixed  $value  The control value to check
	 * @param string $operator  Comparison operator.
	 */
	protected static function check_date_archive( $value, $operator ) {
		$show = false;

		if ( is_array( $value ) && ! empty( $value ) ) {
			foreach ( $value as $_key => $_value ) {
				if ( self::check_date_archive_type( $_value ) ) {
					$show = true; break;
				}
			}
		} else { $show = is_date( $value ); }

		return self::compare( $show, true, $operator );
	}

	/**
	 * Checks a given date type against the current page template
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 *
	 * @param string  $type  The type of date archive to check against
	 */
	protected static function check_date_archive_type( $type ) {
		if ( 'day' === $type ) { // Day
			return is_day();
		} elseif ( 'month' === $type ) { // Month
			return is_month();
		} elseif ( 'year' === $type ) { // Year
			return is_year();
		}

		return false;
	}

	/**
	 * Check current author archive
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 *
	 * @param mixed  $value  The control value to check
	 * @param string $operator  Comparison operator.
	 */
	protected static function check_author_archive( $value, $operator ) {
		$show = false;

		if ( is_array( $value ) && ! empty( $value ) ) {
			foreach ( $value as $_key => $_value ) {
				if ( is_author( $_value ) ) {
					$show = true; break;
				}
			}
		} else { $show = is_author( $value ); }

		return self::compare( $show, true, $operator );
	}

	/**
	 * Check current search query
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 *
	 * @param mixed  $value  The control value to check
	 * @param string $operator  Comparison operator.
	 */
	protected static function check_search_results( $value, $operator ) {
		$show = false;

		if ( is_search() ) {

			if ( empty( $value ) ) { // We're showing on all search pages

				$show = true;

			} else { // We're showing on specific keywords

				$phrase = get_search_query(); // The user search query

				if ( '' !== $phrase && ! empty( $phrase ) ) { // Only proceed if there is a query

					$keywords = explode( ',', $value ); // Separate keywords

					foreach ( $keywords as $index => $keyword ) {
						if ( self::keyword_exists( trim( $keyword ), $phrase ) ) {
							$show = true; break;
						}
					}
				}
			}
		}

		return self::compare( $show, true, $operator );
	}

	/**
	 * Check is EDD Cart is empty
	 *
	 * @since 2.1.0
	 *
	 * @access protected
	 *
	 * @param mixed  $value  The control value to check
	 * @param string $operator  Comparison operator.
	 */
	protected static function check_edd_cart( $value, $operator ) {
		
		if ( ! class_exists( 'Easy_Digital_Downloads', false ) )
			return false;

		$show = empty( edd_get_cart_contents() );

		return self::compare( $show, true, $operator );
	}

	protected static function keyword_exists( $keyword, $phrase ) {
		return strpos( $phrase, trim( $keyword ) ) !== false;
	}
}