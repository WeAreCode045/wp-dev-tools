<?php
/**
 * Plugin Name:       WP Dev Tools
 * Plugin URI:        https://example.com/plugins/the-basics/
 * Description:       A collection of useful tools for WordPress development and management.
 * Version:           1.0.1
 * Author:            Your Name
 * Author URI:        https://author.example.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-dev-tools
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

define( 'WP_DEV_TOOLS_VERSION', '1.0.0' );

// Include the lifecycle functions.
require_once plugin_dir_path( __FILE__ ) . 'includes/lifecycle.php';
register_activation_hook( __FILE__, 'wp_dev_tools_activate' );
register_deactivation_hook( __FILE__, 'wp_dev_tools_deactivate' );


// Include the admin settings page.
require_once plugin_dir_path( __FILE__ ) . 'admin/settings.php';

// Include the notifications functionality.
require_once plugin_dir_path( __FILE__ ) . 'includes/notifications.php';

// Include the remote library functionality.
require_once plugin_dir_path( __FILE__ ) . 'includes/remote-library.php';

// Include the assets enqueuing functionality.
require_once plugin_dir_path( __FILE__ ) . 'includes/assets.php';