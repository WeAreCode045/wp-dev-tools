<?php
/**
 * Plugin lifecycle functions.
 *
 * @package WP_Dev_Tools
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Fired upon plugin activation.
 */
function wp_dev_tools_activate() {
    // Add default options.
    add_option( 'wp_dev_tools_hide_notifications', 0 );
    add_option( 'wp_dev_tools_remote_library_url', '' );
}

/**
 * Fired upon plugin deactivation.
 */
function wp_dev_tools_deactivate() {
    // Clean up options.
    delete_option( 'wp_dev_tools_hide_notifications' );
    delete_option( 'wp_dev_tools_remote_library_url' );
}
