<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Contact Form 7 integration.
 *
 * Form tag:  [listnest_products list:my-list]
 *            Renders a hidden field carrying the list key plus a read-only
 *            summary of the visitor's list. Submitting the form with an
 *            empty list fails validation (filter: listnest_cf7_require_items).
 *
 * Mail tag:  [listnest_products]
 *            Inserts the list into the mail body: an HTML table when the
 *            mail uses HTML content type, a plain-text listing otherwise.
 *
 * After the mail has been sent, the list is cleared
 * (filter: listnest_cf7_clear_after_send).
 *
 * Backward compatibility: forms without the form tag may still carry the
 * list key in a hidden field named "listnest_cf7_list"; the mail tag and
 * the clearing then use that value.
 *
 * Hooks are only registered on wpcf7_init, i.e. when Contact Form 7 is active.
 *
 * Session note: CF7 submits through the REST API, where WooCommerce does not
 * load its session by default, so load_session() is called before every
 * read. For logged-in users the REST request is only authenticated when it
 * carries a valid wp_rest nonce; if that nonce is missing or stale (e.g. a
 * cached form page), WordPress treats the request as a guest, WooCommerce
 * ignores the user's session cookie and the list appears empty. To be
 * verified in Stage 5 testing (guest and logged-in, with and without page cache).
 */
class ListNest_CF7 {

    /**
     * Form tag and mail tag name.
     */
    const TAG = 'listnest_products';

    /**
     * Hidden field carrying the list key (also accepted from older forms).
     */
    const FIELD = 'listnest_cf7_list';

    /**
     * List storage.
     *
     * @var ListNest_Session
     */
    private $storage;

    /**
     * Front-end component (assets), optional.
     *
     * @var ListNest_Frontend|null
     */
    private $frontend;

    /**
     * @param ListNest_Session       $storage  List storage.
     * @param ListNest_Frontend|null $frontend Front-end component, used to enqueue the stylesheet.
     */
    public function __construct( ListNest_Session $storage, $frontend = null ) {
        $this->storage  = $storage;
        $this->frontend = ( $frontend instanceof ListNest_Frontend ) ? $frontend : null;
    }

    /**
     * Registers hooks. Called once by ListNest_Plugin.
     *
     * wpcf7_init only fires when Contact Form 7 is active, so nothing else
     * is hooked on sites without CF7.
     */
    public function register_hooks() {
        add_action( 'wpcf7_init', array( $this, 'register_cf7_hooks' ) );
    }

    /**
     * Registers the form tag, mail tag, validation and post-send handlers.
     */
    public function register_cf7_hooks() {
        if ( function_exists( 'wpcf7_add_form_tag' ) ) {
            wpcf7_add_form_tag( self::TAG, array( $this, 'render_form_tag' ) );
        }

        add_filter( 'wpcf7_validate_' . self::TAG, array( $this, 'validate_form_tag' ), 10, 2 );
        add_filter( 'wpcf7_special_mail_tags', array( $this, 'render_mail_tag' ), 10, 3 );
        add_action( 'wpcf7_mail_sent', array( $this, 'clear_after_send' ) );
    }

    /* ------------------------------------------------------------------
     * Form tag
     * ------------------------------------------------------------------ */

    /**
     * Renders [listnest_products list:my-list] inside the form.
     *
     * @param WPCF7_FormTag $tag Form tag.
     * @return string
     */
    public function render_form_tag( $tag ) {
        $list_key = $this->get_tag_list_key( $tag );

        if ( '' === $list_key || ! ListNest_CPT::get_published_list_id( $list_key ) ) {
            if ( ! current_user_can( 'edit_posts' ) ) {
                return '';
            }
            return '<span class="listnest listnest-cf7-summary listnest-cf7-summary--error">' . esc_html(
                sprintf(
                    /* translators: %s: form tag example */
                    __( 'ListNest: this form tag needs the key of a published list, e.g. %s.', 'listnest' ),
                    '[' . self::TAG . ' list:my-list]'
                )
            ) . '</span>';
        }

        // The summary is personal (session based): keep the page out of full-page caches.
        if ( ! defined( 'DONOTCACHEPAGE' ) ) {
            define( 'DONOTCACHEPAGE', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Standard caching plugin constant.
        }

        if ( $this->frontend ) {
            $this->frontend->enqueue_style();
        }

        $rows = ListNest_Item::prepare_rows( $this->get_items( $list_key ) );

        // Inline elements only: CF7 wraps form tags in paragraphs.
        $summary = '<span class="listnest listnest-cf7-summary">';
        $summary .= '<span class="listnest-cf7-summary__title">' . esc_html( ListNest_Mail::list_title( $list_key ) ) . '</span>';

        if ( empty( $rows ) ) {
            $summary .= '<span class="listnest-cf7-summary__empty">' . esc_html__( 'No products on the list.', 'listnest' ) . '</span>';
        } else {
            $summary .= '<span class="listnest-cf7-summary__items" role="list">';
            foreach ( $rows as $row ) {
                $summary .= '<span class="listnest-cf7-summary__item" role="listitem">';
                $summary .= '<span class="listnest-cf7-summary__name">' . esc_html( $row['name'] ) . '</span> ';
                $summary .= '<span class="listnest-cf7-summary__quantity">' . esc_html(
                    sprintf(
                        /* translators: %s: quantity */
                        __( '× %s', 'listnest' ),
                        number_format_i18n( $row['quantity'] )
                    )
                ) . '</span>';
                if ( ! empty( $row['attributes'] ) ) {
                    $summary .= '<span class="listnest-cf7-summary__attributes">' . esc_html( ListNest_Mail::attributes_text( $row['attributes'], ', ' ) ) . '</span>';
                }
                $summary .= '</span>';
            }
            $summary .= '</span>';
        }
        $summary .= '</span>';

        /**
         * Filters the read-only list summary shown inside the form.
         *
         * @param string $summary  Summary HTML (escaped).
         * @param string $list_key List key.
         * @param array  $rows     Rows from ListNest_Item::prepare_rows().
         */
        $summary = (string) apply_filters( 'listnest_cf7_form_summary_html', $summary, $list_key, $rows );

        // REST cookie authentication needs a nonce or WordPress treats logged-in
        // customers as guests, losing access to their WooCommerce session.
        $rest_nonce = is_user_logged_in() ? '<input type="hidden" name="_wpnonce" value="' . esc_attr( wp_create_nonce( 'wp_rest' ) ) . '" />' : '';

        return sprintf(
            '<span class="wpcf7-form-control-wrap %1$s listnest-cf7" data-name="%1$s"><input type="hidden" name="%1$s" value="%2$s" />%3$s</span>',
            esc_attr( self::FIELD ),
            esc_attr( $list_key ),
            $summary
        ) . $rest_nonce;
    }

    /**
     * Validation: fails when the list attached to the form is empty.
     *
     * @param WPCF7_Validation $result Validation result.
     * @param WPCF7_FormTag    $tag    Form tag.
     * @return WPCF7_Validation
     */
    public function validate_form_tag( $result, $tag ) {
        $list_key = $this->get_tag_list_key( $tag );
        if ( '' === $list_key ) {
            $list_key = $this->get_submitted_list_key();
        }

        /**
         * Filters whether the form may only be sent with a non-empty list.
         *
         * @param bool                   $require      Default true.
         * @param string                 $list_key     List key.
         * @param WPCF7_ContactForm|null $contact_form Contact form.
         */
        if ( ! apply_filters( 'listnest_cf7_require_items', true, $list_key, $this->get_current_form() ) ) {
            return $result;
        }

        $items = $this->get_items( $list_key );
        $rows = ListNest_Item::prepare_rows( $items, true );
        $unavailable = false;
        foreach ( $rows as $row ) {
            if ( ! $row['product'] ) {
                $unavailable = true;
            }
        }
        if ( '' === $list_key || empty( $items ) || $unavailable ) {
            // The form tag has no name of its own; report the error on the hidden field wrapper.
            $named       = clone $tag;
            $named->name = self::FIELD;
            $result->invalidate( $named, $unavailable ? __( 'Some products are no longer available. Remove them from your list before sending.', 'listnest' ) : __( 'Your list is empty. Add at least one product before sending the form.', 'listnest' ) );
        }

        return $result;
    }

    /* ------------------------------------------------------------------
     * Mail tag
     * ------------------------------------------------------------------ */

    /**
     * Replaces the [listnest_products] mail tag.
     *
     * @param string|null $output Output from other handlers.
     * @param string      $name   Mail tag name.
     * @param bool        $html   Whether the mail is HTML.
     * @return string|null
     */
    public function render_mail_tag( $output, $name, $html ) {
        if ( self::TAG !== $name ) {
            return $output;
        }

        $list_key = $this->get_form_list_key( $this->get_current_form() );
        if ( '' === $list_key ) {
            return $output;
        }

        $rows = ListNest_Item::prepare_rows( $this->get_items( $list_key ), true );
        $mail = $html ? ListNest_Mail::products_html( $list_key, $rows ) : ListNest_Mail::products_text( $list_key, $rows );

        /**
         * Filters the list inserted into the mail by the [listnest_products] mail tag.
         *
         * @param string $mail     Mail output (escaped HTML or plain text).
         * @param string $list_key List key.
         * @param array  $rows     Rows from ListNest_Item::prepare_rows() (missing products included).
         * @param bool   $html     Whether the mail is HTML.
         */
        return (string) apply_filters( 'listnest_cf7_mail_output', $mail, $list_key, $rows, (bool) $html );
    }

    /* ------------------------------------------------------------------
     * After sending
     * ------------------------------------------------------------------ */

    /**
     * Clears the list once the mail has been sent.
     *
     * @param WPCF7_ContactForm $contact_form Contact form.
     */
    public function clear_after_send( $contact_form ) {
        $list_key = $this->get_form_list_key( $contact_form );
        if ( '' === $list_key ) {
            return;
        }

        /**
         * Filters whether the list is cleared after the form has been sent.
         *
         * @param bool              $clear        Default true.
         * @param string            $list_key     List key.
         * @param WPCF7_ContactForm $contact_form Contact form.
         */
        if ( ! apply_filters( 'listnest_cf7_clear_after_send', true, $list_key, $contact_form ) ) {
            return;
        }

        $this->storage->load_session();
        $this->storage->clear_list( $list_key );
    }

    /* ------------------------------------------------------------------
     * Helpers
     * ------------------------------------------------------------------ */

    /**
     * Items of a list; loads the WooCommerce session first (REST requests).
     *
     * @param string $list_key List key.
     * @return array
     */
    private function get_items( $list_key ) {
        if ( '' === $list_key ) {
            return array();
        }
        $this->storage->load_session();
        return $this->storage->get_list( $list_key );
    }

    /**
     * List key configured on a form tag: list:my-list (or a quoted value).
     *
     * @param WPCF7_FormTag $tag Form tag.
     * @return string
     */
    private function get_tag_list_key( $tag ) {
        if ( ! is_object( $tag ) ) {
            return '';
        }

        $key = method_exists( $tag, 'get_option' ) ? $tag->get_option( 'list', '[-0-9a-zA-Z_]+', true ) : false;
        if ( ! $key && ! empty( $tag->values ) && is_array( $tag->values ) ) {
            $key = reset( $tag->values );
        }

        return is_string( $key ) ? sanitize_key( $key ) : '';
    }

    /**
     * List key for a submitted form: from the form's [listnest_products]
     * tag, otherwise from the submitted hidden field.
     *
     * @param WPCF7_ContactForm|null $contact_form Contact form.
     * @return string
     */
    private function get_form_list_key( $contact_form ) {
        if ( is_object( $contact_form ) && method_exists( $contact_form, 'scan_form_tags' ) ) {
            foreach ( (array) $contact_form->scan_form_tags( array( 'type' => self::TAG ) ) as $tag ) {
                $list_key = $this->get_tag_list_key( $tag );
                if ( '' !== $list_key ) {
                    return $list_key;
                }
            }
        }

        return $this->get_submitted_list_key();
    }

    /**
     * List key from the submitted hidden field.
     *
     * @return string
     */
    private function get_submitted_list_key() {
        $value = null;

        if ( class_exists( 'WPCF7_Submission' ) ) {
            $submission = WPCF7_Submission::get_instance();
            if ( $submission ) {
                $value = $submission->get_posted_data( self::FIELD );
            }
        }

        // CF7 verifies its own submission; this only reads the visitor's own list key.
        if ( ( null === $value || '' === $value ) && isset( $_POST[ self::FIELD ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $value = wp_unslash( $_POST[ self::FIELD ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized below.
        }

        if ( is_array( $value ) ) {
            $value = reset( $value );
        }

        return is_string( $value ) ? sanitize_key( $value ) : '';
    }

    /**
     * Contact form being submitted (or rendered).
     *
     * @return WPCF7_ContactForm|null
     */
    private function get_current_form() {
        if ( class_exists( 'WPCF7_Submission' ) ) {
            $submission = WPCF7_Submission::get_instance();
            if ( $submission ) {
                return $submission->get_contact_form();
            }
        }
        if ( class_exists( 'WPCF7_ContactForm' ) ) {
            return WPCF7_ContactForm::get_current();
        }
        return null;
    }
}
