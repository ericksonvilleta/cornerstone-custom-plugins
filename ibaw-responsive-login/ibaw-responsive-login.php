<?php
/**
 * Plugin Name: IBAW- Mobile Responsive Login
 * Plugin URI:  https://ericksonvilleta.com
 * Description: Displays a responsive Login/Signup link that is white by default and switches to green on hover.
 * Version:     1.0
 * Author:      Erick Villeta
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Generate the Responsive Auth Markup
 */
function ibaw_responsive_login_markup() {
    $login_url  = wp_login_url();
    $signup_url = wp_registration_url();
    
    ob_start(); ?>
    
    <div class="ibaw-auth-container">
        <?php if ( is_user_logged_in() ) : ?>
            <a href="<?php echo wp_logout_url(); ?>" class="ibaw-auth-link logout" title="Logout">
                <i class="fas fa-sign-out-alt"></i>
                <span class="ibaw-text">Logout</span>
            </a>
        <?php else : ?>
            <a href="<?php echo $login_url; ?>" class="ibaw-auth-link login" title="Login / Sign up">
                <i class="fas fa-user-circle"></i>
                <span class="ibaw-text">Login / Sign up</span>
            </a>
        <?php endif; ?>
    </div>

    <style>
        /* Base Container */
        .ibaw-auth-container {
            display: inline-flex;
            align-items: center;
            font-family: inherit;
        }

        /* Link Styling */
        .ibaw-auth-link {
            text-decoration: none;
            color: #ffffff; /* White text by default */
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        /* Icon setup */
        .ibaw-auth-link i {
            display: none; /* Hidden on large desktop text view */
            font-size: 1.25rem;
            color: #ffffff; /* White icon by default */
            transition: all 0.3s ease;
        }

        /* Hover State - Green transition */
        .ibaw-auth-link:hover {
            color: #75b034;
        }
        
        .ibaw-auth-link:hover i {
            color: #75b034;
            background-color: rgba(255, 255, 255, 0.1); /* Subtle white glow on green hover */
        }

        /* Mobile Viewport Breakpoint */
        @media (max-width: 768px) {
            .ibaw-auth-link .ibaw-text {
                display: none; /* Hide text on mobile */
            }
            
            .ibaw-auth-link i {
                display: flex; 
                align-items: center;
                justify-content: center;
                width: 42px;
                height: 42px;
                border: 1px solid rgba(255, 255, 255, 0.3); /* Soft white border for visibility */
                border-radius: 50%;
            }
        }
    </style>
    
    <?php
    return ob_get_clean();
}
add_shortcode( 'ibaw_login_icon', 'ibaw_responsive_login_markup' );

/**
 * Enqueue Font Awesome 6
 */
add_action( 'wp_enqueue_scripts', function() {
    wp_enqueue_style( 'font-awesome-6', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css' );
});