<?php
/**
 * Development only (wp-env): instead of sending, appends every wp_mail()
 * call to wp-content/listnest-mail.log. Not part of the plugin.
 *
 * Set the option listnest_dev_mail_fail to 1 to simulate a failed send.
 */
add_filter(
    'pre_wp_mail',
    function ( $return, $atts ) {
        $entry = array(
            'time'    => gmdate( 'c' ),
            'to'      => $atts['to'],
            'subject' => $atts['subject'],
            'headers' => $atts['headers'],
            'message' => $atts['message'],
        );
        file_put_contents( WP_CONTENT_DIR . '/listnest-mail.log', wp_json_encode( $entry ) . "\n", FILE_APPEND );
        return get_option( 'listnest_dev_mail_fail' ) ? false : true;
    },
    10,
    2
);

// Exercise a failed database insert without changing the database schema.
add_filter( 'wp_insert_post_empty_content', function ( $empty, $postarr ) {
    return get_option( 'listnest_dev_store_fail' ) && isset( $postarr['post_type'] ) && 'listnest_inquiry' === $postarr['post_type'] ? true : $empty;
}, 10, 2 );
