<?php
/**
 * Admin product panel renderer.
 *
 * @package Try3D
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renders WooCommerce product edit tabs and variation fields.
 */
class Try3D_Admin_Panel {

	/**
	 * @var Try3D_Storage_Interface
	 */
	private $storage;

	/**
	 * @param Try3D_Storage_Interface $storage Storage service.
	 */
	public function __construct( Try3D_Storage_Interface $storage ) {
		$this->storage = $storage;
	}

	/**
	 * Add product data tab.
	 *
	 * @param array<string,array<string,mixed>> $tabs Tabs.
	 * @return array<string,array<string,mixed>>
	 */
	public function add_tab( $tabs ) {
		$tabs['try3d'] = array(
			'label'    => __( '3D & AR', 'try3d-viewer' ),
			'target'   => 'try3d_product_data',
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
		$token       = (string) get_post_meta( $post_id, Try3D_Model_Repository::META_TOKEN, true );
		$filename    = (string) get_post_meta( $post_id, Try3D_Model_Repository::META_FILENAME, true );
		$hd_token    = (string) get_post_meta( $post_id, Try3D_Model_Repository::META_HD_TOKEN, true );
		$hd_filename = (string) get_post_meta( $post_id, Try3D_Model_Repository::META_HD_FILENAME, true );
		$fixed       = 'yes' === get_post_meta( $post_id, Try3D_Model_Repository::META_AR_FIXED, true );
		$btn_text    = (string) get_post_meta( $post_id, Try3D_Model_Repository::META_BUTTON_TEXT, true );
		?>
		<div id="try3d_product_data" class="panel woocommerce_options_panel hidden">
			<div class="try3d-fields">
				<input type="hidden" name="try3d_present" value="1" />
				<p class="try3d-lead"><?php esc_html_e( 'The file is protected on upload and is not served directly from the uploads folder. On the product page, the 3D script downloads only after the shopper clicks the button.', 'try3d-viewer' ); ?></p>

				<div class="try3d-quality-grid">
					<div class="try3d-quality-card">
						<div class="try3d-quality-card__badge try3d-badge-sd"><?php esc_html_e( 'Standard model (SD)', 'try3d-viewer' ); ?></div>
						<p class="try3d-label"><?php esc_html_e( '3D model used for the first view', 'try3d-viewer' ); ?></p>
						<p class="try3d-note"><?php esc_html_e( 'This smaller file opens immediately for the shopper.', 'try3d-viewer' ); ?></p>
						<?php $this->render_file_field( 'try3d_token', 'try3d_filename', $token, $filename ); ?>
					</div>

					<div class="try3d-quality-card">
						<div class="try3d-quality-card__badge try3d-badge-hd"><?php esc_html_e( 'High-quality model (HD)', 'try3d-viewer' ); ?></div>
						<p class="try3d-label"><?php esc_html_e( 'High-resolution 3D model (optional)', 'try3d-viewer' ); ?></p>
						<p class="try3d-note"><?php esc_html_e( 'Opens in the background after the standard model, then replaces it on its own.', 'try3d-viewer' ); ?></p>
						<?php $this->render_file_field( 'try3d_hd_token', 'try3d_hd_filename', $hd_token, $hd_filename ); ?>
					</div>
				</div>

				<label class="try3d-check">
					<input type="checkbox" name="try3d_ar_fixed" value="yes" <?php checked( $fixed ); ?> />
					<span><?php esc_html_e( 'Use the real size in AR (the shopper cannot scale the model)', 'try3d-viewer' ); ?></span>
				</label>

				<p class="try3d-label"><?php esc_html_e( 'Custom button label (optional)', 'try3d-viewer' ); ?></p>
				<input type="text" name="try3d_button_text" class="regular-text" placeholder="<?php esc_attr_e( 'View the 3D model', 'try3d-viewer' ); ?>" value="<?php echo esc_attr( $btn_text ); ?>" />
				<p class="try3d-note"><?php esc_html_e( 'Leave this empty to use the default label.', 'try3d-viewer' ); ?></p>

				<p class="try3d-label"><?php esc_html_e( 'Shortcode for a custom product layout', 'try3d-viewer' ); ?></p>
				<input type="text" class="try3d-shortcode" readonly value="[try3d_viewer]" />
				<p class="try3d-note"><?php esc_html_e( 'For a variable product, set the file or color on each variation. An empty variation uses this product model.', 'try3d-viewer' ); ?></p>
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
		$token       = (string) get_post_meta( $id, Try3D_Model_Repository::META_TOKEN, true );
		$filename    = (string) get_post_meta( $id, Try3D_Model_Repository::META_FILENAME, true );
		$hd_token    = (string) get_post_meta( $id, Try3D_Model_Repository::META_HD_TOKEN, true );
		$hd_filename = (string) get_post_meta( $id, Try3D_Model_Repository::META_HD_FILENAME, true );
		$color       = (string) get_post_meta( $id, Try3D_Model_Repository::META_COLOR, true );
		$tinted      = (bool) preg_match( '/^#[0-9a-fA-F]{6}$/', $color );
		$index       = (int) $loop;
		?>
		<div class="form-row form-row-full try3d-variation">
			<input type="hidden" name="try3d_variation_present[<?php echo esc_attr( (string) $index ); ?>]" value="1" />
			<p class="try3d-label"><?php esc_html_e( 'Standard 3D model for this variation (small and fast)', 'try3d-viewer' ); ?></p>
			<?php
			$this->render_file_field(
				'try3d_variation_token[' . $index . ']',
				'try3d_variation_filename[' . $index . ']',
				$token,
				$filename
			);
			?>
			<p class="try3d-label" style="margin-top:12px;"><?php esc_html_e( 'High-quality (HD) model for this variation (optional)', 'try3d-viewer' ); ?></p>
			<?php
			$this->render_file_field(
				'try3d_variation_hd_token[' . $index . ']',
				'try3d_variation_hd_filename[' . $index . ']',
				$hd_token,
				$hd_filename
			);
			?>
			<div class="try3d-tint">
				<label class="try3d-check">
					<input type="checkbox" name="try3d_variation_tint[<?php echo esc_attr( (string) $index ); ?>]" value="yes" <?php checked( $tinted ); ?> />
					<span><?php esc_html_e( 'Tint the material when this variation is selected', 'try3d-viewer' ); ?></span>
				</label>
				<input type="color" name="try3d_variation_color[<?php echo esc_attr( (string) $index ); ?>]" value="<?php echo esc_attr( $tinted ? $color : '#ffffff' ); ?>" />
			</div>
			<p class="try3d-note"><?php esc_html_e( 'Upload a separate file when the variation needs its own model. Color shows best on a light model.', 'try3d-viewer' ); ?></p>
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
			$label = __( 'File not found. Upload it again.', 'try3d-viewer' );
			$token = '';
		}
		?>
		<div class="try3d-file">
			<input type="file" class="try3d-file-input" accept=".glb,.w3d,.bin,model/gltf-binary" hidden />
			<input type="hidden" class="try3d-token" name="<?php echo esc_attr( $token_name ); ?>" value="<?php echo esc_attr( $known ? $token : '' ); ?>" />
			<input type="hidden" class="try3d-filename" name="<?php echo esc_attr( $filename_name ); ?>" value="<?php echo esc_attr( $filename ); ?>" />
			<button type="button" class="button button-primary try3d-upload-btn"><?php esc_html_e( 'Upload a 3D file', 'try3d-viewer' ); ?></button>
			<button type="button" class="button try3d-preview-btn" <?php echo $known ? '' : 'hidden'; ?>><?php esc_html_e( 'Preview', 'try3d-viewer' ); ?></button>
			<button type="button" class="button-link-delete try3d-remove-btn" <?php echo $known ? '' : 'hidden'; ?>><?php esc_html_e( 'Remove', 'try3d-viewer' ); ?></button>
			<span class="try3d-file-name"><?php echo esc_html( '' !== $label ? $label : __( 'No file selected', 'try3d-viewer' ) ); ?></span>
			<span class="try3d-file-error" role="alert"></span>
		</div>
		<?php
	}
}
