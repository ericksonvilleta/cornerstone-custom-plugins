<?php
/**
 * Plugin Name: IBAW-Shed Inventory Filters
 * Plugin URI: https://ericksonvilleta.com
 * Description: Adds a shortcode [ibaw_shed_filters] to display and handle client-side shed inventory filters inside a vertical accordion.
 * Author: Erick Villeta
 * Version: 1.1.1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

add_shortcode('ibaw_shed_filters', function() {
    global $wpdb;
    
    // 1. Get Categories
    $terms = get_terms(['taxonomy' => 'shed_category', 'hide_empty' => true]);

    // 2. Get Max Price (Cached for 12 hours)
    $max_p = get_transient('ibaw_shed_max_price');
    if ( false === $max_p ) {
        $max_p_query = $wpdb->get_var("SELECT MAX(CAST(meta_value AS DECIMAL(10,2))) FROM {$wpdb->postmeta} WHERE meta_key = 'total' AND post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = 'on_the_lot' AND post_status = 'publish')");
        $max_p = $max_p_query ? ceil($max_p_query) : 10000;
        set_transient('ibaw_shed_max_price', $max_p, 12 * HOUR_IN_SECONDS);
    }
    
    // 3. Get Wall Colors (Cached for 12 hours)
    $wall_c = get_transient('ibaw_shed_wall_colors');
    if ( false === $wall_c ) {
        $wall_c = $wpdb->get_col("SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key = 'wall_color' AND meta_value != '' ORDER BY meta_value ASC");
        if ( ! is_array($wall_c) ) {
            $wall_c = [];
        }
        set_transient('ibaw_shed_wall_colors', $wall_c, 12 * HOUR_IN_SECONDS);
    }

    ob_start(); ?>
    
    <style>
        .ibaw-filter-container {
            max-width: 350px;
            font-family: sans-serif;
            color: #333;
        }
        .ibaw-accordion-item {
            margin-bottom: 12px;
            border: 1px solid #888;
            border-radius: 8px;
            background: #fff;
            overflow: hidden;
        }
        .ibaw-accordion-header {
            padding: 15px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 500;
            font-size: 15px;
            user-select: none;
            transition: background 0.2s;
        }
        .ibaw-accordion-header:hover {
            background: #fafafa;
        }
        .ibaw-accordion-content {
            display: none;
            padding: 15px;
            border-top: 1px solid #eee;
            background: #fff;
        }
        .ibaw-chevron {
            transition: transform 0.3s ease;
            display: flex;
            align-items: center;
        }
        .ibaw-accordion-header.active .ibaw-chevron {
            transform: rotate(180deg);
        }
        .ibaw-input-element {
            width: 100%;
            padding: 12px;
            border-radius: 5px;
            border: 1px solid #ddd;
            margin-bottom: 10px;
        }
        .ibaw-filter-btn {
            width: 100%;
            padding: 10px;
            border-radius: 30px;
            border: 1px solid #ddd;
            background: #fff;
            color: #333;
            cursor: pointer;
            font-weight: 600;
            margin-bottom: 8px;
            transition: all 0.2s;
        }
        .ibaw-filter-btn.active {
            background: #81B716;
            color: #fff;
            border-color: #81B716;
        }
        #clear-shed-filters {
            width: 100%;
            padding: 12px;
            background: #f8f8f8;
            color: #555;
            border: 1px solid #bbb;
            border-radius: 5px;
            font-weight: 700;
            cursor: pointer;
            text-transform: uppercase;
            font-size: 12px;
            margin-top: 10px;
        }
    </style>

    <div class="ibaw-filter-container">
        
        <div class="ibaw-accordion-item">
            <div class="ibaw-accordion-header active">
                Sort Inventory
                <span class="ibaw-chevron">
                    <svg width="12" height="8" viewBox="0 0 12 8" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 1.5L6 6.5L11 1.5" stroke="#666" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
            </div>
            <div class="ibaw-accordion-content" style="display:block;">
                <select id="shed-sort-dropdown" class="ibaw-input-element">
                    <option value="numeric">A to Z</option>
                    <option value="date-desc">Newest First</option>
                    <option value="price-asc">Price: Low to High</option>
                    <option value="price-desc">Price: High to Low</option>
                </select>
                <button id="clear-shed-filters">Reset All Filters</button>
            </div>
        </div>

        <div class="ibaw-accordion-item">
            <div class="ibaw-accordion-header">
                Price Limit
                <span class="ibaw-chevron">
                    <svg width="12" height="8" viewBox="0 0 12 8" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 1.5L6 6.5L11 1.5" stroke="#666" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
            </div>
            <div class="ibaw-accordion-content">
                <label style="display:block; font-weight:bold; margin-bottom:15px; font-size:14px;">
                    Up to: <span id="price-limit-display" style="color:#81B716;">$<?php echo esc_html(number_format($max_p)); ?></span>
                </label>
                <input type="range" id="price-range" min="0" max="<?php echo esc_attr($max_p); ?>" value="<?php echo esc_attr($max_p); ?>" step="100" style="width:100%; cursor:pointer; accent-color:#81B716;">
            </div>
        </div>

        <div class="ibaw-accordion-item">
            <div class="ibaw-accordion-header">
                Wall Color
                <span class="ibaw-chevron">
                    <svg width="12" height="8" viewBox="0 0 12 8" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 1.5L6 6.5L11 1.5" stroke="#666" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
            </div>
            <div class="ibaw-accordion-content">
                <select class="color-filter ibaw-input-element" data-meta="wall">
                    <option value="all">Any Color</option>
                    <?php 
                    if ( ! empty($wall_c) ) {
                        foreach($wall_c as $c) {
                            echo '<option value="' . esc_attr($c) . '">' . esc_html($c) . '</option>'; 
                        }
                    }
                    ?>
                </select>
            </div>
        </div>

        <div class="ibaw-accordion-item">
            <div class="ibaw-accordion-header">
                Building Style
                <span class="ibaw-chevron">
                    <svg width="12" height="8" viewBox="0 0 12 8" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 1.5L6 6.5L11 1.5" stroke="#666" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
            </div>
            <div class="ibaw-accordion-content">
                <button class="ibaw-filter-btn active" data-filter="all">All Styles</button>
                <?php 
                if ( ! is_wp_error($terms) && ! empty($terms) ) {
                    foreach($terms as $t): ?>
                        <button class="ibaw-filter-btn" data-filter="cat-<?php echo esc_attr($t->slug); ?>"><?php echo esc_html($t->name); ?></button>
                    <?php endforeach; 
                }
                ?>
            </div>
        </div>

    </div>

    <script>
    jQuery(document).ready(function($){
        var defMax = '<?php echo esc_js($max_p); ?>';
        
        // Accordion Toggle Logic
        $('body').on('click', '.ibaw-accordion-header', function() {
            var $header = $(this);
            var $content = $header.next('.ibaw-accordion-content');
            
            $header.toggleClass('active');
            $content.slideToggle(250);
        });

        // Filtering & Sorting Logic
        function applyFilters() {
            var cat = $('.ibaw-filter-btn.active').data('filter'), 
                pr = parseFloat($('#price-range').val()),
                w = $('.color-filter[data-meta="wall"]').val(),
                sortBy = $('#shed-sort-dropdown').val();

            $('#price-limit-display').text('$' + pr.toLocaleString());
            
            // 1. FILTERING
            $('.shed-card').each(function(){
                var s = $(this), 
                    mC = (cat === 'all' || s.hasClass(cat)), 
                    mP = (parseFloat(s.data('price')) <= pr),
                    mW = (w === 'all' || s.data('wall') === w);
                
                (mC && mP && mW) ? s.show() : s.hide();
            });

            // 2. NATURAL SORTING
            var grid = $('.shed-inventory-grid');
            var cards = grid.children('.shed-card').get();

            cards.sort(function(a, b) {
                switch(sortBy) {
                    case 'price-asc':
                        return parseFloat($(a).data('price')) - parseFloat($(b).data('price'));
                    case 'price-desc':
                        return parseFloat($(b).data('price')) - parseFloat($(a).data('price'));
                    case 'date-desc':
                        return parseInt($(b).data('date')) - parseInt($(a).data('date'));
                    case 'numeric':
                        var titleA = $(a).find('h3').text();
                        var titleB = $(b).find('h3').text();
                        return titleA.localeCompare(titleB, undefined, {numeric: true, sensitivity: 'base'});
                    default:
                        return 0;
                }
            });

            $.each(cards, function(i, card) {
                grid.append(card);
            });
        }

        // Event Listeners for Filters
        $('body').on('click', '.ibaw-filter-btn', function(e){ 
            e.preventDefault();
            $('.ibaw-filter-btn').removeClass('active'); 
            $(this).addClass('active'); 
            applyFilters(); 
        });

        $('body').on('input change', '#price-range, .color-filter, #shed-sort-dropdown', applyFilters);

        // Reset Filters
        $('body').on('click', '#clear-shed-filters', function(e){ 
            e.preventDefault();
            $('.ibaw-filter-btn').removeClass('active'); 
            $('.ibaw-filter-btn[data-filter="all"]').addClass('active'); 
            $('#price-range').val(defMax); 
            $('.color-filter').val('all'); 
            $('#shed-sort-dropdown').val('numeric');
            applyFilters(); 
        });
    });
    </script>
    <?php return ob_get_clean();
});