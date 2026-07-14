<?php
/**
 * Plugin Name: Play Set Inventory - Master
 * Description: A specialized inventory management and e-commerce solution for selling play sets and outdoor swing sets. Combines custom data structures with deep WooCommerce integration.
 * Version: 2.2.0
 * Author: Erick Villeta
 * Plugin URI: https://ericksonvilleta.com
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ==========================================================================
   1. CORE REGISTRATION (CPT, TAXONOMY & CUSTOM STATUS)
   ========================================================================== */

function register_play_set_system() {
    register_post_type("play_set_lot", [
        "labels" => [
            "name" => "Play Set Inventory",
            "singular_name" => "Play Set",
            "menu_name" => "Play Set Lot",
            "add_new" => "Add New Play Set",
            "add_new_item" => "Add New Play Set to Lot",
            "edit_item" => "Edit Play Set Details",
            "view_item" => "View Play Set",
        ],
        "public" => true,
        "show_in_menu" => true,
        "menu_icon" => "https://cornerstonelandscapesupply.com/wp-content/uploads/adminify-custom-icons/playset.ico", 
        "supports" => ["title", "editor", "thumbnail", "revisions"],
        "has_archive" => true,
        "show_in_rest" => true,
    ]);

    register_taxonomy("play_set_category", ["play_set_lot"], [
        "hierarchical" => true,
        "labels" => ["name" => "Play Set Categories", "singular_name" => "Play Set Category"],
        "show_ui" => true,
        "show_admin_column" => true,
        "show_in_rest" => true,
        "rewrite" => ["slug" => "play-set-category"],
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
add_action('init', 'register_play_set_system');

add_action('admin_head', function() {
    echo '<style>#menu-posts-play_set_lot .wp-menu-image img { padding: 5px 0; filter: brightness(0); }</style>';
});

add_action('admin_footer-edit.php', 'playset_append_sold_status');
add_action('admin_footer-post.php', 'playset_append_sold_status');
function playset_append_sold_status() {
    global $post;
    if ($post && $post->post_type !== 'play_set_lot') return;
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
    add_meta_box('playset_pdf_box', 'Play Set PDF Spec Sheet', 'render_playset_pdf_box', 'play_set_lot', 'side');
    add_meta_box('playset_status_box', 'Availability Status', 'render_playset_status_box', 'play_set_lot', 'side');
});

function render_playset_pdf_box($post) {
    $pdf_url = get_post_meta($post->ID, '_playset_spec_sheet_url', true);
    wp_nonce_field('save_playset_meta', 'playset_meta_nonce');
    echo '<input type="text" name="playset_pdf_url" value="'.esc_attr($pdf_url).'" style="width:100%;" placeholder="PDF URL">';
}

function render_playset_status_box($post) {
    $is_sold = get_post_meta($post->ID, '_is_sold', true);
    echo '<label><input type="checkbox" name="playset_is_sold" value="1" '.checked($is_sold, 1, false).'> <strong style="color:red;">Mark as SOLD (Manual Meta)</strong></label>';
    echo '<p class="description">Note: Changing the "Status" dropdown to "Mark as Sold" will unpublish this play set.</p>';
}

add_action('save_post', function($post_id) {
    if (!isset($_POST['playset_meta_nonce']) || !wp_verify_nonce($_POST['playset_meta_nonce'], 'save_playset_meta')) return;
    update_post_meta($post_id, '_playset_spec_sheet_url', esc_url_raw($_POST['playset_pdf_url']));
    update_post_meta($post_id, '_is_sold', isset($_POST['playset_is_sold']) ? 1 : 0);
});

/* ==========================================================================
   3. SIDEBAR FILTERS SHORTCODE
   ========================================================================== */

add_shortcode('playset_inventory_filters', function() {
    global $wpdb;
    $terms = get_terms(['taxonomy' => 'play_set_category', 'hide_empty' => false]);

    $prices = $wpdb->get_col("SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = 'retail_price' AND meta_value != '' AND post_id IN (SELECT ID FROM $wpdb->posts WHERE post_type = 'play_set_lot' AND post_status = 'publish')");
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
    $color_c = $wpdb->get_col("SELECT DISTINCT meta_value FROM $wpdb->postmeta WHERE meta_key = 'color' AND meta_value != '' ORDER BY meta_value ASC");

    ob_start(); ?>
    <div class="playset-sidebar-filters" style="max-width:350px; background:#fff; padding:20px; border:2px solid #eee; border-radius:10px; font-family:sans-serif;">
        <div style="margin-bottom:25px;">
            <label style="display:block; font-weight:bold; margin-bottom:10px; font-size:14px;">Sort By</label>
            <select class="playset-sort-dropdown" style="width:100%; padding:12px; border-radius:5px; border:1px solid #ddd; margin-bottom:15px;">
                <option value="numeric">A to Z</option>
                <option value="date-desc">Newest First</option>
                <option value="price-asc">Price: Low to High</option>
                <option value="price-desc">Price: High to Low</option>
            </select>
            <button class="clear-playset-filters" style="width:100%; padding:12px; background:#f8f8f8; color:#555; border:1px solid #bbb; border-radius:5px; font-weight:700; cursor:pointer; text-transform:uppercase; font-size:12px;">Reset All Filters</button>
        </div>

        <div style="margin-bottom:25px; padding:15px 0; border-top:1px solid #eee; border-bottom:1px solid #eee;">
            <label style="display:block; font-weight:bold; margin-bottom:10px; font-size:14px;">Max Price: <span class="playset-price-display" style="color:#81B716;">$<?php echo number_format($max_p); ?></span></label>
            <input type="range" class="playset-price-range" min="0" max="<?php echo $max_p; ?>" value="<?php echo $max_p; ?>" step="100" style="width:100%; cursor:pointer; accent-color:#81B716;">
        </div>

        <div style="margin-bottom:25px;">
            <label style="display:block; font-weight:bold; font-size:13px; margin-bottom:8px;">Color</label>
            <select class="playset-color-filter" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:5px;">
                <option value="all">Any Color</option>
                <?php foreach($color_c as $c) { echo '<option value="'.esc_attr(sanitize_title($c)).'">'.esc_html($c).'</option>'; } ?>
            </select>
        </div>

        <div style="padding-top:15px; border-top:1px solid #eee; display:flex; flex-direction:column; gap:10px;">
            <label style="font-weight:bold; margin-bottom:5px; font-size:14px;">Filter by Type</label>
            <button class="playset-filter-btn active" data-filter="all" style="width:100%; padding:12px; border-radius:30px; border:none; background:#81B716; color:#fff; font-weight:600; cursor:pointer;">All Types</button>
            <?php if (!is_wp_error($terms) && !empty($terms)) : ?>
                <?php foreach($terms as $t): ?>
                    <button class="playset-filter-btn" data-filter="cat-<?php echo esc_attr($t->slug); ?>" style="width:100%; padding:12px; border-radius:30px; border:1px solid #ddd; background:#fff; cursor:pointer; font-weight:600; color:#333;"><?php echo esc_html($t->name); ?></button>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($){
        const defMax = <?php echo $max_p; ?>;
        
        function applyFilters() {
            let parsedPrice = parseFloat($('.playset-price-range').first().val());
            const maxPrice = isNaN(parsedPrice) ? defMax : parsedPrice;
            const selectedColor = $('.playset-color-filter').first().val() || 'all';
            const selectedCat = $('.playset-filter-btn.active').first().attr('data-filter') || 'all';
            const sortBy = $('.playset-sort-dropdown').first().val() || 'numeric';

            $('.playset-price-display').text('$' + maxPrice.toLocaleString());
            
            const grid = $('#main-playset-grid');
            let cards = grid.children('.playset-card').get();

            $.each(cards, function(i, el){
                const card = $(el);
                const cPrice = parseFloat(card.attr('data-price')) || 0;
                const cColor = card.attr('data-color') || ''; 
                
                const matchCat = (selectedCat === 'all' || card.hasClass(selectedCat));
                const matchPrice = (cPrice <= maxPrice);
                const matchColor = (selectedColor === 'all' || cColor === selectedColor);
                
                if (matchCat && matchPrice && matchColor) { card.show(); } else { card.hide(); }
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

        $('body').on('input change', '.playset-price-range', function() { $('.playset-price-range').val($(this).val()); applyFilters(); });
        $('body').on('change', '.playset-color-filter, .playset-sort-dropdown', function() { const selector = '.' + $(this).attr('class').split(' ').join('.'); $(selector).val($(this).val()); applyFilters(); });
        $('body').on('click', '.playset-filter-btn', function(e){ e.preventDefault(); const filterType = $(this).attr('data-filter'); $('.playset-filter-btn').css({'background':'#fff','color':'#333','border':'1px solid #ddd'}).removeClass('active'); $('.playset-filter-btn[data-filter="'+filterType+'"]').css({'background':'#81B716','color':'#fff','border':'none'}).addClass('active'); applyFilters(); });
        $('body').on('click', '.clear-playset-filters', function(e){ e.preventDefault(); $('.playset-filter-btn').css({'background':'#fff','color':'#333','border':'1px solid #ddd'}).removeClass('active'); $('.playset-filter-btn[data-filter="all"]').css({'background':'#81B716','color':'#fff','border':'none'}).addClass('active'); $('.playset-price-range').val(defMax); $('.playset-color-filter').val('all'); $('.playset-sort-dropdown').val('numeric'); applyFilters(); });
    });
    </script>
    <?php return ob_get_clean();
});

/* ==========================================================================
    4. INVENTORY GRID
    ========================================================================== */

add_shortcode('playset_inventory_grid', function() {
    global $post;
    $playset_posts = get_posts(['post_type' => 'play_set_lot', 'posts_per_page' => -1, 'post_status' => 'publish']);
    if (empty($playset_posts)) return '<p>No play sets available.</p>';
    usort($playset_posts, function($a, $b) { return strnatcasecmp($a->post_title, $b->post_title); });

    $output = '<div id="main-playset-grid" class="playset-inventory-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">';
    
    foreach ($playset_posts as $post) {
        setup_postdata($post);
        $p_id = $post->ID;
        
        $img_html = get_the_post_thumbnail($p_id, 'medium', ['style' => 'width:100%; height:200px; object-fit:cover; display:block;']);
        if (empty($img_html)) {
            $gal = get_field('playset_gallery', $p_id);
            $url = $gal ? ($gal[0]['sizes']['medium'] ?? $gal[0]['url']) : '';
            $img_html = $url ? '<img src="'.esc_url($url).'" style="width:100%; height:200px; object-fit:cover;">' : '<div style="height:200px; background:#f5f5f5;"></div>';
        }

        $price = get_field('retail_price', $p_id);
        $raw_p = $price ? floatval(preg_replace('/[^0-9.]/', '', $price)) : 0;
        
        $tax = get_the_terms($p_id, 'play_set_category');
        $cls_array = [];
        if (!empty($tax) && !is_wp_error($tax)) { foreach ($tax as $t) { $cls_array[] = 'cat-' . esc_attr($t->slug); } }
        $cls = implode(' ', $cls_array);
        
        $is_sold = get_post_meta($p_id, '_is_sold', true);
        $raw_color = get_field('color', $p_id);
        $c_color_slug = $raw_color ? sanitize_title($raw_color) : '';

        $output .= sprintf(
            '<div class="playset-card %s" data-price="%s" data-color="%s" data-date="%d" style="position:relative; border:1px solid #ddd; border-radius:8px; overflow:hidden; background:#fff;">
                %s %s
                <div style="padding:15px;">
                    <h3 style="margin:0 0 5px 0; font-size:18px;">%s</h3>
                    <p style="color:#81B716; font-weight:bold; font-size:18px;">$%s</p>
                    <a href="%s" style="display:block; text-align:center; background:#81B716; color:#fff; padding:10px; border-radius:4px; text-decoration:none;">VIEW DETAILS</a>
                </div>
            </div>',
            $cls, $raw_p, esc_attr($c_color_slug), get_the_time('U', $p_id),
            ($is_sold ? '<div style="position:absolute; background:red; color:#fff; padding:5px; z-index:5;">SOLD</div>' : ''), 
            $img_html, get_the_title($p_id), number_format($raw_p, 2), get_permalink($p_id)
        );
    }
    
    $output .= '</div>';
    wp_reset_postdata();
    return $output;
});

/* ==========================================================================
   5. SINGLE PAGE SHORTCODES & BUTTON LOGIC (CLONED FROM SHEDS)
   ========================================================================== */

add_shortcode('playset_buy_button', function() {
    if (get_post_meta(get_the_ID(), '_is_sold', true) || get_post_status(get_the_ID()) === 'sold') return '<p style="color:red; font-weight:bold; font-size: 24px; text-align: center;">SOLD</p>';
    ob_start(); ?>
    <div class="playset-buy-btn-container">
        <button class="playset-buy-btn" data-id="<?php the_ID(); ?>" data-now="false" style="padding:15px; border-radius:35px; border:1px solid #ccc; font-weight:bold; cursor:pointer;">ADD TO CART</button>
        <button class="playset-buy-btn" data-id="<?php the_ID(); ?>" data-now="true" style="padding:15px; border-radius:35px; background:#81B716; color:#fff; border:none; font-weight:bold; cursor:pointer;">BUY NOW</button>
    </div>
    <script>
    jQuery(document).ready(function($){
        $('.playset-buy-btn').click(function(){
            var b = $(this); b.prop('disabled', true).text('Processing...');
            $.post('<?php echo admin_url('admin-ajax.php'); ?>', { action: 'add_playset_to_cart', playset_id: b.data('id'), buy_now: b.data('now') }, function(r) { if(r.success) window.location.href = r.data.redirect; });
        });
    });
    </script>
    <?php return ob_get_clean();
});

add_shortcode('playset_package_details', function() {
    $output = '<div class="playset-package-info"><ul style="list-style:none; padding:0; margin:0;">';
    $text_fields = ['Model'=>'playset_name_&_model', 'Color'=>'color', 'Roof Type'=>'roof_type', 'Swing Beam'=>'swing_beam', 'Monkey Bar'=>'monkey_bar', 'Glider'=>'glider', 'Swing'=>'swing', 'Panel'=>'panel', 'Height'=>'height'];
    foreach ($text_fields as $label => $key) { $val = get_field($key); if ($val) { $output .= sprintf('<li style="margin-bottom:8px; border-bottom:1px solid #f0f0f0; padding-bottom:4px;"><strong>%s:</strong> %s</li>', esc_html($label), esc_html($val)); } }
    if (have_rows('towers')) { $tower_list = []; while (have_rows('towers')) { the_row(); $tower_list[] = get_sub_field('tower'); } $output .= sprintf('<li style="margin-bottom:8px; border-bottom:1px solid #f0f0f0; padding-bottom:4px;"><strong>Towers:</strong> %s</li>', esc_html(implode(', ', $tower_list))); }
    if (have_rows('accesses')) { $access_list = []; while (have_rows('accesses')) { the_row(); $access_list[] = get_sub_field('access'); } $output .= sprintf('<li style="margin-bottom:8px; border-bottom:1px solid #f0f0f0; padding-bottom:4px;"><strong>Accesses:</strong> %s</li>', esc_html(implode(', ', $access_list))); }
    if (have_rows('slides')) { $slide_list = []; while (have_rows('slides')) { the_row(); $slide_list[] = get_sub_field('slide'); } $output .= sprintf('<li style="margin-bottom:8px; border-bottom:1px solid #f0f0f0; padding-bottom:4px;"><strong>Slides:</strong> %s</li>', esc_html(implode(', ', $slide_list))); }
    if (have_rows('add-on_play_features')) { $addon_list = []; while (have_rows('add-on_play_features')) { the_row(); $addon_list[] = get_sub_field('add-on'); } $output .= sprintf('<li style="margin-bottom:8px; border-bottom:1px solid #f0f0f0; padding-bottom:4px;"><strong>Play Features:</strong> %s</li>', esc_html(implode(', ', $addon_list))); }
    $trapeze = get_field('trapeze'); if ($trapeze) { $val = is_array($trapeze) ? implode(', ', $trapeze) : $trapeze; $output .= sprintf('<li style="margin-bottom:8px; border-bottom:1 solid #f0f0f0; padding-bottom:4px;"><strong>Trapeze:</strong> %s</li>', esc_html($val)); }
    return $output . '</ul></div>';
});

add_shortcode('playset_info_display', function() { $info = get_field('playset_info'); return $info ? '<div class="playset-description" style="margin:20px 0; line-height:1.6; color:#444;">' . wpautop(esc_html($info)) . '</div>' : ''; });
add_shortcode('playset_installation_info', function() {
    $fields = ['Recommended Area'=>'recommended_area', 'Recommended Border'=>'recommended_border', 'Recommended Mulch'=>'recommended_mulch', 'Weed Guard'=>'weed_guard'];
    $output = '<div class="installation-box" style="background:#f9f9f9; padding:20px; border-radius:8px; border-left:4px solid #81B716;"><ul style="list-style:none; padding:0; margin:0;">';
    foreach ($fields as $label => $key) { $val = get_field($key); if ($val) $output .= sprintf('<li style="margin-bottom:10px;"><strong>%s:</strong><br>%s</li>', esc_html($label), esc_html($val)); }
    return $output . '</ul></div>';
});

add_shortcode('playset_retail_price', function() {
    $price = get_field('retail_price'); 
    $raw_p = $price ? floatval(preg_replace('/[^0-9.]/', '', $price)) : 0;
    return $raw_p ? '<div class="playset-price-tag" style="font-size:32px; font-weight:800; color:#81B716;">$' . number_format($raw_p, 2) . '</div>' : ''; 
});

add_shortcode('playset_visualize_qr', function() { $qr = get_field('qr_image'); if (!$qr) return ''; $url = is_array($qr) ? $qr['url'] : wp_get_attachment_url($qr); return sprintf('<div class="qr-visualize" style="text-align:center; padding:20px; border:1px dashed #ccc; background:#fff;"><p style="margin-top:0;"><strong>Visualize It At Home</strong></p><img src="%s" style="max-width:150px; height:auto;"><p style="font-size:12px; color:#777; margin-bottom:0;">Scan with your phone camera to see this in your yard.</p></div>', esc_url($url)); });

/* ==========================================================================
   PLAYSET CUSTOM GALLERY WITH NAVIGATION
   ========================================================================== */

function pcg_enqueue_assets() {
    wp_register_style( 'pcg-style', false ); wp_enqueue_style( 'pcg-style' );
    wp_add_inline_style( 'pcg-style', ".pcg-container { display: flex; flex-direction: column; gap: 15px; max-width: 100%; margin: 20px 0; position: relative; } .pcg-main-wrapper { position: relative; width: 100%; overflow: hidden; border-radius: 8px; } .pcg-main-image img { width: 100%; height: auto; border-radius: 8px; transition: opacity 0.3s ease; display: block; object-fit: contain; background: #f9f9f9; } .pcg-nav-btn { position: absolute; top: 50%; transform: translateY(-50%); background: rgba(0,0,0,0.5); color: #fff; border: none; padding: 15px 10px; cursor: pointer; font-size: 20px; z-index: 10; border-radius: 4px; transition: background 0.2s; } .pcg-nav-btn:hover { background: rgba(0,0,0,0.8); } .pcg-prev { left: 10px; } .pcg-next { right: 10px; } .pcg-thumbnails { display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 10px; margin-top: 10px; } .pcg-thumb img { width: 100%; height: 80px; object-fit: cover; cursor: pointer; border: 2px solid transparent; border-radius: 4px; transition: 0.2s; } .pcg-thumb img.active { border-color: #81B716; } .pcg-thumb img:hover { opacity: 0.8; }" );
}
add_action( 'wp_enqueue_scripts', 'pcg_enqueue_assets' );

function pcg_display_gallery() {
    $post_id = get_the_ID(); $featured_id = get_post_thumbnail_id($post_id); $gallery_images = get_field('playset_gallery', $post_id); 
    if ( !$featured_id && empty($gallery_images) ) return '';
    $all_ids = array(); if ($featured_id) $all_ids[] = $featured_id;
    if ($gallery_images) { foreach ($gallery_images as $img) { $id = is_array($img) ? $img['ID'] : $img; if ($id !== $featured_id) $all_ids[] = $id; } }

    ob_start();
    ?>
    <div class="pcg-container">
        <div class="pcg-main-wrapper"><button class="pcg-nav-btn pcg-prev" id="pcg-prev-btn">&#10094;</button><div class="pcg-main-image" id="pcg-main-viewport"><?php echo wp_get_attachment_image($all_ids[0], 'large'); ?></div><button class="pcg-nav-btn pcg-next" id="pcg-next-btn">&#10095;</button></div>
        <div class="pcg-thumbnails">
            <?php foreach ($all_ids as $index => $img_id) : $thumb_src = wp_get_attachment_image_src($img_id, 'thumbnail'); $large_src = wp_get_attachment_image_src($img_id, 'large'); $thumb_url = $thumb_src ? $thumb_src[0] : ''; $large_url = $large_src ? $large_src[0] : ''; $srcset = wp_get_attachment_image_srcset($img_id, 'large'); if (!$thumb_url) continue; ?>
                <div class="pcg-thumb"><img src="<?php echo esc_url($thumb_url); ?>" data-large="<?php echo esc_url($large_url); ?>" data-srcset="<?php echo esc_attr($srcset); ?>" class="<?php echo ($index === 0) ? 'active' : ''; ?>" alt="Play Set Thumbnail"></div>
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
add_shortcode( 'playset_gallery_view', 'pcg_display_gallery' );

add_shortcode('playset_name_display', function() { $name_model = get_field('playset_name_&_model'); return $name_model ? sprintf('<h2 class="playset-title" style="margin: 10px 0; font-size: 28px; font-weight: 700; color: #333;">%s</h2>', esc_html($name_model)) : ''; });
add_shortcode('playset_safety_spec', function() {
    $image = get_field('safety_specification_image'); if (!$image) return '';
    $url = is_array($image) ? $image['url'] : wp_get_attachment_url($image); $alt = is_array($image) ? $image['alt'] : get_the_title();
    ob_start(); ?>
    <div class="playset-safety-spec-container" style="margin: 20px 0; border: 1px solid #eee; padding: 15px; border-radius: 8px; background: #fff;">
        <h4 style="margin-top: 0; font-size: 16px; text-transform: uppercase; letter-spacing: 1px; color: #666;">Safety Specifications</h4><img src="<?php echo esc_url($url); ?>" alt="<?php echo esc_attr($alt); ?>" style="max-width: 100%; height: auto; border-radius: 4px; display: block;"><p style="font-size: 12px; color: #999; margin-top: 10px; font-style: italic;">*Safety specifications provided by Adventure World Playsets</p>
    </div>
    <?php return ob_get_clean();
});

/* ==========================================================================
   6. WOOCOMMERCE SYSTEM (NAME, PRICE, & IMAGE FIX) - CLONED FROM SHEDS
   ========================================================================== */

add_action('wp_ajax_add_playset_to_cart', 'handle_playset_cart');
add_action('wp_ajax_nopriv_add_playset_to_cart', 'handle_playset_cart');

function handle_playset_cart() {
    if ( is_null( WC()->cart ) ) { wc_load_cart(); }
    $s_id = intval($_POST['playset_id']);
    $base_id = 12351; 

    $p = get_field('retail_price', $s_id);
    
    $raw_price = floatval(preg_replace('/[^0-9.]/', '', $p));
    $img = get_the_post_thumbnail_url($s_id, 'thumbnail');
    if (!$img) { $gal = get_field('playset_gallery', $s_id); $img = $gal ? ($gal[0]['sizes']['thumbnail'] ?? $gal[0]['url']) : ''; }

    $data = [ 'playset_data' => [ 'title' => get_the_title($s_id), 'price' => $raw_price, 'image' => $img ], 'unique_key' => md5($s_id . microtime()) ];

    WC()->cart->add_to_cart($base_id, 1, 0, [], $data);
    wp_send_json_success(['redirect' => ($_POST['buy_now'] == 'true' ? wc_get_checkout_url() : wc_get_cart_url())]);
    wp_die();
}

add_action( 'woocommerce_before_calculate_totals', function( $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
    foreach ( $cart->get_cart() as $item ) { if ( isset( $item['playset_data'] ) ) { $item['data']->set_price( $item['playset_data']['price'] ); $item['data']->set_name( $item['playset_data']['title'] ); } }
}, 10, 1 );

add_filter( 'woocommerce_cart_item_thumbnail', function( $thumb, $item ) {
    if ( isset( $item['playset_data']['image'] ) ) return sprintf('<img src="%s" style="width:70px; border-radius:4px;">', esc_url($item['playset_data']['image']));
    return $thumb;
}, 10, 2 );

/* ==========================================================================
   7. RELATED PLAY SETS
   ========================================================================== */

add_shortcode('related_playsets', function() {
    $p_id = get_the_ID(); $terms = get_the_terms($p_id, 'play_set_category'); if (empty($terms) || is_wp_error($terms)) return '';
    $term_ids = wp_list_pluck($terms, 'term_id');
    $args = ['post_type' => 'play_set_lot', 'posts_per_page' => 3, 'post__not_in' => [$p_id], 'tax_query' => [['taxonomy' => 'play_set_category', 'field' => 'term_id', 'terms' => $term_ids]], 'orderby' => 'rand', 'post_status' => 'publish'];
    $query = new WP_Query($args); if (!$query->have_posts()) return '';
    
    $output = '<div class="related-playsets-section" style="margin-top:60px; border-top:2px solid #eee; padding-top:40px; font-family: sans-serif;"><h3 style="text-transform:uppercase; margin-bottom:30px; letter-spacing:1px; font-size:22px; color: #333;">Recommended Play Sets</h3><div class="playset-inventory-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">';
    
    while ($query->have_posts()) { 
        $query->the_post(); $rel_id = get_the_ID(); $is_sold = get_post_meta($rel_id, '_is_sold', true);
        $img_html = get_the_post_thumbnail($rel_id, 'medium', ['style' => 'width:100%; height:200px; object-fit:cover; display:block;']);
        
        $price = get_field('retail_price', $rel_id);
        $raw_p = $price ? floatval(preg_replace('/[^0-9.]/', '', $price)) : 0;
        
        $output .= sprintf('<div class="playset-card" style="position:relative; border:1px solid #ddd; border-radius:8px; overflow:hidden; background:#fff; %s">%s<div style="padding:15px;"><h3 style="margin:0 0 5px 0; font-size:18px;">%s</h3><p style="color:#81B716; font-weight:bold; font-size:18px;">$%s</p><a href="%s" style="display:block; text-align:center; background:#81B716; color:#fff; padding:12px; border-radius:4px; text-decoration:none; font-weight:bold;">VIEW DETAILS</a></div></div>', ($is_sold ? 'opacity:0.7;' : ''), $img_html, get_the_title(), number_format($raw_p, 2), get_permalink()); 
    }
    $output .= '</div></div>'; wp_reset_postdata(); return $output;
});

/* ==========================================================================
   8. THE GLOBAL HAMMER: CSS OVERRIDES
   ========================================================================== */

add_action('wp_head', function() {
    if ( is_singular('play_set_lot') ) { 
        ?>
        <style type="text/css">
            aside#sidebar, .aux-sidebar-primary, .aux-widget-area { display: none !important; }
            #primary.aux-primary, main#main { width: 100% !important; max-width: 100% !important; flex: 0 0 100% !important; }
            .playset-buy-btn-container { display: flex !important; gap: 15px !important; margin: 20px 0 !important; }
            .playset-buy-btn { flex: 1 !important; transition: all 0.3s ease; }
            .playset-buy-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        </style>
        <?php
    }
}, 999);

/* ==========================================================================
   9. ADMIN BANNER (REQUIRED DIMENSIONS)
   ========================================================================== */

add_action('admin_footer', function() {
    $screen = get_current_screen(); if (!$screen || $screen->post_type !== 'play_set_lot') return;
?>
<script>
jQuery(document).ready(function($){
    function insertPlaysetBanner() {
        if ($('.playset-locked-banner').length) return; var group = $('.acf-field-group, .acf-postbox').first();
        if (group.length) { var banner = `<div class="playset-locked-banner" style="border: 2px solid #c9bfa5; background: #d9cfb8; padding: 20px; margin: 10px 0 15px 0; text-align: center;"><div style="font-size:16px;font-weight:700;color:#333;">REQUIRED: 1200x900px (4:3 Landscape)</div></div>`; group.before(banner); return true; } return false;
    }
    var attempts = 0; var interval = setInterval(function(){ if (insertPlaysetBanner()) clearInterval(interval); if (attempts++ > 40) clearInterval(interval); }, 250);
});
</script>
<?php
});