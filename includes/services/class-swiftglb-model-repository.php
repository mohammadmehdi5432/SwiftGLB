<?php
/**
 * Model repository implementation.
 *
 * @package SwiftGLB
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles database persistence, querying, and lifecycle of 3D model metadata.
 */
class SwiftGLB_Model_Repository implements SwiftGLB_Model_Repository_Interface {

	const META_TOKEN       = '_swiftglb_token';
	const META_FILENAME    = '_swiftglb_filename';
	const META_HD_TOKEN    = '_swiftglb_hd_token';
	const META_HD_FILENAME = '_swiftglb_hd_filename';
	const META_HD_AUTO     = '_swiftglb_hd_auto';
	const META_ENABLED     = '_swiftglb_enabled';
	const META_ORBIT       = '_swiftglb_orbit_lock';
	const META_AR_FIXED    = '_swiftglb_ar_fixed';
	const META_COLOR       = '_swiftglb_color';
	const META_AUTO_ROTATE = '_swiftglb_auto_rotate';
	const META_BUTTON_TEXT = '_swiftglb_button_text';

	/**
	 * @var SwiftGLB_Storage_Interface
	 */
	private $storage;

	/**
	 * @param SwiftGLB_Storage_Interface $storage Storage service.
	 */
	public function __construct( SwiftGLB_Storage_Interface $storage ) {
		$this->storage = $storage;
	}

	/**
	 * Meta keys managed by the plugin.
	 *
	 * @return string[]
	 */
	public function get_meta_keys() {
		return array(
			self::META_TOKEN,
			self::META_FILENAME,
			self::META_HD_TOKEN,
			self::META_HD_FILENAME,
			self::META_HD_AUTO,
			self::META_ENABLED,
			self::META_ORBIT,
			self::META_AR_FIXED,
			self::META_COLOR,
			self::META_AUTO_ROTATE,
			self::META_BUTTON_TEXT,
		);
	}

	/**
	 * Count posts utilizing a specific token.
	 *
	 * @param string $token Token.
	 * @return int
	 */
	public function get_usage_count( $token ) {
		global $wpdb;

		if ( ! $this->storage->is_valid_token( $token ) ) {
			return 0;
		}

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key IN (%s, %s) AND meta_value = %s",
				self::META_TOKEN,
				self::META_HD_TOKEN,
				$token
			)
		);
	}

	/**
	 * Post IDs referencing a token.
	 *
	 * @param string $token Token.
	 * @return int[]
	 */
	public function get_post_ids_for_token( $token ) {
		global $wpdb;

		if ( ! $this->storage->is_valid_token( $token ) ) {
			return array();
		}

		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key IN (%s, %s) AND meta_value = %s",
				self::META_TOKEN,
				self::META_HD_TOKEN,
				$token
			)
		);

		return array_map( 'absint', is_array( $ids ) ? $ids : array() );
	}

	/**
	 * Delete a token file if no posts reference it.
	 *
	 * @param string $token Token.
	 * @return void
	 */
	public function delete_if_unused( $token ) {
		if ( 0 === $this->get_usage_count( $token ) ) {
			$this->storage->delete( $token );
		}
	}

	/**
	 * Clean up stored files not referenced in database.
	 *
	 * @return void
	 */
	public function cleanup_orphans() {
		if ( false !== get_transient( 'swiftglb_cleanup' ) ) {
			return;
		}
		set_transient( 'swiftglb_cleanup', 1, DAY_IN_SECONDS );

		$dir = $this->storage->get_dir();
		if ( '' === $dir || ! is_dir( $dir ) ) {
			return;
		}

		$files = glob( trailingslashit( $dir ) . '*.dat' );
		if ( ! is_array( $files ) ) {
			return;
		}

		foreach ( $files as $file ) {
			$name  = basename( $file, '.dat' );
			$mtime = filemtime( $file );
			if ( false !== $mtime && ( time() - $mtime ) < HOUR_IN_SECONDS ) {
				continue;
			}
			$this->delete_if_unused( $name );
		}
	}
}
