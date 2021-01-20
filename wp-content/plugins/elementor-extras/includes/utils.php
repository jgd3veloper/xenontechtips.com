<?php
namespace ElementorExtras;

// Elementor Classes
use Elementor\Widget_Button;

// Elementor Pro Classes
use ElementorPro\Plugin;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Utils {

	/**
	 * Fetches available post types
	 *
	 * @since 2.0.0
	 */
	public static function get_public_post_types_options( $singular = false, $any = false, $args = [] ) {
		$post_type_args = [
			'show_in_nav_menus' => true,
		];

		if ( $any ) $post_types['any'] = __( 'Any', 'elementor-extras' );

		if ( ! function_exists( 'get_post_types' ) )
			return $post_types;

		$_post_types = get_post_types( $post_type_args, 'objects' );

		foreach ( $_post_types as $post_type => $object ) {
			$post_types[ $post_type ] = $singular ? $object->labels->singular_name : $object->label;
		}

		return $post_types;
	}

	/**
	 * Get Taxonomies Options
	 *
	 * Fetches available taxonomies
	 *
	 * @since 2.0.0
	 */
	public static function get_taxonomies_options() {

		$options = [];

		$taxonomies = get_taxonomies( array(
			'show_in_nav_menus' => true
		), 'objects' );

		if ( empty( $taxonomies ) ) {
			$options[ '' ] = __( 'No taxonomies found', 'elementor-extras' );
			return $options;
		}

		foreach ( $taxonomies as $taxonomy ) {
			$options[ $taxonomy->name ] = $taxonomy->label;
		}

		return $options;
	}

	/**
	 * Get Taxonomies Labels
	 *
	 * Fetches labels for given taxonomy
	 *
	 * @since 2.1.0
	 */
	public static function get_taxonomy_labels( $taxonomy = '' ) {

		if ( ! $taxonomy || '' === $taxonomy )
			return false;

		$labels = false;
		$taxonomy_object = get_taxonomy( $taxonomy );

		if ( $taxonomy_object && is_object( $taxonomy_object ) ) {
			$labels = $taxonomy_object->labels;
		}

		return $labels;
	}

	/**
	 * Get Terms Options
	 * 
	 * Retrieve the terms options array for a control
	 *
	 * @since  1.6.0
	 * @param  taxonomy  	The taxonomy for the terms
	 * @param  key|string 	The key to use when building the options. Can be 'slug' or 'id'
	 * @param  all|bool  	The string to use for the first option. Can be false to disable. Default: true
	 * @return array
	 */
	public static function get_terms_options( $taxonomy, $key = 'slug', $all = true ) {

		if ( false !== $all ) {
			$all = ( true === $all ) ? __( 'All', 'elementor-extras' ) : $all;
			$options = [ '' => $all ];
		}

		$terms = get_terms( array(
			'taxonomy' => $taxonomy
		));

		if ( empty( $terms ) ) {
			$options[ '' ] = sprintf( __( 'No terms found', 'elementor-extras' ), $taxonomy );
			return $options;
		}

		foreach ( $terms as $term ) {
			$term_key = ( 'id' === $key ) ? $term->term_id : $term->slug;
			$options[ $term_key ] = $term->name;
		}

		return $options;
	}

	/**
	 * Get Terms
	 *
	 * Retrieve a list of terms for specific taxonomies
	 *
	 * @since  1.6.0
	 * @return array
	 */
	public static function get_terms( $taxonomies = [] ) {
		$_terms = [];

		if ( empty( $taxonomies ) )
			return false;

		if ( is_array( $taxonomies ) ) {
			foreach( $taxonomies as $taxonomy ) {
				$terms = get_the_terms( get_the_ID(), $taxonomy );

				if ( empty( $terms ) )
					continue;

				foreach( $terms as $term ) { $_terms[] = $term; }
			}
		} else {
			$_terms = get_the_terms( get_the_ID(), $taxonomies );
		}

		if ( empty( $_terms ) || 0 === count( $_terms ) )
			return false;

		return $_terms;

	}

	/**
	 * Fetches available pages
	 *
	 * @since 2.0.0
	 */
	public static function get_pages_options() {

		$options = [];

		$pages = get_pages( array(
			'hierarchical' => false,
		) );

		if ( empty( $pages ) ) {
			$options[ '' ] = __( 'No pages found', 'elementor-extras' );
			return $options;
		}

		foreach ( $pages as $page ) {
			$options[ $page->ID ] = $page->post_title;
		}

		return $options;
	}

	/**
	 * Fetches available users
	 *
	 * @since 2.0.0
	 */
	public static function get_users_options() {

		$options = [];

		$users = get_users( array(
			'fields' => [ 'ID', 'display_name' ],
		) );

		if ( empty( $users ) ) {
			$options[ '' ] = __( 'No users found', 'elementor-extras' );
			return $options;
		}

		foreach ( $users as $user ) {
			$options[ $user->ID ] = $user->display_name;
		}

		return $options;
	}

	/**
	 * Get category with highest number of parents
	 * from a given list
	 *
	 * @since 2.0.0
	 */
	public static function get_most_parents_category( $categories = [] ) {

		$counted_cats = [];

		if ( ! is_array( $categories ) )
			return $categories;

		foreach ( $categories as $category ) {
			$category_parents = get_category_parents( $category->term_id, false, ',' );
			$category_parents = explode( ',', $category_parents );
			$counted_cats[ $category->term_id ] = count( $category_parents );
		}

		arsort( $counted_cats );
		reset( $counted_cats );

		return key( $counted_cats );
	}

	/**
	 * Get list of terms for a specific post ID
	 * from a taxonomy with highter number of terms used
	 *
	 * @since 2.0.0
	 */
	public static function get_parent_terms_highest( $post_id ) {

		$taxonomies = get_post_taxonomies( $post_id );
		$tax 		= $taxonomies[0];
		$tax_term_c = 0;

		foreach ( $taxonomies as $taxonomy => $name ) {
			$taxonomy_terms = wp_get_post_terms( $post_id, $name );

			if ( count( $taxonomy_terms ) > $tax_term_c ) {
				$tax_term_c = count( $taxonomy_terms );
				$tax 		= $name;
			}
		}

		$terms = wp_get_post_terms( $post_id, $tax );

		return $terms;
	}

	/**
	 * Retrieve tooltip selectors for background color
	 * from a given list
	 *
	 * @since 2.0.0
	 */
	public static function get_tooltip_background_selectors( $prefix = '.ee-tooltip.ee-tooltip-{{ID}}' ) {

		return [
			$prefix => 'background-color: {{VALUE}};',
			$prefix . '.to--top.at--center:after,' .
			$prefix . '.to--left.at--top:after,' .
			$prefix . '.to--right.at--top:after' => 'border-top-color: {{VALUE}};',

			$prefix . '.to--left.at--center:after,' .
			$prefix . '.to--top.at--left:after,' .
			$prefix . '.to--bottom.at--left:after' => 'border-left-color: {{VALUE}};',

			$prefix . '.to--right.at--center:after,' .
			$prefix . '.to--bottom.at--right:after,' .
			$prefix . '.to--top.at--right:after' => 'border-right-color: {{VALUE}};',

			$prefix . '.to--bottom.at--center:after,' .
			$prefix . '.to--right.at--bottom:after,' .
			$prefix . '.to--left.at--bottom:after' => 'border-bottom-color: {{VALUE}};',
		];
	}

	/**
	 * Retrieve tooltip selectors for background color
	 * from a given list
	 *
	 * @since 2.1.0
	 */
	public static function get_placeholder_selectors( $prefix = '.ee-tooltip.ee-tooltip-{{ID}}', $properties = null ) {

		if ( ! $properties )
			return
				$prefix . '::-webkit-input-placeholder, ' .
				$prefix . ':-moz-placeholder, ' .
				$prefix . '::-moz-placeholder, ' .
				$prefix . ':-ms-input-placeholder, ' .
				$prefix . '::placeholder';

		return [
			$prefix . '::-webkit-input-placeholder' => $properties,
			$prefix . ':-moz-placeholder' 			=> $properties,
			$prefix . '::-moz-placeholder' 			=> $properties,
			$prefix . ':-ms-input-placeholder' 		=> $properties,
			$prefix . '::placeholder' 				=> $properties,
		];
	}

	/**
	 * Get Button Sizes
	 * 
	 * Get the button sizes from the Elementor default Button widget
	 *
	 * @since  2.1.0
	 * @return array
	 */
	public static function get_button_sizes() {
		return Widget_Button::get_button_sizes();
	}

	/**
	 * Elementor
	 * 
	 * Retrieves the elementor plugin instance
	 *
	 * @since  2.1.0
	 * @return \Elementor\Plugin|$instace
	 */
	public static function elementor() {
		return \Elementor\Plugin::$instance;
	}
}
