<?php
defined( 'ABSPATH' ) || exit;

/**
 * Branded admin area: top-level menu, dashboard, settings (general / feedback / about).
 */
class MFBE_Admin {

	const SLUG        = 'nazari-metafield';
	const SETTINGS    = 'nazari-metafield-settings';
	const OPTION      = 'mfbe_settings';
	const FEEDBACK_TO = 'sajjadnazari.ir@gmail.com';
	const AUTHOR      = 'Sajjad Nazari';
	const AUTHOR_URL  = 'https://github.com/SajjadNazari1995';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 5 );
		add_action( 'admin_menu', array( __CLASS__, 'menu_settings' ), 99 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'menu_icon_css' ) );
		add_action( 'in_admin_header', array( __CLASS__, 'brand_bar' ) );
		add_action( 'admin_post_mfbe_feedback', array( __CLASS__, 'handle_feedback' ) );
		add_action( 'admin_post_mfbe_save_settings', array( __CLASS__, 'handle_settings' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( MFBE_FILE ), array( __CLASS__, 'action_links' ) );
		add_filter( 'plugin_row_meta', array( __CLASS__, 'row_meta' ), 10, 2 );
		add_filter( 'admin_footer_text', array( __CLASS__, 'footer_text' ) );
	}

	/* ------------------------------------------------------------------ *
	 * Helpers
	 * ------------------------------------------------------------------ */

	public static function option( $key, $default = '' ) {
		$settings = get_option( self::OPTION, array() );
		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
	}

	private static function page() {
		return isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	}

	public static function is_plugin_screen() {
		if ( 0 === strpos( self::page(), self::SLUG ) ) {
			return true;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		return $screen && MFBE_Registry::CPT === $screen->post_type;
	}

	public static function dashboard_url() {
		return admin_url( 'admin.php?page=' . self::SLUG );
	}

	public static function settings_url( $tab = 'general', $extra = array() ) {
		return add_query_arg(
			array_merge( array( 'page' => self::SETTINGS, 'tab' => $tab ), $extra ),
			admin_url( 'admin.php' )
		);
	}

	private static function logo( $white = false ) {
		return MFBE_URL . 'assets/images/' . ( $white ? 'logo-white.png' : 'logo.png' );
	}

	/* ------------------------------------------------------------------ *
	 * Menu
	 * ------------------------------------------------------------------ */

	public static function menu() {
		add_menu_page(
			__( 'Elementor Metafield', 'nazari-metafield-for-elementor' ),
			__( 'Elementor Metafield', 'nazari-metafield-for-elementor' ),
			'manage_options',
			self::SLUG,
			array( __CLASS__, 'render_dashboard' ),
			'none',
			58
		);
		add_submenu_page(
			self::SLUG,
			__( 'Dashboard', 'nazari-metafield-for-elementor' ),
			__( 'Dashboard', 'nazari-metafield-for-elementor' ),
			'manage_options',
			self::SLUG,
			array( __CLASS__, 'render_dashboard' )
		);
	}

	public static function menu_settings() {
		add_submenu_page(
			self::SLUG,
			__( 'Settings', 'nazari-metafield-for-elementor' ),
			__( 'Settings', 'nazari-metafield-for-elementor' ),
			'manage_options',
			self::SETTINGS,
			array( __CLASS__, 'render_settings' )
		);
	}

	public static function action_links( $links ) {
		array_unshift(
			$links,
			'<a href="' . esc_url( self::dashboard_url() ) . '">' . esc_html__( 'Dashboard', 'nazari-metafield-for-elementor' ) . '</a>',
			'<a href="' . esc_url( self::settings_url() ) . '">' . esc_html__( 'Settings', 'nazari-metafield-for-elementor' ) . '</a>'
		);
		return $links;
	}

	public static function row_meta( $meta, $file ) {
		if ( plugin_basename( MFBE_FILE ) === $file ) {
			$meta[] = '<a href="' . esc_url( self::settings_url( 'feedback' ) ) . '">' . esc_html__( 'Send feedback', 'nazari-metafield-for-elementor' ) . '</a>';
		}
		return $meta;
	}

	public static function footer_text( $text ) {
		if ( ! self::is_plugin_screen() ) {
			return $text;
		}
		return sprintf(
			/* translators: %s: author link */
			esc_html__( 'Elementor Metafield — made by %s', 'nazari-metafield-for-elementor' ),
			'<a href="' . esc_url( self::AUTHOR_URL ) . '" target="_blank" rel="noopener">' . esc_html( self::AUTHOR ) . '</a>'
		);
	}

	/**
	 * Draws the logo emblem as a CSS mask, so it has the same size, alignment and colour
	 * (including hover / active / admin colour schemes) as the built-in font icons.
	 */
	public static function menu_icon_css() {
		$url = esc_url_raw( MFBE_URL . 'assets/images/menu-icon.png' );
		$css = '#adminmenu .toplevel_page_nazari-metafield div.wp-menu-image:before{content:"";display:block;width:20px;height:20px;margin:0 auto;padding:7px 0;box-sizing:content-box;background-color:currentColor;-webkit-mask:url("' . $url . '") center / 20px 20px no-repeat content-box;mask:url("' . $url . '") center / 20px 20px no-repeat content-box;}';
		wp_register_style( 'mfbe-menu-icon', false, array(), MFBE_VERSION );
		wp_enqueue_style( 'mfbe-menu-icon' );
		wp_add_inline_style( 'mfbe-menu-icon', $css );
	}

	/* ------------------------------------------------------------------ *
	 * Assets and brand bar
	 * ------------------------------------------------------------------ */

	public static function enqueue() {
		if ( ! self::is_plugin_screen() ) {
			return;
		}
		wp_enqueue_style( 'mfbe-admin', MFBE_URL . 'assets/admin.css', array(), MFBE_VERSION );
		wp_enqueue_script( 'mfbe-admin', MFBE_URL . 'assets/admin.js', array(), MFBE_VERSION, true );
		wp_localize_script(
			'mfbe-admin',
			'MFBE_ADMIN',
			array(
				'copied' => __( 'Copied!', 'nazari-metafield-for-elementor' ),
			)
		);
	}

	public static function brand_bar() {
		if ( ! self::is_plugin_screen() ) {
			return;
		}

		$page   = self::page();
		$screen = get_current_screen();
		$in_cpt = $screen && MFBE_Registry::CPT === $screen->post_type;

		$tabs = array(
			array( __( 'Dashboard', 'nazari-metafield-for-elementor' ), self::dashboard_url(), self::SLUG === $page ),
			array( __( 'Field Groups', 'nazari-metafield-for-elementor' ), admin_url( 'edit.php?post_type=' . MFBE_Registry::CPT ), $in_cpt ),
			array( __( 'Settings', 'nazari-metafield-for-elementor' ), self::settings_url(), self::SETTINGS === $page ),
		);
		?>
		<div class="mfbe-brandbar">
			<img class="mfbe-brandbar-logo" src="<?php echo esc_url( self::logo( true ) ); ?>" alt="">
			<div class="mfbe-brandbar-title">
				<strong><?php esc_html_e( 'Elementor Metafield', 'nazari-metafield-for-elementor' ); ?></strong>
				<span>Nazari Technologies</span>
			</div>
			<span class="mfbe-badge">v<?php echo esc_html( MFBE_VERSION ); ?></span>
			<nav class="mfbe-brandbar-nav">
				<?php foreach ( $tabs as $tab ) : ?>
					<a href="<?php echo esc_url( $tab[1] ); ?>" class="<?php echo $tab[2] ? 'is-active' : ''; ?>"><?php echo esc_html( $tab[0] ); ?></a>
				<?php endforeach; ?>
			</nav>
		</div>
		<?php
	}

	/* ------------------------------------------------------------------ *
	 * Dashboard
	 * ------------------------------------------------------------------ */

	public static function render_dashboard() {
		$groups      = MFBE_Registry::groups();
		$field_count = 0;
		$types_used  = array();
		foreach ( $groups as $group ) {
			$field_count += count( $group['fields'] );
			foreach ( (array) $group['post_types'] as $slug ) {
				$types_used[ $slug ] = true;
			}
		}

		$elementor     = did_action( 'elementor/loaded' );
		$elementor_pro = defined( 'ELEMENTOR_PRO_VERSION' );
		$new_url       = admin_url( 'post-new.php?post_type=' . MFBE_Registry::CPT );
		$type_labels   = MFBE_Groups::selectable_post_types();
		?>
		<div class="wrap mfbe-wrap">
			<h1 class="screen-reader-text"><?php esc_html_e( 'Elementor Metafield', 'nazari-metafield-for-elementor' ); ?></h1>

			<section class="mfbe-hero">
				<div class="mfbe-hero-text">
					<h2><?php esc_html_e( 'Build meta fields. Use them anywhere in Elementor.', 'nazari-metafield-for-elementor' ); ?></h2>
					<p><?php esc_html_e( 'Create field groups for posts and custom post types, then show the values with the Meta Field widget, dynamic tags or a shortcode.', 'nazari-metafield-for-elementor' ); ?></p>
					<p class="mfbe-hero-actions">
						<a class="mfbe-btn mfbe-btn-light" href="<?php echo esc_url( $new_url ); ?>"><?php esc_html_e( 'Create a field group', 'nazari-metafield-for-elementor' ); ?></a>
						<a class="mfbe-btn mfbe-btn-ghost" href="<?php echo esc_url( self::settings_url( 'feedback' ) ); ?>"><?php esc_html_e( 'Send feedback', 'nazari-metafield-for-elementor' ); ?></a>
					</p>
				</div>
				<img class="mfbe-hero-logo" src="<?php echo esc_url( self::logo( true ) ); ?>" alt="Nazari Technologies">
			</section>

			<section class="mfbe-stats">
				<div class="mfbe-stat">
					<span class="dashicons dashicons-feedback"></span>
					<strong><?php echo esc_html( number_format_i18n( count( $groups ) ) ); ?></strong>
					<span><?php esc_html_e( 'Field groups', 'nazari-metafield-for-elementor' ); ?></span>
				</div>
				<div class="mfbe-stat">
					<span class="dashicons dashicons-editor-table"></span>
					<strong><?php echo esc_html( number_format_i18n( $field_count ) ); ?></strong>
					<span><?php esc_html_e( 'Fields', 'nazari-metafield-for-elementor' ); ?></span>
				</div>
				<div class="mfbe-stat">
					<span class="dashicons dashicons-admin-post"></span>
					<strong><?php echo esc_html( number_format_i18n( count( $types_used ) ) ); ?></strong>
					<span><?php esc_html_e( 'Post types covered', 'nazari-metafield-for-elementor' ); ?></span>
				</div>
				<div class="mfbe-stat">
					<span class="dashicons dashicons-admin-plugins"></span>
					<strong class="<?php echo $elementor ? 'is-ok' : 'is-warn'; ?>">
						<?php echo $elementor ? esc_html__( 'Active', 'nazari-metafield-for-elementor' ) : esc_html__( 'Missing', 'nazari-metafield-for-elementor' ); ?>
					</strong>
					<span><?php esc_html_e( 'Elementor', 'nazari-metafield-for-elementor' ); ?></span>
				</div>
			</section>

			<div class="mfbe-columns">
				<div class="mfbe-col-main">
					<section class="mfbe-card">
						<header class="mfbe-card-head">
							<h3><?php esc_html_e( 'Your field groups', 'nazari-metafield-for-elementor' ); ?></h3>
							<a class="mfbe-link" href="<?php echo esc_url( $new_url ); ?>">+ <?php esc_html_e( 'Add new', 'nazari-metafield-for-elementor' ); ?></a>
						</header>

						<?php if ( ! $groups ) : ?>
							<div class="mfbe-empty-state">
								<span class="dashicons dashicons-feedback"></span>
								<p><?php esc_html_e( 'No field groups yet. Create your first one to get started.', 'nazari-metafield-for-elementor' ); ?></p>
								<a class="mfbe-btn mfbe-btn-primary" href="<?php echo esc_url( $new_url ); ?>"><?php esc_html_e( 'Create a field group', 'nazari-metafield-for-elementor' ); ?></a>
							</div>
						<?php else : ?>
							<div class="mfbe-groups">
							<?php foreach ( $groups as $group ) : ?>
								<div class="mfbe-group">
									<div class="mfbe-group-head">
										<a class="mfbe-group-title" href="<?php echo esc_url( get_edit_post_link( $group['id'] ) ); ?>"><?php echo esc_html( $group['title'] ); ?></a>
										<span class="mfbe-group-types">
											<?php
											$labels = array();
											foreach ( (array) $group['post_types'] as $slug ) {
												$labels[] = isset( $type_labels[ $slug ] ) ? $type_labels[ $slug ] : $slug;
											}
											echo esc_html( $labels ? implode( ' · ', $labels ) : '—' );
											?>
										</span>
									</div>
									<div class="mfbe-chips">
										<?php foreach ( $group['fields'] as $name => $field ) : ?>
											<button type="button" class="mfbe-chip" data-mfbe-copy="<?php echo esc_attr( '[mfbe field="' . $name . '"]' ); ?>" title="<?php esc_attr_e( 'Click to copy the shortcode', 'nazari-metafield-for-elementor' ); ?>">
												<span class="dashicons <?php echo esc_attr( self::type_icon( $field['type'] ) ); ?>"></span>
												<?php echo esc_html( '' !== $field['label'] ? $field['label'] : $name ); ?>
												<code><?php echo esc_html( $name ); ?></code>
											</button>
										<?php endforeach; ?>
									</div>
								</div>
							<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</section>

					<section class="mfbe-card">
						<header class="mfbe-card-head"><h3><?php esc_html_e( 'How it works', 'nazari-metafield-for-elementor' ); ?></h3></header>
						<ol class="mfbe-steps">
							<li>
								<strong><?php esc_html_e( 'Create a group', 'nazari-metafield-for-elementor' ); ?></strong>
								<span><?php esc_html_e( 'Pick the post types, add your fields and publish.', 'nazari-metafield-for-elementor' ); ?></span>
							</li>
							<li>
								<strong><?php esc_html_e( 'Fill the values', 'nazari-metafield-for-elementor' ); ?></strong>
								<span><?php esc_html_e( 'The meta box appears on the edit screen of those post types.', 'nazari-metafield-for-elementor' ); ?></span>
							</li>
							<li>
								<strong><?php esc_html_e( 'Show them in Elementor', 'nazari-metafield-for-elementor' ); ?></strong>
								<span><?php esc_html_e( 'Use the Meta Field widget, dynamic tags (Elementor Pro) or the shortcode.', 'nazari-metafield-for-elementor' ); ?></span>
							</li>
						</ol>
					</section>
				</div>

				<aside class="mfbe-col-side">
					<section class="mfbe-card">
						<header class="mfbe-card-head"><h3><?php esc_html_e( 'System status', 'nazari-metafield-for-elementor' ); ?></h3></header>
						<ul class="mfbe-status">
							<li>
								<span><?php esc_html_e( 'Elementor', 'nazari-metafield-for-elementor' ); ?></span>
								<b class="<?php echo $elementor ? 'is-ok' : 'is-warn'; ?>"><?php echo $elementor ? esc_html( defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '✓' ) : esc_html__( 'Not active', 'nazari-metafield-for-elementor' ); ?></b>
							</li>
							<li>
								<span><?php esc_html_e( 'Elementor Pro (dynamic tags)', 'nazari-metafield-for-elementor' ); ?></span>
								<b class="<?php echo $elementor_pro ? 'is-ok' : 'is-muted'; ?>"><?php echo $elementor_pro ? esc_html( ELEMENTOR_PRO_VERSION ) : esc_html__( 'Not active', 'nazari-metafield-for-elementor' ); ?></b>
							</li>
							<li><span>WordPress</span><b><?php echo esc_html( get_bloginfo( 'version' ) ); ?></b></li>
							<li><span>PHP</span><b><?php echo esc_html( PHP_VERSION ); ?></b></li>
							<li><span><?php esc_html_e( 'Plugin version', 'nazari-metafield-for-elementor' ); ?></span><b><?php echo esc_html( MFBE_VERSION ); ?></b></li>
						</ul>
						<?php if ( ! $elementor ) : ?>
							<p class="mfbe-note is-warn"><?php esc_html_e( 'Elementor is not active. You can still build fields and use the shortcode, but the widget and dynamic tags need Elementor.', 'nazari-metafield-for-elementor' ); ?></p>
						<?php endif; ?>
					</section>

					<section class="mfbe-card">
						<header class="mfbe-card-head"><h3><?php esc_html_e( 'Shortcode', 'nazari-metafield-for-elementor' ); ?></h3></header>
						<p><button type="button" class="mfbe-code" data-mfbe-copy='[mfbe field="meta_key"]'>[mfbe field="meta_key"]</button></p>
						<p class="description"><?php esc_html_e( 'Optional attributes: post_id, format="text", size, columns, fallback.', 'nazari-metafield-for-elementor' ); ?></p>
					</section>

					<section class="mfbe-card mfbe-card-accent">
						<h3><?php esc_html_e( 'Help me improve it', 'nazari-metafield-for-elementor' ); ?></h3>
						<p><?php esc_html_e( 'Found a bug or have an idea? Tell me what you think.', 'nazari-metafield-for-elementor' ); ?></p>
						<a class="mfbe-btn mfbe-btn-primary" href="<?php echo esc_url( self::settings_url( 'feedback' ) ); ?>"><?php esc_html_e( 'Send feedback', 'nazari-metafield-for-elementor' ); ?></a>
					</section>
				</aside>
			</div>
		</div>
		<?php
	}

	public static function type_icon( $type ) {
		$icons = array(
			'text'     => 'dashicons-editor-textcolor',
			'textarea' => 'dashicons-text-page',
			'number'   => 'dashicons-editor-ol',
			'email'    => 'dashicons-email',
			'url'      => 'dashicons-admin-links',
			'select'   => 'dashicons-arrow-down-alt2',
			'radio'    => 'dashicons-marker',
			'checkbox' => 'dashicons-yes-alt',
			'switcher' => 'dashicons-leftright',
			'date'     => 'dashicons-calendar-alt',
			'color'    => 'dashicons-art',
			'wysiwyg'  => 'dashicons-edit',
			'image'    => 'dashicons-format-image',
			'gallery'  => 'dashicons-format-gallery',
			'file'     => 'dashicons-media-default',
			'link'     => 'dashicons-admin-links',
			'time'     => 'dashicons-clock',
			'datetime' => 'dashicons-calendar',
			'oembed'   => 'dashicons-video-alt3',
			'range'    => 'dashicons-image-flip-horizontal',
		);
		return isset( $icons[ $type ] ) ? $icons[ $type ] : 'dashicons-admin-generic';
	}

	/* ------------------------------------------------------------------ *
	 * Settings page
	 * ------------------------------------------------------------------ */

	private static function notice_messages() {
		return array(
			'saved'   => array( 'success', __( 'Settings saved.', 'nazari-metafield-for-elementor' ) ),
			'sent'    => array( 'success', __( 'Thank you! Your message was sent.', 'nazari-metafield-for-elementor' ) ),
			'short'   => array( 'error', __( 'Please write a little more (at least 10 characters).', 'nazari-metafield-for-elementor' ) ),
			'toofast' => array( 'error', __( 'You just sent a message. Please wait a minute before sending another one.', 'nazari-metafield-for-elementor' ) ),
			'imported'       => array( 'success', __( 'Field groups imported.', 'nazari-metafield-for-elementor' ) ),
			'import_empty'   => array( 'error', __( 'No valid field groups were found in that file.', 'nazari-metafield-for-elementor' ) ),
			'import_invalid' => array( 'error', __( 'Please choose a valid JSON file exported from this plugin (max 1 MB).', 'nazari-metafield-for-elementor' ) ),
			'failed'  => array( 'error', __( 'The email could not be sent from this server. Your text is kept below; please try again later or write to me directly.', 'nazari-metafield-for-elementor' ) ),
		);
	}

	public static function render_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$tabs = array(
			'general'  => array( __( 'General', 'nazari-metafield-for-elementor' ), 'dashicons-admin-generic' ),
			'queries'  => array( __( 'Loop Queries', 'nazari-metafield-for-elementor' ), 'dashicons-filter' ),
			'tools'    => array( __( 'Tools', 'nazari-metafield-for-elementor' ), 'dashicons-admin-tools' ),
			'feedback' => array( __( 'Feedback', 'nazari-metafield-for-elementor' ), 'dashicons-email-alt' ),
			'about'    => array( __( 'About', 'nazari-metafield-for-elementor' ), 'dashicons-info-outline' ),
		);
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification
		if ( ! isset( $tabs[ $tab ] ) ) {
			$tab = 'general';
		}

		$notice_key = isset( $_GET['mfbe_notice'] ) ? sanitize_key( wp_unslash( $_GET['mfbe_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$notices    = self::notice_messages();
		?>
		<div class="wrap mfbe-wrap">
			<h1 class="screen-reader-text"><?php esc_html_e( 'Settings', 'nazari-metafield-for-elementor' ); ?></h1>

			<?php if ( isset( $notices[ $notice_key ] ) ) : ?>
				<div class="mfbe-toast is-<?php echo esc_attr( $notices[ $notice_key ][0] ); ?>"><?php echo esc_html( $notices[ $notice_key ][1] ); ?></div>
			<?php endif; ?>

			<nav class="mfbe-tabs">
				<?php foreach ( $tabs as $key => $tab_info ) : ?>
					<a href="<?php echo esc_url( self::settings_url( $key ) ); ?>" class="<?php echo $key === $tab ? 'is-active' : ''; ?>">
						<span class="dashicons <?php echo esc_attr( $tab_info[1] ); ?>"></span> <?php echo esc_html( $tab_info[0] ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<?php
			if ( 'queries' === $tab ) {
				MFBE_Queries::render_tab();
			} elseif ( 'tools' === $tab ) {
				MFBE_Tools::render_tab();
			} elseif ( 'feedback' === $tab ) {
				self::render_feedback_tab();
			} elseif ( 'about' === $tab ) {
				self::render_about_tab();
			} else {
				self::render_general_tab();
			}
			?>
		</div>
		<?php
	}

	private static function render_general_tab() {
		?>
		<div class="mfbe-columns">
			<div class="mfbe-col-main">
				<section class="mfbe-card">
					<header class="mfbe-card-head"><h3><?php esc_html_e( 'General settings', 'nazari-metafield-for-elementor' ); ?></h3></header>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="mfbe_save_settings">
						<?php wp_nonce_field( 'mfbe_save_settings' ); ?>

						<label class="mfbe-setting">
							<span class="mfbe-switch">
								<input type="checkbox" name="delete_on_uninstall" value="1" <?php checked( (bool) self::option( 'delete_on_uninstall', false ) ); ?>>
								<span class="mfbe-slider"></span>
							</span>
							<span>
								<strong><?php esc_html_e( 'Delete data on uninstall', 'nazari-metafield-for-elementor' ); ?></strong>
								<em><?php esc_html_e( 'When you delete the plugin, also remove field groups and settings. Values already saved on posts are always kept.', 'nazari-metafield-for-elementor' ); ?></em>
							</span>
						</label>

						<p><button type="submit" class="mfbe-btn mfbe-btn-primary"><?php esc_html_e( 'Save settings', 'nazari-metafield-for-elementor' ); ?></button></p>
					</form>
				</section>
			</div>

			<aside class="mfbe-col-side">
				<section class="mfbe-card">
					<header class="mfbe-card-head"><h3><?php esc_html_e( 'Shortcode', 'nazari-metafield-for-elementor' ); ?></h3></header>
					<p><button type="button" class="mfbe-code" data-mfbe-copy='[mfbe field="meta_key"]'>[mfbe field="meta_key"]</button></p>
					<p class="description"><?php esc_html_e( 'Optional attributes: post_id, format="text", size, columns, fallback.', 'nazari-metafield-for-elementor' ); ?></p>
				</section>
				<section class="mfbe-card mfbe-card-accent">
					<h3><?php esc_html_e( 'Help me improve it', 'nazari-metafield-for-elementor' ); ?></h3>
					<p><?php esc_html_e( 'Found a bug or have an idea? Tell me what you think.', 'nazari-metafield-for-elementor' ); ?></p>
					<a class="mfbe-btn mfbe-btn-primary" href="<?php echo esc_url( self::settings_url( 'feedback' ) ); ?>"><?php esc_html_e( 'Send feedback', 'nazari-metafield-for-elementor' ); ?></a>
				</section>
			</aside>
		</div>
		<?php
	}

	private static function render_feedback_tab() {
		$user  = wp_get_current_user();
		$draft = get_transient( 'mfbe_fb_draft_' . $user->ID );
		$draft = is_array( $draft ) ? $draft : array();

		$name    = isset( $draft['name'] ) ? $draft['name'] : $user->display_name;
		$email   = isset( $draft['email'] ) ? $draft['email'] : $user->user_email;
		$type    = isset( $draft['type'] ) ? $draft['type'] : 'suggestion';
		$message = isset( $draft['message'] ) ? $draft['message'] : '';
		$rating  = isset( $draft['rating'] ) ? (int) $draft['rating'] : 0;
		$tech    = isset( $draft['tech'] ) ? (bool) $draft['tech'] : true;
		?>
		<div class="mfbe-columns">
			<div class="mfbe-col-main">
				<section class="mfbe-card">
					<header class="mfbe-card-head"><h3><?php esc_html_e( 'Send feedback', 'nazari-metafield-for-elementor' ); ?></h3></header>
					<p class="description"><?php esc_html_e( 'Criticism, ideas and bug reports are all welcome — they go straight to the developer and help make the plugin better.', 'nazari-metafield-for-elementor' ); ?></p>

					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mfbe-form">
						<input type="hidden" name="action" value="mfbe_feedback">
						<?php wp_nonce_field( 'mfbe_feedback' ); ?>

						<div class="mfbe-form-row">
							<label for="mfbe_fb_type"><?php esc_html_e( 'Message type', 'nazari-metafield-for-elementor' ); ?></label>
							<select id="mfbe_fb_type" name="fb_type">
								<?php foreach ( self::feedback_types() as $key => $label ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $type, $key ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="mfbe-form-row">
							<span class="mfbe-form-label"><?php esc_html_e( 'How do you like the plugin?', 'nazari-metafield-for-elementor' ); ?></span>
							<div class="mfbe-stars">
								<?php for ( $i = 5; $i >= 1; $i-- ) : ?>
									<input type="radio" id="mfbe_star_<?php echo (int) $i; ?>" name="fb_rating" value="<?php echo (int) $i; ?>" <?php checked( $rating, $i ); ?>>
									<label for="mfbe_star_<?php echo (int) $i; ?>" title="<?php echo (int) $i; ?>">★</label>
								<?php endfor; ?>
							</div>
						</div>

						<div class="mfbe-form-grid">
							<div class="mfbe-form-row">
								<label for="mfbe_fb_name"><?php esc_html_e( 'Your name', 'nazari-metafield-for-elementor' ); ?></label>
								<input type="text" id="mfbe_fb_name" name="fb_name" value="<?php echo esc_attr( $name ); ?>">
							</div>
							<div class="mfbe-form-row">
								<label for="mfbe_fb_email"><?php esc_html_e( 'Your email (for a reply)', 'nazari-metafield-for-elementor' ); ?></label>
								<input type="email" id="mfbe_fb_email" name="fb_email" value="<?php echo esc_attr( $email ); ?>">
							</div>
						</div>

						<div class="mfbe-form-row">
							<label for="mfbe_fb_message"><?php esc_html_e( 'Your message', 'nazari-metafield-for-elementor' ); ?> <span class="mfbe-req">*</span></label>
							<textarea id="mfbe_fb_message" name="fb_message" rows="8" required minlength="10" maxlength="5000"><?php echo esc_textarea( $message ); ?></textarea>
						</div>

						<label class="mfbe-check">
							<input type="checkbox" name="fb_tech" value="1" <?php checked( $tech ); ?>>
							<span><?php esc_html_e( 'Include technical info (site address, WordPress, PHP, Elementor and plugin versions) to help me reproduce problems.', 'nazari-metafield-for-elementor' ); ?></span>
						</label>

						<p><button type="submit" class="mfbe-btn mfbe-btn-primary"><?php esc_html_e( 'Send message', 'nazari-metafield-for-elementor' ); ?></button></p>
					</form>
				</section>
			</div>

			<aside class="mfbe-col-side">
				<section class="mfbe-card mfbe-card-accent">
					<h3><?php esc_html_e( 'What happens next?', 'nazari-metafield-for-elementor' ); ?></h3>
					<p><?php esc_html_e( 'Your message is emailed to the plugin author. If you leave your email address, you may get a reply.', 'nazari-metafield-for-elementor' ); ?></p>
				</section>
			</aside>
		</div>
		<?php
	}

	private static function render_about_tab() {
		$changelog = array(
			'1.2.1' => array(
				__( 'Renamed to Elementor Metafield. It works with or without Elementor.', 'nazari-metafield-for-elementor' ),
				__( 'Security hardening: stricter cleaning of saved field definitions.', 'nazari-metafield-for-elementor' ),
			),
			'1.2.0' => array(
				__( 'Elementor: show or hide any element by a meta field (Meta Field Visibility, in the Advanced tab).', 'nazari-metafield-for-elementor' ),
				__( 'Elementor: loop query rules by Query ID, and the Meta Field Link widget.', 'nazari-metafield-for-elementor' ),
				__( 'Dynamic tags now understand the new field types.', 'nazari-metafield-for-elementor' ),
			),
			'1.1.0' => array(
				__( 'Six new field types: file, link, time, date and time, embed and range.', 'nazari-metafield-for-elementor' ),
				__( 'Validation (required, length, unique) and conditional logic.', 'nazari-metafield-for-elementor' ),
				__( 'Admin list columns with sorting and filters, JSON import/export and duplicate group.', 'nazari-metafield-for-elementor' ),
			),
			'1.0.2' => array(
				__( 'New name and branding.', 'nazari-metafield-for-elementor' ),
				__( 'Dashboard, settings page and feedback form.', 'nazari-metafield-for-elementor' ),
				__( 'Field builder: type icons, duplicate field, expand/collapse and copy shortcode.', 'nazari-metafield-for-elementor' ),
			),
			'1.0.0' => array(
				__( 'First release: field groups, 14 field types, Elementor widget, dynamic tags and shortcode.', 'nazari-metafield-for-elementor' ),
			),
		);
		?>
		<div class="mfbe-columns">
			<div class="mfbe-col-main">
				<section class="mfbe-card mfbe-about">
					<img src="<?php echo esc_url( self::logo() ); ?>" alt="Nazari Technologies" class="mfbe-about-logo">
					<div>
						<h3><?php esc_html_e( 'Elementor Metafield', 'nazari-metafield-for-elementor' ); ?></h3>
						<p class="description"><?php echo esc_html( sprintf( /* translators: %s: version */ __( 'Version %s', 'nazari-metafield-for-elementor' ), MFBE_VERSION ) ); ?></p>
						<p><?php esc_html_e( 'A lightweight meta box builder with first-class Elementor support.', 'nazari-metafield-for-elementor' ); ?></p>
					</div>
				</section>

				<section class="mfbe-card">
					<header class="mfbe-card-head"><h3><?php esc_html_e( 'Changelog', 'nazari-metafield-for-elementor' ); ?></h3></header>
					<?php foreach ( $changelog as $version => $items ) : ?>
						<h4 class="mfbe-version">v<?php echo esc_html( $version ); ?></h4>
						<ul class="mfbe-list">
							<?php foreach ( $items as $item ) : ?>
								<li><?php echo esc_html( $item ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endforeach; ?>
				</section>
			</div>

			<aside class="mfbe-col-side">
				<section class="mfbe-card">
					<header class="mfbe-card-head"><h3><?php esc_html_e( 'Author', 'nazari-metafield-for-elementor' ); ?></h3></header>
					<ul class="mfbe-status">
						<li><span><?php esc_html_e( 'Name', 'nazari-metafield-for-elementor' ); ?></span><b><?php echo esc_html( self::AUTHOR ); ?></b></li>
						<li><span><?php esc_html_e( 'GitHub', 'nazari-metafield-for-elementor' ); ?></span><b><a href="<?php echo esc_url( self::AUTHOR_URL ); ?>" target="_blank" rel="noopener">github.com/SajjadNazari1995</a></b></li>
						<li><span><?php esc_html_e( 'License', 'nazari-metafield-for-elementor' ); ?></span><b>GPL-2.0+</b></li>
					</ul>
					<p><a class="mfbe-btn mfbe-btn-primary" href="<?php echo esc_url( self::settings_url( 'feedback' ) ); ?>"><?php esc_html_e( 'Send feedback', 'nazari-metafield-for-elementor' ); ?></a></p>
				</section>
			</aside>
		</div>
		<?php
	}

	/* ------------------------------------------------------------------ *
	 * Handlers
	 * ------------------------------------------------------------------ */

	public static function handle_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'nazari-metafield-for-elementor' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'mfbe_save_settings' );

		update_option(
			self::OPTION,
			array(
				'delete_on_uninstall' => ! empty( $_POST['delete_on_uninstall'] ) ? 1 : 0,
			),
			false
		);

		wp_safe_redirect( self::settings_url( 'general', array( 'mfbe_notice' => 'saved' ) ) );
		exit;
	}

	public static function feedback_types() {
		return array(
			'suggestion' => __( 'Suggestion', 'nazari-metafield-for-elementor' ),
			'criticism'  => __( 'Criticism', 'nazari-metafield-for-elementor' ),
			'bug'        => __( 'Bug report', 'nazari-metafield-for-elementor' ),
			'feature'    => __( 'Feature request', 'nazari-metafield-for-elementor' ),
			'other'      => __( 'Other', 'nazari-metafield-for-elementor' ),
		);
	}

	public static function handle_feedback() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'nazari-metafield-for-elementor' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'mfbe_feedback' );

		$user_id = get_current_user_id();
		$types   = self::feedback_types();

		$type = isset( $_POST['fb_type'] ) ? sanitize_key( wp_unslash( $_POST['fb_type'] ) ) : 'suggestion';
		$type = isset( $types[ $type ] ) ? $type : 'other';

		$name = isset( $_POST['fb_name'] ) ? sanitize_text_field( wp_unslash( $_POST['fb_name'] ) ) : '';
		$name = trim( preg_replace( '/[^\p{L}\p{N} ._-]/u', '', $name ) );

		$email   = isset( $_POST['fb_email'] ) ? sanitize_email( wp_unslash( $_POST['fb_email'] ) ) : '';
		$message = isset( $_POST['fb_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['fb_message'] ) ) : '';
		$rating  = isset( $_POST['fb_rating'] ) ? min( 5, absint( $_POST['fb_rating'] ) ) : 0;
		$tech    = ! empty( $_POST['fb_tech'] );

		$message = function_exists( 'mb_substr' ) ? mb_substr( $message, 0, 5000 ) : substr( $message, 0, 5000 );
		$length  = function_exists( 'mb_strlen' ) ? mb_strlen( trim( $message ) ) : strlen( trim( $message ) );

		$draft = array(
			'type'    => $type,
			'name'    => $name,
			'email'   => $email,
			'message' => $message,
			'rating'  => $rating,
			'tech'    => $tech,
		);
		$draft_key = 'mfbe_fb_draft_' . $user_id;

		$back = function ( $notice ) use ( $draft, $draft_key ) {
			if ( 'sent' === $notice ) {
				delete_transient( $draft_key );
			} else {
				set_transient( $draft_key, $draft, 10 * MINUTE_IN_SECONDS );
			}
			wp_safe_redirect( MFBE_Admin::settings_url( 'feedback', array( 'mfbe_notice' => $notice ) ) );
			exit;
		};

		if ( $length < 10 ) {
			$back( 'short' );
		}
		if ( get_transient( 'mfbe_fb_lock_' . $user_id ) ) {
			$back( 'toofast' );
		}

		$lines   = array();
		$lines[] = 'Type: ' . $types[ $type ];
		$lines[] = 'Rating: ' . ( $rating ? $rating . '/5' : '—' );
		$lines[] = 'From: ' . ( '' !== $name ? $name : '—' ) . ( $email ? ' <' . $email . '>' : '' );
		$lines[] = '';
		$lines[] = $message;

		if ( $tech ) {
			global $wp_version;
			$lines[] = '';
			$lines[] = '---';
			$lines[] = 'Site: ' . home_url();
			$lines[] = 'Plugin: ' . MFBE_VERSION;
			$lines[] = 'WordPress: ' . $wp_version;
			$lines[] = 'PHP: ' . PHP_VERSION;
			$lines[] = 'Elementor: ' . ( defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : 'not active' );
			$lines[] = 'Elementor Pro: ' . ( defined( 'ELEMENTOR_PRO_VERSION' ) ? ELEMENTOR_PRO_VERSION : 'not active' );
			$lines[] = 'Locale: ' . get_locale();
		}

		$host    = wp_parse_url( home_url(), PHP_URL_HOST );
		$subject = sanitize_text_field( sprintf( '[Elementor Metafield %s] %s — %s', MFBE_VERSION, $types[ $type ], $host ? $host : 'site' ) );

		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
		if ( is_email( $email ) ) {
			$headers[] = 'Reply-To: ' . ( '' !== $name ? $name . ' <' . $email . '>' : $email );
		}

		$sent = wp_mail( self::FEEDBACK_TO, $subject, implode( "\n", $lines ), $headers );

		if ( $sent ) {
			set_transient( 'mfbe_fb_lock_' . $user_id, 1, MINUTE_IN_SECONDS );
			$back( 'sent' );
		}

		$back( 'failed' );
	}
}
