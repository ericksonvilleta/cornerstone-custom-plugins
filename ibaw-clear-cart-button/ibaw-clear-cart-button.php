<?php
/**
 * Plugin Name: IBAW-Clear Cart Button
 * Plugin URI: https://ericksonvilleta.com
 * Description: Adds a trash can icon to the WooCommerce cart table header to remove all items at once.
 * Version: 1.0
 * Author: Erick Villeta
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IBAW_Clear_Cart {

    public function __construct() {
        // Hook to process the cart clearing action
        add_action( 'template_redirect', [ $this, 'process_clear_cart' ] );
        // Hook to inject the icon via JavaScript
        add_action( 'wp_footer', [ $this, 'inject_trash_icon_js' ] );
    }

    public function process_clear_cart() {
        // Check if we are on the cart page and our specific URL parameter is present
        if ( is_cart() && isset( $_GET['ibaw_clear_cart'] ) && 'yes' === $_GET['ibaw_clear_cart'] ) {
            WC()->cart->empty_cart();
            wc_add_notice( 'All items have been removed from your cart.', 'notice' );
            wp_safe_redirect( wc_get_cart_url() );
            exit;
        }
    }

    public function inject_trash_icon_js() {
        // Only run this script on the cart page if the cart actually has items
        if ( ! is_cart() || WC()->cart->is_empty() ) return;
        
        // Generate the URL that triggers the PHP clearing function above
        $clear_url = esc_url( add_query_arg( 'ibaw_clear_cart', 'yes', wc_get_cart_url() ) );
        ?>
        <script>
        jQuery(document).ready(function($){
            // The SVG Trash Can Icon structure
            var trashIcon = '<a href="<?php echo $clear_url; ?>" class="ibaw-empty-cart-btn" title="Clear entire cart" onclick="return confirm(\'Are you sure you want to remove all items from your cart?\');" style="display:inline-block; color:#666; cursor:pointer; transition: color 0.2s;"><svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg></a>';
            
            // Inject a quick style rule so it turns #a4e517 when a user hovers over it
            $('head').append('<style>.ibaw-empty-cart-btn:hover { color: #a4e517 !important; }</style>');

            // Target the empty <th> cell above the individual "X" remove buttons and inject the icon
            var $targetTh = $('.woocommerce-cart-form__contents thead th.product-remove');
            
            if ($targetTh.length) {
                $targetTh.html(trashIcon).css({'text-align': 'center', 'vertical-align': 'middle'});
            } else {
                // Fallback just in case a custom theme removes standard WooCommerce classes
                $('.woocommerce-cart-form__contents thead th.product-name').prepend(trashIcon + '&nbsp;&nbsp;');
            }
        });
        </script>
        <?php
    }
}
new IBAW_Clear_Cart();