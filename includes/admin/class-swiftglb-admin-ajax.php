<?php
/**
 * Admin AJAX controller.
 *
 * @package SwiftGLB
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles AJAX requests for admin file uploads and live 3D preview.
 */
class SwiftGLB_Admin_Ajax {

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
	 * Handle AJAX file upload.
	 *
	 * @return void
	 */
	public function handle_upload() {
		check_ajax_referer( 'swiftglb_upload', 'nonce' );
		add_filter( 'wp_check_filetype_and_ext', array( 'SwiftGLB_Plugin', 'filetype' ), 10, 4 );
		if ( ! current_user_can( 'edit_products' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to do this.', 'swiftglb-3d-ar-product-viewer' ) ), 403 );
		}

		if ( empty( $_FILES['file'] ) || ! is_array( $_FILES['file'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			wp_send_json_error( array( 'message' => __( 'No file was sent.', 'swiftglb-3d-ar-product-viewer' ) ), 400 );
		}

		$file  = $_FILES['file']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$error = isset( $file['error'] ) ? (int) $file['error'] : UPLOAD_ERR_NO_FILE;
		if ( UPLOAD_ERR_OK !== $error ) {
			wp_send_json_error( array( 'message' => $this->upload_error_message( $error ) ), 400 );
		}

		$tmp  = isset( $file['tmp_name'] ) ? (string) $file['tmp_name'] : '';
		$name = isset( $file['name'] ) ? sanitize_file_name( (string) $file['name'] ) : '';
		if ( ! is_uploaded_file( $tmp ) ) {
			wp_send_json_error( array( 'message' => __( 'The uploaded file is not valid.', 'swiftglb-3d-ar-product-viewer' ) ), 400 );
		}

		$checked = wp_check_filetype_and_ext( $tmp, $name );
		$ext     = isset( $checked['ext'] ) ? strtolower( (string) $checked['ext'] ) : '';
		if ( ! in_array( $ext, array( 'glb', 'w3d', 'bin' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'This file extension is not allowed.', 'swiftglb-3d-ar-product-viewer' ) ), 400 );
		}

		$upload = apply_filters(
			'wp_handle_upload_prefilter',
			array(
				'name'     => $name,
				'type'     => isset( $checked['type'] ) ? (string) $checked['type'] : 'application/octet-stream',
				'tmp_name' => $tmp,
				'error'    => 0,
				'size'     => isset( $file['size'] ) ? (int) $file['size'] : 0,
			)
		);
		if ( ! empty( $upload['error'] ) ) {
			$message = is_string( $upload['error'] ) ? $upload['error'] : __( 'The upload was rejected.', 'swiftglb-3d-ar-product-viewer' );
			wp_send_json_error( array( 'message' => $message ), 400 );
		}

		$token = $this->storage->import_file( $tmp, $name );
		if ( is_wp_error( $token ) ) {
			wp_send_json_error( array( 'message' => $token->get_error_message() ), 400 );
		}

		$info = $this->storage->inspect( $token );
		wp_send_json_success(
			array(
				'token'    => $token,
				'filename' => '' !== $name ? $name : 'model.glb',
				'size'     => is_wp_error( $info ) ? '' : size_format( (int) $info['size'] ),
			)
		);
	}

	/**
	 * Stream model for authorized admin preview.
	 *
	 * @return void
	 */
	public function handle_preview() {
		check_ajax_referer( 'swiftglb_preview', 'nonce' );
		if ( ! current_user_can( 'edit_products' ) ) {
			status_header( 403 );
			exit;
		}

		$token = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';
		if ( ! $this->storage->is_valid_token( $token ) || ! $this->storage->exists( $token ) ) {
			status_header( 404 );
			exit;
		}

		$meta = $this->storage->inspect( $token );
		if ( is_wp_error( $meta ) ) {
			status_header( 404 );
			exit;
		}

		nocache_headers();
		header( 'Content-Type: model/gltf-binary' );
		header( 'Content-Length: ' . $meta['size'] );
		header( 'Content-Disposition: inline; filename="preview.glb"' );

		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}

		$this->storage->stream( $token );
		exit;
	}

	/**
	 * @param int $code PHP upload error.
	 * @return string
	 */
	private function upload_error_message( $code ) {
		if ( UPLOAD_ERR_INI_SIZE === $code || UPLOAD_ERR_FORM_SIZE === $code ) {
			return __( 'The file is larger than the server allows.', 'swiftglb-3d-ar-product-viewer' );
		}
		if ( UPLOAD_ERR_PARTIAL === $code ) {
			return __( 'The upload was incomplete. Try again.', 'swiftglb-3d-ar-product-viewer' );
		}
		return __( 'The upload failed.', 'swiftglb-3d-ar-product-viewer' );
	}
}
