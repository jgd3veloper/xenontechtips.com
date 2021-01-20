<?php
/**
 * Development Sandbox functions and definitions
 * 
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Development_Sandbox
 */

if ( ! function_exists( 'development_sandbox_setup' ) ) :
	/**
	 * Sets up theme defaults and registers support for various WordPress features.
	 *
	 * Note that this function is hooked into the after_setup_theme hook, which
	 * runs before the init hook. The init hook is too late for some features, such
	 * as indicating support for post thumbnails.
	 */
	function development_sandbox_setup() {
		/*
		 * Make theme available for translation.
		 * Translations can be filed in the /languages/ directory.
		 * If you're building a theme based on Development Sandbox, use a find and replace
		 * to change 'development-sandbox' to the name of your theme in all the template files.
		 */
		load_theme_textdomain( 'development-sandbox', get_template_directory() . '/languages' );

		// Add default posts and comments RSS feed links to head.
		add_theme_support( 'automatic-feed-links' );

		/*
		 * Let WordPress manage the document title.
		 * By adding theme support, we declare that this theme does not use a
		 * hard-coded <title> tag in the document head, and expect WordPress to
		 * provide it for us.
		 */
		add_theme_support( 'title-tag' );

		/*
		 * Enable support for Post Thumbnails on posts and pages.
		 *
		 * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		 */
		add_theme_support( 'post-thumbnails' );

		// This theme uses wp_nav_menu() in one location.
		register_nav_menus( array(
			'menu-1' => esc_html__( 'Primary', 'development-sandbox' ),
		) );

		/*
		 * Switch default core markup for search form, comment form, and comments
		 * to output valid HTML5.
		 */
		add_theme_support( 'html5', array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
		) );

		// Set up the WordPress core custom background feature.
		add_theme_support( 'custom-background', apply_filters( 'development_sandbox_custom_background_args', array(
			'default-color' => 'ffffff',
			'default-image' => '',
		) ) );

		// Add theme support for selective refresh for widgets.
		add_theme_support( 'customize-selective-refresh-widgets' );

		/**
		 * Add support for core custom logo.
		 *
		 * @link https://codex.wordpress.org/Theme_Logo
		 */
		add_theme_support( 'custom-logo', array(
			'height'      => 250,
			'width'       => 250,
			'flex-width'  => true,
			'flex-height' => true,
		) );
	}
endif;
add_action( 'after_setup_theme', 'development_sandbox_setup' );

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function development_sandbox_content_width() {
	// This variable is intended to be overruled from themes.
	// Open WPCS issue: {@link https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards/issues/1043}.
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
	$GLOBALS['content_width'] = apply_filters( 'development_sandbox_content_width', 640 );
}
add_action( 'after_setup_theme', 'development_sandbox_content_width', 0 );

/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */
function development_sandbox_widgets_init() {
	register_sidebar( array(
		'name'          => esc_html__( 'Sidebar', 'development-sandbox' ),
		'id'            => 'sidebar-1',
		'description'   => esc_html__( 'Add widgets here.', 'development-sandbox' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>',
	) );
}
add_action( 'widgets_init', 'development_sandbox_widgets_init' );

/**
 * Enqueue scripts and styles.
 */
function development_sandbox_scripts() {
	wp_enqueue_style( 'development-sandbox-style', get_stylesheet_uri() );

	wp_enqueue_script( 'development-sandbox-navigation', get_template_directory_uri() . '/js/navigation.js', array(), '20151215', true );

	wp_enqueue_script( 'development-sandbox-skip-link-focus-fix', get_template_directory_uri() . '/js/skip-link-focus-fix.js', array(), '20151215', true );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'development_sandbox_scripts' );

/**
 * Implement the Custom Header feature.
 */
require get_template_directory() . '/inc/custom-header.php';

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Functions which enhance the theme by hooking into WordPress.
 */
require get_template_directory() . '/inc/template-functions.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';

/**
 * Load Jetpack compatibility file.
 */
if ( defined( 'JETPACK__VERSION' ) ) {
	require get_template_directory() . '/inc/jetpack.php';
}

/**
 * Custom PHP Functions - START
 */
/**
 * Change Howdy.
 */
add_filter('gettext', 'change_howdy', 10, 3);

function change_howdy($translated, $text, $domain) {

    if (!is_admin() || 'default' != $domain)
        return $translated;

    if (false !== strpos($translated, 'Howdy'))
        return str_replace('Howdy', 'Welcome', $translated);

    return $translated;
}

/**
 * Change Footer Link - Back-End
 */
// Custom Admin footer
function wpexplorer_remove_footer_admin () {
	echo '<span id="footer-thankyou">Built with love by <a href="https://xenontechtips.com/" target="_blank">Xenon</a>.</span>';
}
add_filter( 'admin_footer_text', 'wpexplorer_remove_footer_admin' ); 
 
/**
 * Remove Side Menu Links
 */
/* function wpexplorer_remove_menus() {
	remove_menu_page( 'themes.php' );          // Appearance
	remove_menu_page( 'plugins.php' );         // Plugins
	remove_menu_page( 'users.php' );           // Users
	remove_menu_page( 'tools.php' );           // Tools
	remove_menu_page( 'options-general.php' ); // Settings
} */

/**
 * Add a widget to the dashboard.
 *
 * This function is hooked into the 'wp_dashboard_setup' action below.
 */
/**
 * Custom Dashboard Widget.
 */
add_action( 'wp_dashboard_setup', 'register_my_dashboard_widget' );
function register_my_dashboard_widget() {
wp_add_dashboard_widget(
'my_dashboard_widget',
'Quick Links',
'my_dashboard_widget_display'
);

}
/**
 * Create the function to output the contents of your Dashboard Widget.
 */
function my_dashboard_widget_display() {
echo '<iframe src="https://xenontechtips.com/" width="100%" height="600"></iframe><hr style="border-top: 1px dashed #F2F2F2;"/>';
    echo '&#187; <a href="edit.php?post_type=page">View All Pages</a><hr style="border-top: 1px dashed #F2F2F2;"/>';
echo '&#187; <a href="upload.php">View Media Library</a><hr style="border-top: 1px dashed #F2F2F2;"/>';
echo '<a class="button button-primary button-hero" href="https://josephgiancola.com">Send Us a Message</a>';
}
/**
 * Custom PHP Functions - END
 */

//Exclude pages from WordPress Search
if (!is_admin()) {
function wpb_search_filter($query) {
if ($query->is_search) {
$query->set('post_type', 'post');
}
return $query;
}
add_filter('pre_get_posts','wpb_search_filter');
}