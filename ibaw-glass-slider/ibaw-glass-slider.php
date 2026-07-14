<?php
/**
 * Plugin Name: IBAW-Glass Slider
 * Description: Custom Elementor Widget for a glassmorphism card slider.
 * Plugin URI: https://ericksonvilleta.com
 * Version: 1.0.2
 * Author: Erick Villeta
 * Text Domain: ibaw-glass-slider
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ibaw_glass_slider_register_widget( $widgets_manager ) {
	require_once( __DIR__ . '/widgets/glass-slider-widget.php' );
	$widgets_manager->register( new \IBAW_Glass_Slider_Widget() );
}
add_action( 'elementor/widgets/register', 'ibaw_glass_slider_register_widget' );

function ibaw_glass_slider_enqueue_assets() {
	wp_register_style( 'ibaw-glass-slider-style', plugins_url( 'assets/css/style.css', __FILE__ ), [], '1.0.2' );
	wp_register_script( 'ibaw-glass-slider-script', plugins_url( 'assets/js/script.js', __FILE__ ), [], '1.0.2', true );
}
add_action( 'elementor/frontend/after_register_styles', 'ibaw_glass_slider_enqueue_assets' );
add_action( 'elementor/frontend/after_register_scripts', 'ibaw_glass_slider_enqueue_assets' );