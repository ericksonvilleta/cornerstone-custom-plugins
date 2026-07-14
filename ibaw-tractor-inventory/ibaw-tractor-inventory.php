<?php
/**
 * Plugin Name: IBAW - Tractor Inventory - Master
 * Description: A specialized inventory management and e-commerce solution for selling tractors. Combines custom data structures with deep WooCommerce integration.
 * Version:     1.0
 * Author:      Erick Villeta
 * Plugin URI:  https://ericksonvilleta.com
 * Text Domain: ibaw-plugin
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ==========================================================================
   1. CORE REGISTRATION (CPT, TAXONOMY & CUSTOM STATUS)
   ========================================================================== */

function register_tractor_system() {
    register_post_type("tractor_lot", [
        "labels" => [
            "name" => "Tractor Inventory",
            "singular_name" => "Tractor",
            "menu_name" => "Tractor Lot",
            "add_new" => "Add New Tractor",
            "add_new_item" => "Add New Tractor to Lot",
            "edit_item" => "Edit Tractor Details",
            "view_item" => "View Tractor",
        ],
        "public" => true,
        "show_in_menu" => true,
        "menu_icon" => "https://cornerstonelandscapesupply.com/wp-content/uploads/adminify-custom-icons/tractor.ico", 
        "supports" => ["title", "editor", "thumbnail", "revisions"],
        "has_archive" => true,
        "show_in_rest" => true,
    ]);

    register_taxonomy("tractor_category", ["tractor_lot"], [
        "hierarchical" => true,
        "labels" => ["name" => "Tractor Categories", "singular_name" => "Tractor Category"],
        "show_ui" => true,
        "show_admin_column" => true,
        "show_in_rest" => true,
        "rewrite" => ["slug" => "tractor-category"],
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
add_action('init', 'register_tractor_system');

add_action('admin_head', function() {
    echo '<style>#menu-posts-tractor_lot .wp-menu-image img { padding: 5px 0; filter: brightness(0); }</style>';
});

add_action('admin_footer-edit.php', 'tractor_append_sold_status');
add_action('admin_footer-post.php', 'tractor_append_sold_status');
function tractor_append_sold_status() {
    global $post;
    if ($post && $post->post_type !== 'tractor_lot') return;
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
    add_meta_box('tractor_pdf_box', 'Tractor PDF Spec Sheet', 'render_tractor_pdf_box', 'tractor_lot', 'side');
    add_meta_box('tractor_status_box', 'Availability Status', 'render_tractor_status_box', 'tractor_lot', 'side');
});

function render_tractor_pdf_box($post) {
    $pdf_url = get_post_meta($post->ID, '_tractor_spec_sheet_url', true);
    wp_nonce_field('save_tractor_meta', 'tractor_meta_nonce');
    echo '<input type="text" name="tractor_pdf_url" value="'.esc_attr($pdf_url).'" style="width:100%;" placeholder="PDF URL">';
}

function render_tractor_status_box($post) {
    $is_sold = get_post_meta($post->ID, '_is_sold', true);
    echo '<label><input type="checkbox" name="tractor_is_sold" value="1" '.checked($is_sold, 1, false).'> <strong style="color:red;">Mark as SOLD (Manual Meta)</strong></label>';
    echo '<p class="description">Note: Changing the "Status" dropdown to "Mark as Sold" will unpublish this tractor.</p>';
}

add_action('save_post', function($post_id) {
    if (!isset($_POST['tractor_meta_nonce']) || !wp_verify_nonce($_POST['tractor_meta_nonce'], 'save_tractor_meta')) return;
    update_post_meta($post_id, '_tractor_spec_sheet_url', esc_url_raw($_POST['tractor_pdf_url']));
    update_post_meta($post_id, '_is_sold', isset($_POST['tractor_is_sold']) ? 1 : 0);
});

/* ==========================================================================
   3. SIDEBAR FILTERS SHORTCODE
   ========================================================================== */

add_shortcode('tractor_inventory_filters', function() {
    global $wpdb;
    $terms = get_terms(['taxonomy' => 'tractor_category', 'hide_empty' => false]);

    $prices = $wpdb->get_col("SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = 'tractor_price' AND meta_value != '' AND post_id IN (SELECT ID FROM $wpdb->posts WHERE post_type = 'tractor_lot' AND post_status = 'publish')");
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
    $engine_options = $wpdb->get_col("SELECT DISTINCT meta_value FROM $wpdb->postmeta WHERE meta_key = 'engine' AND meta_value != '' ORDER BY meta_value ASC");

    ob_start(); ?>
    <div class="tractor-sidebar-filters" style="max-width:350px; background:#fff; padding:20px; border:2px solid #eee; border-radius:10px; font-family:sans-serif;">
        <div style="margin-bottom:25px;">
            <label style="display:block; font-weight:bold; margin-bottom:10px; font-size:14px;">Sort By</label>
            <select class="tractor-sort-dropdown" style="width:100%; padding:12px; border-radius:5px; border:1px solid #ddd; margin-bottom:15px;">
                <option value="numeric">A to Z</option>
                <option value="date-desc">Newest First</option>
                <option value="price-asc">Price: Low to High</option>
                <option value="price-desc">Price: High to Low</option>
            </select>
            <button class="clear-tractor-filters" style="width:100%; padding:12px; background:#f8f8f8; color:#555; border:1px solid #bbb; border-radius:5px; font-weight:700; cursor:pointer; text-transform:uppercase; font-size:12px;">Reset All Filters</button>
        </div>

        <div style="margin-bottom:25px; padding:15px 0; border-top:1px solid #eee; border-bottom:1px solid #eee;">
            <label style="display:block; font-weight:bold; margin-bottom:10px; font-size:14px;">Max Price: <span class="tractor-price-display" style="color:#81B716;">$<?php echo number_format($max_p); ?></span></label>
            <input type="range" class="tractor-price-range" min="0" max="<?php echo $max_p; ?>" value="<?php echo $max_p; ?>" step="100" style="width:100%; cursor:pointer; accent-color:#81B716;">
        </div>

        <div style="margin-bottom:25px;">
            <label style="display:block; font-weight:bold; font-size:13px; margin-bottom:8px;">Engine Power</label>
            <select class="tractor-color-filter" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:5px;">
                <option value="all">Any Engine</option>
                <?php foreach($engine_options as $e) { echo '<option value="'.esc_attr(sanitize_title($e)).'">'.esc_html($e).' hp</option>'; } ?>
            </select>
        </div>

        <div style="padding-top:15px; border-top:1px solid #eee; display:flex; flex-direction:column; gap:10px;">
            <label style="font-weight:bold; margin-bottom:5px; font-size:14px;">Filter by Category</label>
            <button class="tractor-filter-btn active" data-filter="all" style="width:100%; padding:12px; border-radius:30px; border:none; background:#81B716; color:#fff; font-weight:600; cursor:pointer;">All Categories</button>
            <?php if (!is_wp_error($terms) && !empty($terms)) : ?>
                <?php foreach($terms as $t): ?>
                    <button class="tractor-filter-btn" data-filter="cat-<?php echo esc_attr($t->slug); ?>" style="width:100%; padding:12px; border-radius:30px; border:1px solid #ddd; background:#fff; cursor:pointer; font-weight:600; color:#333;"><?php echo esc_html($t->name); ?></button>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($){
        const defMax = <?php echo $max_p; ?>;
        
        function applyFilters() {
            let parsedPrice = parseFloat($('.tractor-price-range').first().val());
            const maxPrice = isNaN(parsedPrice) ? defMax : parsedPrice;
            const selectedEngine = $('.tractor-color-filter').first().val() || 'all';
            const selectedCat = $('.tractor-filter-btn.active').first().attr('data-filter') || 'all';
            const sortBy = $('.tractor-sort-dropdown').first().val() || 'numeric';

            $('.tractor-price-display').text('$' + maxPrice.toLocaleString());
            
            const grid = $('#main-tractor-grid');
            let cards = grid.children('.tractor-card').get();

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

        $('body').on('input change', '.tractor-price-range', function() { $('.tractor-price-range').val($(this).val()); applyFilters(); });
        $('body').on('change', '.tractor-color-filter, .tractor-sort-dropdown', function() { const selector = '.' + $(this).attr('class').split(' ').join('.'); $(selector).val($(this).val()); applyFilters(); });
        $('body').on('click', '.tractor-filter-btn', function(e){ e.preventDefault(); const filterType = $(this).attr('data-filter'); $('.tractor-filter-btn').css({'background':'#fff','color':'#333','border':'1px solid #ddd'}).removeClass('active'); $('.tractor-filter-btn[data-filter="'+filterType+'"]').css({'background':'#81B716','color':'#fff','border':'none'}).addClass('active'); applyFilters(); });
        $('body').on('click', '.clear-tractor-filters', function(e){ e.preventDefault(); $('.tractor-filter-btn').css({'background':'#fff','color':'#333','border':'1px solid #ddd'}).removeClass('active'); $('.tractor-filter-btn[data-filter="all"]').css({'background':'#81B716','color':'#fff','border':'none'}).addClass('active'); $('.tractor-price-range').val(defMax); $('.tractor-color-filter').val('all'); $('.tractor-sort-dropdown').val('numeric'); applyFilters(); });
    });
    </script>
    <?php return ob_get_clean();
});

/* ==========================================================================
   4. INVENTORY GRID
   ========================================================================== */

add_shortcode('tractor_inventory_grid', function() {
    global $post;
    $tractor_posts = get_posts(['post_type' => 'tractor_lot', 'posts_per_page' => -1, 'post_status' => 'publish']);
    if (empty($tractor_posts)) return '<p>No tractors available at this moment.</p>';
    usort($tractor_posts, function($a, $b) { return strnatcasecmp($a->post_title, $b->post_title); });

    $output = '<div id="main-tractor-grid" class="tractor-inventory-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">';
    
    foreach ($tractor_posts as $post) {
        setup_postdata($post);
        $t_id = $post->ID;
        
        $img_html = get_the_post_thumbnail($t_id, 'medium', ['style' => 'width:100%; height:200px; object-fit:cover; display:block;']);
        if (empty($img_html)) {
            $gallery = get_field('tractor_gallery', $t_id);
            $url = $gallery ? ($gallery[0]['sizes']['medium'] ?? $gallery[0]['url']) : '';
            $img_html = $url ? '<img src="'.esc_url($url).'" style="width:100%; height:200px; object-fit:cover;">' : '<div style="height:200px; background:#f5f5f5;"></div>';
        }

        $price = get_field('tractor_price', $t_id);
        $raw_p = $price ? floatval(preg_replace('/[^0-9.]/', '', $price)) : 0;
        
        $tax = get_the_terms($t_id, 'tractor_category');
        $cls_array = [];
        if (!empty($tax) && !is_wp_error($tax)) { foreach ($tax as $t) { $cls_array[] = 'cat-' . esc_attr($t->slug); } }
        $cls = implode(' ', $cls_array);
        
        $is_sold = get_post_meta($t_id, '_is_sold', true);
        $raw_engine = get_field('engine', $t_id);
        $engine_slug = $raw_engine ? sanitize_title($raw_engine) : '';

        $output .= sprintf(
            '<div class="tractor-card %s" data-price="%s" data-color="%s" data-date="%d" style="position:relative; border:1px solid #ddd; border-radius:8px; overflow:hidden; background:#fff;">
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

add_shortcode('tractor_buy_button', function() {
    if (get_post_meta(get_the_ID(), '_is_sold', true) || get_post_status(get_the_ID()) === 'sold') return '<p style="color:red; font-weight:bold; font-size: 24px; text-align: center;">SOLD</p>';
    ob_start(); ?>
    <div class="tractor-buy-btn-container">
        <button class="tractor-buy-btn" data-id="<?php the_ID(); ?>" data-now="false" style="padding:15px; border-radius:35px; border:1px solid #ccc; font-weight:bold; cursor:pointer;">ADD TO CART</button>
        <button class="tractor-buy-btn" data-id="<?php the_ID(); ?>" data-now="true" style="padding:15px; border-radius:35px; background:#81B716; color:#fff; border:none; font-weight:bold; cursor:pointer;">BUY NOW</button>
    </div>
    <script>
    jQuery(document).ready(function($){
        $('.tractor-buy-btn').click(function(){
            var b = $(this); b.prop('disabled', true).text('Processing...');
            $.post('<?php echo admin_url('admin-ajax.php'); ?>', { action: 'add_tractor_to_cart', tractor_id: b.data('id'), buy_now: b.data('now') }, function(r) { if(r.success) window.location.href = r.data.redirect; });
        });
    });
    </script>
    <?php return ob_get_clean();
});

add_shortcode('tractor_package_details', function() {
    $output = '<div class="tractor-specifications-table-wrapper" style="font-family:sans-serif; max-width:100%; margin:20px 0; overflow-x:auto;">';
    
    // Group 1: Tractor Specifications Tab
    $general_specs = [
        'Model Name'                      => ['key' => 'tractor_name_&_model', 'suffix' => ''],
        'Engine Power'                    => ['key' => 'engine', 'suffix' => ' hp'],
        'Loader Capacity'                 => ['key' => 'loader_capacity', 'suffix' => ' lbs'],
        'PTO Power'                       => ['key' => 'pto__hp', 'suffix' => ' hp'],
        'Hitch Lift'                      => ['key' => 'hitch_lift', 'suffix' => ' lbs'],
        'Total Weight'                    => ['key' => 'total_weight', 'suffix' => ' lbs'],
    ];

    // Group 2: Core Height & Reach Specs
    $height_reach_specs = [
        'Maximum lift height'             => ['key' => 'maximum_lift_height', 'suffix' => ' in.'],
        'Maximum lift height at pivot pin'=> ['key' => 'maximum_lift_height_at_pivot_pin', 'suffix' => ' in.'],
        'Clearance with attachment dumped'=> ['key' => 'clearance_with_attachment_dumped', 'suffix' => ' in.'],
        'Reach at maximum height'         => ['key' => 'reach_at_maximum_height', 'suffix' => ' in.'],
        'Maximum dump angle'              => ['key' => 'maximum_dump_angle', 'suffix' => ' deg.'],
        'Reach with attachment on ground' => ['key' => 'reach_with_attachment_on_ground', 'suffix' => ' in.'],
        'Maximum rollback angle'          => ['key' => 'maximum_rollback_angle', 'suffix' => ' deg.'],
        'Digging depth'                   => ['key' => 'digging_depth', 'suffix' => ' in.'],
    ];

    // Group 3: Capacity & Weight Specs
    $capacity_weight_specs = [
        'Lift capacity at pivot pin'      => ['key' => 'lift_capacity_at_pivot_pin', 'suffix' => ' lbs.'],
        'Lift capacity'                   => ['key' => 'lift_capacity', 'suffix' => ' lbs.'],
        'Lift capacity to full height at pivot pin' => ['key' => 'lift_capacity_to_full_height_at_pivot_pin', 'suffix' => ' lbs.'],
        'Breakout force at pivot pin'     => ['key' => 'breakout_force_at_pivot_pin', 'suffix' => ' lbs.'],
        'Breakout force at 500mm'         => ['key' => 'breakout_force_at_500mm', 'suffix' => ' lbs.'],
        'Breakout force'                  => ['key' => 'breakout_force', 'suffix' => ' lbs.'],
        'Rated flow (loader control valve)' => ['key' => 'rated_flow_loader_control_valve', 'suffix' => ' gpm'],
        'Approximate weight (without bucket)' => ['key' => 'approximate_weight_without_bucket', 'suffix' => ' lbs.'],
    ];

    // Group 4: Attachment & Compatibility Specs
    $compatibility_specs = [
        'Bucket size'                     => ['key' => 'bucket_size', 'suffix' => ' in.'],
        'Overall weight with attachment'  => ['key' => 'overall_weight with_attachment', 'suffix' => ' lbs.'],
        'Compatible Tractors'             => ['key' => 'compatible_tractors', 'suffix' => ''],
    ];

    // Group 5: Cutting & Deck Performance (New Section Appended)
    $cutting_deck_specs = [
        'Cutting Width'                   => ['key' => 'cutting_width', 'suffix' => ' in.'],
        'Cutting Height Range'            => ['key' => 'cutting_height_range', 'suffix' => ' in.'],
        'Adjustment of Cutting Height'    => ['key' => 'adjustment_of_cutting_height', 'suffix' => ''],
        'Deck Thickness'                  => ['key' => 'deck_thickness', 'suffix' => ' in.'],
        'Discharge Type'                  => ['key' => 'discharge_type', 'suffix' => ''],
        'Number of Blades'                => ['key' => 'number_of_blades', 'suffix' => ''],
        'Blade Length'                    => ['key' => 'blade_length', 'suffix' => ' in.'],
        'Blade Width'                     => ['key' => 'blade_width', 'suffix' => ' in.'],
        'Blade Thickness'                 => ['key' => 'blade_thickness', 'suffix' => ' in.'],
        'Blade Tip Speed'                 => ['key' => 'blade_tip_speed', 'suffix' => ' ft./min.'],
        'Spindle Speed'                   => ['key' => 'spindle_speed', 'suffix' => ' RPM'],
    ];

    // Group 6: Mechanical & Drivetrain (New Section Appended)
    $mechanical_drivetrain_specs = [
        'Gear Type'                       => ['key' => 'gear_type', 'suffix' => ''],
        'Ratio of Gear'                   => ['key' => 'ratio_of_gear', 'suffix' => ''],
        'Drive Type'                      => ['key' => 'drive_type', 'suffix' => ''],
        'Lifting Type / Mounting Method'  => ['key' => 'lifting_type_mounting_method', 'suffix' => ''],
        'Attach / Detaching Type'         => ['key' => 'attach_detaching_type', 'suffix' => ''],
    ];

    // Group 7: Physical Dimensions & Weight (New Section Appended)
    $physical_dimensions_specs = [
        'Overall Width'                   => ['key' => 'overall_width', 'suffix' => ' in.'],
        'Total Width'                     => ['key' => 'total_width', 'suffix' => ' in.'],
        'Total Length (w/o Linkage)'      => ['key' => 'total_length', 'suffix' => ' in.'],
        'Total Height (w/o Linkage)'      => ['key' => 'total_height', 'suffix' => ' in.'],
        'Transportation Height'           => ['key' => 'transportation_height', 'suffix' => ' in.'],
        'Shipping Weight of Base 60" Deck' => ['key' => 'shipping_weight_of_base_60_deck', 'suffix' => ' lbs.'],
    ];

    $sections = [
        'Tractor Specifications'           => $general_specs,
        'Core Height & Reach Specs'        => $height_reach_specs,
        'Capacity & Weight Specs'          => $capacity_weight_specs,
        'Attachment & Compatibility Specs' => $compatibility_specs,
        'Cutting & Deck Performance'       => $cutting_deck_specs,
        'Mechanical & Drivetrain'          => $mechanical_drivetrain_specs,
        'Physical Dimensions & Weight'     => $physical_dimensions_specs,
    ];

    foreach ($sections as $section_title => $fields) {
        $table_rows = '';
        $row_index = 0;

        foreach ($fields as $label => $data) {
            $val = get_field($data['key']);
            
            if (is_string($val)) {
                $val = trim($val);
            }

            if ($val !== false && $val !== null && $val !== '') {
                $bg_toggle = ($row_index % 2 === 0) ? '#ffffff' : '#f9f9f9';
                $table_rows .= sprintf(
                    '<tr style="background:%s; transition:background 0.2s;">
                        <td style="padding:12px 15px; font-weight:600; color:#333; border-bottom:1px solid #eaeaea; font-size:14px; width:45%%;">%s</td>
                        <td style="padding:12px 15px; color:#555; border-bottom:1px solid #eaeaea; font-size:14px; font-variant-numeric:tabular-nums;">%s%s</td>
                     </tr>',
                    $bg_toggle,
                    esc_html($label),
                    esc_html($val),
                    esc_html($data['suffix'])
                );
                $row_index++;
            }
        }

        if (!empty($table_rows)) {
            $output .= sprintf(
                '<div class="tractor-spec-table-block" style="margin-bottom:35px;">
                    <h3 style="font-size:16px; font-weight:700; text-transform:uppercase; color:#81B716; letter-spacing:0.8px; margin:0 0 12px 0; padding-bottom:6px; border-bottom:2px solid #81B716;">%s</h3>
                    <table style="width:100%%; border-collapse:collapse; text-align:left; background:#fff; border:1px solid #eaeaea; border-radius:4px;">
                        <tbody>%s</tbody>
                    </table>
                 </div>',
                esc_html($section_title),
                $table_rows
            );
        }
    }

    $output .= '</div>';
    return $output;
});

add_shortcode('tractor_info_display', function() { $info = get_field('tractor_info'); return $info ? '<div class="tractor-description" style="margin:20px 0; line-height:1.6; color:#444;">' . wpautop(esc_html($info)) . '</div>' : ''; });

add_shortcode('tractor_retail_price', function() {
    $price = get_field('tractor_price'); 
    $raw_p = $price ? floatval(preg_replace('/[^0-9.]/', '', $price)) : 0;
    return $raw_p ? '<div class="tractor-price-tag" style="font-size:32px; font-weight:800; color:#81B716;">$' . number_format($raw_p, 2) . '</div>' : ''; 
});

/* ==========================================================================
   6. TRACTOR CUSTOM GALLERY WITH NAVIGATION (NAMESPACED FOR CO-EXISTENCE)
   ========================================================================== */

function ibaw_tractor_enqueue_gallery_assets() {
    wp_register_style( 'pcg-tractor-style', false ); 
    wp_enqueue_style( 'pcg-tractor-style' );
    wp_add_inline_style( 'pcg-tractor-style', ".pcg-container { display: flex; flex-direction: column; gap: 15px; max-width: 100%; margin: 20px 0; position: relative; } .pcg-main-wrapper { position: relative; width: 100%; overflow: hidden; border-radius: 8px; } .pcg-main-image img { width: 100%; height: auto; border-radius: 8px; transition: opacity 0.3s ease; display: block; object-fit: contain; background: #f9f9f9; } .pcg-nav-btn { position: absolute; top: 50%; transform: translateY(-50%); background: rgba(0,0,0,0.5); color: #fff; border: none; padding: 15px 10px; cursor: pointer; font-size: 20px; z-index: 10; border-radius: 4px; transition: background 0.2s; } .pcg-nav-btn:hover { background: rgba(0,0,0,0.8); } .pcg-prev { left: 10px; } .pcg-next { right: 10px; } .pcg-thumbnails { display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 10px; margin-top: 10px; } .pcg-thumb img { width: 100%; height: 80px; object-fit: cover; cursor: pointer; border: 2px solid transparent; border-radius: 4px; transition: 0.2s; } .pcg-thumb img.active { border-color: #81B716; } .pcg-thumb img:hover { opacity: 0.8; }" );
}
add_action( 'wp_enqueue_scripts', 'ibaw_tractor_enqueue_gallery_assets' );

function ibaw_tractor_display_gallery_shortcode() {
    $post_id = get_the_ID(); 
    $featured_id = get_post_thumbnail_id($post_id); 
    $gallery_images = get_field('tractor_gallery', $post_id); 
    
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
                <div class="pcg-thumb"><img src="<?php echo esc_url($thumb_url); ?>" data-large="<?php echo esc_url($large_url); ?>" data-srcset="<?php echo esc_attr($srcset); ?>" class="<?php echo ($index === 0) ? 'active' : ''; ?>" alt="Tractor Image Thumbnail"></div>
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
add_shortcode( 'tractor_gallery_view', 'ibaw_tractor_display_gallery_shortcode' );

add_shortcode('tractor_name_display', function() { $name_model = get_field('tractor_name_&_model'); return $name_model ? sprintf('<h2 class="tractor-title" style="margin: 10px 0; font-size: 28px; font-weight: 700; color: #333;">%s</h2>', esc_html($name_model)) : ''; });

/* ==========================================================================
   7. WOOCOMMERCE BRIDGE SYSTEM (DYNAMIC OVERRIDES)
   ========================================================================== */

add_action('wp_ajax_add_tractor_to_cart', 'handle_tractor_cart');
add_action('wp_ajax_nopriv_add_tractor_to_cart', 'handle_tractor_cart');

function handle_tractor_cart() {
    if ( is_null( WC()->cart ) ) { wc_load_cart(); }
    $s_id = intval($_POST['tractor_id']);
    $base_id = 13772; 

    $p = get_field('tractor_price', $s_id);
    $raw_price = floatval(preg_replace('/[^0-9.]/', '', $p));
    
    $img = get_the_post_thumbnail_url($s_id, 'thumbnail');
    if (!$img) { 
        $gallery = get_field('tractor_gallery', $s_id); 
        $img = $gallery ? ($gallery[0]['sizes']['thumbnail'] ?? $gallery[0]['url']) : ''; 
    }

    $data = [ 'tractor_data' => [ 'title' => get_the_title($s_id), 'price' => $raw_price, 'image' => $img ], 'unique_key' => md5($s_id . microtime()) ];

    WC()->cart->add_to_cart($base_id, 1, 0, [], $data);
    wp_send_json_success(['redirect' => ($_POST['buy_now'] == 'true' ? wc_get_checkout_url() : wc_get_cart_url())]);
    wp_die();
}

add_action( 'woocommerce_before_calculate_totals', function( $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
    foreach ( $cart->get_cart() as $item ) { if ( isset( $item['tractor_data'] ) ) { $item['data']->set_price( $item['tractor_data']['price'] ); $item['data']->set_name( $item['tractor_data']['title'] ); } }
}, 10, 1 );

add_filter( 'woocommerce_cart_item_thumbnail', function( $thumb, $item ) {
    if ( isset( $item['tractor_data']['image'] ) ) return sprintf('<img src="%s" style="width:70px; border-radius:4px;">', esc_url($item['tractor_data']['image']));
    return $thumb;
}, 10, 2 );

/* ==========================================================================
   8. RECOMMENDED / RELATED LOTS
   ========================================================================== */
/*
add_shortcode('related_tractors', function() {
    $p_id = get_the_ID(); $terms = get_the_terms($p_id, 'tractor_category'); if (empty($terms) || is_wp_error($terms)) return '';
    $term_ids = wp_list_pluck($terms, 'term_id');
    $args = ['post_type' => 'tractor_lot', 'posts_per_page' => 3, 'post__not_in' => [$p_id], 'tax_query' => [['taxonomy' => 'tractor_category', 'field' => 'term_id', 'terms' => $term_ids]], 'orderby' => 'rand', 'post_status' => 'publish'];
    $args_query = new WP_Query($args); if (!$args_query->have_posts()) return '';
    
    $output = '<div class="related-tractors-section" style="margin-top:60px; border-top:2px solid #eee; padding-top:40px; font-family: sans-serif;"><h3 style="text-transform:uppercase; margin-bottom:30px; letter-spacing:1px; font-size:22px; color: #333;">Recommended Tractor Inventory</h3><div class="tractor-inventory-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">';
    
    while ($args_query->have_posts()) { 
        $args_query->the_post(); $rel_id = get_the_ID(); $is_sold = get_post_meta($rel_id, '_is_sold', true);
        $img_html = get_the_post_thumbnail($rel_id, 'medium', ['style' => 'width:100%; height:200px; object-fit:cover; display:block;']);
        
        $price = get_field('tractor_price', $rel_id);
        $raw_p = $price ? floatval(preg_replace('/[^0-9.]/', '', $price)) : 0;
        
        $output .= sprintf('<div class="tractor-card" style="position:relative; border:1px solid #ddd; border-radius:8px; overflow:hidden; background:#fff; %s">%s<div style="padding:15px;"><h3 style="margin:0 0 5px 0; font-size:18px;">%s</h3><p style="color:#81B716; font-weight:bold; font-size:18px;">$%s</p><a href="%s" style="display:block; text-align:center; background:#81B716; color:#fff; padding:12px; border-radius:4px; text-decoration:none; font-weight:bold;">VIEW DETAILS</a></div></div>', ($is_sold ? 'opacity:0.7;' : ''), $img_html, get_the_title(), number_format($raw_p, 2), get_permalink()); 
    }
    $output .= '</div></div>'; wp_reset_postdata(); return $output;
});
*/
/* ==========================================================================
   9. GLOBAL HOOK LAYOUT ADJUSTMENTS & STYLING OVERRIDES
   ========================================================================== */

add_action('wp_head', function() {
    if ( is_singular('tractor_lot') ) { 
        ?>
        <style type="text/css">
            aside#sidebar, .aux-sidebar-primary, .aux-widget-area { display: none !important; }
            #primary.aux-primary, main#main { width: 100% !important; max-width: 100% !important; flex: 0 0 100% !important; }
            .tractor-buy-btn-container { display: flex !important; gap: 15px !important; margin: 20px 0 !important; }
            .tractor-buy-btn { flex: 1 !important; transition: all 0.3s ease; }
            .tractor-buy-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        </style>
        <?php
    }
}, 999);

/* ==========================================================================
   10. ADMIN LAYOUT COMPLIANCE BANNER
   ========================================================================== */

add_action('admin_footer', function() {
    $screen = get_current_screen(); if (!$screen || $screen->post_type !== 'tractor_lot') return;
?>
<script>
jQuery(document).ready(function($){
    function insertTractorBanner() {
        if ($('.tractor-locked-banner').length) return; var group = $('.acf-field-group, .acf-postbox').first();
        if (group.length) { var banner = `<div class="tractor-locked-banner" style="border: 2px solid #c9bfa5; background: #d9cfb8; padding: 20px; margin: 10px 0 15px 0; text-align: center;"><div style="font-size:16px;font-weight:700;color:#333;">REQUIRED DIMENSIONS: 1200x900px (4:3 Landscape View)</div></div>`; group.before(banner); return true; } return false;
    }
    var attempts = 0; var interval = setInterval(function(){ if (insertTractorBanner()) clearInterval(interval); if (attempts++ > 40) clearInterval(interval); }, 250);
});
</script>
<?php
});