<?php
/**
 * Frontend class
 *
 * @package AI_Store_Assistant
 * @subpackage Public
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles frontend chat widget display and functionality.
 */
class ASA_Frontend {

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
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_footer', array( $this, 'render_chat_widget' ) );
	}

	/**
	 * Enqueue frontend scripts and styles.
	 */
	public function enqueue_scripts() {
		// Only load if API key is configured
		if ( ! $this->settings->has_api_key() ) {
			return;
		}

		wp_enqueue_style(
			'asa-chat-widget-css',
			ASA_PLUGIN_URL . 'public/css/chat-widget.css',
			array(),
			ASA_VERSION
		);

		wp_enqueue_script(
			'asa-chat-widget-js',
			ASA_PLUGIN_URL . 'public/js/chat-widget.js',
			array(),
			ASA_VERSION,
			true
		);

		// Localize script with REST API data
		wp_localize_script(
			'asa-chat-widget-js',
			'asaChatData',
			array(
				'restUrl' => rest_url( 'ai-store-assistant/v1/chat' ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'i18n'    => array(
					'error'           => __( 'Sorry, there was an error. Please try again.', 'ai-store-assistant' ),
					'apiError'        => __( 'Assistant is temporarily unavailable.', 'ai-store-assistant' ),
					'send'            => __( 'Send', 'ai-store-assistant' ),
					'typeMessage'     => __( 'Type your message...', 'ai-store-assistant' ),
					'viewProduct'     => __( 'View Product', 'ai-store-assistant' ),
					'assistantTitle'  => __( 'AI Assistant', 'ai-store-assistant' ),
					'welcomeMessage'  => __( 'Hello! How can I help you today?', 'ai-store-assistant' ),
				),
			)
		);
	}

	/**
	 * Render chat widget HTML.
	 */
	public function render_chat_widget() {
		// Only show if API key is configured
		if ( ! $this->settings->has_api_key() ) {
			return;
		}
		?>
		<div id="asa-chat-widget" class="asa-chat-widget">
			<div id="asa-chat-button" class="asa-chat-button" aria-label="<?php esc_attr_e( 'Open chat', 'ai-store-assistant' ); ?>">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</div>
			<div id="asa-chat-panel" class="asa-chat-panel" style="display: none;">
				<div class="asa-chat-header">
					<h3><?php esc_html_e( 'AI Assistant', 'ai-store-assistant' ); ?></h3>
					<button id="asa-chat-close" class="asa-chat-close" aria-label="<?php esc_attr_e( 'Close chat', 'ai-store-assistant' ); ?>">×</button>
				</div>
				<div id="asa-chat-messages" class="asa-chat-messages"></div>
				<div class="asa-chat-input-container">
					<input type="text" id="asa-chat-input" class="asa-chat-input" placeholder="<?php esc_attr_e( 'Type your message...', 'ai-store-assistant' ); ?>" />
					<button id="asa-chat-send" class="asa-chat-send"><?php esc_html_e( 'Send', 'ai-store-assistant' ); ?></button>
				</div>
			</div>
		</div>
		<?php
	}
}

