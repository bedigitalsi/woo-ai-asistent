<?php
/**
 * Register all actions and filters for the plugin
 *
 * @package AI_Store_Assistant
 * @subpackage Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core plugin class that orchestrates all components.
 */
class ASA_Loader {

	/**
	 * The array of actions registered with WordPress.
	 *
	 * @var array
	 */
	protected $actions;

	/**
	 * The array of filters registered with WordPress.
	 *
	 * @var array
	 */
	protected $filters;

	/**
	 * Initialize the collections used to maintain the actions and filters.
	 */
	public function __construct() {
		$this->actions = array();
		$this->filters = array();
	}

	/**
	 * Add a new action to the collection to be registered with WordPress.
	 *
	 * @param string $hook          The name of the WordPress action that is being registered.
	 * @param object $component     A reference to the instance of the object on which the action is defined.
	 * @param string $callback      The name of the function definition on the $component.
	 * @param int    $priority      Optional. The priority at which the function should be fired. Default is 10.
	 * @param int    $accepted_args Optional. The number of arguments that should be passed to the $callback. Default is 1.
	 */
	public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Add a new filter to the collection to be registered with WordPress.
	 *
	 * @param string $hook          The name of the WordPress filter that is being registered.
	 * @param object $component     A reference to the instance of the object on which the filter is defined.
	 * @param string $callback      The name of the function definition on the $component.
	 * @param int    $priority      Optional. The priority at which the function should be fired. Default is 10.
	 * @param int    $accepted_args Optional. The number of arguments that should be passed to the $callback. Default is 1.
	 */
	public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * A utility function that is used to register the actions and hooks into a single
	 * collection.
	 *
	 * @param array  $hooks         The collection of hooks that is being registered (that is, actions or filters).
	 * @param string $hook          The name of the WordPress filter that is being registered.
	 * @param object $component     A reference to the instance of the object on which the filter is defined.
	 * @param string $callback      The name of the function definition on the $component.
	 * @param int    $priority      The priority at which the function should be fired.
	 * @param int    $accepted_args The number of arguments that should be passed to the $callback.
	 * @return array                                  The collection of actions and filters registered with WordPress.
	 */
	private function add( $hooks, $hook, $component, $callback, $priority, $accepted_args ) {
		$hooks[] = array(
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);
		return $hooks;
	}

	/**
	 * Register the filters and actions with WordPress.
	 */
	public function run() {
		// Wait for plugins to load before checking WooCommerce
		add_action( 'plugins_loaded', array( $this, 'init_plugin' ), 20 );
	}

	/**
	 * Initialize plugin after plugins are loaded.
	 */
	public function init_plugin() {
		// Check if WooCommerce is active - use multiple methods for compatibility
		if ( ! $this->is_woocommerce_active() ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
			return;
		}

		// Load dependencies
		require_once ASA_PLUGIN_DIR . 'includes/class-asa-settings.php';
		require_once ASA_PLUGIN_DIR . 'includes/class-asa-knowledge-base.php';
		require_once ASA_PLUGIN_DIR . 'includes/class-asa-product-recommender.php';
		require_once ASA_PLUGIN_DIR . 'includes/class-asa-chat-endpoint.php';
		require_once ASA_PLUGIN_DIR . 'includes/class-asa-order-handler.php';

		// Initialize components
		$settings = new ASA_Settings();
		$knowledge_base = new ASA_Knowledge_Base();
		$product_recommender = new ASA_Product_Recommender();
		$chat_endpoint = new ASA_Chat_Endpoint( $settings, $knowledge_base, $product_recommender );
		$order_handler = new ASA_Order_Handler();

		// Load admin
		if ( is_admin() ) {
			require_once ASA_PLUGIN_DIR . 'admin/class-asa-admin-page.php';
			$admin_page = new ASA_Admin_Page( $settings );
		}

		// Load frontend
		require_once ASA_PLUGIN_DIR . 'public/class-asa-frontend.php';
		$frontend = new ASA_Frontend( $settings );

		// Register hooks
		foreach ( $this->filters as $hook ) {
			add_filter( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
		}

		foreach ( $this->actions as $hook ) {
			add_action( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
		}
	}

	/**
	 * Check if WooCommerce is active.
	 *
	 * @return bool True if WooCommerce is active.
	 */
	private function is_woocommerce_active() {
		// Check if WooCommerce class exists
		if ( class_exists( 'WooCommerce' ) ) {
			return true;
		}

		// Check if WooCommerce constant is defined
		if ( defined( 'WC_VERSION' ) ) {
			return true;
		}

		// Check if WooCommerce functions are available
		if ( function_exists( 'WC' ) || function_exists( 'wc_get_products' ) ) {
			return true;
		}

		// Check if plugin is active (fallback method)
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Show notice if WooCommerce is not active.
	 */
	public function woocommerce_missing_notice() {
		?>
		<div class="notice notice-error">
			<p><?php esc_html_e( 'AI Store Assistant requires WooCommerce to be installed and active.', 'ai-store-assistant' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Activation hook callback.
	 */
	public static function activate() {
		// Flush rewrite rules for custom post type
		flush_rewrite_rules();
	}

	/**
	 * Deactivation hook callback.
	 */
	public static function deactivate() {
		// Flush rewrite rules
		flush_rewrite_rules();
	}
}

