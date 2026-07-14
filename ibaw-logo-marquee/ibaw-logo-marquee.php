<?php
/**
 * Plugin Name: IBAW-Logo Marquee
 * Description: A logo marquee addon for Elementor, inspired by image_eba2e7.png.
 * Plugin URI: https://ericksonvilleta.com
 * Author: Erick Villeta
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function register_ibaw_logo_marquee_widget( $widgets_manager ) {
    require_once( __DIR__ . '/widgets/logo-marquee-widget.php' );
    $widgets_manager->register( new \IBAW_Logo_Marquee_Widget() );
}
add_action( 'elementor/widgets/register', 'register_ibaw_logo_marquee_widget' );