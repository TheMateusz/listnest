<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * [listnest] shortcode (list table) and the list update form handler.
 */
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
     * Front-end component (assets).
     *
     * @var ListNest_Frontend
     */
    private $frontend;

    /**
     * Built-in inquiry form (shown below the list when enabled), optional.
     *
     * @var ListNest_Form|null
     */
    private $form;

    /**
     * @param ListNest_Session   $storage  List storage.
     * @param ListNest_Frontend  $frontend Front-end component.
     * @param ListNest_Form|null $form     Built-in inquiry form.
     */
    public function __construct(ListNest_Session $storage, ListNest_Frontend $frontend, $form = null) {
        $this->storage  = $storage;
        $this->frontend = $frontend;
        $this->form     = ($form instanceof ListNest_Form) ? $form : null;
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
     *
     * The list is personal (session based), so the page must not be served
     * from a full-page cache: DONOTCACHEPAGE is defined while rendering.
     * Headers are already sent at this point, so caching plugins that do
     * not honour DONOTCACHEPAGE need the list page excluded manually.
     *
     * @param array|string $atts Shortcode attributes.
     * @return string
     */
    public function render_list_shortcode($atts) {
        $atts = shortcode_atts(array(
            'list_key' => ''
        ), $atts, 'listnest');

        $list_key = sanitize_key($atts['list_key']);
        if (empty($list_key)) {
            return '<p><strong>' . esc_html__('ListNest: the list_key shortcode attribute is missing.', 'listnest') . '</strong></p>';
        }

        if (! defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Standard caching plugin constant.
        }

        $template_path = $this->locate_list_template($list_key);
        if ('' === $template_path) {
            return '';
        }

        $this->frontend->enqueue_style();

        // Variables available in the template (documented in templates/list-table.php).
        $items        = $this->storage->get_list($list_key);
        $rows         = ListNest_Item::prepare_rows($items, true);
        $max_quantity = $this->storage->get_max_quantity();
        $nonce_action = self::NONCE_ACTION;
        $nonce_field  = self::NONCE_FIELD;
        $updated      = isset($_GET['listnest_list_updated']) && is_string($_GET['listnest_list_updated']) && '1' === sanitize_key(wp_unslash($_GET['listnest_list_updated'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only flag set by our own redirect.

        $html = $this->render_template($template_path, compact('list_key', 'items', 'rows', 'max_quantity', 'nonce_action', 'nonce_field', 'updated'));

        if ($this->form && $this->form->is_auto_form($list_key)) {
            $form_html = $this->form->render($list_key);
            // Right after sending, the thank-you message goes above the (now empty) list.
            $html = $this->form->is_sent($list_key) ? $form_html . $html : $html . $form_html;
        }

        return $html;
    }

    /**
     * Includes a template with an explicit, whitelisted set of variables.
     *
     * @param string $template_path Absolute template path.
     * @param array  $vars          Variables (list_key, items, rows, max_quantity, nonce_action, nonce_field, updated).
     * @return string
     */
    private function render_template($template_path, array $vars) {
        $list_key     = isset($vars['list_key']) ? $vars['list_key'] : '';
        $items        = isset($vars['items']) ? $vars['items'] : array();
        $rows         = isset($vars['rows']) ? $vars['rows'] : array();
        $max_quantity = isset($vars['max_quantity']) ? $vars['max_quantity'] : 1;
        $nonce_action = isset($vars['nonce_action']) ? $vars['nonce_action'] : self::NONCE_ACTION;
        $nonce_field  = isset($vars['nonce_field']) ? $vars['nonce_field'] : self::NONCE_FIELD;
        $updated      = ! empty($vars['updated']);

        ob_start();
        include $template_path;
        return ob_get_clean();
    }

    /**
     * Resolves the list table template. First match wins:
     *  1. {child theme}/listnest/{list_key}-list-table.php
     *  2. {child theme}/listnest/list-table.php
     *  3. {parent theme}/listnest/{list_key}-list-table.php
     *  4. {parent theme}/listnest/list-table.php
     *  5. {plugin}/templates/{list_key}-list-table.php
     *  6. {plugin}/templates/list-table.php
     * The result can be changed with the listnest_locate_template filter.
     *
     * @param string $list_key Sanitized list key.
     * @return string Absolute template path, or '' when none exists.
     */
    private function locate_list_template($list_key) {
        $list_key = sanitize_key($list_key);
        $names    = array('list-table.php');
        if ('' !== $list_key) {
            array_unshift($names, $list_key . '-list-table.php');
        }

        return ListNest_Template::locate($names, $list_key);
    }

    /**
     * Handles the list update form (quantity changes and item removal).
     */
    public function handle_update_list() {
        if (!isset($_POST['listnest_list_key'])) {
            return;
        }
        foreach (array('listnest_list_key', self::NONCE_FIELD, 'listnest_remove_item') as $field) {
            if (isset($_POST[$field]) && ! is_string($_POST[$field])) {
                wp_die(esc_html__('List not updated', 'listnest'), '', array('response' => 400));
            }
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
                if ('' === $item_key || !isset($items[$item_key]) || !is_array($vals) || !isset($vals['quantity']) || !is_int($vals['quantity'])) {
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

        if (! $this->storage->set_list($list_key, $items)) {
            wp_die(esc_html__('List not updated', 'listnest'), '', array('response' => 500));
        }

        $url = ListNest_Frontend::get_return_url(array('listnest_sent', 'listnest_list_updated'));
        wp_safe_redirect(add_query_arg('listnest_list_updated', '1', $url));
        exit;
    }
}
