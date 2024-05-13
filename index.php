<?php
/**
 * Plugin Name: Sort Product by Discount - by Mithu
 * Plugin URI:
 * Description: A plugins sort products by discount
 * Version: 1.0
 * Author: Mithu A Quayium
 * Author URI:
 * Text Domain: vpl
 * Domain Path: /i18n/languages/
 * Requires at least: 6.3
 * Requires PHP: 5.4
 */
//This is hot deals , sort by discount
function custom_woocommerce_catalog_orderby( $sortby ) {
	$sortby['nd-discount'] = __( 'Sort by Discount', 'woocommerce' );
	return $sortby;
}
add_filter( 'woocommerce_catalog_orderby', 'custom_woocommerce_catalog_orderby', 20 );

// Add filter to modify the SQL query
add_filter('posts_clauses', 'nd_handle_discount_percentage_posts_clauses', 10, 2);
function nd_handle_discount_percentage_posts_clauses( $clauses, $query ) {
	if ( ! is_main_query() || ! isset( $_GET['orderby'] ) || $_GET['orderby'] != 'nd-discount' || $query->get('post_type') != 'product' ) return $clauses;
	global $wpdb;
	$clauses['join'] .= "
            INNER JOIN {$wpdb->postmeta} pm ON $wpdb->posts.ID = pm.post_id AND pm.meta_key = '_regular_price'
    INNER JOIN {$wpdb->postmeta} discount ON $wpdb->posts.ID = discount.post_id AND discount.meta_key = '_sale_price'
        ";
	$clauses['where'] .= $wpdb->prepare(" AND $wpdb->posts.post_type = 'product'
    AND $wpdb->posts.post_status = 'publish'
    AND pm.meta_value != ''
    AND discount.meta_value != ''
    AND ( ( CAST(pm.meta_value AS DECIMAL) - CAST(discount.meta_value AS DECIMAL) ) / CAST(pm.meta_value AS DECIMAL)) >= ( %d / %d )
    AND ( ( CAST(pm.meta_value AS DECIMAL) - CAST(discount.meta_value AS DECIMAL) ) / CAST(pm.meta_value AS DECIMAL)) <= ( %d / %d )"
		,
		1,
		100,
		100,
		100
	);

	// Add the ORDER BY clause
	$clauses['orderby'] = "( ( CAST(pm.meta_value AS DECIMAL) - CAST(discount.meta_value AS DECIMAL) ) / CAST(pm.meta_value AS DECIMAL)) DESC";
	remove_filter('posts_clauses', 'flatsome_handle_discount_percentage_posts_clauses', 10);
	return $clauses;
}
