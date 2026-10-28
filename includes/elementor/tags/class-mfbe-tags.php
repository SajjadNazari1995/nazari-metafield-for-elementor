<?php
defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Core\DynamicTags\Data_Tag;
use Elementor\Core\DynamicTags\Tag;
use Elementor\Modules\DynamicTags\Module;

/**
 * Text: every scalar field type, printed as text (select/radio show the label, dates are formatted…).
 */
class MFBE_Tag_Text extends Tag {
	use MFBE_Tag_Helpers;

	public function get_name() {
		return 'mfbe-text';
	}

	public function get_title() {
		return __( 'Meta Field (Text)', 'nazari-metafield-for-elementor' );
	}

	public function get_categories() {
		return array( Module::TEXT_CATEGORY, Module::POST_META_CATEGORY );
	}

	protected function register_controls() {
		$this->register_field_control( array( 'text', 'textarea', 'number', 'email', 'url', 'select', 'radio', 'checkbox', 'switcher', 'date', 'time', 'datetime', 'range', 'link', 'color', 'wysiwyg' ) );
		$this->add_control(
			'fallback',
			array(
				'label' => __( 'Fallback', 'nazari-metafield-for-elementor' ),
				'type'  => Controls_Manager::TEXT,
			)
		);
	}

	public function render() {
		list( $field, $value ) = $this->current_field_value();

		$text = $field ? MFBE_Fields::to_text( $field, $value ) : '';
		if ( '' === $text ) {
			$text = (string) $this->get_settings( 'fallback' );
			$text = esc_html( $text );
		} elseif ( 'wysiwyg' !== $field['type'] ) {
			$text = esc_html( $text );
		}

		echo wp_kses_post( $text );
	}
}

/**
 * Number: for number fields (and text fields holding numbers).
 */
class MFBE_Tag_Number extends Tag {
	use MFBE_Tag_Helpers;

	public function get_name() {
		return 'mfbe-number';
	}

	public function get_title() {
		return __( 'Meta Field (Number)', 'nazari-metafield-for-elementor' );
	}

	public function get_categories() {
		return array( Module::NUMBER_CATEGORY );
	}

	protected function register_controls() {
		$this->register_field_control( array( 'number', 'range', 'text' ) );
		$this->add_control(
			'fallback',
			array(
				'label' => __( 'Fallback', 'nazari-metafield-for-elementor' ),
				'type'  => Controls_Manager::NUMBER,
			)
		);
	}

	public function render() {
		list( , $value ) = $this->current_field_value();

		if ( ! is_numeric( $value ) ) {
			$value = $this->get_settings( 'fallback' );
		}

		echo is_numeric( $value ) ? esc_html( (string) $value ) : '';
	}
}

/**
 * URL: URL fields, emails (mailto:), images (file URL) and text fields holding a link.
 */
class MFBE_Tag_URL extends Tag {
	use MFBE_Tag_Helpers;

	public function get_name() {
		return 'mfbe-url';
	}

	public function get_title() {
		return __( 'Meta Field (URL)', 'nazari-metafield-for-elementor' );
	}

	public function get_categories() {
		return array( Module::URL_CATEGORY );
	}

	protected function register_controls() {
		$this->register_field_control( array( 'url', 'link', 'file', 'email', 'image', 'text' ) );
		$this->add_control(
			'fallback',
			array(
				'label' => __( 'Fallback', 'nazari-metafield-for-elementor' ),
				'type'  => Controls_Manager::TEXT,
			)
		);
	}

	public function render() {
		list( $field, $value ) = $this->current_field_value();

		$url = '';
		if ( $field && '' !== $value ) {
			if ( in_array( $field['type'], array( 'image', 'file' ), true ) ) {
				$url = (string) wp_get_attachment_url( absint( $value ) );
			} elseif ( 'link' === $field['type'] ) {
				$url = is_array( $value ) && isset( $value['url'] ) ? (string) $value['url'] : '';
			} elseif ( 'email' === $field['type'] ) {
				$url = 'mailto:' . sanitize_email( (string) $value );
			} else {
				$url = (string) $value;
			}
		}

		if ( '' === $url ) {
			$url = (string) $this->get_settings( 'fallback' );
		}

		echo esc_url( $url );
	}
}

/**
 * Color: color fields.
 */
class MFBE_Tag_Color extends Tag {
	use MFBE_Tag_Helpers;

	public function get_name() {
		return 'mfbe-color';
	}

	public function get_title() {
		return __( 'Meta Field (Color)', 'nazari-metafield-for-elementor' );
	}

	public function get_categories() {
		return array( Module::COLOR_CATEGORY );
	}

	protected function register_controls() {
		$this->register_field_control( array( 'color' ) );
	}

	public function render() {
		list( , $value ) = $this->current_field_value();

		$color = sanitize_hex_color( (string) $value );
		echo $color ? esc_html( $color ) : '';
	}
}

/**
 * Image: image fields. Works in any image control (Image widget, backgrounds, …).
 */
class MFBE_Tag_Image extends Data_Tag {
	use MFBE_Tag_Helpers;

	public function get_name() {
		return 'mfbe-image';
	}

	public function get_title() {
		return __( 'Meta Field (Image)', 'nazari-metafield-for-elementor' );
	}

	public function get_categories() {
		return array( Module::IMAGE_CATEGORY );
	}

	protected function register_controls() {
		$this->register_field_control( array( 'image' ) );
		$this->add_control(
			'fallback',
			array(
				'label' => __( 'Fallback', 'nazari-metafield-for-elementor' ),
				'type'  => Controls_Manager::MEDIA,
			)
		);
	}

	public function get_value( array $options = array() ) {
		list( , $value ) = $this->current_field_value();

		$id  = absint( $value );
		$url = $id ? wp_get_attachment_url( $id ) : '';

		if ( $url ) {
			return array(
				'id'  => $id,
				'url' => $url,
			);
		}

		$fallback = $this->get_settings( 'fallback' );
		return is_array( $fallback ) ? $fallback : array();
	}
}

/**
 * Gallery: gallery fields. Works in the Image Carousel, Basic Gallery, etc.
 */
class MFBE_Tag_Gallery extends Data_Tag {
	use MFBE_Tag_Helpers;

	public function get_name() {
		return 'mfbe-gallery';
	}

	public function get_title() {
		return __( 'Meta Field (Gallery)', 'nazari-metafield-for-elementor' );
	}

	public function get_categories() {
		return array( Module::GALLERY_CATEGORY );
	}

	protected function register_controls() {
		$this->register_field_control( array( 'gallery' ) );
	}

	public function get_value( array $options = array() ) {
		list( , $value ) = $this->current_field_value();

		$images = array();
		foreach ( MFBE_Fields::ids_from_value( $value ) as $id ) {
			$url = wp_get_attachment_url( $id );
			if ( $url ) {
				$images[] = array(
					'id'  => $id,
					'url' => $url,
				);
			}
		}

		return $images;
	}
}
