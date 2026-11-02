<?php
/**
 * Uninstall cleanup.
 *
 * @package Try3D
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once __DIR__ . '/includes/contracts/interface-try3d-crypto.php';
require_once __DIR__ . '/includes/contracts/interface-try3d-storage.php';
require_once __DIR__ . '/includes/contracts/interface-try3d-model-repository.php';
require_once __DIR__ . '/includes/services/class-try3d-crypto.php';
require_once __DIR__ . '/includes/services/class-try3d-storage-service.php';
require_once __DIR__ . '/includes/services/class-try3d-model-repository.php';
require_once __DIR__ . '/includes/class-try3d-container.php';
require_once __DIR__ . '/includes/class-try3d-storage.php';

Try3D_Storage::delete_all();
delete_option( 'try3d_secret' );
delete_option( 'swiftglb_secret' );
delete_option( '3dsaz_secret' );
delete_option( 'try3d_brand_migrated' );
delete_option( 'swiftglb_brand_migrated' );

global $wpdb;
$keys   = Try3D_Storage::meta_keys();
$keys[] = '_swiftglb_token';
$keys[] = '_swiftglb_filename';
$keys[] = '_swiftglb_hd_token';
$keys[] = '_swiftglb_hd_filename';
$keys[] = '_swiftglb_hd_auto';
$keys[] = '_swiftglb_enabled';
$keys[] = '_swiftglb_orbit_lock';
$keys[] = '_swiftglb_ar_fixed';
$keys[] = '_swiftglb_color';
$keys[] = '_swiftglb_auto_rotate';
$keys[] = '_swiftglb_button_text';
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
delete_transient( 'try3d_cleanup' );
delete_transient( 'swiftglb_cleanup' );
delete_transient( '3dsaz_cleanup' );
