<?php

if (!defined('ABSPATH')) {
    exit;
}

class Vwcl_Popup {

    public function __construct() {
        add_action('wp_footer', array($this, 'render_popup_template'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }

    /**
     * Wyświetla HTML pop-upu w `wp_footer`
     */
    public function render_popup_template() {
        ?>
        <div id="vwcl-popup-modal" class="vwcl-popup hidden">
            <div class="vwcl-popup-content">
                <h5 id="vwcl-popup-title" class="mb-4 mb-lg-6"><?php esc_html_e('Popup title', 'venti-wc-custom-list'); ?></h5>
                <div id="vwcl-popup-buttons" class="d-flex gap-5 gap-10 justify-content-center align-items-center">
                    <a href="#" id="vwcl-popup-btn1" class="vwcl-popup-btn vwcl-popup-close">
                        <?php esc_html_e('Close', 'venti-wc-custom-list'); ?>
                    </a>
                    <a href="#" id="vwcl-popup-btn2" class="button button--outline button--mini">
                        <?php esc_html_e('Go to list', 'venti-wc-custom-list'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Rejestruje i enqueue’uje plik CSS dla pop-upu
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            'vwcl-popup-style',
            plugin_dir_url(__FILE__) . '../assets/css/vwcl-popup.css',
            array(),
            '1.0'
        );
    }
}
