<?php
/**
 * Plugin Name:       ListNest
 * Description:       Let customers collect products into custom lists — quote requests, samples, wishlists — and send them through Contact Form 7.
 * Version:           0.1.0
 * Author:            Mateusz Matusiak
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * Text Domain:       listnest
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'includes/class-listnest-cpt.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-listnest-session.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-listnest-ajax.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-listnest-shortcodes.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-listnest-init.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-listnest-popup.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-listnest-cf7.php';


function lnst_load_textdomain() {
    $folder_relative = dirname(plugin_basename(__FILE__)) . '/languages';

    load_plugin_textdomain(
        'listnest',
        false,
        $folder_relative
    );
}
add_action('plugins_loaded', 'lnst_load_textdomain');


function lnst_init() {
    $init = new ListNest_Init();
    $init->setup_hooks();
}

add_action( 'plugins_loaded', 'lnst_init' );


