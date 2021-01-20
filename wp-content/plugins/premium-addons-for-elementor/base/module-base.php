<?php

namespace PremiumAddons\Base;

use PremiumAddons\Admin\Includes\Admin_Helper;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


/**
 * Module Base
 *
 * @since 4.0.0
 */
abstract class Module_Base {

	/**
	 * Reflection
	 *
	 * @var reflection
	 */
	private $reflection;
    
    /**
	 * Modules
	 *
	 * @var modules
	 */
    private static $modules = null;

	/**
	 * Reflection
	 *
	 * @var instances
	 */
	protected static $instances = [];


	/**
	 * Class name to Call
	 *
	 * @since 4.0.0
	 */
	public static function class_name() {
		return get_called_class();
	}

	/**
	 * Class instance
	 *
	 * @since 4.0.0
	 *
	 * @return static
	 */
	public static function instance() {

		if ( empty( static::$instances[ static::class_name() ] ) ) {

            static::$instances[ static::class_name() ] = new static();
            
		}

		return static::$instances[ static::class_name() ];
	}

	/**
	 * Constructor
	 */
	public function __construct() {

		$this->reflection = new \ReflectionClass( $this );

		add_action( 'elementor/widgets/widgets_registered', [ $this, 'init_widgets' ] );
	}

	/**
	 * Init Widgets
	 *
	 * @since 4.0.0
	 */
	public function init_widgets() {

        self::$modules = Admin_Helper::get_enabled_elements();
        
		$widget_manager = \Elementor\Plugin::instance()->widgets_manager;
        
		foreach ( $this->get_widgets() as $widget ) {
            
            $key = sprintf( 'premium-%s', strtolower( str_replace('_', '-', $widget ) ) );

            $enabled = isset( self::$modules[ $key ] ) ? self::$modules[ $key ] : '';

            if ( filter_var( $enabled, FILTER_VALIDATE_BOOLEAN ) ) {
                $class_name = $this->reflection->getNamespaceName() . '\Widgets\\' . $widget;

                $widget_manager->register_widget_type( new $class_name() );
            }
            

		}
	}

	/**
	 * Get Widgets
	 *
	 * @since 4.0.0
	 *
	 * @return array
	 */
	public function get_widgets() {
		return [];
	}
}