<?php
/**
 * Plugin Name: IBAW-Restrict Shipping States
 * Plugin URI: https://ericksonvilleta.com
 * Description: Restricts WooCommerce shipping states to MD, VA, and WV only.
 * Version: 1.0
 * Author: Erick Villeta
 */

// Exit if accessed directly for security
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Restrict Shipping States to MD, VA, and WV only
 */
add_filter( 'woocommerce_states', 'erick_limit_shipping_states' );

function erick_limit_shipping_states( $states ) {
    $target_states = array( 'MD', 'VA', 'WV' );
    
    // We only want to restrict the United States list
    if ( isset( $states['US'] ) ) {
        foreach ( $states['US'] as $state_code => $state_name ) {
            if ( ! in_array( $state_code, $target_states ) ) {
                unset( $states['US'][$state_code] );
            }
        }
    }

    return $states;
}