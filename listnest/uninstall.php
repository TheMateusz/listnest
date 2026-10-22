<?php
/**
 * Uninstall routine: removes scheduled jobs and plugin settings.
 * Lists/inquiries are preserved unless deletion was explicitly enabled.
 *
 * Visitors' list contents live in the WooCommerce session, which expires
 * on its own; the WooCommerce session
 * table is intentionally left untouched. Rate-limit transients of the
 * inquiry form expire on their own.
 *
 * @package ListNest
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * Force-deletes every listnest_list and listnest_inquiry post (any status)
 * on the current site.
 * wp_delete_post() also removes the post meta.
 */
function listnest_uninstall_site() {
    global $wpdb;

    wp_clear_scheduled_hook( 'listnest_cleanup_inquiries' );
    wp_unschedule_hook( 'listnest_release_submission_lock' );
    $locks = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( 'listnest_submit_lock_' ) . '%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-off uninstall cleanup.
    foreach ( $locks as $lock ) {
        delete_option( $lock );
    }
    $remove = get_option( 'listnest_remove_data', false );
    delete_option( 'listnest_remove_data' );
    delete_option( 'listnest_retention_days' );
    if ( ! $remove ) {
        return;
    }

    // The post types are not registered during uninstall, so query the IDs directly.
    $ids = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-off uninstall query.
        $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type IN ( %s, %s )", 'listnest_list', 'listnest_inquiry' )
    );

    foreach ( (array) $ids as $id ) {
        wp_delete_post( (int) $id, true );
    }
}

if ( is_multisite() ) {
    $listnest_site_ids = get_sites(
        array(
            'fields' => 'ids',
            'number' => 0,
        )
    );
    foreach ( $listnest_site_ids as $listnest_site_id ) {
        switch_to_blog( (int) $listnest_site_id );
        listnest_uninstall_site();
        restore_current_blog();
    }
} else {
    listnest_uninstall_site();
}
