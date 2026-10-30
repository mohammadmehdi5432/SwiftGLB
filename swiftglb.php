<?php
/**
 * Plugin Name:       SwiftGLB – 3D & AR Product Viewer
 * Plugin URI:        https://github.com/mohammadmehdi5432/SwiftGLB
 * Description:       Show WooCommerce products in 3D and augmented reality. The model downloads only after the shopper clicks.
 * Version:           1.1.1
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            mohammadmehdi5432
 * Author URI:        https://github.com/mohammadmehdi5432
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       swiftglb-3d-ar-product-viewer
 * Domain Path:       /languages
 * Requires Plugins:  woocommerce
 * WC requires at least: 8.0
 * WC tested up to:   11.1
 *
 * @package SwiftGLB
 */

defined( 'ABSPATH' ) || exit;

if ( defined( 'SWIFTGLB_FILE' ) ) {
	return;
}

define( 'SWIFTGLB_VERSION', '1.1.1' );
define( 'SWIFTGLB_FILE', __FILE__ );
define( 'SWIFTGLB_PATH', plugin_dir_path( __FILE__ ) );
define( 'SWIFTGLB_URL', plugin_dir_url( __FILE__ ) );

// Contracts (Interfaces).
require_once SWIFTGLB_PATH . 'includes/contracts/interface-swiftglb-crypto.php';
require_once SWIFTGLB_PATH . 'includes/contracts/interface-swiftglb-storage.php';
require_once SWIFTGLB_PATH . 'includes/contracts/interface-swiftglb-model-repository.php';

// Domain Services.
require_once SWIFTGLB_PATH . 'includes/services/class-swiftglb-crypto.php';
require_once SWIFTGLB_PATH . 'includes/services/class-swiftglb-storage-service.php';
require_once SWIFTGLB_PATH . 'includes/services/class-swiftglb-model-repository.php';

// Subsystems (Admin, Frontend, REST).
require_once SWIFTGLB_PATH . 'includes/admin/class-swiftglb-admin-assets.php';
require_once SWIFTGLB_PATH . 'includes/admin/class-swiftglb-admin-panel.php';
require_once SWIFTGLB_PATH . 'includes/admin/class-swiftglb-admin-ajax.php';
require_once SWIFTGLB_PATH . 'includes/admin/class-swiftglb-admin-saver.php';

require_once SWIFTGLB_PATH . 'includes/frontend/class-swiftglb-frontend-assets.php';
require_once SWIFTGLB_PATH . 'includes/frontend/class-swiftglb-frontend-renderer.php';
require_once SWIFTGLB_PATH . 'includes/frontend/class-swiftglb-frontend-hooks.php';

require_once SWIFTGLB_PATH . 'includes/rest/class-swiftglb-access-validator.php';
require_once SWIFTGLB_PATH . 'includes/rest/class-swiftglb-rest-controller.php';

// Service Container.
require_once SWIFTGLB_PATH . 'includes/class-swiftglb-container.php';

// Facades & Orchestrators.
require_once SWIFTGLB_PATH . 'includes/class-swiftglb-storage.php';
require_once SWIFTGLB_PATH . 'includes/class-swiftglb-rest.php';
require_once SWIFTGLB_PATH . 'includes/class-swiftglb-admin.php';
require_once SWIFTGLB_PATH . 'includes/class-swiftglb-frontend.php';
require_once SWIFTGLB_PATH . 'includes/class-swiftglb-plugin.php';

register_activation_hook( __FILE__, array( 'SwiftGLB_Plugin', 'activate' ) );

add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', SWIFTGLB_FILE, true );
		}
	}
);

add_action(
	'plugins_loaded',
	static function () {
		load_plugin_textdomain( 'swiftglb-3d-ar-product-viewer', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		SwiftGLB_Plugin::migrate_legacy_brand();
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action(
				'admin_notices',
				static function () {
					if ( ! current_user_can( 'activate_plugins' ) ) {
						return;
					}
					echo '<div class="notice notice-error"><p>' . esc_html__( 'SwiftGLB needs WooCommerce to be active.', 'swiftglb-3d-ar-product-viewer' ) . '</p></div>';
				}
			);
			return;
		}

		SwiftGLB_Plugin::init();
	}
);
