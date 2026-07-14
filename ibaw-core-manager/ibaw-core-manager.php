<?php
/**
 * Plugin Name: IBAW-Core-Site-Manager
 * Plugin URI:  https://ericksonvilleta.com
 * Description: Master plugin for Cornerstone Landscape: Custom Order Statuses, Email Notifications, Tracking, and UI Fixes.
 * Version:     1.0
 * Author:      Erick Villeta
 * License:     GPL2
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ==========================================================================
   1. REGISTER CUSTOM ORDER STATUSES
   ========================================================================== */

add_action('init', 'ibaw_register_custom_order_statuses');
function ibaw_register_custom_order_statuses() {
    register_post_status('wc-awaiting-dispatch', array(
        'label'                     => 'Awaiting Dispatch',
        'public'                    => true,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Awaiting Dispatch <span class="count">(%s)</span>', 'Awaiting Dispatch <span class="count">(%s)</span>')
    ));

    register_post_status('wc-out-for-delivery', array(
        'label'                     => 'Out for Delivery',
        'public'                    => true,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Out for Delivery <span class="count">(%s)</span>', 'Out for Delivery <span class="count">(%s)</span>')
    ));
}

add_filter('wc_order_statuses', 'ibaw_add_custom_statuses_to_list');
function ibaw_add_custom_statuses_to_list($order_statuses) {
    $order_statuses['wc-awaiting-dispatch'] = 'Awaiting Dispatch';
    $order_statuses['wc-out-for-delivery']  = 'Out for Delivery';
    return $order_statuses;
}

/* ==========================================================================
   2. TRACKING URL META BOX
   ========================================================================== */

add_action( 'add_meta_boxes', 'ibaw_add_tracking_meta_box' );
function ibaw_add_tracking_meta_box() {
    add_meta_box( 'ibaw_tracking_link', 'Delivery Tracking', 'ibaw_tracking_meta_box_html', 'shop_order', 'side', 'high' );
}

function ibaw_tracking_meta_box_html( $post ) {
    $value = get_post_meta( $post->ID, '_ibaw_tracking_url', true );
    echo '<label for="ibaw_tracking_url">Tracking URL:</label>';
    echo '<input type="url" name="ibaw_tracking_url" id="ibaw_tracking_url" value="'.esc_attr($value).'" style="width:100%;" placeholder="https://tracking-link.com" />';
}

add_action( 'save_post_shop_order', 'ibaw_save_tracking_url_meta' );
function ibaw_save_tracking_url_meta( $post_id ) {
    if ( isset( $_POST['ibaw_tracking_url'] ) ) {
        update_post_meta( $post_id, '_ibaw_tracking_url', esc_url_raw( $_POST['ibaw_tracking_url'] ) );
    }
}

/* ==========================================================================
   3. SETTINGS PAGE (WooCommerce > Order Email Settings)
   ========================================================================== */

add_action( 'admin_menu', 'ibaw_settings_menu' );
function ibaw_settings_menu() {
    add_submenu_page('woocommerce', 'Order Email Settings', 'Order Email Settings', 'manage_options', 'ibaw-settings', 'ibaw_settings_page_html');
}

add_action( 'admin_init', 'ibaw_register_settings' );
function ibaw_register_settings() {
    register_setting( 'ibaw-settings-group', 'ibaw_bcc_email' );
    register_setting( 'ibaw-settings-group', 'ibaw_awaiting_dispatch_msg' );
    register_setting( 'ibaw-settings-group', 'ibaw_out_for_delivery_msg' );
}

function ibaw_settings_page_html() {
    ?>
    <div class="wrap">
        <h1>IBAW Core Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields( 'ibaw-settings-group' ); ?>
            <table class="form-table">
                <tr valign="top"><th scope="row">BCC Email</th><td><input type="email" name="ibaw_bcc_email" value="<?php echo esc_attr(get_option('ibaw_bcc_email')); ?>" class="regular-text" /></td></tr>
                <tr valign="top"><th scope="row">Awaiting Dispatch Msg</th><td><textarea name="ibaw_awaiting_dispatch_msg" rows="4" class="large-text"><?php echo esc_textarea(get_option('ibaw_awaiting_dispatch_msg')); ?></textarea></td></tr>
                <tr valign="top"><th scope="row">Out for Delivery Msg</th><td><textarea name="ibaw_out_for_delivery_msg" rows="4" class="large-text"><?php echo esc_textarea(get_option('ibaw_out_for_delivery_msg')); ?></textarea></td></tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

/* ==========================================================================
   4. EMAIL NOTIFICATIONS
   ========================================================================== */

add_action( 'woocommerce_order_status_changed', 'ibaw_send_custom_emails', 10, 4 );
function ibaw_send_custom_emails( $order_id, $old_status, $new_status, $order ) {
    $target_statuses = array( 'awaiting-dispatch', 'out-for-delivery' );
    if ( in_array( $new_status, $target_statuses ) ) {
        $mailer = WC()->mailer();
        $recipient = $order->get_billing_email();
        $bcc = get_option('ibaw_bcc_email', get_option('admin_email'));
        $tracking = get_post_meta($order_id, '_ibaw_tracking_url', true);

        if ( $new_status === 'awaiting-dispatch' ) {
            $subject = 'Order #' . $order->get_order_number() . ': Awaiting Dispatch';
            $body = get_option('ibaw_awaiting_dispatch_msg', 'Your order is ready for dispatch.');
        } else {
            $subject = 'Order #' . $order->get_order_number() . ' is Out for Delivery!';
            $body = get_option('ibaw_out_for_delivery_msg', 'Your order is on the way!');
        }

        $content = "<h2>Status Update</h2><p>Hi " . esc_html($order->get_billing_first_name()) . ",</p><p>$body</p>";
        if ( $new_status === 'out-for-delivery' && !empty($tracking) ) {
            $content .= '<p><a href="'.esc_url($tracking).'" style="background:#2196f3; color:#fff; padding:10px 20px; text-decoration:none; border-radius:4px;">Track Delivery</a></p>';
        }

        $headers = array('Content-Type: text/html; charset=UTF-8', 'Bcc: ' . $bcc);
        $mailer->send( $recipient, $subject, $mailer->wrap_message($subject, $content), $headers );
    }
}

/* ==========================================================================
   5. UI FIXES (Scrollbar & Admin Colors)
   ========================================================================== */

add_action('wp_head', 'ibaw_frontend_fixes');
function ibaw_frontend_fixes() {
    echo '<style>html, body { overflow-x: hidden !important; } .site-content, #page { overflow: visible !important; }</style>';
}

add_action('admin_head', 'ibaw_admin_fixes');
function ibaw_admin_fixes() {
    echo '<style>mark.wc-awaiting-dispatch::after { content: "\e012"; color: #ff9800; } mark.wc-out-for-delivery::after { content: "\e019"; color: #2196f3; }</style>';
}