<?php
if (!defined('ABSPATH')) {
    exit;
}

class ListNest_Shortcodes {

    /**
     * Nonce action / field for the list update form.
     */
    const NONCE_ACTION = 'listnest_update_list';
    const NONCE_FIELD  = 'listnest_list_nonce';

    /**
     * List storage.
     *
     * @var ListNest_Session
     */
    private $storage;

    /**
     * @param ListNest_Session $storage List storage.
     */
    public function __construct(ListNest_Session $storage) {
        $this->storage = $storage;
    }

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

        $list_key = sanitize_key($atts['list_key']);
        if (empty($list_key)) {
            return '<p><strong>' . esc_html__('No list_key!', 'listnest') . '</strong></p>';
        }

        $items         = $this->storage->get_list($list_key);
        $template_path = $this->locate_list_template($list_key);

        ob_start();
        include $template_path;
        return ob_get_clean();
    }

    /**
     * Resolves the list table template: templates/{list_key}-list-table.php
     * when it exists, otherwise templates/list-table.php. The resolved path
     * must stay inside the plugin templates directory.
     *
     * @param string $list_key Sanitized list key.
     * @return string Absolute template path.
     */
    private function locate_list_template($list_key) {
        $templates_dir = realpath(LISTNEST_PATH . 'templates');
        $default       = LISTNEST_PATH . 'templates/list-table.php';
        $list_key      = sanitize_key($list_key);

        if ('' === $list_key || false === $templates_dir) {
            return $default;
        }

        $candidate = realpath($templates_dir . DIRECTORY_SEPARATOR . $list_key . '-list-table.php');
        if (false !== $candidate && 0 === strpos($candidate, $templates_dir . DIRECTORY_SEPARATOR) && is_file($candidate)) {
            return $candidate;
        }

        return $default;
    }

    /**
     * Handles the list update form (quantity changes and item removal).
     */
    public function handle_update_list() {
        if (!isset($_POST['listnest_list_key'])) {
            return;
        }

        if (
            !isset($_POST[self::NONCE_FIELD]) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[self::NONCE_FIELD])), self::NONCE_ACTION)
        ) {
            wp_die(
                esc_html__('Your session has expired. Please go back, reload the page and try again.', 'listnest'),
                esc_html__('List not updated', 'listnest'),
                array('response' => 403, 'back_link' => true)
            );
        }

        $list_key = sanitize_key(wp_unslash($_POST['listnest_list_key']));
        if ('' === $list_key) {
            return;
        }

        $items = $this->storage->get_list($list_key);
        if (empty($items)) {
            return;
        }

        $max_quantity = $this->storage->get_max_quantity();

        if (!empty($_POST['listnest_items']) && is_array($_POST['listnest_items'])) {
            $posted_items = map_deep(wp_unslash($_POST['listnest_items']), 'absint');

            foreach ($posted_items as $raw_key => $vals) {
                $item_key = ListNest_Session::sanitize_item_key($raw_key);
                if ('' === $item_key || !isset($items[$item_key]) || !is_array($vals) || !isset($vals['quantity'])) {
                    continue;
                }

                $qty = (int) $vals['quantity'];
                if ($qty <= 0) {
                    unset($items[$item_key]);
                } else {
                    $items[$item_key]['quantity'] = min($qty, $max_quantity);
                }
            }
        }

        if (!empty($_POST['listnest_remove_item'])) {
            $remove_key = ListNest_Session::sanitize_item_key(sanitize_text_field(wp_unslash($_POST['listnest_remove_item'])));
            if ('' !== $remove_key && isset($items[$remove_key])) {
                unset($items[$remove_key]);
            }
        }

        $this->storage->set_list($list_key, $items);

        $referer = wp_get_referer();
        wp_safe_redirect(add_query_arg('listnest_list_updated', '1', $referer ? $referer : home_url('/')));
        exit;
    }
}
