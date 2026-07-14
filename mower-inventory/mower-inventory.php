<?php
/**
 * Plugin Name: Mower Inventory - Master
 * Description: A specialized inventory management and e-commerce solution for selling mowers. Combines custom data structures with deep WooCommerce integration.
 * Version:     1.1.0
 * Author:      Erick Villeta
 * Plugin URI:  https://ericksonvilleta.com
 * Text Domain: mower-plugin
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ==========================================================================
   1. CORE REGISTRATION (CPT, TAXONOMY & CUSTOM STATUS)
   ========================================================================== */

function register_mower_system() {
    register_post_type("mower_lot", [
        "labels" => [
            "name" => "Mower Inventory",
            "singular_name" => "Mower",
            "menu_name" => "Mower Lot",
            "add_new" => "Add New Mower",
            "add_new_item" => "Add New Mower to Lot",
            "edit_item" => "Edit Mower Details",
            "view_item" => "View Mower",
        ],
        "public" => true,
        "show_in_menu" => true,
        "menu_icon" => "https://cornerstonelandscapesupply.com/wp-content/uploads/adminify-custom-icons/mower.ico", 
        "supports" => ["title", "editor", "thumbnail", "revisions"],
        "has_archive" => true,
        "show_in_rest" => true,
    ]);

    register_taxonomy("mower_category", ["mower_lot"], [
        "hierarchical" => true,
        "labels" => ["name" => "Mower Categories", "singular_name" => "Mower Category"],
        "show_ui" => true,
        "show_admin_column" => true,
        "show_in_rest" => true,
        "rewrite" => ["slug" => "mower-category"],
    ]);

    register_post_status('sold', [
        'label'                     => _x('Mark as Sold', 'post'),
        'public'                    => false,
        'exclude_from_search'       => true,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Sold <span class="count">(%s)</span>', 'Sold <span class="count">(%s)</span>'),
    ]);
}
add_action('init', 'register_mower_system');

add_action('admin_head', function() {
    echo '<style>#menu-posts-mower_lot .wp-menu-image img { padding: 5px 0; filter: brightness(0); }</style>';
});

add_action('admin_footer-edit.php', 'mower_append_sold_status');
add_action('admin_footer-post.php', 'mower_append_sold_status');
function mower_append_sold_status() {
    global $post;
    if ($post && $post->post_type !== 'mower_lot') return;
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
   2. ADMIN META BOXES (PDF SPEC & MANUAL SOLD META)
   ========================================================================== */

add_action('add_meta_boxes', function() {
    add_meta_box('mower_pdf_box', 'Mower PDF Spec Sheet', 'render_mower_pdf_box', 'mower_lot', 'side');
    add_meta_box('mower_status_box', 'Availability Status', 'render_mower_status_box', 'mower_lot', 'side');
});

function render_mower_pdf_box($post) {
    $pdf_url = get_post_meta($post->ID, '_mower_spec_sheet_url', true);
    wp_nonce_field('save_mower_meta', 'mower_meta_nonce');
    echo '<input type="text" name="mower_pdf_url" value="'.esc_attr($pdf_url).'" style="width:100%;" placeholder="PDF URL">';
}

function render_mower_status_box($post) {
    $is_sold = get_post_meta($post->ID, '_is_sold', true);
    echo '<label><input type="checkbox" name="mower_is_sold" value="1" '.checked($is_sold, 1, false).'> <strong style="color:red;">Mark as SOLD (Manual Meta)</strong></label>';
    echo '<p class="description">Note: Changing the "Status" dropdown to "Mark as Sold" will unpublish this mower.</p>';
}

add_action('save_post', function($post_id) {
    if (!isset($_POST['mower_meta_nonce']) || !wp_verify_nonce($_POST['mower_meta_nonce'], 'save_mower_meta')) return;
    update_post_meta($post_id, '_mower_spec_sheet_url', esc_url_raw($_POST['mower_pdf_url']));
    update_post_meta($post_id, '_is_sold', isset($_POST['mower_is_sold']) ? 1 : 0);
});

/* ==========================================================================
   3. SIDEBAR FILTERS SHORTCODE
   ========================================================================== */

add_shortcode('mower_inventory_filters', function() {
    global $wpdb;
    $terms = get_terms(['taxonomy' => 'mower_category', 'hide_empty' => false]);

    $prices = $wpdb->get_col("SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = 'mower_price' AND meta_value != '' AND post_id IN (SELECT ID FROM $wpdb->posts WHERE post_type = 'mower_lot' AND post_status = 'publish')");
    $max_p = 0;
    if (!empty($prices)) {
        foreach($prices as $p) {
            $clean_p = floatval(preg_replace('/[^0-9.]/', '', $p));
            if ($clean_p > $max_p) {
                $max_p = $clean_p;
            }
        }
    }
    
    $max_p = $max_p > 0 ? ceil($max_p / 100) * 100 : 10000;
    $engine_options = $wpdb->get_col("SELECT DISTINCT meta_value FROM $wpdb->postmeta WHERE meta_key = 'engine' AND meta_value != '' AND post_id IN (SELECT ID FROM $wpdb->posts WHERE post_type = 'mower_lot' AND post_status = 'publish') ORDER BY meta_value ASC");

    ob_start(); ?>
    <div class="mower-sidebar-filters" style="max-width:350px; background:#fff; padding:20px; border:2px solid #eee; border-radius:10px; font-family:sans-serif;">
        <div style="margin-bottom:25px;">
            <label style="display:block; font-weight:bold; margin-bottom:10px; font-size:14px;">Sort By</label>
            <select class="mower-sort-dropdown" style="width:100%; padding:12px; border-radius:5px; border:1px solid #ddd; margin-bottom:15px;">
                <option value="numeric">A to Z</option>
                <option value="date-desc">Newest First</option>
                <option value="price-asc">Price: Low to High</option>
                <option value="price-desc">Price: High to Low</option>
            </select>
            <button class="clear-mower-filters" style="width:100%; padding:12px; background:#f8f8f8; color:#555; border:1px solid #bbb; border-radius:5px; font-weight:700; cursor:pointer; text-transform:uppercase; font-size:12px;">Reset All Filters</button>
        </div>

        <div style="margin-bottom:25px; padding:15px 0; border-top:1px solid #eee; border-bottom:1px solid #eee;">
            <label style="display:block; font-weight:bold; margin-bottom:10px; font-size:14px;">Max Price: <span class="mower-price-display" style="color:#81B716;">$<?php echo number_format($max_p); ?></span></label>
            <input type="range" class="mower-price-range" min="0" max="<?php echo $max_p; ?>" value="<?php echo $max_p; ?>" step="100" style="width:100%; cursor:pointer; accent-color:#81B716;">
        </div>

        <div style="margin-bottom:25px;">
            <label style="display:block; font-weight:bold; font-size:13px; margin-bottom:8px;">Engine Type</label>
            <select class="mower-color-filter" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:5px;">
                <option value="all">Any Engine</option>
                <?php foreach($engine_options as $e) { echo '<option value="'.esc_attr(sanitize_title($e)).'">'.esc_html($e).'</option>'; } ?>
            </select>
        </div>

        <div style="padding-top:15px; border-top:1px solid #eee; display:flex; flex-direction:column; gap:10px;">
            <label style="font-weight:bold; margin-bottom:5px; font-size:14px;">Filter by Category</label>
            <button class="mower-filter-btn active" data-filter="all" style="width:100%; padding:12px; border-radius:30px; border:none; background:#81B716; color:#fff; font-weight:600; cursor:pointer;">All Categories</button>
            <?php if (!is_wp_error($terms) && !empty($terms)) : ?>
                <?php foreach($terms as $t): ?>
                    <button class="mower-filter-btn" data-filter="cat-<?php echo esc_attr($t->slug); ?>" style="width:100%; padding:12px; border-radius:30px; border:1px solid #ddd; background:#fff; cursor:pointer; font-weight:600; color:#333;"><?php echo esc_html($t->name); ?></button>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($){
        const defMax = <?php echo $max_p; ?>;
        
        function applyFilters() {
            let parsedPrice = parseFloat($('.mower-price-range').first().val());
            const maxPrice = isNaN(parsedPrice) ? defMax : parsedPrice;
            const selectedEngine = $('.mower-color-filter').first().val() || 'all';
            const selectedCat = $('.mower-filter-btn.active').first().attr('data-filter') || 'all';
            const sortBy = $('.mower-sort-dropdown').first().val() || 'numeric';

            $('.mower-price-display').text('$' + maxPrice.toLocaleString());
            
            const grid = $('#main-mower-grid');
            let cards = grid.children('.mower-card').get();

            $.each(cards, function(i, el){
                const card = $(el);
                const cPrice = parseFloat(card.attr('data-price')) || 0;
                const cEngine = card.attr('data-color') || ''; 
                
                const matchCat = (selectedCat === 'all' || card.hasClass(selectedCat));
                const matchPrice = (cPrice <= maxPrice);
                const matchEngine = (selectedEngine === 'all' || cEngine === selectedEngine);
                
                if (matchCat && matchPrice && matchEngine) { card.show(); } else { card.hide(); }
            });

            cards.sort(function(a, b) {
                const priceA = parseFloat($(a).attr('data-price')) || 0;
                const priceB = parseFloat($(b).attr('data-price')) || 0;
                const dateA = parseInt($(a).attr('data-date')) || 0;
                const dateB = parseInt($(b).attr('data-date')) || 0;
                const titleA = $(a).find('h3').text().trim();
                const titleB = $(b).find('h3').text().trim();

                if (sortBy === 'price-asc') return priceA - priceB;
                if (sortBy === 'price-desc') return priceB - priceA;
                if (sortBy === 'date-desc') return dateB - dateA;
                return titleA.localeCompare(titleB, undefined, {numeric: true, sensitivity: 'base'});
            });

            $.each(cards, function(i, el) { grid.append(el); });
        }

        $('body').on('input change', '.mower-price-range', function() { $('.mower-price-range').val($(this).val()); applyFilters(); });
        $('body').on('change', '.mower-color-filter, .mower-sort-dropdown', function() { const selector = '.' + $(this).attr('class').split(' ').join('.'); $(selector).val($(this).val()); applyFilters(); });
        $('body').on('click', '.mower-filter-btn', function(e){ e.preventDefault(); const filterType = $(this).attr('data-filter'); $('.mower-filter-btn').css({'background':'#fff','color':'#333','border':'1px solid #ddd'}).removeClass('active'); $('.mower-filter-btn[data-filter="'+filterType+'"]').css({'background':'#81B716','color':'#fff','border':'none'}).addClass('active'); applyFilters(); });
        $('body').on('click', '.clear-mower-filters', function(e){ e.preventDefault(); $('.mower-filter-btn').css({'background':'#fff','color':'#333','border':'1px solid #ddd'}).removeClass('active'); $('.mower-filter-btn[data-filter="all"]').css({'background':'#81B716','color':'#fff','border':'none'}).addClass('active'); $('.mower-price-range').val(defMax); $('.mower-color-filter').val('all'); $('.mower-sort-dropdown').val('numeric'); applyFilters(); });
    });
    </script>
    <?php return ob_get_clean();
});

/* ==========================================================================
   4. INVENTORY GRID
   ========================================================================== */

add_shortcode('mower_inventory_grid', function() {
    global $post;
    $mower_posts = get_posts(['post_type' => 'mower_lot', 'posts_per_page' => -1, 'post_status' => 'publish']);
    if (empty($mower_posts)) return '<p>No mowers available at this moment.</p>';
    usort($mower_posts, function($a, $b) { return strnatcasecmp($a->post_title, $b->post_title); });

    $output = '<div id="main-mower-grid" class="mower-inventory-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">';
    
    foreach ($mower_posts as $post) {
        setup_postdata($post);
        $t_id = $post->ID;
        
        $img_html = get_the_post_thumbnail($t_id, 'medium', ['style' => 'width:100%; height:200px; object-fit:cover; display:block;']);
        if (empty($img_html)) {
            $gallery = get_field('mower_gallery', $t_id);
            $url = $gallery ? ($gallery[0]['sizes']['medium'] ?? $gallery[0]['url']) : '';
            $img_html = $url ? '<img src="'.esc_url($url).'" style="width:100%; height:200px; object-fit:cover;">' : '<div style="height:200px; background:#f5f5f5;"></div>';
        }

        $price = get_field('mower_price', $t_id);
        $raw_p = $price ? floatval(preg_replace('/[^0-9.]/', '', $price)) : 0;
        
        $tax = get_the_terms($t_id, 'mower_category');
        $cls_array = [];
        if (!empty($tax) && !is_wp_error($tax)) { foreach ($tax as $t) { $cls_array[] = 'cat-' . esc_attr($t->slug); } }
        $cls = implode(' ', $cls_array);
        
        $is_sold = get_post_meta($t_id, '_is_sold', true);
        $raw_engine = get_field('engine', $t_id);
        $engine_slug = $raw_engine ? sanitize_title($raw_engine) : '';

        $output .= sprintf(
            '<div class="mower-card %s" data-price="%s" data-color="%s" data-date="%d" style="position:relative; border:1px solid #ddd; border-radius:8px; overflow:hidden; background:#fff;">
                %s %s
                <div style="padding:15px;">
                    <h3 style="margin:0 0 5px 0; font-size:18px;">%s</h3>
                    <p style="color:#81B716; font-weight:bold; font-size:18px;">$%s</p>
                    <a href="%s" style="display:block; text-align:center; background:#81B716; color:#fff; padding:10px; border-radius:4px; text-decoration:none;">VIEW DETAILS</a>
                </div>
            </div>',
            $cls, $raw_p, esc_attr($engine_slug), get_the_time('U', $t_id),
            ($is_sold ? '<div style="position:absolute; background:red; color:#fff; padding:5px; z-index:5;">SOLD</div>' : ''), 
            $img_html, get_the_title($t_id), number_format($raw_p, 2), get_permalink($t_id)
        );
    }
    
    $output .= '</div>';
    wp_reset_postdata();
    return $output;
});

/* ==========================================================================
   5. SINGLE PAGE SHORTCODES & SPECIFICATIONS TABLE VISUALIZERS
   ========================================================================== */

add_shortcode('mower_buy_button', function() {
    if (get_post_meta(get_the_ID(), '_is_sold', true) || get_post_status(get_the_ID()) === 'sold') return '<p style="color:red; font-weight:bold; font-size: 24px; text-align: center;">SOLD</p>';
    ob_start(); ?>
    <div class="mower-buy-btn-container">
        <button class="mower-buy-btn" data-id="<?php the_ID(); ?>" data-now="false" style="padding:15px; border-radius:35px; border:1px solid #ccc; font-weight:bold; cursor:pointer;">ADD TO CART</button>
        <button class="mower-buy-btn" data-id="<?php the_ID(); ?>" data-now="true" style="padding:15px; border-radius:35px; background:#81B716; color:#fff; border:none; font-weight:bold; cursor:pointer;">BUY NOW</button>
    </div>
    <script>
    jQuery(document).ready(function($){
        $('.mower-buy-btn').click(function(){
            var b = $(this); b.prop('disabled', true).text('Processing...');
            $.post('<?php echo admin_url('admin-ajax.php'); ?>', { action: 'add_mower_to_cart', mower_id: b.data('id'), buy_now: b.data('now') }, function(r) { if(r.success) window.location.href = r.data.redirect; });
        });
    });
    </script>
    <?php return ob_get_clean();
});

add_shortcode('mower_warranty_display', function() {
    $comm = get_field('commercial_use');
    $res  = get_field('residential_use');
    if (empty($comm) && empty($res)) return '';
    
    $output = '<div class="mower-spec-table-block" style="margin-bottom:35px;">';
    $output .= '<h3 style="font-size:16px; font-weight:700; text-transform:uppercase; color:#81B716; letter-spacing:0.8px; margin:0 0 12px 0; padding-bottom:6px; border-bottom:2px solid #81B716;">Warranty Information</h3>';
    $output .= '<table style="width:100%; border-collapse:collapse; text-align:left; background:#fff; border:1px solid #eaeaea; border-radius:4px;">';
    $output .= '<tbody>';
    
    if ($res) {
        $output .= '<tr style="background:#ffffff; transition:background 0.2s;"><td style="padding:12px 15px; font-weight:600; color:#333; border-bottom:1px solid #eaeaea; font-size:14px; width:45%;">Residential Use</td><td style="padding:12px 15px; color:#555; border-bottom:1px solid #eaeaea; font-size:14px;">' . esc_html($res) . '</td></tr>';
    }
    if ($comm) {
        $output .= '<tr style="background:#f9f9f9; transition:background 0.2s;"><td style="padding:12px 15px; font-weight:600; color:#333; border-bottom:1px solid #eaeaea; font-size:14px; width:45%;">Commercial Use</td><td style="padding:12px 15px; color:#555; border-bottom:1px solid #eaeaea; font-size:14px;">' . esc_html($comm) . '</td></tr>';
    }
    
    $output .= '</tbody></table></div>';
    return $output;
});

add_shortcode('mower_package_details', function() {
    $output = '<div class="mower-specifications-table-wrapper" style="font-family:sans-serif; max-width:100%; margin:20px 0; overflow-x:auto;">';
    
    $general_specs = ['Model Name' => ['key' => 'mower_name_&_model', 'suffix' => '']];
    $engine_drive_specs = [
        'Engine'             => ['key' => 'engine', 'suffix' => ''],
        'Hydrostatic Pumps'  => ['key' => 'hydrostatic_pumps', 'suffix' => ''],
        'Max Forward Speed'  => ['key' => 'max_forward_speed_mph', 'suffix' => ' MPH'],
        'Max Acres Per Hour' => ['key' => 'max_acres_per_hour', 'suffix' => ' Acres/hr'],
        'Fuel Capacity'      => ['key' => 'fuel_capacity_gallons', 'suffix' => ' Gallons'],
    ];
    $deck_cutting_specs = [
        'Deck Type'           => ['key' => 'deck_type', 'suffix' => ''],
        'Blade Configuration' => ['key' => 'blade_configuration', 'suffix' => ''],
        'Cut Height Range'    => ['key' => 'cut_height_range', 'suffix' => ''],
    ];
    $dimensions_weight_specs = [
        'Weight'               => ['key' => 'weight_lbs', 'suffix' => ' LBS'],
        'Height'               => ['key' => 'height_inches', 'suffix' => ' in.'],
        'Length'               => ['key' => 'length_inches', 'suffix' => ' in.'],
        'Width (Deflector Up)'   => ['key' => 'width_deflector_up_inches', 'suffix' => ' in.'],
        'Width (Deflector Down)' => ['key' => 'width_deflector_down_inches', 'suffix' => ' in.'],
        'Width (Deflector Off)'  => ['key' => 'width_deflector_off_inches', 'suffix' => ' in.'],
    ];
    $wheels_specs = [
        'Drive Tires' => ['key' => 'drive_tires', 'suffix' => ''],
        'Front Tires' => ['key' => 'front_tires', 'suffix' => ''],
    ];
    $features_compliance_specs = [
        'Height Adjustable Handles' => ['key' => 'height_adjustable_handles', 'suffix' => ''],
        'Hour Meter'                => ['key' => 'hour_meter', 'suffix' => ''],
        'CARB Compliant'            => ['key' => 'carb_compliant', 'suffix' => ''],
    ];

    $sections = [
        'General Specifications' => $general_specs,
        'Engine & Drive'         => $engine_drive_specs,
        'Deck & Cutting'         => $deck_cutting_specs,
        'Dimensions & Weight'    => $dimensions_weight_specs,
        'Wheels'                 => $wheels_specs,
        'Features & Compliance'  => $features_compliance_specs,
    ];

    foreach ($sections as $section_title => $fields) {
        $table_rows = '';
        $row_index = 0;
        foreach ($fields as $label => $data) {
            $val = get_field($data['key']);
            if (in_array($data['key'], ['height_adjustable_handles', 'hour_meter', 'carb_compliant'])) {
                if ($val !== null && $val !== '') { $val = $val ? 'Yes' : 'No'; }
            }
            if (is_string($val)) { $val = trim($val); }
            if ($val !== false && $val !== null && $val !== '') {
                $bg_toggle = ($row_index % 2 === 0) ? '#ffffff' : '#f9f9f9';
                $table_rows .= sprintf(
                    '<tr style="background:%s; transition:background 0.2s;">
                        <td style="padding:12px 15px; font-weight:600; color:#333; border-bottom:1px solid #eaeaea; font-size:14px; width:45%%;">%s</td>
                        <td style="padding:12px 15px; color:#555; border-bottom:1px solid #eaeaea; font-size:14px; font-variant-numeric:tabular-nums;">%s%s</td>
                     </tr>',
                    $bg_toggle, esc_html($label), esc_html($val), esc_html($data['suffix'])
                );
                $row_index++;
            }
        }
        if (!empty($table_rows)) {
            $output .= sprintf(
                '<div class="mower-spec-table-block" style="margin-bottom:35px;">
                    <h3 style="font-size:16px; font-weight:700; text-transform:uppercase; color:#81B716; letter-spacing:0.8px; margin:0 0 12px 0; padding-bottom:6px; border-bottom:2px solid #81B716;">%s</h3>
                    <table style="width:100%%; border-collapse:collapse; text-align:left; background:#fff; border:1px solid #eaeaea; border-radius:4px;">
                        <tbody>%s</tbody>
                    </table>
                 </div>',
                esc_html($section_title), $table_rows
            );
        }
    }
    $output .= '</div>';
    return $output;
});

add_shortcode('mower_info_display', function() { $info = get_field('mower_info'); return $info ? '<div class="mower-description" style="margin:20px 0; line-height:1.6; color:#444;">' . wpautop(esc_html($info)) . '</div>' : ''; });

add_shortcode('mower_retail_price', function() {
    $price = get_field('mower_price'); 
    $raw_p = $price ? floatval(preg_replace('/[^0-9.]/', '', $price)) : 0;
    return $raw_p ? '<div class="mower-price-tag" style="font-size:32px; font-weight:800; color:#81B716;">$' . number_format($raw_p, 2) . '</div>' : ''; 
});

/* ==========================================================================
   6. MOWER CUSTOM GALLERY WITH NAVIGATION (MAPPED TO NEW JSON FIELD)
   ========================================================================== */

function mower_enqueue_gallery_assets() {
    wp_register_style( 'pcg-mower-style', false ); 
    wp_enqueue_style( 'pcg-mower-style' );
    wp_add_inline_style( 'pcg-mower-style', ".pcg-container { display: flex; flex-direction: column; gap: 15px; max-width: 100%; margin: 20px 0; position: relative; } .pcg-main-wrapper { position: relative; width: 100%; overflow: hidden; border-radius: 8px; } .pcg-main-image img { width: 100%; height: auto; border-radius: 8px; transition: opacity 0.3s ease; display: block; object-fit: contain; background: #f9f9f9; } .pcg-nav-btn { position: absolute; top: 50%; transform: translateY(-50%); background: rgba(0,0,0,0.5); color: #fff; border: none; padding: 15px 10px; cursor: pointer; font-size: 20px; z-index: 10; border-radius: 4px; transition: background 0.2s; } .pcg-nav-btn:hover { background: rgba(0,0,0,0.8); } .pcg-prev { left: 10px; } .pcg-next { right: 10px; } .pcg-thumbnails { display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 10px; margin-top: 10px; } .pcg-thumb img { width: 100%; height: 80px; object-fit: cover; cursor: pointer; border: 2px solid transparent; border-radius: 4px; transition: 0.2s; } .pcg-thumb img.active { border-color: #81B716; } .pcg-thumb img:hover { opacity: 0.8; }" );
}
add_action( 'wp_enqueue_scripts', 'mower_enqueue_gallery_assets' );

function mower_display_gallery_shortcode() {
    $post_id = get_the_ID(); 
    $featured_id = get_post_thumbnail_id($post_id); 
    
    $gallery_images = get_field('mower_gallery', $post_id); 
    
    if ( !$featured_id && empty($gallery_images) ) return '';
    $all_ids = array(); 
    if ($featured_id) $all_ids[] = $featured_id;
    if ($gallery_images) { foreach ($gallery_images as $img) { $id = is_array($img) ? $img['ID'] : $img; if ($id !== $featured_id) $all_ids[] = $id; } }

    ob_start();
    ?>
    <div class="pcg-container">
        <div class="pcg-main-wrapper"><button class="pcg-nav-btn pcg-prev" id="pcg-prev-btn">&#10094;</button><div class="pcg-main-image" id="pcg-main-viewport"><?php echo wp_get_attachment_image($all_ids[0], 'large'); ?></div><button class="pcg-nav-btn pcg-next" id="pcg-next-btn">&#10095;</button></div>
        <div class="pcg-thumbnails">
            <?php foreach ($all_ids as $index => $img_id) : $thumb_src = wp_get_attachment_image_src($img_id, 'thumbnail'); $large_src = wp_get_attachment_image_src($img_id, 'large'); $thumb_url = $thumb_src ? $thumb_src[0] : ''; $large_url = $large_src ? $large_src[0] : ''; $srcset = wp_get_attachment_image_srcset($img_id, 'large'); if (!$thumb_url) continue; ?>
                <div class="pcg-thumb"><img src="<?php echo esc_url($thumb_url); ?>" data-large="<?php echo esc_url($large_url); ?>" data-srcset="<?php echo esc_attr($srcset); ?>" class="<?php echo ($index === 0) ? 'active' : ''; ?>" alt="Mower Image Thumbnail"></div>
            <?php endforeach; ?>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const thumbs = document.querySelectorAll('.pcg-thumb img'); const mainImg = document.querySelector('#pcg-main-viewport img'); const prevBtn = document.querySelector('#pcg-prev-btn'); const nextBtn = document.querySelector('#pcg-next-btn');
        if (!thumbs.length || !mainImg) return;
        function updateMainImage(index) { const selectedThumb = thumbs[index]; mainImg.style.opacity = '0.5'; setTimeout(() => { mainImg.src = selectedThumb.dataset.large; mainImg.srcset = selectedThumb.dataset.srcset; mainImg.style.opacity = '1'; }, 150); thumbs.forEach(t => t.classList.remove('active')); selectedThumb.classList.add('active'); }
        thumbs.forEach((thumb, index) => { thumb.addEventListener('click', () => updateMainImage(index)); });
        nextBtn.addEventListener('click', function() { let currentIndex = Array.from(thumbs).findIndex(img => img.classList.contains('active')); let nextIndex = (currentIndex + 1) % thumbs.length; updateMainImage(nextIndex); });
        prevBtn.addEventListener('click', function() { let currentIndex = Array.from(thumbs).findIndex(img => img.classList.contains('active')); let prevIndex = (currentIndex - 1 + thumbs.length) % thumbs.length; updateMainImage(prevIndex); });
    });
    </script>
    <?php return ob_get_clean();
}
add_shortcode( 'mower_gallery_view', 'mower_display_gallery_shortcode' );

add_shortcode('mower_name_display', function() { $name_model = get_field('mower_name_&_model'); return $name_model ? sprintf('<h2 class="mower-title" style="margin: 10px 0; font-size: 28px; font-weight: 700; color: #333;">%s</h2>', esc_html($name_model)) : ''; });

/* ==========================================================================
   7. WOOCOMMERCE BRIDGE SYSTEM (DYNAMIC OVERRIDES)
   ========================================================================== */

add_action('wp_ajax_add_mower_to_cart', 'handle_mower_cart');
add_action('wp_ajax_nopriv_add_mower_to_cart', 'handle_mower_cart');

function handle_mower_cart() {
    if ( is_null( WC()->cart ) ) { wc_load_cart(); }
    $s_id = intval($_POST['mower_id']);
    $base_id = 14264; 

    $p = get_field('mower_price', $s_id);
    $raw_price = floatval(preg_replace('/[^0-9.]/', '', $p));
    
    $img = get_the_post_thumbnail_url($s_id, 'thumbnail');
    if (!$img) { 
        $gallery = get_field('mower_gallery', $s_id); 
        $img = $gallery ? ($gallery[0]['sizes']['thumbnail'] ?? $gallery[0]['url']) : ''; 
    }

    $data = [ 'mower_data' => [ 'title' => get_the_title($s_id), 'price' => $raw_price, 'image' => $img ], 'unique_key' => md5($s_id . microtime()) ];

    WC()->cart->add_to_cart($base_id, 1, 0, [], $data);
    wp_send_json_success(['redirect' => ($_POST['buy_now'] == 'true' ? wc_get_checkout_url() : wc_get_cart_url())]);
    wp_die();
}

add_action( 'woocommerce_before_calculate_totals', function( $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
    foreach ( $cart->get_cart() as $item ) { if ( isset( $item['mower_data'] ) ) { $item['data']->set_price( $item['mower_data']['price'] ); $item['data']->set_name( $item['mower_data']['title'] ); } }
}, 10, 1 );

add_filter( 'woocommerce_cart_item_thumbnail', function( $thumb, $item ) {
    if ( isset( $item['mower_data']['image'] ) ) return sprintf('<img src="%s" style="width:70px; border-radius:4px;">', esc_url($item['mower_data']['image']));
    return $thumb;
}, 10, 2 );

/* ==========================================================================
   8. GLOBAL HOOK LAYOUT ADJUSTMENTS & STYLING OVERRIDES
   ========================================================================== */

add_action('wp_head', function() {
    if ( is_singular('mower_lot') ) { 
        ?>
        <style type="text/css">
            aside#sidebar, .aux-sidebar-primary, .aux-widget-area { display: none !important; }
            #primary.aux-primary, main#main { width: 100% !important; max-width: 100% !important; flex: 0 0 100% !important; }
            .mower-buy-btn-container { display: flex !important; gap: 15px !important; margin: 20px 0 !important; }
            .mower-buy-btn { flex: 1 !important; transition: all 0.3s ease; }
            .mower-buy-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        </style>
        <?php
    }
}, 999);

/* ==========================================================================
   9. ADMIN LAYOUT COMPLIANCE BANNER
   ========================================================================== */

add_action('admin_footer', function() {
    $screen = get_current_screen(); if (!$screen || $screen->post_type !== 'mower_lot') return;
?>
<script>
jQuery(document).ready(function($){
    function insertMowerBanner() {
        if ($('.mower-locked-banner').length) return; var group = $('.acf-field-group, .acf-postbox').first();
        if (group.length) { var banner = `<div class="mower-locked-banner" style="border: 2px solid #c9bfa5; background: #d9cfb8; padding: 20px; margin: 10px 0 15px 0; text-align: center;"><div style="font-size:16px;font-weight:700;color:#333;">REQUIRED DIMENSIONS: 1200x900px (4:3 Landscape View)</div></div>`; group.before(banner); return true; } return false;
    }
    var attempts = 0; var interval = setInterval(function(){ if (insertMowerBanner()) clearInterval(interval); if (attempts++ > 40) clearInterval(interval); }, 250);
});
</script>
<?php
});