<?php
namespace ElementorPro\Core\Connect\Apps;

use Elementor\Core\Common\Modules\Connect\Apps\Common_App;
use ElementorPro\License;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Activate extends Common_App {
	public function get_title() {
		return __( 'Activate', 'elementor-pro' );
	}

	public function get_slug() {
		return 'activate';
	}

	protected function after_connect() {
		$this->action_activate_license();
	}

	public function render_admin_widget() {
		parent::render_admin_widget();

		$license = License\Admin::get_license_key();

		$status = $license ? 'Exist' : 'Missing';

		echo sprintf( '<p>License Key: <strong>%s</strong></p>', $status );
	}

	/**
	 * @since 2.3.0
	 * @access public
	 */
	public function action_authorize() {
		// In case the first connect was not from Activate App - require a new authorization.
		if ( $this->is_connected() && ! License\Admin::get_license_key() ) {
			$this->disconnect();
		}

		parent::action_authorize();
	}

	public function action_activate_pro() {
		$this->action_activate_license();
	}

	public function action_switch_license() {
		$this->disconnect();
		$this->action_authorize();
	}

	public function action_deactivate() {
		License\Admin::deactivate();
		$this->disconnect();
		wp_safe_redirect( License\Admin::get_url() );
		die;
	}

	public function action_activate_license() {
		

		$license = $this->request( 'get_connected_license' );
	
		$license_key = 'gplready';
		$data = License\API::activate_license( $license_key );
		License\Admin::set_license_key( $license_key );
		License\API::set_license_data( $data );
		$this->request( 'set_site_owner' );
		$this->add_notice( __( 'License has been activated successfully.', 'elementor-pro' ) );

		$this->redirect_to_admin_page( License\Admin::get_url() );
		die;
	}
}
