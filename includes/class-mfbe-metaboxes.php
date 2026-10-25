<?php
defined( 'ABSPATH' ) || exit;

/**
 * Registers the meta boxes defined by the groups, renders the fields and saves them.
 */
class MFBE_Metaboxes {

	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'register' ) );
		add_action( 'save_post', array( __CLASS__, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
	}

	private static function groups_for( $post_type ) {
		$matched = array();
		foreach ( MFBE_Registry::groups() as $group ) {
			if ( $group['fields'] && in_array( $post_type, (array) $group['post_types'], true ) ) {
				$matched[ $group['id'] ] = $group;
			}
		}
		return $matched;
	}

	public static function register( $post_type ) {
		foreach ( self::groups_for( $post_type ) as $group ) {
			add_meta_box(
				'mfbe_group_' . $group['id'],
				$group['title'],
				array( __CLASS__, 'render' ),
				$post_type,
				$group['context'],
				$group['priority'],
				array( 'group_id' => $group['id'] )
			);
		}
	}

	public static function render( $post, $box ) {
		$groups   = MFBE_Registry::groups();
		$group_id = isset( $box['args']['group_id'] ) ? (int) $box['args']['group_id'] : 0;
		if ( ! isset( $groups[ $group_id ] ) ) {
			return;
		}
		$group = $groups[ $group_id ];

		wp_nonce_field( 'mfbe_save_' . $group_id, 'mfbe_nonce_' . $group_id, false );

		printf( '<div class="mfbe-fields mfbe-ctx-%s">', esc_attr( $group['context'] ) );

		foreach ( $group['fields'] as $name => $field ) {
			if ( metadata_exists( 'post', $post->ID, $name ) ) {
				$value = get_post_meta( $post->ID, $name, true );
			} else {
				$value = $field['default'];
			}

			printf( '<div class="mfbe-row" style="--mfbe-w:%d%%">', (int) $field['width'] );
			printf( '<input type="hidden" name="mfbe_seen[%s]" value="1">', esc_attr( $name ) );

			if ( '' !== $field['label'] ) {
				printf( '<label class="mfbe-label" for="%s">%s</label>', esc_attr( 'mfbe_' . $name ), esc_html( $field['label'] ) );
			}

			MFBE_Fields::render( $field, $value );

			if ( '' !== $field['description'] ) {
				printf( '<p class="description">%s</p>', esc_html( $field['description'] ) );
			}

			echo '</div>';
		}

		echo '</div>';
	}

	public static function save( $post_id, $post ) {
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$groups = self::groups_for( $post->post_type );
		if ( ! $groups ) {
			return;
		}

		$submitted = isset( $_POST['mfbe'] ) ? (array) wp_unslash( $_POST['mfbe'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput -- verified per group below; values are sanitized per field type.
		$seen      = isset( $_POST['mfbe_seen'] ) ? (array) wp_unslash( $_POST['mfbe_seen'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput

		foreach ( $groups as $group_id => $group ) {
			$nonce_key = 'mfbe_nonce_' . $group_id;
			if ( ! isset( $_POST[ $nonce_key ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $nonce_key ] ) ), 'mfbe_save_' . $group_id ) ) {
				continue;
			}

			foreach ( $group['fields'] as $name => $field ) {
				// Only touch fields that were actually shown in the form.
				if ( ! isset( $seen[ $name ] ) ) {
					continue;
				}

				$raw = isset( $submitted[ $name ] ) ? $submitted[ $name ] : ( 'checkbox' === $field['type'] ? array() : '' );
				$value = MFBE_Fields::sanitize( $field, $raw );

				if ( '' === $value || array() === $value ) {
					delete_post_meta( $post_id, $name );
				} else {
					update_post_meta( $post_id, $name, $value );
				}
			}
		}
	}

	public static function enqueue( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || ! self::groups_for( $screen->post_type ) ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 'mfbe-meta', MFBE_URL . 'assets/meta.css', array(), MFBE_VERSION );
		wp_enqueue_script( 'mfbe-meta', MFBE_URL . 'assets/meta.js', array( 'jquery', 'jquery-ui-sortable', 'wp-color-picker' ), MFBE_VERSION, true );
		wp_localize_script(
			'mfbe-meta',
			'MFBE_META',
			array(
				'selectImage'  => __( 'Select image', 'nazari-metafield-for-elementor' ),
				'selectImages' => __( 'Select images', 'nazari-metafield-for-elementor' ),
				'use'          => __( 'Use selected', 'nazari-metafield-for-elementor' ),
				'remove'       => __( 'Remove', 'nazari-metafield-for-elementor' ),
			)
		);
	}
}
