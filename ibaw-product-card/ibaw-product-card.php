<?php
/**
 * Plugin Name: IBAW-Product Card
 * Description: Product display widget inspired by image_fdec58.png.
 * Plugin URI: https://ericksonvilleta.com
 * Author: Erick Villeta
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function register_ibaw_product_card_widget( $widgets_manager ) {
    require_once( __DIR__ . '/widgets/product-card-widget.php' );
    $widgets_manager->register( new \IBAW_Product_Card_Widget() );
}
add_action( 'elementor/widgets/register', 'register_ibaw_product_card_widget' );