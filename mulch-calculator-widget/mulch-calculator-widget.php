<?php
/**
 * Plugin Name: IBAW-Mulch & Stone Calculator Widget
 * Description: A mulch & stone calculator widget built for Cornerstone Landscape Supply LLC. Uses shortcode [ibaw_calculator] for Elementor placement, controlled by a dashboard settings panel.
 * Version: 1.1.3
 * Author: Erick Villeta
 * Plugin URI: https://ericksonvilleta.com
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * 1. Add the Options Page to the Settings Menu
 */
add_action('admin_menu', 'ibaw_calculator_add_settings_page');
function ibaw_calculator_add_settings_page() {
    add_options_page(
        'Calculator Widget Settings', 
        'Calculator Widget', 
        'manage_options', 
        'ibaw-calculator-settings', 
        'ibaw_calculator_render_settings_page'
    );
}

/**
 * 2. Register the Setting in the Database
 */
add_action('admin_init', 'ibaw_calculator_register_settings');
function ibaw_calculator_register_settings() {
    register_setting('ibaw_calculator_options_group', 'ibaw_calculator_selected_products');
}

/**
 * 3. Render the Admin Settings Page & Checkbox UI
 */
function ibaw_calculator_render_settings_page() {
    $selected_products = get_option('ibaw_calculator_selected_products');
    if ( ! is_array( $selected_products ) ) {
        $selected_products = [];
    }

    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'title',
        'order'          => 'ASC',
        'fields'         => 'ids'
    );
    $product_ids = get_posts($args);

    ?>
    <div class="wrap">
        <h2>Calculator Widget Settings</h2>
        <form method="post" action="options.php">
            <?php settings_fields('ibaw_calculator_options_group'); ?>
            
            <p style="margin-bottom: 15px; font-size: 14px;">Select the products where the Mulch & Stone Calculator should be displayed:</p>
            
            <div style="max-height: 250px; max-width: 500px; overflow-y: scroll; border: 1px solid #ccc; padding: 10px; background: #fff; border-radius: 4px;">
                <?php if ( empty( $product_ids ) ): ?>
                    <p>No products found. Please ensure WooCommerce is active and products are published.</p>
                <?php else: ?>
                    <ul style="margin: 0; padding: 0; list-style: none;">
                        <?php foreach ( $product_ids as $product_id ) : 
                            $product_title = get_the_title( $product_id );
                            $checked = in_array( $product_id, $selected_products ) ? 'checked="checked"' : '';
                        ?>
                        <li style="margin-bottom: 6px;">
                            <label style="font-size: 13px;">
                                <input type="checkbox" name="ibaw_calculator_selected_products[]" value="<?php echo esc_attr( $product_id ); ?>" <?php echo $checked; ?>>
                                <?php echo esc_html( $product_title ); ?>
                            </label>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            
            <?php submit_button('Save Product Placements'); ?>
        </form>
    </div>
    <?php
}

/**
 * 4. The Shortcode Generator [ibaw_calculator]
 */
add_shortcode( 'ibaw_calculator', 'ibaw_render_calculator_shortcode' );
function ibaw_render_calculator_shortcode() {
    global $product;

    // Safely get the actual product ID, even inside an Elementor Template
    $current_id = 0;
    if ( is_a( $product, 'WC_Product' ) ) {
        $current_id = $product->get_id();
    } elseif ( function_exists('is_product') && is_product() ) {
        $current_id = get_the_ID();
    }

    if ( ! $current_id ) {
        return ''; // Return nothing if we can't find a product ID
    }

    $selected_products = get_option('ibaw_calculator_selected_products');
    if ( ! is_array( $selected_products ) ) {
        $selected_products = [];
    }

    // Gatekeeper: If the product is NOT checked in the settings, return an empty string (hide it)
    if ( empty( $selected_products ) || ( ! in_array( (string)$current_id, $selected_products, true ) && ! in_array( (int)$current_id, $selected_products, true ) ) ) {
        return ''; 
    }

    // If it passes the check, generate the HTML
    ob_start();
    ?>
    <div class="mulch-calculator" style="max-width: 500px; font-family: Arial, sans-serif; padding: 20px; border: 1px solid #e0e0e0; border-radius: 5px; background-color: #fcfcfc;">
        
        <h3 style="margin-top: 0; color: #333;">Determine the Amount You Need:</h3>
        
        <div style="margin-bottom: 15px;">
            <label for="sqftArea" style="display: block; font-weight: bold; margin-bottom: 5px; font-size: 14px; color: #555;">
                Enter the total square footage of the area:*
                <span style="font-weight: normal; font-size: 12px; color: #8dc63f; cursor: pointer;" title="Length × Width = Square Footage">
                    &#9432;
                </span>
            </label>
            <input type="text" inputmode="decimal" pattern="[0-9]*" id="sqftArea" placeholder="e.g., 150" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 16px;">
        </div>

        <div style="margin-bottom: 20px;">
            <label for="mulchDepth" style="display: block; font-weight: bold; margin-bottom: 5px; font-size: 14px; color: #555;">
                Select your desired material depth:*
            </label>
            <select id="mulchDepth" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 16px; background-color: #fff;">
                <option value="">Choose an option</option>
                <option value="1">1 inches</option>
                <option value="2">2 inches</option>
                <option value="3">3 inches</option>
            </select>
        </div>

        <button type="button" onclick="calculateMulch()" style="background-color: #8dc63f; color: white; padding: 12px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold; width: 100%;">
            Calculate
        </button>

        <div id="calcResults" style="display: none; margin-top: 20px; padding: 15px; background-color: #f8fbf6; border-left: 5px solid #8dc63f; border-radius: 4px;">
            <p style="margin: 0 0 10px 0; font-weight: bold; color: #333;">Estimated Amount Needed:</p>
            <p style="margin: 5px 0; color: #333;"><strong id="primaryLabel">Cubic Yards:</strong> <span id="outPrimary"></span> <span id="primaryUnit">cu. yd.</span></p>
        </div>

    </div>

    <script>
        (function updateMulchLabel() {
            let isMulch = false;
            const urlPath = window.location.pathname.toLowerCase();
            if (urlPath.includes('mulch')) {
                isMulch = true;
            } else {
                const productTitle = document.querySelector('h1');
                if (productTitle && productTitle.innerText.toLowerCase().includes('mulch')) {
                    isMulch = true;
                }
            }

            if (isMulch) {
                const selectElement = document.getElementById('mulchDepth');
                if(selectElement) {
                    for(let i = 0; i < selectElement.options.length; i++) {
                        if(selectElement.options[i].value === '2') {
                            selectElement.options[i].text = '2 inches (Recommended for mulch)';
                        }
                    }
                }
            }
        })();

        function calculateMulch() {
            const sqftInput = document.getElementById('sqftArea').value;
            const depth = parseFloat(document.getElementById('mulchDepth').value);
            const resultsBox = document.getElementById('calcResults');
            const sqft = parseFloat(sqftInput.replace(/[^0-9.]/g, ''));

            if (isNaN(sqft) || sqft <= 0 || isNaN(depth)) {
                alert('Please enter valid area and select a depth.');
                return;
            }

            let isStone = false;
            const formElement = document.querySelector('form.cart');
            if (formElement) {
                const formText = formElement.innerText.toLowerCase();
                if (formText.includes(' ton') || formText.includes('tons')) {
                    isStone = true;
                }
            }

            if (!isStone) {
                const urlPath = window.location.pathname.toLowerCase();
                if (urlPath.match(/\b(stone|gravel|sand|river-jack|dust|slate|cobble)\b/)) {
                    isStone = true;
                }
            }
            
            if (!isStone) {
                const productTitle = document.querySelector('h1');
                if (productTitle) {
                    const titleText = productTitle.innerText.toLowerCase();
                    if (titleText.match(/\b(stone|gravel|sand|river jack|dust|slate|cobble)\b/)) {
                        isStone = true;
                    }
                }
            }

            const cubicYards = (sqft * depth) / 324;
            
            if (isStone) {
                const tons = cubicYards * 1.4;
                document.getElementById('primaryLabel').innerText = 'Tons:';
                document.getElementById('outPrimary').innerText = tons.toFixed(2);
                document.getElementById('primaryUnit').innerText = 'tn';
            } else {
                document.getElementById('primaryLabel').innerText = 'Cubic Yards:';
                document.getElementById('outPrimary').innerText = cubicYards.toFixed(2);
                document.getElementById('primaryUnit').innerText = 'cu. yd.';
            }
            
            resultsBox.style.display = 'block';
        }
    </script>
    <?php
    return ob_get_clean();
}