<?php
/**
 * Admin assets manager.
 *
 * @package SwiftGLB
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles registration and enqueueing of admin CSS and JS.
 */
class SwiftGLB_Admin_Assets {

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
	 * Enqueue assets on product edit screens.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue( $hook ) {
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || 'product' !== $screen->post_type ) {
			return;
		}

		$viewer = '';
		if ( file_exists( SWIFTGLB_PATH . 'assets/vendor/model-viewer.min.js' ) ) {
			$viewer = SWIFTGLB_URL . 'assets/vendor/model-viewer.min.js';
		}

		wp_enqueue_style( 'swiftglb-admin', SWIFTGLB_URL . 'assets/css/admin.css', array(), SWIFTGLB_VERSION );
		wp_enqueue_script( 'swiftglb-admin', SWIFTGLB_URL . 'assets/js/admin.js', array(), SWIFTGLB_VERSION, true );

		wp_localize_script(
			'swiftglb-admin',
			'swiftglbAdmin',
			array(
				'ajax'       => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'swiftglb_upload' ),
				'preview'    => wp_create_nonce( 'swiftglb_preview' ),
				'viewer'     => esc_url_raw( $viewer ),
				'max'        => $this->storage->get_max_bytes(),
				'maxMessage' => sprintf(
					/* translators: %s: maximum upload size. */
					__( 'The file is larger than the limit of %s.', 'swiftglb-3d-ar-product-viewer' ),
					size_format( $this->storage->get_max_bytes() )
				),
				'upload'     => __( 'Upload a 3D file', 'swiftglb-3d-ar-product-viewer' ),
				'uploading'  => __( 'Sending…', 'swiftglb-3d-ar-product-viewer' ),
				'empty'      => __( 'No file selected', 'swiftglb-3d-ar-product-viewer' ),
				'error'      => __( 'The upload failed. Check the connection and try again.', 'swiftglb-3d-ar-product-viewer' ),
				'badType'    => __( 'Only a GLB file or a protected plugin file is accepted.', 'swiftglb-3d-ar-product-viewer' ),
				'preview3d'  => __( 'Live 3D preview', 'swiftglb-3d-ar-product-viewer' ),
				'close'      => __( 'Close', 'swiftglb-3d-ar-product-viewer' ),
				'sending'    => __( 'Sending the file to the server…', 'swiftglb-3d-ar-product-viewer' ),
				'protecting' => __( 'Protecting the file on the server…', 'swiftglb-3d-ar-product-viewer' ),
				'bytesOf'    =>
					/* translators: %1$s: amount uploaded, %2$s: total size. */
					__( '%1$s of %2$s', 'swiftglb-3d-ar-product-viewer' ),
				'zeroSize'   => __( '0 MB', 'swiftglb-3d-ar-product-viewer' ),
				'megabytes'  =>
					/* translators: %s: size in megabytes. */
					__( '%s MB', 'swiftglb-3d-ar-product-viewer' ),
				'kilobytes'  =>
					/* translators: %s: size in kilobytes. */
					__( '%s KB', 'swiftglb-3d-ar-product-viewer' ),
				'previewTitle' => __( '3D model preview', 'swiftglb-3d-ar-product-viewer' ),
				'previewLoading' => __( 'Loading the model…', 'swiftglb-3d-ar-product-viewer' ),
				'previewError' => __( 'The preview could not be loaded.', 'swiftglb-3d-ar-product-viewer' ),
				'uploadingStatus' => __( 'Uploading the file…', 'swiftglb-3d-ar-product-viewer' ),
			)
		);
	}
}
