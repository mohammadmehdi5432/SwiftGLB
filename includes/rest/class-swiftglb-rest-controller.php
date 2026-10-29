<?php
/**
 * REST & AJAX model delivery controller.
 *
 * @package SwiftGLB
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles REST requests and AJAX streaming of protected 3D models.
 */
class SwiftGLB_REST_Controller {

	/**
	 * @var SwiftGLB_Storage_Interface
	 */
	private $storage;

	/**
	 * @var SwiftGLB_Access_Validator
	 */
	private $validator;

	/**
	 * @param SwiftGLB_Storage_Interface $storage   Storage service.
	 * @param SwiftGLB_Access_Validator  $validator Validator service.
	 */
	public function __construct( SwiftGLB_Storage_Interface $storage, SwiftGLB_Access_Validator $validator ) {
		$this->storage   = $storage;
		$this->validator = $validator;
	}

	/**
	 * Register REST route.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			'swiftglb/v1',
			'/model/(?P<token>[a-f0-9]{32})',
			array(
				'methods'             => 'GET, POST',
				'callback'            => array( $this, 'serve_rest_model' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'token' => array(
						'validate_callback' => array( $this->storage, 'is_valid_token' ),
						'required'          => true,
					),
				),
			)
		);
	}

	/**
	 * Serve model via REST route.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_Error|void
	 */
	public function serve_rest_model( WP_REST_Request $request ) {
		$token = (string) $request->get_param( 'token' );
		if ( ! $this->storage->is_valid_token( $token ) || ! $this->storage->exists( $token ) ) {
			return new WP_Error( 'swiftglb_missing', __( 'Model not found.', 'swiftglb-3d-ar-product-viewer' ), array( 'status' => 404 ) );
		}

		$ids = $this->ids_from_request( $request );
		if ( ! $this->is_authorized( $request, $token, $ids['product_id'], $ids['variation_id'] ) ) {
			return new WP_Error( 'swiftglb_forbidden', __( 'You are not allowed to access this model.', 'swiftglb-3d-ar-product-viewer' ), array( 'status' => 403 ) );
		}

		$meta = $this->storage->inspect( $token );
		if ( is_wp_error( $meta ) ) {
			return new WP_Error( 'swiftglb_corrupt', __( 'The model could not be read.', 'swiftglb-3d-ar-product-viewer' ), array( 'status' => 500 ) );
		}

		$this->stream_model( $token, $meta, $ids['product_id'], $ids['variation_id'] );
		exit;
	}

	/**
	 * Serve model via admin-ajax fallback.
	 *
	 * @return void
	 */
	public function serve_ajax_model() {
		$token = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';
		if ( ! $this->storage->is_valid_token( $token ) || ! $this->storage->exists( $token ) ) {
			status_header( 404 );
			exit;
		}

		$product_id   = $this->request_int( array( 'p', 'product_id' ) );
		$variation_id = $this->request_int( array( 'v', 'variation_id' ) );
		$nonce        = $this->request_nonce();

		if ( ! $this->validator->can_read_token( $token, $product_id, $variation_id ) || ! $this->nonce_or_signature( $nonce, $product_id ) ) {
			status_header( 403 );
			exit;
		}

		$meta = $this->storage->inspect( $token );
		if ( is_wp_error( $meta ) ) {
			status_header( 404 );
			exit;
		}

		$this->stream_model( $token, $meta, $product_id, $variation_id );
		exit;
	}

	/**
	 * Send streaming headers and write bytes to output.
	 *
	 * @param string                      $token        Token.
	 * @param array{name:string,size:int} $meta         Metadata.
	 * @param int                         $product_id   Product ID.
	 * @param int                         $variation_id Variation ID.
	 * @return void
	 */
	private function stream_model( $token, $meta, $product_id = 0, $variation_id = 0 ) {
		nocache_headers();
		header( 'Content-Type: model/gltf-binary' );
		header( 'Content-Disposition: inline; filename="model.glb"' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Accept-Ranges: none' );
		header( 'Content-Length: ' . (int) $meta['size'] );
		if ( $product_id > 0 ) {
			$ar_url = str_replace( array( "\r", "\n" ), '', $this->validator->signed_model_url( $token, $product_id, $variation_id ) );
			header( 'X-SwiftGLB-AR-URL: ' . $ar_url );
			header( 'Access-Control-Expose-Headers: X-SwiftGLB-AR-URL, Content-Length, Content-Type' );
		}

		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}

		$this->storage->stream( $token );
	}

	/**
	 * Check REST authorization.
	 *
	 * @param WP_REST_Request $request      Request.
	 * @param string          $token        Token.
	 * @param int             $product_id   Product ID.
	 * @param int             $variation_id Variation ID.
	 * @return bool
	 */
	private function is_authorized( WP_REST_Request $request, $token, $product_id, $variation_id ) {
		if ( ! $this->validator->can_read_token( $token, $product_id, $variation_id ) ) {
			return false;
		}

		$nonce = (string) $request->get_header( 'x_wp_nonce' );
		if ( '' === $nonce ) {
			$nonce = (string) $request->get_header( 'x_swiftglb_nonce' );
		}
		if ( '' === $nonce ) {
			$nonce = sanitize_text_field( (string) $request->get_param( 'nonce' ) );
		}

		return $this->nonce_or_signature( $nonce, $product_id );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return array{product_id:int,variation_id:int}
	 */
	private function ids_from_request( WP_REST_Request $request ) {
		$product_id = absint( $request->get_param( 'p' ) );
		if ( $product_id < 1 ) {
			$product_id = absint( $request->get_param( 'product_id' ) );
		}
		$variation_id = absint( $request->get_param( 'v' ) );
		if ( $variation_id < 1 ) {
			$variation_id = absint( $request->get_param( 'variation_id' ) );
		}
		return array(
			'product_id'   => $product_id,
			'variation_id' => $variation_id,
		);
	}

	/**
	 * @param string[] $keys Query keys, first match wins.
	 * @return int
	 */
	private function request_int( $keys ) {
		foreach ( $keys as $key ) {
			if ( isset( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return absint( wp_unslash( $_GET[ $key ] ) );
			}
			if ( isset( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				return absint( wp_unslash( $_POST[ $key ] ) );
			}
		}
		return 0;
	}

	/**
	 * @return string
	 */
	private function request_nonce() {
		if ( isset( $_GET['nonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return sanitize_text_field( wp_unslash( $_GET['nonce'] ) );
		}
		if ( isset( $_SERVER['HTTP_X_3DSAZ_NONCE'] ) ) {
			return sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_3DSAZ_NONCE'] ) );
		}
		if ( isset( $_SERVER['HTTP_X_WP_NONCE'] ) ) {
			return sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_WP_NONCE'] ) );
		}
		return '';
	}

	/**
	 * @param string $nonce      Nonce value.
	 * @param int    $product_id Product ID.
	 * @return bool
	 */
	private function nonce_or_signature( $nonce, $product_id ) {
		if ( $product_id > 0 && $this->validator->allow_signed_fetch( $product_id ) ) {
			return true;
		}
		if ( '' === $nonce ) {
			return false;
		}
		return false !== wp_verify_nonce( $nonce, 'swiftglb_view' ) || false !== wp_verify_nonce( $nonce, 'wp_rest' );
	}
}
