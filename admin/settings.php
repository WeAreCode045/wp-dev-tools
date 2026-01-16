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
	<div class="wrap bg-gray-100 p-6">
		<h1 class="text-2xl font-semibold text-gray-800 mb-4">WP Dev Tools</h1>
		<div class="flex border-b border-gray-300">
			<a href="?page=wp-dev-tools&tab=general" class="px-4 py-2 text-gray-600 border-b-2 <?php echo $active_tab === 'general' ? 'border-blue-500 text-blue-600' : 'border-transparent'; ?>">General</a>
			<a href="?page=wp-dev-tools&tab=hidden_notifications" class="px-4 py-2 text-gray-600 border-b-2 <?php echo $active_tab === 'hidden_notifications' ? 'border-blue-500 text-blue-600' : 'border-transparent'; ?>">Hidden Notifications</a>
		</div>
		<form action="options.php" method="post" class="mt-6">
			<?php
			if ( $active_tab === 'general' ) {
				settings_fields( 'wp_dev_tools' );
				do_settings_sections( 'wp_dev_tools' );
			} elseif ( $active_tab === 'hidden_notifications' ) {
				do_settings_sections( 'wp_dev_tools_hidden_notifications' );
			}
			submit_button( 'Save Settings', 'primary', 'submit', true, [ 'class' => 'bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded' ] );
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
	echo '<div class="bg-white p-6 rounded-lg shadow-md mb-6"><h2 class="text-xl font-semibold mb-2">Admin Notifications</h2><p class="text-gray-600">Settings for managing admin notifications.</p></div>';
}

function wp_dev_tools_remote_library_section_callback() {
	if ( 'general' !== ( $_GET['tab'] ?? 'general' ) ) {
		return;
	}
	echo '<div class="bg-white p-6 rounded-lg shadow-md"><h2 class="text-xl font-semibold mb-2">Remote Library</h2><p class="text-gray-600">Settings for the remote library feature.</p></div>';
}

function wp_dev_tools_hidden_notifications_section_callback() {
	$removed_notices = get_transient( 'wp_dev_tools_removed_notices' );

	if ( empty( $removed_notices ) ) {
		echo '<div class="bg-white p-6 rounded-lg shadow-md"><p class="text-gray-600">No hidden notifications.</p></div>';
		return;
	}

	echo '<div class="bg-white p-6 rounded-lg shadow-md">';
	echo '<table class="min-w-full divide-y divide-gray-200">';
	echo '<thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hook</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Callback</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plugin</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th></tr></thead>';
	echo '<tbody class="bg-white divide-y divide-gray-200">';

	foreach ( $removed_notices as $hook => $callbacks ) {
		foreach ( $callbacks->callbacks as $priority => $priority_callbacks ) {
			foreach ( $priority_callbacks as $key => $callback ) {
				$function = $callback['function'];
				$plugin   = 'Unknown';

				try {
					if ( is_array( $function ) ) {
						if ( is_object( $function[0] ) ) {
							$reflector = new ReflectionClass( $function[0] );
							$plugin    = basename( dirname( $reflector->getFileName(), 2 ) );
						} elseif ( is_string( $function[0] ) ) {
							$reflector = new ReflectionClass( $function[0] );
							$plugin    = basename( dirname( $reflector->getFileName(), 2 ) );
						}
					} elseif ( is_string( $function ) && function_exists( $function ) ) {
						$reflector = new ReflectionFunction( $function );
						$plugin    = basename( dirname( $reflector->getFileName(), 2 ) );
					}
				} catch ( ReflectionException $e ) {
					// Could be a closure or other non-reflectable callback, ignore.
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
				echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . esc_html( $hook ) . '</td>';
				if ( is_array( $function ) ) {
					$class_name = is_object( $function[0] ) ? get_class( $function[0] ) : $function[0];
					echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . esc_html( $class_name . '::' . $function[1] ) . '</td>';
				} else {
					echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . esc_html( $function ) . '</td>';
				}
				echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . esc_html( $plugin ) . '</td>';
				echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-medium"><a href="' . esc_url( $restore_url ) . '" class="text-indigo-600 hover:text-indigo-900">Restore</a></td>';
				echo '</tr>';
			}
		}
	}

	echo '</tbody>';
	echo '</table>';
	echo '</div>';
}

/**
 * Field callbacks
 */
function wp_dev_tools_hide_notifications_callback() {
	$option = get_option( 'wp_dev_tools_hide_notifications' );
	?>
	<div class="flex items-center">
		<input type="checkbox" id="wp_dev_tools_hide_notifications" name="wp_dev_tools_hide_notifications" value="1" <?php checked( 1, $option, true ); ?> class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
		<label for="wp_dev_tools_hide_notifications" class="ml-2 block text-sm text-gray-900">Hide all admin notifications.</label>
	</div>
	<?php
}

function wp_dev_tools_remote_library_url_callback() {
	$option = get_option( 'wp_dev_tools_remote_library_url' );
	?>
	<input type="text" id="wp_dev_tools_remote_library_url" name="wp_dev_tools_remote_library_url" value="<?php echo esc_attr( $option ); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
	<p class="mt-2 text-sm text-gray-500">Enter the base URL for the remote library (e.g., https://remoteurl.com).</p>
	<?php
}
