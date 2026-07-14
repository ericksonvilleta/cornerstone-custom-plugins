<?php
/**
 * Plugin Name: IBAW-Content Block
 * Description: Content block widget inspired by image_c93139.png.
 * Plugin URI: https://ericksonvilleta.com
 * Author: Erick Villeta
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function register_ibaw_content_block_widget( $widgets_manager ) {
    require_once( __DIR__ . '/widgets/content-block-widget.php' );
    $widgets_manager->register( new \IBAW_Content_Block_Widget() );
}
add_action( 'elementor/widgets/register', 'register_ibaw_content_block_widget' );