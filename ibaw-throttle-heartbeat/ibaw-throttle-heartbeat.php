<?php
/**
 * Plugin Name: IBAW-Throttle Heartbeat API
 * Plugin URI: https://ericksonvilleta.com
 * Description: Version 1.0 - Disables WordPress Heartbeat on the frontend and throttles it to 60 seconds in the backend to save server CPU.
 * Version: 1.0
 * Author: Erick Villeta
 */

// Exit if accessed directly for security
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ==========================================================================
   1. DISABLE HEARTBEAT ON THE FRONTEND
   ========================================================================== */
add_action( 'init', 'erick_disable_frontend_heartbeat', 1 );

function erick_disable_frontend_heartbeat() {
    // If the user is looking at the public-facing website, deregister the script
    if ( ! is_admin() ) {
        wp_deregister_script( 'heartbeat' );
    }
}

/* ==========================================================================
   2. THROTTLE HEARTBEAT IN THE ADMIN DASHBOARD
   ========================================================================== */
add_filter( 'heartbeat_settings', 'erick_throttle_admin_heartbeat' );

function erick_throttle_admin_heartbeat( $settings ) {
    // Force the pulse interval to 60 seconds (reduces server load during edits)
    $settings['interval'] = 60;
    
    return $settings;
}