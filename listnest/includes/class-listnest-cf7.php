<?php
if (!defined('ABSPATH')) {
    exit;
}

class ListNest_CF7 {

    /**
     * Registers hooks. Called once by ListNest_Plugin (the nested function
     * declarations below would fatal if this ran twice).
     */
    public function register_hooks() {

        add_action('wpcf7_init', 'listnest_cf7_add_product_list_tag');
        function listnest_cf7_add_product_list_tag()
        {
            wpcf7_add_form_tag('listnest_products', 'listnest_cf7_product_list_callback', array('name-attr' => true));
        }

        function listnest_cf7_product_list_callback($tag = null)
        {
            if (!empty($_POST['listnest_cf7_list'])) {
                $list_key = sanitize_text_field($_POST['listnest_cf7_list']);

                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }

                if (!empty($_SESSION['listnest_lists'][$list_key])) {
                    $items = $_SESSION['listnest_lists'][$list_key];

                    $products_message = "<br><strong>Rodzaj listy: (" . esc_html($list_key) . "):</strong><br>";
                    $products_message .= "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%; max-width: 500px;'>";
                    $products_message .= "<tr>
                                    <th style='background: #f4f4f4;'>Produkt</th>
                                    <th style='background: #f4f4f4;'>Atrybuty</th>
                                    <th style='background: #f4f4f4;'>Ilość</th>
                                  </tr>";

                    foreach ($items as $product_key => $product) {
                        $product_name = 'Nieznany produkt';
                        $attributes_output = 'Brak atrybutów';

                        if (!empty($product['product_id'])) {
                            $wc_product = wc_get_product($product['product_id']);
                            if ($wc_product) {
                                $product_name = esc_html($wc_product->get_name());
                            }

                            if (!empty($product['attributes'])) {
                                $attributes_output = '';
                                foreach ($product['attributes'] as $attr_k => $attr_v) {
                                    $attr_k = str_replace('attribute_', '', $attr_k);
                                    $attribute_name = wc_attribute_label($attr_k);
                                    $term = get_term_by('slug', $attr_v, $attr_k);
                                    $value = $term ? $term->name : esc_html($attr_v);
                                    $attributes_output .= esc_html($attribute_name) . ': ' . esc_html($value) . '<br>';
                                }
                            }
                        }

                        $quantity = isset($product['quantity']) ? intval($product['quantity']) : 1;

                        $products_message .= "<tr>
                                        <td>{$product_name}</td>
                                        <td>{$attributes_output}</td>
                                        <td style='text-align: center;'>{$quantity}</td>
                                      </tr>";
                    }
                    $products_message .= "</table>";

                    return $products_message;
                }
            }
            return "";
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
