<?php

/**
 * Plugin Name: IBAW - Power Tool Inventory - Master
 * Description: Specialized inventory solution for power tools with advanced features.
 * Version:     1.9.5
 * Author:      Erick Villeta
 * Plugin URI:  https://ericksonvilleta.com
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ==========================================================================
   1. CORE REGISTRATION (CPT, TAXONOMY, SOLD STATUS)
   ========================================================================== */

function register_power_tool_system() {
    register_post_type("power_tool_lot", [
        "labels" => [
            "name"             => "ECHO Power Tool Inventory",
            "singular_name"    => "ECHO Power Tool",
            "menu_name"        => "Power Tool Inventory",
            "add_new"          => "Add New",
            "add_new_item"     => "Add New ECHO Power Tool",
            "edit_item"        => "Edit ECHO Power Tool",
            "new_item"         => "New ECHO Power Tool",
            "view_item"        => "View ECHO Power Tool",
            "all_items"        => "All ECHO Power Tools",
            "search_items"     => "Search ECHO Power Tools",
            "not_found"        => "No power tools found."
        ],
        "public" => true,
        "menu_icon" => "dashicons-hammer",
        "supports" => ["title", "editor", "thumbnail", "revisions"],
        "has_archive" => true,
        "show_in_rest" => true,
    ]);

    register_taxonomy("power_tool_category", ["power_tool_lot"], [
        "hierarchical" => true,
        "labels" => ["name" => "Power Tool Categories", "singular_name" => "Power Tool Category"],
        "show_ui" => true, 
        "show_admin_column" => true, 
        "show_in_rest" => true,
    ]);

    register_post_status('sold', [
        'label' => _x('Mark as Sold', 'post'), 
        'public' => false, 
        'exclude_from_search' => true,
        'show_in_admin_all_list' => true, 
        'show_in_admin_status_list' => true,
    ]);
}
add_action('init', 'register_power_tool_system');

/* ==========================================================================
   2. ADMIN META BOXES (PDF, STATUS, BANNERS)
   ========================================================================== */

add_action('add_meta_boxes', function() {
    add_meta_box('pt_pdf_box', 'PDF Spec Sheet', 'render_pt_pdf_box', 'power_tool_lot', 'side');
    add_meta_box('pt_status_box', 'Availability Status', 'render_pt_status_box', 'power_tool_lot', 'side');
});

function render_pt_pdf_box($post) {
    $url = get_post_meta($post->ID, '_pt_spec_sheet_url', true);
    wp_nonce_field('save_pt_meta', 'pt_meta_nonce');
    echo '<input type="text" name="pt_pdf_url" value="'.esc_attr($url).'" style="width:100%;" placeholder="PDF URL">';
}

function render_pt_status_box($post) {
    $is_sold = get_post_meta($post->ID, '_is_sold', true);
    echo '<label><input type="checkbox" name="pt_is_sold" value="1" '.checked($is_sold, 1, false).'> <strong>Mark as SOLD</strong></label>';
}

add_action('save_post', function($post_id) {
    if (!isset($_POST['pt_meta_nonce']) || !wp_verify_nonce($_POST['pt_meta_nonce'], 'save_pt_meta')) return;
    update_post_meta($post_id, '_pt_spec_sheet_url', esc_url_raw($_POST['pt_pdf_url']));
    update_post_meta($post_id, '_is_sold', isset($_POST['pt_is_sold']) ? 1 : 0);
});

add_action('admin_footer', function() {
    $screen = get_current_screen(); 
    if (!$screen || $screen->post_type !== 'power_tool_lot') return;
?>
<script>
jQuery(document).ready(function($){
    function insertPowerToolBanner() {
        if ($('.power-tool-locked-banner').length) return; 
        var group = $('.acf-field-group, .acf-postbox').first();
        if (group.length) { 
            var banner = `<div class="power-tool-locked-banner" style="border: 2px solid #c9bfa5; background: #d9cfb8; padding: 20px; margin: 10px 0 15px 0; text-align: center;"><div style="font-size:16px;font-weight:700;color:#333;">REQUIRED DIMENSIONS: 1200x900px</div></div>`; 
            group.before(banner); 
            return true; 
        } 
        return false;
    }
    var attempts = 0; 
    var interval = setInterval(function(){ 
        if (insertPowerToolBanner()) clearInterval(interval); 
        if (attempts++ > 40) clearInterval(interval); 
    }, 250);
});
</script>
<?php
});

/* ==========================================================================
   3. SHORTCODES (FILTERS, GRID, SPECS, WARRANTY, GALLERY, BUY, PRICE, YOUTUBE)
   ========================================================================== */

add_shortcode('power_tool_inventory_filters', function() {
    global $wpdb;
    $terms = get_terms(['taxonomy' => 'power_tool_category', 'hide_empty' => false]);
    $prices = $wpdb->get_col("SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = 'power_tool_price' AND meta_value != ''");
    $max_p = !empty($prices) ? ceil(max(array_map('floatval', $prices)) / 100) * 100 : 10000;

    ob_start(); ?>
    <div class="mower-sidebar-filters" style="max-width:350px; background:#fff; padding:20px; border:2px solid #eee; border-radius:10px; font-family:sans-serif;">
        <div style="margin-bottom:25px;">
            <label style="display:block; font-weight:bold; margin-bottom:10px; font-size:14px;">Sort By</label>
            <select class="power-sort-dropdown" style="width:100%; padding:12px; border-radius:5px; border:1px solid #ddd; margin-bottom:15px;">
                <option value="az">A to Z</option>
                <option value="price-asc">Price: Low to High</option>
                <option value="price-desc">Price: High to Low</option>
            </select>
            <button class="clear-power-filters" style="width:100%; padding:12px; background:#f8f8f8; color:#555; border:1px solid #bbb; border-radius:5px; font-weight:700; cursor:pointer; text-transform:uppercase; font-size:12px;">RESET ALL FILTERS</button>
        </div>
        <div style="margin-bottom:25px; padding:15px 0; border-top:1px solid #eee; border-bottom:1px solid #eee;">
            <label style="display:block; font-weight:bold; margin-bottom:10px; font-size:14px;">Max Price: <span class="power-price-display" style="color:#81B716;">$<?php echo number_format($max_p); ?></span></label>
            <input type="range" class="power-price-range" min="0" max="<?php echo $max_p; ?>" value="<?php echo $max_p; ?>" step="100" style="width:100%; cursor:pointer; accent-color:#81B716;">
        </div>
        <div style="padding-top:15px; border-top:1px solid #eee; display:flex; flex-direction:column; gap:10px;">
            <label style="font-weight:bold; margin-bottom:5px; font-size:14px;">Filter by Category</label>
            <button class="power-filter-btn active" data-filter="all" style="width:100%; padding:12px; border-radius:30px; border:none; background:#81B716; color:#fff; font-weight:600; cursor:pointer;">All Categories</button>
            <?php foreach($terms as $t): ?>
                <button class="power-filter-btn" data-filter="cat-<?php echo esc_attr($t->slug); ?>" style="width:100%; padding:12px; border-radius:30px; border:1px solid #ddd; background:#fff; cursor:pointer; font-weight:600; color:#333;"><?php echo esc_html($t->name); ?></button>
            <?php endforeach; ?>
        </div>
    </div>
    <script>
    jQuery(document).ready(function($){
        const defMax = <?php echo $max_p; ?>;
        
        function applyFilters() {
            let parsedPrice = parseFloat($('.power-price-range').first().val());
            const maxPrice = isNaN(parsedPrice) ? defMax : parsedPrice;
            const selectedCat = $('.power-filter-btn.active').first().attr('data-filter') || 'all';
            const sortBy = $('.power-sort-dropdown').first().val() || 'az';

            $('.power-price-display').text('$' + maxPrice.toLocaleString());
            
            const grid = $('#main-power-grid');
            let cards = grid.children('.power-card').get();

            // 1. Filter loop
            $.each(cards, function(i, el){
                const card = $(el);
                const cPrice = parseFloat(card.attr('data-price')) || 0;
                const cCat = card.attr('data-cat') || 'all';
                
                const matchCat = (selectedCat === 'all' || cCat === selectedCat);
                const matchPrice = (cPrice <= maxPrice);
                
                if (matchCat && matchPrice) { card.show(); } else { card.hide(); }
            });

            // 2. Sort loop
            cards.sort(function(a, b) {
                const priceA = parseFloat($(a).attr('data-price')) || 0;
                const priceB = parseFloat($(b).attr('data-price')) || 0;
                const titleA = $(a).find('h3').text().trim();
                const titleB = $(b).find('h3').text().trim();

                if (sortBy === 'price-asc') return priceA - priceB;
                if (sortBy === 'price-desc') return priceB - priceA;
                return titleA.localeCompare(titleB, undefined, {numeric: true, sensitivity: 'base'});
            });

            // 3. Re-append to DOM
            $.each(cards, function(i, el) { grid.append(el); });
        }

        // Apply on load
        applyFilters();

        // Using body delegation exactly like mower.php
        $('body').on('input change', '.power-price-range', function() { 
            $('.power-price-range').val($(this).val()); 
            applyFilters(); 
        });
        
        $('body').on('change', '.power-sort-dropdown', function() { 
            $('.power-sort-dropdown').val($(this).val()); 
            applyFilters(); 
        });
        
        $('body').on('click', '.power-filter-btn', function(e){ 
            e.preventDefault(); 
            const filterType = $(this).attr('data-filter'); 
            $('.power-filter-btn').css({'background':'#fff','color':'#333','border':'1px solid #ddd'}).removeClass('active'); 
            $('.power-filter-btn[data-filter="'+filterType+'"]').css({'background':'#81B716','color':'#fff','border':'none'}).addClass('active'); 
            applyFilters(); 
        });
        
        $('body').on('click', '.clear-power-filters', function(e){ 
            e.preventDefault(); 
            $('.power-filter-btn').css({'background':'#fff','color':'#333','border':'1px solid #ddd'}).removeClass('active'); 
            $('.power-filter-btn[data-filter="all"]').css({'background':'#81B716','color':'#fff','border':'none'}).addClass('active'); 
            $('.power-price-range').val(defMax); 
            $('.power-sort-dropdown').val('az'); 
            applyFilters(); 
        });
    });
    </script>
    <?php return ob_get_clean();
});

add_shortcode('power_tool_inventory_grid', function() {
    $tools = get_posts(['post_type' => 'power_tool_lot', 'posts_per_page' => -1]);
    if (empty($tools)) return '<p>No power tools available.</p>';
    
    $output = '<div id="main-power-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">';
    foreach ($tools as $post) {
        $price = floatval(preg_replace('/[^0-9.]/', '', get_field('power_tool_price', $post->ID)));
        $is_sold = get_post_meta($post->ID, '_is_sold', true);
        $terms = get_the_terms($post->ID, 'power_tool_category');
        $cat = !empty($terms) && !is_wp_error($terms) ? 'cat-' . $terms[0]->slug : 'all';
        
        $output .= sprintf(
            '<div class="power-card" data-cat="%s" data-price="%s" style="position:relative; border:1px solid #ddd; border-radius:8px; padding:15px; background:#fff;">
                %s
                %s
                <h3 style="margin:10px 0 5px; font-size:18px;">%s</h3>
                <p style="color:#81B716; font-weight:bold; font-size:18px;">$%s</p>
                <a href="%s" style="display:block; text-align:center; background:#81B716; color:#fff; padding:10px; border-radius:4px; text-decoration:none;">VIEW DETAILS</a>
            </div>',
            esc_attr($cat), esc_attr($price), ($is_sold ? '<div style="position:absolute; background:red; color:#fff; padding:5px; z-index:5;">SOLD</div>' : ''),
            get_the_post_thumbnail($post->ID, 'medium', ['style' => 'width:100%; height:200px; object-fit:cover;']),
            get_the_title($post->ID), 
            number_format($price, 2), 
            get_permalink($post->ID)
        );
    }
    return $output . '</div>';
});

add_shortcode('power_tool_retail_price', function() {
    $post_id = get_the_ID();
    if (!$post_id) return '';
    $price = get_field('power_tool_price', $post_id); 
    $raw_p = $price ? floatval(preg_replace('/[^0-9.]/', '', $price)) : 0;
    return $raw_p ? '<div class="power-price-tag" style="font-size:32px; font-weight:800; color:#81B716;">$' . number_format($raw_p, 2) . '</div>' : ''; 
});

add_shortcode('power_tool_features_grid', function() {
    $post_id = get_the_ID();
    if (!$post_id) return '';

    $name = get_field('power_tool_name_and_model', $post_id);
    if (!$name) $name = get_field('power_tool_name_&_model', $post_id);
    
    $sku = get_field('manufacturer_part_number_oem__stock_keeping_unit_sku', $post_id);
    $info = get_field('power_tool_info', $post_id);

    $output = '<div class="power-features-container">';
    
    if ($name) {
        $output .= '<h2 class="power-feature-name">' . esc_html($name) . '</h2>';
    }
    
    if ($sku) {
        $output .= '<p class="power-feature-sku"><strong>' . esc_html($sku) . '</strong></p>';
    }
    
    if ($info) {
        $output .= '<div class="power-feature-info">' . wp_kses_post($info) . '</div>';
    }
    
    $output .= '</div>';
    
    return $output;
});

add_shortcode('power_tool_specs_table', function() {
    $post_id = get_the_ID();
    if (!$post_id) return '';
    
    $fields = get_field_objects($post_id);
    if (!$fields || !is_array($fields)) return ''; 

    $output = '<div class="power-spec-table-block" style="margin-bottom:35px;">';
    $output .= '<h3 style="font-size:16px; font-weight:700; text-transform:uppercase; color:#81B716; border-bottom:2px solid #81B716; padding-bottom:6px;">Technical Specifications</h3>';
    $output .= '<table style="width:100%; border-collapse:collapse; border:1px solid #eaeaea;"><tbody>';
    
    $excluded_fields = [
        'tool_warranty', 
        'battery_warranty', 
        'warranty_info',
        'power_tool_name_and_model', 
        'power_tool_name_&_model', 
        'power_tool_info', 
        'power_tool_price',
        'manufacturer_part_number_oem__stock_keeping_unit_sku',
        'youtube_link',
        'features_grid'
    ];

    foreach ($fields as $field) {
        if (in_array($field['name'], $excluded_fields) || in_array($field['type'], ['tab', 'repeater', 'gallery', 'image'])) continue;
        
        if (!empty($field['value'])) {
            $output .= sprintf(
                '<tr><td style="padding:12px; font-weight:600; border-bottom:1px solid #eaeaea; width:45%%;">%s</td><td style="padding:12px; border-bottom:1px solid #eaeaea;">%s</td></tr>', 
                esc_html($field['label']), 
                esc_html($field['value'])
            );
        }
    }
    return $output . '</tbody></table></div>';
});

add_shortcode('power_tool_warranty_display', function() {
    $post_id = get_the_ID();
    if (!$post_id) return '';

    $warranties = [
        'Tool Warranty' => get_field('tool_warranty', $post_id),
        'Battery Warranty' => get_field('battery_warranty', $post_id),
        'Warranty' => get_field('warranty_info', $post_id)
    ];
    
    $output = '<div class="power-spec-table-block" style="margin-bottom:35px;">';
    $output .= '<h3 style="font-size:16px; font-weight:700; text-transform:uppercase; color:#81B716; border-bottom:2px solid #81B716; padding-bottom:6px;">Warranty Information</h3>';
    $output .= '<table style="width:100%; border-collapse:collapse; border:1px solid #eaeaea;"><tbody>';
    
    $has_warranty = false;
    foreach ($warranties as $label => $value) {
        if ($value) {
            $output .= sprintf('<tr><td style="padding:12px; font-weight:600; border-bottom:1px solid #eaeaea; width:45%%;">%s</td><td style="padding:12px; border-bottom:1px solid #eaeaea;">%s</td></tr>', esc_html($label), esc_html($value));
            $has_warranty = true;
        }
    }
    
    return $has_warranty ? $output . '</tbody></table></div>' : '';
});

add_shortcode('power_tool_gallery_view', function() {
    $post_id = get_the_ID();
    if (!$post_id) return '';

    $images = get_field('power_tool_gallery', $post_id);
    if (empty($images) || !is_array($images)) return ''; 

    $first_img_id = is_array($images[0]) && isset($images[0]['ID']) ? $images[0]['ID'] : $images[0];

    ob_start(); ?>
    <div class="pcg-container">
        <div id="pt-viewport" style="margin-bottom:10px;">
            <?php echo wp_get_attachment_image($first_img_id, 'large', false, ['style' => 'width:100%; height:auto; display:block; object-fit:contain; background:#f9f9f9;']); ?>
        </div>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <?php foreach ($images as $img) { 
                $img_id = is_array($img) && isset($img['ID']) ? $img['ID'] : $img;
                $thumb_src = wp_get_attachment_image_src($img_id, 'thumbnail');
                $large_src = wp_get_attachment_image_src($img_id, 'large');
                if (!$thumb_src) continue;
            ?>
                <img src="<?php echo esc_url($thumb_src[0]); ?>" 
                     style="width:80px; height:80px; object-fit:cover; cursor:pointer; border:2px solid transparent; border-radius:4px;" 
                     onclick="document.getElementById('pt-viewport').innerHTML = '<img src=\'<?php echo esc_url($large_src[0]); ?>\' style=\'width:100%; height:auto; display:block; object-fit:contain; background:#f9f9f9;\'>'">
            <?php } ?>
        </div>
    </div>
    <?php return ob_get_clean();
});

add_shortcode('power_tool_youtube_button', function() {
    $post_id = get_the_ID();
    if (!$post_id) return '';
    $link = get_field('youtube_link', $post_id);
    
    if (is_array($link) && isset($link['url']) && !empty($link['url'])) {
        parse_str(parse_url($link['url'], PHP_URL_QUERY), $query);
        $video_id = isset($query['v']) ? $query['v'] : basename($link['url']);
        $embed_url = 'https://www.youtube.com/embed/' . $video_id . '?autoplay=1';
        
        ob_start(); ?>
        <a href="#" class="pt-yt-btn" data-src="<?php echo esc_url($embed_url); ?>" style="display:inline-flex; align-items:center; gap:8px; padding:12px 24px; background:#FF0000; color:#fff; text-decoration:none; border-radius:30px; font-weight:bold; font-family:sans-serif; cursor:pointer;">
            <svg viewBox="0 0 24 24" fill="white" width="24" height="24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.377.505 9.377.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
            Watch Video
        </a>
        <div class="pt-yt-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; justify-content:center; align-items:center;">
            <div style="position:relative; width:80%; max-width:800px;">
                <span class="pt-yt-close" style="position:absolute; top:-30px; right:0; color:#fff; cursor:pointer; font-size:24px;">&times;</span>
                <iframe width="100%" height="450" src="" frameborder="0" allowfullscreen></iframe>
            </div>
        </div>
        <script>
        jQuery(document).ready(function($){
            $('.pt-yt-btn').on('click', function(e){
                e.preventDefault();
                var src = $(this).data('src');
                $(this).next('.pt-yt-modal').css('display', 'flex').find('iframe').attr('src', src);
            });
            $('.pt-yt-close').on('click', function(){
                $(this).closest('.pt-yt-modal').hide().find('iframe').attr('src', '');
            });
        });
        </script>
        <?php return ob_get_clean();
    }
    return '';
});

/* ==========================================================================
   5. BUY BUTTONS & WOOCOMMERCE BRIDGE
   ========================================================================== */

add_shortcode('power_tool_buy_button', function() {
    $post_id = get_the_ID();
    if (!$post_id) return '';
    
    if (get_post_meta($post_id, '_is_sold', true) || get_post_status($post_id) === 'sold') {
        return '<p style="color:red; font-weight:bold; font-size: 24px; text-align: center;">SOLD</p>';
    }

    ob_start(); ?>
    <div style="display: flex; gap: 15px; margin: 20px 0;">
        <button class="power-buy-btn" data-id="<?php echo esc_attr($post_id); ?>" data-now="false" style="padding:15px; border-radius:35px; border:1px solid #ccc; font-weight:bold; cursor:pointer; flex:1;">ADD TO CART</button>
        <button class="power-buy-btn" data-id="<?php echo esc_attr($post_id); ?>" data-now="true" style="padding:15px; border-radius:35px; background:#81B716; color:#fff; border:none; font-weight:bold; cursor:pointer; flex:1;">BUY NOW</button>
    </div>
    <script>
    jQuery(document).ready(function($){
        $('.power-buy-btn').click(function(){
            var b = $(this); 
            b.prop('disabled', true).text('Processing...');
            $.post('<?php echo admin_url('admin-ajax.php'); ?>', { 
                action: 'add_tool_to_cart', 
                tool_id: b.data('id'), 
                buy_now: b.data('now') 
            }, function(r) { 
                if(r.success) {
                    window.location.href = r.data.redirect; 
                } else {
                    b.prop('disabled', false).text('Error');
                    alert('Error adding to cart.');
                }
            });
        });
    });
    </script>
    <?php return ob_get_clean();
});

add_action('wp_ajax_add_tool_to_cart', 'handle_power_tool_cart');
add_action('wp_ajax_nopriv_add_tool_to_cart', 'handle_power_tool_cart');

function handle_power_tool_cart() {
    if ( !class_exists('WooCommerce') || is_null( WC()->cart ) ) {
        wp_send_json_error(['message' => 'WooCommerce is not active.']);
        wp_die();
    }

    $s_id = intval($_POST['tool_id']);
    $base_id = 14264; 
    
    $p = get_field('power_tool_price', $s_id);
    $raw_price = floatval(preg_replace('/[^0-9.]/', '', $p));
    
    $img_url = get_the_post_thumbnail_url($s_id, 'thumbnail');
    
    $data = [ 
        'tool_data' => [
            'price' => $raw_price, 
            'title' => get_the_title($s_id),
            'image' => $img_url
        ] 
    ];
    
    WC()->cart->add_to_cart($base_id, 1, 0, [], $data);
    wp_send_json_success(['redirect' => ($_POST['buy_now'] == 'true' ? wc_get_checkout_url() : wc_get_cart_url())]);
    wp_die();
}

add_filter( 'woocommerce_cart_item_thumbnail', function( $thumb, $item ) {
    if ( isset( $item['tool_data']['image'] ) && !empty($item['tool_data']['image']) ) {
        return sprintf('<img src="%s" style="width:70px; border-radius:4px;">', esc_url($item['tool_data']['image']));
    }
    return $thumb;
}, 10, 2 );

add_action( 'woocommerce_before_calculate_totals', function( $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
    foreach ( $cart->get_cart() as $item ) { 
        if ( isset( $item['tool_data'] ) ) { 
            $item['data']->set_price( $item['tool_data']['price'] ); 
            $item['data']->set_name( $item['tool_data']['title'] ); 
        } 
    }
}, 10, 1 );