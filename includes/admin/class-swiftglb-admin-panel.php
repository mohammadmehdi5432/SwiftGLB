<?php
/**
 * Admin product panel renderer.
 *
 * @package SwiftGLB
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renders WooCommerce product edit tabs and variation fields.
 */
class SwiftGLB_Admin_Panel {

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
	 * Add product data tab.
	 *
	 * @param array<string,array<string,mixed>> $tabs Tabs.
	 * @return array<string,array<string,mixed>>
	 */
	public function add_tab( $tabs ) {
		$tabs['swiftglb'] = array(
			'label'    => __( '3D & AR', 'swiftglb' ),
			'target'   => 'swiftglb_product_data',
			'class'    => array( 'show_if_simple', 'show_if_variable', 'show_if_subscription', 'show_if_variable_subscription' ),
			'priority' => 75,
		);
		return $tabs;
	}

	/**
	 * Render 3D settings panel on WooCommerce product edit screen.
	 *
	 * @return void
	 */
	public function render_panel() {
		global $post;

		$post_id     = $post instanceof WP_Post ? (int) $post->ID : 0;
		$token       = (string) get_post_meta( $post_id, SwiftGLB_Model_Repository::META_TOKEN, true );
		$filename    = (string) get_post_meta( $post_id, SwiftGLB_Model_Repository::META_FILENAME, true );
		$hd_token    = (string) get_post_meta( $post_id, SwiftGLB_Model_Repository::META_HD_TOKEN, true );
		$hd_filename = (string) get_post_meta( $post_id, SwiftGLB_Model_Repository::META_HD_FILENAME, true );
		$hd_auto     = 'yes' === get_post_meta( $post_id, SwiftGLB_Model_Repository::META_HD_AUTO, true );
		$enabled     = 'no' !== get_post_meta( $post_id, SwiftGLB_Model_Repository::META_ENABLED, true );
		$orbit       = 'yes' === get_post_meta( $post_id, SwiftGLB_Model_Repository::META_ORBIT, true );
		$fixed       = 'yes' === get_post_meta( $post_id, SwiftGLB_Model_Repository::META_AR_FIXED, true );
		$auto_rotate = 'yes' === get_post_meta( $post_id, SwiftGLB_Model_Repository::META_AUTO_ROTATE, true );
		$btn_text    = (string) get_post_meta( $post_id, SwiftGLB_Model_Repository::META_BUTTON_TEXT, true );
		?>
		<div id="swiftglb_product_data" class="panel woocommerce_options_panel hidden">
			<div class="swiftglb-fields">
				<input type="hidden" name="swiftglb_present" value="1" />
				<p class="swiftglb-lead"><?php esc_html_e( 'The file is protected on upload and is not served directly from the uploads folder. On the product page, the 3D script downloads only after the shopper clicks the button.', 'swiftglb' ); ?></p>

				<label class="swiftglb-check">
					<input type="checkbox" name="swiftglb_enabled" value="yes" <?php checked( $enabled ); ?> />
					<span><?php esc_html_e( 'Show the 3D viewer on the product page', 'swiftglb' ); ?></span>
				</label>

				<div class="swiftglb-quality-grid">
					<div class="swiftglb-quality-card">
						<div class="swiftglb-quality-card__badge swiftglb-badge-sd"><?php esc_html_e( 'Standard model (SD)', 'swiftglb' ); ?></div>
						<p class="swiftglb-label"><?php esc_html_e( '3D model used for the first view', 'swiftglb' ); ?></p>
						<p class="swiftglb-note"><?php esc_html_e( 'This smaller file opens immediately for the shopper.', 'swiftglb' ); ?></p>
						<?php $this->render_file_field( 'swiftglb_token', 'swiftglb_filename', $token, $filename ); ?>
					</div>

					<div class="swiftglb-quality-card">
						<div class="swiftglb-quality-card__badge swiftglb-badge-hd"><?php esc_html_e( 'High-quality model (HD)', 'swiftglb' ); ?></div>
						<p class="swiftglb-label"><?php esc_html_e( 'High-resolution 3D model (optional)', 'swiftglb' ); ?></p>
						<p class="swiftglb-note"><?php esc_html_e( 'When this file is uploaded, an HD switch appears on the product page.', 'swiftglb' ); ?></p>
						<?php $this->render_file_field( 'swiftglb_hd_token', 'swiftglb_hd_filename', $hd_token, $hd_filename ); ?>
					</div>
				</div>

				<div class="swiftglb-hd-options">
					<label class="swiftglb-check">
						<input type="checkbox" name="swiftglb_hd_auto" value="yes" <?php checked( $hd_auto ); ?> />
						<span><?php esc_html_e( 'Automatically upgrade to high quality (HD) in the background', 'swiftglb' ); ?></span>
					</label>
					<p class="swiftglb-note"><?php esc_html_e( 'When this is off, the high-quality model loads only after the shopper clicks HD. That is recommended, because it uses less data.', 'swiftglb' ); ?></p>
				</div>

				<label class="swiftglb-check">
					<input type="checkbox" name="swiftglb_auto_rotate" value="yes" <?php checked( $auto_rotate ); ?> />
					<span><?php esc_html_e( 'Rotate the model automatically when it opens', 'swiftglb' ); ?></span>
				</label>
				<p class="swiftglb-note"><?php esc_html_e( 'The model turns slowly so the shopper can see that it is 3D.', 'swiftglb' ); ?></p>

				<label class="swiftglb-check">
					<input type="checkbox" name="swiftglb_orbit_lock" value="yes" <?php checked( $orbit ); ?> />
					<span><?php esc_html_e( 'Hide the underside of the product (useful for shoes and furniture)', 'swiftglb' ); ?></span>
				</label>
				<p class="swiftglb-note"><?php esc_html_e( 'The camera cannot move below the product.', 'swiftglb' ); ?></p>

				<label class="swiftglb-check">
					<input type="checkbox" name="swiftglb_ar_fixed" value="yes" <?php checked( $fixed ); ?> />
					<span><?php esc_html_e( 'Use the real size in AR (the shopper cannot scale the model)', 'swiftglb' ); ?></span>
				</label>

				<p class="swiftglb-label"><?php esc_html_e( 'Custom button label (optional)', 'swiftglb' ); ?></p>
				<input type="text" name="swiftglb_button_text" class="regular-text" placeholder="<?php esc_attr_e( 'View the 3D model', 'swiftglb' ); ?>" value="<?php echo esc_attr( $btn_text ); ?>" />
				<p class="swiftglb-note"><?php esc_html_e( 'Leave this empty to use the default label.', 'swiftglb' ); ?></p>

				<p class="swiftglb-label"><?php esc_html_e( 'Shortcode for a custom product layout', 'swiftglb' ); ?></p>
				<input type="text" class="swiftglb-shortcode" readonly value="[swiftglb_viewer]" />
				<p class="swiftglb-note"><?php esc_html_e( 'For a variable product, set the file or color on each variation. An empty variation uses this product model.', 'swiftglb' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render variation fields.
	 *
	 * @param int     $loop           Variation index.
	 * @param array   $variation_data Variation meta.
	 * @param WP_Post $variation      Variation post.
	 * @return void
	 */
	public function render_variation_fields( $loop, $variation_data, $variation ) {
		unset( $variation_data );
		$id          = $variation instanceof WP_Post ? (int) $variation->ID : 0;
		$token       = (string) get_post_meta( $id, SwiftGLB_Model_Repository::META_TOKEN, true );
		$filename    = (string) get_post_meta( $id, SwiftGLB_Model_Repository::META_FILENAME, true );
		$hd_token    = (string) get_post_meta( $id, SwiftGLB_Model_Repository::META_HD_TOKEN, true );
		$hd_filename = (string) get_post_meta( $id, SwiftGLB_Model_Repository::META_HD_FILENAME, true );
		$color       = (string) get_post_meta( $id, SwiftGLB_Model_Repository::META_COLOR, true );
		$tinted      = (bool) preg_match( '/^#[0-9a-fA-F]{6}$/', $color );
		$index       = (int) $loop;
		?>
		<div class="form-row form-row-full swiftglb-variation">
			<input type="hidden" name="swiftglb_variation_present[<?php echo esc_attr( (string) $index ); ?>]" value="1" />
			<p class="swiftglb-label"><?php esc_html_e( 'Standard 3D model for this variation (small and fast)', 'swiftglb' ); ?></p>
			<?php
			$this->render_file_field(
				'swiftglb_variation_token[' . $index . ']',
				'swiftglb_variation_filename[' . $index . ']',
				$token,
				$filename
			);
			?>
			<p class="swiftglb-label" style="margin-top:12px;"><?php esc_html_e( 'High-quality (HD) model for this variation (optional)', 'swiftglb' ); ?></p>
			<?php
			$this->render_file_field(
				'swiftglb_variation_hd_token[' . $index . ']',
				'swiftglb_variation_hd_filename[' . $index . ']',
				$hd_token,
				$hd_filename
			);
			?>
			<div class="swiftglb-tint">
				<label class="swiftglb-check">
					<input type="checkbox" name="swiftglb_variation_tint[<?php echo esc_attr( (string) $index ); ?>]" value="yes" <?php checked( $tinted ); ?> />
					<span><?php esc_html_e( 'Tint the material when this variation is selected', 'swiftglb' ); ?></span>
				</label>
				<input type="color" name="swiftglb_variation_color[<?php echo esc_attr( (string) $index ); ?>]" value="<?php echo esc_attr( $tinted ? $color : '#ffffff' ); ?>" />
			</div>
			<p class="swiftglb-note"><?php esc_html_e( 'Upload a separate file when the variation needs its own model. Color shows best on a light model.', 'swiftglb' ); ?></p>
		</div>
		<?php
	}

	/**
	 * @param string $token_name    Input name.
	 * @param string $filename_name Input name.
	 * @param string $token         Stored token.
	 * @param string $filename      Stored label.
	 * @return void
	 */
	public function render_file_field( $token_name, $filename_name, $token, $filename ) {
		$known = $this->storage->is_valid_token( $token ) && $this->storage->exists( $token );
		$label = $filename;
		if ( $known ) {
			$info = $this->storage->inspect( $token );
			if ( ! is_wp_error( $info ) ) {
				$label = trim( $filename . ' (' . size_format( (int) $info['size'] ) . ')' );
			}
		} elseif ( '' !== $token ) {
			$label = __( 'File not found. Upload it again.', 'swiftglb' );
			$token = '';
		}
		?>
		<div class="swiftglb-file">
			<input type="file" class="swiftglb-file-input" accept=".glb,.w3d,.bin,model/gltf-binary" hidden />
			<input type="hidden" class="swiftglb-token" name="<?php echo esc_attr( $token_name ); ?>" value="<?php echo esc_attr( $known ? $token : '' ); ?>" />
			<input type="hidden" class="swiftglb-filename" name="<?php echo esc_attr( $filename_name ); ?>" value="<?php echo esc_attr( $filename ); ?>" />
			<button type="button" class="button button-primary swiftglb-upload-btn"><?php esc_html_e( 'Upload a 3D file', 'swiftglb' ); ?></button>
			<button type="button" class="button swiftglb-preview-btn" <?php echo $known ? '' : 'hidden'; ?>><?php esc_html_e( 'Preview', 'swiftglb' ); ?></button>
			<button type="button" class="button-link-delete swiftglb-remove-btn" <?php echo $known ? '' : 'hidden'; ?>><?php esc_html_e( 'Remove', 'swiftglb' ); ?></button>
			<span class="swiftglb-file-name"><?php echo esc_html( '' !== $label ? $label : __( 'No file selected', 'swiftglb' ) ); ?></span>
			<span class="swiftglb-file-error" role="alert"></span>
		</div>
		<?php
	}
}
