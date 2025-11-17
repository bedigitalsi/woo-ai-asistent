<?php
/**
 * Admin page class
 *
 * @package AI_Store_Assistant
 * @subpackage Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles admin page display and functionality.
 */
class ASA_Admin_Page {

	/**
	 * Settings instance.
	 *
	 * @var ASA_Settings
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param ASA_Settings $settings Settings instance.
	 */
	public function __construct( $settings ) {
		$this->settings = $settings;
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Add admin menu.
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'AI Store Assistant', 'ai-store-assistant' ),
			__( 'AI Store Assistant', 'ai-store-assistant' ),
			'manage_options',
			'ai-store-assistant',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_scripts( $hook ) {
		if ( 'woocommerce_page_ai-store-assistant' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'asa-admin-css',
			ASA_PLUGIN_URL . 'admin/css/admin.css',
			array(),
			ASA_VERSION
		);

		// Enqueue WordPress color picker
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );

		wp_enqueue_script(
			'asa-admin-js',
			ASA_PLUGIN_URL . 'admin/js/admin.js',
			array( 'jquery', 'wp-color-picker' ),
			ASA_VERSION,
			true
		);
	}

	/**
	 * Render settings page.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Show save message
		if ( isset( $_GET['settings-updated'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'ai-store-assistant' ) . '</p></div>';
		}

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<div class="asa-admin-header">
				<p><?php esc_html_e( 'Configure your AI Store Assistant chatbot. Set up your OpenAI API key, customize the system prompt, and manage the knowledge base.', 'ai-store-assistant' ); ?></p>
			</div>

			<form method="post" action="options.php">
				<?php settings_fields( 'asa_settings' ); ?>
				<?php do_settings_sections( 'asa_settings_page' ); ?>
				<?php submit_button(); ?>
			</form>

			<div class="asa-admin-section">
				<h2><?php esc_html_e( 'Knowledge Base', 'ai-store-assistant' ); ?></h2>
				<p><?php esc_html_e( 'Manage your chatbot knowledge base by adding, editing, or deleting knowledge items.', 'ai-store-assistant' ); ?></p>
				<p>
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=asa_knowledge' ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'Manage Knowledge Base', 'ai-store-assistant' ); ?>
					</a>
				</p>
			</div>

			<div class="asa-admin-section">
				<h2><?php esc_html_e( 'Documentation', 'ai-store-assistant' ); ?></h2>
				<h3><?php esc_html_e( 'Installation', 'ai-store-assistant' ); ?></h3>
				<ol>
					<li><?php esc_html_e( 'Upload the plugin folder to /wp-content/plugins/', 'ai-store-assistant' ); ?></li>
					<li><?php esc_html_e( 'Activate the plugin through the WordPress Plugins menu', 'ai-store-assistant' ); ?></li>
					<li><?php esc_html_e( 'Ensure WooCommerce is installed and active', 'ai-store-assistant' ); ?></li>
				</ol>

				<h3><?php esc_html_e( 'Configuration', 'ai-store-assistant' ); ?></h3>
				<ol>
					<li><?php esc_html_e( 'Go to WooCommerce → AI Store Assistant', 'ai-store-assistant' ); ?></li>
					<li><?php esc_html_e( 'Enter your OpenAI API key (get one at https://platform.openai.com/api-keys)', 'ai-store-assistant' ); ?></li>
					<li><?php esc_html_e( 'Set your preferred model name (e.g., gpt-4o-mini)', 'ai-store-assistant' ); ?></li>
					<li><?php esc_html_e( 'Customize the system prompt to define the assistant\'s behavior and tone', 'ai-store-assistant' ); ?></li>
					<li><?php esc_html_e( 'Enable or disable product suggestions and order creation features', 'ai-store-assistant' ); ?></li>
				</ol>

				<h3><?php esc_html_e( 'Knowledge Base Management', 'ai-store-assistant' ); ?></h3>
				<ol>
					<li><?php esc_html_e( 'Go to Chatbot Knowledge in the WordPress admin menu', 'ai-store-assistant' ); ?></li>
					<li><?php esc_html_e( 'Add new knowledge items with titles and detailed content', 'ai-store-assistant' ); ?></li>
					<li><?php esc_html_e( 'The chatbot will use this information to answer customer questions', 'ai-store-assistant' ); ?></li>
					<li><?php esc_html_e( 'Set the maximum number of knowledge items to include in context (default: 20)', 'ai-store-assistant' ); ?></li>
				</ol>
			</div>
		</div>
		<?php
	}
}

