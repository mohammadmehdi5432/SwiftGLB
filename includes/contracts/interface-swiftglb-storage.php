<?php
/**
 * Storage service contract.
 *
 * @package SwiftGLB
 */

defined( 'ABSPATH' ) || exit;

/**
 * Interface for private 3D model filesystem storage.
 */
interface SwiftGLB_Storage_Interface {

	/**
	 * Private storage directory.
	 *
	 * @return string
	 */
	public function get_dir();

	/**
	 * Protect storage directory from direct web access.
	 *
	 * @return true|WP_Error
	 */
	public function protect_directory();

	/**
	 * Verify if a token has valid hex syntax.
	 *
	 * @param mixed $token Token string.
	 * @return bool
	 */
	public function is_valid_token( $token );

	/**
	 * Absolute path for a token file.
	 *
	 * @param string $token Token.
	 * @return string
	 */
	public function get_path( $token );

	/**
	 * Check if token file exists.
	 *
	 * @param string $token Token.
	 * @return bool
	 */
	public function exists( $token );

	/**
	 * Get metadata for stored model.
	 *
	 * @param string $token Token.
	 * @return array{name:string,size:int}|WP_Error
	 */
	public function inspect( $token );

	/**
	 * Import and encrypt a file into private storage.
	 *
	 * @param string $source_path   Uploaded temporary file.
	 * @param string $original_name Filename.
	 * @return string|WP_Error New token.
	 */
	public function import_file( $source_path, $original_name );

	/**
	 * Stream model plain bytes to output.
	 *
	 * @param string $token Token.
	 * @return true|WP_Error
	 */
	public function stream( $token );

	/**
	 * Delete a model file by token.
	 *
	 * @param string $token Token.
	 * @return void
	 */
	public function delete( $token );

	/**
	 * Delete all stored models.
	 *
	 * @return void
	 */
	public function delete_all();
}
