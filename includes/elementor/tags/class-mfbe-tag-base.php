<?php
defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;

/**
 * Shared helpers for every dynamic tag of the "Meta Fields" group.
 */
trait MFBE_Tag_Helpers {

	public function get_group() {
		return 'mfbe';
	}

	/**
	 * @param string[] $types Field types the tag can read.
	 */
	protected function register_field_control( array $types ) {
		$this->add_control(
			'field',
			array(
				'label'   => __( 'Field', 'nazari-metafield-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'options' => MFBE_Registry::field_choices( $types ),
				'default' => '',
			)
		);
	}

	/**
	 * @return array{0: array|null, 1: mixed} Field definition and its value for the current post.
	 */
	protected function current_field_value() {
		$name  = (string) $this->get_settings( 'field' );
		$field = $name ? MFBE_Registry::find_field( $name ) : null;
		if ( ! $field ) {
			return array( null, '' );
		}

		$post_id = (int) get_the_ID();
		if ( ! $post_id || ! MFBE_Shortcode::can_read( $post_id ) ) {
			return array( $field, '' );
		}

		return array( $field, MFBE_Fields::get_value( $name, $post_id ) );
	}
}
