<?php
/**
 * Plugin Name: AI Store Assistant
 * Plugin URI: https://github.com/bedigitalsi/woo-ai-asistent
 * Description: A WordPress plugin that integrates with OpenAI API to provide a product-aware chatbot on WooCommerce stores.
 * Version: 1.0.10
 * Author: beDigital SI
 * Author URI: https://github.com/bedigitalsi
 * Text Domain: ai-store-assistant
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.1
 * WC requires at least: 8.0
 * WC tested up to: 10.2
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package AI_Store_Assistant
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 */
define( 'ASA_VERSION', '1.0.10' );
define( 'ASA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ASA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ASA_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 */
function activate_ai_store_assistant() {
	require_once ASA_PLUGIN_DIR . 'includes/class-asa-loader.php';
	ASA_Loader::activate();
}
register_activation_hook( __FILE__, 'activate_ai_store_assistant' );

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_ai_store_assistant() {
	require_once ASA_PLUGIN_DIR . 'includes/class-asa-loader.php';
	ASA_Loader::deactivate();
}
register_deactivation_hook( __FILE__, 'deactivate_ai_store_assistant' );

/**
 * Initialize plugin updater for GitHub releases.
 */
function init_ai_store_assistant_updater() {
	if ( ! class_exists( 'ASA_Updater' ) ) {
		require_once ASA_PLUGIN_DIR . 'includes/class-asa-updater.php';
	}
	new ASA_Updater( __FILE__, ASA_VERSION );
}
add_action( 'admin_init', 'init_ai_store_assistant_updater' );

/**
 * Begins execution of the plugin.
 */
function run_ai_store_assistant() {
	require_once ASA_PLUGIN_DIR . 'includes/class-asa-loader.php';
	$loader = new ASA_Loader();
	$loader->run();
}
run_ai_store_assistant();

