<?php
/**
 * Runs when the plugin is deleted from the Plugins screen.
 * Field groups and settings are removed only if "Delete data on uninstall" is enabled.
 * Values already saved on posts are always kept.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$mfbe_settings = get_option( 'mfbe_settings', array() );

if ( ! empty( $mfbe_settings['delete_on_uninstall'] ) ) {
	$mfbe_groups = get_posts(
		array(
			'post_type'      => 'mfbe_group',
			'post_status'    => 'any',
			'numberposts'    => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);

	foreach ( $mfbe_groups as $mfbe_group_id ) {
		wp_delete_post( $mfbe_group_id, true );
	}

	delete_option( 'mfbe_settings' );
}
