<?php
/**
 * Cryptographic service implementation.
 *
 * @package SwiftGLB
 */

defined( 'ABSPATH' ) || exit;

/**
 * Encrypts, decrypts, and manages headers for obfuscated 3D model files.
 */
class SwiftGLB_Crypto implements SwiftGLB_Crypto_Interface {

	const MAGIC    = 'W3D1';
	const VERSION  = 1;
	const FLAG_MAC = 2;

	/**
	 * @var string|null
	 */
	private $cached_secret = null;

	/**
	 * Retrieve site secret.
	 *
	 * @return string
	 */
	public function get_secret() {
		if ( null !== $this->cached_secret ) {
			return $this->cached_secret;
		}

		$secret = get_option( 'swiftglb_secret' );
		if ( ! is_string( $secret ) || strlen( $secret ) < 64 ) {
			$legacy = get_option( '3dsaz_secret' );
			if ( is_string( $legacy ) && strlen( $legacy ) >= 64 ) {
				$secret = $legacy;
				update_option( 'swiftglb_secret', $secret, false );
			}
		}
		if ( is_string( $secret ) && strlen( $secret ) >= 64 ) {
			$this->cached_secret = $secret;
			return $secret;
		}

		$secret = bin2hex( random_bytes( 32 ) );
		update_option( 'swiftglb_secret', $secret, false );
		$this->cached_secret = $secret;
		return $secret;
	}

	/**
	 * @param string $name Raw name.
	 * @return string
	 */
	public function sanitize_header_name( $name ) {
		$clean = sanitize_file_name( $name );
		if ( '' === $clean ) {
			$clean = 'model.glb';
		}
		if ( strlen( $clean ) > 255 ) {
			$clean = substr( $clean, 0, 255 );
		}
		return $clean;
	}

	/**
	 * Encrypt a GLB file to an obfuscated .dat destination.
	 *
	 * @param string $source_path Source GLB file.
	 * @param string $dest_path   Destination encrypted file.
	 * @param string $name        Stored filename.
	 * @param int    $size        Uncompressed byte length.
	 * @return true|WP_Error
	 */
	public function encrypt_file( $source_path, $dest_path, $name, $size ) {
		$input = fopen( $source_path, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( ! $input ) {
			return new WP_Error( 'swiftglb_read', __( 'The file could not be read.', 'swiftglb' ) );
		}

		$temporary = $dest_path . '.tmp';
		$output    = fopen( $temporary, 'wb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( ! $output ) {
			fclose( $input ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			return new WP_Error( 'swiftglb_write', __( 'The file could not be saved.', 'swiftglb' ) );
		}

		$iv      = random_bytes( 16 );
		$name    = $this->sanitize_header_name( $name );
		$mac_key = $this->mac_key( $iv );
		$mac_ctx = hash_init( 'sha256', HASH_HMAC, $mac_key );
		if ( false === $mac_ctx ) {
			fclose( $input ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			wp_delete_file( $temporary );
			return new WP_Error( 'swiftglb_key', __( 'The key could not be created.', 'swiftglb' ) );
		}

		$header = self::MAGIC
			. chr( self::VERSION )
			. chr( self::FLAG_MAC )
			. pack( 'V', $size )
			. $iv
			. pack( 'v', strlen( $name ) )
			. $name;

		$offset = 0;
		$ok     = false !== fwrite( $output, $header ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite

		while ( $ok && ! feof( $input ) ) {
			$chunk = fread( $input, 65536 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
			if ( ! is_string( $chunk ) || '' === $chunk ) {
				break;
			}
			$masked = $this->xor_chunk( $iv, $chunk, $offset );
			if ( is_wp_error( $masked ) || false === fwrite( $output, $masked ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
				$ok = false;
				break;
			}
			hash_update( $mac_ctx, $masked );
			$offset += strlen( $chunk );
		}

		if ( $ok ) {
			$mac = hash_final( $mac_ctx, true );
			if ( ! is_string( $mac ) || 32 !== strlen( $mac ) || false === fwrite( $output, $mac ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
				$ok = false;
			}
		}

		fclose( $input ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		if ( ! $ok || $offset !== $size ) {
			wp_delete_file( $temporary );
			return new WP_Error( 'swiftglb_write', __( 'Protecting the file did not finish.', 'swiftglb' ) );
		}

		if ( ! rename( $temporary, $dest_path ) ) {
			wp_delete_file( $temporary );
			return new WP_Error( 'swiftglb_write', __( 'The file could not be saved.', 'swiftglb' ) );
		}

		return true;
	}

	/**
	 * Read plain prefix bytes from an encrypted file.
	 *
	 * @param string $file_path File path.
	 * @param int    $length    Prefix length.
	 * @return string|WP_Error
	 */
	public function read_plain_prefix( $file_path, $length ) {
		$handle = fopen( $file_path, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( ! $handle ) {
			return new WP_Error( 'swiftglb_read', __( 'The file could not be read.', 'swiftglb' ) );
		}
		$header = $this->parse_header( $handle );
		if ( is_wp_error( $header ) ) {
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			return $header;
		}
		$chunk = $this->read_exact( $handle, $length );
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		if ( is_wp_error( $chunk ) ) {
			return $chunk;
		}
		return $this->xor_chunk( $header['iv'], $chunk, 0 );
	}

	/**
	 * Parse header of an encrypted model.
	 *
	 * @param resource $handle Open stream resource.
	 * @return array{iv:string,size:int,name:string,flags:int}|WP_Error
	 */
	public function parse_header( $handle ) {
		$magic = $this->read_exact( $handle, 4 );
		if ( is_wp_error( $magic ) || self::MAGIC !== $magic ) {
			return new WP_Error( 'swiftglb_format', __( 'The file format was not recognized.', 'swiftglb' ) );
		}

		$version = $this->read_exact( $handle, 1 );
		$flags   = $this->read_exact( $handle, 1 );
		$packed  = $this->read_exact( $handle, 4 );
		$iv      = $this->read_exact( $handle, 16 );
		$len_raw = $this->read_exact( $handle, 2 );

		if ( is_wp_error( $version ) || is_wp_error( $flags ) || is_wp_error( $packed ) || is_wp_error( $iv ) || is_wp_error( $len_raw ) ) {
			return new WP_Error( 'swiftglb_header', __( 'The file header is incomplete.', 'swiftglb' ) );
		}

		if ( ord( $version ) !== self::VERSION ) {
			return new WP_Error( 'swiftglb_version', __( 'This file was made by a different plugin version.', 'swiftglb' ) );
		}

		$size_unpacked = unpack( 'Vsize', $packed );
		$name_len      = unpack( 'vlen', $len_raw );
		if ( ! is_array( $size_unpacked ) || ! isset( $size_unpacked['size'] ) || ! is_array( $name_len ) || ! isset( $name_len['len'] ) ) {
			return new WP_Error( 'swiftglb_unpack', __( 'The file header size could not be read.', 'swiftglb' ) );
		}

		$len  = (int) $name_len['len'];
		$name = '';
		if ( $len > 0 ) {
			$name_raw = $this->read_exact( $handle, $len );
			if ( is_wp_error( $name_raw ) ) {
				return $name_raw;
			}
			$name = $this->sanitize_header_name( $name_raw );
		}

		return array(
			'iv'    => $iv,
			'size'  => (int) $size_unpacked['size'],
			'name'  => '' !== $name ? $name : 'model.glb',
			'flags' => ord( $flags ),
		);
	}

	/**
	 * @param resource $handle Stream positioned at the payload.
	 * @param string   $iv     Initialization vector.
	 * @param int      $size   Payload length.
	 * @param int      $flags  Header flags.
	 * @return true|WP_Error
	 */
	public function verify_payload( $handle, $iv, $size, $flags ) {
		$start = ftell( $handle );
		if ( false === $start ) {
			return new WP_Error( 'swiftglb_read', __( 'The file could not be read.', 'swiftglb' ) );
		}

		if ( ( (int) $flags & self::FLAG_MAC ) !== self::FLAG_MAC ) {
			return true;
		}

		$context = hash_init( 'sha256', HASH_HMAC, $this->mac_key( $iv ) );
		if ( false === $context ) {
			return new WP_Error( 'swiftglb_key', __( 'The key could not be created.', 'swiftglb' ) );
		}

		$left = (int) $size;
		while ( $left > 0 && ! feof( $handle ) ) {
			$chunk = fread( $handle, min( 65536, $left ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
			if ( ! is_string( $chunk ) || '' === $chunk ) {
				break;
			}
			hash_update( $context, $chunk );
			$left -= strlen( $chunk );
		}

		$stored = 0 === $left ? fread( $handle, 32 ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
		fseek( $handle, $start );

		$expected = hash_final( $context, true );
		if ( 0 !== $left || ! is_string( $stored ) || ! is_string( $expected ) || strlen( $stored ) !== strlen( $expected ) || ! hash_equals( $expected, $stored ) ) {
			return new WP_Error( 'swiftglb_corrupt', __( 'The model file is damaged.', 'swiftglb' ) );
		}

		return true;
	}

	/**
	 * @param string $file_path File path.
	 * @return true|WP_Error
	 */
	public function verify_file( $file_path ) {
		$handle = fopen( $file_path, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( ! $handle ) {
			return new WP_Error( 'swiftglb_read', __( 'The file could not be read.', 'swiftglb' ) );
		}

		$header = $this->parse_header( $handle );
		if ( is_wp_error( $header ) ) {
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			return $header;
		}

		$result = $this->verify_payload( $handle, $header['iv'], $header['size'], $header['flags'] );
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		return $result;
	}

	/**
	 * @param string $iv Initialization vector.
	 * @return string
	 */
	private function mac_key( $iv ) {
		return hash( 'sha256', $this->get_secret() . $iv . '3dsaz-mac', true );
	}

	/**
	 * XOR-mask/unmask chunk with IV and secret.
	 *
	 * @param string $iv     16-byte initialization vector.
	 * @param string $chunk  Raw or masked data.
	 * @param int    $offset Byte offset within file.
	 * @return string|WP_Error
	 */
	public function xor_chunk( $iv, $chunk, $offset ) {
		$length = strlen( $chunk );
		if ( 0 === $length ) {
			return '';
		}

		$key = hash( 'sha256', $this->get_secret() . $iv, true );
		if ( ! is_string( $key ) || 32 !== strlen( $key ) ) {
			return new WP_Error( 'swiftglb_key', __( 'The key could not be created.', 'swiftglb' ) );
		}

		$start_block = (int) intdiv( $offset, 32 );
		$end_block   = (int) intdiv( $offset + $length - 1, 32 );
		$blocks      = '';

		for ( $block = $start_block; $block <= $end_block; $block++ ) {
			$blocks .= hash( 'sha256', $key . pack( 'N', $block ), true );
		}

		$block_offset = $offset % 32;
		$keystream    = substr( $blocks, $block_offset, $length );

		return $chunk ^ $keystream;
	}

	/**
	 * @param resource $handle Open stream resource.
	 * @param int      $bytes  Number of bytes.
	 * @return string|WP_Error
	 */
	public function read_exact( $handle, $bytes ) {
		$buffer = '';
		while ( strlen( $buffer ) < $bytes && ! feof( $handle ) ) {
			$read = fread( $handle, $bytes - strlen( $buffer ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
			if ( false === $read || '' === $read ) {
				break;
			}
			$buffer .= $read;
		}
		if ( strlen( $buffer ) !== $bytes ) {
			return new WP_Error( 'swiftglb_short_read', __( 'The file ended before the expected size.', 'swiftglb' ) );
		}
		return $buffer;
	}
}
