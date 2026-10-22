<?php
/** Exercise uninstall without deleting the mapped plugin source directory. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || 'ListNest Test' !== get_option( 'blogname' ) ) {
    throw new RuntimeException( 'Test environment required.' );
}
$list_id = wp_insert_post( array( 'post_type' => 'listnest_list', 'post_status' => 'publish', 'post_title' => 'Uninstall fixture' ) );
$inquiry_id = wp_insert_post( array( 'post_type' => 'listnest_inquiry', 'post_status' => 'publish', 'post_title' => 'Uninstall fixture' ) );
delete_option( 'listnest_remove_data' );
define( 'WP_UNINSTALL_PLUGIN', 'listnest/listnest.php' );
require LISTNEST_PATH . 'uninstall.php';
if ( ! get_post( $list_id ) || ! get_post( $inquiry_id ) ) {
    throw new RuntimeException( 'Default uninstall must preserve data.' );
}
WP_CLI::log( 'PASS: Uninstall preserves data by default' );
update_option( 'listnest_remove_data', true );
update_option( 'listnest_retention_days', 30 );
listnest_uninstall_site();
if ( get_post( $list_id ) || get_post( $inquiry_id ) || get_option( 'listnest_remove_data' ) || get_option( 'listnest_retention_days' ) ) {
    throw new RuntimeException( 'Opt-in uninstall must remove plugin data and settings.' );
}
WP_CLI::success( 'PASS: Opt-in uninstall removes data and settings' );
