<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main plugin class. Loads all components and registers every hook exactly once.
 *
 * Pattern: each component has a side-effect-free constructor and a
 * register_hooks() method. This class instantiates each component once
 * and calls register_hooks() on it.
 */
final class ListNest_Plugin {

    /**
     * @var ListNest_Plugin|null
     */
    private static $instance = null;

    /**
     * Component instances, keyed by name.
     *
     * @var array
     */
    private $components = array();

    /**
     * Returns the single plugin instance (created on first call).
     *
     * @return ListNest_Plugin
     */
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->includes();
        $this->init_components();

        add_action( 'before_woocommerce_init', array( $this, 'declare_wc_compatibility' ) );
    }

    /**
     * Returns a component instance by name, or null.
     *
     * @param string $name Component name (e.g. 'cpt', 'ajax').
     * @return object|null
     */
    public function get_component( $name ) {
        return isset( $this->components[ $name ] ) ? $this->components[ $name ] : null;
    }

    /**
     * Whether WooCommerce is active.
     *
     * @return bool
     */
    public static function is_woocommerce_active() {
        return class_exists( 'WooCommerce' );
    }

    private function includes() {
        require_once LISTNEST_PATH . 'includes/class-listnest-cpt.php';
        require_once LISTNEST_PATH . 'includes/class-listnest-session.php';
        require_once LISTNEST_PATH . 'includes/class-listnest-ajax.php';
        require_once LISTNEST_PATH . 'includes/class-listnest-template.php';
        require_once LISTNEST_PATH . 'includes/class-listnest-item.php';
        require_once LISTNEST_PATH . 'includes/class-listnest-frontend.php';
        require_once LISTNEST_PATH . 'includes/class-listnest-shortcodes.php';
        require_once LISTNEST_PATH . 'includes/class-listnest-popup.php';
        require_once LISTNEST_PATH . 'includes/class-listnest-mail.php';
        require_once LISTNEST_PATH . 'includes/class-listnest-inquiries.php';
        require_once LISTNEST_PATH . 'includes/class-listnest-form.php';
        require_once LISTNEST_PATH . 'includes/class-listnest-cf7.php';
    }

    private function init_components() {
        // Admin side (list and inquiry post types) is always available.
        $inquiries = new ListNest_Inquiries();

        $this->components['cpt']       = new ListNest_CPT();
        $this->components['inquiries'] = $inquiries;

        if ( self::is_woocommerce_active() ) {
            // List storage (WooCommerce session) is passed to the components
            // that need it via constructor injection.
            $storage  = new ListNest_Session();
            $frontend = new ListNest_Frontend();
            $form     = new ListNest_Form( $storage, $frontend, $inquiries );

            $this->components['session']    = $storage;
            $this->components['frontend']   = $frontend;
            $this->components['ajax']       = new ListNest_Ajax( $storage );
            $this->components['form']       = $form;
            $this->components['shortcodes'] = new ListNest_Shortcodes( $storage, $frontend, $form );
            $this->components['popup']      = new ListNest_Popup( $frontend );
            // Hooks into Contact Form 7 only when CF7 is active (on wpcf7_init).
            $this->components['cf7']        = new ListNest_CF7( $storage, $frontend );
        } else {
            add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
        }

        foreach ( $this->components as $component ) {
            $component->register_hooks();
        }
    }

    /**
     * Admin notice shown when WooCommerce is not active.
     */
    public function woocommerce_missing_notice() {
        if ( ! current_user_can( 'activate_plugins' ) ) {
            return;
        }
        ?>
        <div class="notice notice-error is-dismissible">
            <p><?php esc_html_e( 'ListNest requires WooCommerce to be installed and active. Front-end list features are disabled until WooCommerce is activated.', 'listnest' ); ?></p>
        </div>
        <?php
    }

    /**
     * Declares compatibility with WooCommerce High-Performance Order Storage.
     */
    public function declare_wc_compatibility() {
        if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', LISTNEST_FILE, true );
        }
    }
}
