<?php
/**
 * Szablon: list-div.php
 * Renderuje listę produktów w formie divów z użyciem Bootstrap.
 *
 * Dostępne zmienne:
 *  - $items (tablica)
 *  - $list_key (string)
 */
?>

<div class="venti-list-wrapper">
    <?php if (empty($items)) : ?>
        <p class="text-muted"><?php esc_html_e('No products on the list.', 'venti-wc-custom-list'); ?></p>
    <?php else : ?>
        <form method="post">
            <input type="hidden" name="vwcl_list_key" value="<?php echo esc_attr($list_key); ?>" />

            <div class="venti-list">

                <div class="col-12 d-flex align-items-center border-bottom border-top py-3 venti-list-item">
                    <div class="mx-3 mx-lg-6" style="width: 30px;"></div>
                    <div class="flex-grow-1 fw-semibold">
                        <?php esc_html_e('PRODUCT', 'venti-wc-custom-list'); ?>
                    </div>
                    <div class="fw-semibold d-flex justify-content-center" style="min-width: 80px">
                        <?php esc_html_e('QUANTITY', 'venti-wc-custom-list'); ?>
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

                    if ($product instanceof WC_Product_Variation) {
                        $product_id = $product->get_parent_id();
                        $product = wc_get_product($product_id);
                    }

                    $product_image = wp_get_attachment_image_src($product->get_image_id(), 'thumbnail');

                    ?>
                    <div class="col-12 d-flex align-items-center border-bottom py-3 venti-list-item">
                        <button type="submit" name="vwcl_remove_item" value="<?php echo esc_attr($item_key); ?>" class="me-3 venti-remove-btn mx-3 mx-lg-6">✖</button>
                        <?php if ($product_image) : ?>
                            <div class="d-flex align-items-center">
                                <div class="me-3" style="width: 80px; min-width: 80px;">
                                    <img src="<?php echo esc_url($product_image[0]); ?>" alt="" style="width: 100%;">
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="flex-grow-1">
                            <p class="fw-bold mb-1 venti-product-name"><?php echo esc_html($product->get_name()); ?></p>

                            <?php if ($attributes) : ?>
                                <div class="venti-product-attributes">
                                    <?php foreach ($attributes as $attr_k => $attr_v) :
                                        $attr_k = str_replace('attribute_', '', $attr_k);
                                        $attribute_name = wc_attribute_label($attr_k);
                                        $term = get_term_by('slug', $attr_v, $attr_k);
                                        $value = $term ? $term->name : esc_html($attr_v);

                                        ?>
                                        <div><?php echo esc_html($attribute_name); ?>: <?php echo esc_html($value); ?></div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else : ?>
                                <em class="text-muted"><?php esc_html_e('No', 'venti-wc-custom-list'); ?></em>
                            <?php endif; ?>
                        </div>
                        <div>
                            <input type="number"
                                   class="form-control text-center"
                                   name="vwcl_items[<?php echo esc_attr($item_key); ?>][quantity]"
                                   value="<?php echo esc_attr($quantity); ?>"
                                   min="0" style="width: 80px;" />
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="d-flex justify-content-end border-bottom py-2 py-lg-4">
                <button type="submit" class="button button--outline button--mini" name="vwcl_update_list" value="1"><?php esc_html_e('Update the list', 'venti-wc-custom-list'); ?></button>
            </div>
        </form>
    <?php endif; ?>
</div>