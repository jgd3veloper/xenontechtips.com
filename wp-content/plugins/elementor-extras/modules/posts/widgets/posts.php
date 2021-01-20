<?php
namespace ElementorExtras\Modules\Posts\Widgets;

// Elementor Extras Classes
use ElementorExtras\Utils;
use ElementorExtras\Group_Control_Button_Effect;
use ElementorExtras\Group_Control_Transition;
use ElementorExtras\Base\Extras_Widget;
use ElementorExtras\Modules\Posts\Skins;
use ElementorExtras\Modules\Posts\Module as PostsModule;

// Elementor Classes
use Elementor\Repeater;
use Elementor\Scheme_Color;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Css_Filter;
use Elementor\Scheme_Typography;
use Elementor\Core\Base\Document;

// Elementor Pro Classes
use ElementorPro\Modules\QueryControl\Controls\Group_Control_Related;
use ElementorPro\Modules\QueryControl\Controls\Group_Control_Posts;
use ElementorPro\Modules\QueryControl\Module as Module_Query;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Posts
 *
 * @since 1.6.0
 */
class Posts extends Extras_Widget {

	/**
	 * Query
	 *
	 * @since  1.6.0
	 * @var    \WP_Query
	 */
	private $_query = null;

	/**
	 * Filters
	 *
	 * @since  1.6.0
	 * @var    array
	 */
	private $_filters = [];

	/**
	 * Has template content
	 *
	 * @since  1.6.0
	 * @var    bool
	 */
	protected $_has_template_content = false;

	/**
	 * Get Name
	 * 
	 * Get the name of the widget
	 *
	 * @since  1.6.0
	 * @return string
	 */
	public function get_name() {
		return 'posts-extra';
	}

	/**
	 * Get Title
	 * 
	 * Get the title of the widget
	 *
	 * @since  1.6.0
	 * @return string
	 */
	public function get_title() {
		return __( 'Posts Extra', 'elementor-extras' );
	}

	/**
	 * Get Icon
	 * 
	 * Get the name of the widget
	 *
	 * @since  1.6.0
	 * @return string
	 */
	public function get_icon() {
		return 'nicon nicon-posts';
	}

	/**
	 * Get Script Depends
	 * 
	 * A list of scripts that the widgets is depended in
	 *
	 * @since  1.6.0
	 * @return array
	 */
	public function get_script_depends() {
		return [
			'jquery-resize-ee',
			'infinite-scroll-ee',
			'isotope',
			'filtery',
		];
	}

	/**
	 * Requires elementor pro
	 * 
	 * Sets the widget requirements for Elementor Pro
	 *
	 * @since  1.6.0
	 * @return bool
	 */
	public static function requires_elementor_pro() {
		return true;
	}

	/**
	 * Register Skins
	 *
	 * @since  1.6.0
	 * @return void
	 */
	protected function _register_skins() {
		$this->add_skin( new Skins\Skin_Classic( $this ) );
		$this->add_skin( new Skins\Skin_Carousel( $this ) );
	}

	/**
	 * Get Query
	 *
	 * @since  1.6.0
	 * @return object|\WP_Query
	 */
	public function get_query() {
		return $this->_query;
	}

	/**
	 * Set filters
	 * 
	 * Set the filter terms
	 *
	 * @since  1.6.0
	 * @param  taxonomy  	The taxonomy for the terms
	 * @return void
	 */
	public function set_filters( $taxonomy = null ) {
		if ( ! $taxonomy )
			return;

		if ( 'yes' === $this->get_skin_setting( 'filters_show_all' ) && 'yes' === $this->get_skin_setting( 'infinite_scroll' ) ) {
			$this->set_all_filters( $taxonomy );
		} else {
			$this->set_query_filters( $taxonomy );
		}
	}

	/**
	 * Set all Filters
	 * 
	 * Set filters to all available for this taxonomy
	 *
	 * @since  1.6.0
	 * @param  taxonomy  	The taxonomy for the terms
	 * @return void
	 */
	public function set_all_filters( $taxonomy ) {

		$terms = get_terms( array(
			'taxonomy' 	=> $taxonomy,
			'exclude'	=> $this->get_skin_setting( 'filters_taxonomy_exclude_' . str_replace( '-', '_', $taxonomy ) ),
			'orderby' 	=> $this->get_skin_setting( 'filters_orderby' ),
			'order' 	=> $this->get_skin_setting( 'filters_order' ),
		) );

		// Set filters for filter menu
		foreach ($terms as $term) {
			$this->_filters[ $term->term_id ] = $term;
		}

		$this->set_posts_filters( $taxonomy );
	}

	/**
	 * Set Query Filters
	 * 
	 * Set filters to those corresponding to queries posts
	 *
	 * @since  1.6.0
	 * @param  taxonomy  	The taxonomy for the terms
	 * @return void
	 */
	public function set_query_filters( $taxonomy ) {

		$taxonomy_terms = get_terms([
			'taxonomy' => $taxonomy,
			'exclude'	=> $this->get_skin_setting( 'filters_taxonomy_exclude_' . str_replace( '-', '_', $taxonomy ) ),
			'orderby' 	=> $this->get_skin_setting( 'filters_orderby' ),
			'order' 	=> $this->get_skin_setting( 'filters_order' ),
		]);

		$posts_filters = array();

		// Populate our filters with all terms
		foreach ( $taxonomy_terms as $key => $term ) {
			$this->_filters[ $term->term_id ] = $term;
		}

		// Create an array of query filters
		foreach ( $this->_query->posts as $post ) {
			$terms = wp_get_post_terms( $post->ID, $taxonomy );

			foreach ($terms as $term) {
				if ( ! array_key_exists( $term->term_id, $posts_filters ) ) {
					$posts_filters[ $term->term_id ] = $term;
				}
			}
		}

		// Filter the terms to include only our filters
		foreach ($this->_filters as $key => $_filter) {
			if ( ! array_key_exists( $key, $posts_filters ) ) {
				unset( $this->_filters[ $key ] );
			}
		}

		$this->set_posts_filters( $taxonomy );
	}

	/**
	 * Set Posts Filters
	 * 
	 * Get terms from posts
	 *
	 * @since  1.6.0
	 * @param  taxonomy  	The taxonomy for the terms
	 * @return void
	 */
	public function set_posts_filters( $taxonomy ) {

		// Set filters for each post
		foreach ( $this->_query->posts as $post ) {

			$filters = [];

			// Get post terms
			$post_terms = wp_get_post_terms( $post->ID, $taxonomy, array(
				'orderby' 	=> $this->get_skin_setting( 'filters_orderby' ),
				'order' 	=> $this->get_skin_setting( 'filters_order' ),
			) );

			// Populate array with post terms
			foreach ( $post_terms as $post_term ) {
				$filters[ $post_term->term_id ] = $post_term;
			}

			// Set pot filters
			$post->filters = $filters;
		}
	}

	/**
	 * Get Filters
	 *
	 * @since  1.6.0
	 * @return _filters|array
	 */
	public function get_filters() {
		return $this->_filters;
	}

	/**
	 * Get Terms
	 *
	 * @since  1.6.0
	 * @return array
	 */
	public function get_terms() {
		$settings 	= $this->get_settings();
		$taxonomies = $settings['post_terms_taxonomy'];
		$_terms 	= Utils::get_terms( $taxonomies );

		return $_terms;
	}

	/**
	 * Get Current Page
	 *
	 * @since  1.6.0
	 * @return array
	 */
	public function get_current_page() {
		if ( '' === $this->get_skin_setting( 'infinite_scroll' ) && '' === $this->get_skin_setting( 'pagination' ) ) {
			return 1;
		}

		if ( 'yes' === $this->get_skin_setting( 'pagination_multiple' ) && '' === $this->get_skin_setting( 'infinite_scroll' ) ) {
			$widget = isset( $_GET['posts'] ) ? $_GET['posts'] : '';

			if ( '' !== $widget && $widget !== $this->get_id() ) {
				return 1;
			}
		}

		return max( 1, get_query_var( 'paged' ), get_query_var( 'page' ) );
	}

	/**
	 * Register Widget Controls
	 *
	 * @since  1.6.0
	 * @return void
	 */
	protected function _register_controls() {

		$this->register_layout_content_controls();
		$this->register_query_content_controls();

		$this->register_media_content_controls();
		$this->register_terms_content_controls();
		$this->register_title_content_controls();
		$this->register_metas_content_controls();
		$this->register_excerpt_content_controls();
		$this->register_button_content_controls();
		$this->register_order_content_controls();

		$this->register_post_style_controls();
		$this->register_header_style_controls();
		$this->register_media_style_controls();
		$this->register_body_style_controls();
		$this->register_footer_style_controls();

		$this->register_terms_style_controls();
		$this->register_metas_style_controls();
		$this->register_title_style_controls();
		$this->register_excerpt_style_controls();
		$this->register_button_style_controls();
		$this->register_hover_animation_controls();

		$this->register_advanced_controls();
	}

	/**
	 * Register Layout Content Controls
	 *
	 * @since  1.6.0
	 * @return void
	 */
	protected function register_layout_content_controls() {

		$this->start_controls_section(
			'section_layout',
			[
				'label' => __( 'Layout', 'elementor-extras' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

			$this->add_control(
				'skin_source',
				[
					'label' 	=> __( 'Post Skin', 'elementor-extras' ),
					'type' 		=> Controls_Manager::SELECT,
					'default' 	=> '',
					'options' 	=> [
						''			=> __( 'Default', 'elementor-extras' ),
						'template' 	=> __( 'Template', 'elementor-extras' ),
					],
				]
			);

			$document_types = Utils::elementor()->documents->get_document_types( [
				'show_in_library' => true,
			] );

			$this->add_control(
				'skin_template',
				[
					'label' 		=> __( 'Post Template', 'elementor-extras' ),
					'type' 			=> Module_Query::QUERY_CONTROL_ID,
					'autocomplete' 	=> [
						'object' 	=> Module_Query::QUERY_OBJECT_LIBRARY_TEMPLATE,
						'query' 	=> [
							'meta_query' => [
								[
									'key' 		=> Document::TYPE_META_KEY,
									'value' 	=> array_keys( $document_types ),
									'compare' 	=> 'IN',
								],
							],
						],
					],
					'condition' => [
						'skin_source' => 'template',
					],
				]
			);

			$this->add_responsive_control(
				'columns',
				[
					'label' 	=> __( 'Columns', 'elementor-extras' ),
					'type' 		=> Controls_Manager::SELECT,
					'default' 	=> '3',
					'tablet_default' => '2',
					'mobile_default' => '1',
					'options' => [
						''	=> __( 'Default', 'elementor-extras' ),
						'1' => '1',
						'2' => '2',
						'3' => '3',
						'4' => '4',
						'5' => '5',
						'6' => '6',
					],
					'prefix_class' => 'ee-grid-columns%s-',
					'frontend_available' => true,
				]
			);

			$this->add_control(
				'posts_per_page',
				[
					'label' => __( 'Posts Per Page', 'elementor-extras' ),
					'type' => Controls_Manager::NUMBER,
					'default' => 6,
				]
			);

		$this->end_controls_section();

	}

	/**
	 * Register Query Content Controls
	 *
	 * @since  1.6.0
	 * @return void
	 */
	protected function register_query_content_controls() {
		
		$this->start_controls_section(
			'section_query',
			[
				'label' => __( 'Query', 'elementor-extras' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

			$this->add_group_control(
				Group_Control_Related::get_type(),
				[
					'name' => 'posts',
					'presets' => [ 'full' ],
					'exclude' => [
						'posts_per_page', //use the one from Layout section
						'ignore_sticky_posts'
					],
				]
			);

			// $this->update_control( 'posts_post_type', [
			// 	'type' 		=> Controls_Manager::SELECT2,
			// 	'multiple'	=> true,
			// ] );

		$this->end_controls_section();

		$this->start_injection( [
			'at' => 'after',
			'of' => 'posts_select_date',
		] );

			$this->update_control( 'posts_orderby', [
				'options' => [
					'post_date' 		=> __( 'Date', 'elementor-extras' ),
					'post_title' 		=> __( 'Title', 'elementor-extras' ),
					'menu_order' 		=> __( 'Menu Order', 'elementor-extras' ),
					'rand' 				=> __( 'Random', 'elementor-extras' ),
					'meta_value'		=> __( 'Meta Value (text)', 'elementor-extras' ),
					'meta_value_num'	=> __( 'Meta Value (number)', 'elementor-extras' )
				],
			] );

		$this->end_injection();

		$this->start_injection( [
			'at' => 'after',
			'of' => 'posts_orderby',
		] );

			$this->add_control( 'posts_orderby_meta_key',
				[
					'label' 		=> __( 'Meta Key', 'elementor-extras' ),
					'type' 			=> Controls_Manager::TEXT,
					'default' 		=> '',
					'condition' => [
						'posts_orderby' => 'meta_value',
					],
				]
			);

		$this->end_injection();

		$this->start_injection( [
			'at' => 'after',
			'of' => 'posts_order',
		] );

			$this->add_control(
				'sticky_posts',
				[
					'label' 		=> __( 'Sticky Posts', 'elementor-extras' ),
					'type' 			=> Controls_Manager::SWITCHER,
					'default'		=> '',
					'separator'		=> 'before',
					'return_value' 	=> 'yes',
				]
			);

			$this->add_control(
				'sticky_only',
				[
					'label' 		=> __( 'Show Only Sticky Posts', 'elementor-extras' ),
					'type' 			=> Controls_Manager::SWITCHER,
					'default'		=> '',
					'condition' 	=> [
						'sticky_posts!' => '',
						'posts_post_type!' => 'by_id',
					],
					'return_value' 	=> 'yes',
				]
			);

		$this->end_injection();
	}

	/**
	 * Register Order Content Controls
	 *
	 * @since  1.6.0
	 * @return void
	 */
	protected function register_order_content_controls() {
		
		$this->start_controls_section(
			'section_order',
			[
				'label' => __( 'Order', 'elementor-extras' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
				'condition' => [
					'skin_source' => '',
				],
			]
		);

			$this->add_control(
				'post_areas_order_heading',
				[
					'label' 	=> __( 'Areas', 'elementor-extras' ),
					'type' 		=> Controls_Manager::HEADING,
				]
			);

			$this->add_control(
				'order_areas_description',
				[
					'type' 				=> Controls_Manager::RAW_HTML,
					'raw' 				=> __( 'Give each area an order number to define the order in which they appear in the post.', 'elementor-extras' ),
					'content_classes' 	=> 'elementor-panel-alert elementor-panel-alert-info',
				]
			);

			$this->add_control(
				'post_header_order',
				[
					'label' 	=> __( 'Header', 'elementor-extras' ),
					'type' 		=> Controls_Manager::NUMBER,
					'default' 	=> 1,
					'min'     	=> 1,
					'selectors' => [
						'{{WRAPPER}} .ee-post__header' => 'order: {{VALUE}};',
					],
				]
			);

			$this->add_control(
				'post_media_order',
				[
					'label' 	=> __( 'Media', 'elementor-extras' ),
					'type' 		=> Controls_Manager::NUMBER,
					'default' 	=> 1,
					'min'     	=> 1,
					'selectors' => [
						'{{WRAPPER}} .ee-post__media' => 'order: {{VALUE}};',
					],
				]
			);

			$this->add_control(
				'post_body_order',
				[
					'label' 	=> __( 'Body', 'elementor-extras' ),
					'type' 		=> Controls_Manager::NUMBER,
					'default' 	=> 1,
					'min'     	=> 1,
					'selectors' => [
						'{{WRAPPER}} .ee-post__body,
						 {{WRAPPER}} .ee-post--horizontal .ee-post__content' => 'order: {{VALUE}};',
					],
				]
			);

			$this->add_control(
				'post_footer_order',
				[
					'label' 	=> __( 'Footer', 'elementor-extras' ),
					'type' 		=> Controls_Manager::NUMBER,
					'default' 	=> 1,
					'min'     	=> 1,
					'selectors' => [
						'{{WRAPPER}} .ee-post__footer' => 'order: {{VALUE}};',
					],
				]
			);

			$this->add_control(
				'post_parts_order_heading',
				[
					'label' 	=> __( 'Parts', 'elementor-extras' ),
					'type' 		=> Controls_Manager::HEADING,
					'separator' => 'before',
				]
			);

			$this->add_control(
				'order_parts_description',
				[
					'type' 				=> Controls_Manager::RAW_HTML,
					'raw' 				=> __( 'Give each post part an order number to define the order in which they appear in post areas.', 'elementor-extras' ),
					'content_classes' 	=> 'elementor-panel-alert elementor-panel-alert-info',
				]
			);

			$this->add_control(
				'post_terms_order',
				[
					'label' 	=> __( 'Terms', 'elementor-extras' ),
					'type' 		=> Controls_Manager::NUMBER,
					'default' 	=> 1,
					'min'     	=> 1,
					'condition' => [
						'post_terms_position!' => '',
					],
				]
			);

			$this->add_control(
				'post_title_order',
				[
					'label' 	=> __( 'Title', 'elementor-extras' ),
					'type' 		=> Controls_Manager::NUMBER,
					'default' 	=> 1,
					'min'     	=> 1,
					'condition' => [
						'post_title_position!' => '',
					],
				]
			);

			$this->add_control(
				'post_excerpt_order',
				[
					'label' 	=> __( 'Excerpt', 'elementor-extras' ),
					'type' 		=> Controls_Manager::NUMBER,
					'default' 	=> 1,
					'min'     	=> 1,
					'condition' => [
						'post_excerpt_position!' => '',
					],
				]
			);

			$this->add_control(
				'post_button_order',
				[
					'label' 	=> __( 'Button', 'elementor-extras' ),
					'type' 		=> Controls_Manager::NUMBER,
					'default' 	=> 1,
					'min'     	=> 1,
					'condition' => [
						'post_button_position!' => '',
					],
				]
			);

			$this->add_control(
				'post_metas_order',
				[
					'label' 	=> __( 'Metas', 'elementor-extras' ),
					'type' 		=> Controls_Manager::NUMBER,
					'default' 	=> 1,
					'min'     	=> 1,
				]
			);

			$this->add_control(
				'post_metas_order_heading',
				[
					'label' 	=> __( 'Metas', 'elementor-extras' ),
					'type' 		=> Controls_Manager::HEADING,
					'separator' => 'before',
				]
			);

			$this->add_control(
				'post_metas_order_description',
				[
					'type' 				=> Controls_Manager::RAW_HTML,
					'raw' 				=> __( 'Order each meta inside any list of metas', 'elementor-extras' ),
					'content_classes' 	=> 'elementor-panel-alert elementor-panel-alert-info',
				]
			);

			$this->add_control(
				'post_author_order',
				[
					'label' 	=> __( 'Author', 'elementor-extras' ),
					'type' 		=> Controls_Manager::NUMBER,
					'default' 	=> 1,
					'min'     	=> 1,
					'condition' => [
						'post_author_position!' => '',
					],
				]
			);

			$this->add_control(
				'post_date_order',
				[
					'label' 	=> __( 'Date', 'elementor-extras' ),
					'type' 		=> Controls_Manager::NUMBER,
					'default' 	=> 1,
					'min'     	=> 1,
					'condition' => [
						'post_date_position!' => '',
					],
				]
			);

			if ( is_woocommerce_active() ) {
				$this->add_control(
					'post_price_order',
					[
						'label' 	=> __( 'Price', 'elementor-extras' ),
						'type' 		=> Controls_Manager::NUMBER,
						'default' 	=> 1,
						'min'     	=> 1,
						'condition' => [
							'post_price_position!' => '',
							'posts_post_type' => ['product', 'current_query'],
						],
					]
				);
			}

			$this->add_control(
				'post_comments_order',
				[
					'label' 	=> __( 'Comments', 'elementor-extras' ),
					'type' 		=> Controls_Manager::NUMBER,
					'default' 	=> 1,
					'min'     	=> 1,
					'condition' => [
						'post_comments_position!' => '',
					],
				]
			);

		$this->end_controls_section();

	}

	/**
	 * Register Advanced Controls
	 *
	 * @since  1.6.0
	 * @return void
	 */
	protected function register_advanced_controls() {
		
		$this->start_controls_section(
			'section_advanced',
			[
				'label' => __( 'Advanced', 'elementor-extras' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

			$this->add_control(
				'nothing_found_message',
				[
					'label' 	=> __( 'Nothing Found Message', 'elementor-extras' ),
					'type' 		=> Controls_Manager::TEXTAREA,
					'default' 	=> __( 'It seems we can\'t find what you\'re looking for.', 'elementor-extras' ),
					'dynamic' 	=> [
						'active' => true,
					],
				]
			);


		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_advanced',
			[
				'tab' 		=> Controls_Manager::TAB_STYLE,
				'label' 	=> __( 'Advanced', 'elementor-extras' ),
				'condition' => [
					'nothing_found_message!' => '',
				],
			]
		);

			$this->add_control(
				'nothing_found_style_heading',
				[
					'label' 	=> __( 'Nothing Found Message', 'elementor-extras' ),
					'type' 		=> Controls_Manager::HEADING,
				]
			);

			$this->add_control(
				'nothing_found_color',
				[
					'label' 	=> __( 'Color', 'elementor-extras' ),
					'type' 		=> Controls_Manager::COLOR,
					'scheme' 	=> [
						'type' 	=> Scheme_Color::get_type(),
						'value' => Scheme_Color::COLOR_3,
					],
					'selectors' => [
						'{{WRAPPER}} .ee-posts__nothing-found' => 'color: {{VALUE}};',
					],
				]
			);

			$this->add_group_control(
				Group_Control_Typography::get_type(),
				[
					'name' 		=> 'nothing_found_typography',
					'scheme' 	=> Scheme_Typography::TYPOGRAPHY_3,
					'selector' 	=> '{{WRAPPER}} .ee-posts__nothing-found',
				]
			);

		$this->end_controls_section();

	}

	/**
	 * Register Media Content Controls
	 *
	 * @since  1.6.0
	 * @return void
	 */
	protected function register_media_content_controls() {

		$this->start_controls_section(
			'section_media',
			[
				'label' => __( 'Media', 'elementor-extras' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
				'condition' => [
					'skin_source' => '',
				],
			]
		);

			$this->add_control(
				'post_media',
				[
					'label' 		=> __( 'Show', 'elementor-extras' ),
					'type' 			=> Controls_Manager::SWITCHER,
					'default'		=> 'yes',
					'return_value' 	=> 'yes',
				]
			);

			$this->add_control(
				'image',
				[
					'label' => __( 'Placeholder Image', 'elementor-extras' ),
					'description' => __( 'An image to be used for all posts that DO NOT have a featured image set.', 'elementor-extras' ),
					'type' => Controls_Manager::MEDIA,
					'dynamic' => [
						'active' => true,
					],
					'default' => [
						'url' => '',
					],
					'condition'		=> [
						'post_media!' => '',
					],
				]
			);

			$this->add_control(
				'post_media_link',
				[
					'label' 		=> __( 'Link to post', 'elementor-extras' ),
					'type' 			=> Controls_Manager::SWITCHER,
					'default'		=> 'yes',
					'return_value' 	=> 'yes',
					'condition'		=> [
						'post_media!' => '',
					],
				]
			);

			$this->add_control(
				'post_media_position',
				[
					'label' 		=> __( 'Position', 'elementor-extras' ),
					'type' 			=> Controls_Manager::CHOOSE,
					'default' 		=> '',
					'options' 		=> [
						'left' 		=> [
							'title' => __( 'Left', 'elementor-extras' ),
							'icon' 	=> 'eicon-h-align-left',
						],
						'' 			=> [
							'title' => __( 'Block', 'elementor-extras' ),
							'icon' 	=> 'eicon-v-align-top',
						],
						'right' 	=> [
							'title' => __( 'Right', 'elementor-extras' ),
							'icon' 	=> 'eicon-h-align-right',
						],
					],
					'label_block'	=> false,
					'condition'		=> [
						'post_media!' 	=> '',
					],
				]
			);

			$this->add_control(
				'post_media_collapse',
				[
					'label' 		=> __( 'Collapse on', 'elementor-extras' ),
					'type' 			=> Controls_Manager::SELECT,
					'default' 		=> 'mobile',
					'options' 		=> [
						'none'		=> __( 'None', 'elementor-extras' ),
						'tablet'	=> __( 'Tablet', 'elementor-extras' ),
						'mobile'	=> __( 'Mobile', 'elementor-extras' ),
					],
					'prefix_class'	=> 'ee-posts-layout-collapse--',
					'condition'		=> [
						'post_media!' 	=> '',
						'post_media_position!' => '',
					],
				]
			);

			$this->add_control(
				'post_media_width',
				[
					'label' 		=> __( 'Width (%)', 'elementor-extras' ),
					'type' 			=> Controls_Manager::SLIDER,
					'range' 		=> [
						'px' 		=> [
							'min' => 20,
							'max' => 80,
						],
					],
					'selectors' 	=> [
						'(tablet+){{WRAPPER}} .ee-post__media' => 'flex-basis: {{SIZE}}%; -ms-flex-preferred-size: {{SIZE}}%;',
						'(tablet+){{WRAPPER}} .ee-post__content' => 'flex-basis: calc( 100% - {{SIZE}}% );',
					],
					'condition'		=> [
						'post_media!' 	=> '',
						'post_media_position!' => '',
					],
				]
			);

			$this->add_control(
				'post_media_custom_height',
				[
					'label' 		=> __( 'Custom Height', 'elementor-extras' ),
					'type' 			=> Controls_Manager::SWITCHER,
					'default'		=> '',
					'return_value' 	=> 'ratio',
					'prefix_class'	=> 'ee-posts-thumbnail-',
					'condition'		=> [
						'post_media!' => '',
					],
				]
			);

			$this->add_responsive_control(
				'post_media_height',
				[
					'label' 		=> __( 'Height', 'elementor-extras' ),
					'type' 			=> Controls_Manager::SLIDER,
					'range' 		=> [
						'px' 		=> [
							'min' => 1,
							'max' => 200,
						],
					],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-post__media:before' => 'padding-bottom: {{SIZE}}%',
					],
					'condition'		=> [
						'post_media!' => '',
						'post_media_custom_height!' => '',
					],
				]
			);

			$this->add_control(
				'post_media_thumbnail_heading',
				[
					'label' 	=> __( 'Thumbnail', 'elementor-extras' ),
					'type' 		=> Controls_Manager::HEADING,
					'separator' => 'before',
					'condition'		=> [
						'post_media!' => '',
					],
				]
			);

			$this->add_group_control(
				Group_Control_Image_Size::get_type(),
				[
					'name' 			=> 'post_media_thumbnail_size',
					'label' 		=> __( 'Thumbnail Size', 'elementor-extras' ),
					'default' 		=> 'large',
					'exclude' 		=> [ 'custom' ],
					'condition'		=> [
						'post_media!' => '',
					],
				]
			);

		$this->end_controls_section();

	}

	/**
	 * Register Terms Content Controls
	 *
	 * @since  1.6.0
	 * @return void
	 */
	protected function register_terms_content_controls() {

		$this->start_controls_section(
			'section_terms',
			[
				'label' => __( 'Terms', 'elementor-extras' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
				'condition' => [
					'skin_source' => '',
				],
			]
		);

			$this->add_control(
				'post_terms_position',
				[
					'label' 		=> __( 'Position', 'elementor-extras' ),
					'type' 			=> Controls_Manager::CHOOSE,
					'default' 		=> 'header',
					'label_block'	=> false,
					'options' 		=> [
						'header'    		=> [
							'title' 	=> __( 'Header', 'elementor-extras' ),
							'icon' 		=> 'nicon nicon-position-header',
						],
						'media'    		=> [
							'title' 	=> __( 'Media', 'elementor-extras' ),
							'icon' 		=> 'nicon nicon-position-media',
						],
						'body'    		=> [
							'title' 	=> __( 'Body', 'elementor-extras' ),
							'icon' 		=> 'nicon nicon-position-body',
						],
						'footer'    		=> [
							'title' 	=> __( 'Footer', 'elementor-extras' ),
							'icon' 		=> 'nicon nicon-position-footer',
						],
						''				=> [
							'title'		=> __( 'Hide', 'elementor-extras' ),
							'icon'		=> 'fa fa-eye-slash',
						],
					],
				]
			);

			$this->add_control(
				'post_terms_link',
				[
					'label' 		=> __( 'Link to term', 'elementor-extras' ),
					'type' 			=> Controls_Manager::SWITCHER,
					'default'		=> 'yes',
					'return_value' 	=> 'yes',
					'condition'		=> [
						'post_terms_position!' => '',
					],
				]
			);

			$this->add_control(
				'post_terms_taxonomy',
				[
					'label' 		=> __( 'Taxonomies', 'elementor-extras' ),
					'type' 			=> Controls_Manager::SELECT2,
					'label_block' 	=> true,
					'default' 		=> 'category',
					'multiple'		=> true,
					'options' 		=> Utils::get_taxonomies_options(),
					'condition' 	=> [
						'post_terms_position!' => '',
					],
				]
			);

			$this->add_control(
				'post_terms_count',
				[
					'label'   		=> __( 'Count', 'elementor-extras' ),
					'description' 	=> __( 'How many terms to show (enter -1 to show all terms)', 'elementor-extras' ),
					'type'    		=> Controls_Manager::NUMBER,
					'default' 		=> 1,
					'condition' 	=> [
						'post_terms_position!' => '',
					],
				]
			);

			$this->add_control(
				'post_terms_prefix',
				[
					'label' 		=> __( 'Prefix', 'elementor-extras' ),
					'type' 			=> Controls_Manager::TEXT,
					'default' 		=> '',
					'placeholder' 	=> __( 'Posted in', 'elementor-extras' ),
					'condition' => [
						'post_terms_position!' => ''
					],
				]
			);

			$this->add_control(
				'post_terms_separator',
				[
					'label' 		=> __( 'Separator', 'elementor-extras' ),
					'type' 			=> Controls_Manager::TEXT,
					'default' 		=> '·',
					'condition' => [
						'post_terms_position!' => ''
					],
				]
			);

		$this->end_controls_section();

	}

	/**
	 * Register Title Content Controls
	 *
	 * @since  1.6.0
	 * @return void
	 */
	protected function register_title_content_controls() {

		$this->start_controls_section(
			'section_title',
			[
				'label' => __( 'Title', 'elementor-extras' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
				'condition' => [
					'skin_source' => '',
				],
			]
		);

			$this->add_control(
				'post_title_position',
				[
					'label' 		=> __( 'Position', 'elementor-extras' ),
					'type' 			=> Controls_Manager::CHOOSE,
					'default' 		=> 'body',
					'label_block'	=> false,
					'options' 		=> [
						'header'    		=> [
							'title' 	=> __( 'Header', 'elementor-extras' ),
							'icon' 		=> 'nicon nicon-position-header',
						],
						'media'    		=> [
							'title' 	=> __( 'Media', 'elementor-extras' ),
							'icon' 		=> 'nicon nicon-position-media',
						],
						'body'    		=> [
							'title' 	=> __( 'Body', 'elementor-extras' ),
							'icon' 		=> 'nicon nicon-position-body',
						],
						''				=> [
							'title'		=> __( 'Hide', 'elementor-extras' ),
							'icon'		=> 'fa fa-eye-slash',
						],
					],
				]
			);

			$this->add_control(
				'post_title_link',
				[
					'label' 		=> __( 'Link to post', 'elementor-extras' ),
					'type' 			=> Controls_Manager::SWITCHER,
					'default'		=> 'yes',
					'return_value' 	=> 'yes',
					'condition'		=> [
						'post_title_position!' => '',
					],
				]
			);

			$this->add_control(
				'post_title_element',
				[
					'label' 	=> __( 'HTML Element', 'elementor-extras' ),
					'type' 		=> Controls_Manager::SELECT,
					'options' 	=> [
						'h1' 	=> __( 'H1', 'elementor-extras' ),
						'h2' 	=> __( 'H2', 'elementor-extras' ),
						'h3' 	=> __( 'H3', 'elementor-extras' ),
						'h4' 	=> __( 'H4', 'elementor-extras' ),
						'h5' 	=> __( 'H5', 'elementor-extras' ),
						'h6' 	=> __( 'H6', 'elementor-extras' ),
						'div'	=> __( 'div', 'elementor-extras' ),
						'span' 	=> __( 'span', 'elementor-extras' ),
					],
					'default' => 'h2',
				]
			);

		$this->end_controls_section();

	}

	/**
	 * Register Excerpt Content Controls
	 *
	 * @since  1.6.0
	 * @return void
	 */
	protected function register_excerpt_content_controls() {

		$this->start_controls_section(
			'section_excerpt',
			[
				'label' => __( 'Excerpt', 'elementor-extras' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
				'condition' => [
					'skin_source' => '',
				],
			]
		);

			$this->add_control(
				'post_excerpt_position',
				[
					'label' 		=> __( 'Position', 'elementor-extras' ),
					'type' 			=> Controls_Manager::CHOOSE,
					'default' 		=> 'body',
					'label_block'	=> false,
					'options' 		=> [
						'media'    		=> [
							'title' 	=> __( 'Media', 'elementor-extras' ),
							'icon' 		=> 'nicon nicon-position-media',
						],
						'body'    		=> [
							'title' 	=> __( 'Body', 'elementor-extras' ),
							'icon' 		=> 'nicon nicon-position-body',
						],
						'footer'    		=> [
							'title' 	=> __( 'Footer', 'elementor-extras' ),
							'icon' 		=> 'nicon nicon-position-footer',
						],
						''				=> [
							'title'		=> __( 'Hide', 'elementor-extras' ),
							'icon'		=> 'fa fa-eye-slash',
						],
					],
				]
			);

			$this->add_control(
				'post_excerpt_trim_custom',
				[
					'label' 		=> __( 'Trim Custom Excerpts', 'elementor-extras' ),
					'description'	=> __( 'Custom excerpts are set manually in the Excerpt field for each post. Enable this if you want to trim those down to the above length as well.' ),
					'type' 			=> Controls_Manager::SWITCHER,
					'default'		=> '',
					'return_value' 	=> 'yes',
					'condition' 	=> [
						'post_excerpt_position!' => '',
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
						'post_excerpt_position!' => '',
					],
				]
			);

			$this->add_control(
				'post_excerpt_more',
				[
					'label' 	=> __( 'Trimmed Suffix', 'elementor-extras' ),
					'type' 		=> Controls_Manager::TEXT,
					'default' 	=> '&hellip;',
					'condition' => [
						'post_excerpt_position!' => '',
					],
				]
			);

		$this->end_controls_section();

	}

	/**
	 * Register Button Content Controls
	 *
	 * @since  1.6.0
	 * @return void
	 */
	protected function register_button_content_controls() {

		$this->start_controls_section(
			'section_button',
			[
				'label' => __( 'Button', 'elementor-extras' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
				'condition' => [
					'skin_source' => '',
				],
			]
		);

			$this->add_control(
				'post_button_position',
				[
					'label' 		=> __( 'Position', 'elementor-extras' ),
					'type' 			=> Controls_Manager::CHOOSE,
					'default' 		=> '',
					'label_block'	=> false,
					'options' 		=> [
						'media'    		=> [
							'title' 	=> __( 'Media', 'elementor-extras' ),
							'icon' 		=> 'nicon nicon-position-media',
						],
						'body'    		=> [
							'title' 	=> __( 'Body', 'elementor-extras' ),
							'icon' 		=> 'nicon nicon-position-body',
						],
						'footer'    		=> [
							'title' 	=> __( 'Footer', 'elementor-extras' ),
							'icon' 		=> 'nicon nicon-position-footer',
						],
						''				=> [
							'title'		=> __( 'Hide', 'elementor-extras' ),
							'icon'		=> 'fa fa-eye-slash',
						],
					],
				]
			);

			$button_type_options = [
				'' => __( 'Read More', 'elementor-extras' ),
			];

			if ( is_woocommerce_active() ) {
				$button_type_options['add_to_cart']	= __( 'Add to Cart', 'elementor-extras' );
			}

			$this->add_control(
				'post_button_type',
				[
					'label' 	=> __( 'Type', 'elementor-extras' ),
					'type' 		=> Controls_Manager::SELECT,
					'default' 	=> '',
					'options' 	=> $button_type_options,
					'condition' => [
						'post_button_position!' => '',
					],
				]
			);

			$this->add_control(
				'post_read_more_text',
				[
					'label' 		=> __( 'Label', 'elementor-extras' ),
					'type' 			=> Controls_Manager::TEXT,
					'default' 		=> __( 'Read more', 'elementor-extras' ),
					'condition' 	=> [
						'post_button_position!' => '',
						'post_button_type' => '',
					],
				]
			);

			$this->add_control(
				'post_button_add_to_cart_text',
				[
					'label' 		=> __( 'Label', 'elementor-extras' ),
					'type' 			=> Controls_Manager::TEXT,
					'default' 		=> '',
					'condition' 	=> [
						'post_button_position!' => '',
						'post_button_type' => 'add_to_cart',
					],
				]
			);

		$this->end_controls_section();

	}

	/**
	 * Register Metas Content Controls
	 *
	 * @since  1.6.0
	 * @return void
	 */
	protected function register_metas_content_controls() {

		$this->start_controls_section(
			'section_metas',
			[
				'label' => __( 'Metas', 'elementor-extras' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
				'condition' => [
					'skin_source' => '',
				],
			]
		);

			$this->add_control(
				'post_metas_separator',
				[
					'label' 		=> __( 'Separator', 'elementor-extras' ),
					'type' 			=> Controls_Manager::TEXT,
					'default' 		=> '·',
				]
			);

			$this->register_author_content_controls();
			$this->register_date_content_controls();

			if ( is_woocommerce_active() ) {
			$this->register_price_content_controls(); }

			$this->register_comments_content_controls();

		$this->end_controls_section();
	}

	/**
	 * Register Author Content Controls
	 *
	 * @since  1.6.0
	 * @return void
	 */
	protected function register_author_content_controls() {

		$this->add_control(
			'post_avatar_heading',
			[
				'label' => __( 'Avatar', 'elementor-extras' ),
				'type' 	=> Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => [
					'skin_source' => '',
				],
			]
		);

		$this->add_control(
			'post_avatar_position',
			[
				'label' 		=> __( 'Position', 'elementor-extras' ),
				'type' 			=> Controls_Manager::CHOOSE,
				'default' 		=> 'footer',
				'label_block'	=> false,
				'options' 		=> [
					'header'    	=> [
						'title' 	=> __( 'Header', 'elementor-extras' ),
						'icon' 		=> 'nicon nicon-position-header',
					],
					'media'    		=> [
						'title' 	=> __( 'Media', 'elementor-extras' ),
						'icon' 		=> 'nicon nicon-position-media',
					],
					'body'    		=> [
						'title' 	=> __( 'Body', 'elementor-extras' ),
						'icon' 		=> 'nicon nicon-position-body',
					],
					'footer'    		=> [
						'title' 	=> __( 'Footer', 'elementor-extras' ),
						'icon' 		=> 'nicon nicon-position-footer',
					],
					''				=> [
						'title'		=> __( 'Hide', 'elementor-extras' ),
						'icon'		=> 'fa fa-eye-slash',
					],
				],
			]
		);

		$this->add_control(
			'post_avatar_link',
			[
				'label' 		=> __( 'Link to Author', 'elementor-extras' ),
				'type' 			=> Controls_Manager::SWITCHER,
				'default'		=> '',
				'return_value' 	=> 'yes',
				'condition' 	=> [
					'post_avatar_position!' => '',
					'post_avatar_position!' => 'media',
				],
			]
		);

		$this->add_control(
			'post_author_heading',
			[
				'label' => __( 'Author', 'elementor-extras' ),
				'type' 	=> Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'post_author_position',
			[
				'label' 		=> __( 'Position', 'elementor-extras' ),
				'type' 			=> Controls_Manager::CHOOSE,
				'default' 		=> 'footer',
				'label_block'	=> false,
				'options' 		=> [
					'header'    		=> [
						'title' 	=> __( 'Header', 'elementor-extras' ),
						'icon' 		=> 'nicon nicon-position-header',
					],
					'media'    		=> [
						'title' 	=> __( 'Media', 'elementor-extras' ),
						'icon' 		=> 'nicon nicon-position-media',
					],
					'body'    		=> [
						'title' 	=> __( 'Body', 'elementor-extras' ),
						'icon' 		=> 'nicon nicon-position-body',
					],
					'footer'    		=> [
						'title' 	=> __( 'Footer', 'elementor-extras' ),
						'icon' 		=> 'nicon nicon-position-footer',
					],
					''				=> [
						'title'		=> __( 'Hide', 'elementor-extras' ),
						'icon'		=> 'fa fa-eye-slash',
					],
				],
			]
		);

		$this->add_control(
			'post_author_link',
			[
				'label' 		=> __( 'Link to Author', 'elementor-extras' ),
				'type' 			=> Controls_Manager::SWITCHER,
				'default'		=> '',
				'return_value' 	=> 'yes',
				'condition' 	=> [
					'post_author_position!' => '',
					'post_author_position!' => 'media',
				],
			]
		);

		$this->add_control(
			'post_author_prefix',
			[
				'label' 		=> __( 'Prefix', 'elementor-extras' ),
				'type' 			=> Controls_Manager::TEXT,
				'default' 		=> '',
				'placeholder' 	=> __( 'Posted by', 'elementor-extras' ),
				'condition' => [
					'post_author_position!' => ''
				],
			]
		);

	}

	/**
	 * Register Price Content Controls
	 *
	 * @since  1.6.0
	 * @return void
	 */
	protected function register_price_content_controls() {

		$this->add_control(
			'post_price_heading',
			[
				'label' => __( 'Price', 'elementor-extras' ),
				'type' 	=> Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => [
					'posts_post_type' => ['product', 'current_query'],
					'skin_source' => '',
				],
			]
		);

		$this->add_control(
			'post_price_position',
			[
				'label' 		=> __( 'Position', 'elementor-extras' ),
				'type' 			=> Controls_Manager::CHOOSE,
				'default' 		=> 'footer',
				'label_block'	=> false,
				'options' 		=> [
					'header'    		=> [
						'title' 	=> __( 'Header', 'elementor-extras' ),
						'icon' 		=> 'nicon nicon-position-header',
					],
					'media'    		=> [
						'title' 	=> __( 'Media', 'elementor-extras' ),
						'icon' 		=> 'nicon nicon-position-media',
					],
					'body'    		=> [
						'title' 	=> __( 'Body', 'elementor-extras' ),
						'icon' 		=> 'nicon nicon-position-body',
					],
					'footer'    		=> [
						'title' 	=> __( 'Footer', 'elementor-extras' ),
						'icon' 		=> 'nicon nicon-position-footer',
					],
					''				=> [
						'title'		=> __( 'Hide', 'elementor-extras' ),
						'icon'		=> 'fa fa-eye-slash',
					],
				],
				'condition' => [
					'posts_post_type' => ['product', 'current_query'],
				],
			]
		);

	}

	/**
	 * Register Date Content Controls
	 *
	 * @since  1.6.0
	 * @return void
	 */
	protected function register_date_content_controls() {

		$this->add_control(
			'post_date_heading',
			[
				'label' => __( 'Date', 'elementor-extras' ),
				'type' 	=> Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => [
					'skin_source' => '',
				],
			]
		);

		$this->add_control(
			'post_date_position',
			[
				'label' 		=> __( 'Position', 'elementor-extras' ),
				'type' 			=> Controls_Manager::CHOOSE,
				'default' 		=> 'footer',
				'label_block'	=> false,
				'options' 		=> [
					'header'    		=> [
						'title' 	=> __( 'Header', 'elementor-extras' ),
						'icon' 		=> 'nicon nicon-position-header',
					],
					'media'    		=> [
						'title' 	=> __( 'Media', 'elementor-extras' ),
						'icon' 		=> 'nicon nicon-position-media',
					],
					'body'    		=> [
						'title' 	=> __( 'Body', 'elementor-extras' ),
						'icon' 		=> 'nicon nicon-position-body',
					],
					'footer'    		=> [
						'title' 	=> __( 'Footer', 'elementor-extras' ),
						'icon' 		=> 'nicon nicon-position-footer',
					],
					''				=> [
						'title'		=> __( 'Hide', 'elementor-extras' ),
						'icon'		=> 'fa fa-eye-slash',
					],
				],
			]
		);

		$this->add_control(
			'post_date_prefix',
			[
				'label' 		=> __( 'Date Prefix', 'elementor-extras' ),
				'type' 			=> Controls_Manager::TEXT,
				'default' 		=> '',
				'placeholder' 	=> __( 'on', 'elementor-extras' ),
				'condition' => [
					'post_date_position!' => ''
				],
			]
		);

		$this->add_control(
			'post_date_time',
			[
				'label' 		=> __( 'Show Time', 'elementor-extras' ),
				'type' 			=> Controls_Manager::SWITCHER,
				'default'		=> 'yes',
				'return_value' 	=> 'yes',
				'condition' 	=> [
					'post_date_position!' => ''
				],
				'separator'		=> 'before',
			]
		);

		$this->add_control(
			'post_date_time_prefix',
			[
				'label' 		=> __( 'Time Prefix', 'elementor-extras' ),
				'type' 			=> Controls_Manager::TEXT,
				'default' 		=> '',
				'placeholder' 	=> __( 'at', 'elementor-extras' ),
				'condition' => [
					'post_date_time' 		=> 'yes',
					'post_date_position!' 	=> '',
				],
			]
		);

	}

	/**
	 * Register Comments Content Controls
	 *
	 * @since  1.6.0
	 * @return void
	 */
	protected function register_comments_content_controls() {

		$this->add_control(
			'post_comments_heading',
			[
				'label' => __( 'Comments', 'elementor-extras' ),
				'type' 	=> Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => [
					'skin_source' => '',
				],
			]
		);

		$this->add_control(
			'post_comments_position',
			[
				'label' 		=> __( 'Position', 'elementor-extras' ),
				'type' 			=> Controls_Manager::CHOOSE,
				'default' 		=> 'footer',
				'label_block'	=> false,
				'options' 		=> [
					'header'    		=> [
						'title' 	=> __( 'Header', 'elementor-extras' ),
						'icon' 		=> 'nicon nicon-position-header',
					],
					'media'    		=> [
						'title' 	=> __( 'Media', 'elementor-extras' ),
						'icon' 		=> 'nicon nicon-position-media',
					],
					'body'    		=> [
						'title' 	=> __( 'Body', 'elementor-extras' ),
						'icon' 		=> 'nicon nicon-position-body',
					],
					'footer'    		=> [
						'title' 	=> __( 'Footer', 'elementor-extras' ),
						'icon' 		=> 'nicon nicon-position-footer',
					],
					''				=> [
						'title'		=> __( 'Hide', 'elementor-extras' ),
						'icon'		=> 'fa fa-eye-slash',
					],
				],
			]
		);

		$this->add_control(
			'post_comments_prefix',
			[
				'label' 		=> __( 'Prefix', 'elementor-extras' ),
				'type' 			=> Controls_Manager::TEXT,
				'default' 		=> '',
				'placeholder' 	=> __( 'Comments:', 'elementor-extras' ),
				'condition' => [
					'post_comments_position!' => '',
				],
			]
		);

		$this->add_control(
			'post_comments_suffix',
			[
				'label' 		=> __( 'Suffix', 'elementor-extras' ),
				'type' 			=> Controls_Manager::TEXT,
				'default' 		=> '',
				'placeholder' 	=> __( 'comments', 'elementor-extras' ),
				'condition' => [
					'post_comments_position!' => '',
				],
			]
		);

	}

	/**
	 * Register Post Style Controls
	 *
	 * @since  1.6.0
	 * @return void
	 */
	public function register_post_style_controls() {

		$this->start_controls_section(
			'section_style_posts',
			[
				'label' => __( 'Posts', 'elementor-extras' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

			$this->add_responsive_control(
				'posts_text_align',
				[
					'label' 		=> __( 'Align Text', 'elementor-extras' ),
					'type' 			=> Controls_Manager::CHOOSE,
					'default' 		=> '',
					'options' 		=> [
						'left'    		=> [
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
					'selectors'		=> [
						'{{WRAPPER}} .ee-post' 	=> 'text-align: {{VALUE}};',
					]
				]
			);

			$this->add_group_control(
				Group_Control_Transition::get_type(),
				[
					'name' 			=> 'posts',
					'selector' 		=> '{{WRAPPER}} .ee-post',
				]
			);

			$this->start_controls_tabs( 'posts_tabs_hover' );

			$this->start_controls_tab( 'posts_tab_default', [ 'label' => __( 'Default', 'elementor-extras' ) ] );

				$this->add_control(
					'post_background_color',
					[
						'label' 	=> __( 'Background Color', 'elementor-extras' ),
						'type' 		=> Controls_Manager::COLOR,
						'selectors' => [
							'{{WRAPPER}} .ee-post' => 'background-color: {{VALUE}};',
						],
					]
				);

				$this->add_group_control(
					Group_Control_Border::get_type(),
					[
						'name' 		=> 'post_border',
						'label' 	=> __( 'Border', 'elementor-extras' ),
						'selector' 	=> '{{WRAPPER}} .ee-post',
					]
				);

				$this->add_group_control(
					Group_Control_Box_Shadow::get_type(),
					[
						'name' 		=> 'post_box_shadow',
						'selector' 	=> '{{WRAPPER}} .ee-post',
						'separator'	=> '',
					]
				);

			$this->end_controls_tab();

			$this->start_controls_tab( 'posts_tab_hover', [ 'label' => __( 'Hover', 'elementor-extras' ) ] );

				$this->add_control(
					'post_background_color_hover',
					[
						'label' 	=> __( 'Background Color', 'elementor-extras' ),
						'type' 		=> Controls_Manager::COLOR,
						'selectors' => [
							'{{WRAPPER}} .ee-post:hover' => 'background-color: {{VALUE}};',
						],
					]
				);

				$this->add_control(
					'post_border_color_hover',
					[
						'label' 	=> __( 'Border Color', 'elementor-extras' ),
						'type' 		=> Controls_Manager::COLOR,
						'selectors' => [
							'{{WRAPPER}} .ee-post:hover' => 'border-color: {{VALUE}};',
						],
					]
				);

				$this->add_group_control(
					Group_Control_Box_Shadow::get_type(),
					[
						'name' 		=> 'post_box_shadow_hover',
						'selector' 	=> '{{WRAPPER}} .ee-post:hover',
						'separator'	=> '',
					]
				);

			$this->end_controls_tab();

			$this->start_controls_tab( 'posts_tab_sticky', [
				'label' => __( 'Sticky', 'elementor-extras' ),
				'condition' => [
					'sticky_posts!' => '',
				],
			] );

				$this->add_control(
					'post_background_color_sticky',
					[
						'label' 	=> __( 'Background Color', 'elementor-extras' ),
						'type' 		=> Controls_Manager::COLOR,
						'selectors' => [
							'{{WRAPPER}} .ee-post.sticky' => 'background-color: {{VALUE}};',
						],
						'condition' => [
							'sticky_posts!' => '',
						],
					]
				);

				$this->add_control(
					'post_border_color_sticky',
					[
						'label' 	=> __( 'Border Color', 'elementor-extras' ),
						'type' 		=> Controls_Manager::COLOR,
						'selectors' => [
							'{{WRAPPER}} .ee-post.sticky' => 'border-color: {{VALUE}};',
						],
						'condition' => [
							'sticky_posts!' => '',
						],
					]
				);

				$this->add_group_control(
					Group_Control_Box_Shadow::get_type(),
					[
						'name' 		=> 'post_box_shadow_sticky',
						'selector' 	=> '{{WRAPPER}} .ee-post.sticky',
						'condition' => [
							'sticky_posts!' => '',
						],
					]
				);

			$this->end_controls_tab();

			$this->end_controls_tabs();

		$this->end_controls_section();
	}

	/**
	 * Register Header Style Controls
	 *
	 * @since  1.6.0
	 * @return void
	 */
	public function register_header_style_controls() {

		$this->start_controls_section(
			'section_style_header',
			[
				'label' => __( 'Header', 'elementor-extras' ),
				'tab'   => Controls_Manager::TAB_STYLE,
				'conditions' => [
					'relation' => 'and',
					'terms' => [
						$this->get_empty_area_condition( 'header' ),
						[
							'name' 		=> 'skin_source',
							'operator' 	=> '==',
							'value' 	=> '',
						],
					],
				],
			]
		);

			$this->add_responsive_control(
				'header_text_align',
				[
					'label' 		=> __( 'Align Text', 'elementor-extras' ),
					'type' 			=> Controls_Manager::CHOOSE,
					'default' 		=> '',
					'options' 		=> [
						'left'    		=> [
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
					'selectors'		=> [
						'{{WRAPPER}} .ee-post__header' 	=> 'text-align: {{VALUE}};',
					],
					'conditions' => $this->get_empty_area_condition( 'header' ),
				]
			);

			$this->add_responsive_control(
				'header_padding',
				[
					'label' 		=> __( 'Padding', 'elementor-extras' ),
					'type' 			=> Controls_Manager::DIMENSIONS,
					'size_units' 	=> [ 'px', 'em', '%' ],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-post__header' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
					'conditions' => $this->get_empty_area_condition( 'header' ),
				]
			);

			$this->add_control(
				'header_border_radius',
				[
					'type' 			=> Controls_Manager::DIMENSIONS,
					'label' 		=> __( 'Border Radius', 'elementor-extras' ),
					'size_units' 	=> [ 'px', '%' ],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-post__header' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
					'conditions' => $this->get_empty_area_condition( 'header' ),
				]
			);

			$this->start_controls_tabs( 'header_tabs_hover' );

			$this->start_controls_tab( 'header_tab_default', [ 'label' => __( 'Default', 'elementor-extras' ) ] );

				$this->add_control(
					'header_background_color',
					[
						'label' 	=> __( 'Background Color', 'elementor-extras' ),
						'type' 		=> Controls_Manager::COLOR,
						'selectors' => [
							'{{WRAPPER}} .ee-post__header' => 'background-color: {{VALUE}};',
						],
						'conditions' => $this->get_empty_area_condition( 'header' ),
					]
				);

				$this->add_control(
					'header_color',
					[
						'label' 	=> __( 'Color', 'elementor-extras' ),
						'type' 		=> Controls_Manager::COLOR,
						'selectors' => [
							'{{WRAPPER}} .ee-post__header' => 'color: {{VALUE}};',
						],
						'conditions' => $this->get_empty_area_condition( 'header' ),
					]
				);

			$this->end_controls_tab();

			$this->start_controls_tab( 'header_tab_hover', [ 'label' => __( 'Hover', 'elementor-extras' ) ] );

				$this->add_control(
					'header_background_color_hover',
					[
						'label' 	=> __( 'Background Color', 'elementor-extras' ),
						'type' 		=> Controls_Manager::COLOR,
						'selectors' => [
							'{{WRAPPER}} .ee-post:hover .ee-post__header' => 'background-color: {{VALUE}};',
						],
						'conditions' => $this->get_empty_area_condition( 'header' ),
					]
				);

				$this->add_control(
					'header_color_hover',
					[
						'label' 	=> __( 'Color', 'elementor-extras' ),
						'type' 		=> Controls_Manager::COLOR,
						'selectors' => [
							'{{WRAPPER}} .ee-post:hover .ee-post__header' => 'color: {{VALUE}};',
						],
						'conditions' => $this->get_empty_area_condition( 'header' ),
					]
				);

			$this->end_controls_tab();

			$this->end_controls_tabs();

			$this->add_control(
				'header_separator_heading',
				[
					'separator' => 'before',
					'label' 	=> __( 'Separator', 'elementor-extras' ),
					'type' 		=> Controls_Manager::HEADING,
					'condition' => [
						'post_media' => ''
					]
				]
			);

			$this->add_control(
				'header_separator_color',
				[
					'label' 	=> __( 'Color', 'elementor-extras' ),
					'type' 		=> Controls_Manager::COLOR,
					'selectors' => [
						'{{WRAPPER}} .ee-post__header' => 'border-color: {{VALUE}};',
					],
					'condition' => [
						'post_media' => ''
					]
				]
			);

			$this->add_responsive_control(
				'header_separator_size',
				[
					'label' 		=> __( 'Separator Size', 'elementor-extras' ),
					'type' 			=> Controls_Manager::SLIDER,
					'range' 		=> [
						'px' 		=> [
							'min' => 0,
							'max' => 10,
						],
					],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-post__header' => 'border-bottom-width: {{SIZE}}px',
					],
					'condition' => [
						'post_media' => ''
					]
				]
			);

			$header_metas_condition = $this->get_area_metas_controls_conditions( 'header' );

			$this->add_control(
				'header_metas',
				[
					'separator' 	=> 'before',
					'label' 		=> __( '↳ Header Metas', 'elementor-extras' ),
					'type' 			=> Controls_Manager::HEADING,
					'conditions'	=> $header_metas_condition,
				]
			);

			$this->add_control(
				'header_metas_description',
				[
					'type' 				=> Controls_Manager::RAW_HTML,
					'raw' 				=> __( 'Use these to style metas that appear only in the Header area', 'elementor-extras' ),
					'content_classes' 	=> 'elementor-panel-alert elementor-panel-alert-warning',
					'conditions'		=> $header_metas_condition,
				]
			);

			$this->add_control(
				'header_metas_spacing',
				[
					'label' 		=> __( 'Spacing', 'elementor-extras' ),
					'type' 			=> Controls_Manager::SLIDER,
					'range' 		=> [
						'px' 		=> [
							'min' => 0,
							'max' => 48,
						],
					],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-post__header .ee-post__metas__list' => 'margin-left: -{{SIZE}}px',
						'{{WRAPPER}} .ee-post__header .ee-post__meta,
						 {{WRAPPER}} .ee-post__header .ee-post__meta__separator' => 'margin-left: {{SIZE}}px',
					],
					'conditions'	=> $header_metas_condition,
				]
			);

			$this->add_control(
				'header_metas_distance',
				[
					'label' 		=> __( 'Distance', 'elementor-extras' ),
					'type' 			=> Controls_Manager::SLIDER,
					'range' 		=> [
						'px' 		=> [
							'min' => 0,
							'max' => 48,
						],
					],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-post__header .ee-post__metas' => 'margin-bottom: {{SIZE}}px',
					],
					'conditions'	=> $header_metas_condition,
				]
			);

			$this->add_responsive_control(
				'header_metas_text_align',
				[
					'label' 		=> __( 'Align Text', 'elementor-extras' ),
					'type' 			=> Controls_Manager::CHOOSE,
					'default' 		=> '',
					'options' 		=> [
						'left'    		=> [
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
					'selectors'		=> [
						'{{WRAPPER}} .ee-post__header .ee-post__metas--has-metas' => 'text-align: {{VALUE}};',
					],
					'conditions'	=> $header_metas_condition,
				]
			);

			$this->add_responsive_control(
				'header_metas_padding',
				[
					'label' 		=> __( 'Padding', 'elementor-extras' ),
					'type' 			=> Controls_Manager::DIMENSIONS,
					'size_units' 	=> [ 'px', 'em', '%' ],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-post__header .ee-post__metas--has-metas' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
					'conditions'	=> $header_metas_condition,
				]
			);

			$this->add_control(
				'header_metas_color',
				[
					'label' 		=> __( 'Color', 'elementor-extras' ),
					'type' 			=> Controls_Manager::COLOR,
					'selectors' 	=> [
						'{{WRAPPER}} .ee-post__header .ee-post__metas--has-metas' => 'color: {{VALUE}};',
					],
					'conditions'	=> $header_metas_condition,
				]
			);

			$this->add_control(
				'header_metas_background_color',
				[
					'label' 	=> __( 'Background Color', 'elementor-extras' ),
					'type' 		=> Controls_Manager::COLOR,
					'selectors' => [
						'{{WRAPPER}} .ee-post__header .ee-post__metas--has-metas' => 'background-color: {{VALUE}};',
					],
					'conditions'	=> $header_metas_condition,
				]
			);

			$this->add_group_control(
				Group_Control_Typography::get_type(),
				[
					'name' 		=> 'header_metas_typography',
					'label' 	=> __( 'Typography', 'elementor-extras' ),
					'scheme' 	=> Scheme_Typography::TYPOGRAPHY_3,
					'selector' 	=> '{{WRAPPER}} .ee-post__header .ee-post__metas--has-metas .ee-post__meta',
					'conditions'	=> $header_metas_condition,
				]
			);

		$this->end_controls_section();

	}

	/**
	 * Register Media Style Controls
	 *
	 * @since  1.6.0
	 * @return void
	 */
	public function register_media_style_controls() {

		$this->start_controls_section(
			'section_style_media',
			[
				'label' => __( 'Media', 'elementor-extras' ),
				'tab'   => Controls_Manager::TAB_STYLE,
				'condition' => [
					'post_media!' => '',
					'skin_source' => '',
				]
			]
		);

			$this->add_responsive_control(
				'media_margin',
				[
					'label' 		=> __( 'Margin', 'elementor-extras' ),
					'type' 			=> Controls_Manager::DIMENSIONS,
					'size_units' 	=> [ 'px', 'em', '%' ],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-post__media' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
					'condition' => [
						'post_media!' => ''
					]
				]
			);

			$this->add_control(
				'media_border_radius',
				[
					'type' 			=> Controls_Manager::DIMENSIONS,
					'label' 		=> __( 'Border Radius', 'elementor-extras' ),
					'size_units' 	=> [ 'px', '%' ],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-post__media' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
				]
			);

			$this->add_control(
				'media_z_index',
				[
					'label' 		=> __( 'Z-Index', 'elementor-extras' ),
					'type' 			=> Controls_Manager::NUMBER,
					'default' 		=> 1,
					'selectors'		=> [
						'{{WRAPPER}} .ee-post__media' => 'z-index: {{VALUE}};'
					]
				]
			);

			$this->add_control(
				'media_content_vertical_aligment',
				[
					'label' 		=> __( 'Vertical Align', 'elementor-extras' ),
					'label_block' 	=> false,
					'type' 			=> Controls_Manager::CHOOSE,
					'options' 		=> [
						'top' 	=> [
							'title' 	=> __( 'Initial', 'elementor-extras' ),
							'icon' 		=> 'eicon-v-align-top',
						],
						'middle' => [
							'title' => __( 'Center', 'elementor-extras' ),
							'icon' => 'eicon-v-align-middle',
						],
						'bottom' 		=> [
							'title' 	=> __( 'Opposite', 'elementor-extras' ),
							'icon' 		=> 'eicon-v-align-bottom',
						],
						'stretch' => [
							'title' 	=> __( 'Stretch', 'elementor-extras' ),
							'icon' 		=> 'eicon-v-align-stretch',
						],
					],
					'default' 		=> 'top',
					'prefix_class' 	=> 'ee-posts-align-',
				]
			);

			$this->add_responsive_control(
				'media_content_text_align',
				[
					'label' 		=> __( 'Align Text', 'elementor-extras' ),
					'type' 			=> Controls_Manager::CHOOSE,
					'label_block' 	=> false,
					'default' 		=> '',
					'options' 		=> [
						'left'    		=> [
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
					'selectors'		=> [
						'{{WRAPPER}} .ee-post__media__content' 	=> 'text-align: {{VALUE}};',
					]
				]
			);

			$this->add_responsive_control(
				'media_content_padding',
				[
					'label' 		=> __( 'Padding', 'elementor-extras' ),
					'type' 			=> Controls_Manager::DIMENSIONS,
					'size_units' 	=> [ 'px', 'em', '%' ],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-post__media__content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
				]
			);

			// $this->add_control(
			// 	'media_thumbnail_style_heading',
			// 	[
			// 		'label' 	=> __( 'Thumbnail', 'elementor-extras' ),
			// 		'type' 		=> Controls_Manager::HEADING,
			// 		'separator' => 'before',
			// 	]
			// );

			// $this->add_control(
			// 	'media_thumbnail_effect',
			// 	[
			// 		'separator'	=> 'after',
			// 		'label' 	=> __( 'Effect', 'elementor-extras' ),
			// 		'type' 		=> Controls_Manager::SELECT,
			// 		'default' 	=> '',
			// 		'options' => [
			// 			''					=> __( 'None', 'elementor-extras' ),
			// 			'rotate-to-left'	=> __( 'Rotate To Left', 'elementor-extras' ),
			// 			'rotate-to-right'	=> __( 'Rotate To Right', 'elementor-extras' ),
			// 			'rotate-from-left'	=> __( 'Rotate From Left', 'elementor-extras' ),
			// 			'rotate-from-right'	=> __( 'Rotate From Right', 'elementor-extras' ),
			// 		],
			// 		'prefix_class'	=> 'ee-posts-effect__thumbnail--',
			// 	]
			// );

			$media_metas_conditions = $this->get_area_metas_controls_conditions( 'media' );

			$this->add_control(
				'media_metas',
				[
					'separator' 	=> 'before',
					'label' 		=> __( '↳ Media Metas', 'elementor-extras' ),
					'type' 			=> Controls_Manager::HEADING,
					'conditions'	=> $media_metas_conditions,
				]
			);

			$this->add_control(
				'media_metas_description',
				[
					'type' 				=> Controls_Manager::RAW_HTML,
					'raw' 				=> __( 'Use these to style metas that appear only in the Media area', 'elementor-extras' ),
					'content_classes' 	=> 'elementor-panel-alert elementor-panel-alert-warning',
					'conditions'		=> $media_metas_conditions,
				]
			);

			$this->add_control(
				'media_metas_spacing',
				[
					'label' 		=> __( 'Spacing', 'elementor-extras' ),
					'type' 			=> Controls_Manager::SLIDER,
					'range' 		=> [
						'px' 		=> [
							'min' => 0,
							'max' => 48,
						],
					],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-post__media .ee-post__metas__list' => 'margin-left: -{{SIZE}}px',
						'{{WRAPPER}} .ee-post__media .ee-post__meta,
						 {{WRAPPER}} .ee-post__media .ee-post__meta__separator' => 'margin-left: {{SIZE}}px',
					],
					'conditions'	=> $media_metas_conditions,
				]
			);

			$this->add_control(
				'media_metas_distance',
				[
					'label' 		=> __( 'Distance', 'elementor-extras' ),
					'type' 			=> Controls_Manager::SLIDER,
					'range' 		=> [
						'px' 		=> [
							'min' => 0,
							'max' => 48,
						],
					],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-post__media .ee-post__metas' => 'margin-bottom: {{SIZE}}px',
					],
					'conditions'	=> $media_metas_conditions,
				]
			);

			$this->add_responsive_control(
				'media_metas_text_align',
				[
					'label' 		=> __( 'Align Text', 'elementor-extras' ),
					'type' 			=> Controls_Manager::CHOOSE,
					'default' 		=> '',
					'options' 		=> [
						'left'    		=> [
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
					'selectors'		=> [
						'{{WRAPPER}} .ee-post__media .ee-post__metas--has-metas' => 'text-align: {{VALUE}};',
					],
					'conditions'	=> $media_metas_conditions,
				]
			);

			$this->add_responsive_control(
				'media_metas_padding',
				[
					'label' 		=> __( 'Padding', 'elementor-extras' ),
					'type' 			=> Controls_Manager::DIMENSIONS,
					'size_units' 	=> [ 'px', 'em', '%' ],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-post__media .ee-post__metas--has-metas' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
					'conditions'	=> $media_metas_conditions,
				]
			);

			$this->add_control(
				'media_metas_color',
				[
					'label' 		=> __( 'Color', 'elementor-extras' ),
					'type' 			=> Controls_Manager::COLOR,
					'selectors' 	=> [
						'{{WRAPPER}} .ee-post__media .ee-post__metas--has-metas .ee-post__meta' => 'color: {{VALUE}};',
					],
					'conditions'	=> $media_metas_conditions,
				]
			);

			$this->add_control(
				'media_metas_background_color',
				[
					'label' 		=> __( 'Background Color', 'elementor-extras' ),
					'type' 			=> Controls_Manager::COLOR,
					'selectors' 	=> [
						'{{WRAPPER}} .ee-post__media .ee-post__metas--has-metas' => 'background-color: {{VALUE}};',
					],
					'conditions'	=> $media_metas_conditions,
				]
			);

			$this->add_group_control(
				Group_Control_Typography::get_type(),
				[
					'name' 			=> 'media_metas_typography',
					'label' 		=> __( 'Typography', 'elementor-extras' ),
					'scheme' 		=> Scheme_Typography::TYPOGRAPHY_3,
					'selector' 		=> '{{WRAPPER}} .ee-post__media .ee-post__metas--has-metas .ee-post__meta',
					'conditions'	=> $media_metas_conditions,
				]
			);

		$this->end_controls_section();

	}

	public function register_body_style_controls() {

		$this->start_controls_section(
			'section_style_body',
			[
				'label' => __( 'Body', 'elementor-extras' ),
				'tab'   => Controls_Manager::TAB_STYLE,
				'conditions' => [
					'relation' => 'and',
					'terms' => [
						$this->get_empty_area_condition( 'body' ),
						[
							'name' 		=> 'skin_source',
							'operator' 	=> '==',
							'value' 	=> '',
						],
					],
				],
			]
		);

			$this->add_responsive_control(
				'body_text_align',
				[
					'label' 		=> __( 'Align Text', 'elementor-extras' ),
					'type' 			=> Controls_Manager::CHOOSE,
					'default' 		=> '',
					'options' 		=> [
						'left'    		=> [
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
					'selectors'		=> [
						'{{WRAPPER}} .ee-post__body' 	=> 'text-align: {{VALUE}};',
					],
					'conditions' => $this->get_empty_area_condition( 'body' ),
				]
			);

			$this->add_responsive_control(
				'body_padding',
				[
					'label' 		=> __( 'Padding', 'elementor-extras' ),
					'type' 			=> Controls_Manager::DIMENSIONS,
					'size_units' 	=> [ 'px', 'em', '%' ],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-post__body' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
					'conditions' => $this->get_empty_area_condition( 'body' ),
				]
			);

			$this->add_responsive_control(
				'body_margin',
				[
					'label' 		=> __( 'Margin', 'elementor-extras' ),
					'type' 			=> Controls_Manager::DIMENSIONS,
					'size_units' 	=> [ 'px', 'em', '%' ],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-post__body' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
					'conditions' => $this->get_empty_area_condition( 'body' ),
				]
			);

			$this->add_control(
				'body_border_radius',
				[
					'type' 			=> Controls_Manager::DIMENSIONS,
					'label' 		=> __( 'Border Radius', 'elementor-extras' ),
					'size_units' 	=> [ 'px', '%' ],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-post__body' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
					'conditions' => $this->get_empty_area_condition( 'body' ),
				]
			);

			$this->start_controls_tabs( 'body_tabs_hover' );

			$this->start_controls_tab( 'body_tab_default', [ 'label' => __( 'Default', 'elementor-extras' ) ] );

				$this->add_control(
					'body_background_color',
					[
						'label' 	=> __( 'Background Color', 'elementor-extras' ),
						'type' 		=> Controls_Manager::COLOR,
						'selectors' => [
							'{{WRAPPER}} .ee-post__body' => 'background-color: {{VALUE}};',
						],
						'conditions' => $this->get_empty_area_condition( 'body' ),
					]
				);

				$this->add_control(
					'body_color',
					[
						'label' 	=> __( 'Color', 'elementor-extras' ),
						'type' 		=> Controls_Manager::COLOR,
						'selectors' => [
							'{{WRAPPER}} .ee-post__body' => 'color: {{VALUE}};',
						],
						'conditions' => $this->get_empty_area_condition( 'body' ),
					]
				);

			$this->end_controls_tab();

			$this->start_controls_tab( 'body_tab_hover', [ 'label' => __( 'Hover', 'elementor-extras' ) ] );

				$this->add_control(
					'body_background_color_hover',
					[
						'label' 	=> __( 'Background Color', 'elementor-extras' ),
						'type' 		=> Controls_Manager::COLOR,
						'selectors' => [
							'{{WRAPPER}} .ee-post:hover .ee-post__body' => 'background-color: {{VALUE}};',
						],
						'conditions' => $this->get_empty_area_condition( 'body' ),
					]
				);

				$this->add_control(
					'body_color_hover',
					[
						'label' 	=> __( 'Color', 'elementor-extras' ),
						'type' 		=> Controls_Manager::COLOR,
						'selectors' => [
							'{{WRAPPER}} .ee-post:hover .ee-post__body' => 'color: {{VALUE}};',
						],
						'conditions' => $this->get_empty_area_condition( 'body' ),
					]
				);

			$this->end_controls_tab();

			$this->end_controls_tabs();

			$body_metas_condition = $this->get_area_metas_controls_conditions( 'body' );

			$this->add_control(
				'body_metas',
				[
					'separator' 	=> 'before',
					'label' 		=> __( '↳ Body Metas', 'elementor-extras' ),
					'type' 			=> Controls_Manager::HEADING,
					'conditions'	=> $body_metas_condition,
				]
			);

			$this->add_control(
				'body_metas_description',
				[
					'type' 				=> Controls_Manager::RAW_HTML,
					'raw' 				=> __( 'Use these to style metas that appear only in the Body area', 'elementor-extras' ),
					'content_classes' 	=> 'elementor-panel-alert elementor-panel-alert-warning',
					'conditions'		=> $body_metas_condition,
				]
			);

			$this->add_control(
				'body_metas_spacing',
				[
					'label' 		=> __( 'Spacing', 'elementor-extras' ),
					'type' 			=> Controls_Manager::SLIDER,
					'range' 		=> [
						'px' 		=> [
							'min' => 0,
							'max' => 48,
						],
					],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-post__body .ee-post__metas__list' => 'margin-left: -{{SIZE}}px',
						'{{WRAPPER}} .ee-post__body .ee-post__meta,
						 {{WRAPPER}} .ee-post__body .ee-post__meta__separator' => 'margin-left: {{SIZE}}px',
					],
					'conditions'	=> $body_metas_condition,
				]
			);

			$this->add_control(
				'body_metas_distance',
				[
					'label' 		=> __( 'Distance', 'elementor-extras' ),
					'type' 			=> Controls_Manager::SLIDER,
					'range' 		=> [
						'px' 		=> [
							'min' => 0,
							'max' => 48,
						],
					],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-post__body .ee-post__metas' => 'margin-bottom: {{SIZE}}px',
					],
					'conditions'	=> $body_metas_condition,
				]
			);

			$this->add_responsive_control(
				'body_metas_text_align',
				[
					'label' 		=> __( 'Align Text', 'elementor-extras' ),
					'type' 			=> Controls_Manager::CHOOSE,
					'default' 		=> '',
					'options' 		=> [
						'left'    		=> [
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
					'selectors'		=> [
						'{{WRAPPER}} .ee-post__body .ee-post__metas--has-metas' => 'text-align: {{VALUE}};',
					],
					'conditions'	=> $body_metas_condition,
				]
			);

			$this->add_responsive_control(
				'body_metas_padding',
				[
					'label' 		=> __( 'Padding', 'elementor-extras' ),
					'type' 			=> Controls_Manager::DIMENSIONS,
					'size_units' 	=> [ 'px', 'em', '%' ],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-post__body .ee-post__metas--has-metas' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
					'conditions'	=> $body_metas_condition,
				]
			);

			$this->add_control(
				'body_metas_color',
				[
					'label' 		=> __( 'Color', 'elementor-extras' ),
					'type' 			=> Controls_Manager::COLOR,
					'selectors' 	=> [
						'{{WRAPPER}} .ee-post__body .ee-post__metas--has-metas' => 'color: {{VALUE}};',
					],
					'conditions'	=> $body_metas_condition,
				]
			);

			$this->add_control(
				'body_metas_background_color',
				[
					'label' 		=> __( 'Background Color', 'elementor-extras' ),
					'type' 			=> Controls_Manager::COLOR,
					'selectors' 	=> [
						'{{WRAPPER}} .ee-post__body .ee-post__metas--has-metas' => 'background-color: {{VALUE}};',
					],
					'conditions'	=> $body_metas_condition,
				]
			);

			$this->add_group_control(
				Group_Control_Typography::get_type(),
				[
					'name' 			=> 'body_metas_typography',
					'label' 		=> __( 'Typography', 'elementor-extras' ),
					'scheme' 		=> Scheme_Typography::TYPOGRAPHY_3,
					'selector' 		=> '{{WRAPPER}} .ee-post__body .ee-post__metas--has-metas .ee-post__meta',
					'conditions'	=> $body_metas_condition,
				]
			);

		$this->end_controls_section();

	}

	/**
	 * Register Footer Style Controls
	 *
	 * @since  1.6.0
	 * @return void
	 */
	public function register_footer_style_controls() {

		$this->start_controls_section(
			'section_style_footer',
			[
				'label' => __( 'Footer', 'elementor-extras' ),
				'tab'   => Controls_Manager::TAB_STYLE,
				'conditions' => [
					'relation' => 'and',
					'terms' => [
						$this->get_empty_area_condition( 'footer' ),
						[
							'name' 		=> 'skin_source',
							'operator' 	=> '==',
							'value' 	=> '',
						],
					],
				],
			]
		);

			$this->add_responsive_control(
				'footer_text_align',
				[
					'label' 		=> __( 'Align Text', 'elementor-extras' ),
					'type' 			=> Controls_Manager::CHOOSE,
					'default' 		=> '',
					'options' 		=> [
						'left'    		=> [
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
					'selectors'		=> [
						'{{WRAPPER}} .ee-post__footer' 	=> 'text-align: {{VALUE}};',
					],
					'conditions' => $this->get_empty_area_condition( 'footer' ),
				]
			);

			$this->add_responsive_control(
				'footer_padding',
				[
					'label' 		=> __( 'Padding', 'elementor-extras' ),
					'type' 			=> Controls_Manager::DIMENSIONS,
					'size_units' 	=> [ 'px', 'em', '%' ],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-post__footer' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
					'conditions' => $this->get_empty_area_condition( 'footer' ),
				]
			);

			$this->add_responsive_control(
				'footer_margin',
				[
					'label' 		=> __( 'Margin', 'elementor-extras' ),
					'type' 			=> Controls_Manager::DIMENSIONS,
					'size_units' 	=> [ 'px', 'em', '%' ],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-post__footer' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
					'conditions' => $this->get_empty_area_condition( 'footer' ),
				]
			);

			$this->add_control(
				'footer_border_radius',
				[
					'type' 			=> Controls_Manager::DIMENSIONS,
					'label' 		=> __( 'Border Radius', 'elementor-extras' ),
					'size_units' 	=> [ 'px', '%' ],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-post__footer' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
					'conditions' => $this->get_empty_area_condition( 'footer' ),
				]
			);

			$this->start_controls_tabs( 'footer_tabs_hover' );

			$this->start_controls_tab( 'footer_tab_default', [ 'label' => __( 'Default', 'elementor-extras' ) ] );

				$this->add_control(
					'footer_background_color',
					[
						'label' 	=> __( 'Background Color', 'elementor-extras' ),
						'type' 		=> Controls_Manager::COLOR,
						'selectors' => [
							'{{WRAPPER}} .ee-post__footer' => 'background-color: {{VALUE}};',
						],
						'conditions' => $this->get_empty_area_condition( 'footer' ),
					]
				);

				$this->add_control(
					'footer_color',
					[
						'label' 	=> __( 'Color', 'elementor-extras' ),
						'type' 		=> Controls_Manager::COLOR,
						'selectors' => [
							'{{WRAPPER}} .ee-post__footer' => 'color: {{VALUE}};',
						],
						'conditions' => $this->get_empty_area_condition( 'footer' ),
					]
				);

				$this->add_control(
					'footer_separator_heading',
					[
						'label' 	=> __( 'Separator', 'elementor-extras' ),
						'type' 		=> Controls_Manager::HEADING,
						'conditions' => $this->get_empty_area_condition( 'footer' ),
					]
				);

				$this->add_control(
					'footer_separator_color',
					[
						'label' 	=> __( 'Color', 'elementor-extras' ),
						'type' 		=> Controls_Manager::COLOR,
						'selectors' => [
							'{{WRAPPER}} .ee-post__footer' => 'border-color: {{VALUE}};',
						],
						'conditions' => $this->get_empty_area_condition( 'footer' ),
					]
				);

			$this->end_controls_tab();

			$this->start_controls_tab( 'footer_tab_hover', [ 'label' => __( 'Hover', 'elementor-extras' ) ] );

				$this->add_control(
					'footer_background_color_hover',
					[
						'label' 	=> __( 'Background Color', 'elementor-extras' ),
						'type' 		=> Controls_Manager::COLOR,
						'selectors' => [
							'{{WRAPPER}} .ee-post:hover .ee-post__footer' => 'background-color: {{VALUE}};',
						],
						'conditions' => $this->get_empty_area_condition( 'footer' ),
					]
				);

				$this->add_control(
					'footer_color_hover',
					[
						'label' 	=> __( 'Color', 'elementor-extras' ),
						'type' 		=> Controls_Manager::COLOR,
						'selectors' => [
							'{{WRAPPER}}  .ee-post:hover .ee-post__footer' => 'color: {{VALUE}};',
						],
						'conditions' => $this->get_empty_area_condition( 'footer' ),
					]
				);

				$this->add_control(
					'footer_separator_heading_hover',
					[
						'label' 	=> __( 'Separator', 'elementor-extras' ),
						'type' 		=> Controls_Manager::HEADING,
						'conditions' => $this->get_empty_area_condition( 'footer' ),
					]
				);

				$this->add_control(
					'footer_separator_color_hover',
					[
						'label' 	=> __( 'Color', 'elementor-extras' ),
						'type' 		=> Controls_Manager::COLOR,
						'selectors' => [
							'{{WRAPPER}} .ee-post:hover .ee-post__footer' => 'border-color: {{VALUE}};',
						],
						'conditions' => $this->get_empty_area_condition( 'footer' ),
					]
				);

			$this->end_controls_tab();

			$this->end_controls_tabs();

			$this->add_responsive_control(
				'footer_separator_size',
				[
					'separator'		=> 'before',
					'label' 		=> __( 'Separator Size', 'elementor-extras' ),
					'type' 			=> Controls_Manager::SLIDER,
					'range' 		=> [
						'px' 		=> [
							'min' => 0,
							'max' => 10,
						],
					],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-post__footer' => 'border-width: {{SIZE}}px',
					],
					'conditions' => $this->get_empty_area_condition( 'footer' ),
				]
			);

			$footer_metas_condition = $this->get_area_metas_controls_conditions( 'footer' );

			$this->add_control(
				'footer_metas',
				[
					'separator' 	=> 'before',
					'label' 		=> __( '↳ Footer Metas', 'elementor-extras' ),
					'type' 			=> Controls_Manager::HEADING,
					'conditions'	=> $footer_metas_condition,
				]
			);

			$this->add_control(
				'footer_metas_description',
				[
					'type' 				=> Controls_Manager::RAW_HTML,
					'raw' 				=> __( 'Use these to style metas that appear only in the Footer area', 'elementor-extras' ),
					'content_classes' 	=> 'elementor-panel-alert elementor-panel-alert-warning',
					'conditions'		=> $footer_metas_condition,
				]
			);

			$this->add_control(
				'footer_metas_spacing',
				[
					'label' 		=> __( 'Spacing', 'elementor-extras' ),
					'type' 			=> Controls_Manager::SLIDER,
					'range' 		=> [
						'px' 		=> [
							'min' => 0,
							'max' => 48,
						],
					],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-post__footer .ee-post__metas__list' => 'margin-left: -{{SIZE}}px',
						'{{WRAPPER}} .ee-post__footer .ee-post__meta,
						 {{WRAPPER}} .ee-post__footer .ee-post__meta__separator' => 'margin-left: {{SIZE}}px',
					],
					'conditions'	=> $footer_metas_condition,
				]
			);

			$this->add_control(
				'footer_metas_distance',
				[
					'label' 		=> __( 'Distance', 'elementor-extras' ),
					'type' 			=> Controls_Manager::SLIDER,
					'range' 		=> [
						'px' 		=> [
							'min' => 0,
							'max' => 48,
						],
					],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-post__footer .ee-post__metas' => 'margin-bottom: {{SIZE}}px',
					],
					'conditions'	=> $footer_metas_condition,
				]
			);

			$this->add_responsive_control(
				'footer_metas_text_align',
				[
					'label' 		=> __( 'Align Text', 'elementor-extras' ),
					'type' 			=> Controls_Manager::CHOOSE,
					'default' 		=> '',
					'options' 		=> [
						'left'    		=> [
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
					'selectors'		=> [
						'{{WRAPPER}} .ee-post__footer .ee-post__metas--has-metas' => 'text-align: {{VALUE}};',
					],
					'conditions'	=> $footer_metas_condition,
				]
			);

			$this->add_responsive_control(
				'footer_metas_padding',
				[
					'label' 		=> __( 'Padding', 'elementor-extras' ),
					'type' 			=> Controls_Manager::DIMENSIONS,
					'size_units' 	=> [ 'px', 'em', '%' ],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-post__footer .ee-post__metas--has-metas' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
					'conditions'	=> $footer_metas_condition,
				]
			);

			$this->add_control(
				'footer_metas_color',
				[
					'label' 		=> __( 'Color', 'elementor-extras' ),
					'type' 			=> Controls_Manager::COLOR,
					'selectors' 	=> [
						'{{WRAPPER}} .ee-post__footer .ee-post__metas--has-metas' => 'color: {{VALUE}};',
					],
					'conditions'	=> $footer_metas_condition,
				]
			);

			$this->add_control(
				'footer_metas_background_color',
				[
					'label' 		=> __( 'Background Color', 'elementor-extras' ),
					'type' 			=> Controls_Manager::COLOR,
					'selectors' 	=> [
						'{{WRAPPER}} .ee-post__footer .ee-post__metas--has-metas' => 'background-color: {{VALUE}};',
					],
					'conditions'	=> $footer_metas_condition,
				]
			);

			$this->add_group_control(
				Group_Control_Typography::get_type(),
				[
					'name' 			=> 'footer_metas_typography',
					'label' 		=> __( 'Typography', 'elementor-extras' ),
					'scheme' 		=> Scheme_Typography::TYPOGRAPHY_3,
					'selector' 		=> '{{WRAPPER}} .ee-post__footer .ee-post__metas--has-metas .ee-post__meta',
					'conditions'	=> $footer_metas_condition,
				]
			);

		$this->end_controls_section();

	}

	/**
	 * Register Metas Style Controls
	 *
	 * @since  1.6.0
	 * @return void
	 */
	public function register_metas_style_controls() {

		$this->start_controls_section(
			'section_style_metas',
			[
				'label' 		=> __( 'Metas', 'elementor-extras' ),
				'tab'   		=> Controls_Manager::TAB_STYLE,
				'conditions' => [
					'relation' => 'and',
					'terms' => [
						[
							'relation'	=> 'or',
							'terms'		=> [
								[
									'name'		=> 'post_avatar_position',
									'operator' 	=> '!=',
									'value'		=> '',
								],
								[
									'name'		=> 'post_author_position',
									'operator' 	=> '!=',
									'value'		=> '',
								],
								[
									'name'		=> 'post_date_position',
									'operator' 	=> '!=',
									'value'		=> '',
								],
								[
									'name'		=> 'post_comments_position',
									'operator' 	=> '!=',
									'value'		=> '',
								]
							]
						],
						[
							'name' 		=> 'skin_source',
							'operator' 	=> '==',
							'value' 	=> '',
						],
					]
				],
			]
		);

			$this->add_control(
				'metas_description',
				[
					'type' 				=> Controls_Manager::RAW_HTML,
					'raw' 				=> __( 'The effects of the controls below can be overriden at an area level by using the options inside each separate area.', 'elementor-extras' ),
					'content_classes' 	=> 'elementor-panel-alert elementor-panel-alert-info',
					'conditions'	=> [
						'relation'	=> 'or',
						'terms'		=> [
							[
								'name'		=> 'post_author_position',
								'operator' 	=> '!=',
								'value'		=> '',
							],
							[
								'name'		=> 'post_date_position',
								'operator' 	=> '!=',
								'value'		=> '',
							],
							[
								'name'		=> 'post_comments_position',
								'operator' 	=> '!=',
								'value'		=> '',
							]
						]
					]
				]
			);

			$this->add_control(
				'metas_spacing',
				[
					'label' 		=> __( 'Spacing', 'elementor-extras' ),
					'type' 			=> Controls_Manager::SLIDER,
					'range' 		=> [
						'px' 		=> [
							'min' => 0,
							'max' => 48,
						],
					],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-post__metas__list' => 'margin-left: -{{SIZE}}px',
						'{{WRAPPER}} .ee-post__meta,
						 {{WRAPPER}} .ee-post__meta__separator' => 'margin-left: {{SIZE}}px',
					],
					'conditions'	=> [
						'relation'	=> 'or',
						'terms'		=> [
							[
								'name'		=> 'post_author_position',
								'operator' 	=> '!=',
								'value'		=> '',
							],
							[
								'name'		=> 'post_date_position',
								'operator' 	=> '!=',
								'value'		=> '',
							],
							[
								'name'		=> 'post_comments_position',
								'operator' 	=> '!=',
								'value'		=> '',
							]
						]
					]
				]
			);

			$this->add_control(
				'author_avatar_heading',
				[
					'separator' 	=> 'before',
					'label' 		=> __( 'Avatar', 'elementor-extras' ),
					'type' 			=> Controls_Manager::HEADING,
					'condition' 	=> [
						'post_avatar_position!' => '',
					]
				]
			);

			$this->add_control(
				'author_avatar_display',
				[
					'label' 		=> __( 'Display', 'elementor-extras' ),
					'type' 			=> Controls_Manager::CHOOSE,
					'default' 		=> 'left',
					'options' 		=> [
						'left' 		=> [
							'title' => __( 'Left', 'elementor-extras' ),
							'icon' 	=> 'eicon-h-align-left',
						],
						'top' 	=> [
							'title' => __( 'Top', 'elementor-extras' ),
							'icon' 	=> 'eicon-v-align-top',
						],
						'right' 	=> [
							'title' => __( 'Right', 'elementor-extras' ),
							'icon' 	=> 'eicon-h-align-right',
						],
					],
					'condition' 	=> [
						'post_avatar_position!' => '',
					],
					'prefix_class'	=> 'ee-posts-avatar-position-',
					'label_block'	=> false,
				]
			);

			$this->add_control(
				'author_avatar_vertical_align',
				[
					'label' 		=> __( 'Align', 'elementor-extras' ),
					'type' 			=> Controls_Manager::CHOOSE,
					'default' 		=> 'center',
					'options' 		=> [
						'flex-start'=> [
							'title' => __( 'Top', 'elementor-extras' ),
							'icon' 	=> 'eicon-v-align-top',
						],
						'center' 	=> [
							'title' => __( 'Center', 'elementor-extras' ),
							'icon' 	=> 'eicon-v-align-middle',
						],
						'flex-end' 	=> [
							'title' => __( 'Bottom', 'elementor-extras' ),
							'icon' 	=> 'eicon-v-align-bottom',
						],
					],
					'selectors'		=> [
						'{{WRAPPER}} .ee-post__metas--has-metas.ee-post__metas--has-avatar' => 'align-items: {{VALUE}};',
					],
					'label_block'	=> false,
					'condition' => [
						'post_avatar_position!' 	=> '',
						'author_avatar_display!' 	=> 'top',
					]
				]
			);

			$this->add_responsive_control(
				'author_avatar_size',
				[
					'label' 		=> __( 'Size', 'elementor-extras' ),
					'type' 			=> Controls_Manager::SLIDER,
					'range' 		=> [
						'px' 		=> [
							'min' => 12,
							'max' => 100,
						],
					],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-post__metas__avatar' => 'max-width: {{SIZE}}px !important;',
					],
					'condition' 	=> [
						'post_avatar_position!' => '',
					],
				]
			);

			$this->add_control(
				'author_avatar_spacing',
				[
					'label' 		=> __( 'Spacing', 'elementor-extras' ),
					'type' 			=> Controls_Manager::SLIDER,
					'range' 		=> [
						'px' 		=> [
							'min' => 0,
							'max' => 48,
						],
					],
					'selectors' 	=> [
						'{{WRAPPER}}.ee-posts-avatar-position-top .ee-post__metas--has-metas .ee-post__metas__avatar' => 'margin: 0 0 {{SIZE}}px 0',
						'{{WRAPPER}}.ee-posts-avatar-position-right .ee-post__metas--has-metas .ee-post__metas__avatar' => 'margin: 0 0 0 {{SIZE}}px',
						'{{WRAPPER}} .ee-post__metas--has-metas .ee-post__metas__avatar' => 'margin: 0 {{SIZE}}px 0 0',
					],
					'condition' 	=> [
						'post_avatar_position!' => '',
					],
				]
			);

			$this->add_control(
				'author_avatar_border_radius',
				[
					'label' 		=> __( 'Border Radius', 'elementor-extras' ),
					'type' 			=> Controls_Manager::SLIDER,
					'range' 		=> [
						'%' 		=> [
							'min' => 0,
							'max' => 100,
							'step'=> 1,
						],
						'px' 		=> [
							'min' => 0,
							'max' => 100,
							'step'=> 1,
						],
					],
					'size_units' 	=> [ '%', 'px' ],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-post__metas__avatar' => 'border-radius: {{SIZE}}{{UNIT}}',
					],
					'condition' 	=> [
						'post_avatar_position!' => '',
					],
				]
			);

			$this->add_group_control(
				Group_Control_Box_Shadow::get_type(),
				[
					'name' 			=> 'author_avatar_box_shadow',
					'selector' 		=> '{{WRAPPER}} .ee-post__metas__avatar',
					'separator'		=> '',
					'condition' 	=> [
						'post_avatar_position!' => '',
					],
				]
			);

			$this->add_control(
				'author_name_heading',
				[
					'separator' 	=> 'before',
					'label' 		=> __( 'Author', 'elementor-extras' ),
					'type' 			=> Controls_Manager::HEADING,
					'condition' 	=> [
						'post_author_position!' => '',
					],
				]
			);

			$this->add_control(
				'author_name_color',
				[
					'label' 		=> __( 'Color', 'elementor-extras' ),
					'type' 			=> Controls_Manager::COLOR,
					'selectors' 	=> [
						'{{WRAPPER}} .ee-post__meta--author' => 'color: {{VALUE}};',
					],
					'condition' 	=> [
						'post_author_position!' => '',
					],
				]
			);

			$this->add_group_control(
				Group_Control_Typography::get_type(),
				[
					'name' 			=> 'author_name_typography',
					'label' 		=> __( 'Typography', 'elementor-extras' ),
					'scheme' 		=> Scheme_Typography::TYPOGRAPHY_3,
					'selector' 		=> '{{WRAPPER}} .ee-post__meta--author',
					'exclude'		=> [
						'font_family',
						'font_size',
						'line_height',
						'letter_spacing',
					],
					'condition' 	=> [
						'post_author_position!' => '',
					],
				]
			);

			$this->add_control(
				'date_heading',
				[
					'separator' => 'before',
					'label' 	=> __( 'Date', 'elementor-extras' ),
					'type' 		=> Controls_Manager::HEADING,
					'condition' 	=> [
						'post_date_position!' => '',
					],
				]
			);

			$this->add_control(
				'date_color',
				[
					'label' 	=> __( 'Color', 'elementor-extras' ),
					'type' 		=> Controls_Manager::COLOR,
					'selectors' => [
						'{{WRAPPER}} .ee-post__meta--date' => 'color: {{VALUE}};',
					],
					'condition' => [
						'post_date_position!' => '',
					],
				]
			);

			$this->add_group_control(
				Group_Control_Typography::get_type(),
				[
					'name' 		=> 'date_typography',
					'label' 	=> __( 'Typography', 'elementor-extras' ),
					'scheme' 	=> Scheme_Typography::TYPOGRAPHY_3,
					'selector' 	=> '{{WRAPPER}} .ee-post__meta--date',
					'exclude'	=> [
						'font_family',
						'font_size',
						'line_height',
						'letter_spacing',
					],
					'condition' => [
						'post_date_position!' => '',
					],
				]
			);

			if ( is_woocommerce_active() ) {
				$this->add_control(
					'price_heading',
					[
						'separator' => 'before',
						'label' 	=> __( 'Price', 'elementor-extras' ),
						'type' 		=> Controls_Manager::HEADING,
						'condition' 	=> [
							'post_price_position!' => '',
							'posts_post_type' => ['product', 'current_query'],
						],
					]
				);

				$this->add_control(
					'price_color',
					[
						'label' 	=> __( 'Color', 'elementor-extras' ),
						'type' 		=> Controls_Manager::COLOR,
						'selectors' => [
							'{{WRAPPER}} .ee-post__meta--price' => 'color: {{VALUE}};',
						],
						'condition' => [
							'post_price_position!' => '',
							'posts_post_type' => ['product', 'current_query'],
						],
					]
				);

				$this->add_group_control(
					Group_Control_Typography::get_type(),
					[
						'name' 		=> 'price_typography',
						'label' 	=> __( 'Typography', 'elementor-extras' ),
						'scheme' 	=> Scheme_Typography::TYPOGRAPHY_3,
						'selector' 	=> '{{WRAPPER}} .ee-post__meta--price',
						'exclude'	=> [
							'font_family',
							'font_size',
							'line_height',
							'letter_spacing',
						],
						'condition' => [
							'post_price_position!' => '',
							'posts_post_type' => ['product', 'current_query'],
						],
					]
				);
			}

			$this->add_control(
				'comments_heading',
				[
					'separator' => 'before',
					'label' 	=> __( 'Comments', 'elementor-extras' ),
					'type' 		=> Controls_Manager::HEADING,
					'condition' => [
						'post_comments_position!' => '',
					],
				]
			);

			$this->add_control(
				'comments_color',
				[
					'label' 	=> __( 'Color', 'elementor-extras' ),
					'type' 		=> Controls_Manager::COLOR,
					'selectors' => [
						'{{WRAPPER}} .ee-post__meta--comments' => 'color: {{VALUE}};',
					],
					'condition' => [
						'post_comments_position!' => '',
					],
				]
			);

			$this->add_group_control(
				Group_Control_Typography::get_type(),
				[
					'name' 		=> 'comments_typography',
					'label' 	=> __( 'Typography', 'elementor-extras' ),
					'scheme' 	=> Scheme_Typography::TYPOGRAPHY_3,
					'selector' 	=> '{{WRAPPER}} .ee-post__meta--comments',
					'exclude'	=> [
						'font_family',
						'font_size',
						'line_height',
						'letter_spacing',
					],
					'condition' => [
						'post_comments_position!' => '',
					],
				]
			);

		$this->end_controls_section();

	}

	/**
	 * Register Terms Style Controls
	 *
	 * @since  1.6.0
	 * @return void
	 */
	public function register_terms_style_controls() {

		$this->start_controls_section(
			'section_style_terms',
			[
				'label' => __( 'Terms', 'elementor-extras' ),
				'tab'   => Controls_Manager::TAB_STYLE,
				'condition' => [
					'post_terms_position!' => '',
				]
			]
		);

			$this->add_control(
				'terms_terms_heading',
				[
					'separator' => 'before',
					'label' 	=> __( 'Terms', 'elementor-extras' ),
					'type' 		=> Controls_Manager::HEADING,
					'condition' => [
						'post_terms_position!' => '',
					]
				]
			);

			$this->add_responsive_control(
				'terms_terms_align',
				[
					'label' 		=> __( 'Align Text', 'elementor-extras' ),
					'type' 			=> Controls_Manager::CHOOSE,
					'default' 		=> '',
					'options' 		=> [
						'left'    		=> [
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
					'selectors'		=> [
						'{{WRAPPER}} .ee-post__terms' 	=> 'text-align: {{VALUE}};',
					],
					'condition' => [
						'post_terms_position!' => '',
					]
				]
			);

			$this->add_responsive_control(
				'terms_distance',
				[
					'label' 		=> __( 'Distance', 'elementor-extras' ),
					'type' 			=> Controls_Manager::SLIDER,
					'range' 		=> [
						'px' 		=> [
							'min' => 0,
							'max' => 48,
						],
					],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-post__terms' => 'margin-bottom: {{SIZE}}px',
					],
					'condition' => [
						'post_terms_position!' => '',
					]
				]
			);

			$this->add_group_control(
				Group_Control_Typography::get_type(),
				[
					'name' 		=> 'terms_typography',
					'label' 	=> __( 'Typography', 'elementor-extras' ),
					'scheme' 	=> Scheme_Typography::TYPOGRAPHY_3,
					'selector' 	=> '{{WRAPPER}} .ee-post__terms__term',
					'condition' => [
						'post_terms_position!' => '',
					]
				]
			);

			$this->add_control(
				'terms_term_heading',
				[
					'separator' => 'before',
					'label' 	=> __( 'Term', 'elementor-extras' ),
					'type' 		=> Controls_Manager::HEADING,
					'condition' => [
						'post_terms_position!' => '',
					],
				]
			);

			$this->add_responsive_control(
				'terms_spacing',
				[
					'label' 		=> __( 'Horzontal Spacing', 'elementor-extras' ),
					'type' 			=> Controls_Manager::SLIDER,
					'range' 		=> [
						'px' 		=> [
							'min' => 0,
							'max' => 48,
						],
					],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-post__terms' => 'margin-left: -{{SIZE}}px',
						'{{WRAPPER}} .ee-post__terms__term' => 'margin-left: {{SIZE}}px',
						'{{WRAPPER}} .ee-post__terms__separator' => 'margin-left: {{SIZE}}px',
					],
					'condition' => [
						'post_terms_count!' 	=> '1',
						'post_terms_position!' 	=> '',
					],
				]
			);

			$this->add_responsive_control(
				'terms_vertical_spacing',
				[
					'label' 		=> __( 'Vertical Spacing', 'elementor-extras' ),
					'description'	=> __( 'If you have multuple lines of terms, this will help you distance them from one another', 'elementor-extras' ),
					'type' 			=> Controls_Manager::SLIDER,
					'range' 		=> [
						'px' 		=> [
							'min' => 0,
							'max' => 48,
						],
					],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-post__terms__term' => 'margin-bottom: {{SIZE}}px',
					],
					'condition' => [
						'post_terms_count!' 	=> '1',
						'post_terms_position!' 	=> '',
					],
				]
			);

			$this->add_responsive_control(
				'terms_padding',
				[
					'label' 		=> __( 'Padding', 'elementor-extras' ),
					'type' 			=> Controls_Manager::DIMENSIONS,
					'size_units' 	=> [ 'px', 'em', '%' ],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-post__terms__link' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
					'condition' => [
						'post_terms_position!' => '',
					],
				]
			);

			$this->add_control(
				'terms_border_radius',
				[
					'type' 			=> Controls_Manager::DIMENSIONS,
					'label' 		=> __( 'Border Radius', 'elementor-extras' ),
					'size_units' 	=> [ 'px', '%' ],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-post__terms__link' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
					'condition' => [
						'post_terms_position!' => '',
					],
				]
			);

			$this->start_controls_tabs( 'terms_tabs_hover' );

			$this->start_controls_tab( 'terms_tab_default', [
				'label' 	=> __( 'Default', 'elementor-extras' ),
				'condition' => [
					'post_terms_position!' => '',
				],
			] );

				$this->add_control(
					'terms_color',
					[
						'label' 	=> __( 'Color', 'elementor-extras' ),
						'type' 		=> Controls_Manager::COLOR,
						'selectors' => [
							'{{WRAPPER}} .ee-post__terms__link' => 'color: {{VALUE}};',
						],
						'condition' => [
							'post_terms_position!' => '',
						],
					]
				);

				$this->add_control(
					'terms_background_color',
					[
						'label' 	=> __( 'Background Color', 'elementor-extras' ),
						'type' 		=> Controls_Manager::COLOR,
						'selectors' => [
							'{{WRAPPER}} .ee-post__terms__link' => 'background-color: {{VALUE}};',
						],
						'condition' => [
							'post_terms_position!' => '',
						],
					]
				);

			$this->end_controls_tab();

			$this->start_controls_tab( 'terms_tab_hover', [
				'label' 		=> __( 'Hover', 'elementor-extras' ),
				'conditions'	=> [
					'relation'	=> 'and',
					'terms'		=> [
						[
							'name' 		=> 'post_terms_position',
							'operator'	=> '!=',
							'value'		=> '',
						],
						[
							'name' 		=> 'post_terms_position',
							'operator'	=> '!=',
							'value'		=> 'media',
						],
					]
				],
			] );

				$this->add_control(
					'terms_color_hover',
					[
						'label' 	=> __( 'Color', 'elementor-extras' ),
						'type' 		=> Controls_Manager::COLOR,
						'selectors' => [
							'{{WRAPPER}} .ee-post__terms__link:hover' => 'color: {{VALUE}};',
						],
						'conditions'	=> [
							'relation'	=> 'and',
							'terms'		=> [
								[
									'name' 		=> 'post_terms_position',
									'operator'	=> '!=',
									'value'		=> '',
								],
								[
									'name' 		=> 'post_terms_position',
									'operator'	=> '!=',
									'value'		=> 'media',
								],
							]
						],
					]
				);

				$this->add_control(
					'terms_background_color_hover',
					[
						'label' 	=> __( 'Background Color', 'elementor-extras' ),
						'type' 		=> Controls_Manager::COLOR,
						'selectors' => [
							'{{WRAPPER}} .ee-post__terms__link:hover' => 'background-color: {{VALUE}};',
						],
						'conditions'	=> [
							'relation'	=> 'and',
							'terms'		=> [
								[
									'name' 		=> 'post_terms_position',
									'operator'	=> '!=',
									'value'		=> '',
								],
								[
									'name' 		=> 'post_terms_position',
									'operator'	=> '!=',
									'value'		=> 'media',
								],
							]
						],
					]
				);

			$this->end_controls_tab();

			$this->end_controls_tabs();

			$this->add_control(
				'terms_separator_heading',
				[
					'separator' => 'before',
					'label' 	=> __( 'Separator', 'elementor-extras' ),
					'type' 		=> Controls_Manager::HEADING,
					'condition' => [
						'post_terms_position!' => '',
					],
				]
			);

			$this->add_control(
				'terms_separator_color',
				[
					'label' 	=> __( 'Color', 'elementor-extras' ),
					'type' 		=> Controls_Manager::COLOR,
					'selectors' => [
						'{{WRAPPER}} .ee-post__terms__separator' => 'color: {{VALUE}};',
					],
					'condition' => [
						'post_terms_position!' => '',
					],
				]
			);

		$this->end_controls_section();

	}

	/**
	 * Register Title Style Controls
	 *
	 * @since  1.6.0
	 * @return void
	 */
	public function register_title_style_controls() {

		$this->start_controls_section(
			'section_style_title',
			[
				'label' 	=> __( 'Title', 'elementor-extras' ),
				'tab'   	=> Controls_Manager::TAB_STYLE,
				'condition' => [
					'post_title_position!' => '',
					'skin_source' => '',
				]
			]
		);

			$this->add_responsive_control(
				'title_align',
				[
					'label' 		=> __( 'Align Text', 'elementor-extras' ),
					'type' 			=> Controls_Manager::CHOOSE,
					'default' 		=> '',
					'options' 		=> [
						'left'    		=> [
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
					'selectors'		=> [
						'{{WRAPPER}} .ee-post__title' 	=> 'text-align: {{VALUE}};',
					],
					'condition' => [
						'post_title_position!' => '',
					]
				]
			);

			$this->add_control(
				'title_color',
				[
					'label' 	=> __( 'Color', 'elementor-extras' ),
					'type' 		=> Controls_Manager::COLOR,
					'selectors' => [
						'{{WRAPPER}} .ee-post__title__heading' => 'color: {{VALUE}};',
					],
					'condition' => [
						'post_title_position!' => '',
					]
				]
			);

			$this->add_control(
				'title_background_color',
				[
					'label' 	=> __( 'Background Color', 'elementor-extras' ),
					'type' 		=> Controls_Manager::COLOR,
					'selectors' => [
						'{{WRAPPER}} .ee-post__title' => 'background-color: {{VALUE}};',
					],
				]
			);

			$this->add_responsive_control(
				'title_margin',
				[
					'label' 		=> __( 'Margin', 'elementor-extras' ),
					'type' 			=> Controls_Manager::DIMENSIONS,
					'size_units' 	=> [ 'px', 'em', '%' ],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-post__title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
					'condition' => [
						'post_title_position!' => '',
					]
				]
			);

			$this->add_responsive_control(
				'title_padding',
				[
					'label' 		=> __( 'Padding', 'elementor-extras' ),
					'type' 			=> Controls_Manager::DIMENSIONS,
					'size_units' 	=> [ 'px', 'em', '%' ],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-post__title' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
					'condition' => [
						'post_title_position!' => '',
					]
				]
			);

			$this->add_group_control(
				Group_Control_Typography::get_type(),
				[
					'name' 		=> 'title_typography',
					'label' 	=> __( 'Typography', 'elementor-extras' ),
					'scheme' 	=> Scheme_Typography::TYPOGRAPHY_3,
					'selector' 	=> '{{WRAPPER}} .ee-post__title__heading',
					'condition' => [
						'post_title_position!' => '',
					]
				]
			);

			$this->add_group_control(
				Group_Control_Text_Shadow::get_type(),
				[
					'name' 		=> 'title_shadow',
					'selector' 	=> '{{WRAPPER}} .ee-post__title__heading',
					'condition' => [
						'post_title_position!' => '',
					]
				]
			);

		$this->end_controls_section();

	}

	/**
	 * Register Excerpt Style Controls
	 *
	 * @since  1.6.0
	 * @return void
	 */
	public function register_excerpt_style_controls() {

		$this->start_controls_section(
			'section_style_excerpt',
			[
				'label' => __( 'Excerpt', 'elementor-extras' ),
				'tab'   => Controls_Manager::TAB_STYLE,
				'condition' => [
					'post_excerpt_position!' => '',
					'skin_source' => '',
				]
			]
		);

			$this->add_responsive_control(
				'excerpt_align',
				[
					'label' 		=> __( 'Align Text', 'elementor-extras' ),
					'type' 			=> Controls_Manager::CHOOSE,
					'default' 		=> '',
					'options' 		=> [
						'left'    		=> [
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
					'selectors'		=> [
						'{{WRAPPER}} .ee-post__excerpt' => 'text-align: {{VALUE}};',
					],
					'condition' => [
						'post_excerpt_position!' => '',
					]
				]
			);

			$this->add_responsive_control(
				'excerpt_margin',
				[
					'label' 		=> __( 'Margin', 'elementor-extras' ),
					'type' 			=> Controls_Manager::DIMENSIONS,
					'size_units' 	=> [ 'px', 'em', '%' ],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-post__excerpt' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
					'condition' => [
						'post_excerpt_position!' => '',
					]
				]
			);

			$this->add_responsive_control(
				'excerpt_padding',
				[
					'label' 		=> __( 'Padding', 'elementor-extras' ),
					'type' 			=> Controls_Manager::DIMENSIONS,
					'size_units' 	=> [ 'px', 'em', '%' ],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-post__excerpt' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
					'condition' => [
						'post_excerpt_position!' => '',
					]
				]
			);

			$this->add_group_control(
				Group_Control_Typography::get_type(),
				[
					'name' 		=> 'excerpt_typography',
					'label' 	=> __( 'Typography', 'elementor-extras' ),
					'scheme' 	=> Scheme_Typography::TYPOGRAPHY_3,
					'selector' 	=> '{{WRAPPER}} .ee-post__excerpt',
				]
			);

		$this->end_controls_section();

	}

	/**
	 * Register Button Style Controls
	 *
	 * @since  1.6.0
	 * @return void
	 */
	public function register_button_style_controls() {

		$this->start_controls_section(
			'section_style_button',
			[
				'label' => __( 'Button', 'elementor-extras' ),
				'tab'   => Controls_Manager::TAB_STYLE,
				'condition' => [
					'post_button_position!' => '',
				]
			]
		);

			$this->add_responsive_control(
				'button_align',
				[
					'label' 		=> __( 'Align', 'elementor-extras' ),
					'type' 			=> Controls_Manager::CHOOSE,
					'default' 		=> '',
					'options' 		=> [
						'left'    		=> [
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
					'selectors'		=> [
						'{{WRAPPER}} .ee-post__read-more' => 'text-align: {{VALUE}};',
					],
					'condition' => [
						'post_button_position!' => '',
					]
				]
			);

			$this->add_responsive_control(
				'button_margin',
				[
					'label' 		=> __( 'Margin', 'elementor-extras' ),
					'type' 			=> Controls_Manager::DIMENSIONS,
					'size_units' 	=> [ 'px', 'em', '%' ],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-post__button' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
					'condition' => [
						'post_button_position!' => '',
					]
				]
			);

			$this->add_responsive_control(
				'button_padding',
				[
					'label' 		=> __( 'Padding', 'elementor-extras' ),
					'type' 			=> Controls_Manager::DIMENSIONS,
					'size_units' 	=> [ 'px', 'em', '%' ],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-post__button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
					'condition' => [
						'post_button_position!' => '',
					],
				]
			);

			$this->add_group_control(
				Group_Control_Border::get_type(),
				[
					'name' 		=> 'button_border',
					'label' 	=> __( 'Border', 'elementor-extras' ),
					'selector' 	=> '{{WRAPPER}} .ee-post__button',
				]
			);

			$this->add_control(
				'button_border_radius',
				[
					'type' 			=> Controls_Manager::DIMENSIONS,
					'label' 		=> __( 'Border Radius', 'elementor-extras' ),
					'size_units' 	=> [ 'px', '%' ],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-post__button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
					'condition' => [
						'post_button_position!' => '',
					],
				]
			);

			$this->add_group_control(
				Group_Control_Typography::get_type(),
				[
					'name' 		=> 'button_typography',
					'label' 	=> __( 'Typography', 'elementor-extras' ),
					'scheme' 	=> Scheme_Typography::TYPOGRAPHY_3,
					'selector' 	=> '{{WRAPPER}} .ee-post__button',
				]
			);

			$this->add_responsive_control(
				'read_more_distance',
				[
					'label' 		=> __( 'Spacing', 'elementor-extras' ),
					'type' 			=> Controls_Manager::SLIDER,
					'range' 		=> [
						'px' 		=> [
							'min' => 0,
							'max' => 100,
						],
					],
					'selectors' 	=> [
						'{{WRAPPER}} .ee-post__read-more' => 'margin-top: {{SIZE}}px;',
					],
					'condition' => [
						'post_button_position!' => '',
					]
				]
			);

			$this->add_group_control(
				Group_Control_Transition::get_type(),
				[
					'name' 			=> 'button',
					'selector' 		=> '{{WRAPPER}} .ee-post__button',
				]
			);

			$this->start_controls_tabs( 'button_tabs' );

			$this->start_controls_tab( 'button_tab_default', [ 'label' => __( 'Default', 'elementor-extras' ) ] );

				$this->add_control(
					'read_more_color',
					[
						'label' 	=> __( 'Color', 'elementor-extras' ),
						'type' 		=> Controls_Manager::COLOR,
						'selectors' => [
							'{{WRAPPER}} .ee-post__button' => 'color: {{VALUE}};',
						],
						'condition' => [
							'post_button_position!' => '',
						]
					]
				);

				$this->add_control(
					'read_more_background_color',
					[
						'label' 	=> __( 'Background Color', 'elementor-extras' ),
						'type' 		=> Controls_Manager::COLOR,
						'selectors' => [
							'{{WRAPPER}} .ee-post__button' => 'background-color: {{VALUE}};',
						],
						'condition' => [
							'post_button_position!' => '',
						]
					]
				);

			$this->end_controls_tab();

			$this->start_controls_tab( 'button_tab_hover', [ 'label' => __( 'Hover', 'elementor-extras' ) ] );

				$this->add_control(
					'read_more_color_hover',
					[
						'label' 	=> __( 'Color', 'elementor-extras' ),
						'type' 		=> Controls_Manager::COLOR,
						'selectors' => [
							'{{WRAPPER}} .ee-post__button:hover' => 'color: {{VALUE}};',
						],
						'condition' => [
							'post_button_position!' => '',
						]
					]
				);

				$this->add_control(
					'read_more_background_color_hover',
					[
						'label' 	=> __( 'Background Color', 'elementor-extras' ),
						'type' 		=> Controls_Manager::COLOR,
						'selectors' => [
							'{{WRAPPER}} .ee-post__button:hover' => 'background-color: {{VALUE}};',
						],
						'condition' => [
							'post_button_position!' => '',
						]
					]
				);

			$this->end_controls_tab();
			$this->end_controls_tabs();

		$this->end_controls_section();

	}

	/**
	 * Register Hover Animation Controls
	 *
	 * @since  1.6.0
	 * @return void
	 */
	public function register_hover_animation_controls() {

		$media_not_empty = [
			'relation'	=> 'or',
			'terms'		=> [
				[
					'name'		=> 'post_avatar_position',
					'operator' 	=> '!=',
					'value'		=> '',
				],
				[
					'name'		=> 'post_author_position',
					'operator' 	=> '!=',
					'value'		=> '',
				],
				[
					'name'		=> 'post_date_position',
					'operator' 	=> '!=',
					'value'		=> '',
				],
				[
					'name'		=> 'post_comments_position',
					'operator' 	=> '!=',
					'value'		=> '',
				],
				[
					'name'		=> 'post_title_position',
					'operator' 	=> '!=',
					'value'		=> '',
				],
				[
					'name'		=> 'post_terms_position',
					'operator' 	=> '!=',
					'value'		=> '',
				]
			],
		];

		$this->start_controls_section(
			'section_style_hover_animation',
			[
				'label' 	=> __( 'Hover Effects', 'elementor-extras' ),
				'tab'   	=> Controls_Manager::TAB_STYLE,
				'condition'	=> [
					'skin_source' => '',
				],
			]
		);

			$this->add_group_control(
				Group_Control_Transition::get_type(),
				[
					'name' 			=> 'media',
					'selector' 		=> '{{WRAPPER}} .ee-post__media,
										{{WRAPPER}} .ee-post__media__content,
										{{WRAPPER}} .ee-post__media__content > *,
										{{WRAPPER}} .ee-post__media__overlay,
										{{WRAPPER}} .ee-post__media__thumbnail,

										{{WRAPPER}} .ee-post__media__header,
										{{WRAPPER}} .ee-post__media__body,
										{{WRAPPER}} .ee-post__media__footer',
				]
			);

			$this->update_control( 'media_transition', array(
				'default' => 'custom',
			));

			$this->add_control(
				'media_content_style_heading',
				[
					'label' 		=> __( 'Content', 'elementor-extras' ),
					'type' 			=> Controls_Manager::HEADING,
					'separator' 	=> 'before',
					'conditions'	=> $media_not_empty,
				]
			);

			$this->add_control(
				'media_content_effect',
				[
					'label' 	=> __( 'Effect', 'elementor-extras' ),
					'type' 		=> Controls_Manager::SELECT,
					'default' 	=> '',
					'options' => [
						''					=> __( 'None', 'elementor-extras' ),
						'fade-in'			=> __( 'Fade In', 'elementor-extras' ),
						'fade-out'			=> __( 'Fade Out', 'elementor-extras' ),
						'from-top'			=> __( 'From Top', 'elementor-extras' ),
						'from-right'		=> __( 'From Right', 'elementor-extras' ),
						'from-bottom'		=> __( 'From Bottom', 'elementor-extras' ),
						'from-left'			=> __( 'From Left', 'elementor-extras' ),
						'fade-from-top'		=> __( 'Fade From Top', 'elementor-extras' ),
						'fade-from-right'	=> __( 'Fade From Right', 'elementor-extras' ),
						'fade-from-bottom'	=> __( 'Fade From Bottom', 'elementor-extras' ),
						'fade-from-left'	=> __( 'Fade From Left', 'elementor-extras' ),
						'to-top'			=> __( 'To Top', 'elementor-extras' ),
						'to-right'			=> __( 'To Right', 'elementor-extras' ),
						'to-bottom'			=> __( 'To Bottom', 'elementor-extras' ),
						'to-left'			=> __( 'To Left', 'elementor-extras' ),
						'fade-to-top'		=> __( 'Fade To Top', 'elementor-extras' ),
						'fade-to-right'		=> __( 'Fade To Right', 'elementor-extras' ),
						'fade-to-bottom'	=> __( 'Fade To Bottom', 'elementor-extras' ),
						'fade-to-left'		=> __( 'Fade To Left', 'elementor-extras' ),
					],
					'conditions'	=> $media_not_empty,
					'prefix_class'	=> 'ee-media-effect__content--',
				]
			);

			$this->add_control(
				'media_area_heading',
				[
					'label' 	=> __( 'Media', 'elementor-extras' ),
					'type' 		=> Controls_Manager::HEADING,
					'separator'	=> 'before',
				]
			);

			$this->start_controls_tabs( 'media_tabs_hover' );

			$this->start_controls_tab( 'media_tab_default', [ 'label' => __( 'Default', 'elementor-extras' ) ] );

				$this->add_responsive_control(
					'media_area_scale',
					[
						'label' 		=> __( 'Scale', 'elementor-extras' ),
						'type' 			=> Controls_Manager::SLIDER,
						'range' 		=> [
							'px' 		=> [
								'min' => 0.7,
								'max' => 1.3,
								'step'=> 0.01,
							],
						],
						'selectors' 	=> [
							'{{WRAPPER}} .ee-post__media' => 'transform: scale({{SIZE}});',
						],
					]
				);

				$this->add_control(
					'media_area_color',
					[
						'label' 	=> __( 'Color', 'elementor-extras' ),
						'type' 		=> Controls_Manager::COLOR,
						'selectors' => [
							'{{WRAPPER}} .ee-post__media__content *' => 'color: {{VALUE}};',
						],
					]
				);

				$this->add_group_control(
					Group_Control_Box_Shadow::get_type(),
					[
						'name' 		=> 'media_area_box_shadow',
						'selector' 	=> '{{WRAPPER}} .ee-post__media',
						'separator'	=> '',
					]
				);

				$this->add_group_control(
					Group_Control_Border::get_type(),
					[
						'name' 		=> 'media_area_border',
						'label' 	=> __( 'Border', 'elementor-extras' ),
						'selector' 	=> '{{WRAPPER}} .ee-post__media',
					]
				);

			$this->end_controls_tab();

			$this->start_controls_tab( 'media_tab_hover', [ 'label' => __( 'Hover', 'elementor-extras' ) ] );

				$this->add_control(
					'media_area_heading_hover',
					[
						'label' 	=> __( 'Area', 'elementor-extras' ),
						'type' 		=> Controls_Manager::HEADING,
					]
				);

				$this->add_responsive_control(
					'media_area_scale_hover',
					[
						'label' 		=> __( 'Scale', 'elementor-extras' ),
						'type' 			=> Controls_Manager::SLIDER,
						'range' 		=> [
							'px' 		=> [
								'min' => 0.7,
								'max' => 1.3,
								'step'=> 0.01,
							],
						],
						'selectors' 	=> [
							'{{WRAPPER}} .ee-post__media:hover' => 'transform: scale({{SIZE}});',
						],
					]
				);

				$this->add_control(
					'media_area_color_hover',
					[
						'label' 	=> __( 'Color', 'elementor-extras' ),
						'type' 		=> Controls_Manager::COLOR,
						'selectors' => [
							'{{WRAPPER}} .ee-post__media:hover .ee-post__media__content *' => 'color: {{VALUE}};',
						],
					]
				);

				$this->add_group_control(
					Group_Control_Box_Shadow::get_type(),
					[
						'name' 		=> 'media_area_box_shadow_hover',
						'selector' 	=> '{{WRAPPER}} .ee-post__media:hover',
						'separator'	=> '',
					]
				);

				$this->add_control(
					'media_area_border_color_hover',
					[
						'label' 	=> __( 'Border Color', 'elementor-extras' ),
						'type' 		=> Controls_Manager::COLOR,
						'selectors' => [
							'{{WRAPPER}} .ee-post__media:hover' => 'border-color: {{VALUE}};',
						],
					]
				);

			$this->end_controls_tab();

			$this->end_controls_tabs();

			$this->add_control(
				'media_thumbnail_heading',
				[
					'label' 	=> __( 'Thumbnail', 'elementor-extras' ),
					'type' 		=> Controls_Manager::HEADING,
					'separator'	=> 'before',
				]
			);

			$this->start_controls_tabs( 'thumbnail_tabs_hover' );

			$this->start_controls_tab( 'thumbnail_tab_default', [ 'label' => __( 'Default', 'elementor-extras' ) ] );

				$this->add_responsive_control(
					'media_thumbnail_scale',
					[
						'label' 		=> __( 'Scale', 'elementor-extras' ),
						'type' 			=> Controls_Manager::SLIDER,
						'range' 		=> [
							'px' 		=> [
								'min' => 1,
								'max' => 1.3,
								'step'=> 0.01,
							],
						],
						'selectors' 	=> [
							'{{WRAPPER}} .ee-post__media__thumbnail' => 'transform: scale({{SIZE}});',
						],
					]
				);

				$this->add_group_control(
					Group_Control_Css_Filter::get_type(),
					[
						'name' => 'media_thumbnail_css_filters',
						'selector' => '{{WRAPPER}} .ee-post__media__thumbnail',
					]
				);

			$this->end_controls_tab();

			$this->start_controls_tab( 'thumbnail_tab_hover', [ 'label' => __( 'Hover', 'elementor-extras' ) ] );

				$this->add_responsive_control(
					'media_thumbnail_scale_hover',
					[
						'label' 		=> __( 'Scale', 'elementor-extras' ),
						'type' 			=> Controls_Manager::SLIDER,
						'range' 		=> [
							'px' 		=> [
								'min' => 1,
								'max' => 1.3,
								'step'=> 0.01,
							],
						],
						'selectors' 	=> [
							'{{WRAPPER}} .ee-post__media:hover .ee-post__media__thumbnail' => 'transform: scale({{SIZE}});',
						],
					]
				);

				$this->add_group_control(
					Group_Control_Css_Filter::get_type(),
					[
						'name' => 'media_thumbnail_css_filters_hover',
						'selector' => '{{WRAPPER}} .ee-post__media:hover .ee-post__media__thumbnail',
					]
				);

			$this->end_controls_tab();

			$this->end_controls_tabs();

			$this->add_control(
				'media_overlay_heading',
				[
					'label' 	=> __( 'Overlay', 'elementor-extras' ),
					'type' 		=> Controls_Manager::HEADING,
					'separator'	=> 'before',
				]
			);

			$this->start_controls_tabs( 'overlay_tabs_hover' );

			$this->start_controls_tab( 'overlay_tab_default', [ 'label' => __( 'Default', 'elementor-extras' ) ] );

				$this->add_group_control(
					Group_Control_Background::get_type(),
					[
						'name' 		=> 'media_overlay_background_color',
						'types' 	=> [ 'classic', 'gradient' ],
						'selector' 	=> '{{WRAPPER}} .ee-post__media__overlay',
						'default'	=> 'classic',
						'exclude'	=> [
							'image',
						]
					]
				);

				$this->add_control(
					'media_overlay_blend',
					[
						'label' 		=> __( 'Blend mode', 'elementor-extras' ),
						'type' 			=> Controls_Manager::SELECT,
						'default' 		=> 'normal',
						'options' => [
							'normal'			=> __( 'Normal', 'elementor-extras' ),
							'multiply'			=> __( 'Multiply', 'elementor-extras' ),
							'screen'			=> __( 'Screen', 'elementor-extras' ),
							'overlay'			=> __( 'Overlay', 'elementor-extras' ),
							'darken'			=> __( 'Darken', 'elementor-extras' ),
							'lighten'			=> __( 'Lighten', 'elementor-extras' ),
							'color'				=> __( 'Color', 'elementor-extras' ),
							'color-dodge'		=> __( 'Color Dodge', 'elementor-extras' ),
							'hue'				=> __( 'Hue', 'elementor-extras' ),

						],
						'selectors' 	=> [
							'{{WRAPPER}} .ee-post__media__overlay' => 'mix-blend-mode: {{VALUE}};',
						],
					]
				);

				$this->add_control(
					'media_overlay_blend_notice',
					[
						'type' 				=> Controls_Manager::RAW_HTML,
						'raw' 				=> sprintf( __( 'Please check blend mode support for your browser %1$s here %2$s', 'elementor-extras' ), '<a href="https://caniuse.com/#search=mix-blend-mode" target="_blank">', '</a>' ),
						'content_classes' 	=> 'elementor-panel-alert elementor-panel-alert-warning',
						'condition' 		=> [
							'media_overlay_blend!' => 'normal'
						],
					]
				);

				$this->add_responsive_control(
					'media_overlay_opacity',
					[
						'label' 		=> __( 'Opacity', 'elementor-extras' ),
						'type' 			=> Controls_Manager::SLIDER,
						'range' 		=> [
							'px' 		=> [
								'min' => 0,
								'max' => 1,
								'step'=> 0.1,
							],
						],
						'selectors' 	=> [
							'{{WRAPPER}} .ee-post__media__overlay' => 'opacity: {{SIZE}};',
						],
					]
				);

			$this->end_controls_tab();

			$this->start_controls_tab( 'overlay_tab_hover', [ 'label' => __( 'Hover', 'elementor-extras' ) ] );

				$this->add_group_control(
					Group_Control_Background::get_type(),
					[
						'name' 		=> 'media_overlay_background_color_hover',
						'types' 	=> [ 'classic', 'gradient' ],
						'selector' 	=> '{{WRAPPER}} .ee-post__media:hover .ee-post__media__overlay',
						'default'	=> 'classic',
						'exclude'	=> [
							'image',
						]
					]
				);

				$this->add_responsive_control(
					'media_overlay_opacity_hover',
					[
						'label' 		=> __( 'Opacity', 'elementor-extras' ),
						'type' 			=> Controls_Manager::SLIDER,
						'range' 		=> [
							'px' 		=> [
								'min' => 0,
								'max' => 1,
								'step'=> 0.1,
							],
						],
						'selectors' 	=> [
							'{{WRAPPER}} .ee-post__media:hover .ee-post__media__overlay' => 'opacity: {{SIZE}};',
						],
					]
				);

			$this->end_controls_tab();

			$this->end_controls_tabs();

		$this->end_controls_section();

	}

	/**
	 * Get Areas Metas Controls Conditions
	 *
	 * @since  1.6.0
	 * @return void
	 */
	protected function get_area_metas_controls_conditions( $area ) {

		if ( ! $area )
			return;

		$conditions = [
			'relation' 	=> 'or',
			'terms'		=> [],
		];

		$metas = PostsModule::get_meta_parts();

		foreach ( $metas as $meta ) {
			$conditions['terms'][] = [
				'name' 		=> 'post_' . $meta . '_position',
				'operator' 	=> '==',
				'value' 	=> $area,
			];
		}

		return $conditions;
	}

	/**
	 * Get Empty Area Condition
	 *
	 * @since  1.6.0
	 * @return void
	 */
	protected function get_empty_area_condition( $area, $other_conditions = [] ) {
		if ( ! $area )
			return;

		$conditions = $this->get_area_metas_controls_conditions( $area );
		$parts = PostsModule::get_content_post_parts();

		foreach( $parts as $part ) {
			$conditions['terms'][] = [
				'name' 		=> 'post_' . $part . '_position',
				'operator' 	=> '==',
				'value' 	=> $area,
			];
		}

		$conditions = array_merge( $conditions, $other_conditions );

		return $conditions;
	}

	/**
	 * Get Ordered Posts Parts
	 *
	 * @since  1.6.0
	 * @return void
	 */
	public function get_ordered_post_parts( $parts ) {

		if ( ! $parts )
			return;
		
		$_parts = [];
		$settings = $this->get_settings();

		foreach ( $parts as $part ) {

			$order = $settings['post_' . $part . '_order'];

			if ( ! $order ) $order = 0;

			$_parts[$part] = $order;
		}
		
		asort( $_parts );

		return $_parts;
	}

	/**
	 * Get Post Classes
	 *
	 * @since  2.1.0
	 * @return array
	 */
	public function get_post_classes() {
		global $post;

		$settings = $this->get_settings();
		$post_classes = [ 'ee-post' ];

		if ( 'yes' === $settings['post_media'] && in_array( $settings[ 'post_media_position' ], array( 'left', 'right' ) ) ) {
			$post_classes[] = 'ee-post--horizontal';
			$post_classes[] = 'ee-post--horizontal__' . $settings[ 'post_media_position' ];
		}

		if ( is_sticky( $post->ID ) ) {
			$post_classes[] = 'sticky';
		}

		return $post_classes;
	}

	/**
	 * In in area
	 *
	 * @since  1.6.0
	 * @return void
	 */
	public function is_in_area( $setting_key, $area ) {

		if ( empty( $setting_key ) || empty( $area ) )
			return;

		$settings = $this->get_settings();

		if ( $settings[ $setting_key ] === $area )
			return true;

		return false;
	}

	/**
	 * Checks if a particular area of the layout has any content
	 *
	 * @since 1.6.0
	 * @return bool
	 * 
	 */
	public function is_empty_area( $area ) {

		if ( empty( $area ) )
			return;

		$settings = $this->get_settings();

		foreach ( PostsModule::get_content_parts() as $_part ) {
			if ( $settings['post_' . $_part . '_position'] === $area ) {

				// Additional check to see if we have any terms in this area
				if ( 'terms' === $_part ) {
					if ( false !== $this->get_terms() )
						return false;
				} else {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Checks if any metas are in a specific area
	 *
	 * @since 1.6.0
	 * @return bool
	 */
	public function metas_in_area( $area ) {

		foreach ( PostsModule::get_meta_parts() as $_part ) {
			if ( $this->is_in_area( 'post_' . $_part . '_position', $area ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Render
	 * 
	 * Render widget contents on frontend
	 *
	 * @since  1.6.0
	 * @return void
	 */
	public function render() {
		$this->add_inline_editing_attributes( 'classic_filters_all_text', 'none' );
	}

	/**
	 * Get link page
	 * 
	 * Fetch link template for page number
	 *
	 * @since  2.1.3
	 * @return void
	 */
	private function get_wp_link_page( $page, $multiple ) {

		if ( ! is_singular() || is_front_page() ) {
			$url = get_pagenum_link( $page );

			if ( $multiple ) {
				$url = add_query_arg( 'posts', $this->get_id(), $url );
			}
			return $url;
		}

		global $wp_rewrite;

		$post 		= get_post();
		$query_args = [];
		$url 		= get_permalink();

		if ( $page > 1 ) {
			if ( '' === get_option( 'permalink_structure' ) || in_array( $post->post_status, [ 'draft', 'pending' ] ) ) {
				$url = add_query_arg( 'page', $page, $url );
			} elseif ( get_option( 'show_on_front' ) === 'page' && (int) get_option( 'page_on_front' ) === $post->ID ) {
				$url = trailingslashit( $url ) . user_trailingslashit( "$wp_rewrite->pagination_base/" . $page, 'single_paged' );
			} else {
				$url = trailingslashit( $url ) . user_trailingslashit( $page, 'single_paged' );
			}
		}

		if ( is_preview() ) {
			if ( ( 'draft' !== $post->post_status ) && isset( $_GET['preview_id'], $_GET['preview_nonce'] ) ) {
				$query_args['preview_id'] = wp_unslash( $_GET['preview_id'] );
				$query_args['preview_nonce'] = wp_unslash( $_GET['preview_nonce'] );
			}

			$url = get_preview_post_link( $post, $query_args, $url );
		}

		if ( $multiple ) {
			$url = add_query_arg( 'posts', $this->get_id(), $url );
		}

		return $url;
	}

	/**
	 * Render Previous Nav Link
	 *
	 * @since  1.6.0
	 * @param  limit|int
	 * @return void
	 */
	public function render_previous_nav_link( $multiple ) {

		$page = $this->get_current_page();

		if ( $page > 1 ) {
			$prev_page = intval( $page ) - 1;
			$prev_page = ( $prev_page < 1 ) ? 1 : $prev_page;

			printf( '<a class="ee-pagination__previous page-numbers" href="%1$s">%2$s</a>', $this->get_wp_link_page( $prev_page, $multiple ), $this->get_skin_setting( 'pagination_previous_label' ) );
		}
	}

	/**
	 * Render Next Nav Link
	 *
	 * @since  1.6.0
	 * @param  limit|int
	 * @return void
	 */
	public function render_next_nav_link( $limit = null, $multiple ) {
		if ( ! $limit )
			$limit = $this->get_query()->max_num_pages;

		$page = $this->get_current_page();
		$next_page = intval( $page ) + 1;

		if ( $next_page <= $limit ) {
			printf( '<a class="ee-pagination__next page-numbers" href="%1$s">%2$s</a>', $this->get_wp_link_page( $next_page, $multiple ), $this->get_skin_setting( 'pagination_next_label' ) );
		}
	}

	/**
	 * Query Posts
	 *
	 * @since  1.6.0
	 * @return void
	 */
	public function query_posts() {

		$sticky_enabled = 'yes' === $this->get_settings( 'sticky_posts' );

		$query_args = [
			'ignore_sticky_posts' 	=> $sticky_enabled ? 0 : 1,
			'posts_per_page' 		=> $sticky_enabled ? 9999 : $this->get_settings( 'posts_per_page' ),
			'paged' 				=> $sticky_enabled ? 0 : $this->get_current_page(),
		];

		if ( $this->get_settings( 'posts_orderby_meta_key' ) ) {
			$query_args['meta_key'] = $this->get_settings( 'posts_orderby_meta_key' );
		}

		$this->set_query( $query_args );

		if ( 'yes' === $this->get_settings( 'sticky_posts' ) ) {
			$this->sort_sticky_posts( $query_args );
		}
	}

	/**
	 * Alters the query to take into account sticky options
	 *
	 * @since 1.9.14
	 * @param Array $query_args
	 */
	public function sort_sticky_posts( $query_args ) {

		if ( empty( $this->_query->posts ) )
			return;

		$post__in = array();

		if ( 'yes' === $this->get_settings( 'sticky_only' ) ) {
			$post__in = get_option( 'sticky_posts' );
		} else {
			foreach ( $this->_query->posts as $index => $query_post ) {
				$post__in[] = $query_post->ID;
			}
		}

		$query_args['post__in'] 			= $post__in;
		$query_args['posts_per_page'] 		= $this->get_settings( 'posts_per_page' );
		$query_args['paged'] 				= $this->get_current_page();
		$query_args['orderby'] 				= 'post__in';
		$query_args['ignore_sticky_posts'] 	= 1;

		$this->set_query( $query_args );

		usort( $this->_query->posts, [ $this, 'sort_by_sticky' ] );
	}

	/**
	 * Custom sort function for sticky posts
	 *
	 * @since 1.9.14
	 * @param \WP_Post $a
	 * @param Function $b
	 */
	public function sort_by_sticky( $a, $b ) {
	    if ( is_sticky( $a->ID ) && ! is_sticky( $b->ID ) ) {
	    	return -1;
	    } else if ( ! is_sticky( $a->ID ) && is_sticky( $b->ID ) ) {
	    	return 1;
	    }
	    return 0;
	}

	/**
	 * Checks for the Query ID and inits the WP_Query object
	 *
	 * @since 1.9.14
	 * @param Array $query_args
	 */
	public function set_query( $query_args ) {

		if ( '' === $query_args['posts_per_page'] ) {
			// Handle empty posts per page setting
			$query_args['posts_per_page'] = (int)get_option( 'posts_per_page' );
		}
		
		$elementor_query = Module_Query::instance();
		
		add_filter( 'elementor/query/get_query_args/current_query', [ $this, 'fix_default_query_args' ] );

		$this->_query = $elementor_query->get_query( $this, 'posts', $query_args, [] );

		remove_filter( 'elementor/query/get_query_args/current_query', [ $this, 'fix_default_query_args' ] );
	}

	/**
	 * Filter to override posts per page on current query setting
	 *
	 * @since 2.1.5 
	 * @param Array $global_args
	 */
	public function fix_default_query_args( $global_args ) {

		$posts_per_page = $this->get_settings( 'posts_per_page' );

		// When using current_query some default categories are set with a new WP_Query
		// which restrict results in archive pages
		if ( 'current_query' === $this->get_settings( 'posts_post_type' ) && ! is_category() ) {
			$global_args['cat'] = false;
			$global_args['category_name'] = '';
		}

		// Fix posts per page
		if ( $posts_per_page && $posts_per_page > 0 ) {
			$global_args['posts_per_page'] = $posts_per_page;
		}

		return $global_args;
	}

	/**
	 * Content Template
	 * 
	 * Javascript content template for quick rendering. None in this case
	 *
	 * @since  1.6.0
	 * @return void
	 */
	protected function _content_template() {}

	/**
	 * get_repeater_setting_key wrapper
	 *
	 * @since 2.1.2
	 * @return void
	 */
	public function _get_repeater_setting_key( $setting_key, $repeater_key, $repeater_item_index ) {
		return $this->get_repeater_setting_key( $setting_key, $repeater_key, $repeater_item_index );
	}
}
