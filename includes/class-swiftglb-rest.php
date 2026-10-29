<?php
/**
 * REST orchestrator & facade.
 *
 * @package SwiftGLB
 */

defined( 'ABSPATH' ) || exit;

/**
 * Connects REST & AJAX routes and delegates to specialized controller and validator components.
 */
class SwiftGLB_REST {

	/**
	 * Register REST and AJAX routes.
	 *
	 * @return void
	 */
	public static function init() {
		$container = SwiftGLB_Container::get_instance();

		add_action( 'rest_api_init', array( $container->get_rest_controller(), 'register_routes' ) );
		add_action( 'wp_ajax_swiftglb_model', array( $container->get_rest_controller(), 'serve_ajax_model' ) );
		add_action( 'wp_ajax_nopriv_swiftglb_model', array( $container->get_rest_controller(), 'serve_ajax_model' ) );
	}

	/**
	 * @return void
	 */
	public static function register() {
		SwiftGLB_Container::get_instance()->get_rest_controller()->register_routes();
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_Error|void
	 */
	public static function serve_model( WP_REST_Request $request ) {
		return SwiftGLB_Container::get_instance()->get_rest_controller()->serve_rest_model( $request );
	}

	/**
	 * @return void
	 */
	public static function ajax_model() {
		SwiftGLB_Container::get_instance()->get_rest_controller()->serve_ajax_model();
	}

	/**
	 * @param string $token        Token.
	 * @param int    $product_id   Product ID.
	 * @param int    $variation_id Variation ID.
	 * @return bool
	 */
	public static function can_read_token( $token, $product_id, $variation_id ) {
		return SwiftGLB_Container::get_instance()->get_access_validator()->can_read_token( $token, $product_id, $variation_id );
	}

	/**
	 * @param string $token Token.
	 * @return bool
	 */
	public static function token_is_public( $token ) {
		return SwiftGLB_Container::get_instance()->get_access_validator()->is_token_public( $token );
	}

	/**
	 * @param int $product_id Product ID.
	 * @return bool
	 */
	public static function product_is_viewable( $product_id ) {
		return SwiftGLB_Container::get_instance()->get_access_validator()->is_product_viewable( $product_id );
	}

	/**
	 * @param int $product_id Product ID.
	 * @return bool
	 */
	public static function allow_signed_fetch( $product_id ) {
		return SwiftGLB_Container::get_instance()->get_access_validator()->allow_signed_fetch( $product_id );
	}

	/**
	 * @return bool
	 */
	public static function has_rest_cookie() {
		return SwiftGLB_Container::get_instance()->get_access_validator()->has_rest_cookie();
	}
}
