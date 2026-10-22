<?php
/** Setup help and data lifecycle settings. @package ListNest */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ListNest_Settings {
    public function register_hooks() {
        add_action( 'admin_menu', array( $this, 'menu' ) );
        add_action( 'admin_post_listnest_save_settings', array( $this, 'save' ) );
        add_action( 'admin_init', array( $this, 'schedule_cleanup' ) );
        add_action( 'listnest_cleanup_inquiries', array( $this, 'cleanup' ) );
        add_filter( 'plugin_action_links_' . plugin_basename( LISTNEST_FILE ), array( $this, 'links' ) );
    }

    public function links( $links ) {
        array_unshift( $links, '<a href="' . esc_url( admin_url( 'edit.php?post_type=listnest_list&page=listnest-settings' ) ) . '">' . esc_html__( 'Setup and privacy', 'listnest' ) . '</a>' );
        return $links;
    }

    public function menu() {
        add_submenu_page( 'edit.php?post_type=listnest_list', __( 'Setup and privacy', 'listnest' ), __( 'Setup and privacy', 'listnest' ), 'manage_woocommerce', 'listnest-settings', array( $this, 'render' ) );
    }

    public function render() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'ListNest - setup and privacy', 'listnest' ); ?></h1>
            <ol>
                <li><?php esc_html_e( 'Create and publish a list under Lists → Add New List.', 'listnest' ); ?></li>
                <li><?php esc_html_e( 'Enable the product button and select the built-in inquiry form in the list options.', 'listnest' ); ?></li>
                <li><?php esc_html_e( 'Create a page with the shortcode below. Select that page in the button or popup settings.', 'listnest' ); ?></li>
                <li><?php esc_html_e( 'Exclude list and form pages from full-page cache. Send a test inquiry and check Lists → Inquiries.', 'listnest' ); ?></li>
            </ol>
            <p><?php esc_html_e( 'Lists use the WooCommerce session and expire with it. They are not shared between devices.', 'listnest' ); ?></p>
            <table class="widefat striped">
                <thead><tr><th><?php esc_html_e( 'List', 'listnest' ); ?></th><th><?php esc_html_e( 'Shortcode', 'listnest' ); ?></th></tr></thead>
                <tbody>
                <?php foreach ( get_posts( array( 'post_type' => 'listnest_list', 'post_status' => 'publish', 'numberposts' => -1 ) ) as $list ) : ?>
                    <tr><td><a href="<?php echo esc_url( get_edit_post_link( $list->ID ) ); ?>"><?php echo esc_html( $list->post_title ); ?></a></td><td><code><?php echo esc_html( '[listnest list_key="' . sanitize_key( $list->post_name ) . '"]' ); ?></code></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <h2><?php esc_html_e( 'Stored inquiries', 'listnest' ); ?></h2>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="listnest_save_settings" />
                <?php wp_nonce_field( 'listnest_save_settings' ); ?>
                <p><label for="listnest-retention"><?php esc_html_e( 'Automatically delete inquiries older than this many days (0 = keep until manually deleted):', 'listnest' ); ?></label></p>
                <input id="listnest-retention" name="listnest_retention_days" type="number" min="0" max="3650" value="<?php echo esc_attr( get_option( 'listnest_retention_days', 0 ) ); ?>" />
                <p><label><input type="checkbox" name="listnest_remove_data" value="1" <?php checked( get_option( 'listnest_remove_data', false ) ); ?> /> <?php esc_html_e( 'Permanently delete all lists and inquiries when this plugin is uninstalled.', 'listnest' ); ?></label></p>
                <p class="description"><?php esc_html_e( 'Data is kept on uninstall unless you enable deletion here. Automatic cleanup runs daily through WP-Cron, in batches of 100 inquiries.', 'listnest' ); ?></p>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function save() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'You are not allowed to change these settings.', 'listnest' ), '', array( 'response' => 403 ) );
        }
        check_admin_referer( 'listnest_save_settings' );
        $days = isset( $_POST['listnest_retention_days'] ) && is_string( $_POST['listnest_retention_days'] ) ? min( 3650, absint( wp_unslash( $_POST['listnest_retention_days'] ) ) ) : 0;
        update_option( 'listnest_retention_days', $days, false );
        update_option( 'listnest_remove_data', isset( $_POST['listnest_remove_data'] ) && '1' === $_POST['listnest_remove_data'], false );
        $this->schedule_cleanup();
        wp_safe_redirect( admin_url( 'edit.php?post_type=listnest_list&page=listnest-settings' ) );
        exit;
    }

    public function schedule_cleanup() {
        if ( get_option( 'listnest_retention_days', 0 ) && ! wp_next_scheduled( 'listnest_cleanup_inquiries' ) ) {
            wp_schedule_event( time() + DAY_IN_SECONDS, 'daily', 'listnest_cleanup_inquiries' );
        } elseif ( ! get_option( 'listnest_retention_days', 0 ) ) {
            wp_clear_scheduled_hook( 'listnest_cleanup_inquiries' );
        }
    }

    public function cleanup() {
        $days = absint( get_option( 'listnest_retention_days', 0 ) );
        if ( ! $days ) {
            return;
        }
        $ids = get_posts( array(
            'post_type' => 'listnest_inquiry', 'post_status' => array( 'publish', 'private', 'draft', 'pending', 'trash' ), 'fields' => 'ids', 'posts_per_page' => 100,
            'date_query' => array( array( 'column' => 'post_date_gmt', 'before' => gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS ) ) ),
        ) );
        foreach ( $ids as $id ) {
            wp_delete_post( $id, true );
        }
    }
}
