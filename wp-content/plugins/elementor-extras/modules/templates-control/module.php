<?php
namespace ElementorExtras\Modules\TemplatesControl;

// Elementor Extras Classes
use ElementorExtras\Utils;
use ElementorExtras\Base\Module_Base;

// Elementor Classes
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * @since 2.0.0
 */
class Module extends Module_Base {

	/**
	 * @since 2.0.0
	 */
	public function get_name() {
		return 'templates';
	}

	/**
	 * @since 2.0.0
	 */
	public function get_widgets() {
		return [];
	}

	/**
	 * @since 2.0.0
	 */
	protected static function get_templates( $args = [] ) {

		if ( ! method_exists( '\Elementor\TemplateLibrary\Manager', 'get_source' ) ||
			 ! method_exists( '\Elementor\TemplateLibrary\Source_Local', 'get_items' ) )
			return;

		return Utils::elementor()->templates_manager->get_source( 'local' )->get_items( $args );
	}

	/**
	 * @since 2.0.0
	 */
	protected static function empty_templates_message( $template_type = '' ) {
		return '<div id="elementor-widget-template-empty-templates">
				<div class="elementor-widget-template-empty-templates-icon"><i class="eicon-nerd"></i></div>
				<div class="elementor-widget-template-empty-templates-title">' . sprintf( __( 'You Haven’t Saved %sTemplates Yet.', 'elementor-extras' ), ucfirst( $template_type ) . ' ' ) . '</div>
				<div class="elementor-widget-template-empty-templates-footer">' . __( 'Want to learn more about Elementor library?', 'elementor-extras' ) . ' <a class="elementor-widget-template-empty-templates-footer-url" href="https://go.elementor.com/docs-library/" target="_blank">' . __( 'Click Here', 'elementor-extras' ) . '</a>
				</div>
				</div>';
	}

	/**
	 * @since 2.0.0
	 */
	public static function add_controls( $object, $args = [] ) {

		$defaults = [
			'type' => [ 'section', 'page', 'widget' ],
			'condition' => [],
			'prefix' => '',
		];

		$args = wp_parse_args( $args, $defaults );

		self::add_types_control( $object, $args );

		if ( ! empty( $args['type'] ) ) {
			if ( is_array( $args['type'] ) ) {
				foreach ( $args['type'] as $type ) {
					self::add_control( $object, $args, $type );
				}
			} else {
				self::add_control( $object, $args, $args['type'] );
			}
		}
	}

	/**
	 * @since 2.0.0
	 */
	protected static function add_types_control( $object, $args = [] ) {

		if ( ! $object )
			return;

		$object->add_control(
			$args['prefix'] . 'template_type',
			[
				'label'		=> __( 'Template Type', 'elementor-extras' ),
				'type' 		=> Controls_Manager::SELECT,
				'default' 	=> 'section',
				'options' 	=> [
					'section'	=> __( 'Section', 'elementor-extras' ),
					'page'		=> __( 'Page', 'elementor-extras' ),
					'widget'	=> __( 'Widget', 'elementor-extras' ),
				],
				'condition' 	=> $args['condition'],
			]
		);
	}

	/**
	 * @since 2.0.0
	 */
	protected static function add_control( $object, $args = [], $type = 'section' ) {
		$defaults = [];

		$args = wp_parse_args( $args, $defaults );

		$templates = self::get_templates( [ 'type' => $type ] );
		$options = [];
		$types = [];

		$prefix 			= $args['prefix'];
		$no_templates_key 	= $prefix . 'no_' . $type . '_templates';
		$templates_key 		= $prefix . $type . '_template_id';

		if ( empty( $templates ) ) {

			$object->add_control(
				$no_templates_key,
				[
					'label' => false,
					'type' 	=> Controls_Manager::RAW_HTML,
					'raw' 	=> self::empty_templates_message( $type ),
					'condition'	=> array_merge( $args['condition'], [
						$args['prefix'] . 'template_type' => $type
					] ),
				]
			);

			return;
		}

		$options['0'] = '— ' . sprintf( __( 'Select %s', 'elementor-extras' ), $type ) . ' —';

		foreach ( $templates as $template ) {
			$options[ $template['template_id'] ] = $template['title'] . ' (' . $template['type'] . ')';
		}

		$object->add_control(
			$templates_key,
			[
				'label' 		=> sprintf( __( 'Choose %s', 'elementor-extras' ), $type ),
				'type' 			=> Controls_Manager::SELECT,
				'default' 		=> '0',
				'options' 		=> $options,
				'condition'		=> array_merge( $args['condition'], [
					$prefix . 'template_type' => $type,
				] ),
			]
		);
	}

	/**
	 * @since 2.0.0
	 */
	public static function render_template_content( $id ) {

		// Double check required method and template_id
		if ( ! $id || ! method_exists( '\Elementor\Frontend', 'get_builder_content_for_display' ) )
			return;

		if ( 'publish' !== get_post_status( $id ) )
			return;

		$template = Utils::elementor()->frontend->get_builder_content_for_display( $id );

		if ( $template ) {
			?><div class="elementor-template ee-template"><?php echo $template; ?></div><?php
		} else {
			_e( 'No template selected.', 'elementor-extras' );
		}
	}
}
