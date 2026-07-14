<?php
/**
 * Plugin Name: IBAW- Prevent AVIF Uploads
 * Plugin URI: https://ericksonvilleta.com
 * Description: Prevents the uploading of .avif image files to the WordPress Media Library.
 * Version: 1.0.0
 * Author: Erick Villeta
 * Author URI: https://ericksonvilleta.com
 * License: GPL v2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Remove AVIF from the allowed upload mime types.
 *
 * @param array $mimes Array of allowed mime types keyed by their file extension regex.
 * @return array Modified array of allowed mime types.
 */
function ibaw_disable_avif_uploads( $mimes ) {
    // Check if avif is in the array and remove it
    if ( isset( $mimes['avif'] ) ) {
        unset( $mimes['avif'] );
    }
    
    return $mimes;
}

add_filter( 'upload_mimes', 'ibaw_disable_avif_uploads', 99, 1 );