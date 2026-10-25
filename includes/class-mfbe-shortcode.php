<?php
defined( 'ABSPATH' ) || exit;

/**
 * [mfbe field="meta_key" post_id="" format="html|text" size="full" columns="3" fallback=""]
 */
class MFBE_Shortcode {

	public static function init() {
		add_shortcode( 'mfbe', array( __CLASS__, 'render' ) );
	}

	public static function render( $atts ) {
		$atts = shortcode_atts(
			array(
				'field'    => '',
				'post_id'  => 0,
				'format'   => 'html',
				'size'     => 'full',
				'columns'  => 3,
				'fallback' => '',
			),
			$atts,
			'mfbe'
		);

		$name  = MFBE_Fields::sanitize_name( $atts['field'] );
		$field = $name ? MFBE_Registry::find_field( $name ) : null;
		if ( ! $field ) {
			return '';
		}

		$post_id = absint( $atts['post_id'] );
		if ( ! $post_id ) {
			$post_id = (int) get_the_ID();
		}
		if ( ! $post_id || ! self::can_read( $post_id ) ) {
			return '';
		}

		$value = MFBE_Fields::get_value( $name, $post_id );

		if ( 'text' === $atts['format'] ) {
			$output = esc_html( MFBE_Fields::to_text( $field, $value ) );
		} else {
			$output = MFBE_Fields::render_html(
				$field,
				$value,
				array(
					'size'    => sanitize_key( $atts['size'] ),
					'columns' => absint( $atts['columns'] ),
				)
			);
		}

		return '' !== $output ? $output : esc_html( $atts['fallback'] );
	}

	/**
	 * Never expose meta of drafts, private or password-protected posts to visitors.
	 */
	public static function can_read( $post_id ) {
		if ( current_user_can( 'edit_post', $post_id ) ) {
			return true;
		}
		return is_post_publicly_viewable( $post_id ) && ! post_password_required( $post_id );
	}
}
