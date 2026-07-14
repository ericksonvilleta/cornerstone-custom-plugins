<?php
/**
 * Plugin Name: Cornerstone Landscape Directory
 * Plugin URI: https://ericksonvilleta.com
 * Description: Directory with Location Search, Radius Filter, Service Filter, Strict Logo Validation, Admin Approval, and Line-by-Line Email Notifications.
 * Version: 4.7.0
 * Author: Erick Villeta
 * Author URI: https://ericksonvilleta.com
 * License: GPL v2 or later
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * 1. Register the "Contractor" Custom Post Type
 */
function erick_register_contractor_cpt() {
    $args = array(
        'public'        => true,
        'label'         => 'Contractors',
        'menu_icon'     => 'dashicons-location',
        'supports'      => array( 'title', 'thumbnail', 'custom-fields' ),
        'has_archive'   => true,
        'show_in_rest'  => true,
    );
    register_post_type( 'contractor', $args );
}
add_action( 'init', 'erick_register_contractor_cpt' );

/**
 * 2. Admin Settings for API Key
 */
function erick_map_settings_menu() {
    add_submenu_page( 'edit.php?post_type=contractor', 'Map Settings', 'Map Settings', 'manage_options', 'erick-map-settings', 'erick_map_settings_page' );
}
add_action( 'admin_menu', 'erick_map_settings_menu' );

function erick_register_map_settings() {
    register_setting( 'erick-map-settings-group', 'erick_gmaps_api_key' );
}
add_action( 'admin_init', 'erick_register_map_settings' );

function erick_map_settings_page() {
    ?>
    <div class="wrap">
        <h1>Directory Map Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields( 'erick-map-settings-group' ); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Google Maps API Key</th>
                    <td>
                        <input type="text" name="erick_gmaps_api_key" value="<?php echo esc_attr( get_option('erick_gmaps_api_key') ); ?>" style="width: 400px;" />
                        <p class="description">Required for Maps, Places Autocomplete, and Radius calculations.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

/**
 * 3. Handle Frontend Submissions (Contractor Form)
 */
function erick_handle_contractor_submission() {
    if ( isset( $_POST['submit_contractor'] ) && wp_verify_nonce( $_POST['contractor_nonce'], 'submit_contractor_action' ) ) {
        
        $business_name = sanitize_text_field( $_POST['business_name'] );
        $business_address = sanitize_text_field( $_POST['business_address'] );
        $biz_email = sanitize_email( $_POST['biz_email'] );
        $biz_phone = sanitize_text_field( $_POST['biz_phone'] );
        $services = isset($_POST['core_services']) ? array_map('sanitize_text_field', $_POST['core_services']) : array();

        if ( ! empty( $_FILES['business_logo']['tmp_name'] ) ) {
            $image_size = getimagesize( $_FILES['business_logo']['tmp_name'] );
            if ( !(($image_size[0] == 32 && $image_size[1] == 32) || ($image_size[0] == 152 && $image_size[1] == 32)) ) {
                wp_die( 'Error: Image must be exactly 32x32 or 152x32 pixels.' );
            }
        }

        $post_id = wp_insert_post( array(
            'post_title'   => $business_name,
            'post_type'    => 'contractor',
            'post_status'  => 'pending', 
        ));

        if ( $post_id ) {
            update_post_meta( $post_id, 'contractor_address', $business_address );
            update_post_meta( $post_id, 'contractor_email', $biz_email );
            update_post_meta( $post_id, 'contractor_phone', $biz_phone );
            update_post_meta( $post_id, 'core_services', $services );
            
            if ( ! empty( $_FILES['business_logo']['name'] ) ) {
                require_once( ABSPATH . 'wp-admin/includes/image.php' );
                require_once( ABSPATH . 'wp-admin/includes/file.php' );
                require_once( ABSPATH . 'wp-admin/includes/media.php' );
                $attachment_id = media_handle_upload( 'business_logo', $post_id );
                if ( ! is_wp_error( $attachment_id ) ) set_post_thumbnail( $post_id, $attachment_id );
            }

            // Line-by-line Email to Erick
			$to = 'info@cornerstonelandscapesupply.com';
			$subject = 'New Contractor Listing Pending Approval: ' . $business_name;
			$service_list = !empty($services) ? implode(', ', $services) : 'None';
						
			$message = "A new contractor has submitted a listing for review.\n\n";
			$message .= "BUSINESS NAME:\n" . $business_name . "\n\n";
			$message .= "EMAIL:\n" . $biz_email . "\n\n";
			$message .= "PHONE:\n" . $biz_phone . "\n\n";
			$message .= "ADDRESS:\n" . $business_address . "\n\n";
			$message .= "SERVICES OFFERED:\n" . $service_list . "\n\n";
			$message .= "REVIEW & APPROVE HERE:\n" . admin_url('post.php?post=' . $post_id . '&action=edit');

			// Define your CC and BCC email addresses
			$cc_email = 'cc_recipient@example.com';   // Replace with actual CC email
			$bcc_email = 'bcc_recipient@example.com'; // Replace with actual BCC email

			// Construct the headers array
			$headers = array(
				'Cc: ' . $cc_email,
				'Bcc: ' . $bcc_email
			);

			// Add the $headers array to the wp_mail function
			wp_mail($to, $subject, $message, $headers);

			wp_redirect( add_query_arg( 'success', '1', wp_get_referer() ) );
			exit;
        }
    }
}
add_action( 'template_redirect', 'erick_handle_contractor_submission' );

/**
 * 4. Shortcode: The Submission Form
 */
function erick_contractor_form_shortcode() {
    if ( isset($_GET['success']) ) {
        return '<div style="max-width:600px; margin:40px auto; text-align:center; padding:50px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:12px; font-family:sans-serif;">
                    <h2 style="color:#166534; margin-top:0;">Thank You!</h2>
                    <p style="color:#1e293b; font-size:1.1em;">Your contractor listing has been submitted successfully. Our team will review your application and notify you once it is live on our directory.</p>
                </div>';
    }

    $api_key = get_option( 'erick_gmaps_api_key' ); 
    $services_list = array("Lawn care", "Planting", "Design consultation", "Landscape design", "Sod or turf installation", "Lawn returfing", "Artificial turf installation", "Irrigation installation", "Property grading", "Tree removal", "Patio installation", "Path or driveway installation", "Retaining wall installation", "Fence installation", "Deck installation", "Outdoor lighting", "Water/fire feature installation", "Property drainage", "Gutter cleaning", "Snow removal", "Seasonal decorations");
    ob_start(); ?>
    <style>
        .erick-dir-form-wrapper { max-width: 600px; margin: 2rem auto; background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); border: 1px solid #f0f0f0; font-family: sans-serif;}
        .erick-dir-form-group { margin-bottom: 24px; }
        .erick-dir-form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #2c3e50; }
        .erick-dir-input { width: 100%; padding: 14px; border: 2px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; }
        .phone-input-wrapper { position: relative; display: flex; align-items: center; }
        .phone-flag { position: absolute; left: 12px; display: flex; align-items: center; gap: 5px; pointer-events: none; }
        .us-flag-icon { width: 20px; height: 14px; background: url('https://upload.wikimedia.org/wikipedia/en/a/a4/Flag_of_the_United_States.svg') no-repeat center; background-size: contain; border: 1px solid #ddd; }
        .country-code { font-weight: bold; color: #64748b; font-size: 0.95em; }
        #biz_phone { padding-left: 65px !important; }
        .services-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; }
        .erick-dir-submit { background-color: #2e7d32; color: #fff; padding: 16px; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; width: 100%; }
        .pac-container { z-index: 10000 !important; }
    </style>
    
    <div class="erick-dir-form-wrapper">
        <form action="" method="POST" enctype="multipart/form-data">
            <?php wp_nonce_field( 'submit_contractor_action', 'contractor_nonce' ); ?>
            <div class="erick-dir-form-group"><label>Business Name *</label><input type="text" name="business_name" class="erick-dir-input" required></div>
            
            <div style="display: flex; gap: 20px;">
                <div class="erick-dir-form-group" style="flex: 1;"><label>Business Email *</label><input type="email" name="biz_email" class="erick-dir-input" required></div>
                <div class="erick-dir-form-group" style="flex: 1;">
                    <label>Business Phone *</label>
                    <div class="phone-input-wrapper">
                        <div class="phone-flag"><div class="us-flag-icon"></div><span class="country-code">+1</span></div>
                        <input type="text" name="biz_phone" id="biz_phone" class="erick-dir-input" placeholder="000-000-0000" maxlength="12" required>
                    </div>
                </div>
            </div>

            <div class="erick-dir-form-group">
                <label>Business Address *</label>
                <input type="text" id="business_address_input" name="business_address" class="erick-dir-input" placeholder="Start typing address..." required>
            </div>

            <div class="erick-dir-form-group">
                <label>Core Services Offered</label>
                <div class="services-grid">
                    <?php foreach($services_list as $service): ?>
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 0.9em;"><input type="checkbox" name="core_services[]" value="<?php echo esc_attr($service); ?>"> <?php echo esc_html($service); ?></label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="erick-dir-form-group">
                <label>Logo (JPG/PNG) *</label>
                <input type="file" id="logo-input" name="business_logo" class="erick-dir-input" accept="image/png, image/jpeg" required>
                <span style="font-size: 0.85em; color: #d32f2f;">Must be exactly 32x32 or 152x32 pixels.</span>
            </div>
            <button type="submit" name="submit_contractor" class="erick-dir-submit">Submit Listing</button>
        </form>
    </div>

    <?php if(!empty($api_key)): ?>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo esc_attr($api_key); ?>&libraries=places"></script>
    <?php endif; ?>

    <script>
    function initFormAutocomplete() {
        const input = document.getElementById('business_address_input');
        if (input && typeof google !== 'undefined' && google.maps && google.maps.places) {
            new google.maps.places.Autocomplete(input, { types: ['address'], componentRestrictions: {country: 'us'} });
        }
    }
    document.getElementById('biz_phone').addEventListener('input', function (e) {
        let x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,4})/);
        e.target.value = !x[2] ? x[1] : x[1] + '-' + x[2] + (x[3] ? '-' + x[3] : '');
    });
    document.getElementById('logo-input').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if(!file) return;
        const img = new Image();
        img.onload = function() {
            if(!((this.width===32 && this.height===32) || (this.width===152 && this.height===32))) {
                alert('Invalid dimensions! Must be 32x32 or 152x32.'); e.target.value = '';
            }
        };
        img.src = URL.createObjectURL(file);
    });
    if (typeof google !== 'undefined' && google.maps && google.maps.places) {
        initFormAutocomplete();
    } else {
        window.addEventListener('load', initFormAutocomplete);
    }
    </script>
    <?php return ob_get_clean();
}
add_shortcode( 'contractor_form', 'erick_contractor_form_shortcode' );

/**
 * 5. Shortcode: The Interactive Map
 */
function erick_contractor_map_shortcode() {
    $api_key = get_option( 'erick_gmaps_api_key' );
    if ( empty( $api_key ) ) return '<p>API Key missing in Map Settings.</p>';
    $services_list = array("Lawn care", "Planting", "Design consultation", "Landscape design", "Sod or turf installation", "Lawn returfing", "Artificial turf installation", "Irrigation installation", "Property grading", "Tree removal", "Patio installation", "Path or driveway installation", "Retaining wall installation", "Fence installation", "Deck installation", "Outdoor lighting", "Water/fire feature installation", "Property drainage", "Gutter cleaning", "Snow removal", "Seasonal decorations");

    $query = new WP_Query( array( 'post_type' => 'contractor', 'post_status' => 'publish', 'posts_per_page' => -1 ) );
    $locations = array(); $index = 0;
    ob_start(); ?>
    <style>
        .erick-map-container { display: flex; height: 700px; border: 1px solid #ddd; margin-bottom: 20px; font-family: sans-serif;}
        .erick-map-sidebar { width: 350px; display: flex; flex-direction: column; background: #fff; border-right: 1px solid #ddd; }
        .erick-map-header { padding: 15px; background: #f9f9f9; border-bottom: 1px solid #eee; }
        .erick-map-list { flex: 1; overflow-y: auto; }
        .erick-list-item { padding: 15px; border-bottom: 1px solid #eee; cursor: pointer; transition: 0.2s; }
        .erick-list-item:hover, .erick-list-item.active { background: #f4fcf4; border-left: 4px solid #a5e515; }
        #erick-google-map { width: 100%; height: 100%; }
        .hidden-item { display: none !important; }
        .erick-infowindow { display: flex; align-items: center; gap: 10px; padding: 5px; font-family: sans-serif;}
        .erick-infowindow img { width: 50px; height: 50px; object-fit: contain; border: 1px solid #eee;}
        @media (max-width: 768px) { .erick-map-container { flex-direction: column; height: 1000px; } .erick-map-sidebar { width: 100%; height: 400px; } }
    </style>

    <div class="erick-map-container">
        <div class="erick-map-sidebar">
            <div class="erick-map-header">
                <input type="text" id="map-search" placeholder="Search City or Zip..." style="width:100%; padding:10px; margin-bottom:10px; border: 1px solid #ccc; border-radius: 4px;">
                <select id="radius-select" style="width:100%; padding:10px; margin-bottom:10px; border: 1px solid #ccc; border-radius: 4px;"><option value="0">All Distances</option><option value="5">5 Miles</option><option value="10">10 Miles</option><option value="25">25 Miles</option><option value="50">50 Miles</option></select>
                <select id="service-filter" style="width:100%; padding:10px; border: 1px solid #ccc; border-radius: 4px;"><option value="">All Services</option><?php foreach($services_list as $s): echo "<option value='".esc_attr($s)."'>$s</option>"; endforeach; ?></select>
            </div>
            <div class="erick-map-list" id="contractor-list">
                <?php while ( $query->have_posts() ) : $query->the_post(); 
                    $addr = get_post_meta( get_the_ID(), 'contractor_address', true );
                    $logo = get_the_post_thumbnail_url(get_the_ID(), 'thumbnail') ?: '';
                    $services = get_post_meta( get_the_ID(), 'core_services', true ) ?: array();
                    if ($addr) {
                        $locations[] = array('title' => get_the_title(), 'address' => $addr, 'logo' => $logo, 'id' => $index, 'lat' => null, 'lng' => null, 'services' => $services); ?>
                        <div class="erick-list-item" id="item-<?php echo $index; ?>" onclick="focusMarker(<?php echo $index; ?>)">
                            <strong><?php the_title(); ?></strong><div style="font-size:12px; color:#666;">📍 <?php echo esc_html($addr); ?></div>
                            <label style="font-size:12px; margin-top:5px; display:block;"><input type="checkbox" class="quote-selector" data-name="<?php the_title(); ?>" onclick="event.stopPropagation();"> Select for Quote</label>
                        </div>
                <?php $index++; } endwhile; wp_reset_postdata(); ?>
            </div>
        </div>
        <div class="erick-map-area" style="flex:1;"><div id="erick-google-map"></div></div>
    </div>

    <script>
        var map, infoWindow, bounds, markers = [], locationsData = <?php echo json_encode($locations); ?>;
        function initMap() {
            map = new google.maps.Map(document.getElementById('erick-google-map'), { zoom: 4, center: {lat: 39.8, lng: -98.5}, mapTypeId: 'terrain' });
            infoWindow = new google.maps.InfoWindow(); bounds = new google.maps.LatLngBounds();
            const geocoder = new google.maps.Geocoder();
            locationsData.forEach((loc, i) => {
                geocoder.geocode({address: loc.address}, (res, status) => {
                    if (status === 'OK') {
                        const pos = res[0].geometry.location; locationsData[i].lat = pos.lat(); locationsData[i].lng = pos.lng();
                        const marker = new google.maps.Marker({ position: pos, map: map, title: loc.title, icon: { path: 'M12 2C8.1 2 5 5.1 5 9c0 5.2 7 13 7 13s7-7.8 7-13c0-3.9-3.1-7-7-7z', fillColor: '#D30000', fillOpacity: 1, scale: 2, anchor: new google.maps.Point(12, 22), strokeWeight: 1, strokeColor: '#fff'} });
                        markers[i] = marker; bounds.extend(pos); map.fitBounds(bounds);
                        marker.addListener('click', () => {
                            infoWindow.setContent(`<div class="erick-infowindow">${loc.logo?`<img src="${loc.logo}">`:''}<div><strong>${loc.title}</strong><br><small>${loc.address}</small></div></div>`);
                            infoWindow.open(map, marker); map.panTo(pos); map.setZoom(15);
                        });
                    }
                });
            });
            document.getElementById('radius-select').addEventListener('change', filterMap);
            document.getElementById('service-filter').addEventListener('change', filterMap);
            new google.maps.places.Autocomplete(document.getElementById('map-search')).addListener('place_changed', filterMap);
        }
        function filterMap() {
            const search = document.getElementById('map-search').value;
            const radius = parseFloat(document.getElementById('radius-select').value);
            const service = document.getElementById('service-filter').value;
            const process = (center = null) => {
                locationsData.forEach((loc, i) => {
                    let visible = (!service || (loc.services && loc.services.includes(service)));
                    if (visible && center && radius > 0) {
                        const d = google.maps.geometry.spherical.computeDistanceBetween(center, new google.maps.LatLng(loc.lat, loc.lng)) * 0.000621;
                        if (d > radius) visible = false;
                    }
                    if(markers[i]) markers[i].setVisible(visible);
                    document.getElementById('item-'+i).classList.toggle('hidden-item', !visible);
                });
            };
            if (search) {
                new google.maps.Geocoder().geocode({address: search}, (res, stat) => { if(stat==='OK') { map.setCenter(res[0].geometry.location); process(res[0].geometry.location); } });
            } else { process(); }
        }
        function focusMarker(id) { if(markers[id]) google.maps.event.trigger(markers[id], 'click'); }
    </script>
    <script async defer src="https://maps.googleapis.com/maps/api/js?key=<?php echo esc_attr($api_key);?>&libraries=places,geometry&callback=initMap"></script>
    <?php return ob_get_clean();
}
add_shortcode( 'contractor_map', 'erick_contractor_map_shortcode' );

/**
 * 6. Handle & Display Quote Form Emails
 */
function erick_handle_quote_submission() {
    if ( isset($_POST['send_quote']) && wp_verify_nonce($_POST['q_nonce'], 'q_action') ) {
        $to = 'ericksonvilleta@gmail.com';
        $name = sanitize_text_field($_POST['u_name']); $email = sanitize_email($_POST['u_email']);
        $contractors = sanitize_text_field($_POST['selected_names']); $details = sanitize_textarea_field($_POST['u_msg']);
        
        // Line-by-line formatting for Quote Request
        $msg = "NEW DIRECTORY QUOTE REQUEST\n\n";
        $msg .= "CUSTOMER NAME:\n" . $name . "\n\n";
        $msg .= "CUSTOMER EMAIL:\n" . $email . "\n\n";
        $msg .= "SELECTED CONTRACTORS:\n" . $contractors . "\n\n";
        $msg .= "PROJECT DETAILS:\n" . $details;

        wp_mail($to, 'New Directory Quote Request', $msg, array('Reply-To: '.$name.' <'.$email.'>'));
        wp_redirect(add_query_arg('sent', '1', wp_get_referer() . '#quote-section')); exit;
    }
}
add_action('template_redirect', 'erick_handle_quote_submission');

function erick_quote_form_shortcode() {
    ob_start(); ?>
    <style>
        /* The Overlay - Now absolute so it sits inside the page flow or map area */
        #quote-section { 
            display: none; 
            position: absolute; 
            z-index: 99999; 
            left: 0; 
            top: 0; 
            width: 100%; 
            height: 100%; 
            background-color: rgba(0,0,0,0.7); 
            backdrop-filter: blur(3px);
            display: flex;
            align-items: flex-start; /* Aligns to top of the map area */
            justify-content: center;
            padding-top: 50px; /* Space from the top of the map */
        }
        
        /* The Modal Box */
        .erick-quote-modal-content { 
            background: #fff; 
            padding: 30px; 
            border-radius: 12px; 
            width: 90%;
            max-width: 500px; 
            position: relative; 
            box-shadow: 0 15px 40px rgba(0,0,0,0.4); 
            font-family: sans-serif;
        }

        /* Close Button */
        .erick-close-btn { 
            position: absolute; top: 15px; right: 20px; 
            font-size: 28px; font-weight: bold; color: #aaa; 
            cursor: pointer; line-height: 1;
        }
        .erick-close-btn:hover { color: #000; }

        .selected-box { 
            font-size: 13px; background: #f8fafc; padding: 12px; 
            border-left: 4px solid #a5e515; margin-bottom: 20px; border-radius: 6px;
        }

        /* Input Styling */
        .erick-dir-form-group { margin-bottom: 15px; text-align: left; }
        .erick-dir-form-group label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 14px; }
        .erick-dir-input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        .erick-dir-submit { background-color: #a5e515; color: #000; padding: 14px; border: none; border-radius: 6px; font-weight: 700; cursor: pointer; width: 100%; }
    </style>

    <div id="quote-section" style="display:none;">
        <div class="erick-quote-modal-content">
            <span class="erick-close-btn" onclick="document.getElementById('quote-section').style.display='none'">&times;</span>
            
            <h3 style="margin-top:0; font-size: 20px;">Request A Quote</h3>
            <div class="selected-box">
                <strong>Selected:</strong> <span id="display-selected">None</span>
            </div>

            <form action="" method="POST">
                <?php wp_nonce_field('q_action','q_nonce'); ?>
                <input type="hidden" name="selected_names" id="hidden-selected-names">
                
                <div class="erick-dir-form-group"><label>Name</label><input type="text" name="u_name" class="erick-dir-input" required></div>
                <div class="erick-dir-form-group"><label>Email</label><input type="email" name="u_email" class="erick-dir-input" required></div>
                <div class="erick-dir-form-group"><label>Details</label><textarea name="u_msg" class="erick-dir-input" rows="3" required></textarea></div>
                
                <button type="submit" name="send_quote" class="erick-dir-submit">Send Quote Request</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('change', function(e) {
            if(e.target.classList.contains('quote-selector')) {
                const checked = Array.from(document.querySelectorAll('.quote-selector:checked'));
                const names = checked.map(cb => cb.dataset.name);
                
                const modal = document.getElementById('quote-section');
                const display = document.getElementById('display-selected');
                const hiddenInput = document.getElementById('hidden-selected-names');
                
                // IMPORTANT: Move the modal inside the map container so 'absolute' works
                const mapContainer = document.querySelector('.erick-map-container');
                if (mapContainer && modal.parentElement !== mapContainer) {
                    mapContainer.appendChild(modal);
                }

                if (names.length > 0) {
                    modal.style.display = 'flex';
                    display.innerText = names.join(', ');
                    hiddenInput.value = names.join(', ');
                } else {
                    modal.style.display = 'none';
                }
            }
        });
    </script>
    <?php return ob_get_clean();
}
add_shortcode( 'quote_form', 'erick_quote_form_shortcode' );

/**
 * 7. Admin Approval System
 */
function erick_contractor_row_actions( $actions, $post ) {
    if ( $post->post_type === 'contractor' && $post->post_status === 'pending' ) {
        $approve_url = wp_nonce_url( admin_url( 'admin-post.php?action=erick_approve_contractor&post_id=' . $post->ID ), 'approve_contractor_' . $post->ID );
        $actions['approve'] = '<a href="' . $approve_url . '" style="color:#2e7d32; font-weight:bold;">APPROVE NOW</a>';
    }
    return $actions;
}
add_filter( 'post_row_actions', 'erick_contractor_row_actions', 10, 2 );

function erick_add_approval_submit_box() {
    global $post;
    if ( is_admin() && $post->post_type === 'contractor' && $post->post_status === 'pending' ) {
        $approve_url = wp_nonce_url( admin_url( 'admin-post.php?action=erick_approve_contractor&post_id=' . $post->ID ), 'approve_contractor_' . $post->ID );
        echo '<div class="misc-pub-section" style="border-top:1px solid #eee; margin-top:10px; padding-top:10px;"><a href="'.$approve_url.'" class="button button-primary button-large" style="background:#2e7d32; border-color:#2e7d32; width:100%; text-align:center; color:white;">Approve & Publish</a></div>';
    }
}
add_action( 'post_submitbox_misc_actions', 'erick_add_approval_submit_box' );

function erick_handle_approve_contractor() {
    if ( ! isset( $_GET['post_id'] ) || ! current_user_can( 'publish_posts' ) ) wp_die( 'Denied.' );
    $post_id = intval( $_GET['post_id'] ); check_admin_referer( 'approve_contractor_' . $post_id );
    wp_update_post( array('ID' => $post_id, 'post_status' => 'publish') );
    wp_redirect( admin_url( 'edit.php?post_type=contractor&approved=1' ) ); exit;
}
add_action( 'admin_post_erick_approve_contractor', 'erick_handle_approve_contractor' );

add_action( 'admin_notices', function() {
    if ( isset( $_GET['approved'] ) && $_GET['approved'] == 1 ) echo '<div class="updated notice is-dismissible"><p>✅ Contractor approved and is now live!</p></div>';
});