<?php
/**
 * Storage facade for backward compatibility and clean delegation.
 *
 * @package SwiftGLB
 */

defined( 'ABSPATH' ) || exit;

/**
 * Provides static access to storage, crypto, and model repository services.
 */
class SwiftGLB_Storage {

	const MAGIC            = SwiftGLB_Crypto::MAGIC;
	const VERSION          = SwiftGLB_Crypto::VERSION;
	const META_TOKEN       = SwiftGLB_Model_Repository::META_TOKEN;
	const META_FILENAME    = SwiftGLB_Model_Repository::META_FILENAME;
	const META_HD_TOKEN    = SwiftGLB_Model_Repository::META_HD_TOKEN;
	const META_HD_FILENAME = SwiftGLB_Model_Repository::META_HD_FILENAME;
	const META_HD_AUTO     = SwiftGLB_Model_Repository::META_HD_AUTO;
	const META_ENABLED     = SwiftGLB_Model_Repository::META_ENABLED;
	const META_ORBIT       = SwiftGLB_Model_Repository::META_ORBIT;
	const META_AR_FIXED    = SwiftGLB_Model_Repository::META_AR_FIXED;
	const META_COLOR       = SwiftGLB_Model_Repository::META_COLOR;
	const META_AUTO_ROTATE = SwiftGLB_Model_Repository::META_AUTO_ROTATE;
	const META_BUTTON_TEXT = SwiftGLB_Model_Repository::META_BUTTON_TEXT;

	/**
	 * @return string[]
	 */
	public static function meta_keys() {
		return SwiftGLB_Container::get_instance()->get_repository()->get_meta_keys();
	}

	/**
	 * @return string
	 */
	public static function dir() {
		return SwiftGLB_Container::get_instance()->get_storage()->get_dir();
	}

	/**
	 * @return true|WP_Error
	 */
	public static function protect_directory() {
		return SwiftGLB_Container::get_instance()->get_storage()->protect_directory();
	}

	/**
	 * @return string
	 */
	public static function secret() {
		return SwiftGLB_Container::get_instance()->get_crypto()->get_secret();
	}

	/**
	 * @param mixed $token Token.
	 * @return bool
	 */
	public static function is_token( $token ) {
		return SwiftGLB_Container::get_instance()->get_storage()->is_valid_token( $token );
	}

	/**
	 * @return int
	 */
	public static function max_bytes() {
		return SwiftGLB_Container::get_instance()->get_storage()->get_max_bytes();
	}

	/**
	 * @param string $path          Source path.
	 * @param string $original_name Filename.
	 * @return string|WP_Error
	 */
	public static function import_file( $path, $original_name ) {
		return SwiftGLB_Container::get_instance()->get_storage()->import_file( $path, $original_name );
	}

	/**
	 * @param string $token Token.
	 * @return string
	 */
	public static function path( $token ) {
		return SwiftGLB_Container::get_instance()->get_storage()->get_path( $token );
	}

	/**
	 * @param string $token Token.
	 * @return bool
	 */
	public static function exists( $token ) {
		return SwiftGLB_Container::get_instance()->get_storage()->exists( $token );
	}

	/**
	 * @param string $token Token.
	 * @return array{name:string,size:int}|WP_Error
	 */
	public static function inspect( $token ) {
		return SwiftGLB_Container::get_instance()->get_storage()->inspect( $token );
	}

	/**
	 * @param string $token Token.
	 * @return true|WP_Error
	 */
	public static function stream( $token ) {
		return SwiftGLB_Container::get_instance()->get_storage()->stream( $token );
	}

	/**
	 * @param string $token Token.
	 * @return void
	 */
	public static function delete( $token ) {
		SwiftGLB_Container::get_instance()->get_storage()->delete( $token );
	}

	/**
	 * @return void
	 */
	public static function delete_all() {
		SwiftGLB_Container::get_instance()->get_storage()->delete_all();
	}

	/**
	 * @param string $token Token.
	 * @return void
	 */
	public static function delete_if_unused( $token ) {
		SwiftGLB_Container::get_instance()->get_repository()->delete_if_unused( $token );
	}

	/**
	 * @param string $token Token.
	 * @return int
	 */
	public static function usage_count( $token ) {
		return SwiftGLB_Container::get_instance()->get_repository()->get_usage_count( $token );
	}

	/**
	 * @param string $token Token.
	 * @return int[]
	 */
	public static function posts_for_token( $token ) {
		return SwiftGLB_Container::get_instance()->get_repository()->get_post_ids_for_token( $token );
	}

	/**
	 * @return void
	 */
	public static function cleanup_orphans() {
		SwiftGLB_Container::get_instance()->get_repository()->cleanup_orphans();
	}
}
