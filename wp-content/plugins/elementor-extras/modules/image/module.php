<?php
namespace ElementorExtras\Modules\Image;

// Elementor Extras Classes
use ElementorExtras\Base\Module_Base;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * \Modules\Image\Module
 *
 * @since  1.6.0
 */
class Module extends Module_Base {

	/**
	 * Get Name
	 * 
	 * Get the name of the module
	 *
	 * @since  1.6.0
	 * @return string
	 */
	public function get_name() {
		return 'image';
	}

	/**
	 * Get Widgets
	 * 
	 * Get the modules' widgets
	 *
	 * @since  1.6.0
	 * @return array
	 */
	public function get_widgets() {
		return [
			'Random_Image',
			'Image_Comparison',
		];
	}

	/**
	 * Get Image Caption
	 * 
	 * Get the attachment caption
	 *
	 * @since  2.1.0
	 * @return string
	 */
	public static function get_image_caption( $attachment, $type = 'caption' ) {

		if ( empty( $type ) ) {
			return '';
		}

		if ( ! is_a( $attachment, 'WP_Post' ) ) {
			if ( is_numeric( $attachment ) ) {
				$attachment = get_post( $attachment );

				if ( ! $attachment ) return '';
			}
		}

		if ( 'caption' === $type ) {
			return $attachment->post_excerpt;
		}

		if ( 'title' === $type ) {
			return $attachment->post_title;
		}

		return $attachment->post_content;
	}
}
