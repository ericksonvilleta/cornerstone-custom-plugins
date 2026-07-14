<?php
/**
 * Plugin Name: IBAW- WooCommerce Bulk Status Changer
 * Plugin URI:  https://ericksonvilleta.com
 * Description: Adds bulk actions for status and a dynamic category toggle tray in Bulk Edit.
 * Version:     1.0
 * Author:      Erick Villeta
 * Author URI:  https://ericksonvilleta.com
 * License:     GPL2
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 1. Register Bulk Actions for Status
 */
add_filter( 'bulk_actions-edit-product', 'ibaw_register_status_actions' );
function ibaw_register_status_actions( $bulk_actions ) {
    $bulk_actions['ibaw_make_published'] = __( 'Set to Published', 'woocommerce' );
    $bulk_actions['ibaw_make_draft']     = __( 'Set to Draft', 'woocommerce' );
    $bulk_actions['ibaw_make_private']   = __( 'Set to Private (Unpublish)', 'woocommerce' );
    return $bulk_actions;
}

/**
 * 2. Add the Category Checklist UI to the Bulk Edit Tray
 * This hook injects your custom HTML into the "Bulk Edit" area.
 */
add_action( 'woocommerce_product_bulk_edit_start', 'ibaw_add_category_checklist_to_bulk_edit' );
function ibaw_add_category_checklist_to_bulk_edit() {
    ?>
    <div class="inline-edit-group">
        <label class="alignleft">
            <span class="title"><?php _e( 'Product Categories (Bulk)', 'woocommerce' ); ?></span>
            <ul class="cat-checklist product_cat-checklist" style="background: #fff; border: 1px solid #ddd; padding: 10px; max-height: 150px; overflow-y: auto; margin-top: 5px;">
                <?php 
                // This renders the actual list of categories from your store
                wp_terms_checklist( 0, array( 'taxonomy' => 'product_cat' ) ); 
                ?>
            </ul>
            <p class="description"><?php _e( 'Check to add, uncheck to remove from selected products.', 'woocommerce' ); ?></p>
        </label>
    </div>
    <?php
}

/**
 * 3. Save the Status and Category Changes
 */
add_action( 'woocommerce_product_bulk_edit_save', 'ibaw_save_bulk_edit_custom_data', 10, 1 );
function ibaw_save_bulk_edit_custom_data( $product ) {
    $product_id = $product->get_id();

    // Handle Category Toggles
    if ( isset( $_REQUEST['tax_input']['product_cat'] ) ) {
        $category_ids = array_map( 'intval', $_REQUEST['tax_input']['product_cat'] );
        $product->set_category_ids( $category_ids );
    }

    // Handle our Custom Status Actions (if they were triggered)
    if ( isset( $_REQUEST['action'] ) || isset( $_REQUEST['action2'] ) ) {
        $action = ( $_REQUEST['action'] != -1 ) ? $_REQUEST['action'] : $_REQUEST['action2'];
        
        switch ( $action ) {
            case 'ibaw_make_published': $product->set_status( 'publish' ); break;
            case 'ibaw_make_draft':     $product->set_status( 'draft' );   break;
            case 'ibaw_make_private':   $product->set_status( 'private' ); break;
        }
    }

    $product->save();
}

/**
 * 4. Bulk Action Handler (For the Status Dropdown specifically)
 */
add_filter( 'handle_bulk_actions-edit-product', 'ibaw_handle_status_actions', 10, 3 );
function ibaw_handle_status_actions( $redirect_to, $action_name, $product_ids ) {
    if ( ! in_array( $action_name, ['ibaw_make_published', 'ibaw_make_draft', 'ibaw_make_private'] ) ) {
        return $redirect_to;
    }

    $count = 0;
    foreach ( $product_ids as $product_id ) {
        $product = wc_get_product( $product_id );
        if ( $product ) {
            $status = str_replace( 'ibaw_make_', '', $action_name );
            if ( $status === 'private' ) { $status = 'private'; }
            if ( $status === 'published' ) { $status = 'publish'; }
            
            $product->set_status( $status );
            $product->save();
            $count++;
        }
    }

    return add_query_arg( array( 'ibaw_done' => $count ), $redirect_to );
}

/**
 * 5. Success Notice
 */
add_action( 'admin_notices', 'ibaw_bulk_notice' );
function ibaw_bulk_notice() {
    if ( ! empty( $_REQUEST['ibaw_done'] ) ) {
        echo '<div class="updated notice is-dismissible"><p>Bulk update completed.</p></div>';
    }
}