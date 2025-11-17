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

		// Add inline CSS for custom color
		$widget_color = $this->settings->get_setting( 'chat_widget_color', '#0073aa' );
		$custom_css = $this->get_custom_color_css( $widget_color );
		wp_add_inline_style( 'asa-chat-widget-css', $custom_css );

		wp_enqueue_script(
			'asa-chat-widget-js',
			ASA_PLUGIN_URL . 'public/js/chat-widget.js',
			array(),
			ASA_VERSION,
			true
		);

		// Get widget settings
		$widget_title = $this->settings->get_setting( 'chat_widget_title', 'AI Assistant' );
		$welcome_message = $this->settings->get_setting( 'chat_welcome_message', 'Hello! How can I help you today?' );

		// Localize script with REST API data
		wp_localize_script(
			'asa-chat-widget-js',
			'asaChatData',
			array(
				'restUrl' => rest_url( 'ai-store-assistant/v1/chat' ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'widgetTitle' => $widget_title,
				'welcomeMessage' => $welcome_message,
				'i18n'    => array(
					'error'           => __( 'Sorry, there was an error. Please try again.', 'ai-store-assistant' ),
					'apiError'        => __( 'Assistant is temporarily unavailable.', 'ai-store-assistant' ),
					'send'            => __( 'Send', 'ai-store-assistant' ),
					'typeMessage'     => __( 'Type your message...', 'ai-store-assistant' ),
					'viewProduct'     => __( 'View Product', 'ai-store-assistant' ),
					'orderSuccess'    => __( 'Order created successfully!', 'ai-store-assistant' ),
					'orderError'      => __( 'Unable to create order. Please try again.', 'ai-store-assistant' ),
					'loading'         => __( 'Thinking...', 'ai-store-assistant' ),
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
					<div class="asa-chat-header-left">
						<?php
						$avatar_image = $this->settings->get_setting( 'chat_widget_image', '' );
						if ( ! empty( $avatar_image ) ) :
							?>
							<img src="<?php echo esc_url( $avatar_image ); ?>" alt="<?php esc_attr_e( 'Assistant Avatar', 'ai-store-assistant' ); ?>" class="asa-chat-avatar" />
						<?php endif; ?>
						<h3 id="asa-chat-title"><?php echo esc_html( $this->settings->get_setting( 'chat_widget_title', 'AI Assistant' ) ); ?></h3>
					</div>
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

	/**
	 * Get custom color CSS for the chat widget.
	 *
	 * @param string $color Hex color code.
	 * @return string CSS rules.
	 */
	private function get_custom_color_css( $color ) {
		// Calculate darker shade for hover states (reduce brightness by 15%)
		$color_rgb = $this->hex_to_rgb( $color );
		$darker_color = $this->darken_color( $color_rgb, 0.15 );

		$css = "
		.asa-chat-button {
			background: {$color} !important;
		}
		.asa-chat-button:hover {
			background: {$darker_color} !important;
		}
		.asa-chat-header {
			background: {$color} !important;
		}
		.asa-chat-message-user .asa-chat-message-content {
			background: {$color} !important;
		}
		.asa-chat-product-price {
			color: {$color} !important;
		}
		.asa-chat-product-button {
			background: {$color} !important;
		}
		.asa-chat-product-button:hover {
			background: {$darker_color} !important;
		}
		.asa-chat-send {
			background: {$color} !important;
		}
		.asa-chat-send:hover:not(:disabled) {
			background: {$darker_color} !important;
		}
		.asa-chat-input:focus {
			border-color: {$color} !important;
		}
		";

		return $css;
	}

	/**
	 * Convert hex color to RGB array.
	 *
	 * @param string $hex Hex color code.
	 * @return array RGB values.
	 */
	private function hex_to_rgb( $hex ) {
		$hex = ltrim( $hex, '#' );
		return array(
			'r' => hexdec( substr( $hex, 0, 2 ) ),
			'g' => hexdec( substr( $hex, 2, 2 ) ),
			'b' => hexdec( substr( $hex, 4, 2 ) ),
		);
	}

	/**
	 * Darken a color by a percentage.
	 *
	 * @param array $rgb RGB color array.
	 * @param float $percent Percentage to darken (0-1).
	 * @return string Hex color code.
	 */
	private function darken_color( $rgb, $percent ) {
		$r = max( 0, min( 255, $rgb['r'] * ( 1 - $percent ) ) );
		$g = max( 0, min( 255, $rgb['g'] * ( 1 - $percent ) ) );
		$b = max( 0, min( 255, $rgb['b'] * ( 1 - $percent ) ) );
		return sprintf( '#%02x%02x%02x', $r, $g, $b );
	}
}

