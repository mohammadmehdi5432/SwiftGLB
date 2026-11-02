<?php
/**
 * Frontend assets manager.
 *
 * @package Try3D
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles registering, configuring, and enqueuing frontend CSS and JS.
 */
class Try3D_Frontend_Assets {

	/**
	 * Enqueue frontend scripts and styles.
	 *
	 * @return void
	 */
	public function enqueue() {
		if ( wp_script_is( 'try3d-frontend', 'enqueued' ) ) {
			return;
		}

		$late = did_action( 'wp_print_styles' ) > 0;
		wp_register_style( 'try3d-frontend', TRY3D_URL . 'assets/css/frontend.css', array(), TRY3D_VERSION );
		wp_register_script( 'try3d-frontend', TRY3D_URL . 'assets/js/frontend.js', array( 'jquery' ), TRY3D_VERSION, true );
		wp_localize_script( 'try3d-frontend', 'try3d', $this->get_script_config() );
		wp_enqueue_style( 'try3d-frontend' );
		wp_enqueue_script( 'try3d-frontend' );

		if ( $late ) {
			add_action(
				'wp_footer',
				static function () {
					wp_print_styles( 'try3d-frontend' );
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
		if ( file_exists( TRY3D_PATH . 'assets/vendor/model-viewer.min.js' ) ) {
			$viewer = TRY3D_URL . 'assets/vendor/model-viewer.min.js';
		}

		$draco = '';
		if ( file_exists( TRY3D_PATH . 'assets/vendor/draco/draco_decoder.wasm' ) ) {
			$draco = TRY3D_URL . 'assets/vendor/draco/';
		}

		$config = array(
			'rest'   => esc_url_raw( rest_url( 'try3d/v1/model/' ) ),
			'ajax'   => esc_url_raw( admin_url( 'admin-ajax.php' ) ),
			'nonce'  => wp_create_nonce( 'wp_rest' ),
			'view'   => wp_create_nonce( 'try3d_view' ),
			'viewer' => esc_url_raw( $viewer ),
			'draco'  => esc_url_raw( $draco ),
			'qr'     => esc_url_raw( TRY3D_URL . 'assets/js/qrcode.js' ),
			'i18n'   => array(
				'loading'            => __( 'Loading…', 'try3d-viewer' ),
				'loadingModel'       => __( 'Downloading the 3D model…', 'try3d-viewer' ),
				'loadingHd'          => __( 'Downloading the original model…', 'try3d-viewer' ),
				'rendering'          => __( 'Preparing the 3D view…', 'try3d-viewer' ),
				'connecting'         => __( 'Connecting…', 'try3d-viewer' ),
				'bytesOf'            =>
					/* translators: %1$s: amount downloaded, %2$s: total size. */
					__( '%1$s of %2$s', 'try3d-viewer' ),
				'downloaded'         =>
					/* translators: %s: amount downloaded. */
					__( '%s downloaded', 'try3d-viewer' ),
				'zeroSize'           => __( '0 MB', 'try3d-viewer' ),
				'megabytes'          =>
					/* translators: %s: size in megabytes. */
					__( '%s MB', 'try3d-viewer' ),
				'kilobytes'          =>
					/* translators: %s: size in kilobytes. */
					__( '%s KB', 'try3d-viewer' ),
				'qualityHd'          => __( 'High quality (HD)', 'try3d-viewer' ),
				'qualitySd'          => __( 'Standard quality (smaller)', 'try3d-viewer' ),
				'switchToHd'         => __( 'Switch to high quality (HD)', 'try3d-viewer' ),
				'switchToSd'         => __( 'Switch to standard quality', 'try3d-viewer' ),
				'hdActive'           => __( 'High quality is on', 'try3d-viewer' ),
				'errorTitle'         => __( 'The model did not open', 'try3d-viewer' ),
				'error'              => __( 'This model could not be displayed. Check the connection and try again.', 'try3d-viewer' ),
				'unsupportedTitle'   => __( 'This device is not supported', 'try3d-viewer' ),
				'unsupported'        => __( 'This browser or device cannot display a 3D model.', 'try3d-viewer' ),
				'arUnsupportedTitle' => __( 'Augmented reality is not available', 'try3d-viewer' ),
				'arUnsupported'      => __( 'This device cannot start augmented reality. It needs a browser with WebXR, ARCore, or Quick Look.', 'try3d-viewer' ),
				'retry'              => __( 'Try again', 'try3d-viewer' ),
				'dismiss'            => __( 'OK', 'try3d-viewer' ),
				'close'              => __( 'Close', 'try3d-viewer' ),
				'day'                => __( 'Day', 'try3d-viewer' ),
				'night'              => __( 'Night', 'try3d-viewer' ),
				'ar'                 => __( 'Augmented reality', 'try3d-viewer' ),
				'arWaiting'          => __( 'Augmented reality starts when the model has loaded', 'try3d-viewer' ),
				'fullscreen'         => __( 'Full screen', 'try3d-viewer' ),
				'exitFullscreen'     => __( 'Exit full screen', 'try3d-viewer' ),
				'autoRotate'         => __( 'Auto-rotate', 'try3d-viewer' ),
				'resetView'          => __( 'Reset the view', 'try3d-viewer' ),
				'snapshot'           => __( 'Save a snapshot', 'try3d-viewer' ),
				'qrTitle'            => __( 'View in augmented reality on a phone', 'try3d-viewer' ),
				'qrDesc'             => __( 'Scan this code with your phone camera to place the product in your room.', 'try3d-viewer' ),
				'qrScan'             => __( 'Scan with your phone', 'try3d-viewer' ),
			),
		);

		return apply_filters( 'try3d_script_config', $config );
	}
}
