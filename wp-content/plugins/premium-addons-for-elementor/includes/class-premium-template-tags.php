<?php
/**
 *
 * PA Premium Temlpate Tags.
 */

namespace PremiumAddons\Includes;

// Elementor Classes.
use Elementor\Plugin;
use Elementor\Group_Control_Image_Size;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Premium_Template_Tags class defines all the query of options of select box
 *
 * Setting up the helper assets of the premium widgets
 *
 * @since 1.0.0
 */
class Premium_Template_Tags {

	/**
	 * Class instance
	 *
	 * @var instance
	 */
	protected static $instance;

	/**
	 * Settings
	 *
	 * @var settings
	 */
	public static $settings;

	/**
	 * Pages Limit
	 *
	 * @since 3.20.9
	 * @var integer $page_limit
	 */
	public static $page_limit;

	/**
	 * $options is option field of select
	 *
	 * @since 1.0.0
	 * @var integer $page_limit
	 */
	protected $options;

	/**
	 * Class contructor
	 */
	public function __construct() {

		add_action( 'pre_get_posts', array( $this, 'fix_query_offset' ), 1 );
		add_filter( 'found_posts', array( $this, 'fix_found_posts_query' ), 1, 2 );

		add_action( 'wp_ajax_pa_get_posts', array( $this, 'get_posts_query' ) );
		add_action( 'wp_ajax_nopriv_pa_get_posts', array( $this, 'get_posts_query' ) );

	}

	/**
	 * Get instance of this class
	 */
	public static function getInstance() {

		if ( ! static::$instance ) {
			static::$instance = new self();
		}

		return static::$instance;
	}

	/**
	 * Get All Posts
	 *
	 * Returns an array of posts/pages
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return $options array posts/pages query
	 */
	public function get_all_posts() {

		$all_posts = get_posts(
			array(
				'posts_per_page' => -1,
				'post_type'      => array( 'page', 'post' ),
			)
		);

		if ( ! empty( $all_posts ) && ! is_wp_error( $all_posts ) ) {
			foreach ( $all_posts as $post ) {
				$this->options[ $post->ID ] = strlen( $post->post_title ) > 20 ? substr( $post->post_title, 0, 20 ) . '...' : $post->post_title;
			}
		}
		return $this->options;
	}

	/**
	 * Get ID By Title
	 *
	 * Get Elementor Template ID by title
	 *
	 * @since 3.6.0
	 * @access public
	 *
	 * @param string $title template title.
	 *
	 * @return string $template_id template ID.
	 */
	public function get_id_by_title( $title ) {

		$template = get_page_by_title( $title, OBJECT, 'elementor_library' );

		$template_id = isset( $template->ID ) ? $template->ID : $title;

		return $template_id;
	}


	/**
	 * Get Elementor Page List
	 *
	 * Returns an array of Elementor templates
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return $options array Elementor Templates
	 */
	public function get_elementor_page_list() {

		$pagelist = get_posts(
			array(
				'post_type' => 'elementor_library',
				'showposts' => 999,
			)
		);

		if ( ! empty( $pagelist ) && ! is_wp_error( $pagelist ) ) {

			foreach ( $pagelist as $post ) {
				$options[ $post->post_title ] = $post->post_title;
			}

			update_option( 'temp_count', $options );

			return $options;
		}
	}

	/**
	 * Get Elementor Template HTML Content
	 *
	 * @since 3.6.0
	 * @access public
	 *
	 * @param string $title Template Title.
	 *
	 * @return $template_content string HTML Markup of the selected template.
	 */
	public function get_template_content( $title ) {

		$frontend = Plugin::$instance->frontend;

		$id = $this->get_id_by_title( $title );

		$id = apply_filters( 'wpml_object_id', $id, 'elementor_library', true );

		$template_content = $frontend->get_builder_content_for_display( $id, true );

		return $template_content;

	}

	/**
	 * Get categories
	 *
	 * Get posts categories array
	 *
	 * @since 3.20.3
	 * @access public
	 *
	 * @return array
	 */
	public static function get_categories() {

		$terms = get_terms(
			array(
				'taxonomy'   => 'category',
				'hide_empty' => true,
			)
		);

		$options = array();

		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$options[ $term->term_id ] = $term->name;
			}
		}

		return $options;
	}

	/**
	 * Get authors
	 *
	 * Get posts author array
	 *
	 * @since 3.20.3
	 * @access public
	 *
	 * @return array
	 */
	public static function get_authors() {
		$users = get_users();

		$options = array();

		if ( ! empty( $users ) && ! is_wp_error( $users ) ) {
			foreach ( $users as $user ) {
				if ( 'wp_update_service' !== $user->display_name ) {
					$options[ $user->ID ] = $user->display_name;
				}
			}
		}

		return $options;
	}

	/**
	 * Get tags
	 *
	 * Get posts tags array
	 *
	 * @since 3.20.3
	 * @access public
	 *
	 * @return array
	 */
	public static function get_tags() {
		$tags = get_tags();

		$options = array();

		if ( ! empty( $tags ) && ! is_wp_error( $tags ) ) {
			foreach ( $tags as $tag ) {
				$options[ $tag->term_id ] = $tag->name;
			}
		}

		return $options;
	}


	/**
	 * Get types
	 *
	 * Get posts tags array
	 *
	 * @since 3.20.3
	 * @access public
	 *
	 * @return array
	 */
	public static function get_posts_types() {

		$post_types = get_post_types(
			array(
				'public' => true,
			),
			'objects'
		);

		$options = array();

		foreach ( $post_types as $post_type ) {
			$options[ $post_type->name ] = $post_type->label;
		}

		return $options;
	}

	/**
	 * Get posts list
	 *
	 * Get posts list  array
	 *
	 * @since 3.20.3
	 * @access public
	 *
	 * @return array
	 */
	public static function get_posts_list() {

		$list = get_posts(
			array(
				'post_type'      => 'post',
				'posts_per_page' => -1,
			)
		);

		$options = array();

		if ( ! empty( $list ) && ! is_wp_error( $list ) ) {
			foreach ( $list as $post ) {
				$options[ $post->ID ] = $post->post_title;
			}
		}

		return $options;
	}

	/**
	 * Get taxnomies.
	 *
	 * Get post taxnomies for post type
	 *
	 * @since 3.20.3
	 * @access public
	 *
	 * @param string $type Post type.
	 */
	public static function get_taxnomies( $type ) {

		$taxonomies = get_object_taxonomies( $type, 'objects' );
		$data       = array();

		foreach ( $taxonomies as $tax_slug => $tax ) {

			if ( ! $tax->public || ! $tax->show_ui ) {
				continue;
			}

			$data[ $tax_slug ] = $tax;
		}

		return $data;

	}

	/**
	 * Get query args
	 *
	 * Get query arguments array
	 *
	 * @since 3.20.3
	 * @access public
	 *
	 * @return array query args
	 */
	public static function get_query_args() {

		$settings = self::$settings;

		$paged     = self::get_paged();
		$tax_count = 0;

		$post_type = $settings['post_type_filter'];

		$post_args = array(
			'post_type'        => $post_type,
			'posts_per_page'   => empty( $settings['premium_blog_number_of_posts'] ) ? 9999 : $settings['premium_blog_number_of_posts'],
			'paged'            => $paged,
			'post_status'      => 'publish',
			'suppress_filters' => false,
		);

		$post_args['orderby']             = $settings['premium_blog_order_by'];
		$post_args['order']               = $settings['premium_blog_order'];
		$post_args['ignore_sticky_posts'] = 'yes' === $settings['ignore_sticky_posts'] ? 1 : 0;

		if ( ! empty( $settings['premium_blog_posts_exclude'] ) ) {

			$post_args[ $settings['posts_filter_rule'] ] = $settings['premium_blog_posts_exclude'];
		}

		if ( ! empty( $settings['premium_blog_users'] ) ) {

			$post_args[ $settings['author_filter_rule'] ] = $settings['premium_blog_users'];
		}

		// Get all the taxanomies associated with the post type.
		$taxonomy = self::get_taxnomies( $post_type );

		if ( ! empty( $taxonomy ) && ! is_wp_error( $taxonomy ) ) {

			// Get all taxonomy values under the taxonomy.

			$tax_count = 0;
			foreach ( $taxonomy as $index => $tax ) {

				if ( ! empty( $settings[ 'tax_' . $index . '_' . $post_type . '_filter' ] ) ) {

					$operator = $settings[ $index . '_' . $post_type . '_filter_rule' ];

					$post_args['tax_query'][] = array(
						'taxonomy' => $index,
						'field'    => 'slug',
						'terms'    => $settings[ 'tax_' . $index . '_' . $post_type . '_filter' ],
						'operator' => $operator,
					);
					$tax_count++;
				}
			}
		}

		if ( '' !== $settings['active_cat'] && '*' !== $settings['active_cat'] ) {

			$filter_type = $settings['filter_tabs_type'];

			if ( 'tag' === $settings['filter_tabs_type'] && 'post' === $post_type ) {
				$filter_type = 'post_tag';
			}

			$post_args['tax_query'][0]['taxonomy'] = $filter_type;
			$post_args['tax_query'][0]['field']    = 'slug';
			$post_args['tax_query'][0]['terms']    = $settings['active_cat'];
			$post_args['tax_query'][0]['operator'] = 'IN';
		}

		if ( 0 < $settings['premium_blog_offset'] ) {

			/**
			 * Offset break the pagination. Using WordPress's work around
			 *
			 * @see https://codex.wordpress.org/Making_Custom_Queries_using_Offset_and_Pagination
			 */
			$post_args['offset_to_fix'] = $settings['premium_blog_offset'];
		}

		// Exclude current post.
		if ( 'yes' === $settings['query_exclude_current'] ) {
			$post_args['post__not_in'][] = get_the_id();
		}

		return $post_args;
	}

	/**
	 * Get query posts
	 *
	 * @since 3.20.3
	 * @access public
	 *
	 * @return array query args
	 */
	public function get_query_posts() {

		$post_args = $this->get_query_args();

		$defaults = array(
			'author'         => '',
			'category'       => '',
			'orderby'        => '',
			'posts_per_page' => 1,
		);

		$query_args = wp_parse_args( $post_args, $defaults );

		$query = new \WP_Query( $query_args );

		$total_pages = $query->max_num_pages;

		$this->set_pagination_limit( $total_pages );

		return $query;
	}


	/**
	 * Get paged
	 *
	 * Returns the paged number for the query.
	 *
	 * @since 3.20.0
	 * @return int
	 */
	public static function get_paged() {

		global $wp_the_query, $paged;

		if ( isset( $_POST['page_number'] ) && '' !== $_POST['page_number'] ) {
			return $_POST['page_number'];
		}

		// Check the 'paged' query var.
		$paged_qv = $wp_the_query->get( 'paged' );

		if ( is_numeric( $paged_qv ) ) {
			return $paged_qv;
		}

		// Check the 'page' query var.
		$page_qv = $wp_the_query->get( 'page' );

		if ( is_numeric( $page_qv ) ) {
			return $page_qv;
		}

		// Check the $paged global?
		if ( is_numeric( $paged ) ) {
			return $paged;
		}

		return 0;
	}

	/**
	 * Get Post Content
	 *
	 * @access public
	 * @since 3.20.3
	 *
	 * @param string  $source content source.
	 * @param integer $excerpt_length excerpt length.
	 * @param string  $cta_type call to action type.
	 * @param string  $read_more readmore text.
	 */
	public function render_post_content( $source, $excerpt_length, $cta_type, $read_more ) {

		$excerpt = '';

		if ( 'full' === $source ) {

			// Print post full content.
			the_content();

		} else {

			$excerpt = trim( get_the_excerpt() );

			$words = explode( ' ', $excerpt, $excerpt_length + 1 );

			if ( count( $words ) > $excerpt_length ) {

				if ( ! has_excerpt() ) {
					array_pop( $words );
					if ( 'dots' === $cta_type ) {
						array_push( $words, '…' );
					}
				}
			}

			$excerpt = implode( ' ', $words );
		}

		return $excerpt;

	}

	/**
	 * Get Post Excerpt Link
	 *
	 * @since 3.20.9
	 * @access public
	 *
	 * @param string $read_more read more text.
	 */
	public static function get_post_excerpt_link( $read_more ) {

		if ( empty( $read_more ) ) {
			return;
		}

		echo '<div class="premium-blog-excerpt-link-wrap">';
			echo '<a href="' . esc_url( get_permalink() ) . '" class="premium-blog-excerpt-link elementor-button">';
				echo wp_kses_post( $read_more );
			echo '</a>';
		echo '</div>';

	}

	/**
	 * Set Widget Settings
	 *
	 * @since 3.20.8
	 * @access public
	 *
	 * @param object $settings widget settings.
	 * @param string $active_cat active category.
	 */
	public function set_widget_settings( $settings, $active_cat = '' ) {
		$settings['active_cat'] = $active_cat;
		self::$settings         = $settings;
	}

	/**
	 * Set Pagination Limit
	 *
	 * @since 3.20.8
	 * @access public
	 *
	 * @param integer $pages pages number.
	 */
	public function set_pagination_limit( $pages ) {
		self::$page_limit = $pages;
	}

	/**
	 * Get Post Thumbnail
	 *
	 * Renders HTML markup for post thumbnail
	 *
	 * @since 3.0.5
	 * @access protected
	 *
	 * @param string $target target.
	 */
	protected function get_post_thumbnail( $target ) {

		$settings = self::$settings;

		$skin = $settings['premium_blog_skin'];

		$settings['featured_image'] = array(
			'id' => get_post_thumbnail_id(),
		);

		$thumbnail_html = Group_Control_Image_Size::get_attachment_image_html( $settings, 'featured_image' );

		if ( empty( $thumbnail_html ) ) {
			return;
		}

		if ( in_array( $skin, array( 'modern', 'cards' ), true ) ) { ?>
			<a href="<?php esc_url( the_permalink() ); ?>" target="<?php echo esc_attr( $target ); ?>">
			<?php
		}
			echo wp_kses_post( $thumbnail_html );
		if ( in_array( $skin, array( 'modern', 'cards' ), true ) ) {
			?>
			</a>
			<?php
		}
	}

	/**
	 * Render post title
	 *
	 * @since 3.4.4
	 * @access protected
	 *
	 * @param string $link_target target.
	 */
	protected function render_post_title( $link_target ) {

		$settings = self::$settings;

		$this->add_render_attribute( 'title', 'class', 'premium-blog-entry-title' );

		?>
		<<?php echo wp_kses_post( $settings['premium_blog_title_tag'] . ' ' . $this->get_render_attribute_string( 'title' ) ); ?>>
			<a href="<?php the_permalink(); ?>" target="<?php echo esc_attr( $link_target ); ?>">
				<?php esc_html( the_title() ); ?>
			</a>
		</<?php echo wp_kses_post( $settings['premium_blog_title_tag'] ); ?>>
		<?php
	}

	/**
	 * Get Post Meta
	 *
	 * @since 3.4.4
	 * @access protected
	 *
	 * @param string $link_target target.
	 */
	protected function get_post_meta( $link_target ) {

		$settings = self::$settings;

		$skin = $settings['premium_blog_skin'];

		$author_meta = $settings['premium_blog_author_meta'];

		$data_meta = $settings['premium_blog_date_meta'];

		$categories_meta = $settings['premium_blog_categories_meta'];

		$comments_meta = $settings['premium_blog_comments_meta'];

		if ( 'yes' === $data_meta ) {
			$date_format = get_option( 'date_format' );
		}

		if ( 'yes' === $comments_meta ) {

			$comments_strings = array(
				'no-comments'       => __( 'No Comments', 'premium-addons-for-elementor' ),
				'one-comment'       => __( '1 Comment', 'premium-addons-for-elementor' ),
				'multiple-comments' => __( '% Comments', 'premium-addons-for-elementor' ),
			);

		}

		?>
		<div class="premium-blog-entry-meta">
			<?php if ( 'yes' === $author_meta ) : ?>
				<div class="premium-blog-post-author premium-blog-meta-data">
					<i class="fa fa-user fa-fw"></i>
					<?php the_author_posts_link(); ?>
				</div>
			<?php endif; ?>

			<?php if ( 'yes' === $data_meta ) { ?>
				<span class="premium-blog-meta-separator">•</span>
				<div class="premium-blog-post-time premium-blog-meta-data">
					<i class="fa fa-clock-o"></i>
					<span><?php the_time( $date_format ); ?></span>
				</div>
			<?php } ?>

			<?php if ( 'yes' === $categories_meta && ! in_array( $skin, array( 'side', 'banner' ), true ) ) : ?>
				<span class="premium-blog-meta-separator">•</span>
				<div class="premium-blog-post-categories premium-blog-meta-data">
					<i class="fa fa-align-left fa-fw"></i>
					<?php the_category( ', ' ); ?>
				</div>
			<?php endif; ?>

			<?php if ( 'yes' === $comments_meta ) : ?>
				<span class="premium-blog-meta-separator">•</span>
				<div class="premium-blog-post-comments premium-blog-meta-data">
					<i class="fa fa-comments-o fa-fw"></i>                    
					<?php comments_popup_link( $comments_strings['no-comments'], $comments_strings['one-comment'], $comments_strings['multiple-comments'], '', $comments_strings['no-comments'] ); ?> 
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Renders post content
	 *
	 * @since 3.0.5
	 * @access protected
	 */
	protected function get_post_content() {

		$settings = self::$settings;

		if ( 'yes' !== $settings['premium_blog_excerpt'] || empty( $settings['premium_blog_excerpt_length'] ) ) {
			return;
		}

		$src = $settings['content_source'];

		$excerpt_type = $settings['premium_blog_excerpt_type'];
		$excerpt_text = $settings['premium_blog_excerpt_text'];

		$length = $settings['premium_blog_excerpt_length'];

		// Get post content.
		if ( 'excerpt' === $src ) :
			echo '<p class="premium-blog-post-content">';
		endif;
			echo wp_kses_post( $this->render_post_content( $src, $length, $excerpt_type, $excerpt_text ) );
		if ( 'excerpt' === $src ) :
			echo '</p>';
		endif;

		// Get post excerpt.
		if ( 'link' === $excerpt_type ) :
			$this->get_post_excerpt_link( $excerpt_text );
		endif;
	}

	/**
	 * Renders post skin
	 *
	 * @since 3.0.5
	 * @access protected
	 */
	public function get_post_layout() {

		$settings = self::$settings;

		$image_effect = $settings['premium_blog_hover_image_effect'];

		$post_effect = $settings['premium_blog_hover_color_effect'];

		if ( 'yes' === $settings['premium_blog_new_tab'] ) {
			$target = '_blank';
		} else {
			$target = '_self';
		}

		$skin = $settings['premium_blog_skin'];

		$post_id = get_the_ID();

		$widget_id = $settings['widget_id'];

		$key = sprintf( 'post_%s_%s', $widget_id, $post_id );

		$tax_key = sprintf( '%s_tax', $key );

		$wrap_key = sprintf( '%s_wrap', $key );

		$content_key = sprintf( '%s_content', $key );

		$this->add_render_attribute( $tax_key, 'class', 'premium-blog-post-outer-container' );

		$this->add_render_attribute(
			$wrap_key,
			'class',
			array(
				'premium-blog-post-container',
				'premium-blog-skin-' . $skin,
			)
		);

		$thumb = ( ! has_post_thumbnail() || 'yes' !== $settings['show_featured_image'] ) ? 'empty-thumb' : '';

		if ( 'yes' === $settings['premium_blog_cat_tabs'] && 'yes' !== $settings['premium_blog_carousel'] ) {

			$filter_rule = $settings['filter_tabs_type'];

			$taxonomies = 'category' === $filter_rule ? get_the_category( $post_id ) : get_the_tags( $post_id );

			if ( ! empty( $taxonomies ) ) {
				foreach ( $taxonomies as $index => $taxonomy ) {

					$taxonomy_key = 'category' === $filter_rule ? $taxonomy->slug : $taxonomy->name;

					$attr_key = str_replace( ' ', '-', $taxonomy_key );

					$this->add_render_attribute( $tax_key, 'class', strtolower( $attr_key ) );
				}
			}
		}

		$this->add_render_attribute(
			$content_key,
			'class',
			array(
				'premium-blog-content-wrapper',
				$thumb,
			)
		);

		?>
		<div <?php echo wp_kses_post( $this->get_render_attribute_string( $tax_key ) ); ?>>
			<div <?php echo wp_kses_post( $this->get_render_attribute_string( $wrap_key ) ); ?>>
				<?php if ( empty( $thumb ) ) : ?>
					<div class="premium-blog-thumb-effect-wrapper">
						<div class="premium-blog-thumbnail-container <?php echo esc_attr( 'premium-blog-' . $image_effect . '-effect' ); ?>">
							<?php $this->get_post_thumbnail( $target ); ?>
						</div>
						<?php if ( in_array( $skin, array( 'modern', 'cards' ), true ) ) : ?>
							<div class="premium-blog-effect-container <?php echo esc_attr( 'premium-blog-' . $post_effect . '-effect' ); ?>">
								<a class="premium-blog-post-link" href="<?php the_permalink(); ?>" target="<?php echo esc_attr( $target ); ?>"></a>
								<?php if ( 'squares' === $settings['premium_blog_hover_color_effect'] ) { ?>
									<div class="premium-blog-squares-square-container"></div>
								<?php } ?>
							</div>
						<?php else : ?>
							<div class="premium-blog-thumbnail-overlay">
								<a class="elementor-icon" href="<?php the_permalink(); ?>" target="<?php echo esc_attr( $target ); ?>"></a>
							</div>
						<?php endif; ?>
					</div>
				<?php endif; ?>
				<?php if ( 'cards' === $skin ) : ?>
					<div class="premium-blog-author-thumbnail">
						<?php echo get_avatar( get_the_author_meta( 'ID' ), 128, '', get_the_author_meta( 'display_name' ) ); ?>
					</div>
				<?php endif; ?>
				<div <?php echo wp_kses_post( $this->get_render_attribute_string( $content_key ) ); ?>>
					<div class="premium-blog-content-wrapper-inner">
						<div class="premium-blog-inner-container">
							<div class="premium-blog-entry-container">
								<?php if ( in_array( $skin, array( 'side', 'banner' ), true ) && 'yes' === $settings['premium_blog_categories_meta'] ) { ?>
									<div class="premium-blog-cats-container">
										<ul class="post-categories">
											<?php
												$post_cats     = get_the_category();
												$cats_repeater = $settings['categories_repeater'];
											if ( count( $post_cats ) ) {
												foreach ( $post_cats as $index => $cat ) {
													$class = isset( $cats_repeater[ $index ] ) ? 'elementor-repeater-item-' . $cats_repeater[ $index ]['_id'] : '';
													echo wp_kses_post( sprintf( '<li><a href="%s" class="%s">%s</a></li>', get_category_link( $cat->cat_ID ), $class, $cat->name ) );
												}
											}

											?>
										</ul>
									</div>
								<?php } ?>
								<?php
									$this->render_post_title( $target );
								if ( 'cards' !== $skin ) {
									$this->get_post_meta( $target );
								}

								?>

							</div>
						</div>

						<?php
							$this->get_post_content();
						if ( 'cards' === $skin ) {
							$this->get_post_meta( $target );
						}
						?>
						<?php if ( 'yes' === $settings['premium_blog_tags_meta'] && has_tag() ) : ?>
							<div class="premium-blog-post-tags-container">
									<i class="fa fa-tags fa-fw"></i>
									<?php the_tags( ' ', ', ' ); ?>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>

		<?php
	}

	/**
	 * Render Posts
	 *
	 * @since 3.20.9
	 * @access public
	 */
	public function render_posts() {

		$query = $this->get_query_posts();

		$posts = $query->posts;

		if ( count( $posts ) ) {
			global $post;

			foreach ( $posts as $post ) {
				setup_postdata( $post );
				$this->get_post_layout();
			}
		}

		wp_reset_postdata();

	}

	/**
	 * Inner Render
	 *
	 * @since 3.20.9
	 * @access public
	 *
	 * @param object $widget widget.
	 * @param string $active_cat active category.
	 */
	public function inner_render( $widget, $active_cat ) {

		ob_start();

		$settings = $widget->get_settings();

		$this->set_widget_settings( $settings, $active_cat );

		$this->render_posts();

		return ob_get_clean();

	}

	/**
	 * Render Pagination
	 *
	 * Written in PHP and used to generate the final HTML for pagination
	 *
	 * @since 3.20.3
	 * @access protected
	 */
	public function render_pagination() {

		$settings = self::$settings;

		$pages = self::$page_limit;

		if ( ! empty( $settings['max_pages'] ) ) {
			$pages = min( $settings['max_pages'], $pages );
		}

		$paged = $this->get_paged();

		$current_page = $paged;
		if ( ! $current_page ) {
			$current_page = 1;
		}

		$nav_links = paginate_links(
			array(
				'current'   => $current_page,
				'total'     => $pages,
				'prev_next' => 'yes' === $settings['pagination_strings'] ? true : false,
				'prev_text' => sprintf( '« %s', $settings['premium_blog_prev_text'] ),
				'next_text' => sprintf( '%s »', $settings['premium_blog_next_text'] ),
				'type'      => 'array',
			)
		);

		?>
		<nav class="premium-blog-pagination-container" role="navigation" aria-label="<?php echo esc_attr( __( 'Pagination', 'premium-addons-for-elementor' ) ); ?>">
			<?php echo wp_kses_post( implode( PHP_EOL, $nav_links ) ); ?>
		</nav>
		<?php
	}

	/**
	 * Inner Pagination Render
	 *
	 * Used to generate the pagination to be used with the AJAX call
	 *
	 * @since 3.20.3
	 * @access protected
	 */
	public function inner_pagination_render() {

		ob_start();

		$this->render_pagination();

		return ob_get_clean();

	}

	/**
	 * Get Posts Query
	 *
	 * Get posts using AJAX
	 *
	 * @since 3.20.9
	 * @access public
	 */
	public function get_posts_query() {

		check_ajax_referer( 'pa-blog-widget-nonce', 'nonce' );

		if ( ! isset( $_POST['page_id'] ) || ! isset( $_POST['widget_id'] ) ) {
			return;
		}

		$doc_id     = isset( $_POST['page_id'] ) ? sanitize_text_field( $_POST['page_id'] ) : '';
		$elem_id    = isset( $_POST['widget_id'] ) ? sanitize_text_field( $_POST['widget_id'] ) : '';
		$active_cat = isset( $_POST['category'] ) ? sanitize_text_field( $_POST['category'] ) : '';

		$elementor = Plugin::$instance;
		$meta      = $elementor->documents->get( $doc_id )->get_elements_data();

		$widget_data = $this->find_element_recursive( $meta, $elem_id );

		$data = array(
			'ID'     => '',
			'posts'  => '',
			'paging' => '',
		);

		if ( null !== $widget_data ) {

			$widget = $elementor->elements_manager->create_element_instance( $widget_data );

			$posts = $this->inner_render( $widget, $active_cat );

			$pagination = $this->inner_pagination_render();

			$data['ID']     = $widget->get_id();
			$data['posts']  = $posts;
			$data['paging'] = $pagination;
		}

		wp_send_json_success( $data );

	}

	/**
	 * Get Current Product Swap Image
	 *
	 * @since 3.4.0
	 * @access public
	 */
	public static function get_current_product_swap_image() {

		global $product;

		$attachment_ids = $product->get_gallery_image_ids();

		if ( $attachment_ids ) {

			$image_size = apply_filters( 'single_product_archive_thumbnail_size', 'shop_catalog' );

			echo wp_kses_post( apply_filters( 'pa_woo_product_swap_image', wp_get_attachment_image( reset( $attachment_ids ), $image_size, false, array( 'class' => 'premium-woo-product__on_hover' ) ) ) );
		}
	}

	/**
	 * Get Current Product Gallery Images
	 *
	 * Gets current product images
	 *
	 * @since 3.4.0
	 * @access public
	 */
	public static function get_current_product_gallery_images() {

		global $product;

		$attachment_ids = $product->get_gallery_image_ids();

		if ( $attachment_ids ) {

			$image_size = apply_filters( 'single_product_archive_thumbnail_size', 'shop_catalog' );

			foreach ( $attachment_ids as $index => $id ) {
				if ( $index > 2 ) {
					break;
				}

				echo wp_kses_post( apply_filters( 'pa_woo_product_gallery_image', wp_get_attachment_image( $id, $image_size, false, array( 'class' => 'premium-woo-product__gallery_image' ) ) ) );
			}
		}
	}

	/**
	 * Get Current Product Category
	 *
	 * @since 3.4.0
	 * @access public
	 */
	public static function get_current_product_category() {
		if ( apply_filters( 'pa_woo_product_parent_category', true ) ) :
			?>
			<span class="premium-woo-product-category">
				<?php
					global $product;
					$product_categories = function_exists( 'wc_get_product_category_list' ) ? wc_get_product_category_list( get_the_ID(), ',', '', '' ) : $product->get_categories( ',', '', '' );

					$product_categories = wp_strip_all_tags( $product_categories );
				if ( $product_categories ) {
					list( $parent_cat ) = explode( ',', $product_categories );
					echo esc_html( $parent_cat );
				}
				?>
			</span> 
			<?php
		endif;
	}

	/**
	 * Get Product Short Description
	 *
	 * @since 3.4.0
	 * @access public
	 */
	public static function get_product_excerpt() {

		if ( has_excerpt() ) {
			echo '<div class="premium-woo-product-desc">';
				echo wp_kses_post( the_excerpt() );
			echo '</div>';
		}

	}


	/**
	 * Get Widget Setting data.
	 *
	 * @since 1.7.0
	 * @access public
	 * @param array  $elements Element array.
	 * @param string $id Element ID.
	 * @return Boolean True/False.
	 */
	public function find_element_recursive( $elements, $id ) {

		foreach ( $elements as $element ) {
			if ( $id === $element['id'] ) {
				return $element;
			}

			if ( ! empty( $element['elements'] ) ) {
				$element = $this->find_element_recursive( $element['elements'], $id );

				if ( $element ) {
					return $element;
				}
			}
		}

		return false;
	}

	/**
	 * Add render attribute.
	 *
	 * Used to add attributes to a specific HTML element.
	 *
	 * The HTML tag is represented by the element parameter, then you need to
	 * define the attribute key and the attribute key. The final result will be:
	 * `<element attribute_key="attribute_value">`.
	 *
	 * Example usage:
	 *
	 * `$this->add_render_attribute( 'wrapper', 'class', 'custom-widget-wrapper-class' );`
	 * `$this->add_render_attribute( 'widget', 'id', 'custom-widget-id' );`
	 * `$this->add_render_attribute( 'button', [ 'class' => 'custom-button-class', 'id' => 'custom-button-id' ] );`
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param array|string $element   The HTML element.
	 * @param array|string $key       Optional. Attribute key. Default is null.
	 * @param array|string $value     Optional. Attribute value. Default is null.
	 * @param bool         $overwrite Optional. Whether to overwrite existing
	 *                                attribute. Default is false, not to overwrite.
	 *
	 * @return Element_Base Current instance of the element.
	 */
	public function add_render_attribute( $element, $key = null, $value = null, $overwrite = false ) {
		if ( is_array( $element ) ) {
			foreach ( $element as $element_key => $attributes ) {
				$this->add_render_attribute( $element_key, $attributes, null, $overwrite );
			}

			return $this;
		}

		if ( is_array( $key ) ) {
			foreach ( $key as $attribute_key => $attributes ) {
				$this->add_render_attribute( $element, $attribute_key, $attributes, $overwrite );
			}

			return $this;
		}

		if ( empty( $this->_render_attributes[ $element ][ $key ] ) ) {
			$this->_render_attributes[ $element ][ $key ] = array();
		}

		settype( $value, 'array' );

		if ( $overwrite ) {
			$this->_render_attributes[ $element ][ $key ] = $value;
		} else {
			$this->_render_attributes[ $element ][ $key ] = array_merge( $this->_render_attributes[ $element ][ $key ], $value );
		}

		return $this;
	}

	/**
	 * Get render attribute string.
	 *
	 * Used to retrieve the value of the render attribute.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param array|string $element The element.
	 *
	 * @return string Render attribute string, or an empty string if the attribute
	 *                is empty or not exist.
	 */
	public function get_render_attribute_string( $element ) {
		if ( empty( $this->_render_attributes[ $element ] ) ) {
			return '';
		}

		$render_attributes = $this->_render_attributes[ $element ];

		$attributes = array();

		foreach ( $render_attributes as $attribute_key => $attribute_values ) {
			$attributes[] = sprintf( '%1$s="%2$s"', $attribute_key, esc_attr( implode( ' ', $attribute_values ) ) );
		}

		return implode( ' ', $attributes );
	}

	/**
	 * Fix Query Offset.
	 *
	 * @since 4.0.8
	 * @access public
	 *
	 * @param object $query query object.
	 */
	public function fix_query_offset( &$query ) {

		if ( ! empty( $query->query_vars['offset_to_fix'] ) ) {
			if ( $query->is_paged ) {
				$query->query_vars['offset'] = $query->query_vars['offset_to_fix'] + ( ( $query->query_vars['paged'] - 1 ) * $query->query_vars['posts_per_page'] );
			} else {
				$query->query_vars['offset'] = $query->query_vars['offset_to_fix'];
			}
		}
	}

	/**
	 * Fix Found Posts Query
	 *
	 * @since 4.0.8
	 * @access public
	 *
	 * @param int    $found_posts found posts.
	 * @param object $query query object.
	 */
	public function fix_found_posts_query( $found_posts, $query ) {

		$offset_to_fix = $query->get( 'offset_to_fix' );

		if ( $offset_to_fix ) {
			$found_posts -= $offset_to_fix;
		}

		return $found_posts;
	}


}
