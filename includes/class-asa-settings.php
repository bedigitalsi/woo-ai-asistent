<?php
/**
 * Settings management class
 *
 * @package AI_Store_Assistant
 * @subpackage Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles plugin settings using WordPress Settings API.
 */
class ASA_Settings {

	/**
	 * Option group name.
	 *
	 * @var string
	 */
	private $option_group = 'asa_settings';

	/**
	 * Option name.
	 *
	 * @var string
	 */
	private $option_name = 'asa_settings';

	/**
	 * Default settings.
	 *
	 * @var array
	 */
	private $defaults = array(
		'openai_api_key'           => '',
		'model_name'               => 'gpt-4o-mini',
		'system_prompt'            => 'You are a helpful assistant for an online store. Help customers find products and answer their questions.',
		'enable_product_suggestions' => true,
		'enable_order_creation'    => false,
		'max_knowledge_items'      => 20,
		'chat_widget_color'        => '#0073aa',
	);

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Register settings using WordPress Settings API.
	 */
	public function register_settings() {
		register_setting(
			$this->option_group,
			$this->option_name,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => $this->defaults,
			)
		);

		add_settings_section(
			'asa_main_section',
			__( 'OpenAI Configuration', 'ai-store-assistant' ),
			array( $this, 'section_callback' ),
			'asa_settings_page'
		);

		add_settings_field(
			'openai_api_key',
			__( 'OpenAI API Key', 'ai-store-assistant' ),
			array( $this, 'api_key_field_callback' ),
			'asa_settings_page',
			'asa_main_section'
		);

		add_settings_field(
			'model_name',
			__( 'Model Name', 'ai-store-assistant' ),
			array( $this, 'model_name_field_callback' ),
			'asa_settings_page',
			'asa_main_section'
		);

		add_settings_field(
			'system_prompt',
			__( 'System Prompt', 'ai-store-assistant' ),
			array( $this, 'system_prompt_field_callback' ),
			'asa_settings_page',
			'asa_main_section'
		);

		add_settings_field(
			'enable_product_suggestions',
			__( 'Enable Product Suggestions', 'ai-store-assistant' ),
			array( $this, 'enable_product_suggestions_field_callback' ),
			'asa_settings_page',
			'asa_main_section'
		);

		add_settings_field(
			'enable_order_creation',
			__( 'Enable Order Creation (Experimental)', 'ai-store-assistant' ),
			array( $this, 'enable_order_creation_field_callback' ),
			'asa_settings_page',
			'asa_main_section'
		);

		add_settings_field(
			'max_knowledge_items',
			__( 'Maximum Knowledge Items for Context', 'ai-store-assistant' ),
			array( $this, 'max_knowledge_items_field_callback' ),
			'asa_settings_page',
			'asa_main_section'
		);

		add_settings_section(
			'asa_appearance_section',
			__( 'Appearance', 'ai-store-assistant' ),
			array( $this, 'appearance_section_callback' ),
			'asa_settings_page'
		);

		add_settings_field(
			'chat_widget_color',
			__( 'Chat Widget Color', 'ai-store-assistant' ),
			array( $this, 'chat_widget_color_field_callback' ),
			'asa_settings_page',
			'asa_appearance_section'
		);
	}

	/**
	 * Sanitize settings before saving.
	 *
	 * @param array $input Raw input data.
	 * @return array Sanitized settings.
	 */
	public function sanitize_settings( $input ) {
		// Get existing settings
		$existing = $this->get_settings();
		$sanitized = array();

		if ( isset( $input['openai_api_key'] ) ) {
			$sanitized['openai_api_key'] = sanitize_text_field( $input['openai_api_key'] );
		} else {
			$sanitized['openai_api_key'] = $existing['openai_api_key'];
		}

		if ( isset( $input['model_name'] ) ) {
			$sanitized['model_name'] = sanitize_text_field( $input['model_name'] );
		} else {
			$sanitized['model_name'] = $existing['model_name'];
		}

		if ( isset( $input['system_prompt'] ) ) {
			$sanitized['system_prompt'] = sanitize_textarea_field( $input['system_prompt'] );
		} else {
			$sanitized['system_prompt'] = $existing['system_prompt'];
		}

		if ( isset( $input['enable_product_suggestions'] ) ) {
			$sanitized['enable_product_suggestions'] = (bool) $input['enable_product_suggestions'];
		} else {
			$sanitized['enable_product_suggestions'] = false;
		}

		if ( isset( $input['enable_order_creation'] ) ) {
			$sanitized['enable_order_creation'] = (bool) $input['enable_order_creation'];
		} else {
			$sanitized['enable_order_creation'] = false;
		}

		if ( isset( $input['max_knowledge_items'] ) ) {
			$sanitized['max_knowledge_items'] = absint( $input['max_knowledge_items'] );
		} else {
			$sanitized['max_knowledge_items'] = $existing['max_knowledge_items'];
		}

		if ( isset( $input['chat_widget_color'] ) ) {
			$color = sanitize_hex_color( $input['chat_widget_color'] );
			$sanitized['chat_widget_color'] = $color ? $color : '#0073aa';
		} else {
			$sanitized['chat_widget_color'] = $existing['chat_widget_color'];
		}

		return wp_parse_args( $sanitized, $this->defaults );
	}

	/**
	 * Section callback.
	 */
	public function section_callback() {
		echo '<p>' . esc_html__( 'Configure OpenAI API settings for the chatbot.', 'ai-store-assistant' ) . '</p>';
	}

	/**
	 * API key field callback.
	 */
	public function api_key_field_callback() {
		$settings = $this->get_settings();
		$value    = isset( $settings['openai_api_key'] ) ? $settings['openai_api_key'] : '';
		$display  = ! empty( $value ) ? str_repeat( '*', min( strlen( $value ), 20 ) ) . '...' : '';
		?>
		<input type="password" name="asa_settings[openai_api_key]" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
		<?php if ( ! empty( $value ) ) : ?>
			<p class="description"><?php echo esc_html( $display ); ?></p>
		<?php endif; ?>
		<p class="description"><?php esc_html_e( 'Enter your OpenAI API key. Get one at https://platform.openai.com/api-keys', 'ai-store-assistant' ); ?></p>
		<?php
	}

	/**
	 * Model name field callback.
	 */
	public function model_name_field_callback() {
		$settings = $this->get_settings();
		$value    = isset( $settings['model_name'] ) ? $settings['model_name'] : 'gpt-4o-mini';
		?>
		<input type="text" name="asa_settings[model_name]" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
		<p class="description"><?php esc_html_e( 'OpenAI model name (e.g., gpt-4o-mini, gpt-4, gpt-3.5-turbo)', 'ai-store-assistant' ); ?></p>
		<?php
	}

	/**
	 * System prompt field callback.
	 */
	public function system_prompt_field_callback() {
		$settings = $this->get_settings();
		$value    = isset( $settings['system_prompt'] ) ? $settings['system_prompt'] : '';
		?>
		<textarea name="asa_settings[system_prompt]" rows="10" cols="50" class="large-text"><?php echo esc_textarea( $value ); ?></textarea>
		<p class="description"><?php esc_html_e( 'This prompt will be prepended to every conversation. Use it to define the assistant\'s role, tone, and behavior.', 'ai-store-assistant' ); ?></p>
		<?php
	}

	/**
	 * Enable product suggestions field callback.
	 */
	public function enable_product_suggestions_field_callback() {
		$settings = $this->get_settings();
		$checked  = isset( $settings['enable_product_suggestions'] ) && $settings['enable_product_suggestions'];
		?>
		<input type="checkbox" name="asa_settings[enable_product_suggestions]" value="1" <?php checked( $checked ); ?> />
		<label><?php esc_html_e( 'Enable product suggestions in chat responses', 'ai-store-assistant' ); ?></label>
		<?php
	}

	/**
	 * Enable order creation field callback.
	 */
	public function enable_order_creation_field_callback() {
		$settings = $this->get_settings();
		$checked  = isset( $settings['enable_order_creation'] ) && $settings['enable_order_creation'];
		?>
		<input type="checkbox" name="asa_settings[enable_order_creation]" value="1" <?php checked( $checked ); ?> />
		<label><?php esc_html_e( 'Enable order creation via chatbot (experimental)', 'ai-store-assistant' ); ?></label>
		<?php
	}

	/**
	 * Max knowledge items field callback.
	 */
	public function max_knowledge_items_field_callback() {
		$settings = $this->get_settings();
		$value    = isset( $settings['max_knowledge_items'] ) ? $settings['max_knowledge_items'] : 20;
		?>
		<input type="number" name="asa_settings[max_knowledge_items]" value="<?php echo esc_attr( $value ); ?>" min="1" max="100" />
		<p class="description"><?php esc_html_e( 'Maximum number of knowledge base items to include in context (1-100)', 'ai-store-assistant' ); ?></p>
		<?php
	}

	/**
	 * Appearance section callback.
	 */
	public function appearance_section_callback() {
		echo '<p>' . esc_html__( 'Customize the appearance of the chat widget.', 'ai-store-assistant' ) . '</p>';
	}

	/**
	 * Chat widget color field callback.
	 */
	public function chat_widget_color_field_callback() {
		$settings = $this->get_settings();
		$value    = isset( $settings['chat_widget_color'] ) ? $settings['chat_widget_color'] : '#0073aa';
		?>
		<input type="color" name="asa_settings[chat_widget_color]" value="<?php echo esc_attr( $value ); ?>" class="asa-color-picker" />
		<p class="description"><?php esc_html_e( 'Choose the main color for the chat widget (button, header, and accents).', 'ai-store-assistant' ); ?></p>
		<?php
	}

	/**
	 * Get all settings.
	 *
	 * @return array Settings array.
	 */
	public function get_settings() {
		$settings = get_option( $this->option_name, $this->defaults );
		return wp_parse_args( $settings, $this->defaults );
	}

	/**
	 * Get a specific setting.
	 *
	 * @param string $key Setting key.
	 * @param mixed  $default Default value if not set.
	 * @return mixed Setting value.
	 */
	public function get_setting( $key, $default = null ) {
		$settings = $this->get_settings();
		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
	}

	/**
	 * Check if API key is configured.
	 *
	 * @return bool True if API key is set.
	 */
	public function has_api_key() {
		$api_key = $this->get_setting( 'openai_api_key' );
		return ! empty( $api_key );
	}
}

