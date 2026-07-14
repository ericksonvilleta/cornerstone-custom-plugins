<?php
/**
 * Plugin Name: Erick's Custom Menu SVG Icons
 * Description: Adds a custom SVG URL field to Menu Items and swaps Phlox icons with styled SVGs.
 * Version: 1.0
 * Author: Erick Villeta
 * Plugin URI: https://ericksonvilleta.com
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * 1. Add "Custom SVG URL" field to Menu Items in Admin
 */
add_action('wp_nav_menu_item_custom_fields', function($item_id, $item, $depth, $args) {
    $svg_url = get_post_meta($item_id, '_erick_menu_svg_url', true);
    ?>
    <div class="field-custom-svg description-wide" style="margin: 10px 0; border-top: 1px solid #eee; pt: 10px;">
        <label for="edit-menu-item-svg-<?php echo $item_id; ?>">
            <strong><?php _e( 'Custom SVG Icon URL', 'erick-svg' ); ?></strong><br />
            <input type="text" 
                   id="edit-menu-item-svg-<?php echo $item_id; ?>" 
                   class="widefat code edit-menu-item-svg" 
                   name="menu-item-svg[<?php echo $item_id; ?>]" 
                   value="<?php echo esc_attr( $svg_url ); ?>" 
                   placeholder="https://.../icon.svg" />
            <span class="description" style="font-size: 11px;">Paste the SVG link from your Media Library.</span>
        </label>
    </div>
    <?php
}, 10, 4);

/**
 * 2. Save the Custom SVG URL
 */
add_action('wp_update_nav_menu_item', function($menu_id, $menu_item_db_id) {
    if (isset($_POST['menu-item-svg'][$menu_item_db_id])) {
        $clean_url = esc_url_raw($_POST['menu-item-svg'][$menu_item_db_id]);
        update_post_meta($menu_item_db_id, '_erick_menu_svg_url', $clean_url);
    }
}, 10, 2);

/**
 * 3. Inject the SVG Mask into the Walker
 */
add_filter('walker_nav_menu_start_el', function($item_output, $item, $depth, $args) {
    $svg_url = get_post_meta($item->ID, '_erick_menu_svg_url', true);

    if ($svg_url) {
        // We use a span with a background mask for perfect color control
        $custom_icon_html = sprintf(
            '<span class="custom-menu-svg-wrapper" style="--svg-icon: url(\'%s\');"></span>',
            esc_url($svg_url)
        );

        // Pattern to find Phlox's default icon tag to replace it
        $pattern = '/<i[^>]*class="[^"]*aux-menu-icon[^"]*"[^>]*><\/i>/i';
        
        if (preg_match($pattern, $item_output)) {
            $item_output = preg_replace($pattern, $custom_icon_html, $item_output);
        } else {
            // If no Phlox icon exists, prepend to the link text
            $item_output = preg_replace('/(<a[^>]*>)/', '$1' . $custom_icon_html, $item_output, 1);
        }
    }
    return $item_output;
}, 20, 4);

/**
 * 4. Add the CSS for Styling and Hover
 */
add_action('wp_head', function() {
    ?>
    <style>
        .custom-menu-svg-wrapper {
            display: inline-block;
            width: 24px;  /* Adjust size as needed */
            height: 24px;
            margin-right: 8px;
            vertical-align: middle;
            background-color: #292929; /* Initial Color: White */
            
            /* CSS Mask Logic */
            -webkit-mask-image: var(--svg-icon);
            mask-image: var(--svg-icon);
            -webkit-mask-repeat: no-repeat;
            mask-repeat: no-repeat;
            -webkit-mask-size: contain;
            mask-size: contain;
            
            transition: background-color 0.3s ease, transform 0.3s ease;
        }

        /* Hover State: Target the link hover to trigger the icon color change */
        .aux-menu-item:hover .custom-menu-svg-wrapper,
        .custom-menu-svg-wrapper:hover {
            background-color: #81ab3f !important; /* Your Green Color */
            transform: scale(1.1);
        }
    </style>
    <?php
});