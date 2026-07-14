<?php
/**
 * Plugin Name: IBAW- Lightspeed Legacy Force Sync PRO
 * Plugin URI:  https://ericksonvilleta.com
 * Description: Resets internal LS flags and forces a clean Legacy API broadcast.
 * Version:     2.0
 * Author:      Erick Villeta
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IBAW_LS_Force_Sync_Pro {

    public function __construct() {
        // We use a later hook to ensure all ZIP/Delivery meta is 100% saved
        add_action( 'woocommerce_order_status_processing', [ $this, 'force_ls_broadcast' ], 999 );
    }

    public function force_ls_broadcast( $order_id ) {
        $order = wc_get_order( $order_id );
        
        // 1. CLEAR INTERNAL LIGHTSPEED FLAGS
        // The plugin often sets these to 'failed' or 'ignored' on mobile. We nuke them.
        delete_post_meta( $order_id, '_wclsi_synced' );
        delete_post_meta( $order_id, '_wclsi_sync_error' );
        delete_post_meta( $order_id, '_litespeed_import_id' );

        // 2. FORCE RELOAD ORDER DATA
        // Ensure WC isn't serving a cached object
        $order->save(); 

        // 3. FLUSH LITESPEED CACHE (CRITICAL)
        // If the Legacy API endpoint is cached, LS sees old data.
        if ( class_exists( 'LSCWP\PHP\Cache' ) ) {
            \LSCWP\PHP\Cache::cls();
            error_log("IBAW: LiteSpeed Cache Flushed for Order #$order_id");
        }

        /**
         * 4. TRIGGER MULTIPLE HOOKS
         * Some versions of the LS connector listen for different events.
         * We fire the three main ones to be safe.
         */
        do_action( 'woocommerce_new_order', $order_id, $order );
        do_action( 'woocommerce_checkout_order_processed', $order_id, [], $order );
        do_action( 'woocommerce_payment_complete', $order_id );

        // 5. MANUAL PING (Optional but recommended)
        // If you know the LS Webhook URL, you can wp_remote_get it here.
        
        $order->add_order_note( 'IBAW: LS Flags Resetted and Hooks Re-broadcasted.' );
        error_log( "IBAW: Hard Force Sync completed for Order #$order_id" );
    }
}

new IBAW_LS_Force_Sync_Pro();