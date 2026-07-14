<?php
/**
 * Plugin Name: IBAW- Subtitle Manager
 * Plugin URI:  https://ericksonvilleta.com
 * Description: Automatically displays subtitles under post titles. Works well with Elementor blogs.
 * Version:     1.0
 * Author:      Erick Villeta
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * 1. THE ADMIN INPUT (The "Where you type it" part)
 */
add_action( 'add_meta_boxes', function() {
    $screens = array( 'post', 'page' );
    foreach ( $screens as $screen ) {
        add_meta_box(
            'ibaw_subtitle_box',
            'Subtitle / Sub-heading',
            function( $post ) {
                wp_nonce_field( 'ibaw_save', 'ibaw_nonce' );
                $val = get_post_meta( $post->ID, '_ibaw_subtitle', true );
                echo '<input type="text" name="ibaw_subtitle_field" value="'.esc_attr($val).'" style="width:100%; height: 45px; font-size: 1.3em; border: 2px solid #2271b1;" placeholder="Enter subtitle here...">';
            },
            $screen,
            'advanced', // Highly visible position
            'high'
        );
    }
});

/**
 * 2. SAVING DATA
 */
add_action( 'save_post', function( $post_id ) {
    if ( ! isset($_POST['ibaw_nonce']) || ! wp_verify_nonce($_POST['ibaw_nonce'], 'ibaw_save') ) return;
    if ( isset($_POST['ibaw_subtitle_field']) ) {
        update_post_meta( $post_id, '_ibaw_subtitle', sanitize_text_field($_POST['ibaw_subtitle_field']) );
    }
});

/**
 * 3. THE AUTOMATIC INJECTION (The "No Shortcode" magic)
 * This hooks into the title output and forces the subtitle underneath it.
 */
add_filter( 'the_title', function( $title, $id = null ) {
    // Prevent subtitle in menus or admin dashboard
    if ( is_admin() || ! $id || ! in_the_loop() || get_post_type($id) === 'nav_menu_item' ) {
        return $title;
    }

    $subtitle = get_post_meta( $id, '_ibaw_subtitle', true );
    
    if ( ! empty( $subtitle ) ) {
        // Append the subtitle with CSS that pushes it to a new line
        $subtitle_html = '<div class="ibaw-subtitle" style="display: block; font-size: 0.6em; color: #666; font-weight: normal; margin-top: 5px; line-height: 1.2;">' . esc_html( $subtitle ) . '</div>';
        return $title . $subtitle_html;
    }

    return $title;
}, 999, 2 ); // Priority 999 to beat Elementor/Theme overrides