<?php
defined( 'ABSPATH' ) || exit;

/**
 * Query Loop rules: filter and sort the Query ID based loops (Elementor Pro Posts / Loop Grid)
 * by meta fields, without writing code. Rules are kept in the "mfbe_queries" option.
 */
class MFBE_Queries {

	const OPTION = 'mfbe_queries';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'hook_rules' ), 20 );
		add_action( 'admin_post_mfbe_save_queries', array( __CLASS__, 'handle_save' ) );
	}

	/* ------------------------------------------------------------------ *
	 * Data
	 * ------------------------------------------------------------------ */

	public static function rules() {
		$rules = get_option( self::OPTION, array() );
		return is_array( $rules ) ? array_values( $rules ) : array();
	}

	private static function sort_choices() {
		return array(
			''         => __( 'Keep default order', 'nazari-metafield-for-elementor' ),
			'asc'      => __( 'Field value, ascending', 'nazari-metafield-for-elementor' ),
			'desc'     => __( 'Field value, descending', 'nazari-metafield-for-elementor' ),
		);
	}

	public static function sanitize_rule( $rule ) {
		if ( ! is_array( $rule ) ) {
			return null;
		}

		$id    = isset( $rule['query_id'] ) ? sanitize_key( $rule['query_id'] ) : '';
		$field = isset( $rule['field'] ) ? MFBE_Fields::sanitize_name( $rule['field'] ) : '';
		if ( '' === $id || '' === $field ) {
			return null;
		}

		$sort = isset( $rule['sort'] ) && isset( self::sort_choices()[ $rule['sort'] ] ) ? $rule['sort'] : '';

		return array(
			'query_id' => $id,
			'field'    => $field,
			'operator' => MFBE_Conditions::sanitize_operator( isset( $rule['operator'] ) ? $rule['operator'] : 'eq' ),
			'value'    => isset( $rule['value'] ) ? sanitize_text_field( (string) $rule['value'] ) : '',
			'sort'     => $sort,
		);
	}

	/* ------------------------------------------------------------------ *
	 * Applying the rules
	 * ------------------------------------------------------------------ */

	public static function hook_rules() {
		$seen = array();
		foreach ( self::rules() as $rule ) {
			if ( isset( $seen[ $rule['query_id'] ] ) ) {
				continue;
			}
			$seen[ $rule['query_id'] ] = true;
			add_action( 'elementor/query/' . $rule['query_id'], array( __CLASS__, 'apply' ) );
		}
	}

	/**
	 * Runs on "elementor/query/{query_id}" and applies every rule that targets this query.
	 *
	 * @param WP_Query $query Query being built.
	 */
	public static function apply( $query ) {
		$hook = current_action();
		$id   = 0 === strpos( $hook, 'elementor/query/' ) ? substr( $hook, strlen( 'elementor/query/' ) ) : '';

		foreach ( self::rules() as $rule ) {
			if ( $rule['query_id'] === $id ) {
				self::apply_rule( $query, $rule );
			}
		}
	}

	public static function apply_rule( $query, array $rule ) {
		$key = $rule['field'];

		$clause = null;
		switch ( $rule['operator'] ) {
			case 'eq':
				$clause = array( 'key' => $key, 'value' => $rule['value'], 'compare' => '=' );
				break;
			case 'neq':
				$clause = array( 'key' => $key, 'value' => $rule['value'], 'compare' => '!=' );
				break;
			case 'contains':
				$clause = array( 'key' => $key, 'value' => $rule['value'], 'compare' => 'LIKE' );
				break;
			case 'gt':
				$clause = array( 'key' => $key, 'value' => $rule['value'], 'compare' => '>', 'type' => 'NUMERIC' );
				break;
			case 'lt':
				$clause = array( 'key' => $key, 'value' => $rule['value'], 'compare' => '<', 'type' => 'NUMERIC' );
				break;
			case 'not_empty':
				$clause = array(
					'relation' => 'AND',
					array( 'key' => $key, 'compare' => 'EXISTS' ),
					array( 'key' => $key, 'value' => '', 'compare' => '!=' ),
				);
				break;
			case 'empty':
				$clause = array(
					'relation' => 'OR',
					array( 'key' => $key, 'compare' => 'NOT EXISTS' ),
					array( 'key' => $key, 'value' => '', 'compare' => '=' ),
				);
				break;
		}

		if ( $clause ) {
			$meta_query = (array) $query->get( 'meta_query' );
			$meta_query[] = $clause;
			$query->set( 'meta_query', $meta_query ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- the whole point of this rule.
		}

		if ( 'asc' === $rule['sort'] || 'desc' === $rule['sort'] ) {
			$field   = MFBE_Registry::find_field( $key );
			$numeric = $field && in_array( $field['type'], array( 'number', 'range' ), true );
			$query->set( 'meta_key', $key ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$query->set( 'orderby', $numeric ? 'meta_value_num' : 'meta_value' );
			$query->set( 'order', 'asc' === $rule['sort'] ? 'ASC' : 'DESC' );
		}
	}

	/* ------------------------------------------------------------------ *
	 * Saving
	 * ------------------------------------------------------------------ */

	public static function handle_save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'nazari-metafield-for-elementor' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'mfbe_save_queries' );

		$rules = array();
		if ( isset( $_POST['mfbe_rules'] ) && is_array( $_POST['mfbe_rules'] ) ) {
			foreach ( wp_unslash( $_POST['mfbe_rules'] ) as $raw ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized in sanitize_rule().
				$rule = self::sanitize_rule( $raw );
				if ( $rule ) {
					$rules[] = $rule;
				}
			}
		}
		update_option( self::OPTION, array_slice( $rules, 0, 50 ), false );

		wp_safe_redirect( MFBE_Admin::settings_url( 'queries', array( 'mfbe_notice' => 'saved' ) ) );
		exit;
	}

	/* ------------------------------------------------------------------ *
	 * Settings tab
	 * ------------------------------------------------------------------ */

	private static function render_row( $i, array $rule ) {
		$fields = MFBE_Registry::field_choices();
		?>
		<div class="mfbe-rule">
			<div class="mfbe-rule-grid">
				<label><span><?php esc_html_e( 'Query ID', 'nazari-metafield-for-elementor' ); ?></span>
					<input type="text" name="mfbe_rules[<?php echo esc_attr( $i ); ?>][query_id]" value="<?php echo esc_attr( $rule['query_id'] ); ?>" placeholder="my_query"></label>
				<label><span><?php esc_html_e( 'Field', 'nazari-metafield-for-elementor' ); ?></span>
					<select name="mfbe_rules[<?php echo esc_attr( $i ); ?>][field]">
						<?php foreach ( $fields as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>"<?php selected( $rule['field'], $value ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select></label>
				<label><span><?php esc_html_e( 'Condition', 'nazari-metafield-for-elementor' ); ?></span>
					<select name="mfbe_rules[<?php echo esc_attr( $i ); ?>][operator]">
						<?php foreach ( MFBE_Conditions::operators() as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>"<?php selected( $rule['operator'], $value ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select></label>
				<label><span><?php esc_html_e( 'Value', 'nazari-metafield-for-elementor' ); ?></span>
					<input type="text" name="mfbe_rules[<?php echo esc_attr( $i ); ?>][value]" value="<?php echo esc_attr( $rule['value'] ); ?>"></label>
				<label><span><?php esc_html_e( 'Order', 'nazari-metafield-for-elementor' ); ?></span>
					<select name="mfbe_rules[<?php echo esc_attr( $i ); ?>][sort]">
						<?php foreach ( self::sort_choices() as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>"<?php selected( $rule['sort'], $value ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select></label>
			</div>
			<button type="button" class="mfbe-btn mfbe-btn-light mfbe-rule-remove"><?php esc_html_e( 'Remove', 'nazari-metafield-for-elementor' ); ?></button>
		</div>
		<?php
	}

	public static function render_tab() {
		$rules = self::rules();
		$blank = array( 'query_id' => '', 'field' => '', 'operator' => 'eq', 'value' => '', 'sort' => '' );
		?>
		<div class="mfbe-columns">
			<div class="mfbe-col-main">
				<section class="mfbe-card">
					<header class="mfbe-card-head"><h3><?php esc_html_e( 'Loop query rules', 'nazari-metafield-for-elementor' ); ?></h3></header>
					<p><?php esc_html_e( 'Filter and sort an Elementor Posts or Loop Grid widget by a meta field. In the widget, open Query and type the same Query ID.', 'nazari-metafield-for-elementor' ); ?></p>

					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="mfbe-rules-form">
						<input type="hidden" name="action" value="mfbe_save_queries">
						<?php wp_nonce_field( 'mfbe_save_queries' ); ?>

						<div id="mfbe-rules">
							<?php
							foreach ( $rules as $i => $rule ) {
								self::render_row( $i, $rule );
							}
							?>
						</div>

						<template id="mfbe-rule-template">
							<?php self::render_row( '__INDEX__', $blank ); ?>
						</template>

						<p>
							<button type="button" class="mfbe-btn mfbe-btn-light" id="mfbe-rule-add"><?php esc_html_e( 'Add rule', 'nazari-metafield-for-elementor' ); ?></button>
							<button type="submit" class="mfbe-btn mfbe-btn-primary"><?php esc_html_e( 'Save rules', 'nazari-metafield-for-elementor' ); ?></button>
						</p>
					</form>
				</section>
			</div>

			<aside class="mfbe-col-side">
				<section class="mfbe-card mfbe-card-accent">
					<h3><?php esc_html_e( 'Good to know', 'nazari-metafield-for-elementor' ); ?></h3>
					<p><?php esc_html_e( 'The Query ID option is part of Elementor Pro (Posts, Portfolio and Loop Grid widgets). Several rules can share one Query ID; all of them apply.', 'nazari-metafield-for-elementor' ); ?></p>
				</section>
			</aside>
		</div>
		<?php
	}
}
