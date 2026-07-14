<?php
/**
 * Plugin Name: Cornerstone Shed Pad Animator
 * Description: A looping overlay animation for the Shed Pad banner.
 * Version: 1.0
 * Author: Erick Villeta
 * Plugin URI: https://ericksonvilleta.com
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ==========================================================================
   1. SHORTCODE: [shed_promo_banner]
   ========================================================================== */
add_shortcode('shed_promo_banner', function() {
    // Replace these with your actual Media Library URLs
    $bg_url = "https://cornerstonelandscapesupply.com/wp-content/uploads/2026/04/dont_forget_your_shed_pad_wo_shed-2.webp"; 
    $shed_url = "https://cornerstonelandscapesupply.com/wp-content/uploads/2026/04/dont_forget_your_shed_pad_shed-2.webp";

    ob_start(); ?>
    
    <style>
        .scg-promo-wrapper {
            position: relative;
            width: 100%;
            max-width: 1100px;
            margin: 20px auto;
            overflow: hidden;
            border-radius: 12px;
            line-height: 0;
        }

        /* The base image (without shed) */
        .scg-promo-bg { 
            width: 100%; 
            display: block; 
            height: auto;
        }

        /* The overlay image (the shed) */
        .scg-promo-shed {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: contain;
            pointer-events: none;
            z-index: 2;
            opacity: 0;
            /* Animation: 5s total (1s fade in, 2s stay, 1s fade out) */
            animation: shedFadeLoop 5s ease-in-out infinite;
        }

        @keyframes shedFadeLoop {
            0%, 100% { opacity: 0; }        /* Start hidden */
            14% { opacity: 1; }             /* Fade in over 1 second (1/7th of 7s) */
            86% { opacity: 1; }             /* Stay visible for 5 seconds */
        }

        /* Ensure the 'Get a Quote' button stays clickable above the overlay */
        .scg-promo-overlay {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            z-index: 3;
            pointer-events: none;
        }

        .scg-promo-btn-link {
            position: absolute;
            bottom: 10%; /* Adjust based on your image layout */
            left: 50%;
            transform: translateX(-50%);
            pointer-events: auto;
            /* Making the actual link area transparent since the button is in the BG image */
            width: 200px;
            height: 50px;
        }
    </style>

    <div class="scg-promo-wrapper">
        <img src="<?php echo esc_url($bg_url); ?>" class="scg-promo-bg" alt="Shed Pad Background">

        <img src="<?php echo esc_url($shed_url); ?>" class="scg-promo-shed" alt="Shed Overlay">

        <div class="scg-promo-overlay">
            <a href="/get-a-quote" class="scg-promo-btn-link"></a>
        </div>
    </div>

    <?php
    return ob_get_clean();
});