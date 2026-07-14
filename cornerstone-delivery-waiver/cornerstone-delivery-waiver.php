<?php
/**
 * Plugin Name: IBAW-CLS Delivery Waiver & Checkout Lock
 * Description: Merges the delivery waiver into the native WooCommerce Terms & Conditions checkbox with a modal popup and shake animation.
 * Version: 1.0
 * Author: Erick Villeta
 * Plugin URI: https://ericksonvilleta.com
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// 1. Inject CSS for Modal and Shake Animation on Native WC Wrapper
add_action('wp_head', function() {
    ?>
    <style>
        .woocommerce-terms-and-conditions-wrapper.shake-me {
            animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both;
            color: #d32f2f !important;
            background: #fff0f0 !important;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #ffcccc;
        }
        @keyframes shake {
            10%, 90% { transform: translate3d(-1px, 0, 0); }
            20%, 80% { transform: translate3d(2px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
            40%, 60% { transform: translate3d(4px, 0, 0); }
        }
        #delivery-modal {
            display:none; position:fixed; z-index:999999; left:0; top:0; width:100%; height:100%; 
            background:rgba(0,0,0,0.8); backdrop-filter: blur(4px);
        }
        .delivery-modal-content {
            background:#fff; margin:5vh auto; padding:40px; border-radius:12px; width:92%; max-width:650px; 
            position:relative; max-height:85vh; overflow-y:auto; box-shadow: 0 20px 50px rgba(0,0,0,0.3); 
            line-height:1.7; font-family: sans-serif;
        }
    </style>
    <?php
});

// 2. Rewrite the Native WooCommerce Terms Checkbox Text
add_filter( 'woocommerce_get_terms_and_conditions_checkbox_text', function( $text ) {
    // Get the standard terms page link if it exists
    $terms_page_id = wc_get_page_id( 'terms' );
    $terms_link    = $terms_page_id ? '<a href="' . esc_url( get_permalink( $terms_page_id ) ) . '" target="_blank" class="woocommerce-terms-and-conditions-link">terms and conditions</a>' : 'terms and conditions';
    
    // Combine standard terms with custom waiver link
    return 'I have read and agree to the website ' . $terms_link . ' as well as the <a href="javascript:void(0);" id="open-delivery-modal" style="color:#81B716; text-decoration:underline; font-weight:bold;">Delivery Disclaimer & Liability Waiver</a><span class="required">*</span>';
});

// 3. The Modal HTML and Checkout Lock Logic
add_action( 'wp_footer', function() { ?>
    <div id="delivery-modal">
        <div class="delivery-modal-content">
            <span id="close-delivery-modal" style="position:absolute; top:20px; right:25px; font-size:32px; cursor:pointer; color:#bbb;">&times;</span>
            <h2 style="margin-top:0; color:#222; font-size:24px; border-bottom: 2px solid #81B716; padding-bottom:10px; display:inline-block;">Delivery Disclaimer & Waiver</h2>
            <div style="font-size:14px; color:#444; display:flex; flex-direction:column; gap:15px; margin-top:20px;">
                <p><strong>1. Property Damage Waiver:</strong> Our vehicles are extremely heavy. Cornerstone Landscape Supply is not responsible for damage to driveways, sidewalks, lawns, septic systems, or underground utilities.</p>
                <p><strong>2. Drop-Off Location:</strong> Materials are dropped in the safest location determined by the driver. Delivery beyond the curb is at the customer's full risk.</p>
                <p><strong>3. Overhead Obstacles:</strong> Customers must ensure clearance from branches, power lines, or overhangs.</p>
                <p><strong>4. Natural Variations:</strong> Bulk materials are natural products. Color and texture variations are expected.</p>
                <p><strong>5. Weather Impact:</strong> Rescheduling may occur due to site conditions.</p>
            </div>
            <button id="modal-agree-btn" style="width:100%; background:#81B716; color:#fff; border:none; padding:18px; border-radius:35px; font-weight:bold; cursor:pointer; margin-top:30px; font-size:16px; text-transform:uppercase;">I Understand & Agree</button>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($){
        function toggleOrderBtn() {
            var checked = $('#terms').is(':checked'); // Now targets native WC terms checkbox
            var btn = $('#place_order');
            
            if (checked) {
                btn.prop('disabled', false).css({'opacity': '1', 'cursor': 'pointer', 'filter': 'grayscale(0%)'});
                $('.woocommerce-terms-and-conditions-wrapper').removeClass('shake-me');
            } else {
                btn.prop('disabled', true).css({'opacity': '0.5', 'cursor': 'not-allowed', 'filter': 'grayscale(100%)'});
            }
        }

        // Handle the "Fake Click" on disabled button to trigger shake on native wrapper
        $(document).on('click', '#place_order', function(e){
            if ($(this).prop('disabled') || !$('#terms').is(':checked')) {
                e.preventDefault();
                $('.woocommerce-terms-and-conditions-wrapper').addClass('shake-me');
                setTimeout(function(){ $('.woocommerce-terms-and-conditions-wrapper').removeClass('shake-me'); }, 500);
            }
        });

        // Watch for standard cart updates and native checkbox changes
        $(document.body).on('updated_checkout', toggleOrderBtn);
        $(document).on('change', '#terms', toggleOrderBtn);
        
        // Open Modal (preventDefault stops it from checking/unchecking the box instantly)
        $(document).on('click', '#open-delivery-modal', function(e){ 
            e.preventDefault(); 
            e.stopPropagation();
            $('#delivery-modal').fadeIn(250); 
        });
        
        // Close Modal & Auto-check the native box
        $(document).on('click', '#modal-agree-btn', function(){
            $('#terms').prop('checked', true).trigger('change');
            $('#delivery-modal').fadeOut(250);
        });
        
        // Modal Close Triggers
        $('#close-delivery-modal').on('click', function(){ $('#delivery-modal').fadeOut(250); });
        $(window).on('click', function(e) { if ($(e.target).is('#delivery-modal')) $('#delivery-modal').fadeOut(250); });
    });
    </script>
<?php }, 999);