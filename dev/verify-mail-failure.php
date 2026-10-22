<?php
$ids = get_posts( array( 'post_type' => 'listnest_inquiry', 'meta_key' => '_listnest_email', 'meta_value' => 'failure-test@example.com', 'fields' => 'ids', 'numberposts' => 1, 'orderby' => 'ID', 'order' => 'DESC' ) );
if ( ! $ids || 'failed' !== get_post_meta( $ids[0], '_listnest_mail_status', true ) ) {
    throw new RuntimeException( 'Failed mail status not persisted.' );
}
WP_CLI::success( 'PASS: Failed notification status saved in inquiry metadata' );
