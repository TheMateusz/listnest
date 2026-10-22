<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Front-end: assets, "Add to list" buttons and the script configuration.
 *
 * Assets are loaded only where they are needed: on single product pages,
 * on singular pages whose content contains a ListNest shortcode, and on any
 * page where a button or list is actually rendered (late enqueue; the
 * scripts are printed in the footer).
 */
class ListNest_Frontend {

    const STYLE_HANDLE  = 'listnest';
    const SCRIPT_HANDLE = 'listnest-add-to-list';

    /**
     * Whether the button script has been enqueued on this request.
     *
     * @var bool
     */
    private $script_enqueued = false;

    /**
     * Published lists and their settings, keyed by list key (lazy loaded).
     *
     * @var array|null
     */
    private $lists = null;

    /**
     * Registers hooks. Called once by ListNest_Plugin.
     */
    public function register_hooks() {
        add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );
        add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ) );
        add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'render_auto_buttons' ) );
        add_shortcode( 'listnest_button', array( $this, 'render_button_shortcode' ) );
    }

    /**
     * Registers (does not enqueue) the front-end stylesheet and script.
     */
    public function register_assets() {
        wp_register_style( self::STYLE_HANDLE, LISTNEST_URL . 'assets/css/listnest.css', array(), LISTNEST_VERSION );
        wp_register_script( self::SCRIPT_HANDLE, LISTNEST_URL . 'assets/js/add-to-list.js', array(), LISTNEST_VERSION, true );
    }

    /**
     * Enqueues assets up front where they are known to be needed, so the
     * stylesheet lands in <head>.
     */
    public function maybe_enqueue_assets() {
        if ( function_exists( 'is_product' ) && is_product() ) {
            $this->enqueue_script();
            return;
        }

        $post = is_singular() ? get_post() : null;
        if ( ! $post || ! is_string( $post->post_content ) ) {
            return;
        }

        if ( has_shortcode( $post->post_content, 'listnest_button' ) ) {
            $this->enqueue_script();
        } elseif ( has_shortcode( $post->post_content, 'listnest' ) ) {
            $this->enqueue_style();
        }
    }

    /**
     * Enqueues the stylesheet.
     */
    public function enqueue_style() {
        if ( ! wp_style_is( self::STYLE_HANDLE, 'registered' ) ) {
            $this->register_assets();
        }
        wp_enqueue_style( self::STYLE_HANDLE );
    }

    /**
     * Enqueues the stylesheet, the button script and its configuration, and
     * marks the popup as needed. Safe to call several times and after
     * wp_head (the script is printed in the footer).
     */
    public function enqueue_script() {
        $this->enqueue_style();

        if ( $this->script_enqueued ) {
            return;
        }
        $this->script_enqueued = true;

        wp_enqueue_script( self::SCRIPT_HANDLE );
        wp_localize_script( self::SCRIPT_HANDLE, 'ListNestConfig', $this->get_script_config() );
    }

    /**
     * Whether the button script was enqueued (popup markup is needed).
     *
     * @return bool
     */
    public function is_script_needed() {
        return $this->script_enqueued;
    }

    /**
     * Whether any published list uses the popup option.
     *
     * @return bool
     */
    public function has_popup_lists() {
        foreach ( $this->get_lists() as $list ) {
            if ( 'popup' === $list['option'] ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Renders buttons for every list with "Show button on product pages"
     * enabled. Hooked to woocommerce_after_add_to_cart_button.
     */
    public function render_auto_buttons() {
        global $product;

        if ( ! $product instanceof WC_Product ) {
            return;
        }

        foreach ( $this->get_lists() as $list_key => $list ) {
            if ( ! $list['auto_button'] ) {
                continue;
            }

            // Grouped and external products have no single product to add.
            $show = ! $product->is_type( array( 'grouped', 'external' ) );

            /**
             * Filters whether the automatic button is shown for a list on a product page.
             *
             * @param bool       $show     Default: true except for grouped and external products.
             * @param string     $list_key List key.
             * @param WC_Product $product  Current product.
             */
            if ( ! apply_filters( 'listnest_show_auto_button', $show, $list_key, $product ) ) {
                continue;
            }

            echo $this->get_button_html( $list_key, $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the template.
        }
    }

    /**
     * Shortcode: [listnest_button list="samples" product_id="123"]
     *
     * product_id is optional and defaults to the current product.
     *
     * @param array|string $atts Shortcode attributes.
     * @return string
     */
    public function render_button_shortcode( $atts ) {
        $atts = shortcode_atts(
            array(
                'list'       => '',
                'product_id' => 0,
            ),
            $atts,
            'listnest_button'
        );

        $list_key   = sanitize_key( $atts['list'] );
        $product_id = absint( $atts['product_id'] );

        if ( $product_id ) {
            $product = wc_get_product( $product_id );
        } else {
            $product = isset( $GLOBALS['product'] ) ? $GLOBALS['product'] : null;
            if ( ! $product instanceof WC_Product && 'product' === get_post_type() ) {
                $product = wc_get_product( get_the_ID() );
            }
        }

        if ( ! $product instanceof WC_Product ) {
            return '';
        }

        return $this->get_button_html( $list_key, $product );
    }

    /**
     * Returns the "Add to list" button markup (templates/add-button.php,
     * overridable in yourtheme/listnest/add-button.php).
     *
     * @param string     $list_key List key.
     * @param WC_Product $product  Product the button adds.
     * @return string '' for unknown lists or products.
     */
    public function get_button_html( $list_key, $product ) {
        $list_key = sanitize_key( $list_key );
        $lists    = $this->get_lists();

        if ( '' === $list_key || ! isset( $lists[ $list_key ] ) || ! $product instanceof WC_Product ) {
            return '';
        }

        $template = ListNest_Template::locate( array( $list_key . '-add-button.php', 'add-button.php' ), $list_key );
        if ( '' === $template ) {
            return '';
        }

        $this->enqueue_script();

        $product_id = $product->get_id();

        /**
         * Filters the button label.
         *
         * @param string     $label    Label from the list settings, or "Add to list".
         * @param string     $list_key List key.
         * @param WC_Product $product  Product.
         */
        $label = (string) apply_filters( 'listnest_button_label', $lists[ $list_key ]['button_label'], $list_key, $product );

        $classes = array( 'button', 'listnest-add-button' );
        if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
            $classes[] = 'wp-element-button';
        }

        /**
         * Filters the button CSS classes.
         *
         * @param string[]   $classes  Classes.
         * @param string     $list_key List key.
         * @param WC_Product $product  Product.
         */
        $classes = (array) apply_filters( 'listnest_button_classes', $classes, $list_key, $product );
        $classes = implode( ' ', array_filter( array_map( 'sanitize_html_class', $classes ) ) );

        ob_start();
        include $template;
        return ob_get_clean();
    }

    /**
     * Returns the published lists and their settings, keyed by list key.
     *
     * Each entry: option, auto_button, button_label, redirect_url,
     * button_text, button_url, popup (title, btn1_text, btn2_text, btn2_url).
     *
     * @return array
     */
    public function get_lists() {
        if ( null !== $this->lists ) {
            return $this->lists;
        }

        $this->lists = array();

        $posts = get_posts(
            array(
                'post_type'      => 'listnest_list',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'no_found_rows'  => true,
                'orderby'        => 'menu_order title',
                'order'          => 'ASC',
            )
        );

        foreach ( $posts as $post ) {
            $list_key = sanitize_key( $post->post_name );
            if ( '' === $list_key ) {
                continue;
            }

            $id     = $post->ID;
            $option = (string) get_post_meta( $id, '_listnest_list_option', true );

            $button_label = (string) get_post_meta( $id, '_listnest_list_add_button_label', true );
            $button_text  = (string) get_post_meta( $id, '_listnest_list_button_text', true );
            $popup_title  = (string) get_post_meta( $id, '_listnest_list_popup_title', true );
            $popup_btn1   = (string) get_post_meta( $id, '_listnest_list_popup_btn1_text', true );
            $popup_btn2   = (string) get_post_meta( $id, '_listnest_list_popup_btn2_text', true );

            $this->lists[ $list_key ] = array(
                'option'       => in_array( $option, array( 'redirect', 'change_text', 'popup' ), true ) ? $option : '',
                'auto_button'  => '1' === (string) get_post_meta( $id, '_listnest_list_auto_button', true ),
                'button_label' => '' !== $button_label ? $button_label : __( 'Add to list', 'listnest' ),
                'redirect_url' => $this->get_page_url( get_post_meta( $id, '_listnest_list_redirect_page', true ) ),
                'button_text'  => '' !== $button_text ? $button_text : __( 'See list', 'listnest' ),
                'button_url'   => $this->get_page_url( get_post_meta( $id, '_listnest_list_button_page', true ) ),
                'popup'        => array(
                    'title'     => '' !== $popup_title ? $popup_title : __( 'Product added to the list', 'listnest' ),
                    'btn1_text' => '' !== $popup_btn1 ? $popup_btn1 : __( 'Continue shopping', 'listnest' ),
                    'btn2_text' => '' !== $popup_btn2 ? $popup_btn2 : __( 'Go to list', 'listnest' ),
                    'btn2_url'  => $this->get_page_url( get_post_meta( $id, '_listnest_list_popup_btn2_page', true ) ),
                ),
            );
        }

        return $this->lists;
    }

    /**
     * Permalink of a published page (translated with Polylang when active).
     *
     * @param mixed $page_id Page ID.
     * @return string
     */
    private function get_page_url( $page_id ) {
        $page_id = absint( $page_id );
        if ( ! $page_id ) {
            return '';
        }

        if ( function_exists( 'pll_get_post' ) ) {
            $translated = pll_get_post( $page_id );
            if ( $translated ) {
                $page_id = (int) $translated;
            }
        }

        if ( 'publish' !== get_post_status( $page_id ) ) {
            return '';
        }

        $url = get_permalink( $page_id );
        return $url ? $url : '';
    }

    /**
     * Data passed to the script as ListNestConfig (public information only).
     *
     * @return array
     */
    private function get_script_config() {
        $lists = array();
        foreach ( $this->get_lists() as $list_key => $list ) {
            $lists[ $list_key ] = array(
                'option'      => $list['option'],
                'redirectUrl' => $list['redirect_url'],
                'buttonText'  => $list['button_text'],
                'buttonUrl'   => $list['button_url'],
                'popup'       => array(
                    'title'    => $list['popup']['title'],
                    'btn1Text' => $list['popup']['btn1_text'],
                    'btn2Text' => $list['popup']['btn2_text'],
                    'btn2Url'  => $list['popup']['btn2_url'],
                ),
            );
        }

        $config = array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'action'  => 'listnest_add_to_list',
            'nonce'   => wp_create_nonce( ListNest_Ajax::NONCE_ACTION ),
            'lists'   => $lists,
            'i18n'    => array(
                'added'         => __( 'Added to the list.', 'listnest' ),
                'error'         => __( 'The product could not be added to the list. Please try again.', 'listnest' ),
                'networkError'  => __( 'Connection error. Please check your connection and try again.', 'listnest' ),
                'unknownList'   => __( 'This list is not available.', 'listnest' ),
                'selectOptions' => __( 'Please select product options before adding this product to the list.', 'listnest' ),
                'adding'        => __( 'Adding to the list…', 'listnest' ),
            ),
        );

        /**
         * Filters the front-end script configuration (ListNestConfig).
         *
         * @param array $config Configuration.
         */
        return apply_filters( 'listnest_script_config', $config );
    }
}
