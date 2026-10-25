<?php
defined( 'ABSPATH' ) || exit;

final class MFBE_Plugin {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		require_once MFBE_PATH . 'includes/class-mfbe-registry.php';
		require_once MFBE_PATH . 'includes/class-mfbe-fields.php';
		require_once MFBE_PATH . 'includes/class-mfbe-groups.php';
		require_once MFBE_PATH . 'includes/class-mfbe-metaboxes.php';
		require_once MFBE_PATH . 'includes/class-mfbe-shortcode.php';

		add_action( 'init', array( $this, 'load_textdomain' ) );

		MFBE_Groups::init();
		MFBE_Metaboxes::init();
		MFBE_Shortcode::init();

		if ( is_admin() ) {
			require_once MFBE_PATH . 'includes/class-mfbe-admin.php';
			MFBE_Admin::init();
		}

		if ( did_action( 'elementor/loaded' ) ) {
			$this->load_elementor();
		} else {
			add_action( 'elementor/loaded', array( $this, 'load_elementor' ) );
		}
	}

	public function load_textdomain() {
		// The Persian translation is bundled with the plugin (it is not distributed via WordPress.org),
		// so the text domain has to be loaded from the plugin's own /languages folder.
		load_plugin_textdomain( 'nazari-metafield-for-elementor', false, dirname( plugin_basename( MFBE_FILE ) ) . '/languages' ); // phpcs:ignore PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound
	}

	public function load_elementor() {
		require_once MFBE_PATH . 'includes/elementor/class-mfbe-elementor.php';
		MFBE_Elementor::init();
	}
}
