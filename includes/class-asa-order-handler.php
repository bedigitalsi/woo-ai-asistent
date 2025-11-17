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

		if ( ! is_array( $products ) || empty( $products ) ) {
			return new WP_Error( 'invalid_products', __( 'Invalid products data.', 'ai-store-assistant' ), array( 'status' => 400 ) );
		}

		// Create order
		$order = $this->create_order( $products, $customer );

		if ( is_wp_error( $order ) ) {
			return $order;
		}

		return rest_ensure_response( array(
			'order_id' => $order->get_id(),
			'status'   => $order->get_status(),
			'total'    => $order->get_total(),
			'currency' => $order->get_currency(),
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
		if ( ! function_exists( 'wc_create_order' ) ) {
			return new WP_Error( 'woocommerce_missing', __( 'WooCommerce is not available.', 'ai-store-assistant' ), array( 'status' => 500 ) );
		}

		$order = wc_create_order();

		if ( is_wp_error( $order ) ) {
			return $order;
		}

		// Add products
		foreach ( $products as $item ) {
			if ( ! isset( $item['id'] ) || ! isset( $item['qty'] ) ) {
				continue;
			}

			$product_id = absint( $item['id'] );
			$quantity   = absint( $item['qty'] );

			$product = wc_get_product( $product_id );
			if ( ! $product || ! $product->is_purchasable() ) {
				continue;
			}

			$order->add_product( $product, $quantity );
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
			// Guest order
			if ( isset( $customer['email'] ) ) {
				$order->set_billing_email( sanitize_email( $customer['email'] ) );
			}
			if ( isset( $customer['name'] ) ) {
				$name_parts = explode( ' ', $customer['name'], 2 );
				$order->set_billing_first_name( sanitize_text_field( $name_parts[0] ) );
				if ( isset( $name_parts[1] ) ) {
					$order->set_billing_last_name( sanitize_text_field( $name_parts[1] ) );
				}
			}
			if ( isset( $customer['address'] ) ) {
				$order->set_billing_address_1( sanitize_text_field( $customer['address'] ) );
			}
		}

		// Set order status
		$order->set_status( 'pending' );
		$order->calculate_totals();
		$order->save();

		return $order;
	}
}


