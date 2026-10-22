<?php

if (!defined('ABSPATH')) {
    exit;
}

class ListNest_Popup {

    /**
     * Registers hooks. Called once by ListNest_Plugin.
     */
    public function register_hooks() {
        add_action('wp_footer', array($this, 'render_popup_template'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }

    /**
     * Wyświetla HTML pop-upu w `wp_footer`
     */
    public function render_popup_template() {
        ?>
        <div id="listnest-popup-modal" class="listnest-popup hidden">
            <div class="listnest-popup-content">
                <h5 id="listnest-popup-title" class="mb-4 mb-lg-6"><?php esc_html_e('Popup title', 'listnest'); ?></h5>
                <div id="listnest-popup-buttons" class="d-flex gap-5 gap-10 justify-content-center align-items-center">
                    <a href="#" id="listnest-popup-btn1" class="listnest-popup-btn listnest-popup-close">
                        <?php esc_html_e('Close', 'listnest'); ?>
                    </a>
                    <a href="#" id="listnest-popup-btn2" class="button button--outline button--mini">
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
            'listnest-popup-style',
            LISTNEST_URL . 'assets/css/listnest-popup.css',
            array(),
            LISTNEST_VERSION
        );
    }
}
