<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Prints the confirmation dialog (templates/popup.php) in the footer, only
 * on pages where the button script is loaded and at least one list uses
 * the popup option.
 */
class ListNest_Popup {

    /**
     * @var ListNest_Frontend
     */
    private $frontend;

    /**
     * @param ListNest_Frontend $frontend Front-end component.
     */
    public function __construct( ListNest_Frontend $frontend ) {
        $this->frontend = $frontend;
    }

    /**
     * Registers hooks. Called once by ListNest_Plugin.
     */
    public function register_hooks() {
        // Before footer scripts are printed (priority 20).
        add_action( 'wp_footer', array( $this, 'render_popup_template' ), 15 );
    }

    /**
     * Prints the dialog markup when needed.
     */
    public function render_popup_template() {
        if ( ! $this->frontend->is_script_needed() || ! $this->frontend->has_popup_lists() ) {
            return;
        }

        $template = ListNest_Template::locate( 'popup.php' );
        if ( '' !== $template ) {
            include $template;
        }
    }
}
