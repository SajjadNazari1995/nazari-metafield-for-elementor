<?php
defined( 'ABSPATH' ) || exit;

/**
 * Field types: rendering inside the meta box, sanitizing on save, and formatting on the front end.
 */
class MFBE_Fields {

	public static function types() {
		return array(
			'text'     => __( 'Text', 'nazari-metafield-for-elementor' ),
			'textarea' => __( 'Textarea', 'nazari-metafield-for-elementor' ),
			'number'   => __( 'Number', 'nazari-metafield-for-elementor' ),
			'email'    => __( 'Email', 'nazari-metafield-for-elementor' ),
			'url'      => __( 'URL', 'nazari-metafield-for-elementor' ),
			'select'   => __( 'Select', 'nazari-metafield-for-elementor' ),
			'radio'    => __( 'Radio', 'nazari-metafield-for-elementor' ),
			'checkbox' => __( 'Checkbox (multiple)', 'nazari-metafield-for-elementor' ),
			'switcher' => __( 'Switcher', 'nazari-metafield-for-elementor' ),
			'date'     => __( 'Date', 'nazari-metafield-for-elementor' ),
			'color'    => __( 'Color', 'nazari-metafield-for-elementor' ),
			'wysiwyg'  => __( 'WYSIWYG editor', 'nazari-metafield-for-elementor' ),
			'image'    => __( 'Image', 'nazari-metafield-for-elementor' ),
			'gallery'  => __( 'Gallery', 'nazari-metafield-for-elementor' ),
			'file'     => __( 'File', 'nazari-metafield-for-elementor' ),
			'link'     => __( 'Link (URL, text, new tab)', 'nazari-metafield-for-elementor' ),
			'time'     => __( 'Time', 'nazari-metafield-for-elementor' ),
			'datetime' => __( 'Date and time', 'nazari-metafield-for-elementor' ),
			'oembed'   => __( 'Embed (video, tweet…)', 'nazari-metafield-for-elementor' ),
			'range'    => __( 'Range slider', 'nazari-metafield-for-elementor' ),
		);
	}

	public static function defaults() {
		return array(
			'label'       => '',
			'name'        => '',
			'type'        => 'text',
			'description' => '',
			'placeholder' => '',
			'default'     => '',
			'options'     => '',
			'width'       => '100',
			'min'         => '',
			'max'         => '',
			'step'        => '',
			'required'    => '',
			'min_length'  => '',
			'max_length'  => '',
			'unique'      => '',
			'column'      => '',
			'sortable'    => '',
			'cond_field'  => '',
			'cond_op'     => 'eq',
			'cond_value'  => '',
		);
	}

	public static function sanitize_name( $name ) {
		$name = strtolower( trim( (string) $name ) );
		$name = preg_replace( '/[^a-z0-9_]+/', '_', $name );
		return trim( $name, '_' );
	}

	/**
	 * Clean a field definition coming from the builder (or from the database).
	 */
	public static function normalize( $field ) {
		$defaults = self::defaults();
		$field    = wp_parse_args( is_array( $field ) ? $field : array(), $defaults );
		// Keep only the known properties, as plain strings.
		$field = array_intersect_key( $field, $defaults );
		foreach ( $field as $key => $value ) {
			$field[ $key ] = is_scalar( $value ) ? (string) $value : '';
		}

		$field['name']        = self::sanitize_name( $field['name'] );
		$field['label']       = sanitize_text_field( $field['label'] );
		$field['description'] = sanitize_text_field( $field['description'] );
		$field['placeholder'] = sanitize_text_field( $field['placeholder'] );
		$field['default']     = sanitize_textarea_field( $field['default'] );
		$field['options']     = sanitize_textarea_field( $field['options'] );
		$field['type']        = isset( self::types()[ $field['type'] ] ) ? $field['type'] : 'text';
		$field['width']       = in_array( (string) $field['width'], array( '25', '33', '50', '66', '75', '100' ), true ) ? (string) $field['width'] : '100';

		foreach ( array( 'min', 'max', 'step' ) as $key ) {
			$field[ $key ] = is_numeric( $field[ $key ] ) ? (string) $field[ $key ] : '';
		}

		foreach ( array( 'required', 'unique', 'column', 'sortable' ) as $key ) {
			$field[ $key ] = ( ! empty( $field[ $key ] ) && '0' !== (string) $field[ $key ] ) ? '1' : '';
		}
		foreach ( array( 'min_length', 'max_length' ) as $key ) {
			$field[ $key ] = ctype_digit( (string) $field[ $key ] ) ? (string) $field[ $key ] : '';
		}

		$field['cond_field'] = self::sanitize_name( $field['cond_field'] );
		$field['cond_op']    = MFBE_Conditions::sanitize_operator( $field['cond_op'] );
		$field['cond_value'] = sanitize_text_field( $field['cond_value'] );
		if ( $field['cond_field'] === $field['name'] ) {
			$field['cond_field'] = '';
		}

		return $field;
	}

	/**
	 * "value : label" per line → array( value => label ).
	 */
	public static function parse_options( $raw ) {
		$options = array();
		foreach ( preg_split( '/\r\n|\r|\n/', (string) $raw ) as $line ) {
			$line = trim( $line );
			if ( '' === $line ) {
				continue;
			}
			if ( false !== strpos( $line, ':' ) ) {
				list( $value, $label ) = array_map( 'trim', explode( ':', $line, 2 ) );
			} else {
				$value = $line;
				$label = $line;
			}
			$options[ $value ] = '' !== $label ? $label : $value;
		}
		return $options;
	}

	public static function is_media( $type ) {
		return in_array( $type, array( 'image', 'gallery' ), true );
	}

	public static function ids_from_value( $value ) {
		if ( is_array( $value ) ) {
			$value = implode( ',', $value );
		}
		return array_values( array_filter( array_map( 'absint', explode( ',', (string) $value ) ) ) );
	}

	/* ------------------------------------------------------------------ *
	 * Admin rendering
	 * ------------------------------------------------------------------ */

	/**
	* HTML attributes shared by the text-like inputs (already escaped).
	*/
	private static function input_attrs( array $f, $with_length = true ) {
		$out = '';
		if ( ! empty( $f['required'] ) ) {
			$out .= ' required';
		}
		if ( $with_length ) {
			if ( ! empty( $f['min_length'] ) ) {
				$out .= ' minlength="' . esc_attr( $f['min_length'] ) . '"';
			}
			if ( ! empty( $f['max_length'] ) ) {
				$out .= ' maxlength="' . esc_attr( $f['max_length'] ) . '"';
			}
		}
		return $out;
	}

	public static function render( array $f, $value ) {
		$name = $f['name'];
		$id   = 'mfbe_' . $name;
		$in   = 'mfbe[' . $name . ']';
		$ph   = $f['placeholder'];

		switch ( $f['type'] ) {

			case 'textarea':
				printf(
					'<textarea id="%1$s" name="%2$s" rows="4" class="large-text" placeholder="%3$s"%5$s>%4$s</textarea>',
					esc_attr( $id ),
					esc_attr( $in ),
					esc_attr( $ph ),
					esc_textarea( (string) $value ),
					self::input_attrs( $f ) // phpcs:ignore WordPress.Security.EscapeOutput -- built from esc_attr() above.
				);
				break;

			case 'number':
				$attrs = '';
				foreach ( array( 'min', 'max', 'step' ) as $attr ) {
					if ( '' !== $f[ $attr ] ) {
						$attrs .= ' ' . $attr . '="' . esc_attr( $f[ $attr ] ) . '"';
					}
				}
				printf(
					'<input type="number" id="%1$s" name="%2$s" value="%3$s" placeholder="%4$s" class="regular-text"%5$s>',
					esc_attr( $id ),
					esc_attr( $in ),
					esc_attr( (string) $value ),
					esc_attr( $ph ),
					$attrs . self::input_attrs( $f, false ) // phpcs:ignore WordPress.Security.EscapeOutput -- built from esc_attr() above.
				);
				break;

			case 'text':
			case 'email':
			case 'url':
			case 'date':
			case 'time':
			case 'datetime':
			case 'oembed':
				$html_types = array(
					'datetime' => 'datetime-local',
					'oembed'   => 'url',
				);
				$html_type  = isset( $html_types[ $f['type'] ] ) ? $html_types[ $f['type'] ] : $f['type'];
				printf(
					'<input type="%1$s" id="%2$s" name="%3$s" value="%4$s" placeholder="%5$s" class="regular-text"%6$s>',
					esc_attr( $html_type ),
					esc_attr( $id ),
					esc_attr( $in ),
					esc_attr( (string) $value ),
					esc_attr( $ph ),
					self::input_attrs( $f, in_array( $f['type'], array( 'text', 'email', 'url' ), true ) ) // phpcs:ignore WordPress.Security.EscapeOutput -- built from esc_attr().
				);
				break;

			case 'color':
				printf(
					'<input type="text" id="%1$s" name="%2$s" value="%3$s" class="mfbe-color">',
					esc_attr( $id ),
					esc_attr( $in ),
					esc_attr( (string) $value )
				);
				break;

			case 'select':
				printf( '<select id="%1$s" name="%2$s"%3$s>', esc_attr( $id ), esc_attr( $in ), self::input_attrs( $f, false ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- built from esc_attr().
				echo '<option value="">' . esc_html__( '— Select —', 'nazari-metafield-for-elementor' ) . '</option>';
				foreach ( self::parse_options( $f['options'] ) as $key => $label ) {
					printf(
						'<option value="%1$s"%2$s>%3$s</option>',
						esc_attr( $key ),
						selected( (string) $value, (string) $key, false ),
						esc_html( $label )
					);
				}
				echo '</select>';
				break;

			case 'radio':
				echo '<div class="mfbe-choices">';
				foreach ( self::parse_options( $f['options'] ) as $key => $label ) {
					printf(
						'<label><input type="radio" name="%1$s" value="%2$s"%3$s> %4$s</label>',
						esc_attr( $in ),
						esc_attr( $key ),
						checked( (string) $value, (string) $key, false ),
						esc_html( $label )
					);
				}
				echo '</div>';
				break;

			case 'checkbox':
				$current = array_map( 'strval', (array) $value );
				echo '<div class="mfbe-choices">';
				foreach ( self::parse_options( $f['options'] ) as $key => $label ) {
					printf(
						'<label><input type="checkbox" name="%1$s[]" value="%2$s"%3$s> %4$s</label>',
						esc_attr( $in ),
						esc_attr( $key ),
						checked( in_array( (string) $key, $current, true ), true, false ),
						esc_html( $label )
					);
				}
				echo '</div>';
				break;

			case 'switcher':
				printf(
					'<label class="mfbe-switch"><input type="hidden" name="%1$s" value="false"><input type="checkbox" id="%2$s" name="%1$s" value="true"%3$s><span class="mfbe-slider"></span></label>',
					esc_attr( $in ),
					esc_attr( $id ),
					checked( 'true', (string) $value, false )
				);
				break;

			case 'wysiwyg':
				wp_editor(
					(string) $value,
					$id,
					array(
						'textarea_name' => $in,
						'textarea_rows' => 8,
						'media_buttons' => true,
					)
				);
				break;

			case 'range':
				$min     = '' !== $f['min'] ? $f['min'] : '0';
				$max     = '' !== $f['max'] ? $f['max'] : '100';
				$step    = '' !== $f['step'] ? $f['step'] : '1';
				$current = is_numeric( $value ) ? $value : $min;
				printf(
					'<div class="mfbe-range"><input type="range" id="%1$s" name="%2$s" value="%3$s" min="%4$s" max="%5$s" step="%6$s"><output>%3$s</output></div>',
					esc_attr( $id ),
					esc_attr( $in ),
					esc_attr( (string) $current ),
					esc_attr( $min ),
					esc_attr( $max ),
					esc_attr( $step )
				);
				break;

			case 'file':
				$attachment_id = absint( $value );
				$file_path     = $attachment_id ? get_attached_file( $attachment_id ) : '';
				echo '<div class="mfbe-file">';
				printf(
					'<input type="hidden" class="mfbe-file-input" name="%1$s" value="%2$s">',
					esc_attr( $in ),
					esc_attr( $attachment_id ? (string) $attachment_id : '' )
				);
				printf( '<span class="mfbe-file-name">%s</span> ', esc_html( $file_path ? wp_basename( $file_path ) : '' ) );
				printf(
					'<button type="button" class="button mfbe-file-add">%1$s</button> <button type="button" class="button-link mfbe-file-clear">%2$s</button>',
					esc_html__( 'Select file', 'nazari-metafield-for-elementor' ),
					esc_html__( 'Clear', 'nazari-metafield-for-elementor' )
				);
				echo '</div>';
				break;

			case 'link':
				$link = wp_parse_args(
					is_array( $value ) ? $value : array(),
					array(
						'url'    => '',
						'text'   => '',
						'target' => '',
					)
				);
				echo '<div class="mfbe-link">';
				printf(
					'<input type="url" id="%1$s" name="%2$s[url]" value="%3$s" placeholder="https://" class="regular-text"%4$s>',
					esc_attr( $id ),
					esc_attr( $in ),
					esc_attr( (string) $link['url'] ),
					self::input_attrs( $f, false ) // phpcs:ignore WordPress.Security.EscapeOutput -- built from esc_attr().
				);
				printf(
					'<input type="text" name="%1$s[text]" value="%2$s" placeholder="%3$s" class="regular-text">',
					esc_attr( $in ),
					esc_attr( (string) $link['text'] ),
					esc_attr__( 'Link text', 'nazari-metafield-for-elementor' )
				);
				printf(
					'<label class="mfbe-link-target"><input type="checkbox" name="%1$s[target]" value="_blank"%2$s> %3$s</label>',
					esc_attr( $in ),
					checked( '_blank', (string) $link['target'], false ),
					esc_html__( 'Open in a new tab', 'nazari-metafield-for-elementor' )
				);
				echo '</div>';
				break;

			case 'image':
			case 'gallery':
				$ids = self::ids_from_value( $value );
				printf( '<div class="mfbe-media" data-multiple="%d">', 'gallery' === $f['type'] ? 1 : 0 );
				printf( '<input type="hidden" class="mfbe-media-input" name="%1$s" value="%2$s">', esc_attr( $in ), esc_attr( implode( ',', $ids ) ) );
				echo '<div class="mfbe-media-preview">';
				foreach ( $ids as $attachment_id ) {
					$thumb = wp_get_attachment_image( $attachment_id, 'thumbnail' );
					if ( $thumb ) {
						printf(
							'<span class="mfbe-thumb" data-id="%1$d">%2$s<button type="button" class="mfbe-thumb-remove" aria-label="%3$s">&times;</button></span>',
							(int) $attachment_id,
							$thumb, // phpcs:ignore WordPress.Security.EscapeOutput -- core markup.
							esc_attr__( 'Remove', 'nazari-metafield-for-elementor' )
						);
					}
				}
				echo '</div>';
				printf(
					'<button type="button" class="button mfbe-media-add">%s</button> <button type="button" class="button-link mfbe-media-clear">%s</button>',
					esc_html( 'gallery' === $f['type'] ? __( 'Select images', 'nazari-metafield-for-elementor' ) : __( 'Select image', 'nazari-metafield-for-elementor' ) ),
					esc_html__( 'Clear', 'nazari-metafield-for-elementor' )
				);
				echo '</div>';
				break;
		}
	}

	/* ------------------------------------------------------------------ *
	 * Saving
	 * ------------------------------------------------------------------ */

	/**
	 * @param array $f   Normalized field.
	 * @param mixed $raw Unslashed value from $_POST.
	 * @return mixed Clean value ('' or empty array means "delete").
	 */
	public static function sanitize( array $f, $raw ) {
		switch ( $f['type'] ) {
			case 'textarea':
				return sanitize_textarea_field( (string) $raw );

			case 'number':
				return ( is_numeric( $raw ) ) ? (string) $raw : '';

			case 'email':
				return sanitize_email( (string) $raw );

			case 'url':
				return esc_url_raw( (string) $raw );

			case 'select':
			case 'radio':
				$options = self::parse_options( $f['options'] );
				return isset( $options[ $raw ] ) ? (string) $raw : '';

			case 'checkbox':
				$allowed = array_map( 'strval', array_keys( self::parse_options( $f['options'] ) ) );
				$clean   = array();
				foreach ( (array) $raw as $item ) {
					if ( in_array( (string) $item, $allowed, true ) ) {
						$clean[] = (string) $item;
					}
				}
				return $clean;

			case 'switcher':
				return 'true' === $raw ? 'true' : 'false';

			case 'date':
				return ( is_string( $raw ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $raw ) ) ? $raw : '';

			case 'color':
				$color = sanitize_hex_color( (string) $raw );
				return $color ? $color : '';

			case 'wysiwyg':
				return wp_kses_post( (string) $raw );

			case 'image':
				$ids = self::ids_from_value( $raw );
				return $ids ? (string) $ids[0] : '';

			case 'gallery':
				return implode( ',', self::ids_from_value( $raw ) );

			case 'time':
				return ( is_string( $raw ) && preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', $raw ) ) ? $raw : '';

			case 'datetime':
				return ( is_string( $raw ) && preg_match( '/^\d{4}-\d{2}-\d{2}T([01]\d|2[0-3]):[0-5]\d$/', $raw ) ) ? $raw : '';

			case 'oembed':
				return esc_url_raw( (string) $raw );

			case 'range':
				if ( ! is_numeric( $raw ) ) {
					return '';
				}
				$number = (float) $raw;
				$low    = '' !== $f['min'] ? (float) $f['min'] : 0.0;
				$high   = '' !== $f['max'] ? (float) $f['max'] : 100.0;
				return (string) max( $low, min( $high, $number ) );

			case 'file':
				$file_id = absint( $raw );
				return ( $file_id && 'attachment' === get_post_type( $file_id ) ) ? (string) $file_id : '';

			case 'link':
				if ( ! is_array( $raw ) ) {
					return array();
				}
				$link_url = isset( $raw['url'] ) ? esc_url_raw( (string) $raw['url'] ) : '';
				if ( '' === $link_url ) {
					return array();
				}
				return array(
					'url'    => $link_url,
					'text'   => isset( $raw['text'] ) ? sanitize_text_field( (string) $raw['text'] ) : '',
					'target' => ( isset( $raw['target'] ) && '_blank' === $raw['target'] ) ? '_blank' : '',
				);

			case 'text':
			default:
				return sanitize_text_field( (string) $raw );
		}
	}

	/* ------------------------------------------------------------------ *
	 * Front end
	 * ------------------------------------------------------------------ */

	/**
	 * Saved value, or the field default when nothing was ever saved.
	 */
	public static function get_value( $name, $post_id = 0 ) {
		$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
		if ( ! $post_id ) {
			return '';
		}

		if ( metadata_exists( 'post', $post_id, $name ) ) {
			return get_post_meta( $post_id, $name, true );
		}

		$field = MFBE_Registry::find_field( $name );
		return $field ? $field['default'] : '';
	}

	/**
	 * Plain-text representation (not escaped; wysiwyg returns sanitized HTML).
	 */
	public static function to_text( array $f, $value ) {
		switch ( $f['type'] ) {
			case 'select':
			case 'radio':
				$options = self::parse_options( $f['options'] );
				return isset( $options[ $value ] ) ? $options[ $value ] : (string) $value;

			case 'checkbox':
				$options = self::parse_options( $f['options'] );
				$labels  = array();
				foreach ( (array) $value as $item ) {
					$labels[] = isset( $options[ $item ] ) ? $options[ $item ] : $item;
				}
				return implode( ', ', $labels );

			case 'switcher':
				return 'true' === $value ? __( 'Yes', 'nazari-metafield-for-elementor' ) : __( 'No', 'nazari-metafield-for-elementor' );

			case 'date':
				$date = date_create_from_format( 'Y-m-d', (string) $value, wp_timezone() );
				return $date ? wp_date( get_option( 'date_format' ), $date->getTimestamp() ) : '';

			case 'image':
				$url = wp_get_attachment_url( absint( $value ) );
				return $url ? $url : '';

			case 'gallery':
				$urls = array();
				foreach ( self::ids_from_value( $value ) as $attachment_id ) {
					$url = wp_get_attachment_url( $attachment_id );
					if ( $url ) {
						$urls[] = $url;
					}
				}
				return implode( ', ', $urls );

			case 'link':
				if ( ! is_array( $value ) || empty( $value['url'] ) ) {
					return '';
				}
				return '' !== (string) $value['text'] ? (string) $value['text'] : (string) $value['url'];

			case 'file':
				$file_url = wp_get_attachment_url( absint( $value ) );
				return $file_url ? $file_url : '';

			case 'time':
				$time = date_create_from_format( 'H:i', (string) $value, wp_timezone() );
				return $time ? wp_date( get_option( 'time_format' ), $time->getTimestamp() ) : '';

			case 'datetime':
				$datetime = date_create_from_format( 'Y-m-d\TH:i', (string) $value, wp_timezone() );
				return $datetime ? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $datetime->getTimestamp() ) : '';

			case 'wysiwyg':
				return wp_kses_post( (string) $value );

			default:
				return is_array( $value ) ? implode( ', ', $value ) : (string) $value;
		}
	}

	/**
	 * Safe HTML for the Elementor widget and the shortcode.
	 *
	 * @param array $args size (image size), columns (gallery).
	 */
	public static function render_html( array $f, $value, array $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'size'    => 'full',
				'columns' => 3,
			)
		);

		if ( '' === $value || array() === $value || null === $value ) {
			return '';
		}

		switch ( $f['type'] ) {
			case 'wysiwyg':
				return wpautop( wp_kses_post( (string) $value ) );

			case 'textarea':
				return nl2br( esc_html( (string) $value ) );

			case 'url':
				return sprintf( '<a href="%1$s">%2$s</a>', esc_url( (string) $value ), esc_html( (string) $value ) );

			case 'email':
				return sprintf( '<a href="mailto:%1$s">%2$s</a>', esc_attr( antispambot( (string) $value ) ), esc_html( antispambot( (string) $value ) ) );

			case 'color':
				return sprintf(
					'<span class="mfbe-swatch" style="display:inline-block;width:1em;height:1em;vertical-align:middle;background:%1$s"></span> %1$s',
					esc_attr( (string) $value )
				);

			case 'image':
				return wp_get_attachment_image( absint( $value ), $args['size'] );

			case 'gallery':
				$html = '';
				foreach ( self::ids_from_value( $value ) as $attachment_id ) {
					$img = wp_get_attachment_image( $attachment_id, $args['size'] );
					if ( $img ) {
						$html .= '<figure class="mfbe-gallery-item">' . $img . '</figure>';
					}
				}
				if ( '' === $html ) {
					return '';
				}
				// columns = 0 means "the caller styles the grid" (the Elementor widget does).
				if ( (int) $args['columns'] < 1 ) {
					return '<div class="mfbe-gallery">' . $html . '</div>';
				}
				return sprintf(
					'<div class="mfbe-gallery" style="display:grid;gap:10px;grid-template-columns:repeat(%d,1fr)">%s</div>',
					(int) $args['columns'],
					$html
				);

			case 'link':
				if ( ! is_array( $value ) || empty( $value['url'] ) ) {
					return '';
				}
				$link_text = '' !== (string) $value['text'] ? (string) $value['text'] : (string) $value['url'];
				$new_tab   = isset( $value['target'] ) && '_blank' === $value['target'];
				return sprintf(
					'<a href="%1$s"%2$s>%3$s</a>',
					esc_url( (string) $value['url'] ),
					$new_tab ? ' target="_blank" rel="noopener noreferrer"' : '',
					esc_html( $link_text )
				);

			case 'file':
				$file_id  = absint( $value );
				$file_url = wp_get_attachment_url( $file_id );
				if ( ! $file_url ) {
					return '';
				}
				return sprintf( '<a href="%1$s" download>%2$s</a>', esc_url( $file_url ), esc_html( wp_basename( (string) get_attached_file( $file_id ) ) ) );

			case 'oembed':
				$embed = wp_oembed_get( (string) $value );
				return $embed ? wp_kses( $embed, self::embed_kses() ) : '';

			default:
				return esc_html( self::to_text( $f, $value ) );
		}
	}

		/**
		* Tags and attributes allowed in embed HTML (iframes, quotes, links).
		*/
		private static function embed_kses() {
			$global = array(
				'class' => true,
				'id'    => true,
				'style' => true,
				'title' => true,
				'data-*' => true,
			);
			return array(
				'iframe'     => $global + array(
					'src'             => true,
					'width'           => true,
					'height'          => true,
					'frameborder'     => true,
					'allow'           => true,
					'allowfullscreen' => true,
					'loading'         => true,
					'referrerpolicy'  => true,
					'sandbox'         => true,
				),
				'blockquote' => $global + array( 'cite' => true ),
				'a'          => $global + array(
					'href'   => true,
					'target' => true,
					'rel'    => true,
				),
				'p'          => $global,
				'div'        => $global,
				'span'       => $global,
				'br'         => array(),
			);
		}

		/**
		* Does this clean value count as "nothing entered"?
		*/
		public static function is_empty_value( $value ) {
			return MFBE_Conditions::is_empty_value( $value );
		}

		/**
		* Server-side validation of an already sanitized value.
		*
		* @return string Error message, or '' when the value is fine.
		*/
		public static function validate( array $f, $value, $post_id ) {
			$label = '' !== $f['label'] ? $f['label'] : $f['name'];

			if ( self::is_empty_value( $value ) ) {
				if ( ! empty( $f['required'] ) ) {
					/* translators: %s: field label */
					return sprintf( __( '“%s” is required.', 'nazari-metafield-for-elementor' ), $label );
				}
				return '';
			}

			if ( is_string( $value ) && in_array( $f['type'], array( 'text', 'textarea', 'email', 'url' ), true ) ) {
				$length = function_exists( 'mb_strlen' ) ? mb_strlen( $value ) : strlen( $value );

				if ( '' !== $f['min_length'] && $length < (int) $f['min_length'] ) {
					/* translators: 1: field label, 2: minimum length */
					return sprintf( __( '“%1$s” must be at least %2$d characters.', 'nazari-metafield-for-elementor' ), $label, (int) $f['min_length'] );
				}
				if ( '' !== $f['max_length'] && $length > (int) $f['max_length'] ) {
					/* translators: 1: field label, 2: maximum length */
					return sprintf( __( '“%1$s” must be at most %2$d characters.', 'nazari-metafield-for-elementor' ), $label, (int) $f['max_length'] );
				}
			}

			if ( ! empty( $f['unique'] ) && is_string( $value ) && self::value_exists( $f['name'], $value, $post_id ) ) {
				/* translators: %s: field label */
				return sprintf( __( '“%s” must be unique: this value is already used by another post.', 'nazari-metafield-for-elementor' ), $label );
			}

			return '';
		}

		private static function value_exists( $key, $value, $post_id ) {
			$query = new WP_Query(
				array(
					'post_type'      => array_keys( get_post_types( array( 'show_ui' => true ) ) ),
					'post_status'    => 'any',
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'no_found_rows'  => true,
					'post__not_in'   => array( (int) $post_id ),
					'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- single indexed lookup, only for fields marked unique.
						array(
							'key'   => $key,
							'value' => $value,
						),
					),
				)
			);
			return $query->have_posts();
		}
	}
