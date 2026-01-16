<?php
/**
 * Functions for enqueuing assets.
 *
 * @package WP_Dev_Tools
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Enqueue admin styles.
 */
function wp_dev_tools_enqueue_admin_styles( $hook ) {
    if ( 'settings_page_wp-dev-tools' !== $hook ) {
        return;
    }

    wp_enqueue_style(
        'wp-dev-tools-admin-style',
        plugin_dir_url( __FILE__ ) . '../dist/css/admin-style.css',
        [],
        WP_DEV_TOOLS_VERSION
    );
}
add_action( 'admin_enqueue_scripts', 'wp_dev_tools_enqueue_admin_styles' );
