<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * List storage backed by the WooCommerce session.
 *
 * All lists of the current visitor are kept under a single session key
 * ('listnest_lists') as: array( list_key => array( item_key => item ) ),
 * where item = array( product_id, variation_id, attributes, quantity ).
 *
 * WC()->session is only available on front-end and AJAX requests; every
 * method fails gracefully (empty list / false) when it is not.
 */
class ListNest_Session {

    /**
     * WooCommerce session key holding all lists.
     */
    const SESSION_KEY = 'listnest_lists';

    /**
     * Registers hooks. Called once by ListNest_Plugin.
     * Storage needs no hooks: WooCommerce manages the session lifecycle.
     */
    public function register_hooks() {
    }

    /**
     * Returns the items of one list.
     *
     * @param string $list_key List key (listnest_list post slug).
     * @return array
     */
    public function get_list( $list_key ) {
        $lists    = $this->get_all();
        $list_key = sanitize_key( $list_key );

        return ( isset( $lists[ $list_key ] ) && is_array( $lists[ $list_key ] ) ) ? $lists[ $list_key ] : array();
    }

    /**
     * Replaces the items of one list. Starts a session (and sets the session
     * cookie) for guests who do not have one yet.
     *
     * @param string $list_key List key.
     * @param array  $items    Items keyed by item key.
     * @return bool False when no WooCommerce session is available.
     */
    public function set_list( $list_key, $items ) {
        $list_key = sanitize_key( $list_key );
        $session  = $this->get_wc_session();
        if ( '' === $list_key || ! $session ) {
            return false;
        }

        if ( ! $session->has_session() ) {
            $session->set_customer_session_cookie( true );
        }

        $lists = $this->get_all();
        if ( empty( $items ) ) {
            unset( $lists[ $list_key ] );
        } else {
            $lists[ $list_key ] = (array) $items;
        }

        $session->set( self::SESSION_KEY, $lists );
        return true;
    }

    /**
     * Returns all lists of the current visitor.
     *
     * @return array
     */
    public function get_all() {
        $session = $this->get_wc_session();
        if ( ! $session ) {
            return array();
        }

        $lists = $session->get( self::SESSION_KEY, array() );
        return is_array( $lists ) ? $lists : array();
    }

    /**
     * Removes one list.
     *
     * @param string $list_key List key.
     * @return bool
     */
    public function clear_list( $list_key ) {
        $session = $this->get_wc_session();
        if ( ! $session || ! $session->has_session() ) {
            return false;
        }
        return $this->set_list( $list_key, array() );
    }

    /**
     * Makes sure WC()->session is loaded. WooCommerce only initialises it on
     * front-end and AJAX requests, so REST requests (e.g. Contact Form 7
     * submissions) must call this before reading. Never runs in wp-admin.
     *
     * @return bool Whether a session object is available.
     */
    public function load_session() {
        if ( ! function_exists( 'WC' ) ) {
            return false;
        }

        $wc = WC();
        if ( null === $wc->session
            && did_action( 'woocommerce_init' )
            && ( ! is_admin() || wp_doing_ajax() )
            && method_exists( $wc, 'initialize_session' )
        ) {
            $wc->initialize_session();
        }

        return null !== $wc->session;
    }

    /**
     * Maximum quantity of a single item (filterable).
     *
     * @return int
     */
    public function get_max_quantity() {
        return max( 1, absint( apply_filters( 'listnest_max_quantity', 9999 ) ) );
    }

    /**
     * Maximum number of distinct items in one list (filterable).
     *
     * @return int
     */
    public function get_max_items() {
        return max( 1, absint( apply_filters( 'listnest_max_items', 100 ) ) );
    }

    /**
     * Sanitizes an item key ("123" or "123:<md5>").
     *
     * @param mixed $item_key Raw item key.
     * @return string
     */
    public static function sanitize_item_key( $item_key ) {
        if ( ! is_scalar( $item_key ) ) {
            return '';
        }
        return (string) preg_replace( '/[^0-9a-f:]/', '', strtolower( (string) $item_key ) );
    }

    /**
     * Returns the WooCommerce session object, or null when unavailable.
     *
     * @return WC_Session|null
     */
    private function get_wc_session() {
        if ( ! function_exists( 'WC' ) ) {
            return null;
        }
        $session = WC()->session;
        return ( $session instanceof WC_Session ) ? $session : null;
    }
}
