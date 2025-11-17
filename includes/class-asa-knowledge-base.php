<?php
/**
 * Knowledge base management class
 *
 * @package AI_Store_Assistant
 * @subpackage Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages the knowledge base custom post type.
 */
class ASA_Knowledge_Base {

	/**
	 * Post type name.
	 *
	 * @var string
	 */
	private $post_type = 'asa_knowledge';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
	}

	/**
	 * Register the custom post type.
	 */
	public function register_post_type() {
		$labels = array(
			'name'               => _x( 'Chatbot Knowledge', 'Post Type General Name', 'ai-store-assistant' ),
			'singular_name'      => _x( 'Knowledge Item', 'Post Type Singular Name', 'ai-store-assistant' ),
			'menu_name'          => __( 'Chatbot Knowledge', 'ai-store-assistant' ),
			'name_admin_bar'     => __( 'Knowledge Item', 'ai-store-assistant' ),
			'archives'           => __( 'Knowledge Archives', 'ai-store-assistant' ),
			'attributes'         => __( 'Knowledge Attributes', 'ai-store-assistant' ),
			'parent_item_colon'  => __( 'Parent Knowledge:', 'ai-store-assistant' ),
			'all_items'          => __( 'All Knowledge Items', 'ai-store-assistant' ),
			'add_new_item'       => __( 'Add New Knowledge Item', 'ai-store-assistant' ),
			'add_new'            => __( 'Add New', 'ai-store-assistant' ),
			'new_item'           => __( 'New Knowledge Item', 'ai-store-assistant' ),
			'edit_item'          => __( 'Edit Knowledge Item', 'ai-store-assistant' ),
			'update_item'        => __( 'Update Knowledge Item', 'ai-store-assistant' ),
			'view_item'          => __( 'View Knowledge Item', 'ai-store-assistant' ),
			'view_items'         => __( 'View Knowledge Items', 'ai-store-assistant' ),
			'search_items'       => __( 'Search Knowledge', 'ai-store-assistant' ),
			'not_found'          => __( 'Not found', 'ai-store-assistant' ),
			'not_found_in_trash' => __( 'Not found in Trash', 'ai-store-assistant' ),
		);

		$args = array(
			'label'                 => __( 'Chatbot Knowledge', 'ai-store-assistant' ),
			'description'           => __( 'Knowledge base items for the chatbot', 'ai-store-assistant' ),
			'labels'                => $labels,
			'supports'              => array( 'title', 'editor' ),
			'hierarchical'          => false,
			'public'                => false,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'menu_position'         => 30,
			'menu_icon'             => 'dashicons-book-alt',
			'show_in_admin_bar'     => true,
			'show_in_nav_menus'     => false,
			'can_export'            => true,
			'has_archive'           => false,
			'exclude_from_search'   => true,
			'publicly_queryable'    => false,
			'capability_type'       => 'post',
			'show_in_rest'          => false,
		);

		register_post_type( $this->post_type, $args );
	}

	/**
	 * Get knowledge base content for context.
	 *
	 * @param int $limit Maximum number of items to include.
	 * @return string Concatenated knowledge base content.
	 */
	public function get_knowledge_context( $limit = 20 ) {
		$args = array(
			'post_type'      => $this->post_type,
			'post_status'    => 'publish',
			'posts_per_page' => absint( $limit ),
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		$query = new WP_Query( $args );
		$content = array();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$title = get_the_title();
				$text  = get_the_content();
				$text  = wp_strip_all_tags( $text );
				$text  = trim( $text );

				if ( ! empty( $text ) ) {
					$content[] = sprintf(
						"## %s\n%s",
						$title,
						$text
					);
				}
			}
			wp_reset_postdata();
		}

		return implode( "\n\n", $content );
	}

	/**
	 * Get the post type name.
	 *
	 * @return string Post type name.
	 */
	public function get_post_type() {
		return $this->post_type;
	}
}


