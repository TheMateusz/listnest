<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AJAX endpoints: add to list and nonce refresh.
 */
class ListNest_Ajax {

    /**
     * Nonce action for the add-to-list request (guests and logged-in users).
     */
    const NONCE_ACTION = 'listnest_add_to_list';

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
        add_action('wp_ajax_listnest_add_to_list', array($this, 'ajax_add_to_list'));
        add_action('wp_ajax_nopriv_listnest_add_to_list', array($this, 'ajax_add_to_list'));
        add_action('wp_ajax_listnest_refresh_nonce', array($this, 'ajax_refresh_nonce'));
        add_action('wp_ajax_nopriv_listnest_refresh_nonce', array($this, 'ajax_refresh_nonce'));
    }

    /**
     * AJAX handler: returns a fresh add-to-list nonce.
     *
     * Pages served from a full-page cache can carry an expired nonce (or one
     * created for a different user). The script requests a new one once
     * after an "invalid_nonce" error and retries the request. The nonce only
     * protects the public add-to-list action, so handing it out is safe.
     */
    public function ajax_refresh_nonce() {
        nocache_headers();
        wp_send_json_success(array(
            'nonce' => wp_create_nonce(self::NONCE_ACTION),
        ));
    }

    /**
     * AJAX handler: adds a product (or variation) to a list.
     *
     * POST: security (nonce), list_key, product_id, variation_id (optional),
     * quantity (optional, default 1), attributes (optional JSON object).
     */
    public function ajax_add_to_list() {
        if (! check_ajax_referer(self::NONCE_ACTION, 'security', false)) {
            // The "invalid_nonce" code lets the script fetch a fresh nonce and retry once.
            wp_send_json_error(
                array(
                    'code'    => 'invalid_nonce',
                    'message' => __('Your session has expired. Please reload the page and try again.', 'listnest'),
                ),
                403
            );
        }

        $list_key     = isset($_POST['list_key']) ? sanitize_key(wp_unslash($_POST['list_key'])) : '';
        $product_id   = isset($_POST['product_id']) ? absint(wp_unslash($_POST['product_id'])) : 0;
        $variation_id = isset($_POST['variation_id']) ? absint(wp_unslash($_POST['variation_id'])) : 0;
        $quantity     = isset($_POST['quantity']) ? absint(wp_unslash($_POST['quantity'])) : 1;
        $attributes   = array();
        if (isset($_POST['attributes']) && is_string($_POST['attributes'])) {
            // Raw JSON; every decoded key and value is sanitized in sanitize_attributes().
            $attributes = json_decode(wp_unslash($_POST['attributes']), true); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        }

        if ('' === $list_key || ! ListNest_CPT::get_published_list_id($list_key)) {
            wp_send_json_error(array('message' => __('This list does not exist.', 'listnest')), 400);
        }

        $max_quantity = $this->storage->get_max_quantity();
        $quantity     = min(max(1, $quantity), $max_quantity);

        $resolved = $this->resolve_product($product_id, $variation_id);
        if (! $resolved) {
            wp_send_json_error(array('message' => __('This product cannot be added to the list.', 'listnest')), 400);
        }
        list($product_id, $variation_id, $variation) = $resolved;

        $attributes = $variation ? $this->sanitize_attributes($attributes, $variation) : array();

        $item_key = (string) ($variation_id ? $variation_id : $product_id);
        if (! empty($attributes)) {
            $item_key .= ':' . md5(wp_json_encode($attributes));
        }

        $items = $this->storage->get_list($list_key);

        if (isset($items[$item_key])) {
            $current = isset($items[$item_key]['quantity']) ? absint($items[$item_key]['quantity']) : 0;
            $items[$item_key]['quantity'] = min($current + $quantity, $max_quantity);
        } else {
            if (count($items) >= $this->storage->get_max_items()) {
                wp_send_json_error(array('message' => __('The list is full. Remove some products before adding new ones.', 'listnest')), 400);
            }
            $items[$item_key] = array(
                'product_id'   => $product_id,
                'variation_id' => $variation_id,
                'attributes'   => $attributes,
                'quantity'     => $quantity,
            );
        }

        if (! $this->storage->set_list($list_key, $items)) {
            wp_send_json_error(array('message' => __('The list could not be saved. Please try again.', 'listnest')), 500);
        }

        wp_send_json_success(array(
            'message' => __('Added to the list.', 'listnest'),
            'count'   => count($items),
        ));
    }

    /**
     * Validates the requested product/variation.
     *
     * Accepts a variation ID passed as product_id (older front-end markup)
     * and derives the parent. Only published products, and published
     * variations belonging to that product, are accepted.
     *
     * @param int $product_id   Product ID.
     * @param int $variation_id Variation ID or 0.
     * @return array|false array( product_id, variation_id, WC_Product_Variation|null ) or false.
     */
    private function resolve_product($product_id, $variation_id) {
        if ($product_id <= 0) {
            return false;
        }

        $product = wc_get_product($product_id);
        if (! $product) {
            return false;
        }

        if ($product->is_type('variation')) {
            if ($variation_id && $variation_id !== $product_id) {
                return false;
            }
            $variation_id = $product_id;
            $product_id   = $product->get_parent_id();
            $product      = $product_id ? wc_get_product($product_id) : false;
            if (! $product) {
                return false;
            }
        }

        if ('publish' !== $product->get_status()) {
            return false;
        }

        $variation = null;
        if ($variation_id) {
            $variation = wc_get_product($variation_id);
            if (
                ! $variation
                || ! $variation->is_type('variation')
                || (int) $variation->get_parent_id() !== (int) $product_id
                || 'publish' !== $variation->get_status()
            ) {
                return false;
            }
        }

        /**
         * Filters whether a product may be added to a list.
         *
         * @param bool                      $can_add   Default true.
         * @param WC_Product                $product   Parent/simple product.
         * @param WC_Product_Variation|null $variation Selected variation, if any.
         */
        if (! apply_filters('listnest_can_add_product', true, $product, $variation)) {
            return false;
        }

        return array((int) $product_id, (int) $variation_id, $variation);
    }

    /**
     * Sanitizes the attributes selected for a variation.
     *
     * Only attributes the variation actually has are kept. Attributes with a
     * fixed value on the variation use that value; "any" attributes use the
     * submitted value (slug for taxonomy attributes, plain text otherwise).
     *
     * @param mixed                $attributes Decoded attributes JSON.
     * @param WC_Product_Variation $variation  Variation.
     * @return array
     */
    private function sanitize_attributes($attributes, $variation) {
        if (! is_array($attributes)) {
            return array();
        }

        $variation_attributes = $variation->get_variation_attributes();
        $clean                = array();

        foreach ($attributes as $key => $value) {
            if (! is_string($key) || ! is_scalar($value)) {
                continue;
            }

            $key = sanitize_title($key);
            if (! array_key_exists($key, $variation_attributes)) {
                continue;
            }

            if ('' !== (string) $variation_attributes[$key]) {
                $value = (string) $variation_attributes[$key];
            } elseif (0 === strpos($key, 'attribute_pa_')) {
                $value = sanitize_title((string) $value);
            } else {
                $value = sanitize_text_field((string) $value);
            }

            if ('' === $value) {
                continue;
            }

            $clean[$key] = mb_substr($value, 0, 200);
        }

        ksort($clean);
        return $clean;
    }

}
