<?php
if (!defined('ABSPATH')) {
    exit;
}

class ListNest_CF7 {

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
     * Returns the list storage.
     *
     * @return ListNest_Session
     */
    public function get_storage() {
        return $this->storage;
    }

    /**
     * Registers hooks. Called once by ListNest_Plugin (the nested function
     * declarations below would fatal if this ran twice).
     *
     * The nested functions cannot use $this; they reach the injected storage
     * through ListNest_Plugin::instance()->get_component('cf7')->get_storage().
     * Stage 4 rewrites them as class methods.
     */
    public function register_hooks() {

        add_action('wpcf7_init', 'listnest_cf7_add_product_list_tag');
        function listnest_cf7_add_product_list_tag()
        {
            wpcf7_add_form_tag('listnest_products', 'listnest_cf7_product_list_callback', array('name-attr' => true));
        }

        function listnest_cf7_product_list_callback($tag = null)
        {
            // CF7 verifies its own submission; this only reads the visitor's own list.
            if (empty($_POST['listnest_cf7_list'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                return '';
            }

            $list_key = sanitize_key(wp_unslash($_POST['listnest_cf7_list'])); // phpcs:ignore WordPress.Security.NonceVerification.Missing
            if ('' === $list_key) {
                return '';
            }

            $cf7 = ListNest_Plugin::instance()->get_component('cf7');
            if (!$cf7) {
                return '';
            }
            $storage = $cf7->get_storage();

            // CF7 submits through the REST API, where WooCommerce does not load its session by default.
            $storage->load_session();
            $items = $storage->get_list($list_key);
            if (empty($items)) {
                return '';
            }

            $products_message = "<br><strong>Rodzaj listy: (" . esc_html($list_key) . "):</strong><br>";
            $products_message .= "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%; max-width: 500px;'>";
            $products_message .= "<tr>
                            <th style='background: #f4f4f4;'>Produkt</th>
                            <th style='background: #f4f4f4;'>Atrybuty</th>
                            <th style='background: #f4f4f4;'>Ilość</th>
                          </tr>";

            foreach ($items as $product) {
                if (!is_array($product)) {
                    continue;
                }

                $product_name      = esc_html('Nieznany produkt');
                $attributes_output = esc_html('Brak atrybutów');

                if (!empty($product['product_id'])) {
                    $wc_product = wc_get_product(absint($product['product_id']));
                    if ($wc_product) {
                        $product_name = esc_html($wc_product->get_name());
                    }

                    if (!empty($product['attributes']) && is_array($product['attributes'])) {
                        $attributes_output = '';
                        foreach ($product['attributes'] as $attr_k => $attr_v) {
                            if (!is_scalar($attr_v)) {
                                continue;
                            }
                            $attr_k         = str_replace('attribute_', '', (string) $attr_k);
                            $attribute_name = wc_attribute_label($attr_k);
                            $term           = taxonomy_exists($attr_k) ? get_term_by('slug', (string) $attr_v, $attr_k) : false;
                            $value          = $term ? $term->name : (string) $attr_v;
                            $attributes_output .= esc_html($attribute_name) . ': ' . esc_html($value) . '<br>';
                        }
                    }
                }

                $quantity = isset($product['quantity']) ? absint($product['quantity']) : 1;

                $products_message .= "<tr>
                                <td>{$product_name}</td>
                                <td>{$attributes_output}</td>
                                <td style='text-align: center;'>" . esc_html($quantity) . "</td>
                              </tr>";
            }
            $products_message .= "</table>";

            return $products_message;
        }


        add_filter('wpcf7_special_mail_tags', 'listnest_cf7_special_mail_tag', 10, 3);
        function listnest_cf7_special_mail_tag($output, $name, $html)
        {
            if ($name === 'listnest_products') {
                return listnest_cf7_product_list_callback();
            }
            return $output;
        }


    }

}
