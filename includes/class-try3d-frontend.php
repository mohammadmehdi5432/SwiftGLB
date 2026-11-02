<?php
/**
 * Frontend orchestrator & facade.
 *
 * @package Try3D
 */

defined( 'ABSPATH' ) || exit;

/**
 * Connects frontend hooks and delegates tasks to specialized frontend components.
 */
class Try3D_Frontend {

	/**
	 * Register frontend hooks.
	 *
	 * @return void
	 */
	public static function init() {
		Try3D_Container::get_instance()->get_frontend_hooks()->register();
	}

	/**
	 * Enqueue frontend assets.
	 *
	 * @return void
	 */
	public static function enqueue() {
		Try3D_Container::get_instance()->get_frontend_assets()->enqueue();
	}

	/**
	 * @param int    $product_id Product ID.
	 * @param string $mode       overlay|inline.
	 * @param bool   $wait       Hide until placed.
	 * @return string
	 */
	public static function launcher_html( $product_id, $mode, $wait = false ) {
		return Try3D_Container::get_instance()->get_frontend_renderer()->render_launcher( $product_id, $mode, $wait );
	}

	/**
	 * @param int $product_id Product ID.
	 * @return bool
	 */
	public static function product_has_model( $product_id ) {
		return Try3D_Container::get_instance()->get_frontend_renderer()->has_model( $product_id );
	}

	/**
	 * @param array<string,mixed>  $data      Variation payload.
	 * @param WC_Product           $product   Product.
	 * @param WC_Product_Variation $variation Variation.
	 * @return array<string,mixed>
	 */
	public static function variation_payload( $data, $product, $variation ) {
		return Try3D_Container::get_instance()->get_frontend_hooks()->on_variation_payload( $data, $product, $variation );
	}

	/**
	 * @param array<string,mixed>|string $atts Shortcode attributes.
	 * @return string
	 */
	public static function shortcode( $atts ) {
		return Try3D_Container::get_instance()->get_frontend_hooks()->shortcode( $atts );
	}
}
