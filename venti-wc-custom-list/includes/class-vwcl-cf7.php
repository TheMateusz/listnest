<?php
if (!defined('ABSPATH')) {
    exit;
}

class Vwcl_CF7 {

    public function __construct() {


        add_action('wpcf7_init', 'my_cf7_add_product_list_tag');
        function my_cf7_add_product_list_tag()
        {
            wpcf7_add_form_tag('my_products_list', 'my_cf7_product_list_callback', array('name-attr' => true));
        }

        function my_cf7_product_list_callback($tag = null)
        {
            if (!empty($_POST['get_product_list'])) {
                $list_key = sanitize_text_field($_POST['get_product_list']);

                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }

                if (!empty($_SESSION['vwcl_lists'][$list_key])) {
                    $items = $_SESSION['vwcl_lists'][$list_key];

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


        add_filter('wpcf7_special_mail_tags', 'my_cf7_special_mail_tag_my_products_list', 10, 3);
        function my_cf7_special_mail_tag_my_products_list($output, $name, $html)
        {
            if ($name === 'my_products_list') {
                return my_cf7_product_list_callback();
            }
            return $output;
        }


    }

}
