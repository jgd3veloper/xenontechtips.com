<?php

/**
 * PA Core.
 */
namespace PremiumAddons\Includes;

if( ! class_exists('PA_Core') ) {
    
    /*
    * Intialize and Sets up the plugin
    */
    class PA_Core {
        
        /**
         * Member Variable
         *
         * @var instance
         */
        private static $instance = null;
        
        /**
         * Sets up needed actions/filters for the plug-in to initialize.
         * 
         * @since 1.0.0
         * @access public
         * 
         * @return void
         */
        public function __construct() {

            //Autoloader
            spl_autoload_register( array( $this, 'autoload' ) );
            
            //Run plugin and require the necessary files
            add_action( 'plugins_loaded', array( $this, 'premium_addons_elementor_setup' ) );
            
            //Load Elementor files
            add_action( 'elementor/init', array( $this, 'elementor_init' ) );
            add_action( 'init', array( $this, 'init' ), -999 );

            //Register Rollback hooks
            add_action( 'admin_post_premium_addons_rollback', 'post_premium_addons_rollback' );
            
            //Register Activation hooks
            register_activation_hook( PREMIUM_ADDONS_FILE, array( $this, 'set_transient' ) );
            
        }

        /**
         * AutoLoad
         *
         * @since 3.20.9
         * @param string $class class.
         */
        public function autoload( $class ) {
            
            if ( 0 !== strpos( $class, 'PremiumAddons' ) ) {
                return;
            }
            
            $class_to_load = $class;
            
            if ( ! class_exists( $class_to_load ) ) {
                $filename = strtolower(
                    preg_replace(
                        array( '/^' . 'PremiumAddons' . '\\\/', '/([a-z])([A-Z])/', '/_/', '/\\\/' ),
                        array( '', '$1-$2', '-', DIRECTORY_SEPARATOR ),
                        $class_to_load
                    )
                );
                
                $filename = PREMIUM_ADDONS_PATH . $filename . '.php';

                if ( is_readable( $filename ) ) {
                    
                    include( $filename );
                }
            }
        }
        
        /**
         * Installs translation text domain and checks if Elementor is installed
         * 
         * @since 1.0.0
         * @access public
         * 
         * @return void
         */
        public function premium_addons_elementor_setup() {
            
            //Load plugin textdomain
            $this->load_domain();
            
            //load plugin necessary files
            $this->load_files(); 

            //Make sure PAPRO is active
            if( defined( 'PREMIUM_PRO_ADDONS_VERSION' ) ) {
                //Make sure PAPRO is outdated
                if ( false == Helper_Functions::check_papro_version() ) {
                    $this->init_papro_updater();
                }
            }
            
        }

        /**
         * Init PAPRO Updater
         * 
         * Initialize Premium Addons PRO updater class
         * 
         * @since 4.0.7
         * @access public
         */
        public function init_papro_updater() {

            //load updater class
            require_once ( PREMIUM_PRO_ADDONS_PATH . 'license/updater.php' ); 

            // Disable SSL verification
            add_filter('edd_sl_api_request_verify_ssl', '__return_false');

            //Get License Key
            $license_key = get_option( 'papro_license_key', false );

            $edd_updater = new \PAPRO_Plugin_Updater(
                PAPRO_STORE_URL,
                PREMIUM_PRO_ADDONS_FILE,
                array(
                    'version'   => PREMIUM_PRO_ADDONS_VERSION,
                    'license'   => $license_key,
                    'item_id'   => PAPRO_ITEM_ID,
                    'author'    => 'Leap13',
                    'url'       => home_url(),
                    'beta'      => false
                )
            );

        }
        
        /**
         * Set transient for admin review notice
         * 
         * @since 3.1.7
         * @access public
         * 
         * @return void
         */
        public function set_transient() {
            
            $cache_key = 'premium_notice_' . PREMIUM_ADDONS_VERSION;
            
            $expiration = 3600 * 72;
            
            set_transient( $cache_key, true, $expiration );
        }
        
        
        /**
         * Require initial necessary files
         * 
         * @since 2.6.8
         * @access public
         * 
         * @return void
         */
        public function load_files() {
            
            \PremiumAddons\Admin\Includes\Admin_Helper::get_instance();

            Addons_Category::get_instance();

            require_once ( PREMIUM_ADDONS_PATH . 'includes/class-premium-template-tags.php' );
            
            if ( is_admin() ) {

                require_once ( PREMIUM_ADDONS_PATH . 'admin/includes/dep/maintenance.php');
                require_once ( PREMIUM_ADDONS_PATH . 'admin/includes/dep/rollback.php');
                
                Beta_Testers::get_instance();
                
                \PremiumAddons\Admin\Includes\Admin_Notices::get_instance();
                
            }
    
        }
        
        /**
         * Load plugin translated strings using text domain
         * 
         * @since 2.6.8
         * @access public
         * 
         * @return void
         */
        public function load_domain() {
            
            load_plugin_textdomain( 'premium-addons-for-elementor' );
            
        }
        
        /**
         * Elementor Init
         * 
         * @since 2.6.8
         * @access public
         * 
         * @return void
         */
        public function elementor_init() {

            Compatibility\Premium_Addons_Wpml::get_instance();

            Addons_Integration::get_instance();

            if ( version_compare( ELEMENTOR_VERSION, '2.0.0' ) < 0 ) {
    
                \Elementor\Plugin::instance()->elements_manager->add_category(
                    'premium-elements',
                    array(
                        'title' => Helper_Functions::get_category()
                    ),
                    1
                );
            }

            //Make sure Woocommerce is insalled and active.
            // if( class_exists('Woocommerce') ) {
            //     Modules_Manager::get_instance();
            // }

        }
        
        /*
         * Init 
         * 
         * @since 3.4.0
         * @access public
         * 
         * @return void
         */
        public function init() {
            
            if ( \PremiumAddons\Admin\Includes\Admin_Helper::check_premium_templates() ) {
                require_once ( PREMIUM_ADDONS_PATH . 'includes/templates/templates.php');
            }
        }


        /**
         * Creates and returns an instance of the class
         * 
         * @since 2.6.8
         * @access public
         * 
         * @return object
         */
        public static function get_instance() {
            if( self::$instance == null ) {
                self::$instance = new self;
            }
            return self::$instance;
        }
    
    }
}

if ( ! function_exists( 'pa_core' ) ) {
    
	/**
	 * Returns an instance of the plugin class.
	 * @since  1.0.0
	 * @return object
	 */
	function pa_core() {
		return PA_Core::get_instance();
	}
}

pa_core();