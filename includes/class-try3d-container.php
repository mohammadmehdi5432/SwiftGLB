<?php
/**
 * Dependency injection container.
 *
 * @package Try3D
 */

defined( 'ABSPATH' ) || exit;

/**
 * Service locator and DI container for Try3D plugin components.
 */
class Try3D_Container {

	/**
	 * @var Try3D_Container|null
	 */
	private static $instance = null;

	/**
	 * @var array<string,object>
	 */
	private $services = array();

	/**
	 * Get singleton container instance.
	 *
	 * @return Try3D_Container
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor.
	 */
	private function __construct() {
		$this->register_services();
	}

	/**
	 * Register default core services.
	 *
	 * @return void
	 */
	private function register_services() {
		$crypto     = new Try3D_Crypto();
		$storage    = new Try3D_Storage_Service( $crypto );
		$repository = new Try3D_Model_Repository( $storage );
		$validator  = new Try3D_Access_Validator( $storage, $repository );
		$rest       = new Try3D_REST_Controller( $storage, $validator );

		$admin_assets = new Try3D_Admin_Assets( $storage );
		$admin_panel  = new Try3D_Admin_Panel( $storage );
		$admin_ajax   = new Try3D_Admin_Ajax( $storage );
		$admin_saver  = new Try3D_Admin_Saver( $repository, $storage );

		$frontend_assets   = new Try3D_Frontend_Assets();
		$frontend_renderer = new Try3D_Frontend_Renderer( $storage );
		$frontend_hooks    = new Try3D_Frontend_Hooks( $frontend_renderer, $frontend_assets );

		$this->services['crypto']            = $crypto;
		$this->services['storage']           = $storage;
		$this->services['repository']        = $repository;
		$this->services['access_validator']  = $validator;
		$this->services['rest_controller']   = $rest;
		$this->services['admin_assets']      = $admin_assets;
		$this->services['admin_panel']       = $admin_panel;
		$this->services['admin_ajax']        = $admin_ajax;
		$this->services['admin_saver']       = $admin_saver;
		$this->services['frontend_assets']   = $frontend_assets;
		$this->services['frontend_renderer'] = $frontend_renderer;
		$this->services['frontend_hooks']    = $frontend_hooks;
	}

	/**
	 * Get service by key.
	 *
	 * @param string $key Service identifier.
	 * @return object|null
	 */
	public function get( $key ) {
		return isset( $this->services[ $key ] ) ? $this->services[ $key ] : null;
	}

	/**
	 * Set or override a service.
	 *
	 * @param string $key     Service key.
	 * @param object $service Service instance.
	 * @return void
	 */
	public function set( $key, $service ) {
		$this->services[ $key ] = $service;
	}

	/**
	 * @return Try3D_Crypto_Interface
	 */
	public function get_crypto() {
		return $this->services['crypto'];
	}

	/**
	 * @return Try3D_Storage_Interface
	 */
	public function get_storage() {
		return $this->services['storage'];
	}

	/**
	 * @return Try3D_Model_Repository_Interface
	 */
	public function get_repository() {
		return $this->services['repository'];
	}

	/**
	 * @return Try3D_Access_Validator
	 */
	public function get_access_validator() {
		return $this->services['access_validator'];
	}

	/**
	 * @return Try3D_REST_Controller
	 */
	public function get_rest_controller() {
		return $this->services['rest_controller'];
	}

	/**
	 * @return Try3D_Admin_Assets
	 */
	public function get_admin_assets() {
		return $this->services['admin_assets'];
	}

	/**
	 * @return Try3D_Admin_Panel
	 */
	public function get_admin_panel() {
		return $this->services['admin_panel'];
	}

	/**
	 * @return Try3D_Admin_Ajax
	 */
	public function get_admin_ajax() {
		return $this->services['admin_ajax'];
	}

	/**
	 * @return Try3D_Admin_Saver
	 */
	public function get_admin_saver() {
		return $this->services['admin_saver'];
	}

	/**
	 * @return Try3D_Frontend_Assets
	 */
	public function get_frontend_assets() {
		return $this->services['frontend_assets'];
	}

	/**
	 * @return Try3D_Frontend_Renderer
	 */
	public function get_frontend_renderer() {
		return $this->services['frontend_renderer'];
	}

	/**
	 * @return Try3D_Frontend_Hooks
	 */
	public function get_frontend_hooks() {
		return $this->services['frontend_hooks'];
	}
}
