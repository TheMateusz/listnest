<?php
/** Extended integration checks on disposable seeded data only. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || 'ListNest Test' !== get_option( 'blogname' ) ) {
    throw new RuntimeException( 'Test environment required.' );
}
$checks = 0;
$assert = function ( $value, $message ) use ( &$checks ) {
    if ( ! $value ) {
        throw new RuntimeException( $message );
    }
    ++$checks;
    WP_CLI::log( 'PASS: ' . $message );
};
$plugin = ListNest_Plugin::instance();
$inquiries = $plugin->get_component( 'inquiries' );
$settings = $plugin->get_component( 'settings' );
$original_user = get_current_user_id();
$retention = get_option( 'listnest_retention_days', 0 );
$created = array();
$product = new WC_Product_Simple();
$product->set_name( 'Private regression product' );
$product->set_status( 'private' );
$product_id = $product->save();
try {
    $item = array( (string) $product_id => array( 'product_id' => $product_id, 'quantity' => 1 ) );
    $rows = ListNest_Item::prepare_rows( $item, true );
    $assert( null === $rows[0]['product'] && false === strpos( $rows[0]['name'], 'Private regression' ), 'Private product details are hidden' );
    $assert( array() === ListNest_Item::prepare_rows( $item ), 'Private product excluded from default mail rows' );
    $product->set_status( 'publish' );
    $product->save();
    $assert( count( ListNest_Item::prepare_rows( $item ) ) === 1, 'Published product remains available' );

    $list_id = ListNest_CPT::get_published_list_id( 'probki' );
    foreach ( array( 'subscriber' => false, 'editor' => false, 'shop_manager' => true, 'administrator' => true ) as $role => $allowed ) {
        $login = 'listnest-test-' . $role;
        $user = get_user_by( 'login', $login );
        if ( ! $user ) {
            $user_id = wp_insert_user( array( 'user_login' => $login, 'user_pass' => wp_generate_password(), 'role' => $role, 'user_email' => $login . '@example.com' ) );
        } else {
            $user_id = $user->ID;
        }
        wp_set_current_user( $user_id );
        $assert( $allowed === current_user_can( 'edit_post', $list_id ), 'List access for ' . $role );
        $assert( $allowed === current_user_can( ListNest_Inquiries::get_capability() ), 'Inquiry access for ' . $role );
    }
    wp_set_current_user( $original_user );

    $data = array( 'name' => 'Privacy test', 'email' => 'listnest-privacy-test@example.com', 'phone' => '', 'message' => 'Test', 'products' => array(), 'list_key' => 'probki', 'consent' => 'Consent' );
    // Ensure rerunning a previously interrupted check cannot change its expected counts.
    do { $erased = $inquiries->erase_personal_data( $data['email'] ); } while ( ! $erased['done'] );
    for ( $i = 0; $i < 51; ++$i ) {
        $created[] = $inquiries->create( $data );
    }
    $page1 = $inquiries->export_personal_data( $data['email'], 1 );
    $page2 = $inquiries->export_personal_data( $data['email'], 2 );
    $assert( 50 === count( $page1['data'] ) && ! $page1['done'], 'Privacy export first page' );
    $assert( 1 === count( $page2['data'] ) && $page2['done'], 'Privacy export final page' );
    $assert( ! $inquiries->erase_personal_data( $data['email'] )['done'], 'Privacy erase batches the first 50' );
    $assert( $inquiries->erase_personal_data( $data['email'], 2 )['done'], 'Privacy erase completes the remaining data' );
    $assert( empty( $inquiries->export_personal_data( $data['email'] )['data'] ), 'Personal data fully erased' );

    $old = $inquiries->create( $data );
    $new = $inquiries->create( $data );
    $created[] = $old;
    $created[] = $new;
    $escaped = $data;
    $escaped['message'] = 'Path C:\\samples\\new and "quotes"';
    $escaped_id = $inquiries->create( $escaped );
    $created[] = $escaped_id;
    $assert( $escaped['message'] === get_post_meta( $escaped_id, ListNest_Inquiries::META_MESSAGE, true ), 'Inquiry preserves backslashes and quotes' );
    wp_update_post( array( 'ID' => $old, 'post_date' => gmdate( 'Y-m-d H:i:s', time() - 60 * DAY_IN_SECONDS ), 'post_date_gmt' => gmdate( 'Y-m-d H:i:s', time() - 60 * DAY_IN_SECONDS ) ) );
    update_option( 'listnest_retention_days', 30 );
    $settings->cleanup();
    $assert( null === get_post( $old ) && null !== get_post( $new ), 'Retention deletes old inquiries and keeps new ones' );
    update_option( 'listnest_retention_days', 0 );
    $settings->schedule_cleanup();
    $assert( false === wp_next_scheduled( 'listnest_cleanup_inquiries' ), 'Retention disabled clears the scheduled job' );

    $storage = $plugin->get_component( 'session' );
    $storage->load_session();
    $method = new ReflectionMethod( $plugin->get_component( 'form' ), 'acquire_submission_lock' );
    $method->setAccessible( true );
    $key = $method->invoke( $plugin->get_component( 'form' ), 'regression-' . wp_generate_uuid4() );
    $assert( is_string( $key ), 'Submission lock acquired' );
    $suffix = 'regression-' . wp_generate_uuid4();
    $other = $method->invoke( $plugin->get_component( 'form' ), $suffix );
    $assert( false === $method->invoke( $plugin->get_component( 'form' ), $suffix ), 'Duplicate submission blocked atomically' );
    delete_option( $key );
    update_option( $other, time() - 1 );
    $assert( $other === $method->invoke( $plugin->get_component( 'form' ), $suffix ), 'Expired submission lock can be reacquired' );
    delete_option( $other );
} finally {
    wp_set_current_user( $original_user );
    update_option( 'listnest_retention_days', $retention );
    $settings->schedule_cleanup();
    foreach ( $created as $id ) {
        wp_delete_post( $id, true );
    }
    wp_delete_post( $product_id, true );
}
WP_CLI::success( $checks . ' release integration checks passed.' );
