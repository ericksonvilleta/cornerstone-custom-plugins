<?php
/**
 * Plugin Name: IBAW-WooCommerce Auth Customizer
 * Plugin URI: https://ericksonvilleta.com
 * Description: Combines custom WooCommerce login and registration shortcodes, redirects logged-in users, and styles the standalone forms.
 * Version: 1.0
 * Author: Erick Villeta
 * Compatible: WooCommerce 9
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/* ==========================================================================
   1. REGISTRATION FORM SHORTCODE: [wc_reg_form_ibaw]
   ========================================================================== */
add_shortcode( 'wc_reg_form_ibaw', 'ibaw_separate_registration_form' );

function ibaw_separate_registration_form() {
   if ( is_user_logged_in() ) {
       return '<p>You are already registered</p>';
   }
   
   ob_start();
   do_action( 'woocommerce_before_customer_login_form' );
   $html = wc_get_template_html( 'myaccount/form-login.php' );
   
   $dom = new DOMDocument();
   $dom->encoding = 'utf-8';
   @$dom->loadHTML( utf8_decode( $html ) ); 
   
   $xpath = new DOMXPath( $dom );
   $form = $xpath->query( '//form[contains(@class,"register")]' );
   
   if ( $form->length > 0 ) {
       $form = $form->item( 0 );
       // Wrap the output so CSS only targets this specific form
       echo '<div class="ibaw-custom-auth-wrap">';
       echo $dom->saveHTML( $form );
       echo '</div>';
   } else {
       echo '<p>Registration form not found. Please ensure WooCommerce registration is enabled in your settings.</p>';
   }
   
   return ob_get_clean();
}

/* ==========================================================================
   2. LOGIN FORM SHORTCODE: [wc_login_form_ibaw]
   ========================================================================== */
add_shortcode( 'wc_login_form_ibaw', 'ibaw_separate_login_form' );

function ibaw_separate_login_form() {
   if ( is_user_logged_in() ) {
       return '<p>You are already logged in</p>'; 
   }
   
   ob_start();
   // Wrap the output so CSS only targets this specific form
   echo '<div class="ibaw-custom-auth-wrap">';
   do_action( 'woocommerce_before_customer_login_form' );
   woocommerce_login_form( array( 'redirect' => wc_get_page_permalink( 'myaccount' ) ) );
   echo '</div>';
   return ob_get_clean();
}

/* ==========================================================================
   3. REDIRECT LOGGED-IN USERS
   ========================================================================== */
add_action( 'template_redirect', 'ibaw_redirect_login_registration_if_logged_in' );

function ibaw_redirect_login_registration_if_logged_in() {
    if ( is_page() && is_user_logged_in() && ( has_shortcode( get_the_content(), 'wc_login_form_ibaw' ) || has_shortcode( get_the_content(), 'wc_reg_form_ibaw' ) ) ) {
        wp_safe_redirect( wc_get_page_permalink( 'myaccount' ) );
        exit;
    }
}

/* ==========================================================================
   4. INJECT CUSTOM CSS FOR STANDALONE FORMS ONLY
   ========================================================================== */
add_action( 'wp_head', 'ibaw_custom_auth_styles' );

function ibaw_custom_auth_styles() {
    ?>
    <style>
        /* Container styling for ONLY our custom shortcode forms */
        .ibaw-custom-auth-wrap form.login, 
        .ibaw-custom-auth-wrap form.register {
            border: 1px solid #e2e2e2 !important;
            padding: 30px !important;
            border-radius: 8px !important;
            background-color: #ffffff !important;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05) !important;
            max-width: 500px !important; 
            margin: 0 auto !important; 
        }

        .ibaw-custom-auth-wrap form.login .form-row, 
        .ibaw-custom-auth-wrap form.register .form-row {
            margin-bottom: 20px !important;
        }

        .ibaw-custom-auth-wrap form.login .woocommerce-Button, 
        .ibaw-custom-auth-wrap form.register .woocommerce-Button {
            background-color: #ff5722 !important; 
            color: #ffffff !important;
            border: none !important;
            padding: 12px 24px !important;
            border-radius: 4px !important;
            width: 100% !important; 
            font-size: 1.1em !important;
            transition: background-color 0.3s ease !important;
        }

        .ibaw-custom-auth-wrap form.login .woocommerce-Button:hover, 
        .ibaw-custom-auth-wrap form.register .woocommerce-Button:hover {
            background-color: #e64a19 !important; 
        }
    </style>
    <?php
}