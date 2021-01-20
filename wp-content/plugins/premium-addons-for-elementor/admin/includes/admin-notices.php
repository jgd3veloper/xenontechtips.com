<?php

/**
 * PA Admin Notices.
 */
namespace PremiumAddons\Admin\Includes;

use PremiumAddons\Includes\Helper_Functions;

if( ! defined( 'ABSPATH') ) exit();

/**
 * Class Admin_Notices
 */
class Admin_Notices {
    
    /**
	 * Class object
	 *
	 * @var instance
	 */
    private static $instance = null;
    
    /**
	 * Elementor slug
	 *
	 * @var elementor
	 */
    private static $elementor = 'elementor';
    
    /**
	 * PAPRO Slug
	 *
	 * @var papro
	 */
    private static $papro = 'premium-addons-pro';
    
    /**
    * Constructor for the class
    */
    public function __construct() {
        
        add_action( 'admin_init', array( $this, 'init') );
        
        add_action( 'admin_notices', array( $this, 'admin_notices' ) );
        
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
        
        add_action( 'wp_ajax_pa_reset_admin_notice', array( $this, 'reset_admin_notice' ) );
        
        add_action( 'wp_ajax_pa_dismiss_admin_notice', array( $this, 'dismiss_admin_notice' ) );
        
    }
    
    /**
    * init required functions
    */
    public function init() {

        $this->handle_review_notice();
        
        $this->handle_major_update_notice();

    }
    
    /**
    * init notices check functions
    */
    public function admin_notices() {
        
        $this->required_plugins_check();
        
        $cache_key = 'premium_notice_' . PREMIUM_ADDONS_VERSION;
        
        $response = get_transient( $cache_key );
        
        $show_review = get_option( 'pa_review_notice' );
        
        //Make sure Already did was not clicked before.
        if( '1' !== $show_review ) {
            if ( false == $response ) {
                $this->get_review_notice();
            }
        }
        
        $this->get_major_update_notice();
        
    }

    /**
     * 
     * Checks if review message is dismissed.
     * 
     * @access public
     * @return void
     * 
     */
    public function handle_review_notice() {

        if ( ! isset( $_GET['pa_review'] ) ) {
            return;
        }

        if ( 'opt_out' === $_GET['pa_review'] ) {
            check_admin_referer( 'opt_out' );

            update_option( 'pa_review_notice', '1' );
        }

        wp_redirect( remove_query_arg( 'pa_review' ) );
        
        exit;
    }

    /**
     * Checks if Premium Horizontal Scroll message is dismissed.
     * 
     * @since 3.11.7
     * @access public
     * 
     * @return void
     */
    public function handle_major_update_notice() {
        
        if ( ! isset( $_GET['major_update'] ) ) {
            return;
        }

        if ( 'opt_out' === $_GET['major_update'] ) {
            check_admin_referer( 'opt_out' );

            update_option( 'major_update_notice', '1' );
        }

        wp_redirect( remove_query_arg( 'major_update' ) );
        exit;
    }

    /**
     * Required plugin check
     * 
     * Shows an admin notice when Elementor is missing.
     * 
     * @access public
     * 
     * @return boolean
     */
    public function required_plugins_check() {

        $elementor_path = sprintf( '%1$s/%1$s.php', self::$elementor );
        
        if( ! defined('ELEMENTOR_VERSION' ) ) {

            if ( ! Helper_Functions::is_plugin_installed( $elementor_path ) ) {

                if( self::check_user_can( 'install_plugins' ) ) {

                    $install_url = wp_nonce_url( self_admin_url( sprintf( 'update.php?action=install-plugin&plugin=%s', self::$elementor ) ), 'install-plugin_elementor' );

                    $message = sprintf( '<p>%s</p>', __('Premium Addons for Elementor is not working because you need to Install Elementor plugin.', 'premium-addons-for-elementor' ) );

                    $message .= sprintf( '<p><a href="%s" class="button-primary">%s</a></p>', $install_url, __( 'Install Now', 'premium-addons-for-elementor' ) );

                }
            } else {
                if( self::check_user_can( 'activate_plugins' ) ) {

                    $activation_url = wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . $elementor_path . '&amp;plugin_status=all&amp;paged=1&amp;s', 'activate-plugin_' . $elementor_path );

                    $message = '<p>' . __( 'Premium Addons for Elementor is not working because you need to activate Elementor plugin.', 'premium-addons-for-elementor' ) . '</p>';

                    $message .= '<p>' . sprintf( '<a href="%s" class="button-primary">%s</a>', $activation_url, __( 'Activate Now', 'premium-addons-for-elementor' ) ) . '</p>';

                }
            }
            $this->render_admin_notices( $message );
        }
    }
    
    /**
     * Gets admin review notice HTML
     * 
     * @since 2.8.4
     * @return void
     * 
     */
    public function get_review_text( $review_url, $optout_url ) {
        
        $notice = sprintf(
            '<p>' . __('Can we take only 2 minutes of your time? We\'d really appreciate it if you give ','premium-addons-for-elementor') . 
            '<b>'  . __('Premium Addons for Elementor','premium-addons-for-elementor') . '</b> a 5 Stars Rating on WordPress.org. By speading the love, we can create even greater free stuff in the future!</p>
            <div>
                <a class="button button-primary" href="%s" target="_blank"><span>' . __('Leave a Review','premium-addons-for-elementor') . '</span></a>
                <a class="button" href="%2$s"><span>' . __('I Already Did','premium-addons-for-elementor') . '</span></a>
                <a class="button button-secondary pa-notice-reset"><span>' . __('Maybe Later','premium-addons-for-elementor') . '</span></a>
            </div>',
        $review_url, $optout_url );
        
        return $notice;
    }
        
    /**
     * Checks if review admin notice is dismissed
     * 
     * @since 2.6.8
     * @return void
     * 
     */
    public function get_review_notice() {

        $review_url = 'https://wordpress.org/support/plugin/premium-addons-for-elementor/reviews/?filter=5';

        $optout_url = wp_nonce_url( add_query_arg( 'pa_review', 'opt_out' ), 'opt_out' );
        ?>

        <div class="error pa-notice-wrap pa-review-notice" data-notice="pa-review">
            <div class="pa-img-wrap">
                <img src="<?php echo PREMIUM_ADDONS_URL .'admin/images/pa-logo-symbol.png'; ?>">
            </div>
            <div class="pa-text-wrap">
                <?php echo $this->get_review_text( $review_url, $optout_url ); ?>
            </div>
            <div class="pa-notice-close">
                <a href="<?php echo esc_url( $optout_url ); ?>"><span class="dashicons dashicons-dismiss"></span></a>
            </div>
        </div>
            
    <?php
        
    }
    
    
    /**
     * 
     * Shows admin notice for Premium Lottie Animations.
     * 
     * @since 3.11.7
     * @access public
     * 
     * @return void
     */
    public function get_major_update_notice() {

        $update_notice = get_option( 'major_update_notice' );
        
        if( '1' === $update_notice )
            return;
            
        $notice_url = Helper_Functions::get_campaign_link( 'https://premiumaddons.com/huge-update-that-you-will-love-premium-addons-4-0-papro-v-2-2/', 'pa4update-notification', 'wp-dash', 'pa4-update' ); 
        
        ?>

        <div class="error pa-notice-wrap pa-new-feature-notice">
            <div class="pa-img-wrap">
                <img src="<?php echo PREMIUM_ADDONS_URL .'admin/images/pa-logo-symbol.png'; ?>">
            </div>
            <div class="pa-text-wrap">
                <p>
                    <?php echo __('Huge Update for', 'premium-addons-for-elementor'); ?>
                    <strong><?php echo __('Premium Addons Free and PRO', 'premium-addons-for-elemetor'); ?></strong>
                    <?php echo sprintf(__('Plugins. Click <a href="%s" target="_blank">Here</a> for Details.','premium-addons-for-elementor'), $notice_url ); ?>
                </p>
                <?php
                    if( defined('PREMIUM_PRO_ADDONS_VERSION') ) { 
                        if( version_compare( PREMIUM_PRO_ADDONS_VERSION, '2.2.0', '<' ) ) {
                            $download_link = PAPRO_STORE_URL . '/my-account';
                        ?>
                        <p>
                            <b>IMPORTANT:</b> 
                            <span>If you’re not getting the update notification for Premium Addons PRO v2.2.0 in your WP Dashboard -> Plugins tab, you can download it from your account settings <a href="<?php echo esc_url( $download_link ); ?>" target="_blank">page</a> and upload it manually on your website. For more clarification, please check this doc<a href="https://premiumaddons.com/docs/how-to-update-premium-addons-pro-manually/" target="_blank">article</a>.</span>
                        </p>
                        <?php
                        }
                    
                    }
                ?>
            </div>
            <div class="pa-notice-close" data-notice="major-update">
                <span class="dashicons dashicons-dismiss"></span>
            </div>
        </div>

        <?php        
    }
    
    /**
     * Checks user credentials for specific action
     * 
     * @since 2.6.8
     * 
     * @return boolean
     */
    public static function check_user_can( $action ) {
        return current_user_can( $action );
    }
    
    /**
     * Renders an admin notice error message
     * 
     * @since 1.0.0
     * @access private
     * 
     * @return void
     */
    private function render_admin_notices( $message, $class = '', $handle = '' ) {
        ?>
            <div class="error pa-new-feature-notice <?php echo $class; ?>" data-notice="<?php echo $handle; ?>">
                <?php echo $message; ?>
            </div>
        <?php
    }
    
    /*
     * Register admin scripts
     * 
     * @since 3.2.8
     * @access public
     * 
     */
    public function admin_enqueue_scripts() {
        
        wp_enqueue_script(
            'pa-notice',
            PREMIUM_ADDONS_URL . 'admin/assets/js/pa-notice.js',
            array( 'jquery' ),
            PREMIUM_ADDONS_VERSION,
            true
        );
        
    }
    
    /**
     * Set transient for admin notice
     * 
     * @since 3.2.8
     * @access public
     * 
     * @return void
     */
    public function reset_admin_notice() {
        
        $key = isset( $_POST['notice'] ) ? $_POST['notice'] : '';
        
        if ( ! empty( $key ) ) {
            
            $cache_key = 'premium_notice_' . PREMIUM_ADDONS_VERSION;
        
            set_transient( $cache_key, true, WEEK_IN_SECONDS );
            
            wp_send_json_success();
            
        } else {
            
            wp_send_json_error();
            
        }
        
    }
    
    /**
     * Dismiss admin notice
     * 
     * @since 3.11.7
     * @access public
     * 
     * @return void
     */
    public function dismiss_admin_notice() {
        
        $key = isset( $_POST['notice'] ) ? $_POST['notice'] : '';
        
        if ( ! empty( $key ) ) {
            
            update_option( $key, '1' );
            
            wp_send_json_success();
            
        } else {
            
            wp_send_json_error();
            
        }
        
    }
    
    /**
     * Creates and returns an instance of the class
     * 
     * @since 2.8.4
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