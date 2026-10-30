<?php
/**
 * Admin data persistence service.
 *
 * @package SwiftGLB
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles saving and cleaning 3D model metadata for products and variations.
 */
class SwiftGLB_Admin_Saver {

	/**
	 * @var SwiftGLB_Model_Repository_Interface
	 */
	private $repository;

	/**
	 * @var SwiftGLB_Storage_Interface
	 */
	private $storage;

	/**
	 * Tokens captured before WordPress deletes the post meta.
	 *
	 * @var array<int,string[]>
	 */
	private $pending_tokens = array();

	/**
	 * @param SwiftGLB_Model_Repository_Interface $repository Repository service.
	 * @param SwiftGLB_Storage_Interface          $storage    Storage service.
	 */
	public function __construct( SwiftGLB_Model_Repository_Interface $repository, SwiftGLB_Storage_Interface $storage ) {
		$this->repository = $repository;
		$this->storage    = $storage;
	}

	/**
	 * Save product 3D metadata.
	 *
	 * @param int $post_id Product ID.
	 * @return void
	 */
	public function save_product( $post_id ) {
		if ( ! isset( $_POST['swiftglb_present'] ) ) {
			return;
		}
		if ( ! isset( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ), 'woocommerce_save_data' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$previous = (string) get_post_meta( $post_id, SwiftGLB_Model_Repository::META_TOKEN, true );
		$token    = isset( $_POST['swiftglb_token'] ) ? sanitize_text_field( wp_unslash( $_POST['swiftglb_token'] ) ) : '';
		$filename = isset( $_POST['swiftglb_filename'] ) ? sanitize_file_name( wp_unslash( $_POST['swiftglb_filename'] ) ) : '';
		if ( ! $this->storage->is_valid_token( $token ) || ! $this->storage->exists( $token ) ) {
			$token    = '';
			$filename = '';
		}

		$previous_hd = (string) get_post_meta( $post_id, SwiftGLB_Model_Repository::META_HD_TOKEN, true );
		$token_hd    = isset( $_POST['swiftglb_hd_token'] ) ? sanitize_text_field( wp_unslash( $_POST['swiftglb_hd_token'] ) ) : '';
		$filename_hd = isset( $_POST['swiftglb_hd_filename'] ) ? sanitize_file_name( wp_unslash( $_POST['swiftglb_hd_filename'] ) ) : '';
		if ( ! $this->storage->is_valid_token( $token_hd ) || ! $this->storage->exists( $token_hd ) ) {
			$token_hd    = '';
			$filename_hd = '';
		}

		$this->write_token( $post_id, $token, $filename, $previous, SwiftGLB_Model_Repository::META_TOKEN, SwiftGLB_Model_Repository::META_FILENAME );
		$this->write_token( $post_id, $token_hd, $filename_hd, $previous_hd, SwiftGLB_Model_Repository::META_HD_TOKEN, SwiftGLB_Model_Repository::META_HD_FILENAME );

		update_post_meta( $post_id, SwiftGLB_Model_Repository::META_ENABLED, 'yes' );
		update_post_meta( $post_id, SwiftGLB_Model_Repository::META_AR_FIXED, isset( $_POST['swiftglb_ar_fixed'] ) ? 'yes' : 'no' );
		update_post_meta( $post_id, SwiftGLB_Model_Repository::META_HD_AUTO, isset( $_POST['swiftglb_hd_auto'] ) ? 'yes' : 'no' );

		$custom_btn = isset( $_POST['swiftglb_button_text'] ) ? sanitize_text_field( wp_unslash( $_POST['swiftglb_button_text'] ) ) : '';
		if ( '' !== $custom_btn ) {
			update_post_meta( $post_id, SwiftGLB_Model_Repository::META_BUTTON_TEXT, $custom_btn );
		} else {
			delete_post_meta( $post_id, SwiftGLB_Model_Repository::META_BUTTON_TEXT );
		}
	}

	/**
	 * Save variation 3D metadata.
	 *
	 * @param int $variation_id Variation ID.
	 * @param int $loop         Posted variation index.
	 * @return void
	 */
	public function save_variation( $variation_id, $loop ) {
		if ( ! isset( $_POST['swiftglb_variation_present'][ $loop ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return;
		}
		if ( ! current_user_can( 'edit_post', $variation_id ) ) {
			return;
		}

		$previous = (string) get_post_meta( $variation_id, SwiftGLB_Model_Repository::META_TOKEN, true );
		$token    = isset( $_POST['swiftglb_variation_token'][ $loop ] ) ? sanitize_text_field( wp_unslash( $_POST['swiftglb_variation_token'][ $loop ] ) ) : '';
		$filename = isset( $_POST['swiftglb_variation_filename'][ $loop ] ) ? sanitize_file_name( wp_unslash( $_POST['swiftglb_variation_filename'][ $loop ] ) ) : '';
		if ( ! $this->storage->is_valid_token( $token ) || ! $this->storage->exists( $token ) ) {
			$token    = '';
			$filename = '';
		}

		$previous_hd = (string) get_post_meta( $variation_id, SwiftGLB_Model_Repository::META_HD_TOKEN, true );
		$token_hd    = isset( $_POST['swiftglb_variation_hd_token'][ $loop ] ) ? sanitize_text_field( wp_unslash( $_POST['swiftglb_variation_hd_token'][ $loop ] ) ) : '';
		$filename_hd = isset( $_POST['swiftglb_variation_hd_filename'][ $loop ] ) ? sanitize_file_name( wp_unslash( $_POST['swiftglb_variation_hd_filename'][ $loop ] ) ) : '';
		if ( ! $this->storage->is_valid_token( $token_hd ) || ! $this->storage->exists( $token_hd ) ) {
			$token_hd    = '';
			$filename_hd = '';
		}

		$this->write_token( $variation_id, $token, $filename, $previous, SwiftGLB_Model_Repository::META_TOKEN, SwiftGLB_Model_Repository::META_FILENAME );
		$this->write_token( $variation_id, $token_hd, $filename_hd, $previous_hd, SwiftGLB_Model_Repository::META_HD_TOKEN, SwiftGLB_Model_Repository::META_HD_FILENAME );

		$color = isset( $_POST['swiftglb_variation_color'][ $loop ] ) ? sanitize_hex_color( wp_unslash( $_POST['swiftglb_variation_color'][ $loop ] ) ) : '';
		$tint  = isset( $_POST['swiftglb_variation_tint'][ $loop ] );
		if ( $tint && is_string( $color ) && '' !== $color ) {
			update_post_meta( $variation_id, SwiftGLB_Model_Repository::META_COLOR, $color );
		} else {
			delete_post_meta( $variation_id, SwiftGLB_Model_Repository::META_COLOR );
		}
	}

	/**
	 * @param int    $post_id       Post ID.
	 * @param string $token         New token.
	 * @param string $filename      Label.
	 * @param string $previous      Previous token.
	 * @param string $meta_token    Token meta key.
	 * @param string $meta_filename Filename meta key.
	 * @return void
	 */
	private function write_token( $post_id, $token, $filename, $previous, $meta_token, $meta_filename ) {
		if ( '' === $token ) {
			delete_post_meta( $post_id, $meta_token );
			delete_post_meta( $post_id, $meta_filename );
		} else {
			update_post_meta( $post_id, $meta_token, $token );
			update_post_meta( $post_id, $meta_filename, $filename );
		}
		if ( '' !== $previous && $previous !== $token ) {
			$this->repository->delete_if_unused( $previous );
		}
	}

	/**
	 * Remember model files before WordPress removes post meta.
	 *
	 * @param int          $post_id Post ID.
	 * @param WP_Post|null $post    Post object.
	 * @return void
	 */
	public function remember_deleted_product( $post_id, $post = null ) {
		if ( ! $post instanceof WP_Post ) {
			$post = get_post( $post_id );
		}
		if ( ! $post || ! in_array( $post->post_type, array( 'product', 'product_variation' ), true ) ) {
			return;
		}

		$tokens = array();
		foreach ( array( SwiftGLB_Model_Repository::META_TOKEN, SwiftGLB_Model_Repository::META_HD_TOKEN ) as $meta_key ) {
			$token = (string) get_post_meta( $post_id, $meta_key, true );
			if ( $this->storage->is_valid_token( $token ) ) {
				$tokens[] = $token;
			}
		}
		$this->pending_tokens[ (int) $post_id ] = $tokens;
	}

	/**
	 * Remove model files after the product row and its meta are gone.
	 *
	 * @param int          $post_id Post ID.
	 * @param WP_Post|null $post    Post object.
	 * @return void
	 */
	public function forget_deleted_product( $post_id, $post = null ) {
		unset( $post );
		$post_id = (int) $post_id;
		if ( ! isset( $this->pending_tokens[ $post_id ] ) ) {
			return;
		}

		$tokens = $this->pending_tokens[ $post_id ];
		unset( $this->pending_tokens[ $post_id ] );
		foreach ( $tokens as $token ) {
			$this->repository->delete_if_unused( $token );
		}
	}
}
