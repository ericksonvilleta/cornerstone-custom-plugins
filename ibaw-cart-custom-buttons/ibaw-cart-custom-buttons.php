<?php
/**
 * Plugin Name: IBAW-Cart Custom Buttons
 * Plugin URI: https://ericksonvilleta.com
 * Description: Version - Adds "Go to Home" and "Continue Shopping" buttons and places them inline next to each other.
 * Version: 1.0
 * Author: Erick Villeta
 */

// Exit if accessed directly for security
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ==========================================================================
   1. CLEANUP (Remove Old Buttons)
   ========================================================================== */
// Removes the default Continue Shopping button if it was hooked below the table
remove_action( 'woocommerce_after_cart_table', 'add_continue_shopping_button' );

/* ==========================================================================
   2. CUSTOM BUTTONS (Inline, Same Size)
   ========================================================================== */
add_action( 'woocommerce_cart_actions', 'erick_add_inline_cart_buttons' );

function erick_add_inline_cart_buttons() {
    // Get URLs
    $shop_page_url = get_permalink( woocommerce_get_page_id( 'shop' ) );
    $home_url = home_url();
    
    // Shared CSS to guarantee they are the EXACT same size and style
    // Added 'display: inline-block' and 'margin-right: 15px' to keep them spaced nicely
    $shared_btn_style = "background: linear-gradient(90deg, #5DA92F, #77B233); color: #ffffff !important; font-family: 'Quicksand', sans-serif !important; font-size: 13px !important; font-weight: 800 !important; border: 2px solid #d3ced2 !important; border-radius: 50px !important; padding: 12px 30px !important; text-transform: uppercase !important; letter-spacing: 1px !important; text-decoration: none !important; display: inline-block; margin-right: 15px; text-align: center;";
    
    // Output both buttons safely, right next to each other
    echo '<a class="button go-to-home" href="' . esc_url( $home_url ) . '" style="' . $shared_btn_style . '">GO TO HOME</a>';
    echo '<a class="button continue-shopping" href="' . esc_url( $shop_page_url ) . '" style="' . $shared_btn_style . '">CONTINUE SHOPPING</a>';
}