<?php
if (!defined('ABSPATH')) {
    exit;
}

class ListNest_Shortcodes {

    /**
     * Registers hooks. Called once by ListNest_Plugin.
     */
    public function register_hooks() {
        add_shortcode('listnest', array($this, 'render_list_shortcode'));
        add_action('wp', array($this, 'handle_update_list'));
    }

    /**
     * Shortcode: [listnest list_key="samples"]
     */
    public function render_list_shortcode($atts) {
        $atts = shortcode_atts(array(
            'list_key' => ''
        ), $atts, 'listnest');

        $list_key = $atts['list_key'];
        if (empty($list_key)) {
            return '<p><strong>' . esc_html__('No list_key!', 'listnest') . '</strong></p>';
        }

        if (!isset($_SESSION['listnest_lists'][$list_key])) {
            $_SESSION['listnest_lists'][$list_key] = array();
        }
        $items = $_SESSION['listnest_lists'][$list_key];

        $template_path = LISTNEST_PATH . "templates/{$list_key}-list-table.php";
        if (!file_exists($template_path)) {
            $template_path = LISTNEST_PATH . 'templates/list-table.php';
        }

        ob_start();
        include $template_path;
        return ob_get_clean();
    }


    /**
     * Obsługa aktualizacji listy (formularz w tabeli)
     */
    public function handle_update_list() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!empty($_POST['listnest_list_key'])) {
            $list_key = sanitize_text_field($_POST['listnest_list_key']);

            if (!isset($_SESSION['listnest_lists'][$list_key])) {
                return;
            }

            $items = $_SESSION['listnest_lists'][$list_key];

            if (!empty($_POST['listnest_items']) && is_array($_POST['listnest_items'])) {
                foreach ($_POST['listnest_items'] as $item_key => $vals) {
                    $qty = isset($vals['quantity']) ? intval($vals['quantity']) : 1;
                    if ($qty <= 0) {
                        unset($items[$item_key]);
                    } else {
                        $items[$item_key]['quantity'] = $qty;
                    }
                }
            }

            if (isset($_POST['listnest_remove_item']) && !empty($_POST['listnest_remove_item'])) {
                $remove_key = sanitize_text_field($_POST['listnest_remove_item']);
                if (isset($items[$remove_key])) {
                    unset($items[$remove_key]);
                }
            }

            $_SESSION['listnest_lists'][$list_key] = $items;

            wp_safe_redirect(add_query_arg('listnest_list_updated', '1', wp_get_referer()));
            exit;
        }
    }
}
