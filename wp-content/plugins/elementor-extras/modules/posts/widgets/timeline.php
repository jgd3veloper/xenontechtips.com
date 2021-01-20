<?php
namespace ElementorExtras\Modules\Posts\Widgets;

use ElementorExtras\Base\Extras_Widget;
use ElementorExtras\Group_Control_Transition;

// Elementor Classes
use Elementor\Icons_Manager;
use Elementor\Controls_Manager;
use Elementor\Utils;
use Elementor\Repeater;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Scheme_Color;
use Elementor\Scheme_Typography;

// Elementor Pro Classes
use ElementorPro\Modules\QueryControl\Controls\Group_Control_Posts;
use ElementorPro\Modules\QueryControl\Module as Query_Control;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Timeline
 *
 * @since 0.1.0
 */
class Timeline extends Extras_Widget {

	/**
	 * @var \WP_Query
	 */
	private $_query = null;

	public function get_name() {
		return 'timeline';
	}

	public function get_title() {
		return __( 'Timeline', 'elementor-extras' );
	}

	public function get_icon() {
		return 'nicon nicon-timeline';
	}

	public function get_categories() {
		return [ 'elementor-extras' ];
	}

	public function get_script_depends() {
		return [
			'ee-timeline',
			'gsap-js',
		];
	}

	public function get_query() {
		return $this->_query;
	}

	protected function _register_controls() {

		$posts_control_settings = [
			'label' 		=> __( 'Source', 'elementor-extras' ),
			'type' 			=> Controls_Manager::SELECT,
			'default' 		=> 'custom',
			'options' 		=> [
				'custom' 		=> __( 'Custom', 'elementor-extras' ),
			],
		];

		if ( is_elementor_pro_active() ) {
			$posts_control_settings[ 'options' ][ 'posts' ] = __( 'Posts', 'elementor-extras' );
		}

		$this->start_controls_section(
			'section_settings',
			[
				'label' => __( 'Settings', 'elementor-extras' ),
			]
		);

			// $this->add_control(
			// 	'Layout',
			// 	[
			// 		'label' 		=> __( 'Layout', 'elementor-extras' ),
			// 		'type' 			=> Controls_Manager::SELECT,
			// 		'default' 		=> 'vertical',
			// 		'options' 		=> [
			// 			'vertical' 		=> __( 'Vertical', 'elementor-extras' ),
			// 			'horizontal' 	=> __( 'Horizontal', 'elementor-extras' ),
			// 		],
			// 		'prefix_class'		=> 'ee-timeline--'
			// 	]
			// );

			$this->add_control( 'source', $posts_control_settings );

			if ( ! is_elementor_pro_active() ) {

				$this->add_control(
					'posts_go_pro',
					[
						'type' 	=> Controls_Manager::RAW_HTML,
						'raw' 	=> '<div class="elementor-panel-nerd-box">
										<i class="elementor-panel-nerd-box-icon eicon-hypster"></i>
										<div class="elementor-panel-nerd-box-title">' .
											__( 'Oups, hang on!', 'elementor-extras' ) .
										'</div>
										<div class="elementor-panel-nerd-box-message">' .
											__( 'This feature is only available if you have Elementor Pro.', 'elementor-extras' ) .
										'</div>
										<a class="elementor-panel-nerd-box-link elementor-button elementor-button-default elementor-go-pro" href="https://elementor.com/pro/" target="_blank">' .
										__( 'Go Pro', 'elementor-extras' ) .
										'</a>
									</div>',
						'condition'	=> [
							'source'	=> 'posts_pro'
						]
					]
	        	);
			}

			if ( is_elementor_pro_active() ) {

				$this->add_control(
					'posts_per_page',
					[
						'label' 	=> __( 'Posts Per Page', 'elementor-extras' ),
						'type' 		=> Controls_Manager::NUMBER,
						'default' 	=> 6,
						'condition'	=> [
							'source'	=> 'posts'
						]
					]
				);
			}

			$this->add_control(
				'card_links',
				[
					'label' 		=> __( 'Enable Links', 'elementor-extras' ),
					'type' 			=> Controls_Manager::SWITCHER,
					'label_on' 		=> __( 'Yes', 'elementor-extras' ),
					'label_off' 	=> __( 'No', 'elementor-extras' ),
					'return_value' 	=> 'yes',
					'description'   => __( 'Enable links at card level. If you have links inside the content of a card, make sure you have this disabled. Links within links are not allowed.', 'elementor-extras' ),
					'condition'	=> [
						'source'	=> 'posts',
					]
				]
			);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_timeline',
			[
				'label' 	=> __( 'Timeline', 'elementor-extras' ),
				'condition'	=> [
					'source!'	=> 'posts',
				]
			]
		);

			$repeater = new Repeater();

			$repeater->start_controls_tabs( 'items_repeater' );

			$repeater->start_controls_tab( 'tab_content', [ 'label' => __( 'Content', 'elementor-extras' ) ] );

				$repeater->add_control(
					'date',
					[
						'label' 		=> __( 'Date', 'elementor-extras' ),
						'type' 			=> Controls_Manager::TEXT,
						'dynamic'		=> [ 'active' => true ],
						'placeholder' 	=> __( '19 January 2000', 'elementor-extras' ),
					]
				);

				$repeater->add_control(
					'link',
					[
						'label' 		=> __( 'Link', 'elementor-extras' ),
						'description'   => __( 'Enable linking the whole card. If you have links inside the content of this card, make sure you have this disabled. Links within links are not allowed.', 'elementor-extras' ),
						'type' 			=> Controls_Manager::URL,
						'dynamic'		=> [ 'active' => true ],
						'placeholder' 	=> esc_url( home_url( '/' ) ),
						'default' 		=> [
							'url' 		=> '',
						],
					]
				);

				$default_title = '<h2>' . _x( 'The birth of mankind', 'Default title for the content of a hotspot.', 'elementor-extras' ) . '</h2>';
				$default_paragraph = '<p>' . _x( 'Something really big happened around this period of time. It affected all of humanity. That explains everything.', 'Default title for the content of a hotspot.', 'elementor-extras' ) . '</p>';

				$repeater->add_control(
					'content',
					[
						'label' 		=> '',
						'type' 			=> Controls_Manager::WYSIWYG,
						'dynamic'		=> [ 'active' => true ],
						'default' 		=> $default_title . $default_paragraph,
					]
				);

			$repeater->end_controls_tab();

			$repeater->start_controls_tab( 'tab_media', [ 'label' => __( 'Media', 'elementor-extras' ) ] );

				$repeater->add_control(
					'image',
					[
						'label' 	=> __( 'Choose Image', 'elementor-extras' ),
						'dynamic'	=> [ 'active' => true ],
						'type' 		=> Controls_Manager::MEDIA,
					]
				);

				$repeater->add_group_control(
					Group_Control_Image_Size::get_type(),
					[
						'name' 		=> 'image', // Actually its `image_size`
						'label' 	=> __( 'Image Size', 'elementor-extras' ),
						'default' 	=> 'large',
					]
				);

			$repeater->end_controls_tab();

			$repeater->start_controls_tab( 'tab_style', [ 'label' => __( 'Style', 'elementor-extras' ) ] );

				$repeater->add_control(
					'custom_style',
					[
						'label' 		=> __( 'Custom', 'elementor-extras' ),
						'type' 			=> Controls_Manager::SWITCHER,
						'label_on' 		=> __( 'Yes', 'elementor-extras' ),
						'label_off' 	=> __( 'No', 'elementor-extras' ),
						'return_value' 	=> 'yes',
						'description'   => __( 'Set custom styles that will only affect this specific item.', 'elementor-extras' ),
					]
				);

				$repeater->add_control(
					'point_content_type',
					[
						'label' 		=> __( 'Type', 'elementor-extras' ),
						'type' 			=> Controls_Manager::SELECT,
						'default' 		=> '',
						'options' 		=> [
							'' 			=> __( 'Global', 'elementor-extras' ),
							'icons' 	=> __( 'Icon', 'elementor-extras' ),
							'numbers' 	=> __( 'Number', 'elementor-extras' ),
							'letters' 	=> __( 'Letter', 'elementor-extras' ),
						],
						'conditions' => [
							'terms' => [
								[
									'name' 		=> 'custom_style',
									'operator' 	=> '==',
									'value' 	=> 'yes',
								]
							],
						],
					]
				);

				$repeater->add_control(
					'selected_icon',
					[
						'label' 		=> __( 'Point Icon', 'elementor-extras' ),
						'type' 			=> Controls_Manager::ICONS,
						'fa4compatibility' => 'icon',
						'label_block' 	=> false,
						'default'		=> [
							'value' 	=> 'fas fa-calendar-alt',
							'library' 	=> 'fa-solid',
						],
						'conditions' 	=> [
							'terms' 	=> [
								[
									'name' 		=> 'custom_style',
									'operator' 	=> '==',
									'value' 	=> 'yes',
								], [
									'name' 		=> 'point_content_type',
									'operator' 	=> '==',
									'value' 	=> 'icons',
								],
							],
						],
					]
				);

				$repeater->add_control(
					'point_content',
					[
						'label' 		=> __( 'Point Content', 'elementor-extras' ),
						'type' 			=> Controls_Manager::TEXT,
						'conditions' => [
							'terms' => [
								[
									'name' 		=> 'custom_style',
									'operator' 	=> '==',
									'value' 	=> 'yes',
								], [
									'name' 		=> 'point_content_type',
									'operator' 	=> '!==',
									'value' 	=> 'icons',
								], [
									'name' 		=> 'point_content_type',
									'operator' 	=> '!==',
									'value' 	=> '',
								],
							],
						],
					]
				);

				$repeater->add_control(
					'item_default',
					[
						'label' => __( 'Default', 'elementor-extras' ),
						'type' 	=> Controls_Manager::HEADING,
						'conditions' => [
							'terms' => [
								[
									'name' 		=> 'custom_style',
									'operator' 	=> '==',
									'value' 	=> 'yes',
								],
							],
						],
					]
				);

					$repeater->add_control(
						'icon_color',
						[
							'label' 	=> __( 'Point Color', 'elementor-extras' ),
							'type' 		=> Controls_Manager::COLOR,
							'default'	=> '',
							'selectors' => [
								'{{WRAPPER}} {{CURRENT_ITEM}} .timeline-item__point' => 'color: {{VALUE}};',
							],
							'conditions' => [
								'terms' => [
									[
										'name' 		=> 'custom_style',
										'operator' 	=> '==',
										'value' 	=> 'yes',
									],
								],
							],
						]
					);

					$repeater->add_control(
						'point_background',
						[
							'label' 		=> __( 'Point Background', 'elementor-extras' ),
							'type' 			=> Controls_Manager::COLOR,
							'default'		=> '',
							'selectors' 	=> [
								'{{WRAPPER}} .ee-timeline {{CURRENT_ITEM}} .timeline-item__point' => 'background-color: {{VALUE}};',
							],
							'conditions' => [
								'terms' => [
									[
										'name' 		=> 'custom_style',
										'operator' 	=> '==',
										'value' 	=> 'yes',
									],
								],
							],
						]
					);

					$repeater->add_control(
						'card_background',
						[
							'label' 		=> __( 'Card Background', 'elementor-extras' ),
							'type' 			=> Controls_Manager::COLOR,
							'default'		=> '',
							'selectors' 	=> [
								'{{WRAPPER}} {{CURRENT_ITEM}}.timeline-item .timeline-item__card' => 'background-color: {{VALUE}};',
								'{{WRAPPER}} {{CURRENT_ITEM}}.timeline-item .timeline-item__card__arrow::after' => 'border-left-color: {{VALUE}}; border-right-color: {{VALUE}};',
							],
							'conditions' => [
								'terms' => [
									[
										'name' 		=> 'custom_style',
										'operator' 	=> '==',
										'value' 	=> 'yes',
									],
								],
							],
						]
					);

					$repeater->add_control(
						'date_color',
						[
							'label' 	=> __( 'Date Color', 'elementor-extras' ),
							'type' 		=> Controls_Manager::COLOR,
							'default'	=> '',
							'selectors' => [
								'{{WRAPPER}} {{CURRENT_ITEM}}.timeline-item .timeline-item__meta' => 'color: {{VALUE}};',
							],
							'conditions' => [
								'terms' => [
									[
										'name' 		=> 'custom_style',
										'operator' 	=> '==',
										'value' 	=> 'yes',
									],
								],
							],
						]
					);

					$repeater->add_control(
						'point_size',
						[
							'label' 		=> __( 'Scale', 'elementor-extras' ),
							'type' 			=> Controls_Manager::SLIDER,
							'default' 		=> [
								'size' 		=> '',
							],
							'range' 		=> [
								'px' 		=> [
									'min' 	=> 0.5,
									'max' 	=> 2,
									'step'	=> 0.01
								],
							],
							'selectors' => [
								// Item
								'{{WRAPPER}} {{CURRENT_ITEM}}.timeline-item .timeline-item__point' => 'transform: scale({{SIZE}})',
							],
							'conditions' => [
								'terms' => [
									[
										'name' 		=> 'custom_style',
										'operator' 	=> '==',
										'value' 	=> 'yes',
									],
								],
							],
						]
					);

				$repeater->add_control(
					'item_hover',
					[
						'label' => __( 'Hover', 'elementor-extras' ),
						'type' 	=> Controls_Manager::HEADING,
						'conditions' => [
							'terms' => [
								[
									'name' 		=> 'custom_style',
									'operator' 	=> '==',
									'value' 	=> 'yes',
								],
							],
						],
					]
				);

					$repeater->add_control(
						'icon_color_hover',
						[
							'label' 	=> __( 'Hovered Point Color', 'elementor-extras' ),
							'type' 		=> Controls_Manager::COLOR,
							'default'	=> '',
							'selectors' => [
								'{{WRAPPER}} {{CURRENT_ITEM}}.timeline-item:hover .timeline-item__point,
								{{WRAPPER}} {{CURRENT_ITEM}}.timeline-item.is--focused:hover .timeline-item__point' => 'color: {{VALUE}};',
							],
							'conditions' => [
								'terms' => [
									[
										'name' 		=> 'custom_style',
										'operator' 	=> '==',
										'value' 	=> 'yes',
									],
								],
							],
						]
					);

					$repeater->add_control(
						'point_background_hover',
						[
							'label' 		=> __( 'Hovered Point Background', 'elementor-extras' ),
							'type' 			=> Controls_Manager::COLOR,
							'default'		=> '',
							'selectors' 	=> [
								'{{WRAPPER}} {{CURRENT_ITEM}}.timeline-item:hover .timeline-item__point,
								{{WRAPPER}} {{CURRENT_ITEM}}.timeline-item.is--focused:hover .timeline-item__point' => 'background-color: {{VALUE}};',
							],
							'conditions' 	=> [
								'terms' 	=> [
									[
										'name' 		=> 'custom_style',
										'operator' 	=> '==',
										'value' 	=> 'yes',
									],
								],
							],
						]
					);

					$repeater->add_control(
						'card_background_hover',
						[
							'label' 		=> __( 'Hovered Card Background', 'elementor-extras' ),
							'type' 			=> Controls_Manager::COLOR,
							'default'		=> '',
							'selectors' 	=> [
								'{{WRAPPER}} {{CURRENT_ITEM}}.timeline-item:hover .timeline-item__card,
								 {{WRAPPER}} {{CURRENT_ITEM}}.timeline-item.is--focused:hover .timeline-item__card' => 'background-color: {{VALUE}};',
								'{{WRAPPER}} {{CURRENT_ITEM}}.timeline-item:hover .timeline-item__card__arrow::after,
								 {{WRAPPER}} {{CURRENT_ITEM}}.timeline-item.is--focused:hover .timeline-item__card__arrow::after' => 'border-left-color: {{VALUE}}; border-right-color: {{VALUE}};',
							],
							'conditions' => [
								'terms' => [
									[
										'name' 		=> 'custom_style',
										'operator' 	=> '==',
										'value' 	=> 'yes',
									],
								],
							],
						]
					);

					$repeater->add_control(
						'date_color_hover',
						[
							'label' 	=> __( 'Hovered Date Color', 'elementor-extras' ),
							'type' 		=> Controls_Manager::COLOR,
							'default'	=> '',
							'selectors' => [
								'{{WRAPPER}} {{CURRENT_ITEM}}.timeline-item:hover .timeline-item__meta,
								{{WRAPPER}} {{CURRENT_ITEM}}.timeline-item.is--focused:hover .timeline-item__meta' => 'color: {{VALUE}};',
							],
							'conditions' => [
								'terms' => [
									[
										'name' 		=> 'custom_style',
										'operator' 	=> '==',
										'value' 	=> 'yes',
									],
								],
							],
						]
					);

					$repeater->add_control(
						'point_size_hover',
						[
							'label' 		=> __( 'Hovered Point Scale', 'elementor-extras' ),
							'type' 			=> Controls_Manager::SLIDER,
							'default' 		=> [
								'size' 		=> '',
							],
							'range' 		=> [
								'px' 		=> [
									'min' 	=> 0.5,
									'max' 	=> 2,
									'step'	=> 0.01
								],
							],
							'selectors' => [
								// Item
								'{{WRAPPER}} {{CURRENT_ITEM}}.timeline-item:hover .timeline-item__point,
								{{WRAPPER}} {{CURRENT_ITEM}}.timeline-item.is--focused:hover .timeline-item__point' => 'transform: scale({{SIZE}})',
							],
							'conditions' => [
								'terms' => [
									[
										'name' 		=> 'custom_style',
										'operator' 	=> '==',
										'value' 	=> 'yes',
									],
								],
							],
						]
					);

				$repeater->add_control(
					'item_focused',
					[
						'label' => __( 'Focused', 'elementor-extras' ),
						'type' 	=> Controls_Manager::HEADING,
						'conditions' => [
							'terms' => [
								[
									'name' 		=> 'custom_style',
									'operator' 	=> '==',
									'value' 	=> 'yes',
								],
							],
						],
					]
				);

					$repeater->add_control(
						'icon_color_focused',
						[
							'label' 	=> __( 'Focused Point Color', 'elementor-extras' ),
							'type' 		=> Controls_Manager::COLOR,
							'default'	=> '',
							'selectors' => [
								'{{WRAPPER}} {{CURRENT_ITEM}}.timeline-item.is--focused .timeline-item__point' => 'color: {{VALUE}};',
							],
							'conditions' => [
								'terms' => [
									[
										'name' 		=> 'custom_style',
										'operator' 	=> '==',
										'value' 	=> 'yes',
									],
								],
							],
						]
					);

					$repeater->add_control(
						'point_background_focused',
						[
							'label' 		=> __( 'Focused Point Background', 'elementor-extras' ),
							'type' 			=> Controls_Manager::COLOR,
							'default'		=> '',
							'selectors' 	=> [
								'{{WRAPPER}} .ee-timeline {{CURRENT_ITEM}}.timeline-item.is--focused .timeline-item__point' => 'background-color: {{VALUE}};',
							],
							'conditions' 	=> [
								'terms' 	=> [
									[
										'name' 		=> 'custom_style',
										'operator' 	=> '==',
										'value' 	=> 'yes',
									],
								],
							],
						]
					);

					$repeater->add_control(
						'card_background_focused',
						[
							'label' 		=> __( 'Focused Card Background', 'elementor-extras' ),
							'type' 			=> Controls_Manager::COLOR,
							'default'		=> '',
							'selectors' 	=> [
								'{{WRAPPER}} {{CURRENT_ITEM}}.timeline-item.is--focused .timeline-item__card' => 'background-color: {{VALUE}};',
								'{{WRAPPER}} {{CURRENT_ITEM}}.timeline-item.is--focused .timeline-item__card__arrow::after' => 'border-left-color: {{VALUE}}; border-right-color: {{VALUE}};',
							],
							'conditions' => [
								'terms' => [
									[
										'name' 		=> 'custom_style',
										'operator' 	=> '==',
										'value' 	=> 'yes',
									],
								],
							],
						]
					);

					$repeater->add_control(
						'date_color_focused',
						[
							'label' 	=> __( 'Focused Date Color', 'elementor-extras' ),
							'type' 		=> Controls_Manager::COLOR,
							'default'	=> '',
							'selectors' => [
								'{{WRAPPER}} {{CURRENT_ITEM}}.timeline-item.is--focused .timeline-item__meta' => 'color: {{VALUE}};',
							],
							'conditions' => [
								'terms' => [
									[
										'name' 		=> 'custom_style',
										'operator' 	=> '==',
										'value' 	=> 'yes',
									],
								],
							],
						]
					);

					$repeater->add_control(
						'point_size_focused',
						[
							'label' 		=> __( 'Focused Point Scale', 'elementor-extras' ),
							'type' 			=> Controls_Manager::SLIDER,
							'default' 		=> [
								'size' 		=> '',
							],
							'range' 		=> [
								'px' 		=> [
									'min' 	=> 0.5,
									'max' 	=> 2,
									'step'	=> 0.01
								],
							],
							'selectors' => [
								// Item
								'{{WRAPPER}} {{CURRENT_ITEM}}.timeline-item.is--focused .timeline-item__point' => 'transform: scale({{SIZE}})',
							],
							'conditions' => [
								'terms' => [
									[
										'name' 		=> 'custom_style',
										'operator' 	=> '==',
										'value' 	=> 'yes',
									],
								],
							],
						]
					);

			$repeater->end_controls_tab();

			$repeater->end_controls_tabs();

			$this->add_control(
				'items',
				[
					'label' 	=> __( 'Items', 'elementor-extras' ),
					'type' 		=> Controls_Manager::REPEATER,
					'default' 	=> [
						[
							'date' => __( 'February 2, 2014', 'elementor-extras' )
						],
						[
							'date' => __( 'May 10, 2015', 'elementor-extras' )
						],
						[
							'date' => __( 'June 21, 2016', 'elementor-extras' )
						],
					],
					'fields' 		=> array_values( $repeater->get_controls() ),
					'title_field' 	=> '{{{ date }}}',
					'condition'		=> [
						'source'	=> 'custom'
					]
				]
			);

		$this->end_controls_section();

		if ( is_elementor_pro_active() ) {

		$this->start_controls_section(
			'section_query',
			[
				'label' 	=> __( 'Query', 'elementor-extras' ),
				'tab' 		=> Controls_Manager::TAB_CONTENT,
				'condition'	=> [
					'source'	=> 'posts'
				]
			]
		);

			$this->add_group_control(
				Group_Control_Posts::get_type(),
				[
					'name' 	=> 'posts',
					'label' => __( 'Posts', 'elementor-extras' ),
				]
			);

			$this->add_control(
				'advanced',
				[
					'label' => __( 'Advanced', 'elementor-extras' ),
					'type' 	=> Controls_Manager::HEADING,
				]
			);

			$this->add_control(
				'orderby',
				[
					'label' 	=> __( 'Order By', 'elementor-extras' ),
					'type' 		=> Controls_Manager::SELECT,
					'default' 	=> 'post_date',
					'options' 	=> [
						'post_date' 	=> __( 'Date', 'elementor-extras' ),
						'post_title' 	=> __( 'Title', 'elementor-extras' ),
						'menu_order' 	=> __( 'Menu Order', 'elementor-extras' ),
						'rand' 			=> __( 'Random', 'elementor-extras' ),
					],
				]
			);

			$this->add_control(
				'order',
				[
					'label' 	=> __( 'Order', 'elementor-extras' ),
					'type' 		=> Controls_Manager::SELECT,
					'default' 	=> 'desc',
					'options' 	=> [
						'asc' 	=> __( 'ASC', 'elementor-extras' ),
						'desc' 	=> __( 'DESC', 'elementor-extras' ),
					],
				]
			);

			$this->add_control(
				'offset',
				[
					'label' 		=> __( 'Offset', 'elementor-extras' ),
					'type' 			=> Controls_Manager::NUMBER,
					'default' 		=> 0,
					'condition' 	=> [
						'posts_post_type!' => 'by_id',
					],
				]
			);

			$this->add_control(
				'post_drafts',
				[
					'label' 		=> __( 'Include Drafts', 'elementor-extras' ),
					'type' 			=> Controls_Manager::SWITCHER,
					'default'		=> '',
					'return_value' 	=> 'yes',
				]
			);

			$this->add_control(
				'post_scheduled',
				[
					'label' 		=> __( 'Include Scheduled', 'elementor-extras' ),
					'type' 			=> Controls_Manager::SWITCHER,
					'default'		=> '',
					'return_value' 	=> 'yes',
				]
			);

			Query_Control::add_exclude_controls( $this );

			$this->add_control(
				'sticky_posts',
				[
					'label' 		=> __( 'Sticky Posts', 'elementor-extras' ),
					'type' 			=> Controls_Manager::SWITCHER,
					'default'		=> '',
					'return_value' 	=> 'yes',
				]
			);

		$this->end_controls_section();

		}

		$this->start_controls_section(
			'section_posts',
			[
				'label' 	=> __( 'Posts', 'elementor-extras' ),
				'tab' 		=> Controls_Manager::TAB_CONTENT,
				'condition'	=> [
					'source'			=> 'posts',
				]
			]
		);

			$this->add_control(
				'post_thumbnail',
				[
					'label' 		=> __( 'Show Image', 'elementor-extras' ),
					'type' 			=> Controls_Manager::SWITCHER,
					'default'		=> 'yes',
					'label_on' 		=> __( 'Yes', 'elementor-extras' ),
					'label_off' 	=> __( 'No', 'elementor-extras' ),
					'return_value' 	=> 'yes',
					'condition'		=> [
						'source'	=> 'posts',
					]
				]
			);

			$this->add_group_control(
				Group_Control_Image_Size::get_type(),
				[
					'name' 			=> 'post_thumbnail_size',
					'label' 		=> __( 'Image Size', 'elementor-extras' ),
					'default' 		=> 'medium',
					'prefix_class' 	=> 'elementor-portfolio--thumbnail-size-',
					'condition'		=> [
						'source'			=> 'posts',
						'post_thumbnail'	=> 'yes'
					]
				]
			);

			// $this->add_control(
			// 	'post_thumbnail_placement',
			// 	[
			// 		'label'   => __( 'Image Placement', 'elementor-extras' ),
			// 		'type'    => Controls_Manager::SELECT,
			// 		'default' => '',
			// 		'options' => [
			// 			'' 			=> __( 'Default', 'elementor-extras' ),
			// 			'opposite' 	=> __( 'Opposite', 'elementor-extras' ),
			// 		],
			// 		'condition'		=> [
			// 			'source'			=> 'posts',
			// 			'post_thumbnail'	=> 'yes'
			// 		],
			// 	]
			// );

			$this->add_control(
				'date_format',
				[
					'label'   => __( 'Date Format', 'elementor-extras' ),
					'type'    => Controls_Manager::SELECT,
					'options' => [
						'default' 	=> __( 'Default', 'elementor-extras' ),
						'' 			=> __( 'None', 'elementor-extras' ),
						'F j, Y' 	=> date( 'F j, Y' ),
						'Y-m-d' 	=> date( 'Y-m-d' ),
						'm/d/Y' 	=> date( 'm/d/Y' ),
						'd/m/Y' 	=> date( 'd/m/Y' ),
						'custom' 	=> __( 'Custom', 'elementor-extras' ),
					],
					'condition'		=> [
						'source'	=> 'posts',
					],
					'default' => 'default',
				]
			);

			$this->add_control(
				'time_format',
				[
					'label'   => __( 'Time Format', 'elementor-extras' ),
					'type'    => Controls_Manager::SELECT,
					'options' => [
						'default' 	=> __( 'Default', 'elementor-extras' ),
						'' 			=> __( 'None', 'elementor-extras' ),
						'g:i a' 	=> date( 'g:i a' ),
						'g:i A' 	=> date( 'g:i A' ),
						'H:i' 		=> date( 'H:i' ),
					],
					'default' 	=> 'default',
					'condition' => [
						'source'		=> 'posts',
						'date_format!' 	=> 'custom',
					],
				]
			);

			$this->add_control(
				'date_custom_format',
				[
					'label'   			=> __( 'Custom Format', 'elementor-extras' ),
					'default' 			=> get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
					'description' 		=> sprintf( '<a href="https://codex.wordpress.org/Formatting_Date_and_Time" target="_blank">%s</a>', __( 'Documentation on date and time formatting', 'elementor-extras' ) ),
					'condition' 		=> [
						'source'		=> 'posts',
						'date_format' 	=> 'custom',
					],
				]
			);

			$this->add_control(
				'post_title',
				[
					'label' 		=> __( 'Show Title', 'elementor-extras' ),
					'type' 			=> Controls_Manager::SWITCHER,
					'default'		=> 'yes',
					'label_on' 		=> __( 'Yes', 'elementor-extras' ),
					'label_off' 	=> __( 'No', 'elementor-extras' ),
					'return_value' 	=> 'yes',
					'condition'		=> [
						'source'	=> 'posts',
					]
				]
			);

			$this->add_control(
				'post_excerpt',
				[
					'label'   => __( 'Excerpt', 'elementor-extras' ),
					'type'    => Controls_Manager::SELECT,
					'options' => [
						'' 			=> __( 'Hide', 'elementor-extras' ),
						'yes' 		=> __( 'Post Excerpt', 'elementor-extras' ),
						'content' 	=> __( 'Post Content', 'elementor-extras' ),
					],
					'default' 		=> 'yes',
					'condition'		=> [
						'source'	=> 'posts',
					],
				]
			);

			$this->add_control(
				'post_excerpt_length',
				[
					'label' 	=> __( 'Excerpt Length', 'elementor-extras' ),
					'type' 		=> Controls_Manager::NUMBER,
					'default' 	=> apply_filters( 'excerpt_length', 25 ),
					'condition' => [
						'post_excerpt' => 'yes',
					],
				]
			);

			if ( is_woocommerce_active() ) {

				$this->add_control(
					'post_product_heading',
					[
						'label' => __( 'Products', 'elementor-extras' ),
						'type' 	=> Controls_Manager::HEADING,
						'condition'		=> [
							'source'			=> 'posts',
							'posts_post_type'	=> 'product'
						]
					]
				);

				$this->add_control(
					'post_buy',
					[
						'label' 		=> __( 'Buy Button', 'elementor-extras' ),
						'type' 			=> Controls_Manager::SWITCHER,
						'default'		=> 'yes',
						'label_on' 		=> __( 'Yes', 'elementor-extras' ),
						'label_off' 	=> __( 'No', 'elementor-extras' ),
						'return_value' 	=> 'yes',
						'condition'		=> [
							'card_links!'		=> 'yes',
							'posts_post_type'	=> 'product'
						]
					]
				);

				$this->add_control(
					'post_product_attributes',
					[
						'label' 		=> __( 'Show Attributes', 'elementor-extras' ),
						'type' 			=> Controls_Manager::SWITCHER,
						'default'		=> 'yes',
						'label_on' 		=> __( 'Yes', 'elementor-extras' ),
						'label_off' 	=> __( 'No', 'elementor-extras' ),
						'return_value' 	=> 'yes',
						'condition'		=> [
							'source'			=> 'posts',
							'posts_post_type'	=> 'product'
						]
					]
				);



				$this->add_control(
					'post_product_attributes_exclude',
					[
						'label' 		=> __( 'Exclude attributes', 'elementor-extras' ),
						'description'	=> __( 'Enter attribute slugs, names or ids, separated by commas', 'elementor-extras' ),
						'type' 			=> Controls_Manager::TEXT,
						'condition'		=> [
							'source'			=> 'posts',
							'posts_post_type'	=> 'product'
						]
					]
				);

			}

		$this->end_controls_section();

		$this->start_controls_section(
			'section_layout',
			[
				'label' => __( 'Layout', 'elementor-extras' ),
				'tab' 		=> Controls_Manager::TAB_STYLE,
			]
		);

			$this->add_responsive_control(
				'align',
				[
					'label' 		=> __( 'Horizontal Align', 'elementor-extras' ),
					'type' 			=> Controls_Manager::SELECT,
					'label_block'	=> true,
					'default'		=> 'center',
					'tablet_default'=> 'left',
					'mobile_default'=> 'left',
					'options' 		=> [
						'left' 		=> __( 'Left', 'elementor-extras' ),
						'center' 	=> __( 'Center', 'elementor-extras' ),
						'overlay' 	=> __( 'Overlay', 'elementor-extras' ),
						'right' 	=> __( 'Right', 'elementor-extras' ),
					],
					'prefix_class' 	=> 'ee-timeline-align%s--',
				]
			);

			$this->add_responsive_control(
				'cards_align',
				[
					'label' 		=> __( 'Vertical Align', 'elementor-extras' ),
					'type' 			=> Controls_Manager::SELECT,
					'label_block'	=> true,
					'default'		=> 'top',
					'tablet_default'=> 'top',
					'mobile_default'=> 'top',
					'options' 		=> [
						'top' 		=> __( 'Top', 'elementor-extras' ),
						'middle' 	=> __( 'Middle', 'elementor-extras' ),
						'bottom' 	=> __( 'Bottom', 'elementor-extras' ),
					],
					'prefix_class' 	=> 'ee-timeline-cards-align%s--',
				]
			);

			$this->add_responsive_control(
				'horizontal_spacing',
				[
					'label' 	=> __( 'Horizontal Spacing', 'elementor-extras' ),
					'type' 		=> Controls_Manager::SLIDER,
					'default' 	=> [
						'size' 	=> '',
					],
					'range' 		=> [
						'px' 		=> [
							'min' 	=> 0,
							'max' 	=> 100,
						],
					],
					'selectors' => [
						'{{WRAPPER}} .ee-timeline .timeline-item .timeline-item__point' 	=> 'margin-left: {{SIZE}}px; margin-right: {{SIZE}}px;',
					],
				]
			);

			$this->add_responsive_control(
				'vertical_spacing',
				[
					'label' 	=> __( 'Vertical Spacing', 'elementor-extras' ),
					'type' 		=> Controls_Manager::SLIDER,
					'default' 	=> [
						'size' 	=> '',
					],
					'range' 		=> [
						'px' 		=> [
							'min' 	=> 0,
							'max' 	=> 100,
						],
					],
					'selectors' => [
						'{{WRAPPER}} .ee-timeline .ee-timeline__item,
						 {{WRAPPER}}.ee-timeline-align--overlay .timeline-item__point' => 'margin-bottom: {{SIZE}}px;',
					],
				]
			);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_images',
			[
				'label' 		=> __( 'Images', 'elementor-extras' ),
				'tab' 			=> Controls_Manager::TAB_STYLE,
			]
		);

			$this->add_control(
				'cards_images_align',
				[
					'label' 		=> __( 'Alignment', 'elementor-extras' ),
					'type' 			=> Controls_Manager::CHOOSE,
					'default'		=> 'left',
					'options' 		=> [
						'left' 		=> [
							'title' => __( 'Left', 'elementor-extras' ),
							'icon' 	=> 'eicon-h-align-left',
						],
						'center' 	=> [
							'title' => __( 'Center', 'elementor-extras' ),
							'icon' 	=> 'eicon-h-align-center',
						],
						'right' 	=> [
							'title' => __( 'Right', 'elementor-extras' ),
							'icon' 	=> 'eicon-h-align-right',
						],
					],
					'selectors' => [
						'{{WRAPPER}} .ee-timeline .timeline-item .timeline-item__img' 	=> 'text-align: {{VALUE}};',
					],
				]
			);

			$this->add_control(
				'cards_images_spacing',
				[
					'label' 	=> __( 'Spacing', 'elementor-extras' ),
					'type' 		=> Controls_Manager::SLIDER,
					'default' 	=> [
						'size' 	=> '',
					],
					'range' 		=> [
						'px' 		=> [
							'min' 	=> 0,
							'max' 	=> 100,
						],
					],
					'selectors' => [
						'{{WRAPPER}} .ee-timeline .timeline-item .timeline-item__img' 	=> 'margin-bottom: {{SIZE}}px;',
					],
				]
			);

			$this->add_control(
				'images_border_radius',
				[
					'label' 		=> __( 'Border Radius', 'elementor-extras' ),
					'type' 			=> Controls_Manager::DIMENSIONS,
					'size_units' 	=> [ 'px', '%' ],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-timeline .timeline-item__img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
				]
			);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_posts_style',
			[
				'label' 		=> __( 'Posts', 'elementor-extras' ),
				'tab' 			=> Controls_Manager::TAB_STYLE,
				'condition'		=> [
					'source'	=> 'posts',
				]
			]
		);

			$this->add_control(
				'titles_heading',
				[
					'label' => __( 'Title', 'elementor-extras' ),
					'type' 	=> Controls_Manager::HEADING,
					'condition'			=> [
						'source'		=> 'posts',
						'post_title!' 	=> '',
					]
				]
			);

			$this->add_control(
				'title_color',
				[
					'label' 	=> __( 'Title Color', 'elementor-extras' ),
					'type' 		=> Controls_Manager::COLOR,
					'default'	=> '',
					'selectors' => [
						'{{WRAPPER}} .ee-timeline .timeline-item__title a' => 'color: {{VALUE}};',
					],
					'condition'			=> [
						'source'		=> 'posts',
						'post_title!' 	=> '',
					],
				]
			);

			$this->add_group_control(
				Group_Control_Typography::get_type(),
				[
					'name' 		=> 'titles_typography',
					'selector' 	=> '{{WRAPPER}} .ee-timeline .timeline-item__title',
					'scheme' 	=> Scheme_Typography::TYPOGRAPHY_3,
					'condition'			=> [
						'source'		=> 'posts',
						'post_title!' 	=> '',
					],
				]
			);

			$this->add_control(
				'titles_spacing',
				[
					'label' 	=> __( 'Spacing', 'elementor-extras' ),
					'type' 		=> Controls_Manager::SLIDER,
					'default' 	=> [
						'size' 	=> '',
					],
					'range' 		=> [
						'px' 		=> [
							'min' 	=> 0,
							'max' 	=> 100,
						],
					],
					'selectors' => [
						'{{WRAPPER}} .ee-timeline .timeline-item__title' 	=> 'margin-bottom: {{SIZE}}px;',
					],
					'condition'			=> [
						'source'		=> 'posts',
						'post_title!' 	=> '',
					],
				]
			);

			$this->add_control(
				'excerpt_heading',
				[
					'label' => __( 'Excerpt', 'elementor-extras' ),
					'type' 	=> Controls_Manager::HEADING,
					'condition'			=> [
						'source'		=> 'posts',
						'post_excerpt!' => '',
					]
				]
			);

			$this->add_control(
				'excerpt_color',
				[
					'label' 	=> __( 'Excerpt Color', 'elementor-extras' ),
					'type' 		=> Controls_Manager::COLOR,
					'default'	=> '',
					'selectors' => [
						'{{WRAPPER}} .ee-timeline .timeline-item__excerpt' => 'color: {{VALUE}};',
					],
					'condition'			=> [
						'source'		=> 'posts',
						'post_excerpt!' 	=> '',
					],
				]
			);

			$this->add_group_control(
				Group_Control_Typography::get_type(),
				[
					'name' 		=> 'excerpt_typography',
					'selector' 	=> '{{WRAPPER}} .ee-timeline .timeline-item__excerpt',
					'scheme' 	=> Scheme_Typography::TYPOGRAPHY_3,
					'condition'			=> [
						'source'		=> 'posts',
						'post_excerpt!' => '',
					]
				]
			);

			$this->add_responsive_control(
				'excerpt_padding',
				[
					'label' 		=> __( 'Padding', 'elementor-extras' ),
					'type' 			=> Controls_Manager::DIMENSIONS,
					'size_units' 	=> [ 'px' ],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-timeline .timeline-item__excerpt' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
					'condition'			=> [
						'source'		=> 'posts',
						'post_excerpt!' => '',
					],
				]
			);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_cards',
			[
				'label' => __( 'Cards', 'elementor-extras' ),
				'tab' 		=> Controls_Manager::TAB_STYLE,
			]
		);

			$this->add_responsive_control(
				'cards_padding',
				[
					'label' 		=> __( 'Card Padding', 'elementor-extras' ),
					'type' 			=> Controls_Manager::DIMENSIONS,
					'size_units' 	=> [ 'px' ],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-timeline .timeline-item__card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
				]
			);

			$this->add_responsive_control(
				'cards_margin',
				[
					'label' 		=> __( 'Card Margin', 'elementor-extras' ),
					'type' 			=> Controls_Manager::DIMENSIONS,
					'size_units' 	=> [ 'px' ],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-timeline .timeline-item__card' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
				]
			);

			$this->add_responsive_control(
				'cards_content_padding',
				[
					'label' 		=> __( 'Content Padding', 'elementor-extras' ),
					'type' 			=> Controls_Manager::DIMENSIONS,
					'size_units' 	=> [ 'px' ],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-timeline .timeline-item__content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
				]
			);

			$this->add_control(
				'cards_border_radius',
				[
					'label' 		=> __( 'Border Radius', 'elementor-extras' ),
					'type' 			=> Controls_Manager::DIMENSIONS,
					'size_units' 	=> [ 'px', '%' ],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-timeline .timeline-item__card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
				]
			);

			$this->add_control(
				'animate_in',
				[
					'label' 		=> __( 'Animate Cards', 'elementor-extras' ),
					'type' 			=> Controls_Manager::SWITCHER,
					'default'		=> 'yes',
					'label_on' 		=> __( 'Yes', 'elementor-extras' ),
					'label_off' 	=> __( 'No', 'elementor-extras' ),
					'return_value' 	=> 'yes',
				]
			);

			$this->add_group_control(
				Group_Control_Transition::get_type(),
				[
					'name' 		=> 'cards',
					'selector' 	=> '{{WRAPPER}} .timeline-item__card,
							 		{{WRAPPER}} .timeline-item__content__wysiwyg *,
							 		{{WRAPPER}} .timeline-item__title,
							 		{{WRAPPER}} .timeline-item__meta,
							 		{{WRAPPER}} .timeline-item__excerpt,
									{{WRAPPER}} .timeline-item__card__arrow::after',
					'separator'	=> '',
				]
			);

			$this->start_controls_tabs( 'tabs_cards' );

			$this->start_controls_tab( 'tab_cards_default', [ 'label' => __( 'Default', 'elementor-extras' ) ] );

				$this->add_responsive_control(
					'cards_color',
					[
						'label' 	=> __( 'Color', 'elementor-extras' ),
						'type' 		=> Controls_Manager::COLOR,
						'selectors' => [
							'{{WRAPPER}} .timeline-item__card,
							 {{WRAPPER}} .timeline-item__content,
							 {{WRAPPER}} .timeline-item__content *' => 'color: {{VALUE}};',
						],
					]
				);

				$this->add_responsive_control(
					'cards_background_color',
					[
						'label' 	=> __( 'Background Color', 'elementor-extras' ),
						'type' 		=> Controls_Manager::COLOR,
						'selectors' => [
							'{{WRAPPER}} .timeline-item__card' 										=> 'background-color: {{VALUE}};',
							'{{WRAPPER}} .timeline-item__card .timeline-item__card__arrow::after' 	=> 'border-left-color: {{VALUE}}; border-right-color: {{VALUE}};',
						],
					]
				);

				$this->add_group_control(
					Group_Control_Box_Shadow::get_type(),
					[
						'name' 		=> 'cards_box_shadow',
						'selector' 	=> '{{WRAPPER}} .timeline-item__card',
					]
				);

			$this->end_controls_tab();

			$this->start_controls_tab( 'tab_cards_hover', [ 'label' => __( 'Hover', 'elementor-extras' ) ] );

				$this->add_responsive_control(
					'cards_color_hover',
					[
						'label' 	=> __( 'Color', 'elementor-extras' ),
						'type' 		=> Controls_Manager::COLOR,
						'selectors' => [
							'{{WRAPPER}} .timeline-item:hover .timeline-item__card,
							 {{WRAPPER}} .timeline-item:hover .timeline-item__card *' => 'color: {{VALUE}};',
						],
					]
				);

				$this->add_responsive_control(
					'cards_background_color_hover',
					[
						'label' 	=> __( 'Background Color', 'elementor-extras' ),
						'type' 		=> Controls_Manager::COLOR,
						'selectors' => [
							'{{WRAPPER}} .timeline-item:not(.is--focused):hover .timeline-item__card' => 'background-color: {{VALUE}};',
							'{{WRAPPER}} .timeline-item:not(.is--focused):hover .timeline-item__card .timeline-item__card__arrow::after' 	=> 'border-left-color: {{VALUE}}; border-right-color: {{VALUE}};',
						],
					]
				);

				$this->add_group_control(
					Group_Control_Box_Shadow::get_type(),
					[
						'name' 		=> 'cards_box_shadow_hover',
						'selector' 	=> '{{WRAPPER}} .timeline-item:hover .timeline-item__card',
					]
				);

			$this->end_controls_tab();

			$this->start_controls_tab( 'tab_cards_focused', [ 'label' => __( 'Focused', 'elementor-extras' ) ] );

				$this->add_responsive_control(
					'cards_color_focused',
					[
						'label' 	=> __( 'Color', 'elementor-extras' ),
						'type' 		=> Controls_Manager::COLOR,
						'selectors' => [
							'{{WRAPPER}} .timeline-item.is--focused .timeline-item__card,
							 {{WRAPPER}} .timeline-item.is--focused .timeline-item__card *' => 'color: {{VALUE}};',
						],
					]
				);

				$this->add_responsive_control(
					'cards_background_color_focused',
					[
						'label' 	=> __( 'Background Color', 'elementor-extras' ),
						'type' 		=> Controls_Manager::COLOR,
						'selectors' => [
							'{{WRAPPER}} .timeline-item.is--focused .timeline-item__card' 				=> 'background-color: {{VALUE}};',
							'{{WRAPPER}} .timeline-item.is--focused .timeline-item__card__arrow::after' => 'border-left-color: {{VALUE}}; border-right-color: {{VALUE}};',
						],
					]
				);

				$this->add_group_control(
					Group_Control_Box_Shadow::get_type(),
					[
						'name' 		=> 'cards_box_shadow_focused',
						'selector' 	=> '{{WRAPPER}} .timeline-item.is--focused .timeline-item__card',
					]
				);

			$this->end_controls_tab();

			$this->end_controls_tabs();

			$this->add_group_control(
				Group_Control_Text_Shadow::get_type(),
				[
					'name' 		=> 'cards_text_shadow',
					'selector' 	=> '{{WRAPPER}} .ee-timeline .timeline-item__card',
				]
			);

			$this->add_group_control(
				Group_Control_Typography::get_type(),
				[
					'name' 		=> 'cards_typography',
					'selector' 	=> '{{WRAPPER}} .ee-timeline .timeline-item__card',
					'scheme' 	=> Scheme_Typography::TYPOGRAPHY_3,
				]
			);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_dates',
			[
				'label' 	=> __( 'Dates', 'elementor-extras' ),
				'tab' 		=> Controls_Manager::TAB_STYLE,
			]
		);

			$this->add_responsive_control(
				'dates_padding',
				[
					'label' 		=> __( 'Padding', 'elementor-extras' ),
					'type' 			=> Controls_Manager::DIMENSIONS,
					'size_units' 	=> [ 'px' ],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-timeline .timeline-item__meta' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
				]
			);

			$this->add_responsive_control(
				'dates_margin',
				[
					'label' 		=> __( 'Margin', 'elementor-extras' ),
					'type' 			=> Controls_Manager::DIMENSIONS,
					'size_units' 	=> [ 'px' ],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-timeline .timeline-item__meta' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
				]
			);

			$this->add_group_control(
				Group_Control_Text_Shadow::get_type(),
				[
					'name' 		=> 'dates_text_shadow',
					'selector' 	=> '{{WRAPPER}} .ee-timeline .timeline-item__meta',
				]
			);

			$this->add_group_control(
				Group_Control_Typography::get_type(),
				[
					'name' 		=> 'dates_typography',
					'selector' 	=> '{{WRAPPER}} .ee-timeline .timeline-item__meta',
					'scheme' 	=> Scheme_Typography::TYPOGRAPHY_3,
				]
			);

			$this->start_controls_tabs( 'tabs_dates_style' );

			$this->start_controls_tab( 'tab_dates_default', [ 'label' => __( 'Default', 'elementor-extras' ) ] );

				$this->add_responsive_control(
					'dates_color',
					[
						'label' 	=> __( 'Color', 'elementor-extras' ),
						'type' 		=> Controls_Manager::COLOR,
						'selectors' => [
							'{{WRAPPER}} .ee-timeline .timeline-item__meta' => 'color: {{VALUE}};',
						],
					]
				);

			$this->end_controls_tab();

			$this->start_controls_tab( 'tab_dates_hover', [ 'label' => __( 'Hover', 'elementor-extras' ) ] );

				$this->add_responsive_control(
					'dates_color_hover',
					[
						'label' 	=> __( 'Color', 'elementor-extras' ),
						'type' 		=> Controls_Manager::COLOR,
						'selectors' => [
							'{{WRAPPER}} .ee-timeline .ee-timeline__item:hover .timeline-item__meta' => 'color: {{VALUE}};',
						],
					]
				);

			$this->end_controls_tab();

			$this->start_controls_tab( 'tab_dates_focused', [ 'label' => __( 'Focused', 'elementor-extras' ) ] );

				$this->add_responsive_control(
					'dates_color_focused',
					[
						'label' 	=> __( 'Color', 'elementor-extras' ),
						'type' 		=> Controls_Manager::COLOR,
						'selectors' => [
							'{{WRAPPER}} .ee-timeline .ee-timeline__item.is--focused .timeline-item__meta' => 'color: {{VALUE}};',
						],
					]
				);

			$this->end_controls_tab();

			$this->end_controls_tabs();

		$this->end_controls_section();

		$this->start_controls_section(
			'section_points',
			[
				'label' 	=> __( 'Points', 'elementor-extras' ),
				'tab' 		=> Controls_Manager::TAB_STYLE,
			]
		);

			$this->add_control(
				'points_content',
				[
					'label' 		=> __( 'Type', 'elementor-extras' ),
					'type' 			=> Controls_Manager::SELECT,
					'default' 		=> 'icons',
					'options' 		=> [
						'icons' 	=> __( 'Icons', 'elementor-extras' ),
						'numbers' 	=> __( 'Numbers', 'elementor-extras' ),
						'letters' 	=> __( 'Letters', 'elementor-extras' ),
					],
				]
			);

			$this->add_control(
				'selected_global_icon',
				[
					'label' 			=> __( 'Icon', 'elementor-extras' ),
					'type' 				=> Controls_Manager::ICONS,
					'label_block' 		=> true,
					'fa4compatibility' 	=> 'global_icon',
					'default'			=> [
						'value' 		=> 'fas fa-calendar-alt',
						'library' 		=> 'fa-solid',
					],
					'condition'			=> [
						'points_content' => 'icons',
					],
				]
			);

			$this->add_group_control(
				Group_Control_Typography::get_type(),
				[
					'name' 		=> 'points_typography',
					'selector' 	=> '{{WRAPPER}} .ee-timeline .timeline-item__point',
					'scheme' 	=> Scheme_Typography::TYPOGRAPHY_3,
					'exclude'	=> [ 'font_size' ],
					'condition' 	=> [
						'points_content!' => 'icons',
					],
				]
			);

			$this->add_group_control(
				Group_Control_Transition::get_type(),
				[
					'name' 		=> 'points',
					'selector' 	=> '{{WRAPPER}} .timeline-item__point',
					'separator'	=> '',
				]
			);

			$this->start_controls_tabs( 'tabs_points' );

			$this->start_controls_tab( 'points_default', [ 'label' => __( 'Default', 'elementor-extras' ) ] );

				$this->add_responsive_control(
					'points_size',
					[
						'label' 	=> __( 'Size', 'elementor-extras' ),
						'type' 		=> Controls_Manager::SLIDER,
						'default' 	=> [
							'size' 	=> 40,
						],
						'range' 		=> [
							'px' 		=> [
								'min' 	=> 10,
								'max' 	=> 80,
							],
						],
						'selectors' => [
							'{{WRAPPER}} .timeline-item__point' => 'width: {{SIZE}}px; height: {{SIZE}}px',
							'{{WRAPPER}} .timeline-item__card__arrow' => 'height: {{SIZE}}px;',
							'{{WRAPPER}} .ee-timeline-align--left .ee-timeline__line' => 'margin-left: calc( {{SIZE}}px / 2 );',
							'{{WRAPPER}} .ee-timeline-align--right .ee-timeline__line' => 'margin-right: calc( {{SIZE}}px / 2 );',
							'(tablet){{WRAPPER}} .ee-timeline-align--center .ee-timeline__line' => 'margin-left: calc( {{points_size_tablet.SIZE}}px / 2 );',
							'(mobile){{WRAPPER}} .ee-timeline-align--center .ee-timeline__line' => 'margin-left: calc( {{points_size_mobile.SIZE}}px / 2 );',
						],
					]
				);

				$this->add_responsive_control(
					'icons_size',
					[
						'label' 	=> __( 'Icon Size', 'elementor-extras' ),
						'type' 		=> Controls_Manager::SLIDER,
						'default' 	=> [
							'size' 	=> 1,
						],
						'range' 		=> [
							'px' 		=> [
								'step'	=> 0.1,
								'min' 	=> 1,
								'max' 	=> 4,
							],
						],
						'selectors' => [
							'{{WRAPPER}} .timeline-item__point' => 'font-size: {{SIZE}}em',
						],
						'condition'		=> [
							'points_content' => 'icons'
						]
					]
				);

				$this->add_responsive_control(
					'content_size',
					[
						'label' 	=> __( 'Content Size', 'elementor-extras' ),
						'type' 		=> Controls_Manager::SLIDER,
						'default' 	=> [
							'size' 	=> 1,
						],
						'range' 		=> [
							'px' 		=> [
								'min' 	=> 1,
								'max' 	=> 4,
							],
						],
						'selectors' => [
							'{{WRAPPER}} .timeline-item__point__text' => 'font-size: {{SIZE}}em',
						],
						'condition'		=> [
							'points_content!' => 'icons'
						]
					]
				);

				$this->add_control(
					'points_background',
					[
						'label' 	=> __( 'Background Color', 'elementor-extras' ),
						'type' 		=> Controls_Manager::COLOR,
						'scheme' 	=> [
							'type' 		=> Scheme_Color::get_type(),
							'value' 	=> Scheme_Color::COLOR_1,
						],
						'selectors' => [
							'{{WRAPPER}} .timeline-item .timeline-item__point' 	=> 'background-color: {{VALUE}};',
						],
					]
				);

				$this->add_control(
					'icons_color',
					[
						'label' 	=> __( 'Points Color', 'elementor-extras' ),
						'type' 		=> Controls_Manager::COLOR,
						'default'	=> '',
						'selectors' => [
							'{{WRAPPER}} .timeline-item .timeline-item__point' 	=> 'color: {{VALUE}};',
						],
					]
				);

				$this->add_group_control(
					Group_Control_Text_Shadow::get_type(),
					[
						'name' 		=> 'points_text_shadow',
						'selector' 	=> '{{WRAPPER}} .timeline-item .timeline-item__point',
					]
				);

			$this->end_controls_tab();

			$this->start_controls_tab( 'points_hover', [ 'label' => __( 'Hover', 'elementor-extras' ) ] );

				$this->add_control(
					'points_size_hover',
					[
						'label' 	=> __( 'Scale', 'elementor-extras' ),
						'type' 		=> Controls_Manager::SLIDER,
						'default' 	=> [
							'size' 	=> 1,
						],
						'range' 		=> [
							'px' 		=> [
								'min' 	=> 0.5,
								'max' 	=> 2,
								'step'	=> 0.01
							],
						],
						'selectors' => [
							// General
							'{{WRAPPER}} .timeline-item:not(.is--focused):hover .timeline-item__point,
							{{WRAPPER}} .timeline-item:not(.is--focused):hover .timeline-item__point' => 'transform: scale({{SIZE}})',
						],
					]
				);

				$this->add_control(
					'points_background_hover',
					[
						'label' 	=> __( 'Background Color', 'elementor-extras' ),
						'type' 		=> Controls_Manager::COLOR,
						'default'	=> '',
						'selectors' => [
							'{{WRAPPER}} .timeline-item:not(.is--focused):hover .timeline-item__point,
							 {{WRAPPER}} .timeline-item:not(.is--focused):hover .timeline-item__point' 			=> 'background-color: {{VALUE}};',
						],
					]
				);

				$this->add_control(
					'icons_color_hover',
					[
						'label' 	=> __( 'Points Color', 'elementor-extras' ),
						'type' 		=> Controls_Manager::COLOR,
						'default'	=> '',
						'selectors' => [
							'{{WRAPPER}} .timeline-item:not(.is--focused):hover .timeline-item__point,
							 {{WRAPPER}} .timeline-item:not(.is--focused):hover .timeline-item__point' 		=> 'color: {{VALUE}};',
						],
					]
				);

				$this->add_group_control(
					Group_Control_Text_Shadow::get_type(),
					[
						'name' 		=> 'points_text_shadow_hover',
						'selector' 	=> '{{WRAPPER}} .timeline-item:not(.is--focused):hover .timeline-item__point, {{WRAPPER}} .timeline-item:hover .timeline-item__point'
					]
				);

			$this->end_controls_tab();

			$this->start_controls_tab( 'points_focused', [ 'label' => __( 'Focused', 'elementor-extras' ) ] );

				$this->add_control(
					'points_size_focused',
					[
						'label' 	=> __( 'Scale', 'elementor-extras' ),
						'type' 		=> Controls_Manager::SLIDER,
						'default' 	=> [
							'size' 	=> 1,
						],
						'range' 		=> [
							'px' 		=> [
								'min' 	=> 0.5,
								'max' 	=> 2,
								'step'	=> 0.01
							],
						],
						'selectors' => [
							'{{WRAPPER}} .timeline-item.is--focused .timeline-item__point' 		=> 'transform: scale({{SIZE}})',
						],
					]
				);

				$this->add_control(
					'points_background_focused',
					[
						'label' 	=> __( 'Background Color', 'elementor-extras' ),
						'type' 		=> Controls_Manager::COLOR,
						'scheme' 	=> [
							'type' 		=> Scheme_Color::get_type(),
							'value' 	=> Scheme_Color::COLOR_4,
						],
						'selectors' => [
							'{{WRAPPER}} .timeline-item.is--focused .timeline-item__point' 			=> 'background-color: {{VALUE}};',
						],
					]
				);

				$this->add_control(
					'icons_color_focused',
					[
						'label' 	=> __( 'Points Color', 'elementor-extras' ),
						'type' 		=> Controls_Manager::COLOR,
						'default'	=> '',
						'selectors' => [
							'{{WRAPPER}} .timeline-item.is--focused .timeline-item__point' 		=> 'color: {{VALUE}};',
						],
					]
				);

				$this->add_group_control(
					Group_Control_Text_Shadow::get_type(),
					[
						'name' 		=> 'points_text_shadow_focused',
						'selector' 	=> '{{WRAPPER}} .timeline-item.is--focused .timeline-item__point',
					]
				);

			$this->end_controls_tab();

			$this->end_controls_tabs();

		$this->end_controls_section();

		$this->start_controls_section(
			'section_line',
			[
				'label' 	=> __( 'Line', 'elementor-extras' ),
				'tab' 		=> Controls_Manager::TAB_STYLE,
			]
		);

			$this->add_control(
				'line_background',
				[
					'label' 	=> __( 'Background Color', 'elementor-extras' ),
					'type' 		=> Controls_Manager::COLOR,
					'scheme' 	=> [
						'type' 		=> Scheme_Color::get_type(),
						'value' 	=> Scheme_Color::COLOR_1,
					],
					'selectors' => [
						'{{WRAPPER}} .ee-timeline__line' => 'background-color: {{VALUE}};',
					],
				]
			);

			$this->add_control(
				'progress_background',
				[
					'label' 	=> __( 'Progress Color', 'elementor-extras' ),
					'type' 		=> Controls_Manager::COLOR,
					'scheme' 	=> [
						'type' 		=> Scheme_Color::get_type(),
						'value' 	=> Scheme_Color::COLOR_4,
					],
					'selectors' => [
						'{{WRAPPER}} .ee-timeline__line__inner' => 'background-color: {{VALUE}};',
					],
				]
			);

			$this->add_control(
				'line_thickness',
				[
					'label' 	=> __( 'Thickness', 'elementor-extras' ),
					'type' 		=> Controls_Manager::SLIDER,
					'default' 	=> [
						'size' 	=> 4,
					],
					'range' 		=> [
						'px' 		=> [
							'min' 	=> 1,
							'max' 	=> 8,
						],
					],
					'selectors' => [
						'{{WRAPPER}} .ee-timeline__line' => 'width: {{SIZE}}px;',
					],
				]
			);

			$this->add_control(
				'line_location',
				[
					'label' 	=> __( 'Location', 'elementor-extras' ),
					'type' 		=> Controls_Manager::SLIDER,
					'default' 	=> [
						'size' 	=> 50,
					],
					'range' 		=> [
						'px' 		=> [
							'min' 	=> 0,
							'max' 	=> 100,
						],
					],
					'frontend_available' => true,
				]
			);

			$this->add_group_control(
				Group_Control_Border::get_type(),
				[
					'name' 		=> 'line_border',
					'label' 	=> __( 'Image Border', 'elementor-extras' ),
					'selector' 	=> '{{WRAPPER}} .ee-timeline__line',
				]
			);

			$this->add_control(
				'line_border_radius',
				[
					'label' 		=> __( 'Border Radius', 'elementor-extras' ),
					'type' 			=> Controls_Manager::DIMENSIONS,
					'size_units' 	=> [ 'px', '%' ],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-timeline__line' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
				]
			);

		$this->end_controls_section();
		
	}

	protected function add_global_render_attributes() {
		$settings = $this->get_settings_for_display();

		$this->add_render_attribute( [
			'wrapper' => [
				'class' => [
					'ee-timeline',
					'ee-timeline--vertical',
				],
			],
			'item' => [
				'class' => [
					'ee-timeline__item',
					'timeline-item',
				],
			],
			'icon-wrapper' => [
				'class' => [
					'ee-icon',
					'ee-icon-support--svg',
					'ee-timeline__item__icon',
				],
			],
			'line' => [
				'class' => 'ee-timeline__line',
			],
			'line-inner' => [
				'class' => 'ee-timeline__line__inner',
			],
			'card-wrapper' => [
				'class' => 'timeline-item__card-wrapper',
			],
			'point' => [
				'class' => 'timeline-item__point',
			],
			'meta' => [
				'class' => 'timeline-item__meta',
			],
			'image' => [
				'class' => [
					'timeline-item__img',
					'ee-post__thumbnail',
				],
			],
			'content' => [
				'class' => 'timeline-item__content',
			],
			'arrow' => [
				'class' => 'timeline-item__card__arrow',
			],
			'meta-wrapper' => [
				'class' => 'timeline-item__meta-wrapper',
			],
		] );

		if ( $settings['animate_in'] === 'yes' ) {
			$this->add_render_attribute( 'item', 'class', 'is--hidden' );
		}
	}

	/**
	 * Render
	 * 
	 * Render widget contents on frontend
	 *
	 * @since  1.9.0
	 * @return void
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		$this->add_global_render_attributes();

		?><section <?php echo $this->get_render_attribute_string( 'wrapper' ); ?>><?php
			$this->render_line();
			call_user_func( [ $this, 'render_' . $settings['source'] . '_cards' ] );
		?></section><?php
	}

	/**
	 * Render Custom Cards
	 * 
	 * Renders markup for custom cards
	 *
	 * @since  1.9.0
	 * @return void
	 */
	protected function render_custom_cards() {
		$settings = $this->get_settings_for_display();

		foreach ( $settings['items'] as $index => $item ) {

			$card_tag 		= 'div';
			$item_key 		= $this->get_repeater_setting_key( 'item', 'items', $index );
			$card_key 		= $this->get_repeater_setting_key( 'card', 'items', $index );
			$point_content 	= '';
			$wysiwyg_key 	= $this->get_repeater_setting_key( 'content', 'items', $index );
			$meta_key 		= $this->get_repeater_setting_key( 'date', 'items', $index );

			$this->add_render_attribute( [
				$item_key => [
					'class' => [
						'elementor-repeater-item-' . $item['_id'],
						'ee-timeline__item',
						'timeline-item',
					],
				],
				$card_key => [
					'class' => 'timeline-item__card',
				],
				$wysiwyg_key => [
					'class' => 'timeline-item__content__wysiwyg',
				],
				$meta_key => [
					'class' => [
						'timeline-item__meta',
						'meta',
					],
				],
			] );

			if ( $settings['animate_in'] === 'yes' ) {
				$this->add_render_attribute( $item_key, 'class', 'is--hidden' );
			}

			if ( ! empty( $item['link']['url'] ) ) {
				$card_tag = 'a';

				$this->add_render_attribute( $card_key, 'href', $item['link']['url'] );

				if ( $item['link']['is_external'] ) {
					$this->add_render_attribute( $card_key, 'target', '_blank' );
				}

				if ( $item['link']['nofollow'] ) {
					$this->add_render_attribute( $card_key, 'rel', 'nofollow' );
				}
			}

			$this->add_inline_editing_attributes( $wysiwyg_key, 'advanced' );
			$this->add_inline_editing_attributes( $meta_key, 'basic' );

			if ( ( 'yes' === $item['custom_style'] && '' !== $item['point_content_type'] ) ) {
				$point_content_type = $item['point_content_type'];
			} else {
				$point_content_type = $settings['points_content'];
			}

			switch( $point_content_type ) {
				case 'numbers' :
				case 'letters' :
					$point_content = $this->get_point_text( $point_content_type, $index, $item );
					break;
				default:
					$point_content = $this->get_point_icon( $item );
			}
			
			?><div <?php echo $this->get_render_attribute_string( $item_key ); ?>>
				<div <?php echo $this->get_render_attribute_string( 'point' ); ?>><?php echo $point_content; ?></div>
				<div <?php echo $this->get_render_attribute_string( 'card-wrapper' ); ?>>
					<<?php echo $card_tag; ?> <?php echo $this->get_render_attribute_string( $card_key ); ?>><?php 
						$this->render_image( $item );

						if ( '' !== $item['content'] ) { ?>
						<!-- content -->
						<div <?php echo $this->get_render_attribute_string( 'content' ); ?>>
							<!-- meta -->
							<div <?php echo $this->get_render_attribute_string( $meta_key ); ?>>
								<?php echo $this->parse_text_editor( $item['date'] ); ?>
							</div>
							<!-- body -->
							<div <?php echo $this->get_render_attribute_string( $wysiwyg_key ); ?>>
								<?php echo $this->parse_text_editor( $item['content'] ); ?>
							</div>
						</div>
						<?php } ?>
						
						<!-- arrow -->
						<?php echo $this->render_card_arrow(); ?>
					</<?php echo $card_tag; ?>>
				</div>
				<div <?php echo $this->get_render_attribute_string( 'meta-wrapper' ); ?>>
					<div <?php echo $this->get_render_attribute_string( $meta_key ); ?>>
						<?php echo $this->parse_text_editor( $item['date'] ); ?>
					</div>
				</div>
			</div><?php
		}
	}

	/**
	 * Render Posts Cards
	 *
	 * Loops through the posts and renders markup for each post card
	 *
	 * @since 2.1.0
	 */
	protected function render_posts_cards() {
		$this->query_posts();

		$wp_query = $this->get_query();

		if ( ! $wp_query->found_posts )
			return;

		add_filter( 'excerpt_more', [ $this, 'custom_excerpt_more' ], 99 );
		add_filter( 'excerpt_length', [ $this, 'custom_excerpt_length' ], 99 );

		while ( $wp_query->have_posts() ) {

			$wp_query->the_post();

			$this->render_post_card( $wp_query->current_post );
		}

		remove_filter( 'excerpt_length', [ $this, 'custom_excerpt_length' ], 99 );
		remove_filter( 'excerpt_more', [ $this, 'custom_excerpt_more' ], 99 );

		wp_reset_postdata();
	}

	/**
	 * Render Post Card
	 *
	 * @since 1.9.0
	 */
	protected function render_post_card( $index ) {

		$settings = $this->get_settings();

		$card_tag 	= 'div';
		$point_content = '';
		$card_key = $this->get_repeater_setting_key( 'card', 'content', $index );

		$this->add_render_attribute( [
			$card_key => [
				'class' => [
					'timeline-item__card',
					implode( ' ', get_post_class() ),
				],
			],
		] );

		if ( 'yes' === $settings['card_links'] ) {
			$card_tag = 'a';

			$this->add_render_attribute( $card_key, 'href', get_permalink() );
		}

		switch ( $settings['points_content'] ) {
			case 'numbers' :
			case 'letters' :
				$point_content = $this->get_point_text( $settings['points_content'], $index );
				break;
			default:
				$point_content = $this->get_point_icon();
		}

		?><div <?php echo $this->get_render_attribute_string( 'item' ); ?>>
			<div <?php echo $this->get_render_attribute_string( 'point' ); ?>>
				<?php echo $point_content; ?>
			</div>
			<div <?php echo $this->get_render_attribute_string( 'card-wrapper' ); ?>>
				<<?php echo $card_tag; ?> <?php echo $this->get_render_attribute_string( $card_key ); ?>><?php
					$this->render_image();
					?><div <?php echo $this->get_render_attribute_string( 'content' ); ?>><?php
						if ( 'product' !== $settings['posts_post_type'] ) { ?>
							<div <?php echo $this->get_render_attribute_string( 'meta' ); ?>>
								<?php echo $this->render_date( false ); ?>
							</div>
						<?php }

						if ( $settings['post_title'] === 'yes' ) {
							$this->render_title( 'yes' !== $settings['card_links'] );	
						}

						if ( is_woocommerce_active() && $settings['post_product_attributes'] === 'yes' && $settings['card_links'] !== 'yes' )
							$this->render_product_attributes();

						if ( 'yes' === $settings['post_excerpt'] ) {
							$this->render_excerpt();
						} elseif ( 'content' === $settings['post_excerpt'] ) {
							$this->render_post_content();
						}

						if ( is_woocommerce_active() && $settings['post_buy'] === 'yes' && $settings['card_links'] !== 'yes' )
							echo do_shortcode('[add_to_cart id="' . get_the_ID() . '" style="border:0px;padding:0px"]');

					?></div><?php
					echo $this->render_card_arrow();
				 ?></<?php echo $card_tag; ?>>
			</div>
			<div <?php echo $this->get_render_attribute_string( 'meta-wrapper' ); ?>>
				<div <?php echo $this->get_render_attribute_string( 'meta' ); ?>>
					<?php echo $this->render_date( false ); ?>
				</div>
			</div>
		</div>
		<?php	
	}

	/**
	 * Render Point Text
	 *
	 * @since 1.9.0
	 */
	protected function get_point_text( $type, $index = false, $item = false ) {

		$settings 	= $this->get_settings();
		$letters 	= range('A', 'Z');
		$point_key 	= ( $item ) ? $this->get_repeater_setting_key( 'icon', 'items', $index ) : 'point-text';
		$text 		= 0;

		$text 		= ( $type === 'numbers' ) ? $index + 1 : $letters[ $index ];

		if ( $item ) {
			if ( $item['custom_style'] === 'yes' && '' !== $item['point_content'] ) {
				$text = $item['point_content'];
			}
		}

		$this->add_render_attribute( $point_key, 'class', 'timeline-item__point__text' );

		$output = '<div ' . $this->get_render_attribute_string( $point_key ) . '>' . $text . '</div>';

		return $output;
	}

	/**
	 * Render Point Icon
	 *
	 * @since 1.9.0
	 */
	protected function get_point_icon( $item = false ) {

		$settings = $this->get_settings();

		$global_icon_migrated 	= isset( $item['__fa4_migrated']['selected_global_icon'] );
		$global_icon_is_new 	= empty( $item['global_icon'] ) && Icons_Manager::is_migration_allowed();
		$has_global_icon 		= ! empty( $settings['global_icon'] ) || ! empty( $settings['selected_global_icon']['value'] );

		if ( $item ) {
			$has_item_icon = ! empty( $item['icon'] ) || ! empty( $item['selected_icon']['value'] );
			$item_icon_migrated = isset( $item['__fa4_migrated']['selected_icon'] );
			$item_icon_is_new = empty( $item['icon'] ) && Icons_Manager::is_migration_allowed();
		}

		$output = '<span ' .$this->get_render_attribute_string( 'icon-wrapper' ) . '>';

		$icon_markup = '<i class="%s timeline-item__point__icon" aria-hidden="true"></i>';

		if ( $item && '' !== $item['custom_style'] && $has_item_icon ) {
			if ( $item_icon_is_new || $item_icon_migrated ) {
				$output .= $this->get_library_point_icon( $item['selected_icon'] );
			} else {
				$output .= sprintf( $icon_markup, $item['icon'] );
			}
		} else if ( $has_global_icon ) {
			if ( $global_icon_is_new || $global_icon_migrated ) {
				$output .= $this->get_library_point_icon( $settings['selected_global_icon'] );
			} else {
				$output .= sprintf( $icon_markup, $settings['global_icon'] );
			}
		}

		$output .= '</span>';

		return $output;
	}

	/**
	 * Render Line
	 *
	 * @since 2.1.5
	 */
	protected function get_library_point_icon( $setting ) {
		ob_start();
		Icons_Manager::render_icon( $setting, [ 'aria-hidden' => 'true' ] );
		return ob_get_clean();
	}

	/**
	 * Render Line
	 *
	 * @since 1.9.0
	 */
	protected function render_line() {
		?><div <?php echo $this->get_render_attribute_string( 'line' ); ?>>
			<div <?php echo $this->get_render_attribute_string( 'line-inner' ); ?>></div>
		</div><?php
	}

	/**
	 * Render Card Arrow
	 *
	 * @since 1.9.0
	 */
	protected function render_card_arrow() {
		?><!-- arrow -->
		<div <?php echo $this->get_render_attribute_string( 'arrow' ); ?>></div><?php
	}

	/**
	 * Render Product Attributes
	 *
	 * @since 1.9.0
	 */
	protected function render_product_attributes() {
		global $post;

		$product = wc_get_product( get_the_ID() );

		if ( ! $product )
			return;

		if ( ! $product->has_attributes() )
			return;

		echo '<table class="shop_attributes">';
		echo '<tbody>';

		$attributes = $product->get_attributes();
		$excluded_attributes = $this->get_settings( 'post_product_attributes_exclude' );

		$excluded_attributes = array_map( 'trim', explode(',', strtolower( $excluded_attributes ) ) );

		foreach ($attributes as $attribute) {

			$label 	= wc_attribute_label( $attribute->get_name() );
			$id 	= $attribute->get_id();

			if ( ! empty( $excluded_attributes ) && (
				in_array( strtolower( $label ), $excluded_attributes ) ||
				in_array( strtolower( $id ), $excluded_attributes ) ||
				( ! empty( $id ) && in_array( $id, $excluded_attributes ) )
			) )
				continue;

			$values = array();

			if ( $attribute->is_taxonomy() ) {
				$attribute_taxonomy = $attribute->get_taxonomy_object();
				$attribute_values = wc_get_product_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'all' ) );

				foreach ( $attribute_values as $attribute_value ) {
					$value_name = esc_html( $attribute_value->name );
					$values[] = $value_name;
				}
			} else {
				$values = $attribute->get_options();

				foreach ( $values as &$value ) {
					$value = make_clickable( esc_html( $value ) );
				}
			}

			echo '<tr>';
			echo '<th>' . $label . '</th>';
			echo '<td>' . apply_filters( 'woocommerce_attribute', wpautop( wptexturize( implode( ', ', $values ) ) ), $attribute, $values ) . '</td>';
			echo '</tr>';
		}

		echo '</tbody>';
		echo '</table>';
	}

	/**
	 * Render Image
	 *
	 * @since 2.1.0
	 */
	protected function render_image( $item = false ) {
		call_user_func( [ $this, 'render_' . $this->get_settings('source') . '_thumbnail' ], $item );
	}

	/**
	 * Render Custom Thumbnail
	 *
	 * @since 2.1.0
	 */
	protected function render_custom_thumbnail( $item ) {
		if ( '' === $item['image']['url'] )
			return;

		?><div <?php echo $this->get_render_attribute_string( 'image' ); ?>><?php
			echo Group_Control_Image_Size::get_attachment_image_html( $item );
		?></div><?php
	}

	/**
	 * Render Thumbnail
	 *
	 * @since 1.9.0
	 */
	protected function render_posts_thumbnail() {
		global $post;

		if ( ! has_post_thumbnail() )
			return;

		$settings = $this->get_settings_for_display();

		$settings['post_thumbnail_size'] = [
			'id' => get_post_thumbnail_id(),
		];

		?><div <?php echo $this->get_render_attribute_string( 'image' ); ?>><?php

		if ( '' === $settings['card_links'] ) {
			?><a href="<?php echo the_permalink(); ?>"><?php
		}
		
		echo Group_Control_Image_Size::get_attachment_image_html( $settings, 'post_thumbnail_size' );

		if ( '' === $settings['card_links'] ) {
			?></a><?php
		}

		?></div><?php
	}

	/**
	 * Render Title
	 *
	 * @since 1.9.0
	 */
	protected function render_title( $link = true, $echo = true ) {
		global $post;

		$title_before = ( $link ) ? '<a href="' . get_permalink() . '">' : '';
		$title_after = ( $link ) ? '</a>' : '';

		$title = '<h2 class="timeline-item__title">' . $title_before . $post->post_title . $title_after . '</h2>';

		if ( $echo )
			echo $title;
		else return $title;
	}

	/**
	 * Render Excerpt
	 *
	 * @since 1.9.0
	 */
	protected function render_excerpt() {
		?><div class="timeline-item__excerpt">
			<?php the_excerpt(); ?>
		</div><?php
	}

	/**
	 * Render Post Content
	 *
	 * @since 1.9.0
	 */
	protected function render_post_content() {
		?><div class="timeline-item__excerpt timeline-item__excerpt--content">
			<?php the_content(); ?>
		</div><?php
	}

	/**
	 * Render Date
	 * 
	 * Outputs the date of the post
	 *
	 * @since 1.9.0
	 */
	protected function render_date( $echo = true ) {

		$settings = $this->get_settings_for_display();

		if ( 'custom' === $settings['date_format'] ) {
			$format = $settings['date_custom_format'];
		} else {
			$date_format = $settings['date_format'];
			$time_format = $settings['time_format'];
			$format = '';

			if ( 'default' === $date_format ) {
				$date_format = get_option( 'date_format' );
			}

			if ( 'default' === $time_format ) {
				$time_format = get_option( 'time_format' );
			}

			if ( $date_format ) {
				$format = $date_format;
				$has_date = true;
			} else {
				$has_date = false;
			}

			if ( $time_format ) {
				if ( $has_date ) {
					$format .= ' ';
				}
				$format .= $time_format;
			}
		}

		$value = get_the_date( $format );

		if ( $echo )
			echo wp_kses_post( $value );
		else return wp_kses_post( $value );
	}

	/**
	 * Query Posts
	 * 
	 * Builds the posts query
	 *
	 * @since 1.9.0
	 */
	public function query_posts() {

		if ( ! is_elementor_pro_active() )
			return;

		$query_control = new Query_Control;
		$query_args = $query_control->get_query_args( 'posts', $this->get_settings() );

		$query_args['ignore_sticky_posts'] = ( 'yes' === $this->get_settings( 'sticky_posts' ) ) ? 0 : 1;
		$query_args['posts_per_page'] = $this->get_settings( 'posts_per_page' );
		$statuses = array( $query_args['post_status'] );

		if ( 'yes' === $this->get_settings( 'post_drafts' ) ) {
			array_push( $statuses, 'draft' );
		}

		if ( 'yes' === $this->get_settings( 'post_scheduled' ) ) {
			array_push( $statuses, 'future' );
		}

		$query_args['post_status'] = $statuses;

		$this->_query = new \WP_Query( $query_args );
	}

	/**
	 * Applies the custom excerpt length
	 *
	 * @since 1.9.0
	 */
	public function custom_excerpt_length() {
		return $this->get_settings( 'post_excerpt_length' );
	}

	/**
	 * Removes the auto more link from the excerpt
	 *
	 * @since 1.9.0
	 */
	public function custom_excerpt_more( $more ) {
		return '&hellip;';
	}

	protected function _content_template() {
		
	}
}
