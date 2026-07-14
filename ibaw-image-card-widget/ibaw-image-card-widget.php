<?php
/**
 * Plugin Name: IBAW - Elementor Image Card with Button
 * Description: A production-ready, highly responsive image card widget with an overlapping foreground image and background overlay matching image_c8fefa.png.
 * Version:     1.2.0
 * Author:      Erick Villeta
 * Plugin URI:  https://ericksonvilleta.com
 * Text Domain: ibaw-image-card
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Register the widget with Elementor.
 */
function ibaw_register_image_card_widget( $widgets_manager ) {
	require_with_clean_context( plugin_dir_path( __FILE__ ) . 'widgets/class-ibaw-image-card-widget.php' );
	$widgets_manager->register( new \IBAW_Image_Card_Widget() );
}
add_action( 'elementor/widgets/register', 'ibaw_register_image_card_widget' );

/**
 * Helper function to require files with an isolated scope.
 */
function require_with_clean_context( $file ) {
	require_once $file;
}