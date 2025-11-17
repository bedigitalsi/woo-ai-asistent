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
		add_action( 'admin_init', array( $this, 'handle_check_updates' ) );
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

		// Show update check message
		if ( isset( $_GET['asa_check_updates'] ) ) {
			$check_result = isset( $_GET['asa_update_found'] ) ? sanitize_text_field( $_GET['asa_update_found'] ) : '';
			$remote_version = isset( $_GET['asa_remote_version'] ) ? sanitize_text_field( $_GET['asa_remote_version'] ) : '';
			
			if ( 'yes' === $check_result ) {
				$message = esc_html__( 'Update check completed! A new version is available.', 'ai-store-assistant' );
				if ( $remote_version ) {
					$message .= ' ' . sprintf( esc_html__( 'Latest version: %s', 'ai-store-assistant' ), '<strong>' . esc_html( $remote_version ) . '</strong>' );
				}
				$message .= ' ' . esc_html__( 'Check the Plugins page for update notification.', 'ai-store-assistant' );
				echo '<div class="notice notice-success is-dismissible"><p>' . $message . '</p></div>';
			} elseif ( 'no' === $check_result ) {
				echo '<div class="notice notice-info is-dismissible"><p>' . esc_html__( 'Update check completed! You are using the latest version.', 'ai-store-assistant' ) . '</p></div>';
			} else {
				echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__( 'Unable to check for updates. Please verify your repository is public and try again later.', 'ai-store-assistant' ) . '</p></div>';
			}
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
				<h2><?php esc_html_e( 'Plugin Updates', 'ai-store-assistant' ); ?></h2>
				<p><?php esc_html_e( 'Check for the latest version of AI Store Assistant from GitHub.', 'ai-store-assistant' ); ?></p>
				<p>
					<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'asa_check_updates', '1', admin_url( 'admin.php?page=ai-store-assistant' ) ), 'asa_check_updates' ) ); ?>" class="button button-secondary">
						<?php esc_html_e( 'Check for Updates', 'ai-store-assistant' ); ?>
					</a>
					<a href="<?php echo esc_url( admin_url( 'plugins.php' ) ); ?>" class="button">
						<?php esc_html_e( 'View Plugins Page', 'ai-store-assistant' ); ?>
					</a>
				</p>
				<p class="description">
					<?php esc_html_e( 'Current version:', 'ai-store-assistant' ); ?> <strong><?php echo esc_html( ASA_VERSION ); ?></strong>
					<br>
					<?php esc_html_e( 'WordPress automatically checks for updates every 12 hours. Use the button above to check immediately after creating a new GitHub release.', 'ai-store-assistant' ); ?>
				</p>
			</div>

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

	/**
	 * Handle manual update check.
	 */
	public function handle_check_updates() {
		if ( ! isset( $_GET['asa_check_updates'] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Verify nonce
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'asa_check_updates' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'ai-store-assistant' ) );
		}

		// Clear update transients
		delete_transient( 'asa_remote_version' );
		delete_transient( 'asa_changelog' );
		
		// Clear WordPress update cache
		delete_site_transient( 'update_plugins' );
		
		// Force WordPress to check for updates
		wp_update_plugins();

		// Check for updates manually
		require_once ASA_PLUGIN_DIR . 'includes/class-asa-updater.php';
		$updater = new ASA_Updater( ASA_PLUGIN_DIR . 'ai-store-assistant.php', ASA_VERSION );
		
		// Get remote version (skip cache)
		$remote_version = $updater->get_remote_version_public( true );

		// Determine result
		$update_found = 'error';
		if ( $remote_version ) {
			if ( version_compare( ASA_VERSION, $remote_version, '<' ) ) {
				$update_found = 'yes';
			} else {
				$update_found = 'no';
			}
		}

		// Redirect with result
		$redirect_args = array(
			'page'              => 'ai-store-assistant',
			'asa_check_updates' => '1',
			'asa_update_found'  => $update_found,
		);
		
		if ( $remote_version ) {
			$redirect_args['asa_remote_version'] = $remote_version;
		}
		
		$redirect_url = add_query_arg( $redirect_args, admin_url( 'admin.php' ) );

		wp_safe_redirect( $redirect_url );
		exit;
	}
}

