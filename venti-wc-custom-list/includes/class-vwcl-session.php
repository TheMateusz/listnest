<?php
if ( ! defined('ABSPATH') ) {
    exit;
}

class Vwcl_Session {

    public function maybe_start_session() {
        if ( ! session_id() ) {
            session_start();
        }
    }
}
