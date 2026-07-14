<?php
/**
 * Plugin Name: IBAW-Allow GLB Uploads
 * Plugin URI: https://ericksonvilleta.com
 * Description: Enables support for .glb (GLTF Binary) file uploads in WordPress.
 * Version: 1.0.0
 * Author: Erick Villeta
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

add_filter( 'upload_mimes', 'allow_custom_mime_types' );
function allow_custom_mime_types( $mimes ) {
    $mimes['glb'] = 'model/gltf-binary';
    return $mimes;
}