<?php
/**
 * Plugin Name: Syntax Highlighter for Elementor
 * Description: An Elementor Syntax Highlighter Widget built with PrismJS
 * Plugin URI:  https://www.soliddigital.com/
 * Version:     1.0.2
 * Author:      Peter Ajtai, Solid Digital
 * Author URI:  https://www.soliddigital.com//
 * Text Domain: solid-syntax-higlighter
 */

/**
https://prismjs.com/download.html#themes=prism-okaidia&languages=markup+css+clike+javascript+bash+c+csharp+cpp+fsharp+git+go+graphql+haskell+http+java+javadoc+javadoclike+jsdoc+js-extras+js-templates+json+latex+lisp+makefile+markdown+markup-templating+matlab+nginx+objectivec+perl+php+phpdoc+php-extras+pug+python+r+jsx+tsx+regex+sass+scss+scala+shell-session+sql+twig+typescript
 */
namespace Solid_Syntax;
use Elementor\Plugin;

define('SYNTAX_VERSION', '1.0.0');

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// The Widget_Base class is not available immediately after plugins are loaded, so
// we delay the class' use until Elementor widgets are registered
add_action( 'elementor/widgets/widgets_registered', function() {
	require_once('widget.php');

	$drop_down_widget =	new Highlighter_Widget();

	// Let Elementor know about our widget
	Plugin::instance()->widgets_manager->register_widget_type( $drop_down_widget );
});

add_action( 'elementor/frontend/after_register_styles', function() {
	wp_enqueue_style(
		'solid-syntax-style',
		plugins_url( 'assets/prism2.css', __FILE__ ),
		[],
		SYNTAX_VERSION
	);
});
