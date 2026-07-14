<?php
/**
 * Plugin Name: IBAW-Cart Add Notification Popup (Final Colors)
 * Plugin URI: https://ericksonvilleta.com
 * Description: Continue button set to #B7E29C (#6FC639 on hover). View Cart remains #a4e517.
 * Version: 1.0
 * Author: Erick Villeta
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'wp_footer', 'ibaw_cart_ajax_popup_v2_4', 99 );

function ibaw_cart_ajax_popup_v2_4() {
    ?>
    <div id="ibaw-cart-popup" style="display:none; position:fixed; z-index:999999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.6); align-items:center; justify-content:center; backdrop-filter: blur(5px);">
        
        <div class="ibaw-modal-content" style="background:#fff; padding:50px 40px 40px; border-radius:28px; max-width:480px; width:90%; text-align:center; position:relative; box-shadow:0 15px 50px rgba(0,0,0,0.25); font-family:'Quicksand', sans-serif;">
            
            <div onclick="document.getElementById('ibaw-cart-popup').style.display='none'" 
                 style="position:absolute; top:20px; right:25px; cursor:pointer; font-size:28px; color:#aaa; line-height:1; font-weight:300; transition:0.3s;"
                 onmouseover="this.style.color='#000'" onmouseout="this.style.color='#aaa'">
                &times;
            </div>

            <div id="ibaw-popup-image" style="margin-bottom:25px; display: flex; justify-content: center;">
                <img src="" style="width:140px; height:140px; object-fit:contain; border-radius:20px; border:1px solid #eaeaea; padding: 10px; background: #fff;">
            </div>

            <div id="ibaw-popup-content" style="margin-bottom:30px; font-weight:800; font-size:20px; color:#152542; line-height:1.2; font-family: 'Nunito Sans', sans-serif;"></div>
            
            <div class="ibaw-btn-container" style="display:flex; gap:15px; justify-content:center; align-items:stretch;">
                <?php 
                $btn_base = "display:flex; align-items:center; justify-content:center; width:170px; height:54px; border-radius:50px; text-transform:uppercase; font-size:13px; font-weight:800; letter-spacing:0.5px; text-decoration:none; border:none; cursor:pointer; color:#fff !important; transition:0.3s ease; margin: 0; padding: 0;";
                ?>
                <button id="ibaw-btn-continue" onclick="document.getElementById('ibaw-cart-popup').style.display='none'" style="<?php echo $btn_base; ?> background-color:#B7E29C;">Continue</button>
                <a id="ibaw-btn-viewcart" href="<?php echo esc_url(wc_get_cart_url()); ?>" style="<?php echo $btn_base; ?> background-color:#a4e517;">View Cart</a>
            </div>
        </div>
    </div>

    <style>
        .aux-woocommerce-ajax-notification, .aux-toast-container, .woocommerce-message[role="alert"], #aux-toast-container { display: none !important; }

        /* Custom Hover States */
        #ibaw-btn-continue:hover { 
            background-color: #6FC639 !important; 
        }
        #ibaw-btn-viewcart:hover { 
            background-color: #8bc213 !important; /* Darker shade of a4e517 */
        }

        /* Responsive Behavior */
        @media (max-width: 480px) {
            .ibaw-btn-container { flex-direction: column; align-items: center; }
            #ibaw-btn-continue, #ibaw-btn-viewcart { width: 100%; max-width: 280px; }
        }
        
        #ibaw-cart-popup a, #ibaw-cart-popup button { outline: none !important; text-decoration: none !important; }
    </style>

    <script type="text/javascript">
        jQuery(document).ready(function($) {
            var autoCloseTimer;

            $(document.body).on('added_to_cart', function(event, fragments, cart_hash, $button) {
                clearTimeout(autoCloseTimer);
                var productImage = $button.closest('.product, .aux-col, .elementor-widget-container').find('img').first().attr('src');
                
                $('#ibaw-popup-content').text("Item has been added to your cart!");
                if(productImage) { 
                    $('#ibaw-popup-image img').attr('src', productImage).parent().show(); 
                } else { 
                    $('#ibaw-popup-image').hide(); 
                }

                $('#ibaw-cart-popup').css('display', 'flex').hide().fadeIn(400);

                // Start 5-second auto-close
                autoCloseTimer = setTimeout(function() {
                    $('#ibaw-cart-popup').fadeOut(400);
                }, 5000);
            });

            $('#ibaw-cart-popup').on('click', function(e) {
                if (e.target === this) $(this).fadeOut(300);
            });
        });
    </script>
    <?php
}