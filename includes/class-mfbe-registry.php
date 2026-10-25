<?php
defined( 'ABSPATH' ) || exit;

/**
 * Reads the field groups saved by the builder and exposes them to the rest of the plugin.
 */
class MFBE_Registry {

	const CPT      = 'mfbe_group';
	const META_KEY = '_mfbe_config';

	private static $groups = null;

	public static function reset() {
		self::$groups = null;
	}

	/**
	 * @return array[] Published groups keyed by post ID.
	 */
	public static function groups() {
		if ( null !== self::$groups ) {
			return self::$groups;
		}

		self::$groups = array();

		$posts = get_posts(
			array(
				'post_type'        => self::CPT,
				'post_status'      => 'publish',
				'numberposts'      => -1,
				'orderby'          => 'menu_order title',
				'order'            => 'ASC',
				'no_found_rows'    => true,
			)
		);

		foreach ( $posts as $post ) {
			$config = get_post_meta( $post->ID, self::META_KEY, true );
			if ( ! is_array( $config ) ) {
				continue;
			}

			$config = wp_parse_args(
				$config,
				array(
					'post_types' => array(),
					'context'    => 'normal',
					'priority'   => 'high',
					'fields'     => array(),
				)
			);

			$fields = array();
			foreach ( (array) $config['fields'] as $field ) {
				$field = MFBE_Fields::normalize( $field );
				if ( '' !== $field['name'] ) {
					$fields[ $field['name'] ] = $field;
				}
			}

			$config['fields'] = $fields;
			$config['id']     = $post->ID;
			$config['title']  = get_the_title( $post );

			self::$groups[ $post->ID ] = $config;
		}

		return self::$groups;
	}

	/**
	 * @return array[] Every field of every group, keyed by meta key.
	 */
	public static function all_fields() {
		$all = array();
		foreach ( self::groups() as $group ) {
			foreach ( $group['fields'] as $name => $field ) {
				$field['group_title'] = $group['title'];
				$all[ $name ]         = $field;
			}
		}
		return $all;
	}

	public static function find_field( $name ) {
		$all = self::all_fields();
		return isset( $all[ $name ] ) ? $all[ $name ] : null;
	}

	/**
	 * Select options for Elementor controls: meta key => "Group: Label".
	 *
	 * @param string[]|null $types Restrict to these field types (null = all).
	 * @param string[]      $exclude Field types to leave out.
	 */
	public static function field_choices( $types = null, $exclude = array() ) {
		$choices = array( '' => __( '— Select a field —', 'nazari-metafield-for-elementor' ) );

		foreach ( self::all_fields() as $name => $field ) {
			if ( null !== $types && ! in_array( $field['type'], $types, true ) ) {
				continue;
			}
			if ( in_array( $field['type'], $exclude, true ) ) {
				continue;
			}
			$label = '' !== $field['label'] ? $field['label'] : $name;
			/* translators: 1: field group title, 2: field label */
			$choices[ $name ] = sprintf( __( '%1$s: %2$s', 'nazari-metafield-for-elementor' ), $field['group_title'], $label );
		}

		return $choices;
	}
}
