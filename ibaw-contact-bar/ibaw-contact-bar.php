<?php
/**
 * Plugin Name: IBAW- Contact Info Bar
 * Plugin URI:  https://ericksonvilleta.com
 * Description: A highly responsive custom Elementor widget to display contact information with icons and dividers.
 * Version:     1.0.0
 * Author:      Erick Villeta
 * Author URI:  https://ericksonvilleta.com
 * Text Domain: ibaw-contact-bar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Register the custom Elementor widget.
 *
 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
 * @return void
 */
function register_ibaw_contact_bar_widget( $widgets_manager ) {
	require_once( __DIR__ . '/widgets/contact-bar-widget.php' );
	$widgets_manager->register( new \IBAW_Contact_Bar_Widget() );
}
add_action( 'elementor/widgets/register', 'register_ibaw_contact_bar_widget' );