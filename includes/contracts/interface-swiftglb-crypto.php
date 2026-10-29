<?php
/**
 * Cryptographic service contract.
 *
 * @package SwiftGLB
 */

defined( 'ABSPATH' ) || exit;

/**
 * Interface for obfuscating and parsing 3D model streams.
 */
interface SwiftGLB_Crypto_Interface {

	/**
	 * Retrieve site secret used for XOR cipher.
	 *
	 * @return string
	 */
	public function get_secret();

	/**
	 * Safe filename for the header.
	 *
	 * @param string $name Raw name.
	 * @return string
	 */
	public function sanitize_header_name( $name );

	/**
	 * Pack header and stream encrypted GLB to destination.
	 *
	 * @param string $source_path Source GLB file.
	 * @param string $dest_path   Destination encrypted file.
	 * @param string $name        Stored filename.
	 * @param int    $size        Uncompressed byte length.
	 * @return true|WP_Error
	 */
	public function encrypt_file( $source_path, $dest_path, $name, $size );

	/**
	 * Read plain prefix bytes from an encrypted file.
	 *
	 * @param string $file_path File path.
	 * @param int    $length    Prefix length.
	 * @return string|WP_Error
	 */
	public function read_plain_prefix( $file_path, $length );

	/**
	 * Parse header of an encrypted model.
	 *
	 * @param resource $handle Open stream resource.
	 * @return array{iv:string,size:int,name:string,flags:int}|WP_Error
	 */
	public function parse_header( $handle );

	/**
	 * Confirm a stored payload still matches its authentication tag.
	 *
	 * Legacy files written before tags existed are accepted.
	 *
	 * @param resource $handle Stream positioned at the payload.
	 * @param string   $iv     Initialization vector.
	 * @param int      $size   Payload length.
	 * @param int      $flags  Header flags.
	 * @return true|WP_Error
	 */
	public function verify_payload( $handle, $iv, $size, $flags );

	/**
	 * Open an encrypted file and verify its authentication tag.
	 *
	 * @param string $file_path File path.
	 * @return true|WP_Error
	 */
	public function verify_file( $file_path );

	/**
	 * XOR-mask/unmask chunk with IV and secret.
	 *
	 * @param string $iv     16-byte initialization vector.
	 * @param string $chunk  Raw or masked data.
	 * @param int    $offset Byte offset within file.
	 * @return string|WP_Error
	 */
	public function xor_chunk( $iv, $chunk, $offset );
}
