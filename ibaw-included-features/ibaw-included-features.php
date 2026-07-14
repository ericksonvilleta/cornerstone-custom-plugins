<?php
/**
 * Plugin Name: IBAW-Included Features
 * Description: Features grid widget based on image_fba5a1.png.
 * Plugin URI: https://ericksonvilleta.com
 * Author: Erick Villeta
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function register_ibaw_included_features_widget( $widgets_manager ) {
    require_once( __DIR__ . '/widgets/included-features-widget.php' );
    $widgets_manager->register( new \IBAW_Included_Features_Widget() );
}
add_action( 'elementor/widgets/register', 'register_ibaw_included_features_widget' );