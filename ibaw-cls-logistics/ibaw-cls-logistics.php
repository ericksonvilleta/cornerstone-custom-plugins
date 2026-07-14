<?php
/**
 * Plugin Name: IBAW-CLS Custom Local Pickup & Delivery
 * Description: Dashboard-Strict Pickup Logic, Fractional Multipliers, Strict ZIP Validation, Auto-Submit UI, Split Tax Logic, Grouped Fees UI, & Strict Power Tool Isolation.
 * Version: 1.4.4
 * Author: Erick Villeta
 * Plugin URI: https://ericksonvilleta.com
 */

if (!defined('ABSPATH')) exit;

class IBAW_CLS_Logistics_Manager {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('init', [$this, 'register_ready_for_pickup_status']);
        add_action('woocommerce_init', [$this, 'force_guest_session']);
        add_filter('wc_order_statuses', [$this, 'add_status_to_list']);

        add_action('wp_ajax_erick_update_checkout_state', [$this, 'erick_ajax_update_state']);
        add_action('wp_ajax_nopriv_erick_update_checkout_state', [$this, 'erick_ajax_update_state']);
        add_action('wp_ajax_cls_check_zip', [$this, 'ajax_check_zip']);
        add_action('wp_ajax_nopriv_cls_check_zip', [$this, 'ajax_check_zip']);
        add_action('wp_ajax_cls_reset_zip', [$this, 'ajax_reset_zip']);
        add_action('wp_ajax_nopriv_cls_reset_zip', [$this, 'ajax_reset_zip']);

        add_action('wp_ajax_cls_get_zip_state', [$this, 'ajax_get_zip_state']);
        add_action('wp_ajax_nopriv_cls_get_zip_state', [$this, 'ajax_get_zip_state']);

        add_action('wp_enqueue_scripts', [$this, 'erick_enqueue_scripts']);
        add_action('woocommerce_before_variations_form', [$this, 'render_zip_checker']);
        add_action('woocommerce_before_cart_totals', [$this, 'erick_fulfillment_toggle_ui']);
        add_action('woocommerce_checkout_before_customer_details', [$this, 'erick_fulfillment_toggle_ui']);

        add_action('woocommerce_before_cart_totals', [$this, 'custom_add_mountain_cart'], 15);
        add_action('woocommerce_checkout_before_customer_details', [$this, 'custom_add_mountain_cart'], 15);

        add_action('wp_footer', [$this, 'erick_huge_order_modal_html']);
        add_action('wp_footer', [$this, 'erick_global_cart_watcher_js']);

        add_filter('woocommerce_package_rates', [$this, 'custom_calculate_shipping'], 100, 2);
        add_filter('woocommerce_cart_calculate_fees', [$this, 'force_pickup_logic']);
        add_filter('woocommerce_add_to_cart_validation', [$this, 'enforce_logistics_and_huge_order'], 20, 3);
        add_filter('woocommerce_checkout_get_value', [$this, 'prefill_checkout_zip'], 10, 2);
        add_filter('woocommerce_cart_shipping_packages', [$this, 'nuke_shipping_display_for_pickup'], 100);
        add_filter('gettext', [$this, 'erick_force_text_translations'], 20, 3);

        add_filter('woocommerce_shipping_package_name', [$this, 'change_shipping_package_name'], 10, 3);
    }

    public function change_shipping_package_name($name, $i, $package) {
        return 'Delivery Mode';
    }

    public function force_guest_session() {
        if (!is_user_logged_in() && isset(WC()->session) && !WC()->session->has_session()) {
            WC()->session->set_customer_session_cookie(true);
        }
    }

    public function add_admin_menu() {
        add_submenu_page('woocommerce', 'Logistics Settings', 'Logistics Settings', 'manage_options', 'cls-logistics-settings', [$this, 'settings_page']);
    }

    public function register_settings() {
        register_setting('cls-logistics-group', 'cls_allowed_zips');
        register_setting('cls-logistics-group', 'cls_excluded_products');
        register_setting('cls-logistics-group', 'cls_pickup_only_products');
        register_setting('cls-logistics-group', 'cls_no_delivery_fee_products');
    }

    public function settings_page() {
        $product_ids = get_posts(['post_type' => 'product', 'posts_per_page' => -1, 'post_status' => 'publish', 'fields' => 'ids']);
        ?>
        <div class="wrap">
            <h1>CLS Logistics Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('cls-logistics-group'); ?>
                <div style="background:#fff; padding:20px; border:1px solid #ccd0d4; border-left:4px solid #8bc34a; margin-bottom:20px;">
                    <h2>1. Delivery Zipcodes & Exclusions</h2>
                    <textarea name="cls_allowed_zips" rows="8" style="width:100%; max-width:600px;"><?php echo esc_textarea(get_option('cls_allowed_zips')); ?></textarea>
                    <h3>Exclude Products from ZIP Validation</h3>
                    <div style="max-height: 150px; overflow-y: auto; border: 1px solid #eee; padding: 10px; max-width:600px;">
                        <?php foreach ($product_ids as $id): ?>
                            <label style="display:block;">
                                <input type="checkbox" name="cls_excluded_products[]" value="<?php echo $id; ?>" <?php checked(in_array($id, (array)get_option('cls_excluded_products', []))); ?>> <?php echo esc_html(get_the_title($id)); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div style="background:#fff; padding:20px; border:1px solid #ccd0d4; border-left:4px solid #2196F3; margin-bottom:20px;">
                    <h2>2. Pickup-Only WC Products</h2>
                    <div style="max-height: 200px; overflow-y: auto; border: 1px solid #eee; padding: 10px; max-width:600px;">
                        <?php foreach ($product_ids as $id): ?>
                            <label style="display:block;">
                                <input type="checkbox" name="cls_pickup_only_products[]" value="<?php echo $id; ?>" <?php checked(in_array($id, (array)get_option('cls_pickup_only_products', []))); ?>> <?php echo esc_html(get_the_title($id)); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div style="background:#fff; padding:20px; border:1px solid #ccd0d4; border-left:4px solid #ff9800; margin-bottom:20px;">
                    <h2>3. Exclude Products from Delivery Fee</h2>
                    <p style="color:#666;">These products will not be charged a delivery fee, but will still be taxed and incur a handling fee based on their price. <strong>Pickup will be disabled, but ZIP verification remains required.</strong></p>
                    <div style="max-height: 200px; overflow-y: auto; border: 1px solid #eee; padding: 10px; max-width:600px;">
                        <?php foreach ($product_ids as $id): ?>
                            <label style="display:block;">
                                <input type="checkbox" name="cls_no_delivery_fee_products[]" value="<?php echo $id; ?>" <?php checked(in_array($id, (array)get_option('cls_no_delivery_fee_products', []))); ?>> <?php echo esc_html(get_the_title($id)); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    private function should_skip_logistics() {
        if (!WC()->cart) return false;
        foreach (WC()->cart->get_cart() as $item) {
            if (!$item['data']) continue;
            $name = strtolower($item['data']->get_name());
            $product_id = $item['product_id'];
            if (strpos($name, 'shed') !== false || empty($product_id) || get_post_type($product_id) !== 'product') return true;
        }
        return false;
    }

    private function get_cart_composition() {
        $comp = ['loose_qty' => 0, 'loose_types' => 0, 'bagged_qty' => 0, 'bagged_names' => [], 'has_mower' => false, 'has_power_tool' => false];
        if (!WC()->cart) return $comp;
        $pickup_only = (array)get_option('cls_pickup_only_products', []);
        foreach (WC()->cart->get_cart() as $item) {
            $product = $item['data'];
            $qty = $item['quantity'];
            $product_id = $item['product_id'];
            $terms = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'slugs']);
            if (is_wp_error($terms)) $terms = [];
            
            $is_mower = strpos(strtolower($product->get_name()), 'mower') !== false;
            $is_power_tool = strpos(strtolower($product->get_name()), 'echo') !== false || strpos(strtolower($product->get_name()), 'power tool') !== false;

            if ($is_mower) {
                $comp['has_mower'] = true;
            }
            if ($is_power_tool) {
                $comp['has_power_tool'] = true;
            }
            
            $is_explicit_bagged = in_array('bulk-bagged-materials', $terms) || in_array($product_id, $pickup_only) || $is_mower || $is_power_tool;

            if ($is_explicit_bagged) {
                $comp['bagged_qty'] += $qty;
                $p_title = strtoupper(strip_tags($product->get_title()));
                if (!isset($comp['bagged_names'][$p_title])) {
                    $comp['bagged_names'][$p_title] = 0;
                }
                $comp['bagged_names'][$p_title] += $qty;
            } else {
                $comp['loose_qty'] += $qty;
                $comp['loose_types']++;
            }
        }
        return $comp;
    }

    public function nuke_shipping_display_for_pickup($packages) {
        if ($this->should_skip_logistics()) return $packages;
        $comp = $this->get_cart_composition();
        
        $no_delivery_fee = (array)get_option('cls_no_delivery_fee_products', []);
        $is_pickup_locked = false;
        if (WC()->cart) {
            foreach (WC()->cart->get_cart() as $item) {
                if (in_array($item['product_id'], $no_delivery_fee)) {
                    $is_pickup_locked = true;
                    break;
                }
            }
        }
        if ($is_pickup_locked) return $packages;

        if (WC()->session && (WC()->session->get('erick_fulfillment') === 'pickup' || $comp['loose_qty'] === 0 || $comp['has_mower'] || $comp['has_power_tool'])) return array();
        return $packages;
    }

    public function force_pickup_logic($cart) { 
        if($this->should_skip_logistics()) return; 
        $comp = $this->get_cart_composition();
        $total_delivery_cost = 0;
        
        $session_zip = WC()->session->get('cls_validated_zip') ?: WC()->customer->get_shipping_postcode();
        $zip = str_replace(' ', '', $session_zip);
        $is_mountain = (WC()->session->get('is_mountain') === 'Yes');
        $is_mtn_flag = ($is_mountain && $zip === '25425');
        
        $allowed_zips = array_filter(array_map('trim', explode("\n", get_option('cls_allowed_zips', ''))));
        $has_valid_zip = (!empty($zip) && in_array($zip, $allowed_zips));
        
        $cls_rates = ['25414'=>70,'25438'=>70,'25442'=>70,'25430'=>70,'25423'=>70,'25410'=>70,'25432'=>70,'25425'=>75,'21779'=>95,'25401'=>90,'25402'=>90,'25403'=>90,'25404'=>90,'25405'=>90,'25443'=>90,'21713'=>95,'21716'=>95,'21718'=>95,'25428'=>90,'25413'=>90,'25446'=>90,'20180'=>90,'25427'=>90,'21758'=>95,'25441'=>90,'21756'=>95,'22611'=>70,'22601'=>150,'22602'=>150,'22603'=>150,'22604'=>150,'21641'=>120,'20175'=>150,'20176'=>150,'20177'=>150,'20178'=>150,'20135'=>120,'20132'=>120,'20134'=>120,'20160'=>120,'21782'=>150,'20141'=>120,'20142'=>120,'21214'=>200,'22622'=>175,'22724'=>175,'20197'=>150];
        $gaskins_rates = ['21779'=>300,'25401'=>250,'25402'=>250,'25403'=>250,'25404'=>250,'25405'=>250,'25443'=>250,'21713'=>300,'21716'=>300,'21718'=>300,'25428'=>150,'25413'=>150,'25446'=>150,'20180'=>300,'25427'=>270,'21758'=>300,'25441'=>325,'21756'=>325,'22611'=>300,'22601'=>325,'22602'=>325,'22603'=>325,'22604'=>325,'21641'=>300,'20175'=>300,'20176'=>300,'20177'=>300,'20178'=>300,'20135'=>300,'25414'=>150,'25438'=>150,'25442'=>150,'25430'=>150,'25425'=>150,'25420'=>250,'25411'=>325];
        $pickup_only = (array)get_option('cls_pickup_only_products', []);
        
        $no_delivery_fee = (array)get_option('cls_no_delivery_fee_products', []);

        foreach ($cart->get_cart() as $item) {
            $product = $item['data']; 
            $name = strtolower($product->get_name()); 
            $qty = $item['quantity']; 
            $product_id = $item['product_id'];
            
            $terms = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'slugs']);
            $is_mower = strpos(strtolower($product->get_name()), 'mower') !== false;
            $is_power_tool = strpos(strtolower($product->get_name()), 'echo') !== false || strpos(strtolower($product->get_name()), 'power tool') !== false;
            $is_explicit_bagged = (is_array($terms) && in_array('bulk-bagged-materials', $terms)) || in_array($product_id, $pickup_only) || $is_mower || $is_power_tool;

            if (!$is_explicit_bagged) {
                if (WC()->session->get('erick_fulfillment') !== 'pickup' && !$has_valid_zip) {
                    $fee_label = in_array($product_id, $no_delivery_fee) ? 'ZIP CODE REQUIRED FOR ' : 'DELIVERY: ZIP REQUIRED FOR ';
                    $cart->add_fee($fee_label . strtoupper(strip_tags($product->get_title())), 0, false);
                    continue; 
                }

                $multiplier = 1;
                if (preg_match('/(1\/2|0\.5)/u', $name)) { $multiplier = 0.5; } 
                elseif (preg_match('/(1\/4|0\.25)/u', $name)) { $multiplier = 0.25; } 
                elseif (preg_match('/(3\/4|0\.75)/u', $name)) { $multiplier = 0.75; }
                $actual_vol_or_weight = $qty * $multiplier;

                $is_soil = preg_match('/(soil|dirt|grow|compost)/i', $name);
                $is_mulch = strpos($name, 'mulch') !== false;
                $is_yards = (preg_match('/(yard|cu\.?\s*yd)/i', $name) || $is_mulch);
                $is_stone_material = preg_match('/(stone|gravel|sand|rock|pebble|jack)/i', $name);

                if ($is_soil) {
                    $max_per_trip = 4;
                } elseif ($is_mulch || $is_yards) {
                    $max_per_trip = ($is_mtn_flag) ? 7 : 8;
                } else {
                    $max_per_trip = ($is_mtn_flag) ? 3 : 4;
                }

                $cls_trips = ceil($actual_vol_or_weight / $max_per_trip);

                if ($is_stone_material) {
                    if ($is_mtn_flag) {
                        $base_cost = ($actual_vol_or_weight > 6) ? 200 : 80;
                        $trips = ($actual_vol_or_weight > 6) ? 1 : $cls_trips;
                    } else {
                        if ($actual_vol_or_weight > 8) {
                            $base_cost = (isset($gaskins_rates[$zip])) ? $gaskins_rates[$zip] : 150;
                            $trips = 1;
                        } else {
                            $base_cost = (isset($cls_rates[$zip])) ? $cls_rates[$zip] : 75;
                            $trips = $cls_trips;
                        }
                    }
                } else {
                    if ($is_mtn_flag) {
                        $base_cost = ($cls_trips > 2) ? 200 : 80;
                    } else {
                        if ($cls_trips > 2) {
                            $base_cost = (isset($gaskins_rates[$zip])) ? $gaskins_rates[$zip] : 150;
                        } else {
                            $base_cost = (isset($cls_rates[$zip])) ? $cls_rates[$zip] : 100;
                        }
                    }
                    $trips = ($cls_trips > 2) ? 1 : $cls_trips;
                }

                $cost = $base_cost * $trips;

                if (WC()->session->get('erick_fulfillment') !== 'pickup') {
                    if (!in_array($product_id, $no_delivery_fee)) {
                        $cart->add_fee('DELIVERY: ' . strtoupper(strip_tags($product->get_title())), $cost, false);
                        $total_delivery_cost += $cost;
                    }
                }
            }
        }

        if ($comp['loose_qty'] === 0 && $comp['bagged_qty'] > 0) {
            foreach ($comp['bagged_names'] as $b_name => $b_qty) { $cart->add_fee('IN-STORE PICKUP REQUIRED FOR ' . $b_name, 0, false); }
        } elseif ($comp['loose_qty'] > 0 && $comp['bagged_qty'] > 10) {
            foreach ($comp['bagged_names'] as $b_name => $b_qty) { $cart->add_fee('IN-STORE PICKUP REQUIRED FOR ' . $b_name, 0, false); }
        } elseif ($comp['has_mower']) {
            $cart->add_fee('IN-STORE PICKUP REQUIRED FOR MOWER', 0, false);
        } elseif ($comp['has_power_tool']) {
            $cart->add_fee('IN-STORE PICKUP REQUIRED FOR POWER TOOL', 0, false);
        }

        $subtotal = $cart->get_subtotal();
        $is_pickup = (WC()->session->get('erick_fulfillment') === 'pickup' || ($comp['loose_qty'] === 0 && $comp['bagged_qty'] > 0) || $comp['has_mower'] || $comp['has_power_tool']);
        
        $is_pickup_locked = false;
        foreach ($cart->get_cart() as $item) {
            if (in_array($item['product_id'], $no_delivery_fee)) {
                $is_pickup_locked = true;
                break;
            }
        }
        
        // --- STRICT ISOLATION FOR POWER TOOLS AND MOWERS (TAX FIX) ---
        // Force pickup state to remain true for tax calculation, bypassing any contradictory admin settings
        if ($comp['has_power_tool'] || $comp['has_mower']) {
            $is_pickup_locked = false;
            $is_pickup = true;
        } elseif ($is_pickup_locked) {
            $is_pickup = false;
        }
        // -------------------------------------------------------------
        
        if ($is_pickup) {
            $tax_amount = $subtotal * 0.07;
            $cart->add_fee('SALES TAX (7%)', $tax_amount, false);
        } elseif ($zip && $has_valid_zip) {
            $tax_rate = ($zip === '25414') ? 0.07 : 0.06;
            $tax_amount = ($subtotal + $total_delivery_cost) * $tax_rate;
            $cart->add_fee('SALES TAX (' . (($zip === '25414') ? '7%' : '6%') . ')', $tax_amount, false);
        } else { $tax_amount = 0; }
        
        $current_total = $subtotal + $total_delivery_cost + $tax_amount;
        if ($current_total > 0) { $cart->add_fee('HANDLING FEE (3.5%)', $current_total * 0.035, false); }
    }

    public function custom_calculate_shipping($rates, $package) {
        if ($this->should_skip_logistics()) return [];
        $comp = $this->get_cart_composition();
        if ($comp['loose_qty'] === 0 || WC()->session->get('erick_fulfillment') === 'pickup' || $comp['has_mower'] || $comp['has_power_tool']) return [];
        $zip = WC()->session->get('cls_validated_zip') ?: WC()->customer->get_shipping_postcode();
        $allowed_zips = array_filter(array_map('trim', explode("\n", get_option('cls_allowed_zips', ''))));
        if (empty($zip) || !in_array($zip, $allowed_zips)) return [];
        $label = ($comp['loose_types'] > 1) ? 'Multiple Truck Delivery' : 'Single Truck Delivery';
        return ['custom_logistics' => new WC_Shipping_Rate('custom_logistics', $label, 0, [], 'custom_logistics')];
    }

    public function erick_global_cart_watcher_js() {
        if ($this->should_skip_logistics()) return;
        
        $stored_zip = WC()->session ? (WC()->session->get('cls_validated_zip') ?: WC()->customer->get_shipping_postcode()) : '';
        $allowed_zips = array_filter(array_map('trim', explode("\n", get_option('cls_allowed_zips', ''))));
        $is_zip_valid = (!empty($stored_zip) && in_array($stored_zip, $allowed_zips)) ? 'true' : 'false';
        $fulfillment_mode = WC()->session ? (WC()->session->get('erick_fulfillment') ?: 'delivery') : 'delivery';
        ?>
        <script>
            jQuery(document).ready(function($) {
                var isZipValid = <?php echo $is_zip_valid; ?>;
                var fulfillmentMode = '<?php echo esc_js($fulfillment_mode); ?>';

                function enforceCheckoutLock() {
                    if (fulfillmentMode === 'delivery' && !isZipValid) {
                        $('.checkout-button, #place_order').addClass('disabled').css({
                            'opacity': '0.5',
                            'pointer-events': 'none',
                            'cursor': 'not-allowed'
                        }).on('click.ziplock', function(e) {
                            e.preventDefault();
                            return false;
                        });
                    } else {
                        $('.checkout-button, #place_order').removeClass('disabled').css({
                            'opacity': '',
                            'pointer-events': '',
                            'cursor': ''
                        }).off('click.ziplock');
                    }
                }

                function applyUIFixes() {
                    var taxRow = $('tr.fee').filter(function() {
                        return $(this).text().toUpperCase().indexOf('SALES TAX') !== -1;
                    });
                    var handlingRow = $('tr.fee').filter(function() {
                        return $(this).text().toUpperCase().indexOf('HANDLING FEE') !== -1;
                    });
                    var totalRow = $('tr.order-total');
                    if ((taxRow.length || handlingRow.length) && totalRow.length) {
                        var totalFee = 0;
                        var breakdownHTML = '';
                        var currencySym = '$';

                        function processRow(row) {
                            if (row.length) {
                                var amountHTML = row.find('td').html();
                                var amountText = row.find('td').text().trim();
                                var rawNum = amountText.replace(/[^0-9.]/g, '');
                                totalFee += parseFloat(rawNum) || 0;
                                var rowName = row.find('th').text().trim();
                                var sym = row.find('.woocommerce-Price-currencySymbol').first().text();
                                if (sym) currencySym = sym;
                                breakdownHTML += '<div style="display:flex; justify-content:space-between; margin-top:6px; font-weight:normal; font-size:0.95em; color:#555;"><span>' + rowName + '</span><span>' + amountHTML + '</span></div>';
                                row.hide();
                            }
                        }
                        $('.erick-combined-fee-row').remove();
                        processRow(taxRow);
                        processRow(handlingRow);
                        var combinedRow = $('<tr class="fee erick-combined-fee-row"><th style="padding-bottom:10px;"><span class="erick-fee-toggle" style="cursor:pointer; display:inline-block; user-select:none; border-bottom:1px dashed #999; padding-bottom:2px;">TAXES & FEES <span class="erick-fee-caret" style="font-size:0.8em; margin-left:4px;">&#9660;</span></span><div class="erick-fee-details" style="display:none; margin-top:10px; padding:10px; background:#fafafa; border-radius:6px; border:1px solid #eee;">' + breakdownHTML + '</div></th><td data-title="TAXES & FEES"><span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">' + currencySym + '</span>' + totalFee.toFixed(2) + '</bdi></span></td></tr>');
                        combinedRow.insertBefore(totalRow);
                    }
                    $('.cart-collaterals th, .shop_table th').each(function() {
                        var el = $(this);
                        var htmlContent = el.html();
                        if (htmlContent && htmlContent.toUpperCase().indexOf('SHIPMENT') !== -1) {
                            el.html(htmlContent.replace(/SHIPMENT/g, 'DELIVERY MODE').replace(/Shipment/g, 'Delivery Mode'));
                        }
                    });
                    enforceCheckoutLock();
                }

                $(document.body).on('updated_cart_totals updated_checkout', function() {
                    applyUIFixes();
                });
                $(document).on('click', '.erick-fee-toggle', function() {
                    $(this).next('.erick-fee-details').slideToggle(200);
                    var arrow = $(this).find('.erick-fee-caret');
                    arrow.html(arrow.html() === '▼' ? '▲' : '▼');
                });
                
                applyUIFixes();
            });
        </script>
        <?php
    }

    public function erick_ajax_update_state() {
        if (!WC()->session->has_session()) {
            WC()->session->set_customer_session_cookie(true);
        }
        if (isset($_POST['fulfillment'])) WC()->session->set('erick_fulfillment', sanitize_text_field($_POST['fulfillment']));
        if (isset($_POST['is_mountain'])) WC()->session->set('is_mountain', sanitize_text_field($_POST['is_mountain']));
        WC()->session->set('shipping_method_counts', array());
        $packages = WC()->cart->get_shipping_packages();
        foreach ($packages as $key => $v) {
            unset(WC()->session->{"shipping_for_package_$key"});
        }
        WC()->cart->calculate_totals();
        wp_send_json_success();
    }

    public function erick_fulfillment_toggle_ui() {
        if (!WC()->cart || WC()->cart->is_empty() || $this->should_skip_logistics()) return;
        $comp = $this->get_cart_composition();
        
        $is_locked = (($comp['loose_qty'] === 0 && $comp['bagged_qty'] > 0) || $comp['has_mower'] || $comp['has_power_tool']);
        
        $no_delivery_fee = (array)get_option('cls_no_delivery_fee_products', []);
        $is_pickup_locked = false;
        foreach (WC()->cart->get_cart() as $item) {
            if (in_array($item['product_id'], $no_delivery_fee)) {
                $is_pickup_locked = true;
                break;
            }
        }
        
        // Ensure Power Tools & ECHO products strictly lock to Pickup, bypassing any conflicting settings
        if ($comp['has_power_tool'] || $comp['has_mower']) {
            $is_locked = true;
            $is_pickup_locked = false;
        }

        if ($is_locked && $is_pickup_locked) {
            $is_locked = false;
        }

        $method = $is_locked ? 'pickup' : (WC()->session->get('erick_fulfillment') ?: 'delivery');
        
        if ($is_locked) {
            WC()->session->set('erick_fulfillment', 'pickup');
        } elseif ($is_pickup_locked && $method === 'pickup') {
            WC()->session->set('erick_fulfillment', 'delivery');
            $method = 'delivery';
        }
        
        $stored_zip = WC()->session->get('cls_validated_zip') ?: WC()->customer->get_shipping_postcode();
        $allowed_zips = array_filter(array_map('trim', explode("\n", get_option('cls_allowed_zips', ''))));
        $is_zip_valid = (!empty($stored_zip) && in_array($stored_zip, $allowed_zips));
        $img_p = plugins_url('image_aa35a3.png', __FILE__);
        $img_d = plugins_url('image_a9db8f.png', __FILE__);
        ?>
        <style>
            .erick-fulfillment-container { border:1px solid #ddd; padding:20px; border-radius:12px; margin-bottom:20px; background:#fff; text-align:center; box-sizing: border-box; }
            .erick-card-wrapper { display:flex; gap:15px; justify-content:center; }
            .erick-card { flex:1; border:2px solid #eee; border-radius:10px; padding:15px; text-align:center; cursor:pointer; transition:0.3s; }
            .erick-card.active { border-color:#8bc34a; background:#f9fff0; }
            .disabled-card { opacity:0.3 !important; filter:grayscale(1) !important; cursor:not-allowed !important; pointer-events:none !important; }
            .erick-is-pickup tr.shipping, .erick-is-pickup .woocommerce-shipping-totals, .erick-is-pickup tr[class*="fulfillment"], .erick-is-pickup .woocommerce-shipping-destination { display:none !important; }
            .pickup-notice { color:#d32f2f; font-weight:bold; font-size:13px; margin-top:15px; display:block; }
            .tax-total, .cart-subtotal + .tax-total { display: none !important; }
            .erick-zip-prompt { margin-top: 20px; background: #f9fff0; border: 1px solid #8bc34a; padding: 15px; border-radius: 8px; box-sizing: border-box; width: 100%; text-align: center !important; }
            .erick-zip-prompt-flex { display: flex !important; flex-direction: row !important; justify-content: center !important; align-items: stretch !important; height: 45px !important; width: 100% !important; max-width: 250px !important; margin: 0 auto !important; box-sizing: border-box !important; }
            #erick_cart_zip_input { width: 130px !important; min-width: 130px !important; padding: 0 15px !important; border: 1px solid #ccc !important; border-right: none !important; border-radius: 4px 0 0 4px !important; text-align: left !important; background: #fff !important; margin: 0 !important; box-sizing: border-box !important; height: 45px !important; }
            #erick_cart_zip_btn { width: 100px !important; min-width: 100px !important; background: #8bc34a !important; color: #fff !important; border: none !important; padding: 0 !important; border-radius: 0 4px 4px 0 !important; cursor: pointer !important; font-weight: bold !important; margin: 0 !important; box-sizing: border-box !important; height: 45px !important; }
        </style>
        <div class="erick-fulfillment-container <?php echo ($method === 'pickup') ? 'erick-is-pickup' : ''; ?>">
            <p style="font-weight:bold; margin-bottom:15px;">How would you like to receive your order?</p>
            <div class="erick-card-wrapper">
                <div class="erick-card <?php echo ($method === 'pickup') ? 'active' : ''; ?> <?php echo $is_pickup_locked ? 'disabled-card' : ''; ?>" <?php echo $is_pickup_locked ? '' : 'onclick="updateFulfillment(\'pickup\')"'; ?>>
                    <img src="<?php echo $img_p; ?>" width="50"><br><strong>Pickup</strong>
                </div>
                <div class="erick-card <?php echo ($method === 'delivery') ? 'active' : ''; ?> <?php echo $is_locked ? 'disabled-card' : ''; ?>" <?php echo $is_locked ? '' : 'onclick="updateFulfillment(\'delivery\')"'; ?>>
                    <img src="<?php echo $img_d; ?>" width="50"><br><strong>Delivery</strong>
                </div>
            </div>
            
            <?php if ($method === 'delivery' && !$is_zip_valid && !$is_locked): ?>
                <div class="erick-zip-prompt">
                    <p style="margin:0 0 10px 0; font-weight:bold; color: #333; text-align:center;">Please Enter Delivery Zip Code:</p>
                    <div class="erick-zip-prompt-flex">
                        <input type="text" id="erick_cart_zip_input" placeholder="Zip Code" maxlength="5" autocomplete="off">
                        <button type="button" id="erick_cart_zip_btn">Verify</button>
                    </div>
                    <div id="erick_cart_zip_msg" style="margin-top:8px; font-size:13px; color:#d32f2f;"></div>
                </div>
                <script>
                    jQuery(document).ready(function($) {
                        function verifyCartZip(zip) {
                            if (zip.length < 5) return;
                            $('#erick_cart_zip_msg').text('Verifying...');
                            $.post(erick_ajax.url, {
                                action: 'cls_check_zip',
                                zip: zip
                            }, function(r) {
                                if (r.success) {
                                    location.reload();
                                } else {
                                    $('#erick_cart_zip_msg').html('❌ Sorry, we do not deliver to ' + zip + '. <a href="#" id="erick_cart_zip_reset" style="color:#d32f2f; font-size:11px; margin-left:10px; text-decoration:underline;">Reset</a>');
                                }
                            });
                        }
                        $('#erick_cart_zip_btn').on('click', function() {
                            verifyCartZip($('#erick_cart_zip_input').val());
                        });
                        $('#erick_cart_zip_input').on('keyup input', function() {
                            var v = $(this).val().replace(/\D/g, '').substring(0, 5);
                            $(this).val(v);
                            if (v.length === 5) verifyCartZip(v);
                        });
                        $(document).on('click', '#erick_cart_zip_reset', function(e) {
                            e.preventDefault();
                            $('#erick_cart_zip_input').val('').focus();
                            $('#erick_cart_zip_msg').html('');
                            $.post(erick_ajax.url, {
                                action: 'cls_reset_zip'
                            });
                        });
                    });
                </script>
            <?php endif; ?>
        </div>
        <script>
            function updateFulfillment(v) {
                jQuery.post(erick_ajax.url, {
                    action: 'erick_update_checkout_state',
                    fulfillment: v
                }, function() {
                    location.reload();
                });
            }
        </script>
    <?php
    }

    public function ajax_get_zip_state() {
        if (isset(WC()->session)) {
            $zip = WC()->session->get('cls_validated_zip');
            if ($zip) {
                wp_send_json_success(['zip' => $zip]);
            }
        }
        wp_send_json_error();
    }

    public function render_zip_checker() {
        if ($this->should_skip_logistics()) return;
        $excluded = (array)get_option('cls_excluded_products', []);
        if (in_array(get_the_ID(), $excluded) || (WC()->session && WC()->session->get('erick_fulfillment') === 'pickup')) return;
    ?>
        <style>
            #cls-zip-checker { width: 100% !important; max-width: 100% !important; margin: 20px auto !important; padding: 20px !important; background: #f1f8e9 !important; border: 2px solid #8bc34a !important; border-radius: 8px !important; box-sizing: border-box !important; clear: both !important; text-align: center !important; }
            .cls-zip-form-inline { display: flex !important; flex-direction: row !important; align-items: stretch !important; justify-content: center !important; height: 45px !important; margin: 15px auto !important; width: 100% !important; max-width: 250px !important; box-sizing: border-box !important; }
            #cls_zip_input { width: 130px !important; min-width: 130px !important; padding: 0 15px !important; border: 1px solid #ccc !important; border-right: none !important; border-radius: 4px 0 0 4px !important; background: #fff !important; height: 45px !important; }
            #cls_zip_btn { width: 100px !important; min-width: 100px !important; background: #8bc34a !important; color: #fff !important; border: none !important; border-radius: 0 4px 4px 0 !important; font-weight: bold !important; cursor: pointer !important; height: 45px !important; display: flex !important; align-items: center !important; justify-content: center !important; padding: 0 !important; margin: 0 !important; font-size: 16px !important; transition: background 0.2s; box-sizing: border-box !important; line-height: 1 !important; }
            #cls_zip_msg { margin-top: 12px !important; font-weight: bold !important; font-size: 14px !important; word-wrap: break-word !important; line-height: 1.4 !important; text-align: center !important; display: block !important; width: 100% !important; }
        </style>
        <div id="cls-zip-checker">
            <h3>Check Delivery Availability:</h3>
            <div class="cls-zip-form-inline"><input type="text" id="cls_zip_input" value="" placeholder="Zip Code" maxlength="5" autocomplete="off"><button type="button" id="cls_zip_btn">Check</button></div>
            <div id="cls_zip_msg"><span style="color:#d32f2f;">⚠️ Check zip code to order.</span></div>
        </div>
        <script>
            jQuery(document).ready(function($) {
                var isValidated = false;

                function lock() {
                    if (!isValidated) {
                        $('.single_add_to_cart_button').addClass('disabled').css({
                            'opacity': '0.5',
                            'pointer-events': 'none'
                        });
                    } else {
                        $('.single_add_to_cart_button').removeClass('disabled').css({
                            'opacity': '1',
                            'pointer-events': 'auto'
                        });
                    }
                }
                lock();
                $(document).on('found_variation reset_data', lock);
                $.post(erick_ajax.url, {
                    action: 'cls_get_zip_state'
                }, function(r) {
                    if (r.success && r.data.zip) {
                        $('#cls_zip_input').val(r.data.zip);
                        isValidated = true;
                        lock();
                        $('#cls_zip_msg').html('<span style="color:#2e7d32;">✅ Validated: We deliver to ' + r.data.zip + '!</span> <a href="#" id="cls_zip_reset" style="color:#d32f2f; font-size:11px; margin-left:10px; text-decoration:underline;">Reset</a>');
                    }
                });

                function validate(zip) {
                    if (zip.length < 5) return;
                    $('#cls_zip_msg').html('Verifying, Please wait...');
                    $.post(erick_ajax.url, {
                        action: 'cls_check_zip',
                        zip: zip
                    }, function(r) {
                        if (r.success) {
                            isValidated = true;
                            lock();
                            $('#cls_zip_msg').html('<span style="color:#2e7d32;">✅ Validated: We deliver to ' + zip + '!</span> <a href="#" id="cls_zip_reset" style="color:#d32f2f; font-size:11px; margin-left:10px; text-decoration:underline;">Reset</a>');
                        } else {
                            isValidated = false;
                            lock();
                            $('#cls_zip_msg').html('<span style="color:#2e7d32;">❌ Sorry, we do not deliver to ' + zip + '.</span> <a href="#" id="cls_zip_reset_error" style="color:#d32f2f; font-size:11px; margin-left:10px; text-decoration:underline;">Reset</a>');
                        }
                    });
                }
                $('#cls_zip_btn').on('click', function() {
                    validate($('#cls_zip_input').val());
                });
                $('#cls_zip_input').on('keyup input', function() {
                    var v = $(this).val().replace(/\D/g, '').substring(0, 5);
                    $(this).val(v);
                    if (v.length === 5) validate(v);
                });
                $(document).on('click', '#cls_zip_reset, #cls_zip_reset_error', function(e) {
                    e.preventDefault();
                    $('#cls_zip_input').val('').focus();
                    $('#cls_zip_msg').html('<span style="color:#d32f2f;">⚠️ Check zip code to order.</span>');
                    isValidated = false;
                    lock();
                    $.post(erick_ajax.url, {
                        action: 'cls_reset_zip'
                    });
                });
            });
        </script>
    <?php
    }

    public function custom_add_mountain_cart() {
        if (!WC()->cart || WC()->cart->is_empty() || $this->should_skip_logistics()) return;
        $comp = $this->get_cart_composition();
        if ($comp['loose_qty'] === 0) return;
        $zip = WC()->session->get('cls_validated_zip') ?: WC()->customer->get_shipping_postcode();
        if (str_replace(' ', '', $zip) !== '25425' || WC()->session->get('erick_fulfillment') === 'pickup') return;
        $current = WC()->session->get('is_mountain') === 'Yes' ? 'Yes' : 'No';
    ?>
        <div id="erick-mountain-check" style="margin:15px 0; padding:15px; border:1px solid #8bc34a; background:#f9fff0; border-radius:8px; text-align: center;">
            <p style="margin:0 0 10px 0; font-weight:bold;">IS THE DELIVERY LOCATION WITHIN THE MOUNTAIN?</p><label style="margin-right:15px;"><input type="radio" name="erick_mountain_toggle" value="Yes" <?php checked($current, 'Yes'); ?>> Yes</label><label><input type="radio" name="erick_mountain_toggle" value="No" <?php checked($current, 'No'); ?>> No</label>
        </div>
        <script>
            jQuery(document).ready(function($) {
                $(document).on('change', 'input[name="erick_mountain_toggle"]', function() {
                    $.post(erick_ajax.url, {
                        action: 'erick_update_checkout_state',
                        is_mountain: $(this).val()
                    }, function() {
                        location.reload();
                    });
                });
            });
        </script>
    <?php
    }

    public function erick_huge_order_modal_html() {
        if (!WC()->cart || $this->should_skip_logistics()) return;
        $comp = $this->get_cart_composition();
        if ($comp['loose_qty'] === 0) return;
        $zip = WC()->session->get('cls_validated_zip') ?: WC()->customer->get_shipping_postcode();
        $zip = str_replace(' ', '', $zip);
        $restricted_zips = ['20180', '22611', '22601', '22602', '22603', '22604', '21641', '20175', '20176', '20177', '20178', '20135'];
        $trigger_modal = false;
        foreach (WC()->cart->get_cart() as $item) {
            $name = strtolower($item['data']->get_name());
            $qty = $item['quantity'];
            $multiplier = 1;
            if (preg_match('/( |1\/2|0\.5)/ui', $name)) {
                $multiplier = 0.5;
            } elseif (preg_match('/( |1\/4|0\.25)/ui', $name)) {
                $multiplier = 0.25;
            } elseif (preg_match('/( |3\/4|0\.75)/ui', $name)) {
                $multiplier = 0.75;
            }
            $actual_qty = $qty * $multiplier;
            $is_mulch = strpos($name, 'mulch') !== false;
            $tons = (strpos($name, 'ton') !== false || strpos($name, 'tn') !== false) ? $actual_qty : 0;
            $yards = (preg_match('/(yard|cu\.?\s*yd)/i', $name) || $is_mulch) ? $actual_qty : 0;
            if ($is_mulch) {
                if ($yards > 25) {
                    $trigger_modal = true;
                    break;
                }
            } elseif ($yards > 0) {
                if ($yards > 20) {
                    $trigger_modal = true;
                    break;
                }
            } elseif ($tons > 0) {
                $limit = in_array($zip, $restricted_zips) ? 14 : 20;
                if ($tons > $limit) {
                    $trigger_modal = true;
                    break;
                }
            }
        }
        if ($trigger_modal && WC()->session->get('erick_fulfillment') !== 'pickup'):
        ?>
            <div id="erick-huge-order-modal" style="position:fixed; z-index:999999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.85); backdrop-filter:blur(5px); display:flex; justify-content:center; align-items:flex-start; padding-top:10vh;">
                <div style="background:#fff; padding:40px; border-radius:15px; width:90%; max-width:450px; text-align:center; border-top: 8px solid #d32f2f; position:relative;"><span onclick="jQuery('#erick-huge-order-modal').fadeOut()" style="position:absolute; top:10px; right:15px; font-size:28px; font-weight:bold; color:#000; cursor:pointer; line-height: 1;">&times;</span>
                    <div style="font-size:40px; margin-bottom:10px;">🚚</div>
                    <h2 style="font-size:32px; margin-bottom:20px;">Large Order Notice</h2>
                    <p style="font-size:16px; margin-bottom:25px;">Thank you for this huge order. Allow us to coordinate your delivery, please call:</p><a href="tel:+13047070437" style="background:#77b235; color:#fff !important; padding:15px 30px; border-radius:50px; display:inline-block; font-weight:bold; text-decoration:none !important; font-size:1.2em; margin-bottom:25px;">📞 +1 (304) 707-0437</a><span onclick="jQuery('#erick-huge-order-modal').fadeOut()" style="display:block; cursor:pointer; text-decoration:underline; color:#888; font-size: 0.95em;">If this was by mistake, close and adjust quantity</span>
                </div>
            </div>
            <style>
                .checkout-button,
                #place_order {
                    display: none !important;
                }
            </style>
    <?php endif;
    }

    public function ajax_check_zip() {
        $zip = isset($_POST['zip']) ? sanitize_text_field($_POST['zip']) : '';
        $allowed = array_filter(array_map('trim', explode("\n", get_option('cls_allowed_zips', ''))));
        if (in_array($zip, $allowed)) {
            if (isset(WC()->session)) {
                if (!WC()->session->has_session()) WC()->session->set_customer_session_cookie(true);
                WC()->session->set('cls_validated_zip', $zip);
                WC()->customer->set_shipping_postcode($zip);
                WC()->customer->save();
                WC()->session->save_data();
            }
            wp_send_json_success();
        }
        wp_send_json_error();
    }

    public function ajax_reset_zip() {
        if (isset(WC()->session)) {
            WC()->session->set('cls_validated_zip', null);
            WC()->customer->set_shipping_postcode('');
            WC()->customer->save();
            WC()->session->save_data();
        }
        wp_send_json_success();
    }

    public function enforce_logistics_and_huge_order($passed, $p_id, $qty) {
        $product = wc_get_product($p_id);
        if (!$product || strpos(strtolower($product->get_name()), 'shed') !== false || empty($p_id) || get_post_type($p_id) !== 'product' || $this->should_skip_logistics()) return $passed;

        $is_adding_power_tool = strpos(strtolower($product->get_name()), 'echo') !== false || strpos(strtolower($product->get_name()), 'power tool') !== false;
        $is_adding_mower = strpos(strtolower($product->get_name()), 'mower') !== false;
        $adding_pickup_only = (array)get_option('cls_pickup_only_products', []);
        $is_adding_explicit_pickup = in_array($p_id, $adding_pickup_only);

        $is_adding_delivery_item = !($is_adding_power_tool || $is_adding_mower || $is_adding_explicit_pickup);

        $cart_has_delivery_item = false;
        $cart_has_power_tool = false;
        
        if (WC()->cart && !WC()->cart->is_empty()) {
            foreach (WC()->cart->get_cart() as $item) {
                $item_name = strtolower($item['data']->get_name());
                $item_id = $item['product_id'];
                
                if (strpos($item_name, 'echo') !== false || strpos($item_name, 'power tool') !== false) {
                    $cart_has_power_tool = true;
                } else {
                    $item_is_mower = strpos($item_name, 'mower') !== false;
                    $item_is_explicit = in_array($item_id, $adding_pickup_only);
                    if (!$item_is_mower && !$item_is_explicit) {
                        $cart_has_delivery_item = true;
                    }
                }
            }
        }

        if ($is_adding_power_tool && $cart_has_delivery_item) {
            wc_add_notice('Power tools and ECHO products are for in-store pickup only and cannot be combined with delivery items. Please purchase them separately.', 'error');
            return false;
        }
        if ($is_adding_delivery_item && $cart_has_power_tool) {
            wc_add_notice('Your cart contains a power tool (in-store pickup only). Please checkout first before adding delivery items.', 'error');
            return false;
        }

        $zip = WC()->session->get('cls_validated_zip') ?: WC()->customer->get_shipping_postcode();
        $zip = str_replace(' ', '', $zip);
        $restricted_zips = ['20180', '22611', '22601', '22602', '22603', '22604', '21641', '20175', '20176', '20177', '20178', '20135'];
        foreach (WC()->cart->get_cart() as $item) {
            $name = strtolower($item['data']->get_name());
            $item_qty = $item['quantity'];
            $multiplier = 1;
            if (preg_match('/( |1\/2|0\.5)/ui', $name)) {
                $multiplier = 0.5;
            } elseif (preg_match('/( |1\/4|0\.25)/ui', $name)) {
                $multiplier = 0.25;
            } elseif (preg_match('/( |3\/4|0\.75)/ui', $name)) {
                $multiplier = 0.75;
            }
            $actual_qty = $item_qty * $multiplier;
            $is_mulch = strpos($name, 'mulch') !== false;
            $tons = (strpos($name, 'ton') !== false || strpos($name, 'tn') !== false) ? $actual_qty : 0;
            $yards = (preg_match('/(yard|cu\.?\s*yd)/i', $name) || $is_mulch) ? $actual_qty : 0;
            if ($is_mulch && $yards > 25) {
                wc_add_notice('Limit exceeded for ' . $item['data']->get_name() . '. Please call to coordinate huge orders.', 'error');
                return false;
            } elseif ($yards > 0 && $yards > 20) {
                wc_add_notice('Limit exceeded for ' . $item['data']->get_name() . '. Please call to coordinate huge orders.', 'error');
                return false;
            } elseif ($tons > 0) {
                $limit = in_array($zip, $restricted_zips) ? 14 : 20;
                if ($tons > $limit) {
                    wc_add_notice('Limit exceeded for ' . $item['data']->get_name() . '. Please call to coordinate huge orders.', 'error');
                    return false;
                }
            }
        }
        $pickup_only = (array)get_option('cls_pickup_only_products', []);
        $terms = wp_get_post_terms($p_id, 'product_cat', ['fields' => 'slugs']);
        if (is_wp_error($terms)) $terms = [];
        
        $is_mower = strpos(strtolower($product->get_name()), 'mower') !== false;
        $is_power_tool = strpos(strtolower($product->get_name()), 'echo') !== false || strpos(strtolower($product->get_name()), 'power tool') !== false;
        $is_explicit_bagged = in_array('bulk-bagged-materials', $terms) || in_array($p_id, $pickup_only) || $is_mower || $is_power_tool;
        
        if (!$is_explicit_bagged && WC()->session->get('erick_fulfillment') !== 'pickup' && !WC()->session->get('cls_validated_zip')) {
            if ($this->get_cart_composition()['loose_qty'] > 0) {
                wc_add_notice('Verify zip code.', 'error');
                return false;
            }
        }
        return $passed;
    }

    public function erick_enqueue_scripts() {
        wp_localize_script('jquery', 'erick_ajax', ['url' => admin_url('admin-ajax.php')]);
    }

    public function prefill_checkout_zip($v, $i) {
        if (($i === 'billing_postcode' || $i === 'shipping_postcode') && WC()->session->get('cls_validated_zip')) return WC()->session->get('cls_validated_zip');
        return $v;
    }

    public function register_ready_for_pickup_status() {
        register_post_status('wc-ready-for-pickup', ['label' => 'Ready for Pickup', 'public' => true, 'show_in_admin_all_list' => true, 'show_in_admin_status_list' => true]);
    }

    public function add_status_to_list($s) {
        $s['wc-ready-for-pickup'] = 'Ready for Pickup';
        return $s;
    }

    public function erick_force_text_translations($t, $text, $d) {
        if (is_admin()) return $t;
        $text_lower = strtolower($text);
        if ($text_lower === 'shopping bag') return 'Shopping Cart';
        if ($text === 'Shipping') return 'Delivering';
        if ($text === 'SHIPPING') return 'DELIVERING';
        if ($text === 'shipping') return 'delivering';
        if ($text === 'Shipment') return 'Delivery Mode';
        if ($text === 'SHIPMENT') return 'DELIVERY MODE';
        if ($text === 'shipment') return 'delivery mode';
        if ($text === 'Shipping to %s.') return 'Delivering to %s.';
        if ($text === 'Shipping to %s') return 'Delivering to %s';
        return $t;
    }
}
new IBAW_CLS_Logistics_Manager();