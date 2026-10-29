<?php
/**
 * Model repository contract.
 *
 * @package SwiftGLB
 */

defined( 'ABSPATH' ) || exit;

/**
 * Interface for database persistence and querying of 3D models.
 */
interface SwiftGLB_Model_Repository_Interface {

	/**
	 * Meta keys managed by the plugin.
	 *
	 * @return string[]
	 */
	public function get_meta_keys();

	/**
	 * Count posts utilizing a specific token.
	 *
	 * @param string $token Token.
	 * @return int
	 */
	public function get_usage_count( $token );

	/**
	 * Post IDs referencing a token.
	 *
	 * @param string $token Token.
	 * @return int[]
	 */
	public function get_post_ids_for_token( $token );

	/**
	 * Delete a token file if no posts reference it.
	 *
	 * @param string $token Token.
	 * @return void
	 */
	public function delete_if_unused( $token );

	/**
	 * Clean up stored files not referenced in database.
	 *
	 * @return void
	 */
	public function cleanup_orphans();
}
