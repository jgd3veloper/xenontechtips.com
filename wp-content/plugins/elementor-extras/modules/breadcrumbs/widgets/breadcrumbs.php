<?php
namespace ElementorExtras\Modules\Breadcrumbs\Widgets;

// Elementor Extras Classes
use ElementorExtras\Base\Extras_Widget;
use ElementorExtras\Utils;

// Elementor Classes
use Elementor\Icons_Manager;
use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Scheme_Color;
use Elementor\Scheme_Typography;
use Elementor\Modules\DynamicTags\Module as TagsModule;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Breadcrumbs
 *
 * @since 1.2.0
 */
class Breadcrumbs extends Extras_Widget {

	/**
	 * Query
	 *
	 * @since  1.2.0
	 * @var    \WP_Query
	 */
	private $_query = null;

	/**
	 * Separator
	 *
	 * @since  1.2.0
	 * @var    string
	 */
	private $_separator = null;

	/**
	 * Get Name
	 * 
	 * Get the name of the widget
	 *
	 * @since  1.2.0
	 * @return string
	 */
	public function get_name() {
		return 'ee-breadcrumbs';
	}

	/**
	 * Get Title
	 * 
	 * Get the title of the widget
	 *
	 * @since  1.2.0
	 * @return string
	 */
	public function get_title() {
		return __( 'Breadcrumbs', 'elementor-extras' );
	}

	/**
	 * Get Icon
	 * 
	 * Get the icon of the widget
	 *
	 * @since  1.2.0
	 * @return string
	 */
	public function get_icon() {
		return 'nicon nicon-breadcrumbs';
	}

	/**
	 * Register Widget Controls
	 *
	 * @since  1.2.0
	 * @return void
	 */
	protected function _register_controls() {
		
		$this->start_controls_section(
			'section_content',
			[
				'label' => __( 'Display', 'elementor-extras' ),
			]
		);

			$this->add_control(
				'source',
				[
					'label' 	=> __( 'Source', 'elementor-extras' ),
					'type'		=> Controls_Manager::SELECT,
					'default'	=> '',
					'options'	=> [
						''		=> __( 'Current page', 'elementor-extras' ),
						'id'	=> __( 'Specific page', 'elementor-extras' ),
					]
				]
			);

			$this->add_control(
				'source_id',
				[
					'label' 		=> __( 'ID', 'elementor-extras' ),
					'type'			=> Controls_Manager::NUMBER,
					'min' 			=> 0,
					'placeholder' 	=> '15',
					'condition'		=> [
						'source'	=> 'id',
					]
				]
			);

			$this->add_control(
				'show_home',
				[
					'label' 		=> __( 'Show Home', 'elementor-extras' ),
					'type' 			=> Controls_Manager::SWITCHER,
					'default' 		=> 'yes',
					'label_on' 		=> __( 'Yes', 'elementor-extras' ),
					'label_off' 	=> __( 'No', 'elementor-extras' ),
					'return_value' 	=> 'yes',
				]
			);

			$this->add_control(
				'show_current',
				[
					'label' 		=> __( 'Show Current', 'elementor-extras' ),
					'type' 			=> Controls_Manager::SWITCHER,
					'default' 		=> 'yes',
					'label_on' 		=> __( 'Yes', 'elementor-extras' ),
					'label_off' 	=> __( 'No', 'elementor-extras' ),
					'return_value' 	=> 'yes',
				]
			);

			$this->add_control(
				'cpt_crumbs',
				[
					'label' 		=> __( 'CPT Crumbs', 'elementor-extras' ),
					'type' 			=> Controls_Manager::SELECT,
					'default' 		=> '',
					'options'		=> [
						'' 			=> __( 'CPT Name', 'elementor-extras' ),
						'terms' 	=> __( 'Taxonomy Terms', 'elementor-extras' ),
						'both' 		=> __( 'Both', 'elementor-extras' ),
					],
				]
			);

			$this->add_control(
				'home_text',
				[
					'label' 		=> __( 'Home Text', 'elementor-extras' ),
					'type' 			=> Controls_Manager::TEXT,
					'default' 		=> __( 'Homepage', 'elementor-extras' ),
					'dynamic'		=> [
						'active'	=> true,
						'categories' => [ TagsModule::POST_META_CATEGORY ]
					],
					'condition'		=> [
						'show_home' => 'yes'
					],
				]
			);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_separator',
			[
				'label' => __( 'Separator', 'elementor-extras' ),
			]
		);

			$this->add_control(
				'separator_type',
				[
					'label'		=> __( 'Type', 'elementor-extras' ),
					'type' 		=> Controls_Manager::SELECT,
					'default' 	=> 'icon',
					'options' 	=> [
						'text' 		=> __( 'Text', 'elementor-extras' ),
						'icon' 		=> __( 'Icon', 'elementor-extras' ),
					],
				]
			);

			$this->add_control(
				'separator_text',
				[
					'label' 		=> __( 'Text', 'elementor-extras' ),
					'type' 			=> Controls_Manager::TEXT,
					'default' 		=> __( '>', 'elementor-extras' ),
					'condition'		=> [
						'separator_type' => 'text'
					],
				]
			);

			$this->add_control(
				'selected_separator_icon',
				[
					'label' => __( 'Icon', 'elementor-extras' ),
					'type' => Controls_Manager::ICONS,
					'label_block' => true,
					'fa4compatibility' => 'separator_icon',
					'condition'		=> [
						'separator_type' => 'icon'
					],
					'default' => [
						'value' => 'fas fa-angle-right',
						'library' => 'fa-solid',
					],
				]
			);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_item_style',
			[
				'label' 	=> __( 'Crumbs', 'elementor-extras' ),
				'tab' 		=> Controls_Manager::TAB_STYLE,
			]
		);

			$this->add_responsive_control(
				'items_align',
				[
					'label' 		=> __( 'Align Crumbs', 'elementor-extras' ),
					'type' 			=> Controls_Manager::CHOOSE,
					'default' 		=> '',
					'options' 		=> [
						'left' 			=> [
							'title' 	=> __( 'Left', 'elementor-extras' ),
							'icon' 		=> 'eicon-h-align-left',
						],
						'center' 		=> [
							'title' 	=> __( 'Center', 'elementor-extras' ),
							'icon' 		=> 'eicon-h-align-center',
						],
						'right' 		=> [
							'title' 	=> __( 'Right', 'elementor-extras' ),
							'icon' 		=> 'eicon-h-align-right',
						],
						'stretch' 		=> [
							'title' 	=> __( 'Stretch', 'elementor-extras' ),
							'icon' 		=> 'eicon-h-align-stretch',
						],
					],
					'prefix_class' 	=> 'ee-breadcrumbs-align%s-',
				]
			);

			$this->add_responsive_control(
				'items_text_align',
				[
					'label' 		=> __( 'Align Text', 'elementor-extras' ),
					'type' 			=> Controls_Manager::CHOOSE,
					'default' 		=> '',
					'options' 		=> [
						'left' 			=> [
							'title' 	=> __( 'Left', 'elementor-extras' ),
							'icon' 		=> 'fa fa-align-left',
						],
						'center' 		=> [
							'title' 	=> __( 'Center', 'elementor-extras' ),
							'icon' 		=> 'fa fa-align-center',
						],
						'right' 		=> [
							'title' 	=> __( 'Right', 'elementor-extras' ),
							'icon' 		=> 'fa fa-align-right',
						],
					],
					'selectors' => [
						'{{WRAPPER}} .ee-breadcrumbs' => 'text-align: {{VALUE}};',
					],
				]
			);

			$this->add_control(
				'item_spacing',
				[
					'label' 	=> __( 'Spacing', 'elementor-extras' ),
					'type' 		=> Controls_Manager::SLIDER,
					'default'	=> [
						'size'	=> 12
					],
					'range' 	=> [
						'px' 	=> [
							'max' => 36,
						],
					],
					'selectors' => [
						'{{WRAPPER}} .ee-breadcrumbs' => 'margin-left: -{{SIZE}}{{UNIT}};',
						'{{WRAPPER}} .ee-breadcrumbs__item' => 'margin-left: {{SIZE}}{{UNIT}};',
						'{{WRAPPER}} .ee-breadcrumbs__separator' => 'margin-left: {{SIZE}}{{UNIT}};',
					],
				]
			);

			$this->add_control(
				'item_padding',
				[
					'label' 		=> __( 'Padding', 'elementor-extras' ),
					'type' 			=> Controls_Manager::DIMENSIONS,
					'size_units' 	=> [ 'px', 'em', '%' ],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-breadcrumbs__item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
					'allowed_dimensions' => [ 'right', 'left' ],
				]
			);

			$this->add_group_control(
				Group_Control_Border::get_type(),
				[
					'name' 		=> 'item_border',
					'label' 	=> __( 'Border', 'elementor-extras' ),
					'selector' 	=> '{{WRAPPER}} .ee-breadcrumbs__item',
				]
			);

			$this->add_control(
				'item_border_radius',
				[
					'label' 		=> __( 'Border Radius', 'elementor-extras' ),
					'type' 			=> Controls_Manager::DIMENSIONS,
					'size_units' 	=> [ 'px', '%' ],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-breadcrumbs__item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
				]
			);

			$this->add_group_control(
				Group_Control_Typography::get_type(),
				[
					'name' 		=> 'item_typography',
					'label' 	=> __( 'Typography', 'elementor-extras' ),
					'scheme' 	=> Scheme_Typography::TYPOGRAPHY_4,
					'selector' 	=> '{{WRAPPER}} .ee-breadcrumbs__text',
				]
			);

			$this->start_controls_tabs( 'crumb_style' );

			$this->start_controls_tab( 'crumb_default', [ 'label' => __( 'Default', 'elementor-extras' ) ] );

				$this->add_control(
					'item_background_color',
					[
						'label' 	=> __( 'Background Color', 'elementor-extras' ),
						'type' 		=> Controls_Manager::COLOR,
						'selectors' => [
							'{{WRAPPER}} .ee-breadcrumbs__item' => 'background-color: {{VALUE}};',
						],
					]
				);

				$this->add_control(
					'item_color',
					[
						'label' 	=> __( 'Color', 'elementor-extras' ),
						'type' 		=> Controls_Manager::COLOR,
						'default'	=> '',
						'selectors' => [
							'{{WRAPPER}} .ee-breadcrumbs__item' => 'color: {{VALUE}};',
							'{{WRAPPER}} .ee-breadcrumbs__item a' => 'color: {{VALUE}};',
						],
					]
				);

			$this->end_controls_tab();

			$this->start_controls_tab( 'crumb_hover', [ 'label' => __( 'Hover', 'elementor-extras' ) ] );

				$this->add_control(
					'item_background_color_hover',
					[
						'label' 	=> __( 'Background Color', 'elementor-extras' ),
						'type' 		=> Controls_Manager::COLOR,
						'selectors' => [
							'{{WRAPPER}} .ee-breadcrumbs__item:hover' => 'background-color: {{VALUE}};',
						],
					]
				);

				$this->add_control(
					'item_color_hover',
					[
						'label' 	=> __( 'Color', 'elementor-extras' ),
						'type' 		=> Controls_Manager::COLOR,
						'default'	=> '',
						'selectors' => [
							'{{WRAPPER}} .ee-breadcrumbs__item:hover' => 'color: {{VALUE}};',
							'{{WRAPPER}} .ee-breadcrumbs__item:hover a' => 'color: {{VALUE}};',
						],
					]
				);
			
			$this->end_controls_tab();

			$this->end_controls_tabs();

		$this->end_controls_section();

		$this->start_controls_section(
			'section_separator_style',
			[
				'label' 	=> __( 'Separators', 'elementor-extras' ),
				'tab' 		=> Controls_Manager::TAB_STYLE,
			]
		);

			$this->add_control(
				'separator_padding',
				[
					'label' 		=> __( 'Padding', 'elementor-extras' ),
					'type' 			=> Controls_Manager::DIMENSIONS,
					'size_units' 	=> [ 'px', 'em', '%' ],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-breadcrumbs__separator' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
					'allowed_dimensions' => [ 'right', 'left' ],
				]
			);

			$this->add_group_control(
				Group_Control_Border::get_type(),
				[
					'name' 		=> 'separator_border',
					'label' 	=> __( 'Border', 'elementor-extras' ),
					'selector' 	=> '{{WRAPPER}} .ee-breadcrumbs__separator',
				]
			);

			$this->add_control(
				'separator_border_radius',
				[
					'label' 		=> __( 'Border Radius', 'elementor-extras' ),
					'type' 			=> Controls_Manager::DIMENSIONS,
					'size_units' 	=> [ 'px', '%' ],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-breadcrumbs__separator' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
				]
			);

			$this->add_control(
				'separator_background_color',
				[
					'label' 	=> __( 'Background Color', 'elementor-extras' ),
					'type' 		=> Controls_Manager::COLOR,
					'selectors' => [
						'{{WRAPPER}} .ee-breadcrumbs__separator' => 'background-color: {{VALUE}};',
					],
				]
			);

			$this->add_control(
				'separator_color',
				[
					'label' 	=> __( 'Color', 'elementor-extras' ),
					'type' 		=> Controls_Manager::COLOR,
					'default'	=> '',
					'selectors' => [
						'{{WRAPPER}} .ee-breadcrumbs__separator' => 'color: {{VALUE}};',
					],
				]
			);

			$this->add_group_control(
				Group_Control_Typography::get_type(),
				[
					'name' 		=> 'separator_typography',
					'label' 	=> __( 'Typography', 'elementor-extras' ),
					'scheme' 	=> Scheme_Typography::TYPOGRAPHY_4,
					'selector' 	=> '{{WRAPPER}} .ee-breadcrumbs__separator',
				]
			);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_current_style',
			[
				'label' 	=> __( 'Current', 'elementor-extras' ),
				'tab' 		=> Controls_Manager::TAB_STYLE,
			]
		);

			$this->add_group_control(
				Group_Control_Border::get_type(),
				[
					'name' 		=> 'current_border',
					'label' 	=> __( 'Border', 'elementor-extras' ),
					'selector' 	=> '{{WRAPPER}} .ee-breadcrumbs__item--current',
				]
			);

			$this->add_control(
				'current_border_radius',
				[
					'label' 		=> __( 'Border Radius', 'elementor-extras' ),
					'type' 			=> Controls_Manager::DIMENSIONS,
					'size_units' 	=> [ 'px', '%' ],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-breadcrumbs__item--current' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
				]
			);

			$this->add_control(
				'current_background_color',
				[
					'label' 	=> __( 'Background Color', 'elementor-extras' ),
					'type' 		=> Controls_Manager::COLOR,
					'selectors' => [
						'{{WRAPPER}} .ee-breadcrumbs__item--current' => 'background-color: {{VALUE}};',
					],
				]
			);

			$this->add_control(
				'current_color',
				[
					'label' 	=> __( 'Color', 'elementor-extras' ),
					'type' 		=> Controls_Manager::COLOR,
					'default'	=> '',
					'selectors' => [
						'{{WRAPPER}} .ee-breadcrumbs__item--current' => 'color: {{VALUE}};',
					],
				]
			);

			$this->add_group_control(
				Group_Control_Typography::get_type(),
				[
					'name' 		=> 'current_typography',
					'label' 	=> __( 'Typography', 'elementor-extras' ),
					'scheme' 	=> Scheme_Typography::TYPOGRAPHY_4,
					'selector' 	=> '{{WRAPPER}} .ee-breadcrumbs__item--current .ee-breadcrumbs__text',
				]
			);

		$this->end_controls_section();

	}

	/**
	 * Get Query
	 *
	 * @since  1.2.0
	 * @return \WP_Query|bool
	 */
	protected function get_query() {

		global $post;

		$settings 	= $this->get_settings_for_display();
		$_id 		= null;
		$_post_type = 'post';

		if ( 'id' === $settings['source'] && '' !== $settings['source_id'] ) {

			$_id = $settings['source_id'];
			$_post_type = 'any';

			$_args = array(
				'p' 		=> $_id,
				'post_type' => $_post_type,
			);

			// Create custom query
			$_post_query = new \WP_Query( $_args );

			return $_post_query;
		}

		return false;
	}

	/**
	 * Set Separator
	 *
	 * Sets the markup for the breadcrumbs separator
	 *
	 * @since  1.2.0
	 * @return string
	 */
	protected function set_separator() {

		$settings = $this->get_settings_for_display();
		$separator = '';

		if ( 'icon' === $settings['separator_type'] ) {
			if ( ! empty( $settings['separator_icon'] ) || ! empty( $settings['selected_separator_icon']['value'] ) ) {
				$migrated = isset( $settings['__fa4_migrated']['selected_separator_icon'] );
				$is_new = empty( $settings['icon'] ) && Icons_Manager::is_migration_allowed();

				$this->add_render_attribute( 'icon-wrapper', 'class', [
					'ee-icon',
					'ee-icon-support--svg',
				] );

				$separator .= '<span ' . $this->get_render_attribute_string( 'icon-wrapper' ) . '>';
				
				if ( $is_new || $migrated ) {
					ob_start();
					Icons_Manager::render_icon( $settings['selected_separator_icon'], [ 'aria-hidden' => 'true' ] );
					$separator .= ob_get_clean();
				} else {
					$this->add_render_attribute( 'icon', [
						'class' => $settings['separator_icon'],
						'aria-hidden' => 'true',
					] );

					ob_start();
					$separator .= '<i '. $this->get_render_attribute_string('icon') . '></i>';
				}

				$separator .= '</span>';
			}
		} else {
			$this->add_inline_editing_attributes( 'separator_text' );
			$this->add_render_attribute( 'separator_text', 'class', 'ee-breadcrumbs__separator__text' );
			
			$separator = '<span ' . $this->get_render_attribute_string( 'separator_text' ) . '>' . $settings['separator_text'] . '</span>';
		}

		$this->_separator = $separator;
	}

	/**
	 * Get Separator
	 *
	 * @since  1.2.0
	 * @return var\string
	 */
	protected function get_separator() {
		return $this->_separator;
	}

	/**
	 * Render
	 * 
	 * Render widget contents on frontend
	 *
	 * @since  1.2.0
	 * @return void
	 */
	protected function render() {

		$settings 	= $this->get_settings_for_display();
		$_query 	= $this->get_query();

		$this->set_separator();
		$this->add_render_attribute( 'breadcrumbs', [
			'class' => 'ee-breadcrumbs',
			'itemscope' => "",
			'itemtype' => "http://schema.org/BreadcrumbList",
		]);

		if ( $_query ) {
			if ( $_query->have_posts() ) {

				// Setup post
				$_query->the_post();

				// Render using the new query
				$this->render_breadcrumbs( $_query );

				// Reset post data to original query
				wp_reset_postdata();
				wp_reset_query();

			} else {

				_e( 'Post or page not found', 'elementor-extras' );

			}
		} else {
			// Render using the original query
			$this->render_breadcrumbs();
		}
	}

	/**
	 * Render Home Link
	 * 
	 * The markup for the home link crumb
	 *
	 * @since  1.2.0
	 * @return void
	 */
	protected function render_home_link() {
		$settings = $this->get_settings_for_display();

		$this->add_item_render_attribute( 'home-item', 0 );
		$this->add_render_attribute( 'home-item', [
			'class' => 'ee-breadcrumbs__item--home',
		] );

		$this->add_link_render_attribute( 'home-link' );
		$this->add_render_attribute( 'home-link', [
			'class' => [
				'ee-breadcrumbs__crumb--link',
				'ee-breadcrumbs__crumb--home'
			],
			'href' 	=> get_home_url(),
			'title' => $settings['home_text'],
		] );

		$this->add_render_attribute( 'home-text', [
			'itemprop' => 'name',
			'class' => 'ee-breadcrumbs__text',
		] );

		?><li <?php echo $this->get_render_attribute_string( 'home-item' ); ?>>
			<a <?php echo $this->get_render_attribute_string( 'home-link' ); ?>>
				<span <?php echo $this->get_render_attribute_string( 'home-text' ); ?>>
					<?php echo $settings['home_text']; ?>
				</span>
			</a>
		</li><?php

		$this->render_separator();

	}

	/**
	 * Render Separator
	 * 
	 * The markup for the separator item
	 *
	 * @since  1.2.0
	 * @return void
	 */
	protected function render_separator( $output = true ) {

		$this->add_render_attribute( 'separator', [
			'class' => [
				'ee-breadcrumbs__separator',
			],
		] );

		$markup = '<li ' . $this->get_render_attribute_string( 'separator' ) . '>';
		$markup .= $this->get_separator();
		$markup .= '</li>';

		if ( $output === true ) {
			echo $markup;
			return;
		}

		return $markup;
	}

	/**
	 * Render Breadcrumbs
	 * 
	 * Identifies and outputs all the breadcrumbs
	 *
	 * @since  1.2.0
	 * @return void
	 */
	protected function render_breadcrumbs( $query = false ) {

		global $post, $wp_query;

		if ( $query === false ) {

			// Reset post data to parent query
			$wp_query->reset_postdata();

			// Set active query to native query
			$query = $wp_query;
		}

		$settings = $this->get_settings_for_display();
		$separator = $this->get_separator();

		$custom_taxonomy = 'product_cat';

		if ( ! $query->is_front_page() ) { ?>
		
			<ul <?php echo $this->get_render_attribute_string( 'breadcrumbs' ); ?>>

			<?php

			if ( 'yes' === $settings['show_home'] ) {
				$this->render_home_link();
			}

			// ——— Custom Archive ——— //
			if ( $query->is_archive() && ! $query->is_tax() && ! $query->is_category() && ! $query->is_tag() && ! $query->is_date() && ! $query->is_author() ) {

				$this->render_item( 'archive', [
					'index'		=> 1,
					'current' 	=> true,
					'separator'	=> false,
					'key' 		=> 'archive',
					'content' 	=> post_type_archive_title( '', false ),
				] );
				
			// ——— Custom Taxonomy Archive ——— //
			} else if ( $query->is_archive() && $query->is_tax() && ! $query->is_category() && ! $query->is_tag() ) {

				$queried_object = get_queried_object();
				$parents = get_ancestors( $queried_object->term_id, $queried_object->taxonomy );

				$post_type = get_post_type();
				$post_type_object = get_post_type_object( $post_type );

				$this->render_item( 'post-type-archive', [
					'index'		=> 1,
					'current' 	=> false,
					'separator'	=> true,
					'key' 		=> 'post-type-archive',
					'ids' 		=> [ $post_type ],
					'content' 	=> $post_type_object->labels->name,
					'link'		=> get_post_type_archive_link( $post_type ),
				] );

				if ( $parents )  {
					$parent_terms = get_terms( [
						'taxonomy' => $queried_object->taxonomy,
						'include' => $parents,
					] );

					$parent_terms = array_reverse( $parent_terms );

					$counter = 2;
					foreach ( $parent_terms as $term ) {
						$this->render_item( 'custom-tax-archive-parents', [
							'index'		=> $counter,
							'current' 	=> false,
							'separator'	=> true,
							'key' 		=> 'custom-tax-archive-' . $term->term_id,
							'ids' 		=> [ $term->term_id, $term->slug ],
							'content' 	=> $term->name,
							'link'		=> get_term_link( $term ),
						] );
						$counter++;
					}
				}

				$this->render_item( 'custom-tax-archive', [
					'index'		=> $counter,
					'current' 	=> true,
					'separator'	=> false,
					'key' 		=> 'custom-tax-archive',
					'ids' 		=> [ $post_type ],
					'content' 	=> get_queried_object()->name,
					'link'		=> '',
				] );

			} else if ( $query->is_post_type_archive() ) {

				$post_type = get_post_type();
				$post_type_object = get_post_type_object( $post_type );

				$this->render_item( 'post-type-archive', [
					'index'		=> 1,
					'current' 	=> true,
					'separator'	=> false,
					'key' 		=> 'post-type-archive',
					'ids' 		=> [ $post_type ],
					'content' 	=> $post_type_object->labels->name,
					'link'		=> get_post_type_archive_link( $post_type ),
				] );
				
			} else if ( $query->is_single() ) {
				
				$post_type = get_post_type();
				
				if ( $post_type !== 'post' ) {

					$counter = 1;

					if ( '' === $settings['cpt_crumbs'] || 'both' === $settings['cpt_crumbs'] ) {

						$post_type_object = get_post_type_object( $post_type );
						$item_content = $post_type_object->labels->name;

						$this->render_item( 'post-type-archive', [
							'index'		=> 1,
							'current' 	=> false,
							'separator'	=> true,
							'key' 		=> 'post-type-archive',
							'ids' 		=> [ $post_type ],
							'content' 	=> $item_content,
							'link'		=> get_post_type_archive_link( $post_type ),
						] );

						$counter++;
					}

					if ( in_array( $settings['cpt_crumbs'], [ 'terms', 'both' ] ) ) {
						$item_content = 'terms';

						$terms = Utils::get_parent_terms_highest( $post->ID );

						if ( $terms ) {
							$counter = 1;
							foreach( $terms as $term ) {
								$this->render_item( 'post-type-terms', [
									'index'		=> $counter,
									'current' 	=> false,
									'separator'	=> true,
									'key' 		=> 'terms-' . $term->term_id,
									'ids' 		=> [ $term->term_id, $term->slug ],
									'content' 	=> $term->name,
									'link'		=> get_term_link( $term ),
								] );

								$counter++;
							}
						}

					}
					
				} else {

					$posts_page_id = get_option( 'page_for_posts' );

					if ( $posts_page_id ) {

						$posts_page = get_post( $posts_page_id );

						$this->render_item( 'blog', [
							'index'		=> 1,
							'current' 	=> false,
							'separator'	=> true,
							'key' 		=> 'blog',
							'ids' 		=> [ $posts_page->ID ],
							'content' 	=> $posts_page->post_title,
							'link'		=> get_permalink( $posts_page->ID ),
						] );
					}
				}

				$category = get_the_category();
				$last_category = null;

				if( ! empty( $category ) ) {

					$cat_display = '';

					$values = array_values($category);

					$last_category = get_term( Utils::get_most_parents_category( $category ) );
						
					$cat_parents = array_reverse( get_ancestors( $last_category->term_id, 'category' ) );
				}

				$taxonomy_exists = taxonomy_exists( $custom_taxonomy );

				if( empty( $last_category ) && ! empty( $custom_taxonomy ) && $taxonomy_exists ) {
						$taxonomy_terms = get_the_terms( $post->ID, $custom_taxonomy );

					if ( $taxonomy_terms ) {
						$cat_id = $taxonomy_terms[0]->term_id;
						$cat_nicename = $taxonomy_terms[0]->slug;
						$cat_link = get_term_link( $taxonomy_terms[0]->term_id, $custom_taxonomy );
						$cat_name = $taxonomy_terms[0]->name;
					}
				}

				if( ! empty( $last_category ) ) {
					$counter = 1;

					foreach ( $cat_parents as $parent ) {
						$_parent = get_term( $parent );

						if ( has_category( $_parent->term_id, $post ) ) {

							$this->render_item( 'category', [
								'index'		=> $counter,
								'current' 	=> false,
								'separator'	=> true,
								'key' 		=> 'category-' . $_parent->term_id,
								'ids' 		=> [ $_parent->term_id, $_parent->slug ],
								'content' 	=> $_parent->name,
								'link'		=> get_term_link( $_parent ),
							] );

							$counter++;
						}
					}

					$this->render_item( 'category', [
						'index'		=> $counter,
						'current' 	=> false,
						'separator'	=> true,
						'key' 		=> 'category' . $last_category->term_id,
						'ids' 		=> [ $last_category->term_id, $last_category->slug ],
						'content' 	=> $last_category->name,
						'link'		=> get_term_link( $last_category ),
					] );

					$this->render_item( 'single', [
						'index'		=> $counter++,
						'current' 	=> true,
						'separator'	=> false,
						'key' 		=> 'single',
						'ids' 		=> [ $post->ID ],
						'content' 	=> get_the_title(),
					] );
					
				} else if ( ! empty( $cat_id ) ) {

					$this->render_item( 'category', [
						'index'		=> 1,
						'current' 	=> false,
						'separator'	=> true,
						'key' 		=> 'category',
						'ids' 		=> [ $cat_nicename, $cat_id ],
						'content' 	=> $cat_name,
						'link'		=> $cat_link,
					] );

					$this->render_item( 'single', [
						'index'		=> 2,
						'current' 	=> true,
						'separator'	=> false,
						'key' 		=> 'single',
						'ids' 		=> [ $post->ID ],
						'content' 	=> get_the_title(),
					] );

				} else {

					$this->render_item( 'single', [
						'index'		=> 1,
						'current' 	=> true,
						'separator'	=> false,
						'key' 		=> 'single',
						'ids' 		=> [ $post->ID ],
						'content' 	=> get_the_title(),
					] );

				}
				
			} else if ( $query->is_category() ) {

				$cat_id = get_query_var( 'cat' );
				$cat = get_category( $cat_id );

				$cat_parents = array_reverse( get_ancestors( $cat_id, 'category' ) );
				$counter = 1;

				foreach ( $cat_parents as $parent ) {
					$_parent = get_term( $parent );

					$this->render_item( 'category', [
						'index'		=> $counter,
						'current' 	=> false,
						'separator'	=> true,
						'key' 		=> 'category-' . $_parent->term_id,
						'ids' 		=> [ $_parent->term_id, $_parent->slug ],
						'content' 	=> $_parent->name,
						'link'		=> get_term_link( $_parent ),
					] );
					$counter++;
				}

				$this->render_item( 'category', [
					'index'		=> $counter,
					'current' 	=> true,
					'separator'	=> false,
					'key' 		=> 'category',
					'ids' 		=> [ $cat_id, $cat->slug ],
					'content' 	=> single_cat_title( '', false ),
				] );
				
			} else if ( $query->is_page() ) {
				
				if ( $post->post_parent ) {
						
					$anc = get_post_ancestors( $post->ID );
					$anc = array_reverse($anc);
						
					if ( ! isset( $parents ) ) $parents = null;

					$counter = 1;

					foreach ( $anc as $ancestor ) {

						$this->render_item( 'ancestor', [
							'index'		=> $counter,
							'current' 	=> false,
							'separator'	=> true,
							'key' 		=> 'ancestor-' . $ancestor,
							'ids' 		=> [ $ancestor ],
							'content' 	=> get_the_title( $ancestor ),
							'link'		=> get_permalink( $ancestor ),
						] );

						$counter++;
					}
				}

				$counter = 1;

				$this->render_item( 'page', [
					'index'		=> $counter,
					'current' 	=> true,
					'separator'	=> false,
					'key' 		=> 'page',
					'ids' 		=> [ $post->ID ],
					'content' 	=> get_the_title(),
				] );
				
			} else if ( $query->is_tag() ) {
				
				$term_id 		= get_query_var('tag_id');
				$taxonomy 		= 'post_tag';
				$args 			= 'include=' . $term_id;
				$terms 			= get_terms( $taxonomy, $args );
				$get_term_id 	= $terms[0]->term_id;
				$get_term_slug 	= $terms[0]->slug;
				$get_term_name 	= $terms[0]->name;

				$this->render_item( 'tag', [
					'index'		=> 1,
					'current' 	=> true,
					'separator'	=> false,
					'key' 		=> 'tag',
					'ids' 		=> [ $get_term_id, $get_term_slug ],
					'content' 	=> sprintf( __( 'Tag: %s', 'elementor-extras' ), $get_term_name ),
				] );
			
			} else if ( $query->is_day() ) {

				$this->render_item( 'year', [
					'index'		=> 1,
					'current' 	=> false,
					'separator'	=> true,
					'key' 		=> 'year',
					'ids' 		=> [ get_the_time('Y') ],
					'content' 	=> sprintf( __( '%s Archives', 'elementor-extras' ), get_the_time('Y') ),
					'link'		=> get_year_link( get_the_time('Y') ),
				] );

				$this->render_item( 'month', [
					'index'		=> 2,
					'current' 	=> false,
					'separator'	=> true,
					'key' 		=> 'month',
					'ids' 		=> [ get_the_time('m') ],
					'content' 	=> sprintf( __( '%s Archives', 'elementor-extras' ), get_the_time('F') ),
					'link'		=> get_month_link( get_the_time('Y'), get_the_time('m') ),
				] );

				$this->render_item( 'day', [
					'index'		=> 3,
					'current' 	=> true,
					'separator'	=> false,
					'key' 		=> 'day',
					'ids' 		=> [ get_the_time('j') ],
					'content' 	=> sprintf( __( '%1$s %2$s Archives', 'elementor-extras' ), get_the_time('F'), get_the_time('jS') ),
				] );
				
			} else if ( $query->is_month() ) {

				$this->render_item( 'year', [
					'index'		=> 1,
					'current' 	=> false,
					'separator'	=> true,
					'key' 		=> 'year',
					'ids' 		=> [ get_the_time('Y') ],
					'content' 	=> sprintf( __( '%s Archives', 'elementor-extras' ), get_the_time('Y') ),
					'link'		=> get_year_link( get_the_time('Y') ),
				] );

				$this->render_item( 'month', [
					'index'		=> 2,
					'current' 	=> true,
					'separator'	=> false,
					'key' 		=> 'month',
					'ids' 		=> [ get_the_time('m') ],
					'content' 	=> sprintf( __( '%s Archives', 'elementor-extras' ), get_the_time('F') ),
				] );
				
			} else if ( $query->is_year() ) {

				$this->render_item( 'year', [
					'index'		=> 1,
					'current' 	=> true,
					'separator'	=> false,
					'key' 		=> 'year',
					'ids' 		=> [ get_the_time('Y') ],
					'content' 	=> sprintf( __( '%s Archives', 'elementor-extras' ), get_the_time('Y') ),
				] );
				
			} else if ( $query->is_author() ) {
				
				global $author;

				$userdata = get_userdata( $author );

				$this->render_item( 'author', [
					'index'		=> 1,
					'current' 	=> true,
					'separator'	=> false,
					'key' 		=> 'author',
					'ids' 		=> [ $userdata->user_nicename ],
					'content' 	=> sprintf( __( 'Author: %s', 'elementor-extras' ), $userdata->display_name ),
				] );
				
			} else if ( $query->is_search() ) {

				$this->render_item( 'search', [
					'index'		=> 1,
					'current' 	=> true,
					'separator'	=> false,
					'key' 		=> 'search',
					'content' 	=> sprintf( __( 'Search results for: %s', 'elementor-extras' ), get_search_query() ),
				] );
			
			} elseif ( $query->is_404() ) {

				$this->render_item( '404', [
					'index'		=> 1,
					'current' 	=> true,
					'separator'	=> false,
					'key' 		=> '404',
					'content' 	=> __( 'Page not found', 'elementor-extras' ),
				] );
			}
		
			echo '</ul>';
			
		}
	}

	/**
	 * Render Item
	 * 
	 * Gets the markup for a breadcrumb item
	 *
	 * @since  1.2.0
	 * @param. slug|string
	 * @param  args|array
	 * @return void
	 */
	protected function render_item( $slug, $args ) {

		$defaults = [
			'current' 		=> false,
			'key' 			=> false,
			'ids'			=> [],
			'content'		=> '',
			'index'			=> false,
			'link'			=> false,
		];

		$args = wp_parse_args( $args, $defaults );

		if ( $args['current'] && '' === $this->get_settings( 'show_current' ) ) {
			return;
		}

		$item_key 	= $args['key'] . '-item';
		$text_key 	= $args['key'] . '-text';
		$link_key 	= ( ! $args['current'] ) ? '-link' : '-current';
		$link_key 	= $args['key'] . $link_key;
		$link_tag 	= ( ! $args['current'] ) ? 'a' : 'strong';
		$link 		= ( ! $args['current'] ) ? ' href="' . $args['link'] .'" ' : ' ';
		$classes 	= [];

		if ( $args['current'] ) {
			$classes[] = 'ee-breadcrumbs__item--current';
		} else {
			$classes[] = 'ee-breadcrumbs__item--parent';
		}

		if ( $slug )
			$classes[] = 'ee-breadcrumbs__item--' . $slug;

		if ( $args['ids'] ) {
			foreach( $args['ids'] as $id ) {
				if ( $slug ) {
					$classes[] = 'ee-breadcrumbs__item--' . $slug . '-' . $id;
				} else { $classes[] = 'ee-breadcrumbs__item--' . $id; }
			}
		}

		$this->add_item_render_attribute( $item_key, $args['index'] );
		$this->add_render_attribute( $item_key, [
			'class' => $classes,
		] );

		$this->add_link_render_attribute( $link_key );
		$this->add_render_attribute( $text_key, [
			'itemprop' 	=> 'name',
			'class' 	=> 'ee-breadcrumbs__text',
		] );

		?><li <?php echo $this->get_render_attribute_string( $item_key ); ?>>
			<<?php echo $link_tag; ?><?php echo $link; ?><?php echo $this->get_render_attribute_string( $link_key ); ?>>
				<span <?php echo $this->get_render_attribute_string( $text_key ); ?>>
					<?php echo $args['content']; ?>
				</span>
			</<?php echo $link_tag; ?>>
		</li><?php

		if ( ! $args['current'] )
			$this->render_separator();
	}

	/**
	 * Add Item Render Attribute
	 * 
	 * Adds the render attributes for a specified item
	 *
	 * @since  1.2.0
	 * @param  key|string 	The render attribute key for the item
	 * @param. index|int 	The index of the item. Defaults to 0 
	 * @return void
	 */
	protected function add_item_render_attribute( $key, $index = 0 ) {

		$this->add_render_attribute( $key, [
			'class' => [
				'ee-breadcrumbs__item',
			],
			'itemprop' 	=> 'itemListElement',
			'position' 	=> $index,
			'itemscope' => '',
			'itemtype' 	=> 'http://schema.org/ListItem',
		] );
	}

	/**
	 * Add Link Render Attribute
	 * 
	 * Adds the render attributes for the item link
	 *
	 * @since  1.2.0
	 * @param  key|string 	The render attribute key for the item
	 * @return void
	 */
	protected function add_link_render_attribute( $key ) {
		$this->add_render_attribute( $key, [
			'class' => [
				'ee-breadcrumbs__crumb',
			],
			'itemprop' 	=> 'item',
			'rel' 		=> 'v:url',
			'property' 	=> 'v:title',
		] );
	}

	/**
	 * Content Template
	 * 
	 * Javascript content template for quick rendering. None in this case
	 *
	 * @since  1.2.0
	 * @return void
	 */
	protected function _content_template() {}
}
