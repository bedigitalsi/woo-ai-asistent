<?php
/**
 * Order handler class
 *
 * @package AI_Store_Assistant
 * @subpackage Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles order creation via chatbot.
 */
class ASA_Order_Handler {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST API routes.
	 */
	public function register_routes() {
		register_rest_route(
			'ai-store-assistant/v1',
			'/create-order',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_create_order' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'products' => array(
						'required' => true,
						'type'     => 'array',
					),
					'customer' => array(
						'required' => false,
						'type'     => 'object',
					),
				),
			)
		);
	}

	/**
	 * Handle order creation request.
	 *
	 * @param WP_REST_Request $request REST request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function handle_create_order( $request ) {
		// Verify nonce
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error( 'rest_forbidden', __( 'Invalid nonce.', 'ai-store-assistant' ), array( 'status' => 403 ) );
		}

		// Check if order creation is enabled
		require_once ASA_PLUGIN_DIR . 'includes/class-asa-settings.php';
		$settings = new ASA_Settings();
		if ( ! $settings->get_setting( 'enable_order_creation', false ) ) {
			return new WP_Error( 'feature_disabled', __( 'Order creation via chatbot is disabled.', 'ai-store-assistant' ), array( 'status' => 403 ) );
		}

		$products = $request->get_param( 'products' );
		$customer = $request->get_param( 'customer' );

		// Log for debugging (remove in production)
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'ASA Order Creation - Products: ' . print_r( $products, true ) );
			error_log( 'ASA Order Creation - Customer: ' . print_r( $customer, true ) );
		}

		if ( ! is_array( $products ) || empty( $products ) ) {
			return new WP_Error( 'invalid_products', __( 'Invalid products data.', 'ai-store-assistant' ), array( 'status' => 400 ) );
		}

		// Validate required customer data
		if ( empty( $customer['email'] ) || ! is_email( $customer['email'] ) ) {
			return new WP_Error( 'invalid_email', __( 'Valid email address is required.', 'ai-store-assistant' ), array( 'status' => 400 ) );
		}

		if ( empty( $customer['phone'] ) ) {
			return new WP_Error( 'invalid_phone', __( 'Phone number is required.', 'ai-store-assistant' ), array( 'status' => 400 ) );
		}

		if ( empty( $customer['first_name'] ) ) {
			return new WP_Error( 'invalid_name', __( 'First name is required.', 'ai-store-assistant' ), array( 'status' => 400 ) );
		}

		if ( empty( $customer['address_1'] ) ) {
			return new WP_Error( 'invalid_address', __( 'Street address is required.', 'ai-store-assistant' ), array( 'status' => 400 ) );
		}

		if ( empty( $customer['city'] ) ) {
			return new WP_Error( 'invalid_city', __( 'City is required.', 'ai-store-assistant' ), array( 'status' => 400 ) );
		}

		if ( empty( $customer['postcode'] ) ) {
			return new WP_Error( 'invalid_postcode', __( 'Postal code is required.', 'ai-store-assistant' ), array( 'status' => 400 ) );
		}

		// Create order
		$order = $this->create_order( $products, $customer );

		if ( is_wp_error( $order ) ) {
			return $order;
		}

		// Get shipping total
		$shipping_total = 0;
		foreach ( $order->get_items( 'shipping' ) as $shipping_item ) {
			$shipping_total += floatval( $shipping_item->get_total() );
		}

		return rest_ensure_response( array(
			'order_id'       => $order->get_id(),
			'status'         => $order->get_status(),
			'total'          => $order->get_total(),
			'subtotal'       => $order->get_subtotal(),
			'shipping_total' => $shipping_total,
			'currency'       => $order->get_currency(),
			'currency_symbol' => get_woocommerce_currency_symbol(),
		) );
	}

	/**
	 * Create a WooCommerce order.
	 *
	 * @param array $products Products array with id and qty.
	 * @param array $customer Customer data.
	 * @return WC_Order|WP_Error Order object or error.
	 */
	private function create_order( $products, $customer = array() ) {
		// Ensure WooCommerce is loaded
		if ( ! function_exists( 'wc_create_order' ) ) {
			return new WP_Error( 'woocommerce_missing', __( 'WooCommerce is not available.', 'ai-store-assistant' ), array( 'status' => 500 ) );
		}

		// Ensure WooCommerce is initialized
		if ( ! function_exists( 'WC' ) || ! WC() ) {
			return new WP_Error( 'woocommerce_not_initialized', __( 'WooCommerce is not initialized.', 'ai-store-assistant' ), array( 'status' => 500 ) );
		}

		$order = wc_create_order();

		if ( is_wp_error( $order ) ) {
			return $order;
		}

		// Add products
		$products_added = 0;
		foreach ( $products as $item ) {
			if ( ! isset( $item['id'] ) || ! isset( $item['qty'] ) ) {
				continue;
			}

			$product_id = absint( $item['id'] );
			$quantity   = absint( $item['qty'] );

			if ( $quantity <= 0 ) {
				continue;
			}

			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				continue;
			}

			if ( ! $product->is_purchasable() ) {
				continue;
			}

			$order->add_product( $product, $quantity );
			$products_added++;
		}

		// Check if any products were added
		if ( $products_added === 0 ) {
			wp_delete_post( $order->get_id(), true );
			return new WP_Error( 'no_products', __( 'No valid products could be added to the order.', 'ai-store-assistant' ), array( 'status' => 400 ) );
		}

		// Set customer data
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
			$order->set_customer_id( $user_id );

			$user = get_userdata( $user_id );
			if ( $user ) {
				$order->set_billing_email( $user->user_email );
				$order->set_billing_first_name( $user->first_name );
				$order->set_billing_last_name( $user->last_name );
			}
		} elseif ( ! empty( $customer ) ) {
			// Guest order - set all required customer data from structured fields
			$order->set_billing_email( sanitize_email( $customer['email'] ) );
			$order->set_billing_phone( sanitize_text_field( $customer['phone'] ) );
			$order->set_billing_first_name( sanitize_text_field( $customer['first_name'] ) );
			$order->set_billing_last_name( sanitize_text_field( $customer['last_name'] ) );
			$order->set_billing_address_1( sanitize_text_field( $customer['address_1'] ) );
			$order->set_billing_city( sanitize_text_field( $customer['city'] ) );
			$order->set_billing_postcode( sanitize_text_field( $customer['postcode'] ) );
			$order->set_billing_country( sanitize_text_field( $customer['country'] ) );

			// Set shipping address same as billing
			$order->set_shipping_first_name( sanitize_text_field( $customer['first_name'] ) );
			$order->set_shipping_last_name( sanitize_text_field( $customer['last_name'] ) );
			$order->set_shipping_address_1( sanitize_text_field( $customer['address_1'] ) );
			$order->set_shipping_city( sanitize_text_field( $customer['city'] ) );
			$order->set_shipping_postcode( sanitize_text_field( $customer['postcode'] ) );
			$order->set_shipping_country( sanitize_text_field( $customer['country'] ) );
		}

		// Set payment method to Cash on Delivery
		$payment_gateways = WC()->payment_gateways->payment_gateways();
		$cod_gateway = isset( $payment_gateways['cod'] ) ? $payment_gateways['cod'] : null;
		
		if ( $cod_gateway && $cod_gateway->enabled === 'yes' ) {
			$order->set_payment_method( 'cod' );
			$order->set_payment_method_title( $cod_gateway->get_title() );
		} else {
			// Fallback: set manually
			$order->set_payment_method( 'cod' );
			$order->set_payment_method_title( __( 'Cash on Delivery', 'woocommerce' ) );
		}

		// Calculate shipping costs
		$this->calculate_shipping( $order );

		// Set order status
		$order->set_status( 'pending' );
		
		// Calculate totals
		try {
			$order->calculate_totals();
			$order->save();
		} catch ( Exception $e ) {
			wp_delete_post( $order->get_id(), true );
			return new WP_Error( 'order_calculation_failed', __( 'Failed to calculate order totals: ', 'ai-store-assistant' ) . $e->getMessage(), array( 'status' => 500 ) );
		}

		// Add order note
		$order->add_order_note( __( 'Order created via AI Store Assistant chatbot.', 'ai-store-assistant' ) );

		return $order;
	}

	/**
	 * Parse address string into components.
	 *
	 * @param string $address Full address string.
	 * @return array Address components.
	 */
	private function parse_address( $address ) {
		$parts = array(
			'street'   => '',
			'city'     => '',
			'postcode' => '',
			'country'  => '',
		);

		// Try to parse address - simple approach
		$lines = explode( "\n", $address );
		$parts['street'] = trim( $lines[0] );

		// Try to extract city, postcode, country from remaining lines
		if ( count( $lines ) > 1 ) {
			$last_line = trim( end( $lines ) );
			// Try to match postcode pattern (varies by country)
			if ( preg_match( '/\b\d{4,6}\b/', $last_line, $matches ) ) {
				$parts['postcode'] = $matches[0];
			}
			// Extract city (usually before postcode)
			$city_part = preg_replace( '/\b\d{4,6}\b.*/', '', $last_line );
			$parts['city'] = trim( $city_part );
		}

		// Default country if not specified
		$parts['country'] = get_option( 'woocommerce_default_country', '' );
		if ( ! empty( $parts['country'] ) && strpos( $parts['country'], ':' ) !== false ) {
			$parts['country'] = explode( ':', $parts['country'] )[0];
		}

		return $parts;
	}

	/**
	 * Calculate shipping costs for order.
	 *
	 * @param WC_Order $order Order object.
	 */
	private function calculate_shipping( $order ) {
		if ( ! function_exists( 'WC' ) || ! WC()->shipping() ) {
			return;
		}

		$shipping_methods = WC()->shipping->get_shipping_methods();
		
		// Try to use flat rate shipping if available
		if ( isset( $shipping_methods['flat_rate'] ) && $shipping_methods['flat_rate']->enabled === 'yes' ) {
			$shipping_item = new WC_Order_Item_Shipping();
			$shipping_item->set_method_title( $shipping_methods['flat_rate']->get_title() );
			$shipping_item->set_method_id( 'flat_rate' );
			$shipping_item->set_total( $this->get_shipping_cost() );
			$order->add_item( $shipping_item );
		} else {
			// Fallback: add a simple shipping cost
			$shipping_item = new WC_Order_Item_Shipping();
			$shipping_item->set_method_title( __( 'Standard Shipping', 'ai-store-assistant' ) );
			$shipping_item->set_method_id( 'standard' );
			$shipping_item->set_total( $this->get_shipping_cost() );
			$order->add_item( $shipping_item );
		}
	}

	/**
	 * Get shipping cost.
	 *
	 * @return float Shipping cost.
	 */
	private function get_shipping_cost() {
		// Try to get from WooCommerce settings
		$flat_rate_cost = get_option( 'woocommerce_flat_rate_cost', '' );
		if ( ! empty( $flat_rate_cost ) && is_numeric( $flat_rate_cost ) ) {
			return floatval( $flat_rate_cost );
		}

		// Default shipping cost (can be configured later)
		$default_shipping = apply_filters( 'asa_default_shipping_cost', 5.00 );
		return floatval( $default_shipping );
	}
}


