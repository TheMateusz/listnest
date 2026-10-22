<?php
if (!defined('ABSPATH')) {
    exit;
}

class ListNest_Ajax {

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_lnst_add_to_list', array($this, 'ajax_add_to_list'));
        add_action('wp_ajax_nopriv_lnst_add_to_list', array($this, 'ajax_add_to_list'));
    }

    /**
     * Ładowanie pliku JS na froncie
     */
    public function enqueue_scripts() {
        $lists_config = $this->get_lists_config();
        $nonce_action = is_user_logged_in() ? 'lnst_nonce' : 'lnst_nonce_nopriv';

        wp_register_script('lnst_add_to_list_js', plugin_dir_url(dirname(__FILE__)) . 'assets/js/add-to-list.js', array(), '1.0', true);
        wp_localize_script('lnst_add_to_list_js', 'ListNestConfig', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce($nonce_action),
            'lists'   => $lists_config,
        ));

        wp_enqueue_script('lnst_add_to_list_js');
    }

    /**
     * Pobiera konfigurację list z bazy danych (np. zapisane w metaboxach)
     */
    private function get_lists_config() {
        $lists = array();

        $query = new WP_Query(array(
            'post_type'      => 'lnst_list',
            'posts_per_page' => -1
        ));

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $list_key = get_post_field('post_name', get_post());

                $option = get_post_meta(get_the_ID(), '_lnst_list_option', true);

                $redirect_page_id = get_post_meta(get_the_ID(), '_lnst_list_redirect_page', true);
                if ($redirect_page_id && function_exists('pll_get_post')) {
                    $redirect_url = pll_get_post($redirect_page_id);
                } else {
                    $redirect_url = $redirect_page_id;
                }

                $button_text = get_post_meta(get_the_ID(), '_lnst_list_button_text', true);
                $button_page = get_post_meta(get_the_ID(), '_lnst_list_button_page', true);

                if ($button_page && function_exists('pll_get_post')) {
                    $redirect_button_url = pll_get_post($button_page);
                } else {
                    $redirect_button_url = $button_page;
                }

                $btn2_page = get_post_meta(get_the_ID(), '_lnst_list_popup_btn2_page', true);

                if ($btn2_page && function_exists('pll_get_post')) {
                    $redirect_btn2_url = pll_get_post($btn2_page);
                } else {
                    $redirect_btn2_url = $btn2_page;
                }

                $popup = array(
                    'title'     => get_post_meta(get_the_ID(), '_lnst_list_popup_title', true),
                    'btn1Text'  => get_post_meta(get_the_ID(), '_lnst_list_popup_btn1_text', true),
                    'btn2Text'  => get_post_meta(get_the_ID(), '_lnst_list_popup_btn2_text', true),
                    'btn2Url'   => $btn2_page,
                );

                $lists[$list_key] = array(
                    'option'       => $option,
                    'redirect_url' => !empty($redirect_url) ? get_permalink($redirect_url) : '',
                    'button_text'  => !empty($button_text) ? $button_text : __('Add to list', 'listnest'),
                    'button_url'   => !empty($redirect_button_url) ? get_permalink($redirect_button_url) : '',
                    'popup'        => array(
                        'title'    => !empty($popup['title']) ? $popup['title'] : __('Popup title', 'listnest'),
                        'btn1Text' => !empty($popup['btn1Text']) ? $popup['btn1Text'] : __('Close', 'listnest'),
                        'btn2Text' => !empty($popup['btn2Text']) ? $popup['btn2Text'] : __('Go to list', 'listnest'),
                        'btn2Url'  => !empty($redirect_btn2_url) ? get_permalink($redirect_btn2_url) : '',
                    )
                );
            }
        }
        wp_reset_postdata();
        return $lists;
    }

    /**
     * Obsługa żądania AJAX: dodanie produktu do listy
     */
    public function ajax_add_to_list() {
        $product_id   = isset($_POST['product_id'])   ? intval($_POST['product_id'])   : 0;
        $variation_id = isset($_POST['variation_id']) ? intval($_POST['variation_id']) : 0;
        $quantity     = isset($_POST['quantity'])     ? intval($_POST['quantity'])     : 1;
        $list_key     = isset($_POST['list_key'])     ? sanitize_text_field($_POST['list_key']) : '';
        $attributes   = isset($_POST['attributes'])   ? json_decode(stripslashes($_POST['attributes']), true) : [];

        if (empty($list_key) || $product_id <= 0) {
            wp_send_json_error(array('message' => 'Błędne dane (list_key lub product_id).'));
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['lnst_lists'])) {
            $_SESSION['lnst_lists'] = array();
        }
        if (!isset($_SESSION['lnst_lists'][$list_key])) {
            $_SESSION['lnst_lists'][$list_key] = array();
        }

        $item_key = $variation_id ? $variation_id : $product_id;
        if (!empty($attributes)) {
            $item_key .= ':' . md5(json_encode($attributes));
        }

        if (isset($_SESSION['lnst_lists'][$list_key][$item_key])) {
            $_SESSION['lnst_lists'][$list_key][$item_key]['quantity'] += $quantity;
        } else {
            $_SESSION['lnst_lists'][$list_key][$item_key] = array(
                'product_id'   => $product_id,
                'variation_id' => $variation_id,
                'attributes'   => $attributes,
                'quantity'     => $quantity,
            );
        }

        wp_send_json_success(array('message' => 'Dodano do listy', 'session' => $_SESSION['lnst_lists'][$list_key]));
    }

}

new ListNest_Ajax();
