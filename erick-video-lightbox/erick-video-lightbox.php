<?php
/**
 * Plugin Name: IBAW-Erick YouTube Lightbox Widget
 * Description: Custom Elementor Button for YouTube Lightbox with Icon & Hover Animations.
 * Version: 1.0
 * Author: Erick Villeta
 * Plugin URI: https://ericksonvilleta.com
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function register_erick_video_widget( $widgets_manager ) {
    require_once( __DIR__ . '/widget-logic.php' );
    $widgets_manager->register( new \Erick_Video_Lightbox_Widget() );
}
add_action( 'elementor/widgets/register', 'register_erick_video_widget' );

add_action( 'elementor/frontend/after_enqueue_scripts', function() {
    wp_enqueue_script( 'elementor-frontend' );
});