<?php
/**
 * Szablon: list-div.php
 * Renderuje listę produktów w formie divów z użyciem Bootstrap.
 *
 * Dostępne zmienne:
 *  - $items (tablica)
 *  - $list_key (string)
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="listnest-list-wrapper">
    <?php if (empty($items)) : ?>
        <p class="text-muted"><?php esc_html_e('No products on the list.', 'listnest'); ?></p>
    <?php else : ?>
        <form method="post">
            <input type="hidden" name="listnest_list_key" value="<?php echo esc_attr($list_key); ?>" />
            <?php wp_nonce_field('listnest_update_list', 'listnest_list_nonce'); ?>

            <div class="listnest-list">

                <div class="col-12 d-flex align-items-center border-bottom border-top py-3 listnest-list-item">
                    <div class="mx-3 mx-lg-6" style="width: 30px;"></div>
                    <div class="flex-grow-1 fw-semibold">
                        <?php esc_html_e('PRODUCT', 'listnest'); ?>
                    </div>
                    <div class="fw-semibold d-flex justify-content-center" style="min-width: 80px">
                        <?php esc_html_e('QUANTITY', 'listnest'); ?>
                    </div>
                </div>

                <?php foreach ($items as $item_key => $data) :
                    $product_id   = $data['product_id'];
                    $variation_id = $data['variation_id'];
                    $quantity     = $data['quantity'];
                    $attributes   = !empty($data['attributes']) ? $data['attributes'] : array();

                    $product = $variation_id ? wc_get_product($variation_id) : wc_get_product($product_id);
                    if (!$product) {
                        continue;
                    }
                    ?>
                    <div class="col-12 d-flex align-items-center border-bottom py-3 listnest-list-item">
                        <button type="submit" name="listnest_remove_item" value="<?php echo esc_attr($item_key); ?>" class="me-3 listnest-remove-btn mx-3 mx-lg-6">✖</button>
                        <div class="flex-grow-1">
                            <p class="fw-bold mb-1 listnest-product-name"><?php echo esc_html($product->get_name()); ?></p>

                            <?php if ($attributes) : ?>
                                <div class="listnest-product-attributes">
                                    <?php foreach ($attributes as $attr_k => $attr_v) :
                                        $attribute_name = wc_attribute_label($attr_k);
                                        $term = get_term_by('slug', $attr_v, $attr_k);
                                        $value = $term ? $term->name : $attr_v;

                                        ?>
                                        <div><?php echo esc_html($attribute_name); ?>: <?php echo esc_html($value); ?></div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else : ?>
                                <em class="text-muted"><?php esc_html_e('No', 'listnest'); ?></em>
                            <?php endif; ?>
                        </div>
                        <div>
                            <input type="number"
                                   class="form-control text-center"
                                   name="listnest_items[<?php echo esc_attr($item_key); ?>][quantity]"
                                   value="<?php echo esc_attr($quantity); ?>"
                                   min="0" style="width: 80px;" />
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="d-flex justify-content-end border-bottom py-2 py-lg-4">
                <button type="submit" class="button button--outline button--mini" name="listnest_update_list" value="1"><?php esc_html_e('Update the list', 'listnest'); ?></button>
            </div>
        </form>
    <?php endif; ?>
</div>