<?php
/**
 * Product recommender class
 *
 * @package AI_Store_Assistant
 * @subpackage Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles product context building for OpenAI.
 */
class ASA_Product_Recommender {

	/**
	 * Get product context for OpenAI.
	 *
	 * @param int $limit Maximum number of products to include.
	 * @return string Product context as formatted text.
	 */
	public function get_product_context( $limit = 50 ) {
		if ( ! function_exists( 'wc_get_products' ) ) {
			return '';
		}

		$args = array(
			'status'         => 'publish',
			'limit'          => absint( $limit ),
			'stock_status'   => 'instock',
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		$products = wc_get_products( $args );
		$product_list = array();

		foreach ( $products as $product ) {
			$product_data = array(
				'id'          => $product->get_id(),
				'name'        => $product->get_name(),
				'description' => wp_strip_all_tags( $product->get_short_description() ?: $product->get_description() ),
				'price'       => $product->get_price_html(),
				'price_raw'   => $product->get_price(),
				'url'         => $product->get_permalink(),
				'image'       => get_the_post_thumbnail_url( $product->get_id(), 'thumbnail' ) ?: '',
			);

			// Format for context
			$formatted = sprintf(
				"ID: %d\nName: %s\nPrice: %s\nDescription: %s\nURL: %s",
				$product_data['id'],
				$product_data['name'],
				$product_data['price_raw'],
				substr( $product_data['description'], 0, 200 ),
				$product_data['url']
			);

			$product_list[] = $formatted;
		}

		return implode( "\n\n---\n\n", $product_list );
	}

	/**
	 * Get products as JSON array for structured output.
	 *
	 * @param int $limit Maximum number of products.
	 * @return array Array of product data.
	 */
	public function get_products_array( $limit = 50 ) {
		if ( ! function_exists( 'wc_get_products' ) ) {
			return array();
		}

		$args = array(
			'status'       => 'publish',
			'limit'        => absint( $limit ),
			'stock_status' => 'instock',
			'orderby'      => 'date',
			'order'        => 'DESC',
		);

		$products = wc_get_products( $args );
		$product_list = array();

		foreach ( $products as $product ) {
			// Format price cleanly
			$price_html = $product->get_price_html();
			$price_clean = wp_strip_all_tags( $price_html );
			$price_clean = html_entity_decode( $price_clean, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			// Clean up multiple spaces
			$price_clean = preg_replace( '/\s+/', ' ', $price_clean );
			$price_clean = trim( $price_clean );

			$product_list[] = array(
				'id'    => $product->get_id(),
				'name'  => $product->get_name(),
				'price' => $price_clean,
				'url'   => $product->get_permalink(),
				'image' => get_the_post_thumbnail_url( $product->get_id(), 'medium' ) ?: '',
			);
		}

		return $product_list;
	}

	/**
	 * Get product by ID for structured output.
	 *
	 * @param int $product_id Product ID.
	 * @return array|null Product data or null if not found.
	 */
	public function get_product_by_id( $product_id ) {
		$product = wc_get_product( $product_id );
		if ( ! $product || ! $product->is_purchasable() ) {
			return null;
		}

		// Format price cleanly
		$price_html = $product->get_price_html();
		$price_clean = wp_strip_all_tags( $price_html );
		$price_clean = html_entity_decode( $price_clean, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		// Clean up multiple spaces
		$price_clean = preg_replace( '/\s+/', ' ', $price_clean );
		$price_clean = trim( $price_clean );

		return array(
			'id'    => $product->get_id(),
			'name'  => $product->get_name(),
			'price' => $price_clean,
			'url'   => $product->get_permalink(),
			'image' => get_the_post_thumbnail_url( $product->get_id(), 'medium' ) ?: '',
		);
	}
}

