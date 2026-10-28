<?php
defined( 'ABSPATH' ) || exit;

/**
 * The "Field Groups" admin area: a custom post type plus the visual builder.
 */
class MFBE_Groups {

	public static function init() {
		$cpt = MFBE_Registry::CPT;

		add_action( 'init', array( __CLASS__, 'register_cpt' ) );
		add_action( 'add_meta_boxes_' . $cpt, array( __CLASS__, 'add_boxes' ) );
		add_action( 'save_post_' . $cpt, array( __CLASS__, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_filter( 'manage_' . $cpt . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . $cpt . '_posts_custom_column', array( __CLASS__, 'column_content' ), 10, 2 );
		add_filter( 'post_row_actions', array( __CLASS__, 'row_actions' ), 10, 2 );
	}

	public static function register_cpt() {
		$caps = array();
		foreach ( array(
			'edit_post', 'read_post', 'delete_post', 'edit_posts', 'edit_others_posts', 'delete_posts',
			'publish_posts', 'read_private_posts', 'create_posts', 'delete_others_posts',
			'delete_published_posts', 'edit_published_posts', 'delete_private_posts', 'edit_private_posts',
		) as $cap ) {
			$caps[ $cap ] = 'manage_options';
		}

		register_post_type(
			MFBE_Registry::CPT,
			array(
				'labels'          => array(
					'name'               => __( 'Field Groups', 'nazari-metafield-for-elementor' ),
					'singular_name'      => __( 'Field Group', 'nazari-metafield-for-elementor' ),
					'menu_name'          => __( 'Field Groups', 'nazari-metafield-for-elementor' ),
					'all_items'          => __( 'Field Groups', 'nazari-metafield-for-elementor' ),
					'add_new'            => __( 'Add New', 'nazari-metafield-for-elementor' ),
					'add_new_item'       => __( 'Add New Field Group', 'nazari-metafield-for-elementor' ),
					'edit_item'          => __( 'Edit Field Group', 'nazari-metafield-for-elementor' ),
					'new_item'           => __( 'New Field Group', 'nazari-metafield-for-elementor' ),
					'view_item'          => __( 'View Field Group', 'nazari-metafield-for-elementor' ),
					'search_items'       => __( 'Search Field Groups', 'nazari-metafield-for-elementor' ),
					'not_found'          => __( 'No field groups found.', 'nazari-metafield-for-elementor' ),
					'not_found_in_trash' => __( 'No field groups found in Trash.', 'nazari-metafield-for-elementor' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => is_admin() ? 'nazari-metafield' : false,
				'show_in_rest'    => false,
				'supports'        => array( 'title' ),
				'capability_type' => 'post',
				'capabilities'    => $caps,
				'map_meta_cap'    => false,
				'rewrite'         => false,
				'query_var'       => false,
			)
		);
	}

	/* ------------------------------------------------------------------ *
	 * Helpers
	 * ------------------------------------------------------------------ */

	private static function is_group_screen() {
		$screen = get_current_screen();
		return $screen && MFBE_Registry::CPT === $screen->post_type;
	}

	public static function selectable_post_types() {
		$exclude = array(
			MFBE_Registry::CPT,
			'attachment',
			'elementor_library',
			'wp_block',
			'wp_template',
			'wp_template_part',
			'wp_navigation',
			'wp_global_styles',
			'wp_font_family',
			'wp_font_face',
		);

		$types = get_post_types( array( 'show_ui' => true ), 'objects' );
		$out   = array();
		foreach ( $types as $slug => $object ) {
			if ( ! in_array( $slug, $exclude, true ) ) {
				$out[ $slug ] = $object->labels->name;
			}
		}
		return $out;
	}

	private static function get_config( $post_id ) {
		$config = get_post_meta( $post_id, MFBE_Registry::META_KEY, true );
		return wp_parse_args(
			is_array( $config ) ? $config : array(),
			array(
				'post_types' => array( 'post' ),
				'context'    => 'normal',
				'priority'   => 'high',
				'fields'     => array(),
			)
		);
	}

	/* ------------------------------------------------------------------ *
	 * Meta boxes of the builder screen
	 * ------------------------------------------------------------------ */

	public static function add_boxes() {
		add_meta_box( 'mfbe_group_settings', __( 'Group settings', 'nazari-metafield-for-elementor' ), array( __CLASS__, 'render_settings' ), MFBE_Registry::CPT, 'normal', 'high' );
		add_meta_box( 'mfbe_group_fields', __( 'Fields', 'nazari-metafield-for-elementor' ), array( __CLASS__, 'render_builder' ), MFBE_Registry::CPT, 'normal', 'high' );
		add_meta_box( 'mfbe_group_help', __( 'Using the fields in Elementor', 'nazari-metafield-for-elementor' ), array( __CLASS__, 'render_help' ), MFBE_Registry::CPT, 'side', 'default' );
	}

	public static function render_settings( $post ) {
		wp_nonce_field( 'mfbe_save_group', 'mfbe_group_nonce' );
		$config = self::get_config( $post->ID );

		$contexts = array(
			'normal'   => __( 'Normal (below the editor)', 'nazari-metafield-for-elementor' ),
			'side'     => __( 'Side', 'nazari-metafield-for-elementor' ),
			'advanced' => __( 'Advanced', 'nazari-metafield-for-elementor' ),
		);
		$priorities = array(
			'high'    => __( 'High', 'nazari-metafield-for-elementor' ),
			'default' => __( 'Default', 'nazari-metafield-for-elementor' ),
			'low'     => __( 'Low', 'nazari-metafield-for-elementor' ),
		);
		?>
		<table class="form-table mfbe-settings" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Show on post types', 'nazari-metafield-for-elementor' ); ?></th>
				<td>
					<?php foreach ( self::selectable_post_types() as $slug => $label ) : ?>
						<label class="mfbe-inline-check">
							<input type="checkbox" name="mfbe_post_types[]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( in_array( $slug, (array) $config['post_types'], true ) ); ?>>
							<?php echo esc_html( $label ); ?>
						</label>
					<?php endforeach; ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="mfbe_context"><?php esc_html_e( 'Position', 'nazari-metafield-for-elementor' ); ?></label></th>
				<td>
					<select id="mfbe_context" name="mfbe_context">
						<?php foreach ( $contexts as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $config['context'], $value ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="mfbe_priority"><?php esc_html_e( 'Priority', 'nazari-metafield-for-elementor' ); ?></label></th>
				<td>
					<select id="mfbe_priority" name="mfbe_priority">
						<?php foreach ( $priorities as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $config['priority'], $value ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
		</table>
		<?php
	}

	public static function render_builder() {
		?>
		<div id="mfbe-builder">
			<input type="hidden" name="mfbe_fields_json" id="mfbe-fields-json" value="">
			<p class="mfbe-empty description"><?php esc_html_e( 'No fields yet. Click “Add field” to create the first one.', 'nazari-metafield-for-elementor' ); ?></p>
			<div id="mfbe-toolbar" class="mfbe-toolbar" style="display:none">
				<button type="button" class="button-link" id="mfbe-expand-all"><?php esc_html_e( 'Expand all', 'nazari-metafield-for-elementor' ); ?></button>
				<span aria-hidden="true">·</span>
				<button type="button" class="button-link" id="mfbe-collapse-all"><?php esc_html_e( 'Collapse all', 'nazari-metafield-for-elementor' ); ?></button>
			</div>
			<div id="mfbe-fields-list"></div>
			<p><button type="button" class="button button-primary" id="mfbe-add-field"><?php esc_html_e( 'Add field', 'nazari-metafield-for-elementor' ); ?></button></p>
		</div>
		<?php
	}

	public static function render_help() {
		?>
		<p><strong><?php esc_html_e( 'Widget', 'nazari-metafield-for-elementor' ); ?></strong><br>
			<?php esc_html_e( 'Drag the “Meta Field” widget into any page, template or loop item and pick a field.', 'nazari-metafield-for-elementor' ); ?></p>
		<p><strong><?php esc_html_e( 'Dynamic tags', 'nazari-metafield-for-elementor' ); ?></strong><br>
			<?php esc_html_e( 'With Elementor Pro, click the dynamic (database) icon of any control and choose “Meta Fields”.', 'nazari-metafield-for-elementor' ); ?></p>
		<p><strong><?php esc_html_e( 'Shortcode', 'nazari-metafield-for-elementor' ); ?></strong><br>
			<code>[mfbe field="meta_key"]</code></p>
		<?php
	}

	/* ------------------------------------------------------------------ *
	 * Saving
	 * ------------------------------------------------------------------ */

	public static function save( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! isset( $_POST['mfbe_group_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mfbe_group_nonce'] ) ), 'mfbe_save_group' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$allowed_types = array_keys( self::selectable_post_types() );
		$post_types    = array();
		if ( isset( $_POST['mfbe_post_types'] ) ) {
			$submitted_types = array_map( 'sanitize_key', (array) wp_unslash( $_POST['mfbe_post_types'] ) );
			$post_types      = array_values( array_intersect( $submitted_types, $allowed_types ) );
		}

		$context = isset( $_POST['mfbe_context'] ) ? sanitize_key( wp_unslash( $_POST['mfbe_context'] ) ) : 'normal';
		if ( ! in_array( $context, array( 'normal', 'side', 'advanced' ), true ) ) {
			$context = 'normal';
		}

		$priority = isset( $_POST['mfbe_priority'] ) ? sanitize_key( wp_unslash( $_POST['mfbe_priority'] ) ) : 'high';
		if ( ! in_array( $priority, array( 'high', 'default', 'low' ), true ) ) {
			$priority = 'high';
		}

		$fields = array();
		$seen   = array();
		if ( isset( $_POST['mfbe_fields_json'] ) ) {
			$decoded = json_decode( wp_unslash( $_POST['mfbe_fields_json'] ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- each field is normalized below.
			if ( is_array( $decoded ) ) {
				foreach ( $decoded as $field ) {
					$field = MFBE_Fields::normalize( $field );
					if ( '' === $field['name'] || isset( $seen[ $field['name'] ] ) ) {
						continue;
					}
					$seen[ $field['name'] ] = true;
					$fields[]               = $field;
				}
			}
		}

		update_post_meta(
			$post_id,
			MFBE_Registry::META_KEY,
			array(
				'post_types' => $post_types,
				'context'    => $context,
				'priority'   => $priority,
				'fields'     => $fields,
			)
		);

		MFBE_Registry::reset();
	}

	/* ------------------------------------------------------------------ *
	 * Assets
	 * ------------------------------------------------------------------ */

	public static function enqueue( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || ! self::is_group_screen() ) {
			return;
		}

		wp_enqueue_style( 'mfbe-builder', MFBE_URL . 'assets/builder.css', array(), MFBE_VERSION );
		wp_enqueue_script( 'mfbe-builder', MFBE_URL . 'assets/builder.js', array( 'jquery', 'jquery-ui-sortable' ), MFBE_VERSION, true );

		$post   = get_post();
		$config = $post ? self::get_config( $post->ID ) : array( 'fields' => array() );

		wp_localize_script(
			'mfbe-builder',
			'MFBE_BUILDER',
			array(
				'fields'    => array_values( (array) $config['fields'] ),
				'types'     => MFBE_Fields::types(),
				'optionTypes' => array( 'select', 'radio', 'checkbox' ),
				'widths'    => array( '100', '75', '66', '50', '33', '25' ),
				'operators' => MFBE_Conditions::operators(),
				'lengthTypes' => array( 'text', 'textarea', 'email', 'url' ),
				'numberTypes' => array( 'number', 'range' ),
				'listTypes'   => array( 'text', 'number', 'email', 'url', 'select', 'radio', 'switcher', 'date', 'time', 'datetime', 'range', 'color' ),
				'icons'     => array(
					'text' => 'editor-textcolor', 'textarea' => 'text-page', 'number' => 'editor-ol', 'email' => 'email',
					'url' => 'admin-links', 'select' => 'arrow-down-alt2', 'radio' => 'marker', 'checkbox' => 'yes-alt',
					'switcher' => 'leftright', 'date' => 'calendar-alt', 'color' => 'art', 'wysiwyg' => 'edit',
					'image' => 'format-image', 'gallery' => 'format-gallery', 'file' => 'media-default', 'link' => 'admin-links',
					'time' => 'clock', 'datetime' => 'calendar', 'oembed' => 'video-alt3', 'range' => 'image-flip-horizontal',
				),
				'i18n'      => array(
					'newField'      => __( 'New field', 'nazari-metafield-for-elementor' ),
					'edit'          => __( 'Edit', 'nazari-metafield-for-elementor' ),
					'close'         => __( 'Close', 'nazari-metafield-for-elementor' ),
					'remove'        => __( 'Remove', 'nazari-metafield-for-elementor' ),
					'confirmRemove' => __( 'Remove this field?', 'nazari-metafield-for-elementor' ),
					'label'         => __( 'Label', 'nazari-metafield-for-elementor' ),
					'name'          => __( 'Meta key', 'nazari-metafield-for-elementor' ),
					'nameHelp'      => __( 'Lowercase letters, numbers and underscores only. This is the key saved in the database.', 'nazari-metafield-for-elementor' ),
					'type'          => __( 'Type', 'nazari-metafield-for-elementor' ),
					'width'         => __( 'Width (%)', 'nazari-metafield-for-elementor' ),
					'description'   => __( 'Description', 'nazari-metafield-for-elementor' ),
					'placeholder'   => __( 'Placeholder', 'nazari-metafield-for-elementor' ),
					'default'       => __( 'Default value', 'nazari-metafield-for-elementor' ),
					'options'       => __( 'Options', 'nazari-metafield-for-elementor' ),
					'optionsHelp'   => __( 'One option per line, written as value : label', 'nazari-metafield-for-elementor' ),
					'min'           => __( 'Min', 'nazari-metafield-for-elementor' ),
					'max'           => __( 'Max', 'nazari-metafield-for-elementor' ),
					'step'          => __( 'Step', 'nazari-metafield-for-elementor' ),
					'sectionValidation' => __( 'Validation', 'nazari-metafield-for-elementor' ),
					'sectionAdmin' => __( 'Admin list', 'nazari-metafield-for-elementor' ),
					'sectionLogic' => __( 'Conditional logic', 'nazari-metafield-for-elementor' ),
					'required' => __( 'Required', 'nazari-metafield-for-elementor' ),
					'unique' => __( 'Unique value', 'nazari-metafield-for-elementor' ),
					'minLength' => __( 'Min length', 'nazari-metafield-for-elementor' ),
					'maxLength' => __( 'Max length', 'nazari-metafield-for-elementor' ),
					'showColumn' => __( 'Show as a column', 'nazari-metafield-for-elementor' ),
					'sortable' => __( 'Sortable column', 'nazari-metafield-for-elementor' ),
					'yes' => __( 'Yes', 'nazari-metafield-for-elementor' ),
					'no' => __( 'No', 'nazari-metafield-for-elementor' ),
					'condField' => __( 'Show this field only if', 'nazari-metafield-for-elementor' ),
					'condAlways' => __( '— Always show —', 'nazari-metafield-for-elementor' ),
					'condOp' => __( 'Condition', 'nazari-metafield-for-elementor' ),
					'condValue' => __( 'Value', 'nazari-metafield-for-elementor' ),
					'columnHelp' => __( 'Columns appear in the list of posts. Select, radio and switcher columns also get a filter dropdown.', 'nazari-metafield-for-elementor' ),
					'duplicate'     => __( 'Duplicate', 'nazari-metafield-for-elementor' ),
					'expandAll'     => __( 'Expand all', 'nazari-metafield-for-elementor' ),
					'collapseAll'   => __( 'Collapse all', 'nazari-metafield-for-elementor' ),
					'copy'          => __( 'Copy', 'nazari-metafield-for-elementor' ),
					'copied'        => __( 'Copied!', 'nazari-metafield-for-elementor' ),
					'shortcode'     => __( 'Shortcode', 'nazari-metafield-for-elementor' ),
					'errorKeys'     => __( 'Every field needs a unique meta key (lowercase letters, numbers and underscores).', 'nazari-metafield-for-elementor' ),
				),
			)
		);
	}

	/* ------------------------------------------------------------------ *
	 * List table
	 * ------------------------------------------------------------------ */

	public static function columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['mfbe_types']  = __( 'Post types', 'nazari-metafield-for-elementor' );
				$new['mfbe_fields'] = __( 'Fields', 'nazari-metafield-for-elementor' );
			}
		}
		return $new;
	}

	public static function column_content( $column, $post_id ) {
		if ( 'mfbe_types' !== $column && 'mfbe_fields' !== $column ) {
			return;
		}

		$config = self::get_config( $post_id );

		if ( 'mfbe_types' === $column ) {
			$labels = array();
			$all    = self::selectable_post_types();
			foreach ( (array) $config['post_types'] as $slug ) {
				$labels[] = isset( $all[ $slug ] ) ? $all[ $slug ] : $slug;
			}
			echo esc_html( $labels ? implode( ', ', $labels ) : '—' );
			return;
		}

		$keys = wp_list_pluck( (array) $config['fields'], 'name' );
		if ( ! $keys ) {
			echo '—';
			return;
		}
		foreach ( $keys as $key ) {
			echo '<code>' . esc_html( $key ) . '</code> ';
		}
	}

	public static function row_actions( $actions, $post ) {
		if ( MFBE_Registry::CPT === $post->post_type ) {
			unset( $actions['inline hide-if-no-js'] );
		}
		return $actions;
	}
}
