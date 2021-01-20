<?php
namespace ElementorExtras;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class Settings_API
 *
 * @since 1.8.0
 */
class Settings_API {

	/**
	 * Settings sections array
	 *
	 * @since 1.8.0
	 * @var array
	 */
	protected $settings_sections = array();

	/**
	 * Settings prefix
	 *
	 * @since 1.8.0
	 * @var array
	 */
	protected $settings_prefix = 'elementor_extras_';

	/**
	 * Settings fields array
	 *
	 * @since 1.8.0
	 * @var array
	 */
	protected $settings_fields = array();

	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
	}

	/**
	 * Enqueue scripts and styles
	 *
	 * @since 1.8.0
	 */
	function admin_enqueue_scripts() {
		wp_enqueue_style( 'wp-color-picker' );

		wp_enqueue_media();
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_script( 'jquery' );
	}

	/**
	 * Set settings sections
	 *
	 * @since 1.8.0
	 * @param array   $sections setting sections array
	 */
	function set_sections( $sections ) {
		$this->settings_sections = $sections;

		return $this;
	}

	/**
	 * Add a single section
	 *
	 * @since 1.8.0
	 * @param array   $section
	 */
	function add_section( $section ) {
		$this->settings_sections[] = $section;

		return $this;
	}

	/**
	 * Set settings fields
	 *
	 * @since 1.8.0
	 * @param array   $fields settings fields array
	 */
	function set_fields( $fields ) {
		$this->settings_fields = $fields;

		return $this;
	}

	/**
	 * Add settings fields
	 *
	 * @since 1.8.0
	 * @param string  $section settings section
	 * @param array   $fields field args
	 */
	function add_field( $section, $field ) {
		$defaults = array(
			'name'  => '',
			'label' => '',
			'desc'  => '',
			'desc_paragraph'  => '',
			'note'	=> '',
			'note_paragraph'  => '',
			'type'  => 'text',
			'disabled' => false,
		);

		$arg = wp_parse_args( $field, $defaults );
		$this->settings_fields[$section][] = $arg;

		return $this;
	}

	/**
	 * Initialize and registers the settings sections and fileds to WordPress
	 *
	 * Usually this should be called at `admin_init` hook.
	 *
	 * This function gets the initiated settings sections and fields. Then
	 * registers them to WordPress and ready for use.
	 *
	 * @since 1.8.0
	 */
	function admin_init() {

		// Register settings sections
		foreach ( $this->settings_sections as $section ) {

			if ( $this->is_tab_linked( $section ) )
				continue;

			if ( false == get_option( $section['id'] ) ) {
				add_option( $section['id'] );
			}

			if ( isset( $section['desc'] ) &&  ! empty( $section['desc'] ) ) {
				$section['desc'] = $section['desc'];
				$callback = function() use ( $section ) { echo $section['desc']; };
			} else if ( isset( $section['callback'] ) ) {
				$callback = $section['callback'];
			} else {
				$callback = null;
			}

			add_settings_section( $section['id'], $section['title'], $callback, $section['id'] );
		}

		// Register settings fields
		foreach ( $this->settings_fields as $section => $field ) {
			foreach ( $field as $option ) {

				$name 		= $option['name'];
				$type 		= isset( $option['type'] ) ? $option['type'] : 'text';
				$label 		= isset( $option['label'] ) ? $option['label'] : '';
				$callback 	= isset( $option['callback'] ) ? $option['callback'] : array( $this, 'callback_' . $type );

				$args = array(
					'id'                => $name,
					'class'             => isset( $option['class'] ) ? $option['class'] : $name,
					'label_for'         => "{$section}[{$name}]",
					'desc'              => isset( $option['desc'] ) ? $option['desc'] : '',
					'no_desc_p'			=> isset( $option['no_desc_p'] ) ? $option['no_desc_p'] : false,
					'note'              => isset( $option['note'] ) ? $option['note'] : '',
					'no_note_p'			=> isset( $option['no_note_p'] ) ? $option['no_note_p'] : false,
					'name'              => $label,
					'section'           => $section,
					'size'              => isset( $option['size'] ) ? $option['size'] : null,
					'options'           => isset( $option['options'] ) ? $option['options'] : '',
					'std'               => isset( $option['default'] ) ? $option['default'] : '',
					'sanitize_callback' => isset( $option['sanitize_callback'] ) ? $option['sanitize_callback'] : '',
					'type'              => $type,
					'placeholder'       => isset( $option['placeholder'] ) ? $option['placeholder'] : '',
					'min'               => isset( $option['min'] ) ? $option['min'] : '',
					'max'               => isset( $option['max'] ) ? $option['max'] : '',
					'step'              => isset( $option['step'] ) ? $option['step'] : '',
					'disabled'			=> isset( $option['disabled'] ) ? 'disabled' : '',
				);

				add_settings_field( "{$section}[{$name}]", $label, $callback, $section, $section, $args );
			}
		}

		// Creates our settings in the options table
		foreach ( $this->settings_sections as $section ) {
			register_setting( $section['id'], $section['id'], array( $this, 'sanitize_options' ) );
		}
	}

	/**
	 * Returns the field description markup
	 *
	 * @since 1.8.0
	 * @param array   $args settings field args
	 */
	public function get_field_description( $args ) {
		if ( ! empty( $args['desc'] ) ) {
			$before = '';
			$after = '';

			if ( false === $args['no_desc_p'] ) {
				$before = '<p class="description ee-description">';
				$after = '</p>';
			}
			
			$desc = $before . $args['desc'] . $after;
		} else {
			$desc = '';
		}

		return $desc;
	}

	/**
	 * Returns the field notice markup
	 *
	 * @since 1.8.0
	 */
	function get_field_note( $args ) {

		if ( ! empty( $args['note'] ) ) {
			$before = '';
			$after = '';

			if ( false === $args['no_note_p'] ) {
				$before = '<p class="note ee-note">';
				$after = '</p>';
			}

			$note = $before . $args['note'] . $after;
		} else {
			$note = '';
		}

		return $note;
	}

	/**
	 * Displays a text field for a settings field
	 *
	 * @since 1.8.0
	 * @param array   $args settings field args
	 */
	function callback_text( $args ) {

		$value       = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
		$size        = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';
		$type        = isset( $args['type'] ) ? $args['type'] : 'text';
		$placeholder = empty( $args['placeholder'] ) ? '' : ' placeholder="' . $args['placeholder'] . '"';

		$html        = sprintf( '<input type="%1$s" class="%2$s-text" id="%3$s[%4$s]" name="%3$s[%4$s]" value="%5$s"%6$s/>', $type, $size, $args['section'], $args['id'], $value, $placeholder );
		$html       .= $this->get_field_description( $args );
		$html       .= $this->get_field_note( $args );

		echo $html;
	}

	/**
	 * Displays a url field for a settings field
	 *
	 * @since 1.8.0
	 * @param array   $args settings field args
	 */
	function callback_url( $args ) {
		$this->callback_text( $args );
	}

	/**
	 * Displays a number field for a settings field
	 *
	 * @since 1.8.0
	 * @param array   $args settings field args
	 */
	function callback_number( $args ) {
		$value       = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
		$size        = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';
		$type        = isset( $args['type'] ) ? $args['type'] : 'number';
		$placeholder = empty( $args['placeholder'] ) ? '' : ' placeholder="' . $args['placeholder'] . '"';
		$min         = empty( $args['min'] ) ? '' : ' min="' . $args['min'] . '"';
		$max         = empty( $args['max'] ) ? '' : ' max="' . $args['max'] . '"';
		$step        = empty( $args['max'] ) ? '' : ' step="' . $args['step'] . '"';

		$html        = sprintf( '<input type="%1$s" class="%2$s-number" id="%3$s[%4$s]" name="%3$s[%4$s]" value="%5$s"%6$s%7$s%8$s%9$s/>', $type, $size, $args['section'], $args['id'], $value, $placeholder, $min, $max, $step );
		$html       .= $this->get_field_description( $args );
		$html       .= $this->get_field_note( $args );

		echo $html;
	}

	/**
	 * Displays a checkbox for a settings field
	 *
	 * @since 1.8.0
	 * @param array   $args settings field args
	 */
	function callback_checkbox( $args ) {

		$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );

		$html  = '<fieldset>';
		$html  .= sprintf( '<label for="%1$s[%2$s]">', $args['section'], $args['id'] );
		$html  .= sprintf( '<input type="hidden" name="%1$s[%2$s]" value="off" />', $args['section'], $args['id'], $args['disabled'] );
		$html  .= sprintf( '<input type="checkbox" class="checkbox" id="%1$s[%2$s]" name="%1$s[%2$s]" value="on" %3$s %4$s />', $args['section'], $args['id'], checked( $value, 'on', false ), $args['disabled'] );
		$html  .= sprintf( '%1$s</label>', $args['desc'] );
		$html  .= $this->get_field_note( $args );
		$html  .= '</fieldset>';

		echo $html;
	}

	/**
	 * Displays a multicheckbox for a settings field
	 *
	 * @since 1.8.0
	 * @param array   $args settings field args
	 */
	function callback_multicheck( $args ) {

		$value = $this->get_option( $args['id'], $args['section'], $args['std'] );
		$html  = '<fieldset>';
		$html .= sprintf( '<input type="hidden" name="%1$s[%2$s]" value="" />', $args['section'], $args['id'] );
		foreach ( $args['options'] as $key => $label ) {
			$checked = isset( $value[$key] ) ? $value[$key] : '0';
			$html    .= sprintf( '<label for="%1$s[%2$s][%3$s]">', $args['section'], $args['id'], $key );
			$html    .= sprintf( '<input type="checkbox" class="checkbox" id="%1$s[%2$s][%3$s]" name="%1$s[%2$s][%3$s]" value="%3$s" %4$s />', $args['section'], $args['id'], $key, checked( $checked, $key, false ) );
			$html    .= sprintf( '%1$s</label><br>',  $label );
		}

		$html .= $this->get_field_description( $args );
		$html .= $this->get_field_note( $args );
		$html .= '</fieldset>';

		echo $html;
	}

	/**
	 * Displays a radio button for a settings field
	 *
	 * @since 1.8.0
	 * @param array   $args settings field args
	 */
	function callback_radio( $args ) {

		$value = $this->get_option( $args['id'], $args['section'], $args['std'] );
		$html  = '<fieldset>';

		foreach ( $args['options'] as $key => $label ) {
			$html .= sprintf( '<label for="%1$s[%2$s][%3$s]">',  $args['section'], $args['id'], $key );
			$html .= sprintf( '<input type="radio" class="radio" id="%1$s[%2$s][%3$s]" name="%1$s[%2$s]" value="%3$s" %4$s />', $args['section'], $args['id'], $key, checked( $value, $key, false ) );
			$html .= sprintf( '%1$s</label><br>', $label );
		}

		$html .= $this->get_field_description( $args );
		$html .= $this->get_field_note( $args );
		$html .= '</fieldset>';

		echo $html;
	}

	/**
	 * Displays a selectbox for a settings field
	 *
	 * @since 1.8.0
	 * @param array   $args settings field args
	 */
	function callback_select( $args ) {

		$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
		$size  = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';
		$html  = sprintf( '<select class="%1$s" name="%2$s[%3$s]" id="%2$s[%3$s]">', $size, $args['section'], $args['id'] );

		foreach ( $args['options'] as $key => $label ) {
			$html .= sprintf( '<option value="%s"%s>%s</option>', $key, selected( $value, $key, false ), $label );
		}

		$html .= sprintf( '</select>' );
		$html .= $this->get_field_description( $args );
		$html .= $this->get_field_note( $args );

		echo $html;
	}

	/**
	 * Displays a textarea for a settings field
	 *
	 * @since 1.8.0
	 * @param array   $args settings field args
	 */
	function callback_textarea( $args ) {

		$value       = esc_textarea( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
		$size        = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';
		$placeholder = empty( $args['placeholder'] ) ? '' : ' placeholder="'.$args['placeholder'].'"';

		$html        = sprintf( '<textarea rows="5" cols="55" class="%1$s-text" id="%2$s[%3$s]" name="%2$s[%3$s]"%4$s>%5$s</textarea>', $size, $args['section'], $args['id'], $placeholder, $value );
		$html       .= $this->get_field_description( $args );
		$html 		.= $this->get_field_note( $args );

		echo $html;
	}

	/**
	 * Displays the html for a settings field
	 *
	 * @since 1.8.0
	 * @param array   $args settings field args
	 * @return string
	 */
	function callback_html( $args ) {
		$html = $this->get_field_description( $args );
		$html .= $this->get_field_note( $args );

		echo $html;
	}

	/**
	 * Displays a rich text textarea for a settings field
	 *
	 * @since 1.8.0
	 * @param array   $args settings field args
	 */
	function callback_wysiwyg( $args ) {

		$value = $this->get_option( $args['id'], $args['section'], $args['std'] );
		$size  = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : '500px';

		echo '<div style="max-width: ' . $size . ';">';

		$editor_settings = array(
			'teeny'         => true,
			'textarea_name' => $args['section'] . '[' . $args['id'] . ']',
			'textarea_rows' => 10
		);

		if ( isset( $args['options'] ) && is_array( $args['options'] ) ) {
			$editor_settings = array_merge( $editor_settings, $args['options'] );
		}

		wp_editor( $value, $args['section'] . '-' . $args['id'], $editor_settings );

		echo '</div>';

		echo $this->get_field_description( $args );
		echo $this->get_field_note( $args );
	}

	/**
	 * Displays a file upload field for a settings field
	 *
	 * @since 1.8.0
	 * @param array   $args settings field args
	 */
	function callback_file( $args ) {

		$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
		$size  = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';
		$id    = $args['section']  . '[' . $args['id'] . ']';
		$label = isset( $args['options']['button_label'] ) ? $args['options']['button_label'] : __( 'Choose File' );

		$html  = sprintf( '<input type="text" class="%1$s-text wpsa-url" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s"/>', $size, $args['section'], $args['id'], $value );
		$html .= '<input type="button" class="button wpsa-browse" value="' . $label . '" />';
		$html .= $this->get_field_description( $args );
		$html .= $this->get_field_note( $args );

		echo $html;
	}

	/**
	 * Displays a password field for a settings field
	 *
	 * @since 1.8.0
	 * @param array   $args settings field args
	 */
	function callback_password( $args ) {

		$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
		$size  = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';

		$html  = sprintf( '<input type="password" class="%1$s-text" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s"/>', $size, $args['section'], $args['id'], $value );
		$html .= $this->get_field_description( $args );
		$html .= $this->get_field_note( $args );

		echo $html;
	}

	/**
	 * Displays a color picker field for a settings field
	 *
	 * @since 1.8.0
	 * @param array   $args settings field args
	 */
	function callback_color( $args ) {

		$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
		$size  = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';

		$html  = sprintf( '<input type="text" class="%1$s-text wp-color-picker-field" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s" data-default-color="%5$s" />', $size, $args['section'], $args['id'], $value, $args['std'] );
		$html .= $this->get_field_description( $args );
		$html .= $this->get_field_note( $args );

		echo $html;
	}


	/**
	 * Displays a select box for creating the pages select box
	 *
	 * @since 1.8.0
	 * @param array   $args settings field args
	 */
	function callback_pages( $args ) {

		$dropdown_args = array(
			'selected' => esc_attr($this->get_option($args['id'], $args['section'], $args['std'] ) ),
			'name'     => $args['section'] . '[' . $args['id'] . ']',
			'id'       => $args['section'] . '[' . $args['id'] . ']',
			'echo'     => 0
		);
		$html = wp_dropdown_pages( $dropdown_args );
		echo $html;
	}

	/**
	 * Sanitize callback for Settings API
	 *
	 * @since 1.8.0
	 * @return mixed
	 */
	function sanitize_options( $options ) {

		if ( !$options ) {
			return $options;
		}

		foreach( $options as $option_slug => $option_value ) {
			$sanitize_callback = $this->get_sanitize_callback( $option_slug );

			// If callback is set, call it
			if ( $sanitize_callback ) {
				$options[ $option_slug ] = call_user_func( $sanitize_callback, $option_value );
				continue;
			}
		}

		return $options;
	}

	/**
	 * Get sanitization callback for given option slug
	 *
	 * @since 1.8.0
	 * @param string $slug option slug
	 *
	 * @return mixed string or bool false
	 */
	function get_sanitize_callback( $slug = '' ) {
		if ( empty( $slug ) ) {
			return false;
		}

		// Iterate over registered fields and see if we can find proper callback
		foreach( $this->settings_fields as $section => $options ) {
			foreach ( $options as $option ) {
				if ( $option['name'] != $slug ) {
					continue;
				}

				// Return the callback name
				return isset( $option['sanitize_callback'] ) && is_callable( $option['sanitize_callback'] ) ? $option['sanitize_callback'] : false;
			}
		}

		return false;
	}

	/**
	 * Get the value of a settings field
	 *
	 * @since 1.8.0
	 * @param string  $option  settings field name
	 * @param string  $section the section name this field belongs to
	 * @param string  $default default text if it's not found
	 * @return string
	 */
	function get_option( $option, $section, $default = '' ) {

		$options = get_option( $section );

		if ( isset( $options[$option] ) ) {
			return $options[$option];
		}

		return $default;
	}

	/**
	 * Determines if a tab should be treated as a link
	 *
	 * @since 1.8.0
	 * Shows all the settings section labels as tab
	 */
	function is_tab_linked( $tab ) {
		if ( array_key_exists( 'link', $tab ) )
			return true;
		
		return false;
	}

	/**
	 * Show navigations as tab
	 *
	 * @since 1.8.0
	 * Shows all the settings section labels as tab
	 */
	function render_navigation() {
		$html = '<h2 class="nav-tab-wrapper ee-nav-tabs">';

		$count = count( $this->settings_sections );

		// don't show the navigation if only one section exists
		if ( $count === 1 ) {
			return;
		}

		$classes = 'nav-tab ee-nav-tabs__tab';

		foreach ( $this->settings_sections as $tab ) {

			$link = '#' . $tab['id'];
			$count = isset( $tab['count'] ) ? $tab['count'] : '';
			$icon = isset( $tab['icon'] ) ? '<span class="' . $tab['icon'] . '"></span>' : '';

			$count_class = '';

			if ( ( ( is_numeric( $count ) && $count > 0 ) || '' !== $count ) ) {
				if ( isset( $tab['label'] ) ) {
					$count_class = 'ee-count--' . $tab['label'];
				}

				$count = '<span class="ee-count ' . $count_class . '">' . $count . '</span>';
			}

			if ( $this->is_tab_linked( $tab ) ) {
				$classes .= ' ee-nav-tabs__link';
				$link = $tab['link'];
			}

			$html .= sprintf( '<a href="%1$s" target="%2$s" class="%3$s" id="%4$s-tab">%5$s%6$s%7$s</a>', $link, isset( $tab['target'] ) ? $tab['target'] : '', $classes, $tab['id'], $tab['title'], $count, $icon );
		}

		$html .= '</h2>';

		echo $html;
	}

	/**
	 * Show the section settings forms
	 *
	 * @since 1.8.0
	 * This function displays every sections in a different form
	 */
	function render_forms() {
		?>
		<div class="metabox-holder ee-metabox-holder ee-settings">
			<?php foreach ( $this->settings_sections as $form ) {

				if ( $this->is_tab_linked( $form ) )
					continue;

				?>
				<div id="<?php echo $form['id']; ?>" class="ee-settings__group" style="display: none;">
					<form method="post" action="options.php" class="ee-settings__form">
						<?php

						settings_fields( $form['id'] );

						do_settings_sections( $form['id'] );

						if ( isset( $this->settings_fields[ $form['id'] ] ) ) {
							submit_button();
						} ?>
					</form>
				</div>
			<?php } ?>
		</div>
		<?php
	}
}