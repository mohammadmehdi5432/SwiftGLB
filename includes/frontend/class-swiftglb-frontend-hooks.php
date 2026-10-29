<?php
/**
 * Frontend hook connectors.
 *
 * @package SwiftGLB
 */

defined( 'ABSPATH' ) || exit;

/**
 * Connects frontend renderer and assets to WooCommerce and WordPress filters/actions.
 */
class SwiftGLB_Frontend_Hooks {

	/**
	 * @var SwiftGLB_Frontend_Renderer
	 */
	private $renderer;

	/**
	 * @var SwiftGLB_Frontend_Assets
	 */
	private $assets;

	/**
	 * @param SwiftGLB_Frontend_Renderer $renderer Renderer service.
	 * @param SwiftGLB_Frontend_Assets   $assets   Assets service.
	 */
	public function __construct( SwiftGLB_Frontend_Renderer $renderer, SwiftGLB_Frontend_Assets $assets ) {
		$this->renderer = $renderer;
		$this->assets   = $assets;
	}

	/**
	 * Register frontend hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'wp_enqueue_scripts', array( $this, 'on_enqueue_scripts' ) );
		add_action( 'woocommerce_before_single_product_summary', array( $this, 'on_photo_overlay' ), 25 );
		add_action( 'woocommerce_single_product_summary', array( $this, 'on_summary' ), 25 );
		add_filter( 'woocommerce_available_variation', array( $this, 'on_variation_payload' ), 10, 3 );
		add_shortcode( 'swiftglb_viewer', array( $this, 'shortcode' ) );
		add_shortcode( 'woo_3d_viewer', array( $this, 'shortcode' ) );
	}

	/**
	 * Enqueue scripts when viewing single product or containing shortcode.
	 *
	 * @return void
	 */
	public function on_enqueue_scripts() {
		if ( ! is_product() ) {
			global $post;
			if ( ! $post || ( ! has_shortcode( $post->post_content, 'swiftglb_viewer' ) && ! has_shortcode( $post->post_content, 'woo_3d_viewer' ) ) ) {
				return;
			}
		}

		$product_id = (int) get_the_ID();
		if ( $product_id > 0 && ! $this->renderer->has_model( $product_id ) && ! current_user_can( 'edit_post', $product_id ) ) {
			return;
		}

		$this->assets->enqueue();
	}

	/**
	 * Photo overlay button.
	 *
	 * @return void
	 */
	public function on_photo_overlay() {
		$product_id = (int) get_the_ID();
		if ( $product_id < 1 ) {
			return;
		}
		echo $this->renderer->render_launcher( $product_id, 'overlay', true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Summary fallback button.
	 *
	 * @return void
	 */
	public function on_summary() {
		$product_id = (int) get_the_ID();
		if ( $product_id < 1 ) {
			return;
		}
		echo $this->renderer->render_launcher( $product_id, 'inline', false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Include 3D data in WooCommerce variation JSON payload.
	 *
	 * @param array<string,mixed>  $data      Variation payload.
	 * @param WC_Product           $product   Product.
	 * @param WC_Product_Variation $variation Variation.
	 * @return array<string,mixed>
	 */
	public function on_variation_payload( $data, $product, $variation ) {
		$data['swiftglb_token']    = '';
		$data['swiftglb_hd_token'] = '';
		$data['swiftglb_color']    = '';

		if ( 'no' === get_post_meta( $product->get_id(), SwiftGLB_Model_Repository::META_ENABLED, true ) ) {
			return $data;
		}

		$token    = (string) get_post_meta( $variation->get_id(), SwiftGLB_Model_Repository::META_TOKEN, true );
		$hd_token = (string) get_post_meta( $variation->get_id(), SwiftGLB_Model_Repository::META_HD_TOKEN, true );
		$color    = (string) get_post_meta( $variation->get_id(), SwiftGLB_Model_Repository::META_COLOR, true );

		$storage = SwiftGLB_Container::get_instance()->get_storage();
		if ( $storage->is_valid_token( $token ) && $storage->exists( $token ) ) {
			$data['swiftglb_token'] = $token;
		}
		if ( $storage->is_valid_token( $hd_token ) && $storage->exists( $hd_token ) ) {
			$data['swiftglb_hd_token'] = $hd_token;
		}
		if ( '' === $data['swiftglb_token'] && '' !== $data['swiftglb_hd_token'] ) {
			$data['swiftglb_token']    = $data['swiftglb_hd_token'];
			$data['swiftglb_hd_token'] = '';
		}
		if ( preg_match( '/^#[0-9a-fA-F]{6}$/', $color ) ) {
			$data['swiftglb_color'] = $color;
		}

		return $data;
	}

	/**
	 * [woo_3d_viewer] shortcode handler.
	 *
	 * @param array<string,mixed>|string $atts Shortcode attributes.
	 * @return string
	 */
	public function shortcode( $atts ) {
		$args = shortcode_atts(
			array(
				'id'   => 0,
				'mode' => 'inline',
			),
			$atts,
			'swiftglb_viewer'
		);

		$product_id = absint( $args['id'] );
		if ( $product_id < 1 ) {
			$product_id = (int) get_the_ID();
		}
		if ( $product_id < 1 ) {
			return '';
		}

		$this->assets->enqueue();
		return $this->renderer->render_launcher( $product_id, 'overlay' === $args['mode'] ? 'overlay' : 'inline', false );
	}
}
