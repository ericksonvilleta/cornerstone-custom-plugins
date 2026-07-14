<?php
/**
 * Plugin Name: IBAW-Cornerstone Shed Inventory - Final Master
 * Description: A comprehensive, specialized inventory management and e-commerce solution designed specifically for businesses selling sheds or outdoor buildings. It transforms WordPress into a robust digital "Shed Lot" by combining custom data structures with deep WooCommerce integration.
 * Version: 1.1
 * Author: Erick Villeta
 * Plugin URI: https://ericksonvilleta.com
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ==========================================================================
   1. CORE REGISTRATION (CPT, TAXONOMY & CUSTOM STATUS)
   ========================================================================== */

function register_on_the_lot_system() {
    register_post_type("on_the_lot", [
        "labels" => [
            "name" => "On the Lot",
            "singular_name" => "Shed",
            "menu_name" => "On the Lot",
            "add_new" => "Add New Shed",
            "add_new_item" => "Add New Shed to Lot",
            "edit_item" => "Edit Shed Details",
            "view_item" => "View Shed",
        ],
        "public" => true,
        "show_in_menu" => true,
        "menu_icon" => "dashicons-admin-multisite",
        "supports" => ["title", "editor", "thumbnail", "revisions"],
        "has_archive" => true,
        "show_in_rest" => true,
    ]);

    register_taxonomy("shed_category", ["on_the_lot"], [
        "hierarchical" => true,
        "labels" => ["name" => "Shed Categories", "singular_name" => "Shed Category"],
        "show_ui" => true,
        "show_admin_column" => true,
        "show_in_rest" => true,
        "rewrite" => ["slug" => "shed-category"],
    ]);

    // Register "Mark as Sold" Custom Status
    register_post_status('sold', [
        'label'                     => _x('Mark as Sold', 'post'),
        'public'                    => false, // Archives it (removes from front-end grid)
        'exclude_from_search'       => true,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Sold <span class="count">(%s)</span>', 'Sold <span class="count">(%s)</span>'),
    ]);
}
add_action('init', 'register_on_the_lot_system');

// Inject "Mark as Sold" into the Post/Quick Edit dropdowns
add_action('admin_footer-edit.php', 'ibaw_append_sold_status_list');
add_action('admin_footer-post.php', 'ibaw_append_sold_status_list');
function ibaw_append_sold_status_list() {
    global $post;
    if ($post && $post->post_type !== 'on_the_lot') return;
    $complete = ($post && $post->post_status == 'sold') ? 'selected="selected"' : '';
    ?>
    <script>
    jQuery(document).ready(function($){
        $("select#post_status, select[name='_status']").append('<option value="sold" <?php echo $complete; ?>>Mark as Sold</option>');
        if ( 'sold' == '<?php echo ($post ? $post->post_status : ""); ?>' ) {
            $('#post-status-display').text('Mark as Sold');
        }
    });
    </script>
    <style>.status-sold { background: #ffe4e4 !important; color: #cc0000 !important; font-weight: bold; }</style>
    <?php
}

/* ==========================================================================
   2. ADMIN META BOXES (PDF & SOLD CHECKBOX)
   ========================================================================== */

add_action('add_meta_boxes', function() {
    add_meta_box('shed_pdf_box', 'Shed PDF Spec Sheet', 'render_shed_pdf_box', 'on_the_lot', 'side');
    add_meta_box('shed_status_box', 'Availability Status', 'render_shed_status_box', 'on_the_lot', 'side');
});

function render_shed_pdf_box($post) {
    $pdf_url = get_post_meta($post->ID, '_shed_spec_sheet_url', true);
    wp_nonce_field('save_shed_meta', 'shed_meta_nonce');
    echo '<input type="text" name="shed_pdf_url" value="'.esc_attr($pdf_url).'" style="width:100%;" placeholder="PDF URL">';
}

function render_shed_status_box($post) {
    // Keeping your original checkbox logic as well for backward compatibility/meta tracking
    $is_sold = get_post_meta($post->ID, '_is_sold', true);
    echo '<label><input type="checkbox" name="shed_is_sold" value="1" '.checked($is_sold, 1, false).'> <strong style="color:red;">Mark as SOLD (Manual Meta)</strong></label>';
    echo '<p class="description">Note: Changing the "Status" dropdown to "Mark as Sold" will unpublish this shed.</p>';
}

add_action('save_post', function($post_id) {
    if (!isset($_POST['shed_meta_nonce']) || !wp_verify_nonce($_POST['shed_meta_nonce'], 'save_shed_meta')) return;
    update_post_meta($post_id, '_shed_spec_sheet_url', esc_url_raw($_POST['shed_pdf_url']));
    update_post_meta($post_id, '_is_sold', isset($_POST['shed_is_sold']) ? 1 : 0);
});

/* ==========================================================================
   3. SIDEBAR FILTERS SHORTCODE [shed_inventory_filters]
   ========================================================================== */

add_shortcode('shed_inventory_filters', function() {
    global $wpdb;
    $terms = get_terms(['taxonomy' => 'shed_category', 'hide_empty' => false]);

    // Use robust string cleanup on 'total' meta to accurately calculate Max Price
    $prices = $wpdb->get_col("SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = 'total' AND meta_value != '' AND post_id IN (SELECT ID FROM $wpdb->posts WHERE post_type = 'on_the_lot' AND post_status = 'publish')");
    $max_p = 0;
    if (!empty($prices)) {
        foreach($prices as $p) {
            $clean_p = floatval(preg_replace('/[^0-9.]/', '', $p));
            if ($clean_p > $max_p) {
                $max_p = $clean_p;
            }
        }
    }
    // FIXED: Round up to the nearest 100 to prevent the HTML step="100" from clamping the max slider value
    $max_p = $max_p > 0 ? ceil($max_p / 100) * 100 : 10000;
    
    // Pull wall colors
    $wall_c = $wpdb->get_col("SELECT DISTINCT meta_value FROM $wpdb->postmeta WHERE meta_key = 'wall_color' AND meta_value != '' ORDER BY meta_value ASC");

    // Replace IDs with unique classes to prevent DOM duplication bugs in sidebars
    ob_start(); ?>
    <div class="shed-sidebar-filters" style="max-width:350px; background:#fff; padding:20px; border:2px solid #eee; border-radius:10px; font-family:sans-serif;">
        
        <div style="margin-bottom:25px;">
            <label style="display:block; font-weight:bold; margin-bottom:10px; font-size:14px;">Sort By</label>
            <select class="shed-sort-dropdown" style="width:100%; padding:12px; border-radius:5px; border:1px solid #ddd; margin-bottom:15px;">
                <option value="numeric">A to Z</option>
                <option value="date-desc">Newest First</option>
                <option value="price-asc">Price: Low to High</option>
                <option value="price-desc">Price: High to Low</option>
            </select>
            <button class="clear-shed-filters" style="width:100%; padding:12px; background:#f8f8f8; color:#555; border:1px solid #bbb; border-radius:5px; font-weight:700; cursor:pointer; text-transform:uppercase; font-size:12px;">Reset All Filters</button>
        </div>

        <div style="margin-bottom:25px; padding:15px 0; border-top:1px solid #eee; border-bottom:1px solid #eee;">
            <label style="display:block; font-weight:bold; margin-bottom:10px; font-size:14px;">Max Price: <span class="shed-price-display" style="color:#81B716;">$<?php echo number_format($max_p); ?></span></label>
            <input type="range" class="shed-price-range" min="0" max="<?php echo $max_p; ?>" value="<?php echo $max_p; ?>" step="100" style="width:100%; cursor:pointer; accent-color:#81B716;">
        </div>

        <div style="margin-bottom:25px; display:flex; flex-direction:column; gap:15px;">
            <div>
                <label style="display:block; font-weight:bold; font-size:13px; margin-bottom:8px;">Wall Color</label>
                <select class="shed-color-filter" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:5px;">
                    <option value="all">Any Color</option>
                    <?php 
                    // FIXED: Reverted back to the exact string matching what your grid outputs naturally
                    foreach($wall_c as $c) {
                        echo '<option value="'.esc_attr($c).'">'.esc_html($c).'</option>'; 
                    }
                    ?>
                </select>
            </div>
        </div>

        <div style="padding-top:15px; border-top:1px solid #eee; display:flex; flex-direction:column; gap:10px;">
            <label style="font-weight:bold; margin-bottom:5px; font-size:14px;">Filter by Style</label>
            <button class="shed-filter-btn active" data-filter="all" style="width:100%; padding:12px; border-radius:30px; border:none; background:#81B716; color:#fff; font-weight:600; cursor:pointer;">All Styles</button>
            <?php if (!is_wp_error($terms) && !empty($terms)) : ?>
                <?php foreach($terms as $t): ?>
                    <button class="shed-filter-btn" data-filter="cat-<?php echo esc_attr($t->slug); ?>" style="width:100%; padding:12px; border-radius:30px; border:1px solid #ddd; background:#fff; cursor:pointer; font-weight:600; color:#333;"><?php echo esc_html($t->name); ?></button>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($){
        const defMax = <?php echo $max_p; ?>;
        
        function applyFilters() {
            const filterContainer = $('.shed-sidebar-filters');
            
            // Explicit check to safely handle '0' on the slider
            let parsedPrice = parseFloat($('.shed-price-range').first().val());
            const maxPrice = isNaN(parsedPrice) ? defMax : parsedPrice;
            
            // Normalize the selected dropdown value to lowercase
            const selectedColor = ($('.shed-color-filter').first().val() || 'all').trim().toLowerCase();
            const selectedCat = $('.shed-filter-btn.active').first().attr('data-filter') || 'all';
            const sortBy = $('.shed-sort-dropdown').first().val() || 'numeric';

            $('.shed-price-display').text('$' + maxPrice.toLocaleString());
            
            const grid = $('.shed-inventory-grid').first(); 
            let cards = grid.children('.shed-card').get();

            $.each(cards, function(i, el){
                const card = $(el);
                
                // Fallbacks covering both .attr() and .data() to guarantee retrieval 
                const cPrice = parseFloat(card.attr('data-price')) || parseFloat(card.data('price')) || 0;
                const rawWall = card.attr('data-wall') || card.data('wall') || ''; 
                
                // Normalize the grid's card value to lowercase for a bulletproof comparison
                const cWall = String(rawWall).trim().toLowerCase();
                
                const matchCat = (selectedCat === 'all' || card.hasClass(selectedCat));
                const matchPrice = (cPrice <= maxPrice);
                const matchColor = (selectedColor === 'all' || cWall === selectedColor);
                
                if (matchCat && matchPrice && matchColor) {
                    card.show(); 
                } else {
                    card.hide();
                }
            });

            cards.sort(function(a, b) {
                const priceA = parseFloat($(a).attr('data-price')) || parseFloat($(a).data('price')) || 0;
                const priceB = parseFloat($(b).attr('data-price')) || parseFloat($(b).data('price')) || 0;
                const dateA = parseInt($(a).attr('data-date')) || parseInt($(a).data('date')) || 0;
                const dateB = parseInt($(b).attr('data-date')) || parseInt($(b).data('date')) || 0;
                const titleA = $(a).find('h3').text().trim();
                const titleB = $(b).find('h3').text().trim();

                if (sortBy === 'price-asc') return priceA - priceB;
                if (sortBy === 'price-desc') return priceB - priceA;
                if (sortBy === 'date-desc') return dateB - dateA;
                
                return titleA.localeCompare(titleB, undefined, {numeric: true, sensitivity: 'base'});
            });

            $.each(cards, function(i, el) {
                grid.append(el);
            });
        }

        $('body').on('input change', '.shed-price-range', function() {
            $('.shed-price-range').val($(this).val());
            applyFilters();
        });

        $('body').on('change', '.shed-color-filter, .shed-sort-dropdown', function() {
            const selector = '.' + $(this).attr('class').split(' ').join('.');
            $(selector).val($(this).val());
            applyFilters();
        });

        $('body').on('click', '.shed-filter-btn', function(e){
            e.preventDefault();
            const filterType = $(this).attr('data-filter');
            
            $('.shed-filter-btn').css({'background':'#fff','color':'#333','border':'1px solid #ddd'}).removeClass('active');
            $('.shed-filter-btn[data-filter="'+filterType+'"]').css({'background':'#81B716','color':'#fff','border':'none'}).addClass('active');
            
            applyFilters();
        });

        $('body').on('click', '.clear-shed-filters', function(e){
            e.preventDefault();
            
            $('.shed-filter-btn').css({'background':'#fff','color':'#333','border':'1px solid #ddd'}).removeClass('active');
            $('.shed-filter-btn[data-filter="all"]').css({'background':'#81B716','color':'#fff','border':'none'}).addClass('active');
            
            $('.shed-price-range').val(defMax);
            $('.shed-color-filter').val('all');
            $('.shed-sort-dropdown').val('numeric');
            
            applyFilters();
        });
    });
    </script>
    <?php return ob_get_clean();
});

/* ==========================================================================
   4. INVENTORY GRID [shed_inventory_grid] - FIXED FOR NATURAL PHP SORT
   ========================================================================== */

add_shortcode('shed_inventory_grid', function() {
    global $post;
    
    $shed_posts = get_posts([
        'post_type'      => 'on_the_lot', 
        'posts_per_page' => -1, 
        'post_status'    => 'publish' // Sold/Draft are hidden automatically here
    ]);

    if (empty($shed_posts)) return '<p>No inventory available.</p>';

    usort($shed_posts, function($a, $b) {
        return strnatcasecmp($a->post_title, $b->post_title);
    });

    $output = '<div class="shed-inventory-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">';
    
    foreach ($shed_posts as $post) {
        setup_postdata($post);
        $p_id = $post->ID;
        
        $img_html = get_the_post_thumbnail($p_id, 'medium', ['style' => 'width:100%; height:200px; object-fit:cover; display:block;']);
        if (empty($img_html)) {
            $gal = get_field('shed_gallery', $p_id);
            $url = $gal ? ($gal[0]['sizes']['medium'] ?? $gal[0]['url']) : '';
            $img_html = $url ? '<img src="'.esc_url($url).'" style="width:100%; height:200px; object-fit:cover;">' : '<div style="height:200px; background:#f5f5f5;"></div>';
        }

        $condition = strtolower(trim(get_field('condition', $p_id)));
        $price = ($condition === 'used') ? get_field('reduced_price', $p_id) : get_field('total', $p_id);
        
        $raw_p = $price ? floatval(preg_replace('/[^0-9.]/', '', $price)) : 0;
        $tax = wp_get_post_terms($p_id, 'shed_category', ['fields' => 'slugs']);
        $cls = !empty($tax) ? 'cat-' . implode(' cat-', $tax) : '';
        $is_sold = get_post_meta($p_id, '_is_sold', true);

        $output .= sprintf(
            '<div class="shed-card %s" data-price="%f" data-wall="%s" data-date="%d" style="position:relative; border:1px solid #ddd; border-radius:8px; overflow:hidden; background:#fff;">
                %s %s
                <div style="padding:15px;">
                    <h3 style="margin:0 0 5px 0; font-size:18px;">%s</h3>
                    <p style="color:#81B716; font-weight:bold; font-size:18px;">$%s</p>
                    <a href="%s" style="display:block; text-align:center; background:#81B716; color:#fff; padding:10px; border-radius:4px; text-decoration:none;">VIEW DETAILS</a>
                </div>
            </div>',
            $cls, 
            $raw_p, 
            get_field('wall_color', $p_id), 
            get_the_time('U', $p_id),
            ($is_sold ? '<div style="position:absolute; background:red; color:#fff; padding:5px; z-index:5;">SOLD</div>' : ''), 
            $img_html, 
            $post->post_title, 
            number_format($raw_p, 2), 
            get_permalink($p_id)
        );
    }
    
    $output .= '</div>';
    wp_reset_postdata();
    return $output;
});

/* ==========================================================================
   5. SINGLE PAGE SHORTCODES ([shed_info], [shed_price], etc.)
   ========================================================================== */

add_shortcode('shed_gallery', function() {
    $images = get_field('shed_gallery'); if (!$images) return '';
    $output = '<div class="shed-gallery" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px;">';
    foreach ($images as $img) { $output .= sprintf('<div class="gallery-item"><a href="%s" target="_blank"><img src="%s" style="width:100%%; border-radius:4px;"></a></div>', esc_url($img['url']), esc_url($img['sizes']['medium'])); }
    return $output . '</div>';
});

add_shortcode('shed_info', function() {
    $fields = ['Building Style' => 'building_style', 'New / Used' => 'condition', 'Size' => 'shed_size', 'Inventory#' => 'inventory_number', 'Wall Color' => 'wall_color'];
    $output = '<ul style="list-style:none; padding:0;">';
    foreach ($fields as $label => $key) { $val = get_field($key); if ($val) $output .= sprintf('<li><strong>%s:</strong> %s</li>', $label, $val); }
    return $output . '</ul>';
});

add_shortcode('shed_price', function() {
    $p_fields = [
        'Building Base Cost'  => 'building_base_cost', 
        'Total Options Cost'  => 'total_options_cost', 
        'Brochure Price'      => 'brochure_price',
        'Reduced Price'       => 'reduced_price',
        'Savings'             => 'savings',
        'Security Deposit'    => 'security_deposit',
        'Total'               => 'total',
        '36 Months'           => '36_months',
        '48 Months'           => '48_months',
        '60 Months'           => '60_months',
        '36 Monthly Payments' => '36_monthly_payments'
    ];

    $output = '<ul style="list-style:none; padding:0; margin-bottom:20px;">';
    
    foreach ($p_fields as $label => $key) { 
        $val = get_field($key); 
        if (!$val && preg_match('/^(\d+)_(.*)$/', $key, $matches)) {
            $fallback_key = $matches[2] . '_' . $matches[1]; 
            $val = get_field($fallback_key);
        }
        if ($val) {
            $display_val = is_numeric($val) ? number_format((float)$val, 2) : $val;
            if ($label === 'Brochure Price') {
                $val_html = '<span style="color:red; text-decoration:line-through;">$' . $display_val . '</span>';
            } else {
                $val_html = '$' . $display_val;
            }
            $output .= sprintf(
                '<li style="display:flex; justify-content:space-between; border-bottom:1px solid #eee; padding:5px 0;"><strong>%s:</strong> <span>%s</span></li>', 
                $label, 
                $val_html
            ); 
        }
    }
    return $output . '</ul>';
});

add_shortcode('shed_download_button', function() {
    $url = get_post_meta(get_the_ID(), '_shed_spec_sheet_url', true); if (!$url) return '';
    return sprintf('<a href="%s" target="_blank" style="background:#81B716; color:#fff; padding:10px 20px; border-radius:25px; text-decoration:none; display:inline-block; font-weight:bold;">DOWNLOAD SPEC SHEET</a>', esc_url($url));
});

add_shortcode('shed_total_only', function() {
    $p_id = get_the_ID();
    $condition = strtolower(trim(get_field('condition', $p_id)));
    if ($condition === 'used') { $price = get_field('reduced_price', $p_id); } else { $price = get_field('total', $p_id); }
    if (!$price) return '';
    $raw_price = floatval(preg_replace('/[^0-9.]/', '', $price));
    return '<span class="shed-price-total">$' . number_format($raw_price, 2) . '</span>';
});

/* ==========================================================================
   6. WOOCOMMERCE SYSTEM (NAME, PRICE, & IMAGE FIX)
   ========================================================================== */

add_action('wp_ajax_add_shed_to_cart', 'handle_shed_cart');
add_action('wp_ajax_nopriv_add_shed_to_cart', 'handle_shed_cart');

function handle_shed_cart() {
    if ( is_null( WC()->cart ) ) { wc_load_cart(); }
    $s_id = intval($_POST['shed_id']);
    $base_id = 11304; 

    $condition = strtolower(trim(get_field('condition', $s_id)));
    if ($condition === 'used') { $p = get_field('reduced_price', $s_id); } else { $p = get_field('total', $s_id); }
    
    $raw_price = floatval(preg_replace('/[^0-9.]/', '', $p));
    $img = get_the_post_thumbnail_url($s_id, 'thumbnail');
    if (!$img) { $gal = get_field('shed_gallery', $s_id); $img = $gal ? ($gal[0]['sizes']['thumbnail'] ?? $gal[0]['url']) : ''; }

    $data = [ 'shed_data' => [ 'title' => get_the_title($s_id), 'price' => $raw_price, 'image' => $img ], 'unique_key' => md5($s_id . microtime()) ];

    WC()->cart->add_to_cart($base_id, 1, 0, [], $data);
    wp_send_json_success(['redirect' => ($_POST['buy_now'] == 'true' ? wc_get_checkout_url() : wc_get_cart_url())]);
    wp_die();
}

add_action( 'woocommerce_before_calculate_totals', function( $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
    foreach ( $cart->get_cart() as $item ) { if ( isset( $item['shed_data'] ) ) { $item['data']->set_price( $item['shed_data']['price'] ); $item['data']->set_name( $item['shed_data']['title'] ); } }
}, 10, 1 );

add_filter( 'woocommerce_cart_item_thumbnail', function( $thumb, $item ) {
    if ( isset( $item['shed_data']['image'] ) ) return sprintf('<img src="%s" style="width:70px; border-radius:4px;">', esc_url($item['shed_data']['image']));
    return $thumb;
}, 10, 2 );

add_shortcode('shed_purchase_buttons', function() {
    // Both meta check and native status check for "Sold"
    if (get_post_meta(get_the_ID(), '_is_sold', true) || get_post_status(get_the_ID()) === 'sold') return '<p style="color:red; font-weight:bold; font-size: 24px; text-align: center;">SOLD</p>';
    ob_start(); ?>
    <div class="shed-buy-btn-container">
        <button class="shed-buy-btn" data-id="<?php the_ID(); ?>" data-now="false" style="padding:15px; border-radius:35px; border:1px solid #ccc; font-weight:bold; cursor:pointer;">ADD TO CART</button>
        <button class="shed-buy-btn" data-id="<?php the_ID(); ?>" data-now="true" style="padding:15px; border-radius:35px; background:#81B716; color:#fff; border:none; font-weight:bold; cursor:pointer;">BUY NOW</button>
    </div>
    <script>
    jQuery(document).ready(function($){
        $('.shed-buy-btn').click(function(){
            var b = $(this); b.prop('disabled', true).text('Processing...');
            $.post('<?php echo admin_url('admin-ajax.php'); ?>', { action: 'add_shed_to_cart', shed_id: b.data('id'), buy_now: b.data('now') }, function(r) { if(r.success) window.location.href = r.data.redirect; });
        });
    });
    </script>
    <?php return ob_get_clean();
});

/* ==========================================================================
   7. RELATED SHEDS
   ========================================================================== */

add_shortcode('related_sheds', function() {
    $p_id = get_the_ID(); 
    $terms = get_the_terms($p_id, 'shed_category'); 
    if (empty($terms) || is_wp_error($terms)) return '';
    $term_ids = wp_list_pluck($terms, 'term_id');

    $args = [
        'post_type'      => 'on_the_lot', 
        'posts_per_page' => 3, 
        'post__not_in'   => [$p_id], 
        'tax_query'      => [['taxonomy' => 'shed_category', 'field' => 'term_id', 'terms' => $term_ids]],
        'orderby'        => 'rand',
        'post_status'    => 'publish' // Ensure related sheds are not sold ones
    ];

    $query = new WP_Query($args); 
    if (!$query->have_posts()) return '';

    $output = '<div class="related-sheds-section" style="margin-top:60px; border-top:2px solid #eee; padding-top:40px; font-family: sans-serif;">';
    $output .= '<h3 style="text-transform:uppercase; margin-bottom:30px; letter-spacing:1px; font-size:22px; color: #333;">You Might Also Like</h3>';
    $output .= '<div class="shed-inventory-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">';

    while ($query->have_posts()) { 
        $query->the_post(); 
        $rel_id = get_the_ID();
        $is_sold = get_post_meta($rel_id, '_is_sold', true);
        $img_html = get_the_post_thumbnail($rel_id, 'medium', ['style' => 'width:100%; height:200px; object-fit:cover; display:block;']);
        if (empty($img_html)) {
            $gal = get_field('shed_gallery', $rel_id);
            $url = $gal ? ($gal[0]['sizes']['medium'] ?? $gal[0]['url']) : '';
            $img_html = $url ? '<img src="'.esc_url($url).'" style="width:100%; height:200px; object-fit:cover;">' : '<div style="height:200px; background:#f5f5f5;"></div>';
        }
        $condition = strtolower(trim(get_field('condition', $rel_id)));
        $price = ($condition === 'used') ? get_field('reduced_price', $rel_id) : get_field('total', $rel_id);
        $raw_p = $price ? floatval(preg_replace('/[^0-9.]/', '', $price)) : 0;
        $size_label = get_field('shed_size', $rel_id);
        $sold_tag = $is_sold ? '<div style="position:absolute; top:10px; right:10px; background:red; color:white; padding:5px 12px; font-weight:bold; border-radius:3px; z-index:5; font-size:12px;">SOLD</div>' : '';

        $output .= sprintf(
            '<div class="shed-card" style="position:relative; border:1px solid #ddd; border-radius:8px; overflow:hidden; background:#fff; transition:0.3s; box-shadow: 0 2px 5px rgba(0,0,0,0.05); %s">
                %s %s
                <div style="padding:15px;">
                    <h3 style="margin:0 0 5px 0; font-size:18px; line-height:1.3; color: #333;">%s</h3>
                    <p style="margin:0 0 10px 0; font-size:13px; color:#666;">Size: %s</p>
                    <p style="color:#81B716; font-weight:bold; font-size:18px; margin:0 0 15px 0;">$%s</p>
                    <a href="%s" style="display:block; text-align:center; background:%s; color:#fff; padding:12px; border-radius:4px; text-decoration:none; font-weight:bold; text-transform:uppercase; font-size:13px;">%s</a>
                </div>
            </div>',
            ($is_sold ? 'opacity:0.7;' : ''), $sold_tag, $img_html, get_the_title(), esc_html($size_label), number_format($raw_p, 2), get_permalink(), ($is_sold ? '#999' : '#81B716'), ($is_sold ? 'View Specs' : 'View Details')
        ); 
    }
    $output .= '</div></div>'; wp_reset_postdata(); return $output;
});

/* ==========================================================================
   8. THE GLOBAL HAMMER: FORCE FULL WIDTH VIA CSS
   ========================================================================== */

add_action('wp_head', function() {
    if ( is_singular('on_the_lot') || is_single(3404) ) { 
        ?>
        <style type="text/css">
            aside#sidebar, .aux-sidebar-primary, .aux-sidebar-style-border, .aux-widget-area, .widget_product_search, .widget_product_categories {
                display: none !important; width: 0 !important; max-width: 0 !important; visibility: hidden !important; opacity: 0 !important; position: absolute !important; left: -9999px !important;
            }
            #primary.aux-primary, .aux-primary, main#main, .aux-main-content {
                width: 100% !important; max-width: 100% !important; flex: 0 0 100% !important; border: none !important; padding-right: 0 !important; padding-left: 0 !important; margin: 0 !important; float: none !important;
            }
            .aux-container { max-width: 1240px !important; margin: 0 auto !important; padding-left: 15px !important; padding-right: 15px !important; }
            .shed-buy-btn-container { display: flex !important; flex-direction: row !important; gap: 15px !important; max-width: 100% !important; margin: 20px 0 !important; }
            .shed-buy-btn { flex: 1 !important; white-space: nowrap !important; }
            .shed-buy-btn:not([data-now="true"]):hover { background-color: #81b717 !important; color: #fff !important; border-color: #81b717 !important; transition: all 0.3s ease; }
            .shed-buy-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
            .shed-price-total { font-family: Quicksand; font-size: 38px; font-weight: 800; color: #81B716; }
        </style>
        <?php
    }
}, 999);

/* ==========================================================================
   9. HARD-LOCKED BANNER ABOVE ACF FIELD GROUP (SHED INVENTORY DETAILS)
   ========================================================================== */

add_action('admin_footer', function() {
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'on_the_lot') return;
?>
<script>
jQuery(document).ready(function($){

    function insertBannerACF() {
        if ($('.ibaw-locked-banner').length) return;

        // ACF field groups container
        var group = $('.acf-field-group, .acf-postbox').first();

        if (group.length) {

            var banner = `
                <div class="ibaw-locked-banner" style="
                    border: 2px solid #c9bfa5;
                    background: #d9cfb8;
                    padding: 20px;
                    margin: 10px 0 15px 0;
                    text-align: center;
                ">
                    <div style="font-size:16px;font-weight:700;color:#333;">
                        REQUIRED: 1200x900px
                    </div>
                    <div style="font-size:14px;font-weight:600;color:#333;margin-top:3px;">
                        (4:3 Landscape Ratio)
                    </div>
                </div>
                <p style="
		    padding: 0 0 0 20px;
                    margin: 0 0 15px 0;
                    font-size: 13px;
                    color: #f00;
                ">
                    Ensure all Featured and Gallery images match these dimensions for a clean grid.
                </p>
            `;

            group.before(banner);
            return true;
        }

        return false;
    }

    // Retry until ACF loads (ACF loads AFTER Gutenberg)
    var attempts = 0;
    var interval = setInterval(function(){
        if (insertBannerACF()) {
            clearInterval(interval);
        }
        attempts++;
        if (attempts > 40) clearInterval(interval);
    }, 250);

});
</script>
<?php
});