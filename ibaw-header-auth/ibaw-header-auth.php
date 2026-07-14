<?php
/**
 * Plugin Name: IBAW-Dynamic Header Auth Link
 * Plugin URI: https://ericksonvilleta.com
 * Description: Creates a dynamic shortcode for the header that switches between "Login / Sign up" and "Log out" with Quicksand font styling, custom icons, and synced hover effects.
 * Version: 1.0
 * Author: Erick Villeta
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// 1. Enqueue Google Font 'Quicksand' to ensure it loads for all visitors
add_action( 'wp_enqueue_scripts', 'ibaw_enqueue_quicksand_font' );
function ibaw_enqueue_quicksand_font() {
    wp_enqueue_style( 'ibaw-quicksand-font', 'https://fonts.googleapis.com/css2?family=Quicksand:wght@500&display=swap', false );
}

// 2. Inject Custom CSS for the link styling and hover effect
add_action( 'wp_head', 'ibaw_header_auth_custom_css' );
function ibaw_header_auth_custom_css() {
    ?>
    <style>
        .ibaw-header-auth-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: 'Quicksand', sans-serif;
            font-size: 16px;
            font-weight: 500;
            font-style: normal;
            color: rgb(133, 137, 151) !important; 
            text-decoration: none !important;
            transition: color 0.3s ease !important; 
        }
        
        .ibaw-header-auth-link svg {
            transition: stroke 0.3s ease !important;
        }

        .ibaw-header-auth-link:hover {
            color: #75b034 !important;
        }
    </style>
    <?php
}

// 3. Create the Shortcode
add_shortcode( 'ibaw_header_auth', 'ibaw_dynamic_header_auth_link' );

function ibaw_dynamic_header_auth_link() {
    // We now direct the user directly to the custom login page instead of the default my-account page.
    // If you named your page something other than 'login', change the slug inside home_url() below.
    if ( class_exists( 'WooCommerce' ) ) {
        $login_url = home_url( '/login/' ); 
    } else {
        $login_url = wp_login_url();
    }

    $logout_url = wp_logout_url( home_url() ); 

    $login_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>';

    $logout_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>';

    if ( is_user_logged_in() ) {
        return '<a href="' . esc_url( $logout_url ) . '" class="ibaw-header-auth-link">' . $logout_icon . '<span>Log out</span></a>';
    } else {
        return '<a href="' . esc_url( $login_url ) . '" class="ibaw-header-auth-link">' . $login_icon . '<span>Login / Sign up</span></a>';
    }
}