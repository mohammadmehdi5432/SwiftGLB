<?php
/**
 * Frontend assets manager.
 *
 * @package SwiftGLB
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles registering, configuring, and enqueuing frontend CSS and JS.
 */
class SwiftGLB_Frontend_Assets {

	/**
	 * Enqueue frontend scripts and styles.
	 *
	 * @return void
	 */
	public function enqueue() {
		if ( wp_script_is( 'swiftglb-frontend', 'enqueued' ) ) {
			return;
		}

		$late = did_action( 'wp_print_styles' ) > 0;
		wp_register_style( 'swiftglb-frontend', SWIFTGLB_URL . 'assets/css/frontend.css', array(), SWIFTGLB_VERSION );
		wp_register_script( 'swiftglb-frontend', SWIFTGLB_URL . 'assets/js/frontend.js', array( 'jquery' ), SWIFTGLB_VERSION, true );
		wp_localize_script( 'swiftglb-frontend', 'swiftglb', $this->get_script_config() );
		wp_enqueue_style( 'swiftglb-frontend' );
		wp_enqueue_script( 'swiftglb-frontend' );

		if ( $late ) {
			add_action(
				'wp_footer',
				static function () {
					wp_print_styles( 'swiftglb-frontend' );
				},
				1
			);
		}
	}

	/**
	 * Build script config and translation map.
	 *
	 * @return array<string,mixed>
	 */
	public function get_script_config() {
		$viewer = '';
		if ( file_exists( SWIFTGLB_PATH . 'assets/vendor/model-viewer.min.js' ) ) {
			$viewer = SWIFTGLB_URL . 'assets/vendor/model-viewer.min.js';
		}

		$draco = '';
		if ( file_exists( SWIFTGLB_PATH . 'assets/vendor/draco/draco_decoder.wasm' ) ) {
			$draco = SWIFTGLB_URL . 'assets/vendor/draco/';
		}

		$config = array(
			'rest'   => esc_url_raw( rest_url( 'swiftglb/v1/model/' ) ),
			'ajax'   => esc_url_raw( admin_url( 'admin-ajax.php' ) ),
			'nonce'  => wp_create_nonce( 'wp_rest' ),
			'view'   => wp_create_nonce( 'swiftglb_view' ),
			'viewer' => esc_url_raw( $viewer ),
			'draco'  => esc_url_raw( $draco ),
			'qr'     => esc_url_raw( SWIFTGLB_URL . 'assets/js/qrcode.js' ),
			'i18n'   => array(
				'loading'            => __( 'Loading…', 'swiftglb-3d-ar-product-viewer' ),
				'loadingModel'       => __( 'Downloading the 3D model…', 'swiftglb-3d-ar-product-viewer' ),
				'loadingHd'          => __( 'Downloading the high-quality model…', 'swiftglb-3d-ar-product-viewer' ),
				'rendering'          => __( 'Preparing the 3D view…', 'swiftglb-3d-ar-product-viewer' ),
				'connecting'         => __( 'Connecting…', 'swiftglb-3d-ar-product-viewer' ),
				'bytesOf'            =>
					/* translators: %1$s: amount downloaded, %2$s: total size. */
					__( '%1$s of %2$s', 'swiftglb-3d-ar-product-viewer' ),
				'downloaded'         =>
					/* translators: %s: amount downloaded. */
					__( '%s downloaded', 'swiftglb-3d-ar-product-viewer' ),
				'zeroSize'           => __( '0 MB', 'swiftglb-3d-ar-product-viewer' ),
				'megabytes'          =>
					/* translators: %s: size in megabytes. */
					__( '%s MB', 'swiftglb-3d-ar-product-viewer' ),
				'kilobytes'          =>
					/* translators: %s: size in kilobytes. */
					__( '%s KB', 'swiftglb-3d-ar-product-viewer' ),
				'qualityHd'          => __( 'High quality (HD)', 'swiftglb-3d-ar-product-viewer' ),
				'qualitySd'          => __( 'Standard quality (smaller)', 'swiftglb-3d-ar-product-viewer' ),
				'switchToHd'         => __( 'Switch to high quality (HD)', 'swiftglb-3d-ar-product-viewer' ),
				'switchToSd'         => __( 'Switch to standard quality', 'swiftglb-3d-ar-product-viewer' ),
				'hdActive'           => __( 'High quality is on', 'swiftglb-3d-ar-product-viewer' ),
				'errorTitle'         => __( 'The model did not open', 'swiftglb-3d-ar-product-viewer' ),
				'error'              => __( 'This model could not be displayed. Check the connection and try again.', 'swiftglb-3d-ar-product-viewer' ),
				'unsupportedTitle'   => __( 'This device is not supported', 'swiftglb-3d-ar-product-viewer' ),
				'unsupported'        => __( 'This browser or device cannot display a 3D model.', 'swiftglb-3d-ar-product-viewer' ),
				'arUnsupportedTitle' => __( 'Augmented reality is not available', 'swiftglb-3d-ar-product-viewer' ),
				'arUnsupported'      => __( 'This device cannot start augmented reality. It needs a browser with WebXR, ARCore, or Quick Look.', 'swiftglb-3d-ar-product-viewer' ),
				'retry'              => __( 'Try again', 'swiftglb-3d-ar-product-viewer' ),
				'dismiss'            => __( 'OK', 'swiftglb-3d-ar-product-viewer' ),
				'close'              => __( 'Close', 'swiftglb-3d-ar-product-viewer' ),
				'day'                => __( 'Day', 'swiftglb-3d-ar-product-viewer' ),
				'night'              => __( 'Night', 'swiftglb-3d-ar-product-viewer' ),
				'ar'                 => __( 'Augmented reality', 'swiftglb-3d-ar-product-viewer' ),
				'fullscreen'         => __( 'Full screen', 'swiftglb-3d-ar-product-viewer' ),
				'exitFullscreen'     => __( 'Exit full screen', 'swiftglb-3d-ar-product-viewer' ),
				'autoRotate'         => __( 'Auto-rotate', 'swiftglb-3d-ar-product-viewer' ),
				'resetView'          => __( 'Reset the view', 'swiftglb-3d-ar-product-viewer' ),
				'snapshot'           => __( 'Save a snapshot', 'swiftglb-3d-ar-product-viewer' ),
				'qrTitle'            => __( 'View in augmented reality on a phone', 'swiftglb-3d-ar-product-viewer' ),
				'qrDesc'             => __( 'Scan this code with your phone camera to place the product in your room.', 'swiftglb-3d-ar-product-viewer' ),
				'qrScan'             => __( 'Scan with your phone', 'swiftglb-3d-ar-product-viewer' ),
			),
		);

		return apply_filters( 'swiftglb_script_config', $config );
	}
}
