<?php
defined( 'ABSPATH' ) || exit;

/**
 * Shared comparison logic: used by conditional fields (meta boxes) and by Elementor visibility rules.
 */
class MFBE_Conditions {

	/**
	 * @return string[] operator => label
	 */
	public static function operators() {
		return array(
			'eq'        => __( 'equals', 'nazari-metafield-for-elementor' ),
			'neq'       => __( 'does not equal', 'nazari-metafield-for-elementor' ),
			'not_empty' => __( 'is not empty', 'nazari-metafield-for-elementor' ),
			'empty'     => __( 'is empty', 'nazari-metafield-for-elementor' ),
			'contains'  => __( 'contains', 'nazari-metafield-for-elementor' ),
			'gt'        => __( 'is greater than', 'nazari-metafield-for-elementor' ),
			'lt'        => __( 'is less than', 'nazari-metafield-for-elementor' ),
		);
	}

	public static function needs_value( $operator ) {
		return ! in_array( $operator, array( 'empty', 'not_empty' ), true );
	}

	public static function sanitize_operator( $operator ) {
		$operator = sanitize_key( $operator );
		return isset( self::operators()[ $operator ] ) ? $operator : 'eq';
	}

	/**
	 * Link fields are arrays; compare their URL.
	 */
	private static function flatten( $value ) {
		if ( is_array( $value ) && array_key_exists( 'url', $value ) ) {
			return (string) $value['url'];
		}
		return $value;
	}

	public static function is_empty_value( $value ) {
		$value = self::flatten( $value );
		if ( is_array( $value ) ) {
			return 0 === count( array_filter( $value, 'strlen' ) );
		}
		return '' === trim( (string) $value );
	}

	/**
	 * @param mixed  $actual   Current value of the field being watched.
	 * @param string $operator One of operators().
	 * @param string $expected Value typed by the user.
	 */
	public static function evaluate( $actual, $operator, $expected ) {
		$actual   = self::flatten( $actual );
		$expected = (string) $expected;

		switch ( $operator ) {
			case 'empty':
				return self::is_empty_value( $actual );

			case 'not_empty':
				return ! self::is_empty_value( $actual );

			case 'eq':
				if ( is_array( $actual ) ) {
					return in_array( $expected, array_map( 'strval', $actual ), true );
				}
				return (string) $actual === $expected;

			case 'neq':
				return ! self::evaluate( $actual, 'eq', $expected );

			case 'contains':
				if ( is_array( $actual ) ) {
					return in_array( $expected, array_map( 'strval', $actual ), true );
				}
				return '' !== $expected && false !== stripos( (string) $actual, $expected );

			case 'gt':
				return is_numeric( $actual ) && is_numeric( $expected ) && (float) $actual > (float) $expected;

			case 'lt':
				return is_numeric( $actual ) && is_numeric( $expected ) && (float) $actual < (float) $expected;
		}

		return false;
	}
}
