<?php
/**
 * Plugin Name: Erick's Speed Optimizer (Client-Side Edition)
 * Plugin URI:  https://ericksonvilleta.com
 * Description: Uses Client-Side AJAX to prevent Server cURL timeouts.
 * Version:     1.0
 * Author:      Erick Villeta
 */

if (!defined('ABSPATH')) exit;

class EricksSpeedOptimizer {

    // Get your key: https://developers.google.com/speed/docs/insights/v5/get-started
    private $google_key = 'AIzaSyDlhcZJSF9ehTVKSIHiV3o11Zdj3PA35Vk'; 

    public function __construct() {
        add_action('admin_menu', [$this, 'create_menu']);
        if (get_option('eso_safe_mode') !== 'on') {
            add_action('wp_enqueue_scripts', [$this, 'apply_fixes'], 100);
        }
    }

    public function create_menu() {
        add_menu_page('Erick Speed', 'Erick Speed', 'manage_options', 'erick-speed', [$this, 'render_page'], 'dashicons-performance');
    }

    public function apply_fixes() {
        // Essential Bloat Removal
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_print_styles', 'print_emoji_styles');
    }

    public function render_page() {
        if (isset($_POST['save_settings'])) {
            update_option('eso_safe_mode', isset($_POST['safe_mode']) ? 'on' : 'off');
        }
        $safe_mode = get_option('eso_safe_mode');
        $site_url = home_url();
        ?>
        <div class="wrap">
            <h1>🚀 Erick's Speed Optimizer (v1.5)</h1>
            
            <div style="background:#fff; padding:20px; border:1px solid #ccd; border-radius:5px; margin-bottom:20px;">
                <h3>Optimization Control</h3>
                <form method="post">
                    <label><input type="checkbox" name="safe_mode" <?php checked($safe_mode, 'on'); ?>> <strong>Safe Mode</strong> (Disable all auto-fixes)</label>
                    <input type="submit" name="save_settings" class="button" value="Save" style="margin-left:10px;">
                </form>
            </div>

            <div style="background:#fff; padding:20px; border:1px solid #ccd; border-radius:5px;">
                <h3>Live Speed Audit</h3>
                <p>Testing: <code><?php echo $site_url; ?></code></p>
                <button id="run-audit" class="button button-primary button-large">Check Speed via Google PSI</button>
                
                <div id="audit-status" style="margin-top:15px; font-weight:bold;"></div>
                <div id="audit-results" style="margin-top:20px; display:none; border-top:1px solid #eee; padding-top:20px;">
                    <h2 id="score-display"></h2>
                    <div id="audit-list"></div>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#run-audit').click(function(e) {
                e.preventDefault();
                const btn = $(this);
                const status = $('#audit-status');
                const results = $('#audit-results');
                
                btn.prop('disabled', true).text('Analyzing Site...');
                status.html('<span style="color:blue;">Connecting to Google... (This may take 30-60 seconds)</span>');
                results.hide();

                const psiUrl = "https://www.googleapis.com/pagespeedonline/v5/runPagespeed?url=<?php echo urlencode($site_url); ?>&key=<?php echo $this->google_key; ?>";

                $.ajax({
                    url: psiUrl,
                    method: 'GET',
                    timeout: 90000, // 90 seconds - Google is slow sometimes
                    success: function(data) {
                        btn.prop('disabled', false).text('Check Performance Now');
                        const score = data.lighthouseResult.categories.performance.score * 100;
                        const audits = data.lighthouseResult.audits;
                        
                        status.html('✅ Audit Complete!');
                        results.show();
                        $('#score-display').html('Performance Score: <span style="color:' + (score > 80 ? 'green' : 'red') + '">' + score + '/100</span>');
                        
                        let html = '<h4>Critical Fixes Required:</h4><ul>';
                        for (let key in audits) {
                            let audit = audits[key];
                            if (audit.score !== null && audit.score < 0.9 && audit.title) {
                                html += '<li style="margin-bottom:10px;"><strong>' + audit.title + '</strong><br><small>' + audit.description + '</small></li>';
                            }
                        }
                        html += '</ul>';
                        $('#audit-list').html(html);
                    },
                    error: function(xhr) {
                        btn.prop('disabled', false).text('Retry Audit');
                        status.html('<span style="color:red;">Error: Could not reach Google. Check if your API key is valid or if your site blocks Google\'s bot.</span>');
                    }
                });
            });
        });
        </script>
        <?php
    }
}

new EricksSpeedOptimizer();