<?php
/**
 * Plugin Name: Menu Item Images (Pro)
 * Description: Adds a Media Uploader to menu items in Appearance > Menus.
 * Version: 1.0
 * Author: Erick Villeta
 * Plugin URI: https://ericksonvilleta.com
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// 1. Load Scripts only on the Menus page
add_action( 'admin_enqueue_scripts', function( $hook ) {
    if ( 'nav-menus.php' !== $hook ) return;
    wp_enqueue_media();
} );

// 2. Add Field to Menu Admin
add_action( 'wp_nav_menu_item_custom_fields', 'mii_stable_field', 10, 4 );
function mii_stable_field( $item_id, $item, $depth, $args ) {
    $image_url = get_post_meta( $item_id, '_menu_item_image_url', true );
    ?>
    <p class="field-image-url description-wide" style="margin: 10px 0;">
        <label for="edit-menu-item-image-url-<?php echo $item_id; ?>">
            <strong>Menu Icon Image</strong><br />
            <div class="mii-uploader-container">
                <input type="text" id="edit-menu-item-image-url-<?php echo $item_id; ?>" 
                       class="widefat mii-url-field" name="menu-item-image-url[<?php echo $item_id; ?>]" 
                       value="<?php echo esc_attr( $image_url ); ?>" />
                <div style="margin-top:5px;">
                    <button type="button" class="button mii-upload-button">Select Image</button>
                    <button type="button" class="button mii-clear-button" style="color:#d63638;">Remove</button>
                </div>
            </div>
        </label>
    </p>

    <script>
    (function($){
        // Using unbind/bind to prevent multiple event triggers
        $(document).off('click', '.mii-upload-button').on('click', '.mii-upload-button', function(e) {
            e.preventDefault();
            var $button = $(this);
            var $input = $button.closest('.mii-uploader-container').find('.mii-url-field');
            
            var frame = wp.media({
                title: 'Select Image',
                button: { text: 'Use this image' },
                multiple: false
            });

            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                $input.val(attachment.url).trigger('change');
            });

            frame.open();
        });

        $(document).off('click', '.mii-clear-button').on('click', '.mii-clear-button', function(e) {
            e.preventDefault();
            $(this).closest('.mii-uploader-container').find('.mii-url-field').val('').trigger('change');
        });
    })(jQuery);
    </script>
    <?php
}

// 3. Save Field
add_action( 'wp_update_nav_menu_item', 'mii_stable_save', 10, 2 );
function mii_stable_save( $menu_id, $menu_item_db_id ) {
    if ( isset( $_POST['menu-item-image-url'][$menu_item_db_id] ) ) {
        update_post_meta( $menu_item_db_id, '_menu_item_image_url', esc_url_raw($_POST['menu-item-image-url'][$menu_item_db_id]) );
    }
}

// 4. Front End Filter
add_filter( 'walker_nav_menu_start_el', 'mii_stable_display', 10, 4 );
function mii_stable_display( $item_output, $item, $depth, $args ) {
    $image_url = get_post_meta( $item->ID, '_menu_item_image_url', true );
    if ( $image_url ) {
        $img = '<img src="'.esc_url($image_url).'" class="menu-icon-img" style="width:auto; height:auto; vertical-align:middle; margin-right:8px; display:inline-block;">';
        $item_output = preg_replace('/(<a.*?>)/i', '$1' . $img, $item_output);
    }
    return $item_output;
}