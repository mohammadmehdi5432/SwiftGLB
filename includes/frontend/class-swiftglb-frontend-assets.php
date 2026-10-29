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
				'loading'            => __( 'Loading…', 'swiftglb' ),
				'loadingModel'       => __( 'Downloading the 3D model…', 'swiftglb' ),
				'loadingHd'          => __( 'Loading high quality (HD)…', 'swiftglb' ),
				'rendering'          => __( 'Preparing the 3D view…', 'swiftglb' ),
				'connecting'         => __( 'Connecting…', 'swiftglb' ),
				'bytesOf'            =>
					/* translators: %1$s: amount downloaded, %2$s: total size. */
					__( '%1$s of %2$s', 'swiftglb' ),
				'downloaded'         =>
					/* translators: %s: amount downloaded. */
					__( '%s downloaded', 'swiftglb' ),
				'zeroSize'           => __( '0 MB', 'swiftglb' ),
				'megabytes'          =>
					/* translators: %s: size in megabytes. */
					__( '%s MB', 'swiftglb' ),
				'kilobytes'          =>
					/* translators: %s: size in kilobytes. */
					__( '%s KB', 'swiftglb' ),
				'qualityHd'          => __( 'High quality (HD)', 'swiftglb' ),
				'qualitySd'          => __( 'Standard quality (smaller)', 'swiftglb' ),
				'switchToHd'         => __( 'Switch to high quality (HD)', 'swiftglb' ),
				'switchToSd'         => __( 'Switch to standard quality', 'swiftglb' ),
				'hdActive'           => __( 'High quality is on', 'swiftglb' ),
				'errorTitle'         => __( 'The model did not open', 'swiftglb' ),
				'error'              => __( 'This model could not be displayed. Check the connection and try again.', 'swiftglb' ),
				'unsupportedTitle'   => __( 'This device is not supported', 'swiftglb' ),
				'unsupported'        => __( 'This browser or device cannot display a 3D model.', 'swiftglb' ),
				'arUnsupportedTitle' => __( 'Augmented reality is not available', 'swiftglb' ),
				'arUnsupported'      => __( 'This device cannot start augmented reality. It needs a browser with WebXR, ARCore, or Quick Look.', 'swiftglb' ),
				'retry'              => __( 'Try again', 'swiftglb' ),
				'dismiss'            => __( 'OK', 'swiftglb' ),
				'close'              => __( 'Close', 'swiftglb' ),
				'day'                => __( 'Day', 'swiftglb' ),
				'night'              => __( 'Night', 'swiftglb' ),
				'ar'                 => __( 'Augmented reality', 'swiftglb' ),
				'fullscreen'         => __( 'Full screen', 'swiftglb' ),
				'exitFullscreen'     => __( 'Exit full screen', 'swiftglb' ),
				'autoRotate'         => __( 'Auto-rotate', 'swiftglb' ),
				'resetView'          => __( 'Reset the view', 'swiftglb' ),
				'snapshot'           => __( 'Save a snapshot', 'swiftglb' ),
				'qrTitle'            => __( 'View in augmented reality on a phone', 'swiftglb' ),
				'qrDesc'             => __( 'Scan this code with your phone camera to place the product in your room.', 'swiftglb' ),
				'qrScan'             => __( 'Scan with your phone', 'swiftglb' ),
			),
		);

		return apply_filters( 'swiftglb_script_config', $config );
	}
}
