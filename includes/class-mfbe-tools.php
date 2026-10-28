<?php
defined( 'ABSPATH' ) || exit;

/**
 * Export / import of field groups (JSON) and duplicating a group.
 */
class MFBE_Tools {

	const MAX_IMPORT_BYTES = 1048576; // 1 MB.

	public static function init() {
		add_action( 'admin_post_mfbe_export', array( __CLASS__, 'handle_export' ) );
		add_action( 'admin_post_mfbe_import', array( __CLASS__, 'handle_import' ) );
		add_action( 'admin_post_mfbe_duplicate', array( __CLASS__, 'handle_duplicate' ) );
		add_filter( 'post_row_actions', array( __CLASS__, 'row_actions' ), 20, 2 );
	}

	private static function check_capability() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'nazari-metafield-for-elementor' ), '', array( 'response' => 403 ) );
		}
	}

	/* ------------------------------------------------------------------ *
	 * Row actions on the group list
	 * ------------------------------------------------------------------ */

	public static function row_actions( $actions, $post ) {
		if ( MFBE_Registry::CPT !== $post->post_type || ! current_user_can( 'manage_options' ) ) {
			return $actions;
		}

		$duplicate = wp_nonce_url( admin_url( 'admin-post.php?action=mfbe_duplicate&post=' . (int) $post->ID ), 'mfbe_duplicate_' . (int) $post->ID );
		$export    = wp_nonce_url( admin_url( 'admin-post.php?action=mfbe_export&group=' . (int) $post->ID ), 'mfbe_export' );

		$actions['mfbe_duplicate'] = '<a href="' . esc_url( $duplicate ) . '">' . esc_html__( 'Duplicate', 'nazari-metafield-for-elementor' ) . '</a>';
		$actions['mfbe_export']    = '<a href="' . esc_url( $export ) . '">' . esc_html__( 'Export', 'nazari-metafield-for-elementor' ) . '</a>';

		return $actions;
	}

	/* ------------------------------------------------------------------ *
	 * Data helpers
	 * ------------------------------------------------------------------ */

	private static function config_of( $post_id ) {
		$config = get_post_meta( $post_id, MFBE_Registry::META_KEY, true );
		return is_array( $config ) ? $config : array();
	}

	/**
	 * @param int[] $ids Group post IDs (empty = every group).
	 */
	private static function collect( array $ids = array() ) {
		$args = array(
			'post_type'      => MFBE_Registry::CPT,
			'post_status'    => array( 'publish', 'draft' ),
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		);
		if ( $ids ) {
			$args['post__in'] = $ids;
		}

		$out = array();
		foreach ( get_posts( $args ) as $post ) {
			$config = self::config_of( $post->ID );
			$out[]  = array(
				'title'      => get_the_title( $post ),
				'post_types' => array_values( (array) ( isset( $config['post_types'] ) ? $config['post_types'] : array() ) ),
				'context'    => isset( $config['context'] ) ? $config['context'] : 'normal',
				'priority'   => isset( $config['priority'] ) ? $config['priority'] : 'high',
				'fields'     => array_values( (array) ( isset( $config['fields'] ) ? $config['fields'] : array() ) ),
			);
		}
		return $out;
	}

	/**
	 * Validate one group coming from an import file and store it.
	 *
	 * @return int|false New group ID.
	 */
	private static function create_group( $group, $status = 'publish', $title_suffix = '' ) {
		if ( ! is_array( $group ) || empty( $group['fields'] ) || ! is_array( $group['fields'] ) ) {
			return false;
		}

		$allowed_types = array_keys( MFBE_Groups::selectable_post_types() );
		$post_types    = array();
		if ( ! empty( $group['post_types'] ) && is_array( $group['post_types'] ) ) {
			$post_types = array_values( array_intersect( array_map( 'sanitize_key', $group['post_types'] ), $allowed_types ) );
		}

		$context  = isset( $group['context'] ) && in_array( $group['context'], array( 'normal', 'side', 'advanced' ), true ) ? $group['context'] : 'normal';
		$priority = isset( $group['priority'] ) && in_array( $group['priority'], array( 'high', 'default', 'low' ), true ) ? $group['priority'] : 'high';

		$fields = array();
		$seen   = array();
		foreach ( $group['fields'] as $field ) {
			$field = MFBE_Fields::normalize( $field );
			if ( '' === $field['name'] || isset( $seen[ $field['name'] ] ) ) {
				continue;
			}
			$seen[ $field['name'] ] = true;
			$fields[]               = $field;
		}
		if ( ! $fields ) {
			return false;
		}

		$title = isset( $group['title'] ) ? sanitize_text_field( (string) $group['title'] ) : '';
		$title = ( '' !== $title ? $title : __( 'Imported group', 'nazari-metafield-for-elementor' ) ) . $title_suffix;

		$new_id = wp_insert_post(
			array(
				'post_type'   => MFBE_Registry::CPT,
				'post_status' => $status,
				'post_title'  => $title,
			)
		);
		if ( ! $new_id || is_wp_error( $new_id ) ) {
			return false;
		}

		update_post_meta(
			$new_id,
			MFBE_Registry::META_KEY,
			array(
				'post_types' => $post_types,
				'context'    => $context,
				'priority'   => $priority,
				'fields'     => $fields,
			)
		);

		MFBE_Registry::reset();
		return (int) $new_id;
	}

	/* ------------------------------------------------------------------ *
	 * Handlers
	 * ------------------------------------------------------------------ */

	public static function handle_export() {
		self::check_capability();
		check_admin_referer( 'mfbe_export' );

		$ids = array();
		if ( isset( $_GET['group'] ) ) {
			$ids = array( absint( $_GET['group'] ) );
		}

		$data = array(
			'plugin'   => 'nazari-metafield-for-elementor',
			'version'  => MFBE_VERSION,
			'exported' => gmdate( 'c' ),
			'groups'   => self::collect( $ids ),
		);

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="nazari-metafield-groups-' . gmdate( 'Y-m-d' ) . '.json"' );
		echo wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		exit;
	}

	public static function handle_import() {
		self::check_capability();
		check_admin_referer( 'mfbe_import' );

		$back = MFBE_Admin::settings_url( 'tools' );

		$tmp  = isset( $_FILES['mfbe_import_file']['tmp_name'] ) ? sanitize_text_field( wp_unslash( $_FILES['mfbe_import_file']['tmp_name'] ) ) : '';
		$name = isset( $_FILES['mfbe_import_file']['name'] ) ? sanitize_file_name( wp_unslash( $_FILES['mfbe_import_file']['name'] ) ) : '';
		$size = isset( $_FILES['mfbe_import_file']['size'] ) ? absint( $_FILES['mfbe_import_file']['size'] ) : 0;

		if ( '' === $tmp || ! is_uploaded_file( $tmp ) || 'json' !== strtolower( pathinfo( $name, PATHINFO_EXTENSION ) ) || $size > self::MAX_IMPORT_BYTES ) {
			wp_safe_redirect( add_query_arg( 'mfbe_notice', 'import_invalid', $back ) );
			exit;
		}

		$data = wp_json_file_decode( $tmp, array( 'associative' => true ) );
		if ( ! is_array( $data ) || empty( $data['groups'] ) || ! is_array( $data['groups'] ) ) {
			wp_safe_redirect( add_query_arg( 'mfbe_notice', 'import_invalid', $back ) );
			exit;
		}

		$existing = array();
		foreach ( MFBE_Registry::groups() as $group ) {
			foreach ( $group['fields'] as $key => $unused ) {
				$existing[ $key ] = true;
			}
		}

		$imported = 0;
		foreach ( $data['groups'] as $group ) {
			// A group that reuses meta keys of an existing one is imported as a draft to avoid clashes.
			$clash  = false;
			if ( is_array( $group ) && ! empty( $group['fields'] ) && is_array( $group['fields'] ) ) {
				foreach ( $group['fields'] as $field ) {
					$key = is_array( $field ) && isset( $field['name'] ) ? MFBE_Fields::sanitize_name( $field['name'] ) : '';
					if ( '' !== $key && isset( $existing[ $key ] ) ) {
						$clash = true;
						break;
					}
				}
			}

			if ( self::create_group( $group, $clash ? 'draft' : 'publish' ) ) {
				++$imported;
			}
		}

		$notice = $imported ? 'imported' : 'import_empty';
		wp_safe_redirect( add_query_arg( array( 'mfbe_notice' => $notice, 'mfbe_count' => $imported ), $back ) );
		exit;
	}

	public static function handle_duplicate() {
		self::check_capability();
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- verified on the next line.
		check_admin_referer( 'mfbe_duplicate_' . $post_id );

		$post = $post_id ? get_post( $post_id ) : null;
		if ( ! $post || MFBE_Registry::CPT !== $post->post_type ) {
			wp_safe_redirect( admin_url( 'edit.php?post_type=' . MFBE_Registry::CPT ) );
			exit;
		}

		$config = self::config_of( $post_id );
		$config['title'] = get_the_title( $post );

		// The copy starts as a draft: it reuses the same meta keys until you change them.
		$new_id = self::create_group( $config, 'draft', ' ' . __( '(copy)', 'nazari-metafield-for-elementor' ) );

		wp_safe_redirect( $new_id ? get_edit_post_link( $new_id, 'raw' ) : admin_url( 'edit.php?post_type=' . MFBE_Registry::CPT ) );
		exit;
	}

	/* ------------------------------------------------------------------ *
	 * Settings tab
	 * ------------------------------------------------------------------ */

	public static function render_tab() {
		$export_url = wp_nonce_url( admin_url( 'admin-post.php?action=mfbe_export' ), 'mfbe_export' );
		?>
		<div class="mfbe-columns">
			<div class="mfbe-col-main">
				<section class="mfbe-card">
					<header class="mfbe-card-head"><h3><?php esc_html_e( 'Export field groups', 'nazari-metafield-for-elementor' ); ?></h3></header>
					<p><?php esc_html_e( 'Download all your field groups as a JSON file. Use it as a backup or to move them to another site.', 'nazari-metafield-for-elementor' ); ?></p>
					<p><a class="mfbe-btn mfbe-btn-primary" href="<?php echo esc_url( $export_url ); ?>"><?php esc_html_e( 'Download JSON', 'nazari-metafield-for-elementor' ); ?></a></p>
				</section>

				<section class="mfbe-card">
					<header class="mfbe-card-head"><h3><?php esc_html_e( 'Import field groups', 'nazari-metafield-for-elementor' ); ?></h3></header>
					<p><?php esc_html_e( 'Choose a JSON file exported from this plugin. Groups that reuse the meta keys of an existing group are imported as drafts so nothing is overwritten.', 'nazari-metafield-for-elementor' ); ?></p>
					<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mfbe-form">
						<input type="hidden" name="action" value="mfbe_import">
						<?php wp_nonce_field( 'mfbe_import' ); ?>
						<div class="mfbe-form-row">
							<label for="mfbe_import_file"><?php esc_html_e( 'JSON file', 'nazari-metafield-for-elementor' ); ?></label>
							<input type="file" id="mfbe_import_file" name="mfbe_import_file" accept=".json,application/json" required>
						</div>
						<p><button type="submit" class="mfbe-btn mfbe-btn-primary"><?php esc_html_e( 'Import', 'nazari-metafield-for-elementor' ); ?></button></p>
					</form>
				</section>
			</div>

			<aside class="mfbe-col-side">
				<section class="mfbe-card mfbe-card-accent">
					<h3><?php esc_html_e( 'Tip', 'nazari-metafield-for-elementor' ); ?></h3>
					<p><?php esc_html_e( 'In the list of field groups, hover a row to Duplicate or Export just that group.', 'nazari-metafield-for-elementor' ); ?></p>
				</section>
			</aside>
		</div>
		<?php
	}
}
