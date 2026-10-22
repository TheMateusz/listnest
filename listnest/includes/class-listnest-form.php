<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Built-in inquiry form - an alternative to Contact Form 7.
 *
 * Shown automatically below the list ([listnest] shortcode) when the list's
 * "Inquiry form" setting is "Built-in form", or anywhere with
 * [listnest_form list="..."].
 *
 * The form is a plain POST form (works without JavaScript). On success the
 * inquiry is stored (Lists → Inquiries), mailed to the shop with Reply-To set
 * to the customer, optionally copied to the customer, the list is cleared
 * and the visitor is redirected back (Post/Redirect/Get) to a thank-you
 * message. Spam protection: nonce, honeypot field and a per-IP rate limit.
 */
class ListNest_Form {

    const NONCE_ACTION   = 'listnest_inquiry_form';
    const NONCE_FIELD    = 'listnest_form_nonce';
    const LIST_FIELD     = 'listnest_form_list';
    const HONEYPOT_FIELD = 'listnest_website';
    const SENT_ARG       = 'listnest_sent';

    /**
     * Field length limits (characters).
     */
    const MAX_NAME    = 100;
    const MAX_PHONE   = 30;
    const MAX_MESSAGE = 5000;

    /**
     * List storage.
     *
     * @var ListNest_Session
     */
    private $storage;

    /**
     * Front-end component (assets).
     *
     * @var ListNest_Frontend
     */
    private $frontend;

    /**
     * Inquiry storage.
     *
     * @var ListNest_Inquiries
     */
    private $inquiries;

    /**
     * List key of the form submitted on this request ('' when none).
     *
     * @var string
     */
    private $submitted_list = '';

    /**
     * Submitted values (re-displayed after a validation error).
     *
     * @var array
     */
    private $values = array();

    /**
     * Validation errors: field name => message; 'form' for general errors.
     *
     * @var array
     */
    private $errors = array();

    /**
     * @param ListNest_Session   $storage   List storage.
     * @param ListNest_Frontend  $frontend  Front-end component.
     * @param ListNest_Inquiries $inquiries Inquiry storage.
     */
    public function __construct( ListNest_Session $storage, ListNest_Frontend $frontend, ListNest_Inquiries $inquiries ) {
        $this->storage   = $storage;
        $this->frontend  = $frontend;
        $this->inquiries = $inquiries;
    }

    /**
     * Registers hooks. Called once by ListNest_Plugin.
     */
    public function register_hooks() {
        add_shortcode( 'listnest_form', array( $this, 'render_shortcode' ) );
        add_action( 'wp', array( $this, 'handle_submit' ) );
        add_action( 'listnest_release_submission_lock', array( $this, 'release_submission_lock' ) );
    }

    /**
     * Form settings of a list.
     *
     * @param string $list_key List key.
     * @return array|null type ('', 'builtin', 'cf7'), recipients, subject, copy, consent; null for unknown lists.
     */
    public static function get_settings( $list_key ) {
        $list_id = ListNest_CPT::get_published_list_id( $list_key );
        if ( ! $list_id ) {
            return null;
        }

        $type = (string) get_post_meta( $list_id, '_listnest_list_form', true );

        return array(
            'type'       => in_array( $type, array( 'builtin', 'cf7' ), true ) ? $type : '',
            'recipients' => (string) get_post_meta( $list_id, '_listnest_list_form_recipient', true ),
            'subject'    => (string) get_post_meta( $list_id, '_listnest_list_form_subject', true ),
            'copy'       => '1' === (string) get_post_meta( $list_id, '_listnest_list_form_copy', true ),
            'consent'    => (string) get_post_meta( $list_id, '_listnest_list_form_consent', true ),
        );
    }

    /**
     * Whether the list shows the built-in form below the list table.
     *
     * @param string $list_key List key.
     * @return bool
     */
    public function is_auto_form( $list_key ) {
        $settings = self::get_settings( $list_key );
        return $settings && 'builtin' === $settings['type'];
    }

    /**
     * Whether the visitor has just sent the form for this list.
     *
     * @param string $list_key List key.
     * @return bool
     */
    public function is_sent( $list_key ) {
        // Display-only flag set by our own redirect.
        return isset( $_GET[ self::SENT_ARG ] ) && is_string( $_GET[ self::SENT_ARG ] ) && sanitize_key( wp_unslash( $_GET[ self::SENT_ARG ] ) ) === sanitize_key( $list_key ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    }

    /* ------------------------------------------------------------------
     * Rendering
     * ------------------------------------------------------------------ */

    /**
     * Shortcode: [listnest_form list="samples"]
     *
     * @param array|string $atts Shortcode attributes.
     * @return string
     */
    public function render_shortcode( $atts ) {
        $atts = shortcode_atts( array( 'list' => '' ), $atts, 'listnest_form' );
        return $this->render( sanitize_key( $atts['list'] ), true );
    }

    /**
     * Form markup (templates/inquiry-form.php, overridable in
     * yourtheme/listnest/inquiry-form.php or {list_key}-inquiry-form.php).
     *
     * @param string $list_key           List key.
     * @param bool   $show_empty_notice  Show "your list is empty" instead of nothing when there is nothing to send.
     * @return string
     */
    public function render( $list_key, $show_empty_notice = false ) {
        $settings = self::get_settings( $list_key );
        if ( null === $settings ) {
            if ( ! current_user_can( 'edit_posts' ) ) {
                return '';
            }
            return '<p class="listnest-notice listnest-notice--error"><strong>' . esc_html(
                sprintf(
                    /* translators: %s: shortcode example */
                    __( 'ListNest: the form needs the key of a published list, e.g. %s.', 'listnest' ),
                    '[listnest_form list="my-list"]'
                )
            ) . '</strong></p>';
        }

        // Personal (session based) content: keep the page out of full-page caches.
        if ( ! defined( 'DONOTCACHEPAGE' ) ) {
            define( 'DONOTCACHEPAGE', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Standard caching plugin constant.
        }

        $is_current = ( $this->submitted_list === $list_key );
        $sent       = $this->is_sent( $list_key );
        $has_items  = ! empty( $this->storage->get_list( $list_key ) );

        if ( ! $sent && ! $has_items && ! $is_current ) {
            if ( ! $show_empty_notice ) {
                return '';
            }
            $this->frontend->enqueue_style();
            return '<div class="listnest listnest-form-wrap"><p class="listnest-notice">' . esc_html__( 'Your list is empty. Add products to the list to send an inquiry.', 'listnest' ) . '</p></div>';
        }

        $template = ListNest_Template::locate( array( $list_key . '-inquiry-form.php', 'inquiry-form.php' ), $list_key );
        if ( '' === $template ) {
            return '';
        }

        $this->frontend->enqueue_style();

        $defaults = array(
            'name'    => '',
            'email'   => '',
            'phone'   => '',
            'message' => '',
            'consent' => false,
        );

        // Variables available in the template (documented in templates/inquiry-form.php).
        $form_id      = 'listnest-form-' . $list_key;
        $values       = $is_current ? wp_parse_args( $this->values, $defaults ) : $defaults;
        $errors       = $is_current ? $this->errors : array();
        $show_form    = ! $sent && ( $has_items || $is_current );
        $consent_text = $this->get_consent_text( $settings );
        // The anchor brings the visitor back to the form after a validation error.
        $action_url   = remove_query_arg( array( self::SENT_ARG, 'listnest_list_updated' ) ) . '#' . $form_id;
        $list_field   = self::LIST_FIELD;
        $nonce_action = self::NONCE_ACTION;
        $nonce_field  = self::NONCE_FIELD;
        $honeypot     = self::HONEYPOT_FIELD;

        /**
         * Filters the message shown after the inquiry has been sent.
         *
         * @param string $message  Message (plain text).
         * @param string $list_key List key.
         */
        $success_message = (string) apply_filters( 'listnest_form_success_message', __( 'Thank you! Your inquiry has been received. We will get back to you as soon as possible.', 'listnest' ), $list_key );

        ob_start();
        include $template;
        return ob_get_clean();
    }

    /**
     * Consent checkbox label (HTML limited to links and emphasis); '' disables the checkbox.
     *
     * @param array $settings List form settings.
     * @return string
     */
    private function get_consent_text( array $settings ) {
        $text = $settings['consent'];

        if ( '' === trim( $text ) ) {
            $text        = esc_html__( 'I agree to the processing of my personal data in order to handle this inquiry.', 'listnest' );
            $privacy_url = get_privacy_policy_url();
            if ( $privacy_url ) {
                $text .= ' <a href="' . esc_url( $privacy_url ) . '" target="_blank" rel="noopener">' . esc_html__( 'Privacy policy', 'listnest' ) . '</a>';
            }
        }

        /**
         * Filters the consent checkbox label. Return '' to remove the checkbox.
         *
         * @param string $text     Label HTML.
         * @param array  $settings List form settings.
         */
        $text = (string) apply_filters( 'listnest_form_consent_text', $text, $settings );

        return trim( wp_kses( $text, ListNest_Inquiries::get_consent_allowed_html() ) );
    }

    /* ------------------------------------------------------------------
     * Submission
     * ------------------------------------------------------------------ */

    /**
     * Handles a form submission (hooked to wp).
     */
    public function handle_submit() {
        if ( ! isset( $_POST[ self::LIST_FIELD ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified below.
            return;
        }
        foreach ( array( self::LIST_FIELD, self::NONCE_FIELD, self::HONEYPOT_FIELD, 'listnest_name', 'listnest_email', 'listnest_phone', 'listnest_message', 'listnest_consent' ) as $field ) {
            if ( isset( $_POST[ $field ] ) && ! is_string( $_POST[ $field ] ) ) {
                wp_die( esc_html__( 'Please correct the highlighted fields.', 'listnest' ), '', array( 'response' => 400 ) );
            }
        }

        $list_key = sanitize_key( wp_unslash( $_POST[ self::LIST_FIELD ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified below.
        $settings = self::get_settings( $list_key );
        if ( null === $settings ) {
            return;
        }

        $this->submitted_list = $list_key;
        $this->values         = $this->get_posted_values();

        // Honeypot filled in: pretend success, send nothing.
        if ( ! empty( $_POST[ self::HONEYPOT_FIELD ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $this->redirect_success( $list_key );
        }

        if ( ! isset( $_POST[ self::NONCE_FIELD ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION ) ) {
            $this->errors['form'] = __( 'Your session has expired. Please send the form again.', 'listnest' );
            return;
        }

        $consent_text = $this->get_consent_text( $settings );
        $this->errors = $this->validate( $this->values, '' !== $consent_text );

        $items = $this->storage->get_list( $list_key );
        if ( empty( $items ) ) {
            $this->errors['form'] = __( 'Your list is empty. Add at least one product before sending the form.', 'listnest' );
        }

        if ( empty( $this->errors ) && $this->is_rate_limited() ) {
            $this->errors['form'] = __( 'Too many inquiries have been sent from your connection. Please try again in a few minutes.', 'listnest' );
        }

        if ( ! empty( $this->errors ) ) {
            return;
        }

        $rows = ListNest_Item::prepare_rows( $items, true );
        foreach ( $rows as $row ) {
            if ( ! $row['product'] ) {
                $this->errors['form'] = __( 'Some products are no longer available. Remove them from your list before sending.', 'listnest' );
                return;
            }
        }
        $data = array(
            'name'     => $this->values['name'],
            'email'    => sanitize_email( $this->values['email'] ),
            'phone'    => $this->values['phone'],
            'message'  => $this->values['message'],
            'list_key' => $list_key,
            'products' => $this->snapshot_rows( $rows ),
            'consent'  => $consent_text,
        );

        $lock = $this->acquire_submission_lock( $list_key );
        if ( ! $lock ) {
            $this->errors['form'] = __( 'This inquiry is already being processed. Please wait a minute before sending again.', 'listnest' );
            return;
        }

        /**
         * Filters whether inquiries are stored under Lists → Inquiries.
         *
         * @param bool  $store Default true.
         * @param array $data  Inquiry data.
         */
        $inquiry_id = apply_filters( 'listnest_form_store_inquiries', true, $data ) ? $this->inquiries->create( $data ) : 0;

        $sent = $this->send_shop_mail( $data, $settings );
        if ( $inquiry_id ) {
            $this->inquiries->set_mail_status( $inquiry_id, $sent );
        }

        if ( ! $sent && ! $inquiry_id ) {
            delete_option( $lock );
            $this->errors['form'] = __( 'Your inquiry could not be sent. Please try again later or contact us directly.', 'listnest' );
            return;
        }

        if ( $settings['copy'] ) {
            $this->send_customer_copy( $data, $settings );
        }

        $this->bump_rate_limit();
        $this->storage->clear_list( $list_key );

        /**
         * Fires after an inquiry has been received.
         *
         * @param array $data       Inquiry data.
         * @param int   $inquiry_id Stored inquiry ID (0 when not stored).
         * @param bool  $sent       Whether the mail to the shop was sent.
         */
        do_action( 'listnest_inquiry_received', $data, $inquiry_id, $sent );

        $this->redirect_success( $list_key );
    }

    /** Atomically serialize submissions for one visitor/list for one minute. */
    private function acquire_submission_lock( $list_key ) {
        $session = WC()->session;
        if ( ! $session instanceof WC_Session ) {
            return false;
        }
        $key = 'listnest_submit_lock_' . hash( 'sha256', $session->get_customer_id() . '|' . $list_key );
        $expired = get_option( $key, false );
        if ( false !== $expired && (int) $expired < time() ) {
            global $wpdb;
            // Compare-and-delete: never remove a newer lock acquired by another request.
            $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", $key, (string) $expired ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Atomic expired-lock removal; cache invalidated below.
            wp_cache_delete( $key, 'options' );
        }
        if ( ! add_option( $key, time() + MINUTE_IN_SECONDS, '', false ) ) {
            return false;
        }
        wp_schedule_single_event( time() + MINUTE_IN_SECONDS + 1, 'listnest_release_submission_lock', array( $key ) );
        return $key;
    }

    /** Remove only an expired lock belonging to this plugin. */
    public function release_submission_lock( $key ) {
        if ( is_string( $key ) && preg_match( '/^listnest_submit_lock_[a-f0-9]{64}$/', $key ) && (int) get_option( $key, 0 ) < time() ) {
            delete_option( $key );
        }
    }

    /**
     * Sanitized submitted values.
     *
     * @return array
     */
    private function get_posted_values() {
        // phpcs:disable WordPress.Security.NonceVerification.Missing -- Read before the nonce check only to re-display the form.
        $name    = isset( $_POST['listnest_name'] ) ? sanitize_text_field( wp_unslash( $_POST['listnest_name'] ) ) : '';
        // Kept as typed (re-displayed on errors); validated with is_email() and sanitize_email()d before use.
        $email   = isset( $_POST['listnest_email'] ) ? sanitize_text_field( wp_unslash( $_POST['listnest_email'] ) ) : '';
        $phone   = isset( $_POST['listnest_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['listnest_phone'] ) ) : '';
        $message = isset( $_POST['listnest_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['listnest_message'] ) ) : '';
        $consent = ! empty( $_POST['listnest_consent'] );
        // phpcs:enable

        return array(
            'name'    => is_string( $name ) ? $name : '',
            'email'   => is_string( $email ) ? $email : '',
            'phone'   => is_string( $phone ) ? $phone : '',
            'message' => is_string( $message ) ? $message : '',
            'consent' => $consent,
        );
    }

    /**
     * @param array $values          Sanitized values.
     * @param bool  $consent_required Whether the consent checkbox is shown.
     * @return array Field => message.
     */
    private function validate( array $values, $consent_required ) {
        $errors = array();

        if ( '' === $values['name'] ) {
            $errors['name'] = __( 'Please enter your name.', 'listnest' );
        } elseif ( $this->length( $values['name'] ) > self::MAX_NAME ) {
            $errors['name'] = __( 'The name is too long.', 'listnest' );
        }

        if ( '' === $values['email'] ) {
            $errors['email'] = __( 'Please enter your email address.', 'listnest' );
        } elseif ( ! is_email( $values['email'] ) ) {
            $errors['email'] = __( 'Please enter a valid email address.', 'listnest' );
        }

        if ( '' !== $values['phone'] && ( $this->length( $values['phone'] ) > self::MAX_PHONE || ! preg_match( '/^[0-9+()\-.\s\/]+$/', $values['phone'] ) ) ) {
            $errors['phone'] = __( 'Please enter a valid phone number.', 'listnest' );
        }

        if ( $this->length( $values['message'] ) > self::MAX_MESSAGE ) {
            $errors['message'] = sprintf(
                /* translators: %s: maximum number of characters */
                __( 'The message is too long (maximum %s characters).', 'listnest' ),
                number_format_i18n( self::MAX_MESSAGE )
            );
        }

        if ( $consent_required && ! $values['consent'] ) {
            $errors['consent'] = __( 'Please give your consent to send the inquiry.', 'listnest' );
        }

        /**
         * Filters the validation errors of the built-in form.
         *
         * @param array $errors Field => message.
         * @param array $values Sanitized values.
         */
        return (array) apply_filters( 'listnest_form_validation_errors', $errors, $values );
    }

    /**
     * @param string $text Text.
     * @return int
     */
    private function length( $text ) {
        return function_exists( 'mb_strlen' ) ? mb_strlen( $text, 'UTF-8' ) : strlen( $text );
    }

    /**
     * Plain-array copy of the rows, independent of later product changes.
     *
     * @param array $rows Rows from ListNest_Item::prepare_rows().
     * @return array
     */
    private function snapshot_rows( array $rows ) {
        $snapshot = array();
        foreach ( $rows as $row ) {
            $product    = $row['product'];
            $snapshot[] = array(
                'product_id'   => $row['parent'] instanceof WC_Product ? $row['parent']->get_id() : 0,
                'variation_id' => ( $product instanceof WC_Product && $product->is_type( 'variation' ) ) ? $product->get_id() : 0,
                'sku'          => $product instanceof WC_Product ? (string) $product->get_sku() : '',
                'name'         => (string) $row['name'],
                'attributes'   => (array) $row['attributes'],
                'quantity'     => (int) $row['quantity'],
            );
        }
        return $snapshot;
    }

    /**
     * Redirects back to the form page with the thank-you flag.
     *
     * @param string $list_key List key.
     */
    private function redirect_success( $list_key ) {
        $url = ListNest_Frontend::get_return_url( array( self::SENT_ARG, 'listnest_list_updated' ) );
        $url = add_query_arg( self::SENT_ARG, rawurlencode( $list_key ), $url ) . '#listnest-form-' . $list_key;

        wp_safe_redirect( $url );
        exit;
    }

    /* ------------------------------------------------------------------
     * Mail
     * ------------------------------------------------------------------ */

    /**
     * Mail to the shop.
     *
     * @param array $data     Inquiry data.
     * @param array $settings List form settings.
     * @return bool
     */
    private function send_shop_mail( array $data, array $settings ) {
        $recipients = $this->get_recipients( $settings );
        $subject    = '' !== trim( $settings['subject'] ) ? $settings['subject'] : __( 'New inquiry: {list}', 'listnest' );
        $subject    = $this->clean_header( strtr(
            $subject,
            array(
                '{list}' => ListNest_Mail::list_title( $data['list_key'] ),
                '{name}' => $data['name'],
                '{site}' => ListNest_Mail::plain_text( get_bloginfo( 'name' ) ),
            )
        ) );

        $body = '<p>' . esc_html__( 'You have received a new inquiry:', 'listnest' ) . '</p>' . $this->get_details_html( $data );

        $args = array(
            'to'      => $recipients,
            'subject' => $subject,
            'message' => ListNest_Mail::wrap_html( $body ),
            'headers' => array(
                'Content-Type: text/html; charset=UTF-8',
                'Reply-To: ' . $this->format_address( $data['email'], $data['name'] ),
            ),
        );

        return $this->mail( $args, 'shop', $data );
    }

    /**
     * Copy for the customer.
     *
     * @param array $data     Inquiry data.
     * @param array $settings List form settings.
     * @return bool
     */
    private function send_customer_copy( array $data, array $settings ) {
        $recipients = $this->get_recipients( $settings );

        $body  = '<p>' . esc_html__( 'Thank you for your inquiry. We will get back to you as soon as possible. Here is a copy of what you sent:', 'listnest' ) . '</p>';
        $body .= $this->get_details_html( $data );

        $args = array(
            'to'      => $data['email'],
            'subject' => $this->clean_header( sprintf(
                /* translators: %s: site name */
                __( 'Your inquiry - %s', 'listnest' ),
                ListNest_Mail::plain_text( get_bloginfo( 'name' ) )
            ) ),
            'message' => ListNest_Mail::wrap_html( $body ),
            'headers' => array(
                'Content-Type: text/html; charset=UTF-8',
                'Reply-To: ' . $recipients[0],
            ),
        );

        return $this->mail( $args, 'customer', $data );
    }

    /**
     * Sends a mail after the listnest_form_mail filter.
     *
     * @param array  $args to, subject, message, headers.
     * @param string $type 'shop' or 'customer'.
     * @param array  $data Inquiry data.
     * @return bool
     */
    private function mail( array $args, $type, array $data ) {
        /**
         * Filters a mail sent by the built-in form.
         *
         * @param array  $args to, subject, message (HTML), headers.
         * @param string $type 'shop' (to the shop) or 'customer' (copy for the customer).
         * @param array  $data Inquiry data.
         */
        $args = (array) apply_filters( 'listnest_form_mail', $args, $type, $data );

        if ( empty( $args['to'] ) ) {
            return false;
        }

        return (bool) wp_mail( $args['to'], $args['subject'], $args['message'], $args['headers'] );
    }

    /**
     * Customer data and products as HTML.
     *
     * @param array $data Inquiry data.
     * @return string Escaped HTML.
     */
    private function get_details_html( array $data ) {
        $th = 'style="text-align:left;padding:6px 12px 6px 0;vertical-align:top;white-space:nowrap;"';
        $td = 'style="padding:6px 0;vertical-align:top;"';

        $fields = array(
            __( 'Name', 'listnest' )  => esc_html( $data['name'] ),
            __( 'Email', 'listnest' ) => '<a href="' . esc_url( 'mailto:' . $data['email'] ) . '">' . esc_html( $data['email'] ) . '</a>',
        );
        if ( '' !== $data['phone'] ) {
            $fields[ __( 'Phone', 'listnest' ) ] = esc_html( $data['phone'] );
        }
        if ( '' !== $data['message'] ) {
            $fields[ __( 'Message', 'listnest' ) ] = nl2br( esc_html( $data['message'] ) );
        }

        $html = '<table cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:0 0 16px;">';
        foreach ( $fields as $label => $value ) {
            $html .= '<tr><th ' . $th . '>' . esc_html( $label ) . '</th><td ' . $td . '>' . $value . '</td></tr>';
        }
        $html .= '</table>';

        return $html . ListNest_Mail::products_html( $data['list_key'], $data['products'] );
    }

    /**
     * Valid recipient addresses from the list settings (comma separated),
     * falling back to the site admin email.
     *
     * @param array $settings List form settings.
     * @return string[]
     */
    private function get_recipients( array $settings ) {
        $recipients = array();
        foreach ( explode( ',', $settings['recipients'] ) as $email ) {
            $email = sanitize_email( trim( $email ) );
            if ( is_email( $email ) ) {
                $recipients[] = $email;
            }
        }
        if ( empty( $recipients ) ) {
            $recipients[] = (string) get_option( 'admin_email' );
        }
        return $recipients;
    }

    /**
     * "Name <email>" with characters that could break the header removed.
     *
     * @param string $email Valid email.
     * @param string $name  Display name.
     * @return string
     */
    private function format_address( $email, $name ) {
        $name = trim( str_replace( array( '"', '<', '>', ',', ';', ':', '\\' ), '', $this->clean_header( $name ) ) );
        return '' !== $name ? '"' . $name . '" <' . $email . '>' : $email;
    }

    /**
     * Removes line breaks (header injection).
     *
     * @param string $value Header value.
     * @return string
     */
    private function clean_header( $value ) {
        return trim( preg_replace( '/[\r\n\t]+/', ' ', (string) $value ) );
    }

    /* ------------------------------------------------------------------
     * Rate limit
     * ------------------------------------------------------------------ */

    /**
     * Rate limit settings.
     *
     * @return array max (submissions), window (seconds).
     */
    private function get_rate_limit() {
        /**
         * Filters the per-IP rate limit of the built-in form.
         *
         * @param array $limit max (default 5 inquiries), window (default 600 seconds). max 0 disables the limit.
         */
        $limit = (array) apply_filters(
            'listnest_form_rate_limit',
            array(
                'max'    => 5,
                'window' => 10 * MINUTE_IN_SECONDS,
            )
        );

        return array(
            'max'    => isset( $limit['max'] ) ? absint( $limit['max'] ) : 5,
            'window' => isset( $limit['window'] ) ? max( 1, absint( $limit['window'] ) ) : 10 * MINUTE_IN_SECONDS,
        );
    }

    /**
     * Transient key for the visitor's IP (hashed; the IP is not stored).
     *
     * @return string '' when the IP is unknown.
     */
    private function get_rate_key() {
        $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? filter_var( wp_unslash( $_SERVER['REMOTE_ADDR'] ), FILTER_VALIDATE_IP ) : false; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Validated as an IP.
        return $ip ? 'listnest_rl_' . md5( wp_salt( 'nonce' ) . $ip ) : '';
    }

    /**
     * @return bool
     */
    private function is_rate_limited() {
        $limit = $this->get_rate_limit();
        $key   = $this->get_rate_key();
        if ( ! $limit['max'] || '' === $key ) {
            return false;
        }
        return (int) get_transient( $key ) >= $limit['max'];
    }

    private function bump_rate_limit() {
        $limit = $this->get_rate_limit();
        $key   = $this->get_rate_key();
        if ( ! $limit['max'] || '' === $key ) {
            return;
        }
        set_transient( $key, (int) get_transient( $key ) + 1, $limit['window'] );
    }
}
