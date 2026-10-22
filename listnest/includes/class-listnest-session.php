<?php
if ( ! defined('ABSPATH') ) {
    exit;
}

class ListNest_Session {

    /**
     * Registers hooks. Called once by ListNest_Plugin.
     */
    public function register_hooks() {
        add_action('init', array($this, 'maybe_start_session'), 1);
    }

    public function maybe_start_session() {
        if ( ! session_id() ) {
            session_start();
        }
    }
}
