<?php
namespace ElementorExtras\Modules\Posts;

// Elementor Extras Classes
use ElementorExtras\Base\Module_Base;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * \Modules\Posts\Module
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
		return 'posts';
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
			'Posts',
			'Timeline',
		];
	}

	/**
	 * Get Content Parts
	 * 
	 * Get the content parts
	 *
	 * @since  1.6.0
	 * @return array
	 */
	public static function get_content_parts() {
		$content_parts = [
			'terms',
			'title',
			'avatar',
			'author',
			'date',
			'comments',
			'excerpt',
			'button',
		];

		if ( is_woocommerce_active() ) {
			$content_parts[] = 'price';
		}

		return $content_parts;
	}

	/**
	 * Get Post Parts
	 * 
	 * Get the post parts
	 *
	 * @since  1.6.0
	 * @return array
	 */
	public static function get_post_parts() {
		$post_parts = [
			'terms',
			'title',	
			'excerpt',
			'button',
			'metas',
		];

		return $post_parts;
	}

	/**
	 * Get Content Parts
	 * 
	 * Get the post content parts
	 *
	 * @since  1.6.0
	 * @return array
	 */
	public static function get_content_post_parts() {
		$post_parts = [
			'terms',
			'title',	
			'excerpt',
			'button',
		];

		return $post_parts;
	}

	/**
	 * Get Meta Parts
	 * 
	 * Get the available metas
	 *
	 * @since  1.6.0
	 * @return array
	 */
	public static function get_meta_parts() {
		$meta_parts = [
			'author',
			'date',
			'comments',
		];

		if ( is_woocommerce_active() ) {
			$meta_parts[] = 'price';
		}

		return $meta_parts;
	}
}
