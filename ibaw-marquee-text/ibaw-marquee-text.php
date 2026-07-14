<?php
/**
 * Plugin Name: IBAW-Marquee Text
 * Description: A highly customizable and mobile responsive Marquee Text display addon widget for Elementor.
 * Plugin URI: https://ericksonvilleta.com
 * Author: Erick Villeta
 * Version: 1.0.0
 * Text Domain: ibaw-marquee
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

final class IBAW_Marquee_Extension {
	const VERSION = '1.0.0';
	const MINIMUM_ELEMENTOR_VERSION = '3.0.0';
	const MINIMUM_PHP_VERSION = '7.0';

	private static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {
		add_action( 'plugins_loaded', [ $this, 'init' ] );
	}

	public function init() {
		// Check if Elementor installed and activated
		if ( ! did_action( 'elementor/loaded' ) ) {
			add_action( 'admin_notices', [ $this, 'admin_notice_missing_main_plugin' ] );
			return;
		}

		// Register Widget Styles
		add_action( 'elementor/frontend/after_enqueue_styles', [ $this, 'widget_styles' ] );

		// Register widgets
		add_action( 'elementor/widgets/register', [ $this, 'init_widgets' ] );
	}

	public function widget_styles() {
		wp_register_style( 'ibaw-marquee-style', plugins_url( 'assets/css/style.css', __FILE__ ), [], self::VERSION );
	}

	public function init_widgets( $widgets_manager ) {
		require_once( __DIR__ . '/widgets/marquee-widget.php' );
		$widgets_manager->register( new \IBAW_Marquee_Widget() );
	}

	public function admin_notice_missing_main_plugin() {
		echo '<div class="notice notice-warning is-dismissible"><p><strong>IBAW-Marquee Text</strong> requires Elementor to be installed and active.</p></div>';
	}
}

IBAW_Marquee_Extension::instance();