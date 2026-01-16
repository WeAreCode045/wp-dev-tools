<?php
/**
 * Functions for hiding admin notifications.
 *
 * @package WP_Dev_Tools
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Hide admin notifications if the setting is enabled.
 */
function wp_dev_tools_hide_admin_notifications() {
    if ( ! get_option( 'wp_dev_tools_hide_notifications' ) ) {
        return;
    }

    global $wp_filter;

    $notification_hooks = [
        'admin_notices',
        'all_admin_notices',
        'user_admin_notices',
        'network_admin_notices',
    ];

    $removed_notices = [];

    foreach ( $notification_hooks as $hook ) {
        if ( isset( $wp_filter[ $hook ] ) ) {
            $removed_notices[ $hook ] = $wp_filter[ $hook ];
            unset( $wp_filter[ $hook ] );
        }
    }

    set_transient( 'wp_dev_tools_removed_notices', $removed_notices, DAY_IN_SECONDS );
}
add_action( 'admin_init', 'wp_dev_tools_hide_admin_notifications', 9999 );

/**
 * Restore a hidden notification.
 */
function wp_dev_tools_restore_notification_action() {
    if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'wp_dev_tools_restore_notification' ) ) {
        wp_die( 'Invalid nonce.' );
    }

    $hook     = isset( $_GET['hook'] ) ? sanitize_key( $_GET['hook'] ) : '';
    $priority = isset( $_GET['priority'] ) ? (int) $_GET['priority'] : 10;
    $callback_key = isset( $_GET['callback'] ) ? sanitize_text_field( wp_unslash( $_GET['callback'] ) ) : '';


    if ( empty( $hook ) || empty( $callback_key ) ) {
        wp_die( 'Missing parameters.' );
    }

    $removed_notices = get_transient( 'wp_dev_tools_removed_notices' );

    if ( isset( $removed_notices[ $hook ]->callbacks[ $priority ][ $callback_key ] ) ) {
        global $wp_filter;

        if ( ! isset( $wp_filter[ $hook ] ) ) {
            $wp_filter[ $hook ] = new WP_Hook();
        }

        $wp_filter[ $hook ]->add_filter( $hook, $removed_notices[ $hook ]->callbacks[ $priority ][ $callback_key ]['function'], $priority, $removed_notices[ $hook ]->callbacks[ $priority ][ $callback_key ]['accepted_args'] );
        unset( $removed_notices[ $hook ]->callbacks[ $priority ][ $callback_key ] );

        // If the priority level is empty, remove it.
        if ( empty( $removed_notices[ $hook ]->callbacks[ $priority ] ) ) {
            unset( $removed_notices[ $hook ]->callbacks[ $priority ] );
        }

        // If the hook is empty, remove it.
        if ( empty( $removed_notices[ $hook ]->callbacks ) ) {
            unset( $removed_notices[ $hook ] );
        }

        set_transient( 'wp_dev_tools_removed_notices', $removed_notices, DAY_IN_SECONDS );
    }

    wp_safe_redirect( admin_url( 'options-general.php?page=wp-dev-tools&tab=hidden_notifications' ) );
    exit;
}
add_action( 'admin_post_wp_dev_tools_restore_notification', 'wp_dev_tools_restore_notification_action' );
