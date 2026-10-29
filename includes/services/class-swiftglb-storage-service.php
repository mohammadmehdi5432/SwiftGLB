<?php
/**
 * Storage service implementation.
 *
 * @package SwiftGLB
 */

defined( 'ABSPATH' ) || exit;

/**
 * Manages physical files, directory permissions, and streaming.
 */
class SwiftGLB_Storage_Service implements SwiftGLB_Storage_Interface {

	/**
	 * @var SwiftGLB_Crypto_Interface
	 */
	private $crypto;

	/**
	 * @var string|null
	 */
	private $resolved_dir = null;

	/**
	 * @param SwiftGLB_Crypto_Interface $crypto Cryptographic service.
	 */
	public function __construct( SwiftGLB_Crypto_Interface $crypto ) {
		$this->crypto = $crypto;
	}

	/**
	 * @return SwiftGLB_Crypto_Interface
	 */
	public function get_crypto() {
		return $this->crypto;
	}

	/**
	 * Private storage directory.
	 *
	 * @return string
	 */
	public function get_dir() {
		if ( null !== $this->resolved_dir ) {
			return $this->resolved_dir;
		}

		$legacy = $this->legacy_dir();
		if ( '' === $legacy ) {
			$this->resolved_dir = '';
			return '';
		}

		$filtered = (string) apply_filters( 'swiftglb_private_dir', $legacy );
		$filtered = (string) apply_filters( '3dsaz_private_dir', $filtered );
		if ( $filtered !== $legacy ) {
			$this->resolved_dir = $filtered;
		} else {
			$secure = $this->secure_dir_candidate();
			if ( '' !== $secure && $this->prepare_secure_dir( $secure, $legacy ) ) {
				$this->resolved_dir = $secure;
			} else {
				$this->resolved_dir = $legacy;
			}
		}

		$this->absorb_older_dirs( $this->resolved_dir );
		return $this->resolved_dir;
	}

	/**
	 * Whether the active storage directory can be fetched as a static file.
	 *
	 * @return bool
	 */
	public function is_inside_web_root() {
		$dir = $this->get_dir();
		if ( '' === $dir || ! defined( 'ABSPATH' ) ) {
			return true;
		}

		$root = realpath( ABSPATH );
		$real = realpath( $dir );
		if ( false === $root ) {
			return true;
		}
		if ( false === $real ) {
			$real = $dir;
		}

		$root = trailingslashit( wp_normalize_path( $root ) );
		$real = wp_normalize_path( $real );
		return $real === untrailingslashit( $root ) || 0 === strpos( trailingslashit( $real ), $root );
	}

	/**
	 * Default directory inside the uploads folder.
	 *
	 * @return string
	 */
	private function legacy_dir() {
		$uploads = wp_upload_dir();
		if ( ! empty( $uploads['error'] ) ) {
			return '';
		}
		return wp_normalize_path( trailingslashit( $uploads['basedir'] ) . 'swiftglb-private' );
	}

	/**
	 * Directory beside the web root, outside public URLs.
	 *
	 * @return string
	 */
	private function secure_dir_candidate() {
		if ( ! defined( 'ABSPATH' ) ) {
			return '';
		}

		$root   = wp_normalize_path( untrailingslashit( ABSPATH ) );
		$parent = wp_normalize_path( dirname( $root ) );
		if ( '' === $parent || '.' === $parent || $parent === $root ) {
			return '';
		}

		$root_prefix   = trailingslashit( $root );
		$parent_prefix = trailingslashit( $parent );
		if ( 0 === strpos( $parent_prefix, $root_prefix ) ) {
			return '';
		}

		$blog_id = function_exists( 'get_current_blog_id' ) ? (int) get_current_blog_id() : 1;
		return $parent . '/swiftglb-private/' . $blog_id;
	}

	/**
	 * Folders used before the SwiftGLB rename.
	 *
	 * @return string[]
	 */
	private function older_brand_dirs() {
		$dirs = array();
		$uploads = wp_upload_dir();
		if ( empty( $uploads['error'] ) ) {
			$dirs[] = wp_normalize_path( trailingslashit( $uploads['basedir'] ) . '3dsaz-private' );
		}
		if ( ! defined( 'ABSPATH' ) ) {
			return $dirs;
		}

		$root   = wp_normalize_path( untrailingslashit( ABSPATH ) );
		$parent = wp_normalize_path( dirname( $root ) );
		if ( '' === $parent || '.' === $parent || $parent === $root ) {
			return $dirs;
		}

		$blog_id = function_exists( 'get_current_blog_id' ) ? (int) get_current_blog_id() : 1;
		$dirs[]  = $parent . '/3dsaz-private/' . $blog_id;
		return $dirs;
	}

	/**
	 * Move models saved by the previous plugin name into the active folder.
	 *
	 * @param string $target Active storage directory.
	 * @return void
	 */
	private function absorb_older_dirs( $target ) {
		if ( '' === $target ) {
			return;
		}
		$target_path = wp_normalize_path( $target );
		foreach ( $this->older_brand_dirs() as $from ) {
			if ( wp_normalize_path( $from ) === $target_path ) {
				continue;
			}
			$this->relocate_dat_files( $from, $target );
		}
	}

	/**
	 * Create the outside directory and move existing models into it.
	 *
	 * @param string $secure Destination directory.
	 * @param string $legacy Source directory.
	 * @return bool
	 */
	private function prepare_secure_dir( $secure, $legacy ) {
		if ( ! wp_mkdir_p( $secure ) || ! wp_is_writable( $secure ) ) {
			return false;
		}

		$real_secure = realpath( $secure );
		$real_root   = defined( 'ABSPATH' ) ? realpath( ABSPATH ) : false;
		if ( false !== $real_secure && false !== $real_root ) {
			$prefix = trailingslashit( wp_normalize_path( $real_root ) );
			$path   = wp_normalize_path( $real_secure );
			if ( $path === untrailingslashit( $prefix ) || 0 === strpos( trailingslashit( $path ), $prefix ) ) {
				return false;
			}
		}

		return $this->relocate_dat_files( $legacy, $secure );
	}

	/**
	 * Move every model file, or put them back if a move fails.
	 *
	 * @param string $from Source directory.
	 * @param string $to   Destination directory.
	 * @return bool
	 */
	private function relocate_dat_files( $from, $to ) {
		if ( '' === $from || ! is_dir( $from ) ) {
			return true;
		}

		$files = glob( trailingslashit( $from ) . '*.dat' );
		if ( ! is_array( $files ) || ! $files ) {
			return true;
		}

		$moved = array();
		foreach ( $files as $file ) {
			if ( ! is_file( $file ) ) {
				continue;
			}
			$dest = trailingslashit( $to ) . basename( $file );
			if ( is_file( $dest ) ) {
				$source_size = filesize( $file );
				$dest_size   = filesize( $dest );
				if ( false === $source_size || $source_size !== $dest_size ) {
					$this->rollback_moves( $moved );
					return false;
				}
				wp_delete_file( $file );
				continue;
			}
			if ( ! $this->move_file( $file, $dest ) ) {
				$this->rollback_moves( $moved );
				return false;
			}
			$moved[] = array( $dest, $file );
		}

		return true;
	}

	/**
	 * @param array<int,array{0:string,1:string}> $moved Destination and original path pairs.
	 * @return void
	 */
	private function rollback_moves( $moved ) {
		foreach ( array_reverse( $moved ) as $pair ) {
			$this->move_file( $pair[0], $pair[1] );
		}
	}

	/**
	 * @param string $from Source path.
	 * @param string $to   Destination path.
	 * @return bool
	 */
	private function move_file( $from, $to ) {
		if ( @rename( $from, $to ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			return true;
		}
		if ( ! @copy( $from, $to ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			return false;
		}
		wp_delete_file( $from );
		return ! is_file( $from );
	}

	/**
	 * Create the private directory and block direct web access where the server allows it.
	 *
	 * @return true|WP_Error
	 */
	public function protect_directory() {
		$dir = $this->get_dir();
		if ( '' === $dir ) {
			return new WP_Error( 'swiftglb_uploads', __( 'The WordPress uploads folder is not available.', 'swiftglb' ) );
		}
		if ( ! wp_mkdir_p( $dir ) ) {
			return new WP_Error( 'swiftglb_dir', __( 'The model folder could not be created.', 'swiftglb' ) );
		}

		$htaccess = $dir . '/.htaccess';
		if ( ! file_exists( $htaccess ) ) {
			file_put_contents( // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
				$htaccess,
				"Options -Indexes\n<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nOrder allow,deny\nDeny from all\n</IfModule>\n"
			);
		}

		$web_config = $dir . '/web.config';
		if ( ! file_exists( $web_config ) ) {
			file_put_contents( // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
				$web_config,
				"<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<configuration><system.webServer><security><authorization><remove users=\"*\" roles=\"\" verbs=\"\" /><add accessType=\"Deny\" users=\"*\" /></authorization></security></system.webServer></configuration>\n"
			);
		}

		$index = $dir . '/index.php';
		if ( ! file_exists( $index ) ) {
			file_put_contents( $index, "<?php\n// Silence is golden.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		}

		return true;
	}

	/**
	 * @param mixed $token Token.
	 * @return bool
	 */
	public function is_valid_token( $token ) {
		return is_string( $token ) && 1 === preg_match( '/^[a-f0-9]{32}$/', $token );
	}

	/**
	 * @return int
	 */
	public function get_max_bytes() {
		$max = (int) wp_max_upload_size();
		if ( $max < 1 ) {
			$max = 32 * MB_IN_BYTES;
		}
		$cap = (int) apply_filters( 'swiftglb_max_bytes', 80 * MB_IN_BYTES );
		if ( $cap < 1 ) {
			$cap = 80 * MB_IN_BYTES;
		}
		return (int) min( $max, $cap );
	}

	/**
	 * @param string $token Token.
	 * @return string
	 */
	public function get_path( $token ) {
		if ( ! $this->is_valid_token( $token ) ) {
			return '';
		}
		$dir = $this->get_dir();
		if ( '' === $dir ) {
			return '';
		}
		return trailingslashit( $dir ) . $token . '.dat';
	}

	/**
	 * @param string $token Token.
	 * @return bool
	 */
	public function exists( $token ) {
		$path = $this->get_path( $token );
		return '' !== $path && is_file( $path );
	}

	/**
	 * Check if file path is strictly inside the private storage directory.
	 *
	 * @param string $path Absolute file path.
	 * @return bool
	 */
	public function is_inside_storage( $path ) {
		$dir = $this->get_dir();
		if ( '' === $dir || ! is_string( $path ) ) {
			return false;
		}
		$real_dir = realpath( $dir );
		if ( false === $real_dir ) {
			return false;
		}
		$real_path = realpath( $path );
		if ( false === $real_path ) {
			$parent = realpath( dirname( $path ) );
			if ( false === $parent ) {
				return false;
			}
			$real_path = trailingslashit( $parent ) . basename( $path );
		}
		$real_dir  = trailingslashit( wp_normalize_path( $real_dir ) );
		$real_path = wp_normalize_path( $real_path );
		return 0 === strpos( $real_path, $real_dir );
	}

	/**
	 * Import a GLB or an already wrapped .w3d/.bin file.
	 *
	 * @param string $source_path   Readable source path.
	 * @param string $original_name Original file name.
	 * @return string|WP_Error Token.
	 */
	public function import_file( $source_path, $original_name ) {
		if ( ! is_string( $source_path ) || ! is_readable( $source_path ) ) {
			return new WP_Error( 'swiftglb_read', __( 'The file could not be read.', 'swiftglb' ) );
		}

		$size = filesize( $source_path );
		if ( false === $size || $size < 12 || $size > $this->get_max_bytes() ) {
			return new WP_Error( 'swiftglb_size', __( 'The file size is not allowed.', 'swiftglb' ) );
		}

		$ready = $this->protect_directory();
		if ( is_wp_error( $ready ) ) {
			return $ready;
		}

		$handle = fopen( $source_path, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( ! $handle ) {
			return new WP_Error( 'swiftglb_read', __( 'The file could not be read.', 'swiftglb' ) );
		}
		$magic = fread( $handle, 4 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		$token = bin2hex( random_bytes( 16 ) );
		$dest  = $this->get_path( $token );
		if ( '' === $dest ) {
			return new WP_Error( 'swiftglb_dir', __( 'The model folder is not valid.', 'swiftglb' ) );
		}

		if ( SwiftGLB_Crypto::MAGIC === $magic ) {
			$plain = $this->crypto->read_plain_prefix( $source_path, 4 );
			if ( is_wp_error( $plain ) ) {
				return $plain;
			}
			if ( 'glTF' !== $plain ) {
				return new WP_Error( 'swiftglb_format', __( 'This protected file belongs to another site, or it is damaged.', 'swiftglb' ) );
			}
			$verified = $this->crypto->verify_file( $source_path );
			if ( is_wp_error( $verified ) ) {
				return $verified;
			}
			if ( ! copy( $source_path, $dest ) ) {
				return new WP_Error( 'swiftglb_write', __( 'The file could not be saved.', 'swiftglb' ) );
			}
			return $token;
		}

		if ( 'glTF' !== $magic ) {
			return new WP_Error( 'swiftglb_format', __( 'Only a GLB file, or a protected plugin file (.w3d or .bin), is accepted.', 'swiftglb' ) );
		}

		$written = $this->crypto->encrypt_file( $source_path, $dest, $original_name, (int) $size );
		if ( is_wp_error( $written ) ) {
			wp_delete_file( $dest );
			return $written;
		}

		return $token;
	}

	/**
	 * Inspect stored model metadata.
	 *
	 * @param string $token Token.
	 * @return array{name:string,size:int}|WP_Error
	 */
	public function inspect( $token ) {
		$opened = $this->open_stored( $token );
		if ( is_wp_error( $opened ) ) {
			return $opened;
		}
		fclose( $opened['handle'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		return array(
			'name' => $opened['name'],
			'size' => $opened['size'],
		);
	}

	/**
	 * Stream model plain bytes to output.
	 *
	 * @param string $token Token.
	 * @return true|WP_Error
	 */
	public function stream( $token ) {
		$opened = $this->open_stored( $token );
		if ( is_wp_error( $opened ) ) {
			return $opened;
		}

		$handle = $opened['handle'];
		$iv     = $opened['iv'];
		$total  = (int) $opened['size'];
		$offset = 0;

		while ( $offset < $total && ! feof( $handle ) ) {
			$want  = min( 65536, $total - $offset );
			$chunk = $this->crypto->read_exact( $handle, $want );
			if ( is_wp_error( $chunk ) ) {
				fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
				return $chunk;
			}
			$plain = $this->crypto->xor_chunk( $iv, $chunk, $offset );
			if ( is_wp_error( $plain ) ) {
				fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
				return $plain;
			}
			echo $plain; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			if ( function_exists( 'flush' ) ) {
				flush();
			}
			$offset += strlen( $chunk );
		}

		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		return true;
	}

	/**
	 * Delete a model file by token.
	 *
	 * @param string $token Token.
	 * @return void
	 */
	public function delete( $token ) {
		$path = $this->get_path( $token );
		if ( '' !== $path && is_file( $path ) ) {
			wp_delete_file( $path );
		}
	}

	/**
	 * Delete all stored models.
	 *
	 * @return void
	 */
	public function delete_all() {
		$dirs = array_merge( array( $this->get_dir(), $this->legacy_dir() ), $this->older_brand_dirs() );
		foreach ( array_unique( $dirs ) as $dir ) {
			if ( '' === $dir || ! is_dir( $dir ) ) {
				continue;
			}
			$files = glob( trailingslashit( $dir ) . '*.dat' );
			if ( ! is_array( $files ) ) {
				continue;
			}
			foreach ( $files as $file ) {
				if ( is_file( $file ) ) {
					wp_delete_file( $file );
				}
			}
		}
	}

	/**
	 * Open stored model file and validate header.
	 *
	 * @param string $token Token.
	 * @return array{handle:resource,iv:string,size:int,name:string}|WP_Error
	 */
	private function open_stored( $token ) {
		$path = $this->get_path( $token );
		if ( '' === $path || ! is_file( $path ) || ! $this->is_inside_storage( $path ) ) {
			return new WP_Error( 'swiftglb_missing', __( 'Model not found.', 'swiftglb' ) );
		}

		$handle = fopen( $path, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( ! $handle ) {
			return new WP_Error( 'swiftglb_read', __( 'The model could not be read.', 'swiftglb' ) );
		}

		$header = $this->crypto->parse_header( $handle );
		if ( is_wp_error( $header ) ) {
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			return $header;
		}

		$payload_at = ftell( $handle );
		$file_size  = filesize( $path );
		$flags      = isset( $header['flags'] ) ? (int) $header['flags'] : 0;
		$remaining  = ( false === $payload_at || false === $file_size ) ? -1 : ( $file_size - $payload_at );
		$needed     = (int) $header['size'] + ( ( $flags & SwiftGLB_Crypto::FLAG_MAC ) === SwiftGLB_Crypto::FLAG_MAC ? 32 : 0 );
		if ( $remaining < $needed || $header['size'] > $this->get_max_bytes() ) {
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			return new WP_Error( 'swiftglb_corrupt', __( 'The model file is damaged.', 'swiftglb' ) );
		}

		$verified = $this->crypto->verify_payload( $handle, $header['iv'], (int) $header['size'], $flags );
		if ( is_wp_error( $verified ) ) {
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			return $verified;
		}

		$header['handle'] = $handle;
		return $header;
	}
}
