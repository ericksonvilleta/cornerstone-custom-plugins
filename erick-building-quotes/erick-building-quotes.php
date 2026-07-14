<?php
/**
 * Plugin Name: IBAW-Erick's Outdoor Building Quotes
 * Plugin URI: https://ericksonvilleta.com
 * Description: Shortcode implementation [ibaw_quote_form]. Bundles the JS with the shortcode, supports multiple email recipients, and includes a WP Dashboard recent leads widget.
 * Version: 1.2
 * Author: Erick Villeta
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// 1. Database Initialization
register_activation_hook( __FILE__, 'erick_quotes_db_init' );
function erick_quotes_db_init() {
    global $wpdb;
    $table = $wpdb->prefix . 'building_quotes';
    $charset = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        name tinytext NOT NULL,
        email varchar(100) NOT NULL,
        phone varchar(20) NOT NULL,
        building varchar(100) NOT NULL,
        message text NOT NULL,
        PRIMARY KEY  (id)
    ) $charset;";
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}

// 2. Admin Menu Registration
add_action('admin_menu', function() {
    add_menu_page( 'Building Quotes', 'Building Quotes', 'manage_options', 'erick-building-quotes', 'erick_quote_settings_page', 'dashicons-email-alt' );
    add_submenu_page( 'erick-building-quotes', 'View Leads', 'View Leads', 'manage_options', 'erick-view-leads', 'erick_view_leads_page' );
});

add_action('admin_init', function() {
    register_setting('erick-quote-settings-group', 'erick_quote_enabled_pages');
    register_setting('erick-quote-settings-group', 'erick_quote_recipient_email');
});

// 3. Admin Settings Page
function erick_quote_settings_page() {
    $pages = get_pages();
    $enabled = (array) get_option('erick_quote_enabled_pages', array());
    // The new default multi-email string
    $default_emails = 'info@cornerstonelandscapesupply.com, justin@cornerstonelawnservices.com, nick@cornerstonelawnservices.com';
    ?>
    <div class="wrap">
        <h1>Building Quote Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('erick-quote-settings-group'); ?>
            <table class="form-table">
                <tr>
                    <th>Recipient Email(s)<br><small style="font-weight:normal;">Separate multiple addresses with a comma.</small></th>
                    <td><input type="text" name="erick_quote_recipient_email" value="<?php echo esc_attr(get_option('erick_quote_recipient_email', $default_emails)); ?>" class="regular-text" style="width: 100%; max-width: 600px;" /></td>
                </tr>
                <tr><th>Enabled Pages (Optional if using Shortcode)</th><td>
                    <div style="background:#fff; border:1px solid #ccc; padding:10px; max-height:200px; overflow-y:auto;">
                        <?php foreach($pages as $p): ?>
                            <label style="display:block;"><input type="checkbox" name="erick_quote_enabled_pages[]" value="<?php echo esc_attr($p->ID); ?>" <?php checked(in_array($p->ID, $enabled)); ?>> <?php echo esc_html($p->post_title); ?></label>
                        <?php endforeach; ?>
                    </div>
                </td></tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// 4. Admin Leads Viewer (Full Table)
function erick_view_leads_page() {
    global $wpdb;
    $results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}building_quotes ORDER BY time DESC");
    echo '<div class="wrap"><h1>Leads Log</h1><table class="wp-list-table widefat fixed striped"><thead><tr><th>Date</th><th>Name</th><th>Email</th><th>Phone</th><th>Building</th><th>Message</th></tr></thead><tbody>';
    
    if (empty($results)) {
        echo '<tr><td colspan="6">No quotes received yet.</td></tr>';
    } else {
        foreach($results as $r) { 
            echo "<tr>
                <td>" . esc_html(date('M j, Y g:ia', strtotime($r->time))) . "</td>
                <td><strong>" . esc_html($r->name) . "</strong></td>
                <td><a href='mailto:" . esc_attr($r->email) . "'>" . esc_html($r->email) . "</a></td>
                <td>" . esc_html($r->phone) . "</td>
                <td>" . esc_html($r->building) . "</td>
                <td>" . nl2br(esc_html($r->message)) . "</td>
            </tr>"; 
        }
    }
    echo '</tbody></table></div>';
}

// 5. Main WP Dashboard Widget (Quick Glance)
add_action('wp_dashboard_setup', 'erick_quotes_dashboard_widget');
function erick_quotes_dashboard_widget() {
    wp_add_dashboard_widget(
        'erick_recent_quotes_widget', 
        'Recent Building Quotes', 
        'erick_quotes_dashboard_widget_display'
    );
}

function erick_quotes_dashboard_widget_display() {
    global $wpdb;
    $results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}building_quotes ORDER BY time DESC LIMIT 5");
    
    if(empty($results)) {
        echo '<p>No quotes received yet.</p>';
        return;
    }
    
    echo '<ul style="margin:0; padding:0; list-style:none;">';
    foreach($results as $r) {
        $date = date('M j, Y', strtotime($r->time));
        echo "<li style='border-bottom:1px solid #eee; padding:10px 0;'>";
        echo "<strong style='color:#2e7d32;'>" . esc_html($r->building) . "</strong> requested by <strong>" . esc_html($r->name) . "</strong><br>";
        echo "<span style='font-size:12px; color:#666;'>" . esc_html($date) . " | " . esc_html($r->phone) . " | <a href='mailto:" . esc_attr($r->email) . "'>Email</a></span>";
        echo "</li>";
    }
    echo '</ul>';
    echo '<p style="margin-top:15px;"><a href="' . admin_url('admin.php?page=erick-view-leads') . '" class="button button-primary">View All Leads in Detail</a></p>';
}

// 6. Shortcode Generation & Self-Contained JS
add_shortcode('ibaw_quote_form', 'erick_building_quote_form_shortcode');
function erick_building_quote_form_shortcode() {
    if (isset($_GET['quote_sent'])) return '<div id="erick-quote-static-fix" style="background:#f0fdf4; padding:20px; border-radius:12px; text-align:center; border:1px solid #bbf7d0; margin:20px 0; color:#15803d; font-weight:bold;">✅ Request for Quote Sent! We will be in touch shortly.</div>';
    
    $current_building = get_the_title();
    $categories = array(
        "Barns" => array("Mini Barns", "Lofted Barns", "Metal Lofted Barns"),
        "Sheds" => array("Utility Sheds", "Metal Sheds", "Single Slope Sheds", "Dormer Sheds", "Gable Dormer Sheds"),
        "Cabins" => array("Utility Side Porch Cabins", "Lofted Side Porch Cabins", "Utility Center Porch Cabins", "Lofted Center Porch Cabins", "Utility Playhouse Cabins", "Lofted Playhouse Cabins", "Deluxe Utility Cabins", "Deluxe Lofted Cabins"),
        "Garages" => array("Utility Garages", "Lofted Garages", "Double Wide Garages"),
        "Specialty" => array("Cabanas", "Greenhouses", "Animal Shelters", "Chicken Coops")
    );

    ob_start(); ?>
    <div id="erick-quote-static-fix" style="clear:both; width:100%; margin-top:30px; position:relative; z-index:10;">
        <div style="background:#fff; padding:25px; border-radius:12px; border:1px solid #e2e8f0; box-shadow:0 10px 30px rgba(0,0,0,0.08); font-family:sans-serif;">
            <h3 style="margin-top:0; color:#2e7d32; border-bottom:3px solid #a5e515; display:inline-block; padding-bottom:5px;">Request a Quote</h3>
            <form action="" method="POST" style="margin-top:15px;">
                <?php wp_nonce_field('b_q_a', 'b_q_n'); ?>
                <div style="margin-bottom:12px;"><label style="display:block; font-weight:600; font-size:14px;">Full Name</label><input type="text" name="q_name" style="width:100%; padding:10px; border-radius:8px; border:1px solid #cbd5e1;" required></div>
                <div style="display:flex; gap:10px; margin-bottom:12px; flex-wrap:wrap;">
                    <div style="flex:1;"><label style="display:block; font-weight:600; font-size:14px;">Email</label><input type="email" name="q_email" style="width:100%; padding:10px; border-radius:8px; border:1px solid #cbd5e1;" required></div>
                    <div style="flex:1;"><label style="display:block; font-weight:600; font-size:14px;">Phone</label><input type="text" name="q_phone" id="q_ph_static_v3" style="width:100%; padding:10px; border-radius:8px; border:1px solid #cbd5e1;" required></div>
                </div>
                <div style="margin-bottom:12px;"><label style="display:block; font-weight:600; font-size:14px;">Building</label>
                <select name="q_interest" style="width:100%; padding:10px; border-radius:8px; border:1px solid #cbd5e1; background:#f9fafb;">
                    <?php foreach($categories as $cat => $items): ?><optgroup label="<?php echo esc_attr($cat); ?>"><?php foreach($items as $item): ?><option value="<?php echo esc_attr($item); ?>" <?php selected($current_building, $item); ?>><?php echo esc_html($item); ?></option><?php endforeach; ?></optgroup><?php endforeach; ?>
                </select></div>
                <div style="margin-bottom:20px;"><label style="display:block; font-weight:600; font-size:14px;">Details</label><textarea name="q_notes" style="width:100%; padding:10px; border-radius:8px; border:1px solid #cbd5e1;" rows="3"></textarea></div>
                <button type="submit" name="s_b_q" style="background:#2e7d32; color:#fff; border:none; padding:18px; font-weight:700; border-radius:8px; cursor:pointer; width:100%; font-size:16px;">Get Quote</button>
            </form>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        $(document).on('input', '#q_ph_static_v3', function(){
            let x = $(this).val().replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,4})/);
            $(this).val(!x[2] ? x[1] : x[1] + '-' + x[2] + (x[3] ? '-' + x[3] : ''));
        });
    });
    </script>
    <?php return ob_get_clean();
}

// 7. Form Submission Handling
add_action('template_redirect', function() {
    if ( isset($_POST['s_b_q']) && isset($_POST['b_q_n']) && wp_verify_nonce($_POST['b_q_n'], 'b_q_a') ) {
        global $wpdb;
        $name = sanitize_text_field($_POST['q_name']); 
        $email = sanitize_email($_POST['q_email']);
        $phone = sanitize_text_field($_POST['q_phone']); 
        $interest = sanitize_text_field($_POST['q_interest']);
        $notes = sanitize_textarea_field($_POST['q_notes']);
        
        // Default emails fallback
        $default_emails = 'info@cornerstonelandscapesupply.com, justin@cornerstonelawnservices.com, nick@cornerstonelawnservices.com';
        $recipients = get_option('erick_quote_recipient_email', $default_emails);
        
        // Insert to DB
        $wpdb->insert($wpdb->prefix . 'building_quotes', array(
            'time' => current_time('mysql'), 
            'name' => $name, 
            'email' => $email, 
            'phone' => $phone, 
            'building' => $interest, 
            'message' => $notes
        ));
        
        // Send Email
        wp_mail(
            $recipients, 
            'New Quote: ' . $interest, 
            "Name: $name\nPhone: $phone\nEmail: $email\nBuilding: $interest\n\nNotes: $notes", 
            array('Reply-To: ' . $email)
        );
        
        // Safe Redirect to prevent header injection
        wp_safe_redirect(add_query_arg('quote_sent', '1', wp_get_referer())); 
        exit;
    }
});