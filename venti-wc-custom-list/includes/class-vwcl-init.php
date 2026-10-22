<?php
if ( ! defined('ABSPATH') ) {
    exit;
}

class Vwcl_Init {

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }


    public function setup_hooks() {
        $cpt = new Vwcl_CPT();
        add_action('init', array($cpt, 'register_lists_post_type'));

        $session = new Vwcl_Session();
        add_action('init', array($session, 'maybe_start_session'), 1);

        $ajax = new Vwcl_Ajax();
        add_action('wp_ajax_vwcl_add_to_list', array($ajax, 'ajax_add_to_list'));
        add_action('wp_ajax_nopriv_vwcl_add_to_list', array($ajax, 'ajax_add_to_list'));
        add_action('wp_enqueue_scripts', array($ajax, 'enqueue_scripts'));

        $shortcodes = new Vwcl_Shortcodes();
        add_shortcode('vwcl_list', array($shortcodes, 'render_list_shortcode'));

        $popup = new Vwcl_Popup();
        $cf7 = new Vwcl_CF7();

        add_action('wp', array($shortcodes, 'handle_update_list'));
    }

    public function enqueue_styles() {
        wp_enqueue_style(
            'vwcl-style',
            plugin_dir_url(__FILE__) . '../assets/css/vwcl.css',
            array(),
            '1.0'
        );
    }
}
