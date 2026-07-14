<?php
/**
 * Plugin Name: IBAW-CLS Global Browser Cache Buster
 * Description: Dynamically forces all active browser sessions across the web to drop their local cache and perform an immediate hard reload upon admin command.
 * Version: 1.0.0
 * Author: Erick Villeta
 * Plugin URI: https://ericksonvilleta.com
 */

if (!defined('ABSPATH')) exit;

class IBAW_CLS_Browser_Cache_Buster {

    public function __construct() {
        // Admin menu and setup
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        
        // Infrastructure endpoints and asset injection
        add_action('init', [$this, 'handle_service_worker_routing']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_cache_watcher_scripts']);
        
        // REST API endpoint for the client-side heartbeat monitor
        add_action('rest_api_init', [$this, 'register_version_check_endpoint']);

        // Prevent standard caching strategies via raw HTTP headers
        add_action('send_headers', [$this, 'inject_aggressive_nocache_headers']);
    }

    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            'Browser Cache Buster',
            'Browser Cache Buster',
            'manage_options',
            'ibaw-cls-cache-buster',
            [$this, 'render_admin_page']
        );
    }

    public function register_settings() {
        register_setting('ibaw-cls-cb-group', 'ibaw_cls_cache_version');
    }

    public function inject_aggressive_nocache_headers() {
        if (is_admin()) return;
        
        // Explicitly instruct browsers and CDNs never to serve stale cache
        header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');
    }

    public function render_admin_page() {
        // Handle explicit manual cache bust executions
        if (isset($_POST['ibaw_cls_bust_cache']) && check_admin_referer('ibaw_cls_bust_action', 'ibaw_cls_bust_nonce')) {
            $new_version = time(); // Use current unix timestamp as unique version id
            update_option('ibaw_cls_cache_version', $new_version);
            echo '<div class="updated"><p><strong>Global browser cache bust triggered! All open client browsers will reload shortly.</strong></p></div>';
        }

        $current_version = get_option('ibaw_cls_cache_version', '1000');
        ?>
        <div class="wrap">
            <h1>IBAW-CLS Global Browser Cache Buster</h1>
            <div style="background:#fff; padding:20px; border:1px solid #ccd0d4; border-left:4px solid #d32f2f; margin-bottom:20px; max-width:600px;">
                <h2>Force Global Cache Eviction</h2>
                <p>Clicking the button below alters the remote configuration token. Any browser instance currently open to your platform will sense this token change, dump its localized caches, and instantly refresh.</p>
                <p><strong>Current Active Token Reference:</strong> <code><?php echo esc_html($current_version); ?></code></p>
                
                <form method="post" action="">
                    <?php wp_nonce_field('ibaw_cls_bust_action', 'ibaw_cls_bust_nonce'); ?>
                    <input type="hidden" name="ibaw_cls_bust_cache" value="1">
                    <?php submit_button('Nuke All Browser Caches Now', 'delete', 'submit', false); ?>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Dynamically intercept and route the Service Worker registration file.
     * Service Workers must be served directly from the root URI scope to accurately possess authority over all pages.
     */
    public function handle_service_worker_routing() {
        if (isset($_SERVER['REQUEST_URI']) && rtrim($_SERVER['REQUEST_URI'], '/') === '/ibaw-cls-sw.js') {
            header('Content-Type: application/javascript; charset=utf-8');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');

            $current_version = get_option('ibaw_cls_cache_version', '1000');
            
            // Service Worker payload that immediately activates and bypasses local constraints
            ?>
            const CACHE_VERSION = '<?php echo esc_js($current_version); ?>';

            self.addEventListener('install', (event) => {
                // Instantly bypass standard waiting states
                event.waitUntil(self.skipWaiting());
            });

            self.addEventListener('activate', (event) => {
                // Claim control of all active clients immediately
                event.waitUntil(
                    caches.keys().then((cacheNames) => {
                        return Promise.all(
                            cacheNames.map((cache) => {
                                console.log('[Service Worker] Evicting old cache storage structure');
                                return caches.delete(cache);
                            })
                        );
                    }).then(() => self.clients.claim())
                );
            });

            self.addEventListener('fetch', (event) => {
                // Network-only fallback strategy to completely ignore programmatic file caching
                event.respondWith(fetch(event.request).catch(() => fetch(event.request)));
            });
            <?php
            exit;
        }
    }

    public function register_version_check_endpoint() {
        register_rest_route('ibaw-cls/v1', '/cache-state', [
            'methods'  => 'GET',
            'callback' => [$this, 'get_current_cache_version'],
            'permission_callback' => '__return_true'
        ]);
    }

    public function get_current_cache_version() {
        return new WP_REST_Response([
            'version' => get_option('ibaw_cls_cache_version', '1000')
        ], 200);
    }

    public function enqueue_cache_watcher_scripts() {
        if (is_admin()) return;

        $current_version = get_option('ibaw_cls_cache_version', '1000');
        $rest_url = esc_url_raw(rest_url('ibaw-cls/v1/cache-state'));

        ?>
        <script>
            (function($) {
                'use strict';

                var currentVersion = '<?php echo esc_js($current_version); ?>';
                var restEndpoint = '<?php echo $rest_url; ?>';

                // 1. Initialize Root Level Service Worker to intercept and manage storage domains
                if ('serviceWorker' in navigator) {
                    navigator.serviceWorker.register('/ibaw-cls-sw.js', { scope: '/' })
                    .then(function(registration) {
                        // Check for operational updates explicitly 
                        registration.update();
                    }).catch(function(err) {
                        console.error('ServiceWorker subscription failed: ', err);
                    });
                }

                // 2. Poll the server periodically to see if an administrator hit the reset button
                function pollCacheState() {
                    $.ajax({
                        url: restEndpoint,
                        method: 'GET',
                        dataType: 'json',
                        cache: false
                    }).done(function(response) {
                        if (response && response.version && response.version !== currentVersion) {
                            console.log('Cache deviation observed. Executing local browser purge sequence...');
                            nukeBrowserCacheAndReload();
                        }
                    });
                }

                function nukeBrowserCacheAndReload() {
                    // Purge standard storage engines
                    if (window.localStorage) {
                        localStorage.clear();
                    }
                    if (window.sessionStorage) {
                        sessionStorage.clear();
                    }

                    // Clear Cache Storage Web API definitions explicitly
                    if ('caches' in window) {
                        caches.keys().then(function(names) {
                            for (let name of names) caches.delete(name);
                        });
                    }

                    // Force unregister all active service worker bindings
                    if ('serviceWorker' in navigator) {
                        navigator.serviceWorker.getRegistrations().then(function(registrations) {
                            for (let registration of registrations) {
                                registration.unregister();
                            }
                        });
                    }

                    // Perform a destructive hard refresh overriding local client artifacts
                    setTimeout(function() {
                        window.location.reload(true);
                    }, 400);
                }

                // Run check every 30 seconds to catch tabs that are sitting open
                setInterval(pollCacheState, 30000);
            })(jQuery);
        </script>
        <?php
    }
}

new IBAW_CLS_Browser_Cache_Buster();