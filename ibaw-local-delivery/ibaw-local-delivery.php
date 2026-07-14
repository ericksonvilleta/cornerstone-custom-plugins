<?php
/**
 * Plugin Name: IBAW- Local Delivery Drivers
 * Plugin URI: https://ericksonvilleta.com
 * Description: A premium WooCommerce local delivery management system featuring driver assignment, frontend dashboards, signature capture, photo upload, live GPS tracking, automated "Out for delivery" status, and custom login redirection for Cornerstone Landscape Supply.
 * Version: 1.5.1
 * Author: Erick Villeta
 * Text Domain: ibaw-local-delivery
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Declare HPOS Compatibility
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );

class IBAW_Local_Delivery {

    public function __construct() {
        // Register Activation Hook for creating the role
        register_activation_hook( __FILE__, array( $this, 'activate_plugin' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate_plugin' ) );

        // Initialize admin and frontend features
        add_action( 'admin_init', array( $this, 'check_woocommerce' ) );
        add_action( 'admin_init', array( $this, 'restrict_driver_admin_access' ) );
        
        // Register Custom Order Status
        add_action( 'init', array( $this, 'register_out_for_delivery_status' ) );
        add_filter( 'wc_order_statuses', array( $this, 'add_out_for_delivery_to_order_statuses' ) );
        
        // Custom Login Redirect
        add_filter( 'login_redirect', array( $this, 'redirect_driver_after_login' ), 10, 3 );
        
        // Add Meta Boxes
        add_action( 'add_meta_boxes', array( $this, 'add_admin_meta_boxes' ), 10, 2 );
        
        // Save Meta Box (Legacy & HPOS compatible hook)
        add_action( 'woocommerce_process_shop_order_meta', array( $this, 'save_driver_assignment' ) );

        // Add Driver column to Orders list (Legacy)
        add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_driver_column' ), 20 );
        add_action( 'manage_shop_order_posts_custom_column', array( $this, 'populate_driver_column_legacy' ), 20, 2 );

        // Add Driver column to Orders list (HPOS)
        add_filter( 'manage_woocommerce_page_wc-orders_columns', array( $this, 'add_driver_column' ), 20 );
        add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( $this, 'populate_driver_column_hpos' ), 20, 2 );

        // Frontend Dashboard Shortcode
        add_shortcode( 'ibaw_driver_dashboard', array( $this, 'render_driver_dashboard' ) );

        // Handle Status Updates via AJAX/Postback
        add_action( 'init', array( $this, 'handle_status_update' ) );

        // AJAX endpoints for location tracking
        add_action( 'wp_ajax_ibaw_update_location', array( $this, 'ajax_update_location' ) );
        add_action( 'wp_ajax_ibaw_get_driver_location', array( $this, 'ajax_get_driver_location' ) );
    }

    /**
     * Create the Delivery Driver role upon activation.
     */
    public function activate_plugin() {
        add_role(
            'delivery_driver',
            __( 'Delivery Driver', 'ibaw-local-delivery' ),
            array(
                'read' => true,
            )
        );
    }

    /**
     * Clean up role upon deactivation if needed.
     */
    public function deactivate_plugin() {
        // Keeping role for data integrity even if deactivated temporarily.
    }

    /**
     * Ensure WooCommerce is active.
     */
    public function check_woocommerce() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            add_action( 'admin_notices', function() {
                echo '<div class="error"><p><strong>IBAW- Local Delivery Drivers</strong> requires WooCommerce to be installed and active.</p></div>';
            });
        }
    }

    /**
     * Register "Out for delivery" Custom Status.
     */
    public function register_out_for_delivery_status() {
        register_post_status( 'wc-out-for-delivery', array(
            'label'                     => __( 'Out for delivery', 'ibaw-local-delivery' ),
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            /* translators: %s: count */
            'label_count'               => _n_noop( 'Out for delivery <span class="count">(%s)</span>', 'Out for delivery <span class="count">(%s)</span>', 'ibaw-local-delivery' )
        ) );
    }

    /**
     * Add "Out for delivery" to WooCommerce Order Statuses array.
     */
    public function add_out_for_delivery_to_order_statuses( $order_statuses ) {
        $new_order_statuses = array();
        foreach ( $order_statuses as $key => $status ) {
            $new_order_statuses[ $key ] = $status;
            if ( 'wc-processing' === $key ) {
                $new_order_statuses['wc-out-for-delivery'] = __( 'Out for delivery', 'ibaw-local-delivery' );
            }
        }
        return $new_order_statuses;
    }

    /**
     * Redirect Delivery Drivers to their specific dashboard upon login.
     */
    public function redirect_driver_after_login( $redirect_to, $request, $user ) {
        if ( isset( $user->roles ) && is_array( $user->roles ) ) {
            if ( in_array( 'delivery_driver', $user->roles ) ) {
                return 'https://cornerstonelandscapesupply.com/delivery-dashboard/';
            }
        }
        return $redirect_to;
    }

    /**
     * Restrict delivery drivers from accessing the WordPress admin area.
     */
    public function restrict_driver_admin_access() {
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            return;
        }

        $current_user = wp_get_current_user();
        if ( in_array( 'delivery_driver', (array) $current_user->roles ) ) {
            wp_redirect( home_url() );
            exit;
        }
    }

    /**
     * Add Meta Boxes to WooCommerce Order Screen (HPOS and Legacy).
     */
    public function add_admin_meta_boxes() {
        $screen = 'shop_order';
        
        if ( class_exists( '\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController' ) && function_exists( 'wc_get_container' ) ) {
            $hpos_enabled = wc_get_container()->get( \Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled();
            if ( $hpos_enabled && function_exists( 'wc_get_page_screen_id' ) ) {
                $screen = wc_get_page_screen_id( 'shop-order' );
            }
        }

        add_meta_box(
            'ibaw_driver_assignment',
            __( 'Assign Delivery Driver', 'ibaw-local-delivery' ),
            array( $this, 'render_driver_meta_box' ),
            $screen,
            'side',
            'core'
        );

        add_meta_box(
            'ibaw_driver_map',
            __( 'Driver Real-Time Location', 'ibaw-local-delivery' ),
            array( $this, 'render_map_meta_box' ),
            $screen,
            'normal',
            'high'
        );

        add_meta_box(
            'ibaw_proof_of_delivery',
            __( 'Proof of Delivery', 'ibaw-local-delivery' ),
            array( $this, 'render_pod_meta_box' ),
            $screen,
            'normal',
            'high'
        );
    }

    /**
     * Render the Driver Assignment Meta Box HTML.
     */
    public function render_driver_meta_box( $post_or_order_object ) {
        $order = ( $post_or_order_object instanceof WP_Post ) ? wc_get_order( $post_or_order_object->ID ) : $post_or_order_object;
        if ( ! $order ) return;

        $assigned_driver = $order->get_meta( '_ibaw_assigned_driver', true );
        
        $drivers = get_users( array( 'role' => 'delivery_driver' ) );

        wp_nonce_field( 'ibaw_save_driver', 'ibaw_driver_nonce' );

        echo '<select name="ibaw_driver_id" style="width: 100%;">';
        echo '<option value="">' . __( '-- Unassigned --', 'ibaw-local-delivery' ) . '</option>';
        foreach ( $drivers as $driver ) {
            $selected = selected( $assigned_driver, $driver->ID, false );
            echo sprintf( '<option value="%s" %s>%s</option>', esc_attr( $driver->ID ), $selected, esc_html( $driver->display_name ) );
        }
        echo '</select>';
        echo '<p class="description">' . __( 'Select a driver to automatically update status to "Out for delivery".', 'ibaw-local-delivery' ) . '</p>';
    }

    /**
     * Render the Live Tracking Map Meta Box HTML.
     */
    public function render_map_meta_box( $post_or_order_object ) {
        $order = ( $post_or_order_object instanceof WP_Post ) ? wc_get_order( $post_or_order_object->ID ) : $post_or_order_object;
        if ( ! $order ) return;

        $driver_id = $order->get_meta( '_ibaw_assigned_driver', true );
        if ( ! $driver_id ) {
            echo '<p>' . __( 'Assign a driver to view their real-time location.', 'ibaw-local-delivery' ) . '</p>';
            return;
        }

        $driver = get_userdata( $driver_id );
        echo '<p><strong>' . esc_html( $driver->display_name ) . '</strong> location status:</p>';
        echo '<div id="ibaw-driver-map" style="height: 350px; background: #e2e8f0; border: 1px solid #ccc; border-radius: 4px;"></div>';
        echo '<p id="ibaw-map-status" style="font-style: italic; color: #666; margin-top: 10px;">Waiting for location data...</p>';

        wp_nonce_field( 'ibaw_get_location_nonce', 'ibaw_get_location_nonce_field' );

        echo '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />';
        echo '<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>';
        ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                var map = L.map('ibaw-driver-map').setView([0, 0], 2);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '© OpenStreetMap contributors'
                }).addTo(map);

                var marker;
                var driverId = <?php echo intval( $driver_id ); ?>;
                var nonce = document.getElementById('ibaw_get_location_nonce_field').value;

                function updateAdminMap() {
                    var formData = new FormData();
                    formData.append('action', 'ibaw_get_driver_location');
                    formData.append('driver_id', driverId);
                    formData.append('security', nonce);

                    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if(data.success) {
                            var lat = parseFloat(data.data.lat);
                            var lng = parseFloat(data.data.lng);
                            var time = data.data.time;

                            if(!marker) {
                                marker = L.marker([lat, lng]).addTo(map);
                                map.setView([lat, lng], 15);
                            } else {
                                marker.setLatLng([lat, lng]);
                            }
                            
                            document.getElementById('ibaw-map-status').innerHTML = 'Last updated: ' + time;
                        } else {
                            document.getElementById('ibaw-map-status').innerHTML = 'Location not currently available for this driver.';
                        }
                    })
                    .catch(error => console.error('Error fetching location:', error));
                }

                updateAdminMap();
                setInterval(updateAdminMap, 10000); 
            });
        </script>
        <?php
    }

    /**
     * Render the Proof of Delivery Meta Box HTML.
     */
    public function render_pod_meta_box( $post_or_order_object ) {
        $order = ( $post_or_order_object instanceof WP_Post ) ? wc_get_order( $post_or_order_object->ID ) : $post_or_order_object;
        if ( ! $order ) return;

        $signature = $order->get_meta( '_ibaw_delivery_signature', true );
        $photo_url = $order->get_meta( '_ibaw_delivery_photo_url', true );

        echo '<div style="display: flex; gap: 20px; flex-wrap: wrap;">';

        echo '<div style="flex: 1; min-width: 300px;">';
        echo '<p><strong>' . __( 'Customer Signature:', 'ibaw-local-delivery' ) . '</strong></p>';
        if ( $signature ) {
            echo '<img src="' . esc_attr( $signature ) . '" style="border: 2px solid #e2e8f0; border-radius: 4px; max-width: 100%; height: auto; background: #fff;" alt="Customer Signature" />';
        } else {
            echo '<p>' . __( 'No signature captured.', 'ibaw-local-delivery' ) . '</p>';
        }
        echo '</div>';

        echo '<div style="flex: 1; min-width: 300px;">';
        echo '<p><strong>' . __( 'Delivery Photo:', 'ibaw-local-delivery' ) . '</strong></p>';
        if ( $photo_url ) {
            echo '<a href="' . esc_url( $photo_url ) . '" target="_blank"><img src="' . esc_url( $photo_url ) . '" style="border: 2px solid #e2e8f0; border-radius: 4px; max-width: 100%; height: auto;" alt="Delivery Photo" /></a>';
        } else {
            echo '<p>' . __( 'No photo uploaded.', 'ibaw-local-delivery' ) . '</p>';
        }
        echo '</div>';

        echo '</div>';
    }

    /**
     * Save the assigned driver and auto-update order status.
     */
    public function save_driver_assignment( $order_id ) {
        if ( ! isset( $_POST['ibaw_driver_nonce'] ) || ! wp_verify_nonce( $_POST['ibaw_driver_nonce'], 'ibaw_save_driver' ) ) {
            return;
        }

        if ( isset( $_POST['ibaw_driver_id'] ) ) {
            $order = wc_get_order( $order_id );
            if ( $order ) {
                $new_driver_id = sanitize_text_field( $_POST['ibaw_driver_id'] );
                $current_driver_id = $order->get_meta( '_ibaw_assigned_driver', true );

                // Update the assigned driver meta
                $order->update_meta_data( '_ibaw_assigned_driver', $new_driver_id );
                
                // If a new driver is assigned (and not empty), change status to Out for Delivery
                if ( ! empty( $new_driver_id ) && $new_driver_id !== $current_driver_id ) {
                    $order->update_status( 'out-for-delivery', __( 'Order assigned to driver and is Out for Delivery.', 'ibaw-local-delivery' ) );
                } else {
                    $order->save();
                }
            }
        }
    }

    /**
     * Add "Driver" column to the Orders list.
     */
    public function add_driver_column( $columns ) {
        $new_columns = array();
        foreach ( $columns as $key => $name ) {
            $new_columns[ $key ] = $name;
            if ( 'order_status' === $key ) {
                $new_columns['ibaw_driver'] = __( 'Driver', 'ibaw-local-delivery' );
            }
        }
        return $new_columns;
    }

    /**
     * Populate the "Driver" column for Legacy Orders.
     */
    public function populate_driver_column_legacy( $column, $post_id ) {
        if ( 'ibaw_driver' === $column ) {
            $order = wc_get_order( $post_id );
            $this->render_driver_column_content( $order );
        }
    }

    /**
     * Populate the "Driver" column for HPOS Orders.
     */
    public function populate_driver_column_hpos( $column, $order ) {
        if ( 'ibaw_driver' === $column ) {
            $this->render_driver_column_content( $order );
        }
    }

    /**
     * Shared logic to render the driver column content.
     */
    private function render_driver_column_content( $order ) {
        if ( ! $order ) {
            echo '<span class="na">–</span>';
            return;
        }
        
        $driver_id = $order->get_meta( '_ibaw_assigned_driver', true );
        if ( $driver_id ) {
            $driver = get_userdata( $driver_id );
            echo esc_html( $driver ? $driver->display_name : __( 'Unknown', 'ibaw-local-delivery' ) );
        } else {
            echo '<span class="na">–</span>';
        }
    }

    /**
     * Handle driver status updates, signature saving, and photo uploads.
     */
    public function handle_status_update() {
        if ( isset( $_POST['ibaw_update_status'] ) && isset( $_POST['order_id'] ) && is_user_logged_in() ) {
            if ( wp_verify_nonce( $_POST['ibaw_status_nonce'], 'update_order_status' ) ) {
                $order_id = intval( $_POST['order_id'] );
                $order = wc_get_order( $order_id );
                
                if ( ! $order ) return;

                $driver_id = $order->get_meta( '_ibaw_assigned_driver', true );

                if ( get_current_user_id() == $driver_id ) {
                    $has_updates = false;

                    if ( ! empty( $_POST['ibaw_signature'] ) ) {
                        $signature_data = sanitize_text_field( wp_unslash( $_POST['ibaw_signature'] ) );
                        if ( strpos( $signature_data, 'data:image/png;base64,' ) === 0 ) {
                            $order->update_meta_data( '_ibaw_delivery_signature', $signature_data );
                            $has_updates = true;
                        }
                    }

                    if ( ! empty( $_FILES['ibaw_delivery_photo']['name'] ) ) {
                        if ( ! function_exists( 'wp_handle_upload' ) ) {
                            require_once( ABSPATH . 'wp-admin/includes/file.php' );
                        }
                        
                        $uploaded_file = $_FILES['ibaw_delivery_photo'];
                        $upload_overrides = array( 'test_form' => false );
                        
                        $movefile = wp_handle_upload( $uploaded_file, $upload_overrides );

                        if ( $movefile && ! isset( $movefile['error'] ) ) {
                            $order->update_meta_data( '_ibaw_delivery_photo_url', $movefile['url'] );
                            $has_updates = true;
                        } else {
                            wc_add_notice( __( 'Error uploading photo: ', 'ibaw-local-delivery' ) . $movefile['error'], 'error' );
                        }
                    }

                    if ( $has_updates ) {
                        $order->save();
                    }

                    $order->update_status( 'completed', __( 'Delivery confirmed by driver. Proof of delivery attached.', 'ibaw-local-delivery' ) );
                    wc_add_notice( __( 'Order marked as Delivered successfully.', 'ibaw-local-delivery' ), 'success' );
                }
            }
        }
    }

    /**
     * AJAX Endpoint: Driver's device updates its GPS coordinates
     */
    public function ajax_update_location() {
        check_ajax_referer( 'ibaw_location_nonce', 'security' );
        $user_id = get_current_user_id();

        if ( $user_id && isset( $_POST['lat'] ) && isset( $_POST['lng'] ) ) {
            update_user_meta( $user_id, '_ibaw_driver_lat', sanitize_text_field( $_POST['lat'] ) );
            update_user_meta( $user_id, '_ibaw_driver_lng', sanitize_text_field( $_POST['lng'] ) );
            update_user_meta( $user_id, '_ibaw_driver_last_updated', current_time( 'mysql' ) );
            wp_send_json_success();
        }
        wp_send_json_error();
    }

    /**
     * AJAX Endpoint: Admin retrieves a specific driver's coordinates
     */
    public function ajax_get_driver_location() {
        check_ajax_referer( 'ibaw_get_location_nonce', 'security' );
        $driver_id = isset( $_POST['driver_id'] ) ? intval( $_POST['driver_id'] ) : 0;

        if ( $driver_id ) {
            $lat = get_user_meta( $driver_id, '_ibaw_driver_lat', true );
            $lng = get_user_meta( $driver_id, '_ibaw_driver_lng', true );
            $time = get_user_meta( $driver_id, '_ibaw_driver_last_updated', true );

            if ( $lat && $lng ) {
                wp_send_json_success( array( 'lat' => $lat, 'lng' => $lng, 'time' => $time ) );
            }
        }
        wp_send_json_error( array( 'message' => 'Location not available.' ) );
    }

    /**
     * Render the Frontend Driver Dashboard.
     * Use shortcode: [ibaw_driver_dashboard]
     */
    public function render_driver_dashboard() {
        if ( ! is_user_logged_in() ) {
            return '<p>' . __( 'You must be logged in to view the driver dashboard.', 'ibaw-local-delivery' ) . '</p>';
        }

        $current_user = wp_get_current_user();
        if ( ! in_array( 'delivery_driver', (array) $current_user->roles ) && ! current_user_can( 'manage_woocommerce' ) ) {
            return '<p>' . __( 'Access denied. You do not have permission to view deliveries.', 'ibaw-local-delivery' ) . '</p>';
        }

        // Fetch orders assigned to current user with Out for delivery, Processing, or On-hold status
        $args = array(
            'limit' => -1,
            'status' => array( 'wc-out-for-delivery', 'wc-processing', 'wc-on-hold' ),
            'meta_key' => '_ibaw_assigned_driver',
            'meta_value' => $current_user->ID,
        );
        $orders = wc_get_orders( $args );

        ob_start();
        $this->print_dashboard_styles();
        ?>
        <div class="ibaw-dashboard-wrapper">
            <h2><?php echo esc_html( $current_user->display_name ); ?>'s Active Deliveries</h2>
            <div id="ibaw-gps-status" style="margin-bottom: 15px; font-size: 0.85rem; color: #d97706;">Initializing GPS Tracking...</div>
            
            <?php wc_print_notices(); ?>

            <?php if ( empty( $orders ) ) : ?>
                <div class="ibaw-no-orders">
                    <p>You have no active deliveries assigned at this time.</p>
                </div>
            <?php else : ?>
                <div class="ibaw-grid">
                    <?php foreach ( $orders as $order ) : 
                        $address = $order->get_shipping_address_1() ? $order->get_formatted_shipping_address() : $order->get_formatted_billing_address();
                        $maps_url = 'https://www.google.com/maps/search/?api=1&query=' . urlencode( strip_tags( $address ) );
                        $order_id = $order->get_id();
                        $status_label = wc_get_order_status_name( $order->get_status() );
                    ?>
                        <div class="ibaw-card">
                            <div class="ibaw-card-header">
                                <h3>Order #<?php echo $order->get_order_number(); ?></h3>
                                <span class="ibaw-status badge-processing"><?php echo esc_html( $status_label ); ?></span>
                            </div>
                            <div class="ibaw-card-body">
                                <p><strong>Customer:</strong> <?php echo $order->get_formatted_billing_full_name(); ?></p>
                                <p><strong>Address:</strong> <?php echo wp_kses_post( $address ); ?></p>
                                <p><strong>Notes:</strong> <?php echo esc_html( $order->get_customer_note() ? $order->get_customer_note() : 'None' ); ?></p>
                                
                                <form method="post" action="" id="form-<?php echo esc_attr( $order_id ); ?>" class="ibaw-delivery-form" enctype="multipart/form-data">
                                    <?php wp_nonce_field( 'update_order_status', 'ibaw_status_nonce' ); ?>
                                    <input type="hidden" name="order_id" value="<?php echo esc_attr( $order_id ); ?>">
                                    <input type="hidden" name="ibaw_signature" value="" id="sig-input-<?php echo esc_attr( $order_id ); ?>">
                                    
                                    <div class="ibaw-signature-wrap">
                                        <p><strong>Customer Signature:</strong></p>
                                        <canvas id="sig-canvas-<?php echo esc_attr( $order_id ); ?>" width="300" height="150" class="ibaw-canvas"></canvas>
                                        <button type="button" class="ibaw-btn-clear" onclick="clearSignature(<?php echo esc_attr( $order_id ); ?>)">Clear Signature</button>
                                    </div>

                                    <div class="ibaw-photo-wrap">
                                        <p><strong>Take a Photo:</strong></p>
                                        <input type="file" name="ibaw_delivery_photo" accept="image/*" capture="environment">
                                        <small class="description">Capture proof of delivery using your device's camera.</small>
                                    </div>

                            </div>
                            <div class="ibaw-card-footer">
                                <a href="<?php echo esc_url( $maps_url ); ?>" target="_blank" class="ibaw-btn ibaw-btn-secondary" style="flex: 1;">Map</a>
                                
                                <button type="submit" name="ibaw_update_status" class="ibaw-btn ibaw-btn-primary" style="flex: 2;" onclick="return confirmDelivery(<?php echo esc_attr( $order_id ); ?>);">Delivered</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                
                var gpsStatus = document.getElementById('ibaw-gps-status');
                if ("geolocation" in navigator) {
                    navigator.geolocation.watchPosition(function(position) {
                        gpsStatus.innerHTML = '<span style="color: #059669;">● Live GPS Active</span>';
                        var formData = new FormData();
                        formData.append('action', 'ibaw_update_location');
                        formData.append('lat', position.coords.latitude);
                        formData.append('lng', position.coords.longitude);
                        formData.append('security', '<?php echo wp_create_nonce("ibaw_location_nonce"); ?>');

                        fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
                            method: 'POST',
                            body: formData
                        });
                    }, function(error) {
                        gpsStatus.innerHTML = '<span style="color: #dc2626;">⚠ GPS Error: Please enable location permissions.</span>';
                    }, {
                        enableHighAccuracy: true,
                        maximumAge: 10000,
                        timeout: 5000
                    });
                } else {
                    gpsStatus.innerHTML = '<span style="color: #dc2626;">⚠ Geolocation not supported by this browser.</span>';
                }

                var forms = document.querySelectorAll('.ibaw-delivery-form');
                forms.forEach(function(form) {
                    var orderId = form.querySelector('input[name="order_id"]').value;
                    var canvas = document.getElementById('sig-canvas-' + orderId);
                    if(!canvas) return;
                    
                    var ctx = canvas.getContext('2d');
                    var drawing = false;
                    var sigInput = document.getElementById('sig-input-' + orderId);

                    ctx.strokeStyle = "#2F4F2F";
                    ctx.lineWidth = 3;
                    ctx.lineCap = "round";

                    function getPos(e) {
                        var rect = canvas.getBoundingClientRect();
                        var clientX = e.clientX || (e.touches && e.touches[0].clientX);
                        var clientY = e.clientY || (e.touches && e.touches[0].clientY);
                        return { x: clientX - rect.left, y: clientY - rect.top };
                    }

                    function startDraw(e) {
                        drawing = true;
                        var pos = getPos(e);
                        ctx.beginPath();
                        ctx.moveTo(pos.x, pos.y);
                        if(e.cancelable) e.preventDefault();
                    }

                    function draw(e) {
                        if (!drawing) return;
                        var pos = getPos(e);
                        ctx.lineTo(pos.x, pos.y);
                        ctx.stroke();
                        if(e.cancelable) e.preventDefault();
                    }

                    function endDraw(e) {
                        drawing = false;
                        sigInput.value = canvas.toDataURL("image/png");
                    }

                    canvas.addEventListener('mousedown', startDraw, {passive: false});
                    canvas.addEventListener('mousemove', draw, {passive: false});
                    canvas.addEventListener('mouseup', endDraw);
                    canvas.addEventListener('touchstart', startDraw, {passive: false});
                    canvas.addEventListener('touchmove', draw, {passive: false});
                    canvas.addEventListener('touchend', endDraw);
                });
            });

            function clearSignature(id) {
                var canvas = document.getElementById('sig-canvas-' + id);
                var ctx = canvas.getContext('2d');
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                document.getElementById('sig-input-' + id).value = '';
            }

            function confirmDelivery(id) {
                var sigInput = document.getElementById('sig-input-' + id).value;
                var form = document.getElementById('form-' + id);
                var photoInput = form.querySelector('input[name="ibaw_delivery_photo"]').value;

                if (!sigInput && !photoInput) {
                    return confirm("No signature or photo captured. Do you still want to mark this as delivered?");
                }
                return confirm("Confirm delivery completion?");
            }
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Injects Custom CSS for the frontend dashboard to match Cornerstone Landscape Supply.
     */
    private function print_dashboard_styles() {
        ?>
        <style>
            .ibaw-dashboard-wrapper {
                font-family: 'Helvetica Neue', Arial, sans-serif;
                color: #2c3e50;
                margin: 20px 0;
            }
            .ibaw-dashboard-wrapper h2 {
                color: #2F4F2F; 
                border-bottom: 2px solid #8FBC8F; 
                padding-bottom: 10px;
                margin-bottom: 5px;
            }
            .ibaw-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
                gap: 20px;
            }
            .ibaw-card {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                overflow: hidden;
                display: flex;
                flex-direction: column;
            }
            .ibaw-card-header {
                background: #F4F6F4;
                padding: 15px;
                border-bottom: 1px solid #e2e8f0;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .ibaw-card-header h3 {
                margin: 0;
                font-size: 1.2rem;
                color: #3b2f2f;
            }
            .ibaw-status {
                font-size: 0.8rem;
                padding: 4px 8px;
                border-radius: 4px;
                font-weight: bold;
                text-transform: uppercase;
            }
            .badge-processing {
                background: #FEF3C7;
                color: #92400E;
            }
            .ibaw-card-body {
                padding: 15px;
                flex-grow: 1;
            }
            .ibaw-card-body p {
                margin: 0 0 10px 0;
                line-height: 1.5;
            }
            .ibaw-signature-wrap, .ibaw-photo-wrap {
                margin-top: 15px;
                background: #f9f9f9;
                padding: 10px;
                border-radius: 6px;
                text-align: left;
            }
            .ibaw-signature-wrap {
                text-align: center;
            }
            .ibaw-canvas {
                border: 2px dashed #8FBC8F;
                background: #ffffff;
                cursor: crosshair;
                border-radius: 4px;
                touch-action: none; 
                max-width: 100%;
            }
            .ibaw-photo-wrap input[type="file"] {
                width: 100%;
                margin-top: 5px;
                padding: 8px;
                background: #fff;
                border: 1px solid #ccc;
                border-radius: 4px;
            }
            .ibaw-photo-wrap small {
                display: block;
                margin-top: 5px;
                color: #666;
            }
            .ibaw-btn-clear {
                background: none;
                border: none;
                color: #e53e3e;
                cursor: pointer;
                font-size: 0.85rem;
                margin-top: 5px;
                text-decoration: underline;
            }
            .ibaw-card-footer {
                padding: 15px;
                background: #fafafa;
                border-top: 1px solid #e2e8f0;
                display: flex;
                gap: 10px;
                align-items: stretch; /* Forces equal height for buttons */
            }
            .ibaw-delivery-form {
                margin: 0;
                flex: 2;
                display: flex; /* Makes the form a flex container */
            }
            .ibaw-btn {
                display: flex; /* Ensures content centers inside perfectly */
                justify-content: center;
                align-items: center;
                padding: 12px 15px; /* Larger padding for better mobile tap target */
                font-size: 0.9rem;
                font-weight: 600;
                text-align: center;
                text-decoration: none;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                transition: background 0.2s ease;
                width: 100%;
                box-sizing: border-box;
            }
            .ibaw-btn-primary {
                background: #4CAF50; 
                color: #ffffff;
            }
            .ibaw-btn-primary:hover {
                background: #45a049;
            }
            .ibaw-btn-secondary {
                background: #8B4513; 
                color: #ffffff;
            }
            .ibaw-btn-secondary:hover {
                background: #A0522D;
            }
            .ibaw-no-orders {
                background: #E8F5E9;
                border-left: 4px solid #4CAF50;
                padding: 15px;
                color: #2E7D32;
            }
        </style>
        <?php
    }
}

// Initialize the plugin
new IBAW_Local_Delivery();