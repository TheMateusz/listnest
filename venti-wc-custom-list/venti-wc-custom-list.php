<?php
/**
 * Plugin Name: Venti WC Custom List
 * Description: Wtyczka tworząca system list produktów z obsługą AJAX i shortcodem.
 * Version:     1.0
 * Author:      Mateusz Matusiak
 * Text Domain: venti-wc-custom-list
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'includes/class-vwcl-cpt.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-vwcl-session.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-vwcl-ajax.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-vwcl-shortcodes.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-vwcl-init.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-vwcl-popup.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-vwcl-cf7.php';


function vwcl_load_textdomain() {
    $folder_relative = dirname(plugin_basename(__FILE__)) . '/languages';

    load_plugin_textdomain(
        'venti-wc-custom-list',
        false,
        $folder_relative
    );
}
add_action('plugins_loaded', 'vwcl_load_textdomain');


function vwcl_wc_custom_list_init() {
    $init = new Vwcl_Init();
    $init->setup_hooks();
}

add_action( 'plugins_loaded', 'vwcl_wc_custom_list_init' );


