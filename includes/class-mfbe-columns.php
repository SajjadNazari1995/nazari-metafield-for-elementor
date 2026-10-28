<?php
defined( 'ABSPATH' ) || exit;

/**
 * Shows fields as columns in the post list (sortable, with filter dropdowns for choice fields).
 */
class MFBE_Columns {

	/** Field types that get a filter dropdown when shown as a column. */
	const FILTER_TYPES = array( 'select', 'radio', 'switcher' );

	/** @var array<string,array> post type => list of column fields (name => field). */
	private static $by_type = array();

	/** @var array|null Pending sort for the main list query. */
	private static $sort = null;

	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'apply_query' ) );
		add_action( 'restrict_manage_posts', array( __CLASS__, 'render_filters' ), 10, 1 );
	}

	/**
	 * Collect the column fields per post type and hook the list tables.
	 */
	public static function register() {
		self::$by_type = array();

		foreach ( MFBE_Registry::groups() as $group ) {
			foreach ( (array) $group['post_types'] as $post_type ) {
				foreach ( $group['fields'] as $name => $field ) {
					if ( ! empty( $field['column'] ) ) {
						self::$by_type[ $post_type ][ $name ] = $field;
					}
				}
			}
		}

		foreach ( array_keys( self::$by_type ) as $post_type ) {
			add_filter( "manage_{$post_type}_posts_columns", array( __CLASS__, 'add_columns' ) );
			add_action( "manage_{$post_type}_posts_custom_column", array( __CLASS__, 'render_column' ), 10, 2 );
			add_filter( "manage_edit-{$post_type}_sortable_columns", array( __CLASS__, 'sortable_columns' ) );
		}
	}

	private static function current_type() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		return $screen ? $screen->post_type : '';
	}

	private static function fields_for_current() {
		$type = self::current_type();
		return isset( self::$by_type[ $type ] ) ? self::$by_type[ $type ] : array();
	}

	public static function add_columns( $columns ) {
		foreach ( self::fields_for_current() as $name => $field ) {
			$columns[ 'mfbe_' . $name ] = '' !== $field['label'] ? $field['label'] : $name;
		}
		return $columns;
	}

	public static function sortable_columns( $columns ) {
		foreach ( self::fields_for_current() as $name => $field ) {
			if ( ! empty( $field['sortable'] ) ) {
				$columns[ 'mfbe_' . $name ] = 'mfbe_' . $name;
			}
		}
		return $columns;
	}

	public static function render_column( $column, $post_id ) {
		if ( 0 !== strpos( $column, 'mfbe_' ) ) {
			return;
		}

		$fields = self::fields_for_current();
		$name   = substr( $column, 5 );
		if ( ! isset( $fields[ $name ] ) ) {
			return;
		}

		$field = $fields[ $name ];
		$value = MFBE_Fields::get_value( $name, $post_id );

		if ( MFBE_Fields::is_empty_value( $value ) ) {
			echo '<span aria-hidden="true">—</span>';
			return;
		}

		switch ( $field['type'] ) {
			case 'image':
				echo wp_kses_post( wp_get_attachment_image( absint( $value ), array( 40, 40 ) ) );
				break;

			case 'gallery':
				$count = count( MFBE_Fields::ids_from_value( $value ) );
				/* translators: %s: number of images */
				echo esc_html( sprintf( __( 'Images: %s', 'nazari-metafield-for-elementor' ), number_format_i18n( $count ) ) );
				break;

			case 'wysiwyg':
			case 'textarea':
			case 'oembed':
				echo esc_html( wp_trim_words( wp_strip_all_tags( MFBE_Fields::to_text( $field, $value ) ), 10 ) );
				break;

			case 'switcher':
				echo 'true' === $value ? '<span class="dashicons dashicons-yes-alt" style="color:#12805c" title="' . esc_attr__( 'Yes', 'nazari-metafield-for-elementor' ) . '"></span>' : '<span class="dashicons dashicons-minus" style="color:#8c8f94" title="' . esc_attr__( 'No', 'nazari-metafield-for-elementor' ) . '"></span>';
				break;

			case 'color':
				printf(
					'<span style="display:inline-block;width:16px;height:16px;border:1px solid #c3c4c7;border-radius:3px;vertical-align:middle;background:%1$s"></span> %1$s',
					esc_attr( (string) $value )
				);
				break;

			default:
				echo esc_html( MFBE_Fields::to_text( $field, $value ) );
		}
	}

	/* ------------------------------------------------------------------ *
	 * Filters and sorting
	 * ------------------------------------------------------------------ */

	private static function filter_options( array $field ) {
		if ( 'switcher' === $field['type'] ) {
			return array(
				'true'  => __( 'Yes', 'nazari-metafield-for-elementor' ),
				'false' => __( 'No', 'nazari-metafield-for-elementor' ),
			);
		}
		return MFBE_Fields::parse_options( $field['options'] );
	}

	public static function render_filters( $post_type ) {
		if ( empty( self::$by_type[ $post_type ] ) ) {
			return;
		}

		foreach ( self::$by_type[ $post_type ] as $name => $field ) {
			if ( ! in_array( $field['type'], self::FILTER_TYPES, true ) ) {
				continue;
			}

			$param   = 'mfbe_f_' . $name;
			$current = isset( $_GET[ $param ] ) ? sanitize_text_field( wp_unslash( $_GET[ $param ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification -- read-only list filter.
			$label   = '' !== $field['label'] ? $field['label'] : $name;

			printf( '<select name="%1$s" aria-label="%2$s">', esc_attr( $param ), esc_attr( $label ) );
			/* translators: %s: field label */
			printf( '<option value="">%s</option>', esc_html( sprintf( __( 'All: %s', 'nazari-metafield-for-elementor' ), $label ) ) );
			foreach ( self::filter_options( $field ) as $value => $option_label ) {
				printf(
					'<option value="%1$s"%2$s>%3$s</option>',
					esc_attr( (string) $value ),
					selected( $current, (string) $value, false ),
					esc_html( $option_label )
				);
			}
			echo '</select>';
		}
	}

	/**
	 * Apply the chosen sort order and filters to the main list query.
	 */
	public static function apply_query( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$post_type = $query->get( 'post_type' );
		if ( ! is_string( $post_type ) || ! isset( self::$by_type[ $post_type ] ) ) {
			return;
		}

		$fields     = self::$by_type[ $post_type ];
		$meta_query = (array) $query->get( 'meta_query' );

		// Filters.
		foreach ( $fields as $name => $field ) {
			$param = 'mfbe_f_' . $name;
			if ( ! in_array( $field['type'], self::FILTER_TYPES, true ) || empty( $_GET[ $param ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- read-only list filter.
				continue;
			}
			$wanted  = sanitize_text_field( wp_unslash( $_GET[ $param ] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			$allowed = array_map( 'strval', array_keys( self::filter_options( $field ) ) );
			if ( in_array( $wanted, $allowed, true ) ) {
				$meta_query[] = array(
					'key'   => $name,
					'value' => $wanted,
				);
			}
		}

		// Sorting.
		$orderby = $query->get( 'orderby' );
		if ( is_string( $orderby ) && 0 === strpos( $orderby, 'mfbe_' ) ) {
			$name = substr( $orderby, 5 );
			if ( isset( $fields[ $name ] ) && ! empty( $fields[ $name ]['sortable'] ) ) {
				self::$sort = array(
					'key'     => $name,
					'numeric' => in_array( $fields[ $name ]['type'], array( 'number', 'range' ), true ),
					'order'   => 'DESC' === strtoupper( (string) $query->get( 'order' ) ) ? 'DESC' : 'ASC',
				);
				$query->set( 'orderby', 'none' );
				add_filter( 'posts_clauses', array( __CLASS__, 'sort_clauses' ), 10, 2 );
			}
		}

		if ( $meta_query ) {
			$query->set( 'meta_query', $meta_query ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- admin list only, chosen by the user.
		}
	}

	/**
	 * Sort by one meta key without hiding posts that have no value (they go last).
	 */
	public static function sort_clauses( $clauses, $query ) {
		global $wpdb;

		remove_filter( 'posts_clauses', array( __CLASS__, 'sort_clauses' ), 10 );

		if ( ! self::$sort || ! $query->is_main_query() ) {
			return $clauses;
		}

		$sort  = self::$sort;
		$cast  = $sort['numeric'] ? 'CAST(mfbe_sort.meta_value AS DECIMAL(20,6))' : 'mfbe_sort.meta_value';
		$order = $sort['order'];

		$clauses['join']   .= $wpdb->prepare( " LEFT JOIN {$wpdb->postmeta} AS mfbe_sort ON ( {$wpdb->posts}.ID = mfbe_sort.post_id AND mfbe_sort.meta_key = %s )", $sort['key'] ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$clauses['orderby'] = "( mfbe_sort.meta_value IS NULL ) ASC, {$cast} {$order}, {$wpdb->posts}.ID DESC";

		self::$sort = null;
		return $clauses;
	}
}
