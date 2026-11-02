<?php
/**
 * Storage facade for backward compatibility and clean delegation.
 *
 * @package Try3D
 */

defined( 'ABSPATH' ) || exit;

/**
 * Provides static access to storage, crypto, and model repository services.
 */
class Try3D_Storage {

	const MAGIC            = Try3D_Crypto::MAGIC;
	const VERSION          = Try3D_Crypto::VERSION;
	const META_TOKEN       = Try3D_Model_Repository::META_TOKEN;
	const META_FILENAME    = Try3D_Model_Repository::META_FILENAME;
	const META_HD_TOKEN    = Try3D_Model_Repository::META_HD_TOKEN;
	const META_HD_FILENAME = Try3D_Model_Repository::META_HD_FILENAME;
	const META_HD_AUTO     = Try3D_Model_Repository::META_HD_AUTO;
	const META_ENABLED     = Try3D_Model_Repository::META_ENABLED;
	const META_ORBIT       = Try3D_Model_Repository::META_ORBIT;
	const META_AR_FIXED    = Try3D_Model_Repository::META_AR_FIXED;
	const META_COLOR       = Try3D_Model_Repository::META_COLOR;
	const META_AUTO_ROTATE = Try3D_Model_Repository::META_AUTO_ROTATE;
	const META_BUTTON_TEXT = Try3D_Model_Repository::META_BUTTON_TEXT;

	/**
	 * @return string[]
	 */
	public static function meta_keys() {
		return Try3D_Container::get_instance()->get_repository()->get_meta_keys();
	}

	/**
	 * @return string
	 */
	public static function dir() {
		return Try3D_Container::get_instance()->get_storage()->get_dir();
	}

	/**
	 * @return true|WP_Error
	 */
	public static function protect_directory() {
		return Try3D_Container::get_instance()->get_storage()->protect_directory();
	}

	/**
	 * @return string
	 */
	public static function secret() {
		return Try3D_Container::get_instance()->get_crypto()->get_secret();
	}

	/**
	 * @param mixed $token Token.
	 * @return bool
	 */
	public static function is_token( $token ) {
		return Try3D_Container::get_instance()->get_storage()->is_valid_token( $token );
	}

	/**
	 * @return int
	 */
	public static function max_bytes() {
		return Try3D_Container::get_instance()->get_storage()->get_max_bytes();
	}

	/**
	 * @param string $path          Source path.
	 * @param string $original_name Filename.
	 * @return string|WP_Error
	 */
	public static function import_file( $path, $original_name ) {
		return Try3D_Container::get_instance()->get_storage()->import_file( $path, $original_name );
	}

	/**
	 * @param string $token Token.
	 * @return string
	 */
	public static function path( $token ) {
		return Try3D_Container::get_instance()->get_storage()->get_path( $token );
	}

	/**
	 * @param string $token Token.
	 * @return bool
	 */
	public static function exists( $token ) {
		return Try3D_Container::get_instance()->get_storage()->exists( $token );
	}

	/**
	 * @param string $token Token.
	 * @return array{name:string,size:int}|WP_Error
	 */
	public static function inspect( $token ) {
		return Try3D_Container::get_instance()->get_storage()->inspect( $token );
	}

	/**
	 * @param string $token Token.
	 * @return true|WP_Error
	 */
	public static function stream( $token ) {
		return Try3D_Container::get_instance()->get_storage()->stream( $token );
	}

	/**
	 * @param string $token Token.
	 * @return void
	 */
	public static function delete( $token ) {
		Try3D_Container::get_instance()->get_storage()->delete( $token );
	}

	/**
	 * @return void
	 */
	public static function delete_all() {
		Try3D_Container::get_instance()->get_storage()->delete_all();
	}

	/**
	 * @param string $token Token.
	 * @return void
	 */
	public static function delete_if_unused( $token ) {
		Try3D_Container::get_instance()->get_repository()->delete_if_unused( $token );
	}

	/**
	 * @param string $token Token.
	 * @return int
	 */
	public static function usage_count( $token ) {
		return Try3D_Container::get_instance()->get_repository()->get_usage_count( $token );
	}

	/**
	 * @param string $token Token.
	 * @return int[]
	 */
	public static function posts_for_token( $token ) {
		return Try3D_Container::get_instance()->get_repository()->get_post_ids_for_token( $token );
	}

	/**
	 * @return void
	 */
	public static function cleanup_orphans() {
		Try3D_Container::get_instance()->get_repository()->cleanup_orphans();
	}
}
