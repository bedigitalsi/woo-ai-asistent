<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package AI_Store_Assistant
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete plugin options
delete_option( 'asa_settings' );

// Delete knowledge base posts
$knowledge_posts = get_posts( array(
	'post_type'      => 'asa_knowledge',
	'posts_per_page' => -1,
	'post_status'    => 'any',
) );

foreach ( $knowledge_posts as $post ) {
	wp_delete_post( $post->ID, true );
}

// Flush rewrite rules
flush_rewrite_rules();


