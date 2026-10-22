<?php

if (!defined('ABSPATH')) {
    exit;
}

class ListNest_Popup {

    public function __construct() {
        add_action('wp_footer', array($this, 'render_popup_template'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }

    /**
     * Wyświetla HTML pop-upu w `wp_footer`
     */
    public function render_popup_template() {
        ?>
        <div id="lnst-popup-modal" class="lnst-popup hidden">
            <div class="lnst-popup-content">
                <h5 id="lnst-popup-title" class="mb-4 mb-lg-6"><?php esc_html_e('Popup title', 'listnest'); ?></h5>
                <div id="lnst-popup-buttons" class="d-flex gap-5 gap-10 justify-content-center align-items-center">
                    <a href="#" id="lnst-popup-btn1" class="lnst-popup-btn lnst-popup-close">
                        <?php esc_html_e('Close', 'listnest'); ?>
                    </a>
                    <a href="#" id="lnst-popup-btn2" class="button button--outline button--mini">
                        <?php esc_html_e('Go to list', 'listnest'); ?>
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
            'lnst-popup-style',
            plugin_dir_url(__FILE__) . '../assets/css/listnest-popup.css',
            array(),
            '1.0'
        );
    }
}
