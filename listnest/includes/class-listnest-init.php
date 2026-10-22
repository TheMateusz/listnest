<?php
if ( ! defined('ABSPATH') ) {
    exit;
}

class ListNest_Init {

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }


    public function setup_hooks() {
        $cpt = new ListNest_CPT();
        add_action('init', array($cpt, 'register_lists_post_type'));

        $session = new ListNest_Session();
        add_action('init', array($session, 'maybe_start_session'), 1);

        $ajax = new ListNest_Ajax();
        add_action('wp_ajax_lnst_add_to_list', array($ajax, 'ajax_add_to_list'));
        add_action('wp_ajax_nopriv_lnst_add_to_list', array($ajax, 'ajax_add_to_list'));
        add_action('wp_enqueue_scripts', array($ajax, 'enqueue_scripts'));

        $shortcodes = new ListNest_Shortcodes();
        add_shortcode('listnest', array($shortcodes, 'render_list_shortcode'));

        $popup = new ListNest_Popup();
        $cf7 = new ListNest_CF7();

        add_action('wp', array($shortcodes, 'handle_update_list'));
    }

    public function enqueue_styles() {
        wp_enqueue_style(
            'lnst-style',
            plugin_dir_url(__FILE__) . '../assets/css/listnest.css',
            array(),
            '1.0'
        );
    }
}
