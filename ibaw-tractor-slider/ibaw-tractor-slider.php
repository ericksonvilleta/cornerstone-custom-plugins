<?php
/**
 * Plugin Name: IBAW - Custom Tractor Metrics Slider for Elementor
 * Description: High-end interactive tractor metrics slider with layout swapping. Installs cleanly without console tools.
 * Version:     1.0.0
 * Author:      Erick Villeta
 * Plugin URI:  https://ericksonvilleta.com
 * Text Domain: ibaw-plugin
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function ibaw_register_vanilla_tractor_slider( $widgets_manager ) {
	require_once __DIR__ . '/widgets/IBAW_Tractor_Slider_Widget.php';
	$widgets_manager->register( new \IBAW\Elementor\Widgets\IBAW_Tractor_Slider_Widget() );
}
add_action( 'elementor/widgets/register', 'ibaw_register_vanilla_tractor_slider' );