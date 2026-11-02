<?php
/**
 * Plugin bootstrap.
 *
 * @package Try3D
 */

defined( 'ABSPATH' ) || exit;

/**
 * Wires the pieces together.
 */
class Try3D_Plugin {

	/**
	 * @return void
	 */
	public static function init() {
		load_plugin_textdomain( 'try3d-viewer', false, dirname( plugin_basename( TRY3D_FILE ) ) . '/languages' );
		self::migrate_legacy_brand();
		Try3D_Admin::init();
		Try3D_REST::init();
		Try3D_Frontend::init();

		if ( is_admin() ) {
			add_action( 'admin_init', array( 'Try3D_Storage', 'cleanup_orphans' ) );
		}
	}

	/**
	 * @return void
	 */
	public static function activate() {
		self::migrate_legacy_brand();
		if ( ! get_option( 'try3d_secret' ) ) {
			add_option( 'try3d_secret', bin2hex( random_bytes( 32 ) ), '', false );
		}
		Try3D_Storage::protect_directory();
	}

	/**
	 * Keep models readable after the Try3D rename.
	 *
	 * @return void
	 */
	public static function migrate_legacy_brand() {
		foreach ( array( 'swiftglb_secret', '3dsaz_secret' ) as $legacy_key ) {
			$legacy_secret = get_option( $legacy_key );
			if ( is_string( $legacy_secret ) && strlen( $legacy_secret ) >= 64 && ! get_option( 'try3d_secret' ) ) {
				add_option( 'try3d_secret', $legacy_secret, '', false );
			}
		}

		if ( get_option( 'try3d_brand_migrated' ) ) {
			return;
		}

		global $wpdb;
		$map = array(
			'_swiftglb_token'       => '_try3d_token',
			'_swiftglb_filename'    => '_try3d_filename',
			'_swiftglb_hd_token'    => '_try3d_hd_token',
			'_swiftglb_hd_filename' => '_try3d_hd_filename',
			'_swiftglb_hd_auto'     => '_try3d_hd_auto',
			'_swiftglb_enabled'     => '_try3d_enabled',
			'_swiftglb_orbit_lock'  => '_try3d_orbit_lock',
			'_swiftglb_ar_fixed'    => '_try3d_ar_fixed',
			'_swiftglb_color'       => '_try3d_color',
			'_swiftglb_auto_rotate' => '_try3d_auto_rotate',
			'_swiftglb_button_text' => '_try3d_button_text',
			'_3dsaz_token'          => '_try3d_token',
			'_3dsaz_filename'       => '_try3d_filename',
			'_3dsaz_hd_token'       => '_try3d_hd_token',
			'_3dsaz_hd_filename'    => '_try3d_hd_filename',
			'_3dsaz_hd_auto'        => '_try3d_hd_auto',
			'_3dsaz_enabled'        => '_try3d_enabled',
			'_3dsaz_orbit_lock'     => '_try3d_orbit_lock',
			'_3dsaz_ar_fixed'       => '_try3d_ar_fixed',
			'_3dsaz_color'          => '_try3d_color',
			'_3dsaz_auto_rotate'    => '_try3d_auto_rotate',
			'_3dsaz_button_text'    => '_try3d_button_text',
		);
		foreach ( $map as $from => $to ) {
			$wpdb->update(
				$wpdb->postmeta,
				array( 'meta_key' => $to ),
				array( 'meta_key' => $from ),
				array( '%s' ),
				array( '%s' )
			);
		}

		update_option( 'try3d_brand_migrated', '1', false );
	}

	/**
	 * WordPress blocks these types unless a plugin allows them.
	 *
	 * @param array<string,string> $mimes Mime map.
	 * @return array<string,string>
	 */
	public static function mimes( $mimes ) {
		if ( ! current_user_can( 'upload_files' ) ) {
			return $mimes;
		}
		$mimes['glb'] = 'model/gltf-binary';
		$mimes['w3d'] = 'application/octet-stream';
		$mimes['bin'] = 'application/octet-stream';
		return $mimes;
	}

	/**
	 * GLB is usually detected as a generic binary, which WordPress would reject.
	 *
	 * @param array<string,string> $data     Detected type.
	 * @param string               $file     Absolute path.
	 * @param string               $filename Original name.
	 * @param array<string,string> $mimes    Allowed mimes.
	 * @return array<string,string>
	 */
	public static function filetype( $data, $file, $filename, $mimes ) {
		unset( $mimes );
		$ext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
		$map = array(
			'glb' => 'model/gltf-binary',
			'w3d' => 'application/octet-stream',
			'bin' => 'application/octet-stream',
		);
		if ( ! isset( $map[ $ext ] ) || ! is_string( $file ) || ! is_readable( $file ) ) {
			return $data;
		}

		$handle = fopen( $file, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$magic  = $handle ? fread( $handle, 4 ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
		if ( $handle ) {
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		}
		if ( 'glTF' !== $magic && Try3D_Storage::MAGIC !== $magic ) {
			return $data;
		}

		$data['ext']             = $ext;
		$data['type']            = $map[ $ext ];
		$data['proper_filename'] = $filename;
		return $data;
	}
}
