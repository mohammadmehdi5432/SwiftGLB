<?php
/**
 * Uninstall cleanup.
 *
 * @package SwiftGLB
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once __DIR__ . '/includes/contracts/interface-swiftglb-crypto.php';
require_once __DIR__ . '/includes/contracts/interface-swiftglb-storage.php';
require_once __DIR__ . '/includes/contracts/interface-swiftglb-model-repository.php';
require_once __DIR__ . '/includes/services/class-swiftglb-crypto.php';
require_once __DIR__ . '/includes/services/class-swiftglb-storage-service.php';
require_once __DIR__ . '/includes/services/class-swiftglb-model-repository.php';
require_once __DIR__ . '/includes/class-swiftglb-container.php';
require_once __DIR__ . '/includes/class-swiftglb-storage.php';

SwiftGLB_Storage::delete_all();
delete_option( 'swiftglb_secret' );
delete_option( '3dsaz_secret' );
delete_option( 'swiftglb_brand_migrated' );

global $wpdb;
$keys   = SwiftGLB_Storage::meta_keys();
$keys[] = '_3dsaz_token';
$keys[] = '_3dsaz_filename';
$keys[] = '_3dsaz_hd_token';
$keys[] = '_3dsaz_hd_filename';
$keys[] = '_3dsaz_hd_auto';
$keys[] = '_3dsaz_enabled';
$keys[] = '_3dsaz_orbit_lock';
$keys[] = '_3dsaz_ar_fixed';
$keys[] = '_3dsaz_color';
$keys[] = '_3dsaz_auto_rotate';
$keys[] = '_3dsaz_button_text';
if ( ! empty( $keys ) ) {
	$placeholders = implode( ', ', array_fill( 0, count( $keys ), '%s' ) );
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->postmeta} WHERE meta_key IN ($placeholders)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$keys
		)
	);
}
delete_transient( 'swiftglb_cleanup' );
delete_transient( '3dsaz_cleanup' );
