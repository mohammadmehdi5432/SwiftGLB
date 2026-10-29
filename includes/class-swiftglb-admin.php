<?php
/**
 * Admin orchestrator & facade.
 *
 * @package SwiftGLB
 */

defined( 'ABSPATH' ) || exit;

/**
 * Connects admin hooks and delegates tasks to specialized admin components.
 */
class SwiftGLB_Admin {

	/**
	 * Register admin hooks.
	 *
	 * @return void
	 */
	public static function init() {
		$container = SwiftGLB_Container::get_instance();

		add_action( 'admin_enqueue_scripts', array( $container->get_admin_assets(), 'enqueue' ) );
		add_filter( 'woocommerce_product_data_tabs', array( $container->get_admin_panel(), 'add_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( $container->get_admin_panel(), 'render_panel' ) );
		add_action( 'woocommerce_product_after_variable_attributes', array( $container->get_admin_panel(), 'render_variation_fields' ), 10, 3 );

		add_action( 'woocommerce_process_product_meta', array( $container->get_admin_saver(), 'save_product' ) );
		add_action( 'woocommerce_save_product_variation', array( $container->get_admin_saver(), 'save_variation' ), 10, 2 );
		add_action( 'before_delete_post', array( $container->get_admin_saver(), 'remember_deleted_product' ), 10, 2 );
		add_action( 'deleted_post', array( $container->get_admin_saver(), 'forget_deleted_product' ), 10, 2 );
		add_action( 'admin_notices', array( __CLASS__, 'storage_notice' ) );

		add_action( 'wp_ajax_swiftglb_upload', array( $container->get_admin_ajax(), 'handle_upload' ) );
		add_action( 'wp_ajax_swiftglb_preview', array( $container->get_admin_ajax(), 'handle_preview' ) );
	}

	/**
	 * @param array<string,array<string,mixed>> $tabs Tabs.
	 * @return array<string,array<string,mixed>>
	 */
	public static function tab( $tabs ) {
		return SwiftGLB_Container::get_instance()->get_admin_panel()->add_tab( $tabs );
	}

	/**
	 * @return void
	 */
	public static function panel() {
		SwiftGLB_Container::get_instance()->get_admin_panel()->render_panel();
	}

	/**
	 * @param int     $loop           Variation index.
	 * @param array   $variation_data Variation meta.
	 * @param WP_Post $variation      Variation post.
	 * @return void
	 */
	public static function variation_fields( $loop, $variation_data, $variation ) {
		SwiftGLB_Container::get_instance()->get_admin_panel()->render_variation_fields( $loop, $variation_data, $variation );
	}

	/**
	 * @param string $token_name    Input name.
	 * @param string $filename_name Input name.
	 * @param string $token         Stored token.
	 * @param string $filename      Stored label.
	 * @return void
	 */
	public static function file_field( $token_name, $filename_name, $token, $filename ) {
		SwiftGLB_Container::get_instance()->get_admin_panel()->render_file_field( $token_name, $filename_name, $token, $filename );
	}

	/**
	 * @param int $post_id Product ID.
	 * @return void
	 */
	public static function save_product( $post_id ) {
		SwiftGLB_Container::get_instance()->get_admin_saver()->save_product( $post_id );
	}

	/**
	 * @param int $variation_id Variation ID.
	 * @param int $loop         Posted index.
	 * @return void
	 */
	public static function save_variation( $variation_id, $loop ) {
		SwiftGLB_Container::get_instance()->get_admin_saver()->save_variation( $variation_id, $loop );
	}

	/**
	 * @param string $hook Current admin page.
	 * @return void
	 */
	public static function assets( $hook ) {
		SwiftGLB_Container::get_instance()->get_admin_assets()->enqueue( $hook );
	}

	/**
	 * @return void
	 */
	public static function ajax_upload() {
		SwiftGLB_Container::get_instance()->get_admin_ajax()->handle_upload();
	}

	/**
	 * @return void
	 */
	public static function preview_model() {
		SwiftGLB_Container::get_instance()->get_admin_ajax()->handle_preview();
	}

	/**
	 * @param int          $post_id Post ID.
	 * @param WP_Post|null $post    Post object.
	 * @return void
	 */
	public static function forget_deleted_product( $post_id, $post = null ) {
		SwiftGLB_Container::get_instance()->get_admin_saver()->forget_deleted_product( $post_id, $post );
	}

	/**
	 * Ask for a server rule when model files remain inside the public web root.
	 *
	 * @return void
	 */
	public static function storage_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$software = isset( $_SERVER['SERVER_SOFTWARE'] ) ? strtolower( (string) $_SERVER['SERVER_SOFTWARE'] ) : '';
		if ( false === strpos( $software, 'nginx' ) ) {
			return;
		}

		$storage = SwiftGLB_Container::get_instance()->get_storage();
		if ( ! $storage->is_inside_web_root() ) {
			return;
		}

		$dir  = wp_normalize_path( $storage->get_dir() );
		$root = wp_normalize_path( untrailingslashit( ABSPATH ) );
		if ( '' === $dir || 0 !== strpos( $dir, $root ) ) {
			return;
		}
		$rel = '/' . ltrim( substr( $dir, strlen( $root ) ), '/' );
		if ( '/' === $rel ) {
			return;
		}
		echo '<div class="notice notice-warning"><p>';
		echo esc_html__( '3D models are still inside the web root, and Nginx ignores .htaccess files. Deny this path in the Nginx configuration so model files cannot be downloaded directly.', 'swiftglb-3d-ar-product-viewer' );
		echo '</p><p><code>';
		echo esc_html( 'location ^~ ' . trailingslashit( $rel ) . ' { deny all; }' );
		echo '</code></p></div>';
	}
}
