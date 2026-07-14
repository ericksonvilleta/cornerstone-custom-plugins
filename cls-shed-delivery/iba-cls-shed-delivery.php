<?php
/*
Plugin Name: IBAW-CLS Shed Delivery
Plugin URI: https://ericksonvilleta.com
Description: Version 11.0 - Direct Meta Extraction, Bulletproof Cart Quarantine, Dynamic Custom Modal UI (Mixed Cart & Address Check), Handling Fee & Unified Fee UI.
Version: 11.0
Author: Erick Villeta
*/

if (!defined('ABSPATH')) exit;

class IBAW_CLS_Shed_Delivery {
    private $origin = "154 Wolfcraft Way, Charles Town, WV 25414";
    private $api = "AIzaSyCh7zq4TLYD9beiLpa30UEGu0GHBYL1Zms"; 

    function __construct(){
        add_action('wp_enqueue_scripts', [$this,'scripts'], 999);
        add_action('woocommerce_before_cart_totals', [$this,'address_field']);
        add_action('woocommerce_cart_calculate_fees', [$this,'calculate_all_shed_charges'], 5); 
        add_action('wp_ajax_cls_save_distance', [$this,'save_distance']);
        add_action('wp_ajax_nopriv_cls_save_distance', [$this,'save_distance']);
        add_action('wp_head', [$this, 'custom_css']);
        
        // Firewall 1: Prevent at the 'Add to Cart' button click
        add_filter('woocommerce_add_to_cart_validation', [$this, 'prevent_mixed_shed_cart'], 99, 4);
        
        // Firewall 2: The ultimate Cart Page security checkpoint
        add_action('woocommerce_check_cart_items', [$this, 'enforce_cart_rules']); 

        // Output the Dynamic Modal HTML to the footer
        add_action('wp_footer', [$this, 'shed_dynamic_modal_html']);
        
        // Output the Grouped Fee UI script
        add_action('wp_footer', [$this, 'erick_shed_cart_fee_grouper_js']);
    }

    /* --- BULLETPROOF SHED IDENTIFIER --- */
    private function is_product_shed($product_id, $variation_id = 0) {
        $check_id = !empty($variation_id) ? $variation_id : $product_id;
        $product = wc_get_product($check_id);
        if (!$product) return false;

        // 1. Check exact name
        if (stripos($product->get_name(), 'Shed') !== false || stripos($product->get_title(), 'Shed') !== false) return true;

        // 2. Deep dive into Parent Category Slugs
        $parent_id = $product->get_parent_id() ? $product->get_parent_id() : $product_id;
        if ($parent_id) {
            $terms = wp_get_post_terms($parent_id, 'product_cat', ['fields' => 'slugs']);
            if (!is_wp_error($terms) && !empty($terms)) {
                foreach ($terms as $slug) {
                    if (stripos($slug, 'shed') !== false) return true;
                }
            }
        }
        
        return false;
    }

    private function has_shed() {
        if (!WC()->cart) return false;
        foreach (WC()->cart->get_cart() as $item) {
            if ($this->is_product_shed($item['product_id'], isset($item['variation_id']) ? $item['variation_id'] : 0)) {
                return true;
            }
        }
        return false;
    }

    /* --- FIREWALL 1: PREVENT MIXING AT ADD-TO-CART --- */
    function prevent_mixed_shed_cart($passed, $product_id, $quantity = 1, $variation_id = 0) {
        if (WC()->cart->is_empty()) return $passed;

        $is_adding_shed = $this->is_product_shed($product_id, $variation_id);
        
        $cart_has_shed = false;
        $cart_has_non_shed = false;

        foreach (WC()->cart->get_cart() as $cart_item) {
            $is_shed = $this->is_product_shed($cart_item['product_id'], isset($cart_item['variation_id']) ? $cart_item['variation_id'] : 0);
            if ($is_shed) {
                $cart_has_shed = true;
            } else {
                $cart_has_non_shed = true;
            }
        }

        if ($is_adding_shed && $cart_has_non_shed) {
            wc_add_notice('🏡 Buildings get special delivery treatment. To make sure everything arrives safely, outdoor buildings must be checked out separately from all other products.', 'error');
            return false;
        } elseif (!$is_adding_shed && $cart_has_shed) {
            wc_add_notice('🏡 Buildings get special delivery treatment. To make sure everything arrives safely, outdoor buildings must be checked out separately from all other products.', 'error');
            return false;
        }

        return $passed;
    }

    /* --- FIREWALL 2: THE CART PAGE CHECKPOINT --- */
    function enforce_cart_rules() {
        if (!WC()->cart || WC()->cart->is_empty()) return;
        
        $cart_has_shed = false;
        $cart_has_non_shed = false;

        foreach (WC()->cart->get_cart() as $cart_item) {
            $is_shed = $this->is_product_shed($cart_item['product_id'], isset($cart_item['variation_id']) ? $cart_item['variation_id'] : 0);
            if ($is_shed) {
                $cart_has_shed = true;
            } else {
                $cart_has_non_shed = true;
            }
        }

        // 1. The Quarantine Check
        if ($cart_has_shed && $cart_has_non_shed) {
            wc_add_notice('🏡 Buildings get special delivery treatment. To make sure everything arrives safely, outdoor buildings must be checked out separately from all other products.', 'error');
            
            if (is_checkout() && !is_wc_endpoint_url()) {
                wp_redirect(wc_get_cart_url());
                exit;
            }
            return; 
        }

        // 2. The Mandatory Address Check
        if ($cart_has_shed) {
            $dist = WC()->session->get('cls_distance');
            if (empty($dist) || $dist <= 0) {
                wc_add_notice('<strong>Shed Delivery Address Required:</strong> Please enter your shed delivery address in the map field to calculate shed delivery fees before proceeding to checkout.', 'error');
                
                if (is_checkout() && !is_wc_endpoint_url()) {
                    wp_redirect(wc_get_cart_url());
                    exit;
                }
            }
        }
    }

    /* --- DYNAMIC MODAL UI INJECTION & JS OBSERVER --- */
    function shed_dynamic_modal_html() {
        ?>
        <div id="erick-shed-modal" style="display:none; position:fixed; z-index:999999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.85); backdrop-filter:blur(5px); justify-content:center; align-items:flex-start; padding-top:10vh;">
            <div style="background:#fff; padding:40px; border-radius:15px; width:90%; max-width:500px; text-align:center; border-top: 8px solid #d32f2f; position:relative; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
                <span onclick="jQuery('#erick-shed-modal').fadeOut()" style="position:absolute; top:10px; right:15px; font-size:28px; font-weight:bold; color:#000; cursor:pointer; line-height: 1;">&times;</span>
                <div id="erick-shed-modal-icon" style="font-size:40px; margin-bottom:10px;">⚠️</div>
                <h2 id="erick-shed-modal-title" style="font-size:26px; margin-bottom:20px; color: #333;">Order Delivery Information</h2>
                <p id="erick-shed-modal-desc" style="font-size:16px; margin-bottom:25px; color: #555; line-height: 1.5;"></p>
                <button id="erick-shed-modal-btn" style="background:#77b235; color:#fff !important; padding:15px 30px; border-radius:50px; display:inline-block; font-weight:bold; border:none; cursor:pointer; font-size:1.1em; transition: 0.3s;"></button>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($){
            // Function to hunt down specific banners and turn them into the dynamic modal
            function convertBannerToModal() {
                var mixedCartTrigger = "Buildings get special delivery treatment";
                var addressTrigger = "Shed Delivery Address Required";
                
                $('.woocommerce-error, .woocommerce-message, .woocommerce-info').each(function() {
                    var bannerText = $(this).text();

                    // CASE 1: Mixed Cart Warning
                    if (bannerText.indexOf(mixedCartTrigger) !== -1) {
                        $(this).hide(); 
                        $('#erick-shed-modal-icon').text('⚠️');
                        $('#erick-shed-modal-title').text('Order Delivery Information');
                        $('#erick-shed-modal-desc').text('🏡 Buildings get special delivery treatment. To make sure everything arrives safely, outdoor buildings must be checked out separately from all other products.');
                        $('#erick-shed-modal-btn').text('Got it, Separate my Order').attr('onclick', "jQuery('#erick-shed-modal').fadeOut()");
                        $('#erick-shed-modal').css('display', 'flex').hide().fadeIn(); 
                        
                        $('.checkout-button, #place_order').hide();
                    } 
                    // CASE 2: Missing Address Warning
                    else if (bannerText.indexOf(addressTrigger) !== -1) {
                        $(this).hide(); 
                        $('#erick-shed-modal-icon').text('📍');
                        $('#erick-shed-modal-title').text('Delivery Address Needed');
                        $('#erick-shed-modal-desc').text('To calculate accurate delivery fees for your building, we need to know where it is going! Please enter your address in the map field.');
                        $('#erick-shed-modal-btn').text('Okay, I will enter my address').attr('onclick', "jQuery('#erick-shed-modal').fadeOut(); jQuery('html, body').animate({scrollTop: jQuery('#cls_wrap').offset().top - 100}, 500); setTimeout(function(){ jQuery('#cls_delivery_address').focus(); }, 600);");
                        $('#erick-shed-modal').css('display', 'flex').hide().fadeIn(); 
                        
                        $('.checkout-button, #place_order').hide();
                    }
                });
            }

            // Run on load
            convertBannerToModal();

            // Run after AJAX cart updates
            $(document.body).on('updated_cart_totals', convertBannerToModal);
            $(document).ajaxComplete(function(event, xhr, settings) {
                convertBannerToModal();
            });
        });
        </script>
        <?php
    }

    /* --- SHED UI GROUPER JS --- */
    function erick_shed_cart_fee_grouper_js() {
        if (!is_cart() || !$this->has_shed()) return;
        ?>
        <script>
        jQuery(document).ready(function($){
            function applyShedUIFixes() {
                var taxRow = $('tr.fee').filter(function() { return $(this).text().toUpperCase().indexOf('SALES TAX') !== -1; });
                var handlingRow = $('tr.fee').filter(function() { return $(this).text().toUpperCase().indexOf('HANDLING FEE') !== -1; });
                var totalRow = $('tr.order-total');

                $('.erick-combined-fee-row').remove();

                if ((taxRow.length || handlingRow.length) && totalRow.length) {
                    var totalFee = 0;
                    var breakdownHTML = '';
                    var currencySym = '$';

                    function processRow(row) {
                        if(row.length) {
                            var amountHTML = row.find('td').html();
                            var amountText = row.find('td').text().trim();
                            var rawNum = amountText.replace(/[^0-9.]/g, ''); 
                            totalFee += parseFloat(rawNum) || 0;
                            var rowName = row.find('th').text().trim();

                            var sym = row.find('.woocommerce-Price-currencySymbol').first().text();
                            if(sym) currencySym = sym;

                            breakdownHTML += '<div style="display:flex; justify-content:space-between; margin-top:6px; font-weight:normal; font-size:0.95em; color:#555;"><span>' + rowName + '</span><span>' + amountHTML + '</span></div>';
                            row.css('display', 'none'); 
                        }
                    }

                    processRow(taxRow);
                    processRow(handlingRow);

                    var combinedRow = $('<tr class="fee erick-combined-fee-row"><th style="padding-bottom:10px;"><span class="erick-fee-toggle" style="cursor:pointer; display:inline-block; user-select:none; border-bottom:1px dashed #999; padding-bottom:2px;">TAXES & FEES <span class="erick-fee-caret" style="font-size:0.8em; margin-left:4px;">▼</span></span><div class="erick-fee-details" style="display:none; margin-top:10px; padding:10px; background:#fafafa; border-radius:6px; border:1px solid #eee;">' + breakdownHTML + '</div></th><td data-title="TAXES & FEES"><span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">' + currencySym + '</span>' + totalFee.toFixed(2) + '</bdi></span></td></tr>');

                    combinedRow.insertBefore(totalRow);
                }
            }

            if(typeof window.erickShedFeeToggleBound === 'undefined') {
                $(document.body).on('click', '.erick-fee-toggle', function(){
                    $(this).next('.erick-fee-details').slideToggle(200);
                    var arrow = $(this).find('.erick-fee-caret');
                    arrow.text(arrow.text() === '▼' ? '▲' : '▼');
                });
                window.erickShedFeeToggleBound = true;
            }

            applyShedUIFixes();
            $(document.body).on('updated_cart_totals', applyShedUIFixes);
        });
        </script>
        <?php
    }

    function custom_css() {
        if ($this->has_shed()) {
            echo '<style>
                .tax-total, .cart-subtotal + .tax-total { display: none !important; } 
                .pac-container { z-index: 999999 !important; }
                #cls_wrap { border: 2px solid #7CB342; padding: 15px; border-radius: 15px; margin-bottom: 25px; background: #fff; }
                #cls_delivery_address { width: 100%; padding: 12px; border: 1px solid #ccc; }
                
                .woocommerce-error + .cart-collaterals .checkout-button, 
                .woocommerce-error + .cart-collaterals #place_order {
                    display: none !important;
                }
            </style>';
        }
    }

    function scripts(){
        if(!is_cart() || !$this->has_shed()) return;
        wp_register_script('google-maps-sdk', 'https://maps.googleapis.com/maps/api/js?key=' . $this->api . '&libraries=places', [], null, true);
        wp_enqueue_script('iba-cls-js', plugin_dir_url(__FILE__).'delivery.js', ['jquery', 'google-maps-sdk'], '11.0', true);
        wp_localize_script('iba-cls-js', 'clsData', ['origin' => $this->origin, 'ajax' => admin_url('admin-ajax.php')]);
    }

    function address_field(){
        if(!$this->has_shed()) return;
        
        $saved_address = WC()->session->get('cls_address') ? WC()->session->get('cls_address') : '';
        $saved_dist = WC()->session->get('cls_distance');
        
        $res_html = '';
        $res_style = 'display:none;';
        
        if ($saved_dist) {
            $res_html = '<strong>Distance:</strong> ' . $saved_dist . ' miles.';
            $res_style = 'display:block;';
        }
        
        echo '<div id="cls_wrap">
                <h3 style="margin-top:0;">Enter Shed Delivery Address</h3>
                <input type="text" id="cls_delivery_address" value="' . esc_attr($saved_address) . '" placeholder="Type address to calculate fees..." autocomplete="off">
                <div id="cls_res" style="background:#f9f9f9; padding:12px; margin-top:10px; border-left:4px solid #7CB342; ' . $res_style . '">' . $res_html . '</div>
              </div>';
    }

    function calculate_all_shed_charges($cart){
        if(is_admin() || !$this->has_shed()) return;
        
        $dist = WC()->session->get('cls_distance');
        $zip = WC()->session->get('cls_zip');

        if ( empty($dist) || $dist <= 0 ) return;

        $total_fee = 0;
        $extra_miles = ($dist > 30) ? round($dist - 30, 1) : 0;
        $rate = 0; 
        
        foreach($cart->get_cart() as $item){
            $product_id = $item['product_id'];
            $product_name = $item['data']->get_name();
            
            $size_val = get_post_meta($product_id, 'shed_size', true);
            
            if(empty($size_val) && (strpos($product_name, '#52') !== false || strpos($product_name, '#13') !== false)) {
                $width = 14;
            } else {
                preg_match('/\d+/', (string)$size_val, $matches);
                $width = isset($matches[0]) ? intval($matches[0]) : 10;
            }

            if ($width === 14) { $rate = 5; }
            elseif ($width >= 16) { $rate = 6; }
            else { $rate = 4; }

            if ($extra_miles > 0) {
                $total_fee += ($extra_miles * $rate);
            }
        }

        if($total_fee > 0) {
            $cart->add_fee('SHED DELIVERY ($' . $rate . '/MI OVER 30MI)', $total_fee, false);
        }
        
        $tax_amount = 0;
        if($zip){
            $tax_rate = ($zip == '25414') ? 0.07 : 0.06;
            $tax_amount = ($cart->get_subtotal() + $total_fee) * $tax_rate;
            $cart->add_fee('SALES TAX (' . ($tax_rate * 100) . '%)', $tax_amount, false);
        }

        // 4. Process Handling Fee (3.5% of Subtotal + Delivery + Tax)
        $subtotal = $cart->get_subtotal();
        $current_total = $subtotal + $total_fee + $tax_amount;
        
        if ($current_total > 0) {
            $handling_fee = $current_total * 0.035;
            $cart->add_fee('HANDLING FEE (3.5%)', $handling_fee, false);
        }
    }

    function save_distance(){
        $d = isset($_POST['distance']) ? $_POST['distance'] : '';
        $z = isset($_POST['zip']) ? $_POST['zip'] : '';
        $a = isset($_POST['address']) ? $_POST['address'] : '';

        if($d === '' || $d <= 0){
            WC()->session->set('cls_distance', null);
            WC()->session->set('cls_zip', null);
            WC()->session->set('cls_address', null);
        } else {
            WC()->session->set('cls_distance', floatval($d));
            WC()->session->set('cls_zip', sanitize_text_field($z));
            WC()->session->set('cls_address', sanitize_text_field($a));
        }
        wp_die();
    }
}
new IBAW_CLS_Shed_Delivery();