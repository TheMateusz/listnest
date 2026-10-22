<?php
/**
 * Plugin Name:          ListNest
 * Description:          Let customers collect products into custom lists — quote requests, samples, wishlists — and send them with the built-in inquiry form or Contact Form 7.
 * Version:              0.1.0
 * Author:               Mateusz Matusiak
 * License:              GPL-2.0-or-later
 * License URI:          https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least:    6.5
 * Requires PHP:         7.4
 * Requires Plugins:     woocommerce
 * WC requires at least: 7.0
 * WC tested up to:      9.3
 * Text Domain:          listnest
 * Domain Path:          /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'LISTNEST_VERSION', '0.1.0' );
define( 'LISTNEST_FILE', __FILE__ );
define( 'LISTNEST_PATH', plugin_dir_path( __FILE__ ) );
define( 'LISTNEST_URL', plugin_dir_url( __FILE__ ) );

require_once LISTNEST_PATH . 'includes/class-listnest-plugin.php';

add_action( 'plugins_loaded', array( 'ListNest_Plugin', 'instance' ) );
