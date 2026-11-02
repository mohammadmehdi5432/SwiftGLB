<?php
/**
 * Admin assets manager.
 *
 * @package Try3D
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles registration and enqueueing of admin CSS and JS.
 */
class Try3D_Admin_Assets {

	/**
	 * @var Try3D_Storage_Interface
	 */
	private $storage;

	/**
	 * @param Try3D_Storage_Interface $storage Storage service.
	 */
	public function __construct( Try3D_Storage_Interface $storage ) {
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
		if ( file_exists( TRY3D_PATH . 'assets/vendor/model-viewer.min.js' ) ) {
			$viewer = TRY3D_URL . 'assets/vendor/model-viewer.min.js';
		}

		wp_enqueue_style( 'try3d-admin', TRY3D_URL . 'assets/css/admin.css', array(), TRY3D_VERSION );
		wp_enqueue_script( 'try3d-admin', TRY3D_URL . 'assets/js/admin.js', array(), TRY3D_VERSION, true );

		wp_localize_script(
			'try3d-admin',
			'try3dAdmin',
			array(
				'ajax'       => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'try3d_upload' ),
				'preview'    => wp_create_nonce( 'try3d_preview' ),
				'viewer'     => esc_url_raw( $viewer ),
				'max'        => $this->storage->get_max_bytes(),
				'maxMessage' => sprintf(
					/* translators: %s: maximum upload size. */
					__( 'The file is larger than the limit of %s.', 'try3d-viewer' ),
					size_format( $this->storage->get_max_bytes() )
				),
				'upload'     => __( 'Upload a 3D file', 'try3d-viewer' ),
				'uploading'  => __( 'Sending…', 'try3d-viewer' ),
				'empty'      => __( 'No file selected', 'try3d-viewer' ),
				'error'      => __( 'The upload failed. Check the connection and try again.', 'try3d-viewer' ),
				'badType'    => __( 'Only a GLB file or a protected plugin file is accepted.', 'try3d-viewer' ),
				'preview3d'  => __( 'Live 3D preview', 'try3d-viewer' ),
				'close'      => __( 'Close', 'try3d-viewer' ),
				'sending'    => __( 'Sending the file to the server…', 'try3d-viewer' ),
				'protecting' => __( 'Protecting the file on the server…', 'try3d-viewer' ),
				'bytesOf'    =>
					/* translators: %1$s: amount uploaded, %2$s: total size. */
					__( '%1$s of %2$s', 'try3d-viewer' ),
				'zeroSize'   => __( '0 MB', 'try3d-viewer' ),
				'megabytes'  =>
					/* translators: %s: size in megabytes. */
					__( '%s MB', 'try3d-viewer' ),
				'kilobytes'  =>
					/* translators: %s: size in kilobytes. */
					__( '%s KB', 'try3d-viewer' ),
				'previewTitle' => __( '3D model preview', 'try3d-viewer' ),
				'previewLoading' => __( 'Loading the model…', 'try3d-viewer' ),
				'previewError' => __( 'The preview could not be loaded.', 'try3d-viewer' ),
				'uploadingStatus' => __( 'Uploading the file…', 'try3d-viewer' ),
			)
		);
	}
}
