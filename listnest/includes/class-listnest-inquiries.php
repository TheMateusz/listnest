<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Inquiries sent through the built-in form, stored as listnest_inquiry
 * posts and shown under Lists → Inquiries.
 *
 * Every inquiry is saved before the mail is sent, so nothing is lost when
 * the site's mail delivery fails; the list screen shows whether the mail
 * went out. Personal data is covered by the WordPress personal data
 * exporter and eraser (Tools → Export / Erase Personal Data).
 */
class ListNest_Inquiries {

    const POST_TYPE = 'listnest_inquiry';

    /**
     * Meta keys of the stored fields.
     */
    const META_NAME        = '_listnest_name';
    const META_EMAIL       = '_listnest_email';
    const META_PHONE       = '_listnest_phone';
    const META_MESSAGE     = '_listnest_message';
    const META_LIST_KEY    = '_listnest_list_key';
    const META_PRODUCTS    = '_listnest_products';
    const META_CONSENT     = '_listnest_consent';
    const META_MAIL_STATUS = '_listnest_mail_status';

    /**
     * Registers hooks. Called once by ListNest_Plugin.
     */
    public function register_hooks() {
        add_action( 'init', array( $this, 'register_post_type' ) );
        add_action( 'add_meta_boxes_' . self::POST_TYPE, array( $this, 'add_meta_boxes' ) );
        add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( $this, 'columns' ) );
        add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
        add_filter( 'post_row_actions', array( $this, 'row_actions' ), 10, 2 );
        add_filter( 'bulk_actions-edit-' . self::POST_TYPE, array( $this, 'bulk_actions' ) );

        add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_exporter' ) );
        add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_eraser' ) );
        add_action( 'admin_init', array( $this, 'add_privacy_policy_content' ) );
    }

    /**
     * Capability required to view and delete inquiries (filterable).
     *
     * @return string
     */
    public static function get_capability() {
        /**
         * Filters the capability required to view and delete inquiries.
         *
         * @param string $capability Default 'manage_woocommerce'.
         */
        return (string) apply_filters( 'listnest_inquiries_capability', 'manage_woocommerce' );
    }

    public function register_post_type() {
        $cap = self::get_capability();

        register_post_type(
            self::POST_TYPE,
            array(
                'labels'              => array(
                    'name'               => __( 'Inquiries', 'listnest' ),
                    'singular_name'      => __( 'Inquiry', 'listnest' ),
                    'menu_name'          => __( 'Inquiries', 'listnest' ),
                    'all_items'          => __( 'Inquiries', 'listnest' ),
                    'edit_item'          => __( 'Inquiry', 'listnest' ),
                    'view_item'          => __( 'View inquiry', 'listnest' ),
                    'search_items'       => __( 'Search inquiries', 'listnest' ),
                    'not_found'          => __( 'No inquiries yet.', 'listnest' ),
                    'not_found_in_trash' => __( 'No inquiries found in Trash.', 'listnest' ),
                ),
                'public'              => false,
                'publicly_queryable'  => false,
                'exclude_from_search' => true,
                'show_ui'             => true,
                'show_in_menu'        => 'edit.php?post_type=listnest_list',
                'show_in_rest'        => false,
                'supports'            => false,
                'rewrite'             => false,
                'query_var'           => false,
                'map_meta_cap'        => true,
                'capabilities'        => array(
                    'create_posts'           => 'do_not_allow',
                    'edit_posts'             => $cap,
                    'edit_others_posts'      => $cap,
                    'edit_published_posts'   => $cap,
                    'edit_private_posts'     => $cap,
                    'publish_posts'          => $cap,
                    'read_private_posts'     => $cap,
                    'delete_posts'           => $cap,
                    'delete_others_posts'    => $cap,
                    'delete_published_posts' => $cap,
                    'delete_private_posts'   => $cap,
                ),
            )
        );
    }

    /**
     * Saves an inquiry.
     *
     * @param array $data name, email, phone, message, list_key, products (rows), consent.
     * @return int Post ID, 0 on failure.
     */
    public function create( array $data ) {
        $post_id = wp_insert_post(
            array(
                'post_type'   => self::POST_TYPE,
                'post_status' => 'publish',
                'post_title'  => sprintf(
                    /* translators: 1: customer name, 2: list name */
                    __( '%1$s – %2$s', 'listnest' ),
                    $data['name'],
                    ListNest_Mail::list_title( $data['list_key'] )
                ),
                'meta_input'  => array(
                    self::META_NAME     => $data['name'],
                    self::META_EMAIL    => $data['email'],
                    self::META_PHONE    => $data['phone'],
                    self::META_MESSAGE  => $data['message'],
                    self::META_LIST_KEY => $data['list_key'],
                    self::META_PRODUCTS => $data['products'],
                    self::META_CONSENT  => $data['consent'],
                ),
            ),
            true
        );

        return is_wp_error( $post_id ) ? 0 : (int) $post_id;
    }

    /**
     * Records whether the mail to the shop was sent.
     *
     * @param int  $post_id Inquiry ID.
     * @param bool $sent    Whether wp_mail() succeeded.
     */
    public function set_mail_status( $post_id, $sent ) {
        update_post_meta( $post_id, self::META_MAIL_STATUS, $sent ? 'sent' : 'failed' );
    }

    /* ------------------------------------------------------------------
     * Admin screens
     * ------------------------------------------------------------------ */

    public function add_meta_boxes() {
        remove_meta_box( 'submitdiv', self::POST_TYPE, 'side' );
        remove_meta_box( 'slugdiv', self::POST_TYPE, 'normal' );

        add_meta_box( 'listnest_inquiry_details', __( 'Inquiry', 'listnest' ), array( $this, 'render_details' ), self::POST_TYPE, 'normal', 'high' );
        add_meta_box( 'listnest_inquiry_actions', __( 'Actions', 'listnest' ), array( $this, 'render_actions' ), self::POST_TYPE, 'side', 'high' );
    }

    /**
     * Read-only view of an inquiry.
     *
     * @param WP_Post $post Inquiry.
     */
    public function render_details( $post ) {
        $name     = (string) get_post_meta( $post->ID, self::META_NAME, true );
        $email    = (string) get_post_meta( $post->ID, self::META_EMAIL, true );
        $phone    = (string) get_post_meta( $post->ID, self::META_PHONE, true );
        $message  = (string) get_post_meta( $post->ID, self::META_MESSAGE, true );
        $list_key = (string) get_post_meta( $post->ID, self::META_LIST_KEY, true );
        $consent  = (string) get_post_meta( $post->ID, self::META_CONSENT, true );
        $products = get_post_meta( $post->ID, self::META_PRODUCTS, true );
        $products = is_array( $products ) ? $products : array();
        ?>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php esc_html_e( 'Date', 'listnest' ); ?></th>
                <td><?php echo esc_html( get_the_date( '', $post ) . ' ' . get_the_time( '', $post ) ); ?></td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Name', 'listnest' ); ?></th>
                <td><?php echo esc_html( $name ); ?></td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Email', 'listnest' ); ?></th>
                <td><a href="<?php echo esc_url( 'mailto:' . $email ); ?>"><?php echo esc_html( $email ); ?></a></td>
            </tr>
            <?php if ( '' !== $phone ) : ?>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Phone', 'listnest' ); ?></th>
                    <td><a href="<?php echo esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a></td>
                </tr>
            <?php endif; ?>
            <tr>
                <th scope="row"><?php esc_html_e( 'Message', 'listnest' ); ?></th>
                <td><?php echo '' !== $message ? wp_kses_post( wpautop( esc_html( $message ) ) ) : '&mdash;'; ?></td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Products', 'listnest' ); ?></th>
                <td><?php echo ListNest_Mail::products_html( $list_key, $products ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by ListNest_Mail. ?></td>
            </tr>
            <?php if ( '' !== $consent ) : ?>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Consent given', 'listnest' ); ?></th>
                    <td><?php echo wp_kses( $consent, self::get_consent_allowed_html() ); ?></td>
                </tr>
            <?php endif; ?>
            <tr>
                <th scope="row"><?php esc_html_e( 'Email to the shop', 'listnest' ); ?></th>
                <td><?php $this->render_mail_status( $post->ID ); ?></td>
            </tr>
        </table>
        <?php
    }

    /**
     * Reply and delete actions.
     *
     * @param WP_Post $post Inquiry.
     */
    public function render_actions( $post ) {
        $email   = (string) get_post_meta( $post->ID, self::META_EMAIL, true );
        $subject = sprintf(
            /* translators: %s: list name */
            __( 'Re: your inquiry – %s', 'listnest' ),
            ListNest_Mail::list_title( (string) get_post_meta( $post->ID, self::META_LIST_KEY, true ) )
        );
        ?>
        <p>
            <a class="button button-primary" href="<?php echo esc_url( 'mailto:' . $email . '?subject=' . rawurlencode( $subject ) ); ?>"><?php esc_html_e( 'Reply by email', 'listnest' ); ?></a>
        </p>
        <?php if ( current_user_can( 'delete_post', $post->ID ) ) : ?>
            <p>
                <a class="submitdelete" style="color:#b32d2e;" href="<?php echo esc_url( get_delete_post_link( $post->ID ) ); ?>"><?php esc_html_e( 'Move to Trash', 'listnest' ); ?></a>
            </p>
        <?php endif; ?>
        <?php
    }

    /**
     * @param array $columns Columns.
     * @return array
     */
    public function columns( $columns ) {
        return array(
            'cb'                   => isset( $columns['cb'] ) ? $columns['cb'] : '<input type="checkbox" />',
            'title'                => __( 'Inquiry', 'listnest' ),
            'listnest_email'       => __( 'Email', 'listnest' ),
            'listnest_list'        => __( 'List', 'listnest' ),
            'listnest_products'    => __( 'Products', 'listnest' ),
            'listnest_mail_status' => __( 'Email to the shop', 'listnest' ),
            'date'                 => __( 'Date', 'listnest' ),
        );
    }

    /**
     * @param string $column  Column name.
     * @param int    $post_id Inquiry ID.
     */
    public function render_column( $column, $post_id ) {
        switch ( $column ) {
            case 'listnest_email':
                $email = (string) get_post_meta( $post_id, self::META_EMAIL, true );
                echo '<a href="' . esc_url( 'mailto:' . $email ) . '">' . esc_html( $email ) . '</a>';
                break;

            case 'listnest_list':
                echo esc_html( ListNest_Mail::list_title( (string) get_post_meta( $post_id, self::META_LIST_KEY, true ) ) );
                break;

            case 'listnest_products':
                $products = get_post_meta( $post_id, self::META_PRODUCTS, true );
                echo esc_html( number_format_i18n( is_array( $products ) ? count( $products ) : 0 ) );
                break;

            case 'listnest_mail_status':
                $this->render_mail_status( $post_id );
                break;
        }
    }

    /**
     * @param int $post_id Inquiry ID.
     */
    private function render_mail_status( $post_id ) {
        $status = (string) get_post_meta( $post_id, self::META_MAIL_STATUS, true );
        if ( 'sent' === $status ) {
            esc_html_e( 'Sent', 'listnest' );
        } elseif ( 'failed' === $status ) {
            echo '<strong style="color:#b32d2e;">' . esc_html__( 'Not sent – check your site\'s email delivery', 'listnest' ) . '</strong>';
        } else {
            echo '&mdash;';
        }
    }

    /**
     * Inquiries are read-only: no "Edit" or "Quick Edit" links.
     *
     * @param array   $actions Row actions.
     * @param WP_Post $post    Post.
     * @return array
     */
    public function row_actions( $actions, $post ) {
        if ( self::POST_TYPE !== $post->post_type ) {
            return $actions;
        }
        unset( $actions['inline hide-if-no-js'] );
        if ( isset( $actions['edit'] ) ) {
            $actions['edit'] = '<a href="' . esc_url( get_edit_post_link( $post->ID ) ) . '">' . esc_html__( 'View', 'listnest' ) . '</a>';
        }
        return $actions;
    }

    /**
     * @param array $actions Bulk actions.
     * @return array
     */
    public function bulk_actions( $actions ) {
        unset( $actions['edit'] );
        return $actions;
    }

    /**
     * HTML allowed in the consent text.
     *
     * @return array
     */
    public static function get_consent_allowed_html() {
        return array(
            'a'      => array(
                'href'   => true,
                'target' => true,
                'rel'    => true,
            ),
            'strong' => array(),
            'em'     => array(),
        );
    }

    /* ------------------------------------------------------------------
     * Privacy (personal data export / erasure)
     * ------------------------------------------------------------------ */

    /**
     * @param array $exporters Exporters.
     * @return array
     */
    public function register_exporter( $exporters ) {
        $exporters['listnest-inquiries'] = array(
            'exporter_friendly_name' => __( 'ListNest inquiries', 'listnest' ),
            'callback'               => array( $this, 'export_personal_data' ),
        );
        return $exporters;
    }

    /**
     * @param array $erasers Erasers.
     * @return array
     */
    public function register_eraser( $erasers ) {
        $erasers['listnest-inquiries'] = array(
            'eraser_friendly_name' => __( 'ListNest inquiries', 'listnest' ),
            'callback'             => array( $this, 'erase_personal_data' ),
        );
        return $erasers;
    }

    /**
     * Inquiries sent from an email address, one page at a time.
     *
     * @param string $email Email address.
     * @param int    $page  Page (1-based).
     * @return int[]
     */
    private function get_ids_by_email( $email, $page ) {
        return get_posts(
            array(
                'post_type'      => self::POST_TYPE,
                'post_status'    => 'any',
                'posts_per_page' => 50,
                'paged'          => max( 1, (int) $page ),
                'fields'         => 'ids',
                'no_found_rows'  => true,
                'meta_key'       => self::META_EMAIL, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Privacy tools only.
                'meta_value'     => $email, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Privacy tools only.
            )
        );
    }

    /**
     * @param string $email Email address.
     * @param int    $page  Page.
     * @return array
     */
    public function export_personal_data( $email, $page = 1 ) {
        $ids  = $this->get_ids_by_email( $email, $page );
        $data = array();

        foreach ( $ids as $id ) {
            $products = get_post_meta( $id, self::META_PRODUCTS, true );
            $list_key = (string) get_post_meta( $id, self::META_LIST_KEY, true );

            $data[] = array(
                'group_id'    => 'listnest-inquiries',
                'group_label' => __( 'Inquiries', 'listnest' ),
                'item_id'     => 'listnest-inquiry-' . $id,
                'data'        => array(
                    array(
                        'name'  => __( 'Date', 'listnest' ),
                        'value' => get_the_date( 'Y-m-d H:i', $id ),
                    ),
                    array(
                        'name'  => __( 'Name', 'listnest' ),
                        'value' => (string) get_post_meta( $id, self::META_NAME, true ),
                    ),
                    array(
                        'name'  => __( 'Email', 'listnest' ),
                        'value' => (string) get_post_meta( $id, self::META_EMAIL, true ),
                    ),
                    array(
                        'name'  => __( 'Phone', 'listnest' ),
                        'value' => (string) get_post_meta( $id, self::META_PHONE, true ),
                    ),
                    array(
                        'name'  => __( 'Message', 'listnest' ),
                        'value' => (string) get_post_meta( $id, self::META_MESSAGE, true ),
                    ),
                    array(
                        'name'  => __( 'Products', 'listnest' ),
                        'value' => ListNest_Mail::products_text( $list_key, is_array( $products ) ? $products : array() ),
                    ),
                ),
            );
        }

        return array(
            'data' => $data,
            'done' => count( $ids ) < 50,
        );
    }

    /**
     * Deletes the inquiries sent from an email address.
     *
     * @param string $email Email address.
     * @param int    $page  Page (ignored: deleted posts drop out of the query).
     * @return array
     */
    public function erase_personal_data( $email, $page = 1 ) {
        $ids     = $this->get_ids_by_email( $email, 1 );
        $removed = false;

        foreach ( $ids as $id ) {
            if ( wp_delete_post( $id, true ) ) {
                $removed = true;
            }
        }

        return array(
            'items_removed'  => $removed,
            'items_retained' => false,
            'messages'       => array(),
            'done'           => count( $ids ) < 50,
        );
    }

    /**
     * Suggested text for the site's privacy policy.
     */
    public function add_privacy_policy_content() {
        if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
            return;
        }

        $content = '<p>' . esc_html__( 'When you send an inquiry with a product list, we store your name, email address, phone number (if given), message, the products on your list and your consent in our website database, and send them to us by email. We use this data to answer your inquiry. You can ask us to export or erase it.', 'listnest' ) . '</p>';

        wp_add_privacy_policy_content( 'ListNest', wp_kses_post( $content ) );
    }
}
