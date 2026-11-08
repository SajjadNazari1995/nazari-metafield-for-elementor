<?php
/**
 * Plugin Name:       Elementor Metafield
 * Description:       Build custom meta boxes (field groups) for posts and custom post types and show the fields on your site. Works with or without Elementor: a dedicated widget and dynamic tags in Elementor, a shortcode everywhere else. A lightweight JetEngine-style meta box builder.
 * Version:           1.2.1
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Sajjad Nazari
 * Author URI:        https://github.com/SajjadNazari1995
 * Text Domain:       nazari-metafield-for-elementor
 * Domain Path:       /languages
 * License:           GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

// The earlier release ("Meta Fields for Elementor") shares this code base: switch it off automatically.
if ( defined( 'MFBE_FILE' ) ) {
	if ( is_admin() && plugin_basename( MFBE_FILE ) !== plugin_basename( __FILE__ ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		deactivate_plugins( plugin_basename( MFBE_FILE ), true );
		add_action(
			'admin_notices',
			static function () {
				echo '<div class="notice notice-success"><p><strong>Elementor Metafield:</strong> '
					. esc_html__( 'The older “Meta Fields for Elementor” plugin was deactivated automatically. Your field groups and saved values are kept. Please reload this page.', 'nazari-metafield-for-elementor' )
					. '</p></div>';
			}
		);
	}
	return;
}

define( 'MFBE_VERSION', '1.2.1' );
define( 'MFBE_FILE', __FILE__ );
define( 'MFBE_PATH', plugin_dir_path( __FILE__ ) );
define( 'MFBE_URL', plugin_dir_url( __FILE__ ) );

require_once MFBE_PATH . 'includes/class-mfbe-plugin.php';

add_action( 'plugins_loaded', array( 'MFBE_Plugin', 'instance' ), 20 );
