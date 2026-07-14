<?php
/**
 * Plugin Name: IBAW- Mobile Sidebar Fix
 * Plugin URI:  https://ericksonvilleta.com
 * Description: Moves the sidebar above content on mobile. Optimized for current Phlox layouts.
 * Version:     2.0
 * Author:      Erick Villeta
 * Author URI:  https://ericksonvilleta.com
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'wp_footer', 'ibaw_mobile_sidebar_reorder_v2' );

function ibaw_mobile_sidebar_reorder_v2() {
    if ( ! is_shop() && ! is_product_taxonomy() ) return;
    ?>
    <style type="text/css">
        @media screen and (max-width: 959px) {
            /* Hide the original sidebar to prevent flash of unstyled content */
            .aux-sidebar-primary:not(.aux-sidebar-moved) {
                display: none !important;
            }
            /* Styling for when the sidebar is moved to the top */
            .aux-sidebar-moved {
                display: block !important;
                width: 100% !important;
                float: none !important;
                clear: both !important;
                margin-bottom: 30px !important;
                padding: 0 15px !important;
                box-sizing: border-box !important;
            }
            /* Ensure the products area follows correctly */
            .aux-primary, .aux-content-area, .aux-main {
                width: 100% !important;
                float: none !important;
            }
        }
    </style>

    <script type="text/javascript">
        (function($) {
            function moveSidebar() {
                if (window.innerWidth <= 959) {
                    // Target the sidebar (try multiple common Phlox classes)
                    var sidebar = document.querySelector('.aux-sidebar-primary') || document.querySelector('#secondary');
                    
                    // Target the main wrapper (The immediate parent of the product grid)
                    var contentRow = document.querySelector('.aux-main-content-inner .aux-row') || 
                                     document.querySelector('.aux-row') ||
                                     document.querySelector('main.aux-main');

                    if (sidebar && contentRow && !sidebar.classList.contains('aux-sidebar-moved')) {
                        // Move the sidebar to the very top of the content row
                        contentRow.prepend(sidebar);
                        sidebar.classList.add('aux-sidebar-moved');
                        console.log('IBAW: Sidebar moved to top.');
                    }
                }
            }

            // Run immediately, on load, and on resize
            moveSidebar();
            window.addEventListener('load', moveSidebar);
            window.addEventListener('resize', moveSidebar);

            // Extra check for AJAX-loaded shops (like some Phlox filters)
            $(document).on('ajaxComplete', function() {
                setTimeout(moveSidebar, 500);
            });

        })(jQuery);
    </script>
    <?php
}