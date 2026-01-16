<?php
/**
 * Functions for the Remote Library feature.
 *
 * @package WP_Dev_Tools
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Replace the base URL of images with the remote URL.
 *
 * @param string $content The content to filter.
 * @return string The filtered content.
 */
function wp_dev_tools_replace_image_urls( $content ) {
    $remote_url = get_option( 'wp_dev_tools_remote_library_url' );

    if ( empty( $remote_url ) ) {
        return $content;
    }

    $local_url = get_site_url();
    // Get the upload directory path
    $upload_dir = wp_get_upload_dir();
    $upload_baseurl = $upload_dir['baseurl'];


    $content = str_replace( $upload_baseurl, $remote_url . str_replace( $local_url, '', $upload_baseurl ), $content );

    return $content;
}
add_filter( 'the_content', 'wp_dev_tools_replace_image_urls' );
add_filter( 'wp_get_attachment_url', 'wp_dev_tools_replace_image_urls' );
add_filter( 'wp_get_attachment_image_src', 'wp_dev_tools_replace_image_urls_in_srcset' );


function wp_dev_tools_replace_image_urls_in_srcset( $image ) {
    $remote_url = get_option( 'wp_dev_tools_remote_library_url' );

    if ( empty( $remote_url ) ) {
        return $image;
    }

    $local_url = get_site_url();
    // Get the upload directory path
    $upload_dir = wp_get_upload_dir();
    $upload_baseurl = $upload_dir['baseurl'];

    if ( is_array( $image ) ) {
        foreach ( $image as $key => $value ) {
            if ( is_string( $value ) ) {
                $image[ $key ] = str_replace( $upload_baseurl, $remote_url . str_replace( $local_url, '', $upload_baseurl ), $value );
            }
        }
    }


    return $image;
}
