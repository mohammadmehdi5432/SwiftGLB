<?php
/**
 * Plugin Name:       Try3D – 3D & AR Product Viewer
 * Plugin URI:        https://try3d.site/
 * Description:       Show WooCommerce products in 3D and augmented reality. The model downloads only after the shopper clicks.
 * Version:           1.1.11
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            mohammadmehdi5432
 * Author URI:        https://try3d.site/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       try3d-viewer
 * Domain Path:       /languages
 * Requires Plugins:  woocommerce
 * WC requires at least: 8.0
 * WC tested up to:   11.1
 *
 * @package Try3D
 */

defined( 'ABSPATH' ) || exit;

if ( defined( 'TRY3D_FILE' ) ) {
	return;
}

/**
 * Move a file with the WordPress filesystem when PHP can write directly.
 *
 * @param string $from Source path.
 * @param string $to   Destination path.
 * @return bool
 */
function try3d_move_file( $from, $to ) {
	if ( ! function_exists( 'get_filesystem_method' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}

	if ( function_exists( 'get_filesystem_method' ) && 'direct' === get_filesystem_method() ) {
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			WP_Filesystem();
		}
		if ( $wp_filesystem instanceof WP_Filesystem_Base && $wp_filesystem->move( $from, $to, true ) ) {
			return true;
		}
	}

	if ( ! @copy( $from, $to ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_copy, WordPress.PHP.NoSilencedErrors.Discouraged
		return false;
	}
	wp_delete_file( $from );
	return ! is_file( $from );
}

define( 'TRY3D_VERSION', '1.1.11' );
define( 'TRY3D_FILE', __FILE__ );
define( 'TRY3D_PATH', plugin_dir_path( __FILE__ ) );
define( 'TRY3D_URL', plugin_dir_url( __FILE__ ) );

// Contracts (Interfaces).
require_once TRY3D_PATH . 'includes/contracts/interface-try3d-crypto.php';
require_once TRY3D_PATH . 'includes/contracts/interface-try3d-storage.php';
require_once TRY3D_PATH . 'includes/contracts/interface-try3d-model-repository.php';

// Domain Services.
require_once TRY3D_PATH . 'includes/services/class-try3d-crypto.php';
require_once TRY3D_PATH . 'includes/services/class-try3d-storage-service.php';
require_once TRY3D_PATH . 'includes/services/class-try3d-model-repository.php';

// Subsystems (Admin, Frontend, REST).
require_once TRY3D_PATH . 'includes/admin/class-try3d-admin-assets.php';
require_once TRY3D_PATH . 'includes/admin/class-try3d-admin-panel.php';
require_once TRY3D_PATH . 'includes/admin/class-try3d-admin-ajax.php';
require_once TRY3D_PATH . 'includes/admin/class-try3d-admin-saver.php';

require_once TRY3D_PATH . 'includes/frontend/class-try3d-frontend-assets.php';
require_once TRY3D_PATH . 'includes/frontend/class-try3d-frontend-renderer.php';
require_once TRY3D_PATH . 'includes/frontend/class-try3d-frontend-hooks.php';

require_once TRY3D_PATH . 'includes/rest/class-try3d-access-validator.php';
require_once TRY3D_PATH . 'includes/rest/class-try3d-rest-controller.php';

// Service Container.
require_once TRY3D_PATH . 'includes/class-try3d-container.php';

// Facades & Orchestrators.
require_once TRY3D_PATH . 'includes/class-try3d-storage.php';
require_once TRY3D_PATH . 'includes/class-try3d-rest.php';
require_once TRY3D_PATH . 'includes/class-try3d-admin.php';
require_once TRY3D_PATH . 'includes/class-try3d-frontend.php';
require_once TRY3D_PATH . 'includes/class-try3d-plugin.php';

register_activation_hook( __FILE__, array( 'Try3D_Plugin', 'activate' ) );

add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', TRY3D_FILE, true );
		}
	}
);

add_action(
	'plugins_loaded',
	static function () {
		load_plugin_textdomain( 'try3d-viewer', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		Try3D_Plugin::migrate_legacy_brand();
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action(
				'admin_notices',
				static function () {
					if ( ! current_user_can( 'activate_plugins' ) ) {
						return;
					}
					echo '<div class="notice notice-error"><p>' . esc_html__( 'Try3D needs WooCommerce to be active.', 'try3d-viewer' ) . '</p></div>';
				}
			);
			return;
		}

		Try3D_Plugin::init();
	}
);
