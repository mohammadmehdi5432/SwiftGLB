<?php
/**
 * Model access validation service.
 *
 * @package SwiftGLB
 */

defined( 'ABSPATH' ) || exit;

/**
 * Enforces permissions and viewability rules for 3D model access.
 */
class SwiftGLB_Access_Validator {

	const SIGNATURE_TTL = 1800;

	/**
	 * @var SwiftGLB_Storage_Interface
	 */
	private $storage;

	/**
	 * @var SwiftGLB_Model_Repository_Interface
	 */
	private $repository;

	/**
	 * @param SwiftGLB_Storage_Interface          $storage    Storage service.
	 * @param SwiftGLB_Model_Repository_Interface $repository Repository service.
	 */
	public function __construct( SwiftGLB_Storage_Interface $storage, SwiftGLB_Model_Repository_Interface $repository ) {
		$this->storage    = $storage;
		$this->repository = $repository;
	}

	/**
	 * Verify if requester can read model token.
	 *
	 * @param string $token        Model token.
	 * @param int    $product_id   Product ID.
	 * @param int    $variation_id Variation ID.
	 * @return bool
	 */
	public function can_read_token( $token, $product_id, $variation_id ) {
		if ( ! $this->storage->is_valid_token( $token ) || $product_id < 1 ) {
			return false;
		}

		if ( 'no' === get_post_meta( $product_id, SwiftGLB_Model_Repository::META_ENABLED, true ) && ! current_user_can( 'edit_post', $product_id ) ) {
			return false;
		}

		$owns = (string) get_post_meta( $product_id, SwiftGLB_Model_Repository::META_TOKEN, true ) === $token
			|| (string) get_post_meta( $product_id, SwiftGLB_Model_Repository::META_HD_TOKEN, true ) === $token;

		if ( ! $owns && $variation_id > 0 && (int) wp_get_post_parent_id( $variation_id ) === $product_id ) {
			$owns = (string) get_post_meta( $variation_id, SwiftGLB_Model_Repository::META_TOKEN, true ) === $token
				|| (string) get_post_meta( $variation_id, SwiftGLB_Model_Repository::META_HD_TOKEN, true ) === $token;
		}

		if ( ! $owns ) {
			return false;
		}

		return $this->is_product_viewable( $product_id );
	}

	/**
	 * Check if token belongs to any public viewable product.
	 *
	 * @param string $token Token.
	 * @return bool
	 */
	public function is_token_public( $token ) {
		foreach ( $this->repository->get_post_ids_for_token( $token ) as $post_id ) {
			$parent = (int) wp_get_post_parent_id( $post_id );
			$target = $parent > 0 ? $parent : $post_id;
			if ( 'no' === get_post_meta( $target, SwiftGLB_Model_Repository::META_ENABLED, true ) && ! current_user_can( 'edit_post', $target ) ) {
				continue;
			}
			if ( $this->is_product_viewable( $target ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Verify if product is publicly visible.
	 *
	 * @param int $product_id Product ID.
	 * @return bool
	 */
	public function is_product_viewable( $product_id ) {
		$post = get_post( $product_id );
		if ( ! $post || 'publish' !== $post->post_status ) {
			return current_user_can( 'edit_post', $product_id );
		}

		if ( ! class_exists( 'WC_Product' ) ) {
			return true;
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return false;
		}

		if ( 'hidden' === $product->get_catalog_visibility() && ! current_user_can( 'edit_post', $product_id ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Verify HMAC signature on URL parameters.
	 *
	 * @param int $product_id Product ID.
	 * @return bool
	 */
	public function allow_signed_fetch( $product_id ) {
		$time = isset( $_GET['t'] ) ? sanitize_text_field( wp_unslash( $_GET['t'] ) ) : '';
		$sig  = isset( $_GET['sig'] ) ? sanitize_text_field( wp_unslash( $_GET['sig'] ) ) : '';
		if ( '' === $time || '' === $sig || ! ctype_digit( $time ) ) {
			return false;
		}

		$stamp = (int) $time;
		if ( abs( time() - $stamp ) > self::SIGNATURE_TTL ) {
			return false;
		}

		$expected = hash_hmac( 'sha256', (string) $product_id . '|' . $time, wp_salt( 'nonce' ) );
		return hash_equals( $expected, $sig );
	}

	/**
	 * Cookieless URL Scene Viewer can fetch for a short time.
	 *
	 * @param string $token        Model token.
	 * @param int    $product_id   Product ID.
	 * @param int    $variation_id Variation ID.
	 * @return string
	 */
	public function signed_model_url( $token, $product_id, $variation_id ) {
		$product_id = (int) $product_id;
		$time       = time();
		$sig        = hash_hmac( 'sha256', (string) $product_id . '|' . $time, wp_salt( 'nonce' ) );
		return add_query_arg(
			array(
				'p'   => $product_id,
				'v'   => (int) $variation_id,
				't'   => $time,
				'sig' => $sig,
			),
			rest_url( 'swiftglb/v1/model/' . $token )
		);
	}

	/**
	 * Logged-in requests already carry a WordPress session.
	 *
	 * @return bool
	 */
	public function has_rest_cookie() {
		return is_user_logged_in();
	}
}
