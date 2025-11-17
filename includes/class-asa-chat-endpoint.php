<?php
/**
 * Chat REST API endpoint class
 *
 * @package AI_Store_Assistant
 * @subpackage Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles chat REST API endpoint and OpenAI integration.
 */
class ASA_Chat_Endpoint {

	/**
	 * Settings instance.
	 *
	 * @var ASA_Settings
	 */
	private $settings;

	/**
	 * Knowledge base instance.
	 *
	 * @var ASA_Knowledge_Base
	 */
	private $knowledge_base;

	/**
	 * Product recommender instance.
	 *
	 * @var ASA_Product_Recommender
	 */
	private $product_recommender;

	/**
	 * Constructor.
	 *
	 * @param ASA_Settings            $settings Settings instance.
	 * @param ASA_Knowledge_Base      $knowledge_base Knowledge base instance.
	 * @param ASA_Product_Recommender $product_recommender Product recommender instance.
	 */
	public function __construct( $settings, $knowledge_base, $product_recommender ) {
		$this->settings            = $settings;
		$this->knowledge_base      = $knowledge_base;
		$this->product_recommender = $product_recommender;

		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST API routes.
	 */
	public function register_routes() {
		register_rest_route(
			'ai-store-assistant/v1',
			'/chat',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_chat' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'messages' => array(
						'required' => true,
						'type'     => 'array',
						'validate_callback' => array( $this, 'validate_messages' ),
					),
				),
			)
		);
	}

	/**
	 * Validate messages array.
	 *
	 * @param array $messages Messages array.
	 * @return bool True if valid.
	 */
	public function validate_messages( $messages ) {
		if ( ! is_array( $messages ) ) {
			return false;
		}

		foreach ( $messages as $message ) {
			if ( ! isset( $message['role'] ) || ! isset( $message['content'] ) ) {
				return false;
			}
			if ( ! in_array( $message['role'], array( 'user', 'assistant', 'system' ), true ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Handle chat request.
	 *
	 * @param WP_REST_Request $request REST request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function handle_chat( $request ) {
		// Verify nonce
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error( 'rest_forbidden', __( 'Invalid nonce.', 'ai-store-assistant' ), array( 'status' => 403 ) );
		}

		// Check if API key is configured
		if ( ! $this->settings->has_api_key() ) {
			return new WP_Error( 'api_key_missing', __( 'OpenAI API key is not configured.', 'ai-store-assistant' ), array( 'status' => 500 ) );
		}

		$messages = $request->get_param( 'messages' );

		// Build system message with context
		$system_message = $this->build_system_message();

		// Prepare messages for OpenAI
		$openai_messages = array(
			array(
				'role'    => 'system',
				'content' => $system_message,
			),
		);

		// Add conversation history (limit to last 10 messages to avoid token limits)
		$conversation_messages = array_slice( $messages, -10 );
		foreach ( $conversation_messages as $msg ) {
			// Skip system messages from frontend
			if ( 'system' !== $msg['role'] ) {
				$openai_messages[] = array(
					'role'    => sanitize_text_field( $msg['role'] ),
					'content' => sanitize_text_field( $msg['content'] ),
				);
			}
		}

		// Call OpenAI
		$response = $this->call_openai( $openai_messages );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Parse response
		$parsed = $this->parse_response( $response );

		return rest_ensure_response( $parsed );
	}

	/**
	 * Build system message with context.
	 *
	 * @return string System message content.
	 */
	private function build_system_message() {
		$system_prompt = $this->settings->get_setting( 'system_prompt', '' );
		$enable_products = $this->settings->get_setting( 'enable_product_suggestions', true );

		$parts = array();

		// Base system prompt
		if ( ! empty( $system_prompt ) ) {
			$parts[] = $system_prompt;
		}

		// Knowledge base context
		$max_items = $this->settings->get_setting( 'max_knowledge_items', 20 );
		$knowledge = $this->knowledge_base->get_knowledge_context( $max_items );
		if ( ! empty( $knowledge ) ) {
			$parts[] = "\n\n## Knowledge Base:\n" . $knowledge;
		}

		// Product context
		if ( $enable_products ) {
			$product_context = $this->product_recommender->get_product_context( 50 );
			if ( ! empty( $product_context ) ) {
				$parts[] = "\n\n## Available Products:\n" . $product_context;
			}

			// JSON schema instructions
			$parts[] = "\n\n## Response Format:\nWhen suggesting products, you MUST respond with valid JSON in this exact format:\n{\n  \"assistant_reply\": \"Your text response here\",\n  \"products\": [\n    {\n      \"id\": 123,\n      \"name\": \"Product Name\",\n      \"price\": \"19.99\",\n      \"url\": \"https://example.com/product\",\n      \"image\": \"https://example.com/image.jpg\"\n    }\n  ]\n}\n\nIf no products should be suggested, return: {\"assistant_reply\": \"Your text\", \"products\": []}\n\nIMPORTANT: Always respond with valid JSON. The assistant_reply field contains your conversational response, and the products array contains product suggestions matching the user's query.";
		} else {
			$parts[] = "\n\n## Response Format:\nRespond with valid JSON: {\"assistant_reply\": \"Your text response\", \"products\": []}";
		}

		// Order creation instructions
		$enable_orders = $this->settings->get_setting( 'enable_order_creation', false );
		if ( $enable_orders ) {
			// Get shipping cost for context
			$shipping_cost = $this->get_shipping_cost_for_prompt();
			
			$parts[] = "\n\n## Order Creation:\nYou can help customers create orders via chat. When a customer wants to place an order, you MUST collect ALL of the following information before creating the order:\n\nREQUIRED INFORMATION:\n1. Products they want (product IDs and quantities) - ask which products and how many\n2. Customer email address - ask for a valid email\n3. Customer phone number - ask for phone number for delivery coordination\n4. Full delivery address - ask for complete address including street, city, postal code, and country\n\nIMPORTANT ORDER CREATION RULES:\n- Do NOT create an order until you have ALL required information listed above\n- If ANY information is missing, politely but firmly ask for it. Explain why each piece is needed\n- Be persistent - don't create an order with incomplete information\n- Always inform the customer about shipping costs: Standard shipping cost is approximately " . $shipping_cost . "\n- Payment method is Cash on Delivery (customer pays when receiving the order)\n- Always calculate and confirm the total order amount (products + shipping) before creating\n- When you have ALL required information, respond with JSON that includes an \"order\" object:\n{\n  \"assistant_reply\": \"Perfect! I have all the information. Your order total will be [amount] including shipping. Creating your order now...\",\n  \"products\": [],\n  \"order\": {\n    \"products\": [{\"id\": 123, \"qty\": 1}],\n    \"email\": \"customer@example.com\",\n    \"phone\": \"+1234567890\",\n    \"address\": \"Street 123\\nCity, Postal Code\\nCountry\"\n  }\n}\n\n- The address should be formatted with line breaks (\\n) for better readability\n- Be friendly, helpful, and professional when collecting information\n- If customer refuses to provide required information, politely explain that you cannot create an order without it";
		}

		return implode( '', $parts );
	}

	/**
	 * Call OpenAI API.
	 *
	 * @param array $messages Messages array.
	 * @return string|WP_Error Response content or error.
	 */
	private function call_openai( $messages ) {
		$api_key = $this->settings->get_setting( 'openai_api_key' );
		$model   = $this->settings->get_setting( 'model_name', 'gpt-4o-mini' );

		$url = 'https://api.openai.com/v1/chat/completions';

		$body = array(
			'model'    => $model,
			'messages' => $messages,
			'temperature' => 0.7,
			'max_tokens' => 1000,
		);

		$args = array(
			'body'        => wp_json_encode( $body ),
			'headers'     => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $api_key,
			),
			'timeout'     => 30,
			'method'      => 'POST',
		);

		$response = wp_remote_post( $url, $args );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'openai_error', $response->get_error_message(), array( 'status' => 500 ) );
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( 200 !== $response_code ) {
			$error_data = json_decode( $response_body, true );
			$error_message = isset( $error_data['error']['message'] ) ? $error_data['error']['message'] : __( 'OpenAI API error.', 'ai-store-assistant' );
			return new WP_Error( 'openai_api_error', $error_message, array( 'status' => $response_code ) );
		}

		$data = json_decode( $response_body, true );

		if ( ! isset( $data['choices'][0]['message']['content'] ) ) {
			return new WP_Error( 'openai_parse_error', __( 'Invalid response from OpenAI.', 'ai-store-assistant' ), array( 'status' => 500 ) );
		}

		return $data['choices'][0]['message']['content'];
	}

	/**
	 * Parse OpenAI response and extract structured data.
	 *
	 * @param string $response Raw response from OpenAI.
	 * @return array Parsed response with assistant_reply and products.
	 */
	private function parse_response( $response ) {
		// Try to parse as JSON
		$decoded = json_decode( $response, true );

		if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
			// Valid JSON response
			$assistant_reply = isset( $decoded['assistant_reply'] ) ? $decoded['assistant_reply'] : $response;
			$products = isset( $decoded['products'] ) && is_array( $decoded['products'] ) ? $decoded['products'] : array();
			$order_data = isset( $decoded['order'] ) && is_array( $decoded['order'] ) ? $decoded['order'] : null;

			// Validate and enrich product data
			$validated_products = array();
			foreach ( $products as $product ) {
				if ( isset( $product['id'] ) ) {
					$product_data = $this->product_recommender->get_product_by_id( $product['id'] );
					if ( $product_data ) {
						$validated_products[] = $product_data;
					}
				}
			}

			$result = array(
				'assistant_reply' => sanitize_text_field( $assistant_reply ),
				'products'        => $validated_products,
			);

			// If order data is present, validate and include it
			if ( $order_data ) {
				$result['order'] = $this->validate_order_data( $order_data );
			}

			return $result;
		}

		// Fallback: treat as plain text
		return array(
			'assistant_reply' => sanitize_text_field( $response ),
			'products'        => array(),
		);
	}

	/**
	 * Validate order data from AI response.
	 *
	 * @param array $order_data Order data from AI.
	 * @return array|null Validated order data or null if invalid.
	 */
	private function validate_order_data( $order_data ) {
		// Check required fields
		if ( ! isset( $order_data['products'] ) || ! is_array( $order_data['products'] ) || empty( $order_data['products'] ) ) {
			return null;
		}

		if ( empty( $order_data['email'] ) || ! is_email( $order_data['email'] ) ) {
			return null;
		}

		if ( empty( $order_data['phone'] ) ) {
			return null;
		}

		if ( empty( $order_data['address'] ) ) {
			return null;
		}

		// Validate products
		$validated_products = array();
		foreach ( $order_data['products'] as $product ) {
			if ( ! isset( $product['id'] ) || ! isset( $product['qty'] ) ) {
				continue;
			}
			$product_obj = wc_get_product( absint( $product['id'] ) );
			if ( $product_obj && $product_obj->is_purchasable() ) {
				$validated_products[] = array(
					'id'  => absint( $product['id'] ),
					'qty' => absint( $product['qty'] ),
				);
			}
		}

		if ( empty( $validated_products ) ) {
			return null;
		}

		return array(
			'products' => $validated_products,
			'email'    => sanitize_email( $order_data['email'] ),
			'phone'    => sanitize_text_field( $order_data['phone'] ),
			'address'  => sanitize_textarea_field( $order_data['address'] ),
		);
	}

	/**
	 * Get shipping cost for system prompt.
	 *
	 * @return string Shipping cost formatted string.
	 */
	private function get_shipping_cost_for_prompt() {
		// Try to get from WooCommerce settings
		$flat_rate_cost = get_option( 'woocommerce_flat_rate_cost', '' );
		if ( ! empty( $flat_rate_cost ) && is_numeric( $flat_rate_cost ) ) {
			$currency_symbol = get_woocommerce_currency_symbol();
			return $currency_symbol . number_format( floatval( $flat_rate_cost ), 2 );
		}

		// Default shipping cost
		$default_shipping = apply_filters( 'asa_default_shipping_cost', 5.00 );
		$currency_symbol = get_woocommerce_currency_symbol();
		return $currency_symbol . number_format( floatval( $default_shipping ), 2 );
	}
}


