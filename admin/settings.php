<?php
/**
 * Admin settings for WP Dev Tools.
 *
 * @package WP_Dev_Tools
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Register the settings page.
 */
function wp_dev_tools_add_settings_page() {
    add_options_page(
        'WP Dev Tools Settings',
        'WP Dev Tools',
        'manage_options',
        'wp-dev-tools',
        'wp_dev_tools_render_settings_page'
    );
}
add_action( 'admin_menu', 'wp_dev_tools_add_settings_page' );

/**
 * Render the settings page.
 */
function wp_dev_tools_render_settings_page() {
    $active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <h2 class="nav-tab-wrapper">
            <a href="?page=wp-dev-tools&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">General</a>
            <a href="?page=wp-dev-tools&tab=hidden_notifications" class="nav-tab <?php echo $active_tab === 'hidden_notifications' ? 'nav-tab-active' : ''; ?>">Hidden Notifications</a>
        </h2>
        <form action="options.php" method="post">
            <?php
            if ( $active_tab === 'general' ) {
                settings_fields( 'wp_dev_tools' );
                do_settings_sections( 'wp_dev_tools' );
            } elseif ( $active_tab === 'hidden_notifications' ) {
                do_settings_sections( 'wp_dev_tools_hidden_notifications' );
            }
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

/**
 * Register settings, sections, and fields.
 */
function wp_dev_tools_register_settings() {
    register_setting( 'wp_dev_tools', 'wp_dev_tools_hide_notifications' );
    register_setting( 'wp_dev_tools', 'wp_dev_tools_remote_library_url' );

    add_settings_section(
        'wp_dev_tools_notifications_section',
        'Admin Notifications',
        'wp_dev_tools_notifications_section_callback',
        'wp_dev_tools'
    );

    add_settings_field(
        'wp_dev_tools_hide_notifications',
        'Hide All Notifications',
        'wp_dev_tools_hide_notifications_callback',
        'wp_dev_tools',
        'wp_dev_tools_notifications_section'
    );

    add_settings_section(
        'wp_dev_tools_remote_library_section',
        'Remote Library',
        'wp_dev_tools_remote_library_section_callback',
        'wp_dev_tools'
    );

    add_settings_field(
        'wp_dev_tools_remote_library_url',
        'Remote Library URL',
        'wp_dev_tools_remote_library_url_callback',
        'wp_dev_tools',
        'wp_dev_tools_remote_library_section'
    );

    add_settings_section(
        'wp_dev_tools_hidden_notifications_section',
        'Hidden Notifications',
        'wp_dev_tools_hidden_notifications_section_callback',
        'wp_dev_tools_hidden_notifications'
    );
}
add_action( 'admin_init', 'wp_dev_tools_register_settings' );

/**
 * Section callbacks
 */
function wp_dev_tools_notifications_section_callback() {
    if ( 'general' !== ( $_GET['tab'] ?? 'general' ) ) {
        return;
    }
    echo '<p>Settings for managing admin notifications.</p>';
}

function wp_dev_tools_remote_library_section_callback() {
    if ( 'general' !== ( $_GET['tab'] ?? 'general' ) ) {
        return;
    }
    echo '<p>Settings for the remote library feature.</p>';
}

function wp_dev_tools_hidden_notifications_section_callback() {
    $removed_notices = get_transient( 'wp_dev_tools_removed_notices' );

    if ( empty( $removed_notices ) ) {
        echo '<p>No hidden notifications.</p>';
        return;
    }

    echo '<table class="wp-list-table widefat striped">';
    echo '<thead><tr><th>Hook</th><th>Callback</th><th>Plugin</th><th>Action</th></tr></thead>';
    echo '<tbody>';

    foreach ( $removed_notices as $hook => $callbacks ) {
        foreach ( $callbacks->callbacks as $priority => $priority_callbacks ) {
            foreach ( $priority_callbacks as $key => $callback ) {
                $function = $callback['function'];
                $plugin = 'Unknown';

                if ( is_array( $function ) && is_object( $function[0] ) ) {
                    $reflector = new ReflectionClass( get_class( $function[0] ) );
                    $plugin = basename( dirname( $reflector->getFileName(), 2 ) );
                } elseif ( is_string( $function ) && function_exists( $function ) ) {
                    $reflector = new ReflectionFunction( $function );
                    $plugin = basename( dirname( $reflector->getFileName(), 2 ) );
                }

                $restore_url = wp_nonce_url(
                    add_query_arg(
                        [
                            'action'   => 'wp_dev_tools_restore_notification',
                            'hook'     => $hook,
                            'priority' => $priority,
                            'callback' => $key,
                        ],
                        admin_url( 'admin-post.php' )
                    ),
                    'wp_dev_tools_restore_notification'
                );

                echo '<tr>';
                echo '<td>' . esc_html( $hook ) . '</td>';
                echo '<td>' . ( is_array( $function ) ? esc_html( get_class( $function[0] ) . '::' . $function[1] ) : esc_html( $function ) ) . '</td>';
                echo '<td>' . esc_html( $plugin ) . '</td>';
                echo '<td><a href="' . esc_url( $restore_url ) . '" class="button">Restore</a></td>';
                echo '</tr>';
            }
        }
    }

    echo '</tbody>';
    echo '</table>';
}

/**
 * Field callbacks
 */
function wp_dev_tools_hide_notifications_callback() {
    $option = get_option( 'wp_dev_tools_hide_notifications' );
    ?>
    <input type="checkbox" id="wp_dev_tools_hide_notifications" name="wp_dev_tools_hide_notifications" value="1" <?php checked( 1, $option, true ); ?> />
    <label for="wp_dev_tools_hide_notifications">Hide all admin notifications.</label>
    <?php
}

function wp_dev_tools_remote_library_url_callback() {
    $option = get_option( 'wp_dev_tools_remote_library_url' );
    ?>
    <input type="text" id="wp_dev_tools_remote_library_url" name="wp_dev_tools_remote_library_url" value="<?php echo esc_attr( $option ); ?>" class="regular-text" />
    <p class="description">Enter the base URL for the remote library (e.g., https://remoteurl.com).</p>
    <?php
}
