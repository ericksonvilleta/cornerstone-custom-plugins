<?php
/**
 * Plugin Name: IBAW-Hero Showcase
 * Description: Interactive 3-column Elementor Hero Widget with fully customizable animations and backgrounds.
 * Version: 1.2.1
 * Author: Erick Villeta
 * Plugin URI: https://ericksonvilleta.com
 */

if (!defined('ABSPATH')) exit;

final class IBAW_Hero_Showcase_Extension {

    const VERSION = '1.2.1';
    const MINIMUM_ELEMENTOR_VERSION = '3.0.0';
    const MINIMUM_PHP_VERSION = '7.4';

    private static $_instance = null;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        add_action('plugins_loaded', [$this, 'init']);
    }

    public function init() {
        if (!did_action('elementor/loaded')) {
            return;
        }
        add_action('elementor/frontend/after_enqueue_styles', [$this, 'enqueue_styles']);
        add_action('elementor/frontend/after_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('elementor/widgets/register', [$this, 'register_widgets']);
    }

    public function enqueue_styles() {
        wp_enqueue_style('ibaw-hero-style', plugins_url('assets/style.css', __FILE__), [], self::VERSION);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ibaw-hero-script', plugins_url('assets/script.js', __FILE__), ['jquery'], self::VERSION, true);
    }

    public function register_widgets($widgets_manager) {
        require_once(__DIR__ . '/widgets/hero-widget.php');
        $widgets_manager->register(new \IBAW_Hero_Widget());
    }
}

IBAW_Hero_Showcase_Extension::instance();