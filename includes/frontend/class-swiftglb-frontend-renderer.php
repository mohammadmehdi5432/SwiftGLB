<?php
/**
 * Frontend launcher & variation markup renderer.
 *
 * @package SwiftGLB
 */

defined( 'ABSPATH' ) || exit;

/**
 * Generates HTML markup for the 3D launcher and variation maps.
 */
class SwiftGLB_Frontend_Renderer {

	/**
	 * @var SwiftGLB_Storage_Interface
	 */
	private $storage;

	/**
	 * @param SwiftGLB_Storage_Interface $storage Storage service.
	 */
	public function __construct( SwiftGLB_Storage_Interface $storage ) {
		$this->storage = $storage;
	}

	/**
	 * Check if product or its variations have a 3D model.
	 *
	 * @param int $product_id Product ID.
	 * @return bool
	 */
	public function has_model( $product_id ) {
		if ( $product_id < 1 ) {
			return false;
		}

		$token = (string) get_post_meta( $product_id, SwiftGLB_Model_Repository::META_TOKEN, true );
		if ( $this->storage->is_valid_token( $token ) && $this->storage->exists( $token ) ) {
			return true;
		}

		$hd_token = (string) get_post_meta( $product_id, SwiftGLB_Model_Repository::META_HD_TOKEN, true );
		if ( $this->storage->is_valid_token( $hd_token ) && $this->storage->exists( $hd_token ) ) {
			return true;
		}

		$product = wc_get_product( $product_id );
		if ( $product && ( $product->is_type( 'variable' ) || $product->is_type( 'variable-subscription' ) ) ) {
			foreach ( $product->get_children() as $child_id ) {
				$child_token = (string) get_post_meta( $child_id, SwiftGLB_Model_Repository::META_TOKEN, true );
				if ( $this->storage->is_valid_token( $child_token ) && $this->storage->exists( $child_token ) ) {
					return true;
				}
				$child_hd = (string) get_post_meta( $child_id, SwiftGLB_Model_Repository::META_HD_TOKEN, true );
				if ( $this->storage->is_valid_token( $child_hd ) && $this->storage->exists( $child_hd ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Generate 3D launcher HTML.
	 *
	 * @param int    $product_id Product ID.
	 * @param string $mode       overlay|inline.
	 * @param bool   $wait       Hide until script positions it on photo.
	 * @return string
	 */
	public function render_launcher( $product_id, $mode, $wait = false ) {
		$product = wc_get_product( $product_id );
		if ( ! $product || ! $this->has_model( $product_id ) ) {
			return '';
		}

		if ( 'no' === get_post_meta( $product_id, SwiftGLB_Model_Repository::META_ENABLED, true ) && ! current_user_can( 'edit_post', $product_id ) ) {
			return '';
		}

		$token = (string) get_post_meta( $product_id, SwiftGLB_Model_Repository::META_TOKEN, true );
		if ( ! $this->storage->is_valid_token( $token ) || ! $this->storage->exists( $token ) ) {
			$token = '';
		}

		$hd_token = (string) get_post_meta( $product_id, SwiftGLB_Model_Repository::META_HD_TOKEN, true );
		if ( ! $this->storage->is_valid_token( $hd_token ) || ! $this->storage->exists( $hd_token ) ) {
			$hd_token = '';
		}

		if ( '' === $token && '' !== $hd_token ) {
			$token    = $hd_token;
			$hd_token = '';
		}

		$hd_auto     = 'yes' === get_post_meta( $product_id, SwiftGLB_Model_Repository::META_HD_AUTO, true ) ? '1' : '0';
		$image_id    = $product->get_image_id();
		$poster      = $image_id ? wp_get_attachment_image_url( $image_id, 'woocommerce_single' ) : '';
		$mode        = 'overlay' === $mode ? 'overlay' : 'inline';
		$class       = 'swiftglb-launcher swiftglb-launcher--' . $mode;
		if ( $wait ) {
			$class .= ' swiftglb-launcher--wait';
		}

		$btn_text = (string) get_post_meta( $product_id, SwiftGLB_Model_Repository::META_BUTTON_TEXT, true );
		if ( '' === $btn_text ) {
			$btn_text = __( 'View the 3D model', 'swiftglb' );
		}
		$auto_rotate = 'yes' === get_post_meta( $product_id, SwiftGLB_Model_Repository::META_AUTO_ROTATE, true ) ? '1' : '0';

		ob_start();
		?>
		<div class="<?php echo esc_attr( $class ); ?>"
			data-product="<?php echo esc_attr( (string) $product_id ); ?>"
			data-token="<?php echo esc_attr( $token ); ?>"
			data-hd-token="<?php echo esc_attr( $hd_token ); ?>"
			data-hd-auto="<?php echo esc_attr( $hd_auto ); ?>"
			data-orbit="<?php echo 'yes' === get_post_meta( $product_id, SwiftGLB_Model_Repository::META_ORBIT, true ) ? '1' : '0'; ?>"
			data-fixed="<?php echo 'yes' === get_post_meta( $product_id, SwiftGLB_Model_Repository::META_AR_FIXED, true ) ? '1' : '0'; ?>"
			data-autorotate="<?php echo esc_attr( $auto_rotate ); ?>"
			data-poster="<?php echo esc_url( $poster ? $poster : '' ); ?>"
			data-title="<?php echo esc_attr( $product->get_name() ); ?>"
			<?php echo '' === $token ? 'hidden' : ''; ?>>
			<button type="button" class="swiftglb-pill" aria-haspopup="dialog">
				<svg class="swiftglb-pill__icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
				<span class="swiftglb-pill__text"><?php echo esc_html( $btn_text ); ?></span>
			</button>
			<script type="application/json" class="swiftglb-map"><?php echo wp_json_encode( $this->build_variation_map( $product ) ); ?></script>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Build variation map for swatches and selection.
	 *
	 * @param WC_Product $product Product.
	 * @return array<int,array<string,mixed>>
	 */
	public function build_variation_map( $product ) {
		if ( ! $product->is_type( 'variable' ) && ! $product->is_type( 'variable-subscription' ) ) {
			return array();
		}

		$map = array();
		foreach ( $product->get_children() as $child_id ) {
			$variation = wc_get_product( $child_id );
			if ( ! $variation instanceof WC_Product_Variation ) {
				continue;
			}

			$token    = (string) get_post_meta( $variation->get_id(), SwiftGLB_Model_Repository::META_TOKEN, true );
			$hd_token = (string) get_post_meta( $variation->get_id(), SwiftGLB_Model_Repository::META_HD_TOKEN, true );
			$color    = (string) get_post_meta( $variation->get_id(), SwiftGLB_Model_Repository::META_COLOR, true );

			if ( ! $this->storage->is_valid_token( $token ) || ! $this->storage->exists( $token ) ) {
				$token = '';
			}
			if ( ! $this->storage->is_valid_token( $hd_token ) || ! $this->storage->exists( $hd_token ) ) {
				$hd_token = '';
			}
			if ( '' === $token && '' !== $hd_token ) {
				$token    = $hd_token;
				$hd_token = '';
			}
			if ( ! preg_match( '/^#[0-9a-fA-F]{6}$/', $color ) ) {
				$color = '';
			}
			if ( '' === $token && '' === $color ) {
				continue;
			}

			$labels = array();
			foreach ( $variation->get_variation_attributes() as $key => $value ) {
				$value = (string) $value;
				if ( '' === $value ) {
					continue;
				}
				$labels[] = $value;
				$taxonomy = str_replace( 'attribute_', '', (string) $key );
				if ( taxonomy_exists( $taxonomy ) ) {
					$term = get_term_by( 'slug', $value, $taxonomy );
					if ( $term instanceof WP_Term ) {
						$labels[] = $term->name;
					}
				}
			}

			$image_id = $variation->get_image_id();
			$map[]    = array(
				'id'      => $variation->get_id(),
				'token'   => $token,
				'hdToken' => $hd_token,
				'color'   => $color,
				'image'   => $image_id ? (string) wp_get_attachment_image_url( $image_id, 'full' ) : '',
				'labels'  => array_values( array_unique( $labels ) ),
			);
		}

		return $map;
	}
}
