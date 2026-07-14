<?php
/**
 * Plugin Name: IBAW-UI Protection
 * Description: Disables right-click, inspect element shortcuts, and text selection.
 * Version: 1.0
 * Author: Erick Villeta
 * Plugin URI: https://ericksonvilleta.com
 */

if (!defined('ABSPATH')) exit;

class IBAW_UI_Protection {

    public function __construct() {
        add_action('wp_footer', [$this, 'render_protection_script'], 999);
        add_action('wp_head', [$this, 'render_selection_css']);
    }

    /**
     * JS to block context menu and dev tools shortcuts
     */
    public function render_protection_script() {
        if (is_admin()) return;
        ?>
        <script type="text/javascript">
            (function() {
                // Disable Right Click
                document.addEventListener('contextmenu', event => event.preventDefault());

                // Disable Common Dev Tool Shortcuts
                document.onkeydown = function(e) {
                    // F12
                    if (e.keyCode == 123) return false;

                    // Ctrl+Shift+I (Inspect), J (Console), C (Selector)
                    if (e.ctrlKey && e.shiftKey && (e.keyCode == 73 || e.keyCode == 74 || e.keyCode == 67)) return false;

                    // Ctrl+U (View Source)
                    if (e.ctrlKey && e.keyCode == 85) return false;

                    // Ctrl+S (Save Page)
                    if (e.ctrlKey && e.keyCode == 83) return false;
                };

                // Prevent Image Dragging
                document.addEventListener('dragstart', event => event.preventDefault());
            })();
        </script>
        <?php
    }

    /**
     * CSS to block text highlighting across the site
     */
    public function render_selection_css() {
        if (is_admin()) return;
        ?>
        <style>
            body {
                -webkit-user-select: none;
                -moz-user-select: none;
                -ms-user-select: none;
                user-select: none;
            }
        </style>
        <?php
    }
}

new IBAW_UI_Protection();