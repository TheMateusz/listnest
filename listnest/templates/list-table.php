<?php
/**
 * List table, rendered by the [listnest list_key="..."] shortcode.
 *
 * Override: copy to yourtheme/listnest/list-table.php
 * (or yourtheme/listnest/{list_key}-list-table.php for one list).
 *
 * The update form must keep: the listnest_list_key hidden field, the nonce
 * field, the listnest_items[{item_key}][quantity] inputs (0 removes the
 * item) and the listnest_remove_item submit buttons (value = item key).
 *
 * Available variables:
 *
 * @var string $list_key     List key (list post slug).
 * @var array  $items        Raw stored items keyed by item key.
 * @var array  $rows         Display rows, each an array with:
 *                           - item_key   (string)     Item key.
 *                           - product    (WC_Product) Variation or simple product.
 *                           - parent     (WC_Product) Parent (or the same simple) product.
 *                           - name       (string)     Product name (plain text).
 *                           - permalink  (string)     Product URL, '' when not visible.
 *                           - image      (string)     Thumbnail HTML.
 *                           - attributes (array)      List of array( 'label' => string, 'value' => string ).
 *                           - quantity   (int)        Quantity.
 * @var int    $max_quantity Maximum quantity per item.
 * @var string $nonce_action Nonce action for the update form.
 * @var string $nonce_field  Nonce field name for the update form.
 * @var bool   $updated      Whether the list has just been updated.
 *
 * @package ListNest
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="listnest listnest-list" id="<?php echo esc_attr( 'listnest-list-' . $list_key ); ?>">
    <?php if ( $updated ) : ?>
        <p class="listnest-notice listnest-notice--success" role="status"><?php esc_html_e( 'The list has been updated.', 'listnest' ); ?></p>
    <?php endif; ?>

    <?php if ( empty( $rows ) ) : ?>
        <p class="listnest-list__empty"><?php esc_html_e( 'No products on the list.', 'listnest' ); ?></p>
    <?php else : ?>
        <form method="post" class="listnest-list__form">
            <input type="hidden" name="listnest_list_key" value="<?php echo esc_attr( $list_key ); ?>" />
            <?php wp_nonce_field( $nonce_action, $nonce_field ); ?>

            <?php // Default submit button: pressing Enter in a quantity field updates the list instead of removing the first item. ?>
            <button type="submit" name="listnest_update_list" value="1" class="listnest-default-submit" tabindex="-1" aria-hidden="true"><?php esc_html_e( 'Update list', 'listnest' ); ?></button>

            <table class="listnest-list__table">
                <thead>
                    <tr>
                        <th scope="col" class="listnest-list__col-image"><span class="screen-reader-text"><?php esc_html_e( 'Image', 'listnest' ); ?></span></th>
                        <th scope="col" class="listnest-list__col-product"><?php esc_html_e( 'Product', 'listnest' ); ?></th>
                        <th scope="col" class="listnest-list__col-quantity"><?php esc_html_e( 'Quantity', 'listnest' ); ?></th>
                        <th scope="col" class="listnest-list__col-remove"><span class="screen-reader-text"><?php esc_html_e( 'Remove', 'listnest' ); ?></span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $rows as $listnest_row ) : ?>
                        <?php $listnest_qty_id = 'listnest-qty-' . $list_key . '-' . str_replace( ':', '-', $listnest_row['item_key'] ); ?>
                        <tr class="listnest-list__item">
                            <td class="listnest-list__image">
                                <?php if ( $listnest_row['permalink'] ) : ?>
                                    <a href="<?php echo esc_url( $listnest_row['permalink'] ); ?>" tabindex="-1" aria-hidden="true"><?php echo $listnest_row['image']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by WC_Product::get_image(). ?></a>
                                <?php else : ?>
                                    <?php echo $listnest_row['image']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by WC_Product::get_image(). ?>
                                <?php endif; ?>
                            </td>
                            <td class="listnest-list__product">
                                <?php if ( $listnest_row['permalink'] ) : ?>
                                    <a class="listnest-list__name" href="<?php echo esc_url( $listnest_row['permalink'] ); ?>"><?php echo esc_html( $listnest_row['name'] ); ?></a>
                                <?php else : ?>
                                    <span class="listnest-list__name"><?php echo esc_html( $listnest_row['name'] ); ?></span>
                                <?php endif; ?>

                                <?php if ( ! empty( $listnest_row['attributes'] ) ) : ?>
                                    <dl class="listnest-list__attributes">
                                        <?php foreach ( $listnest_row['attributes'] as $listnest_attribute ) : ?>
                                            <div class="listnest-list__attribute">
                                                <dt><?php echo esc_html( $listnest_attribute['label'] ); ?>:</dt>
                                                <dd><?php echo esc_html( $listnest_attribute['value'] ); ?></dd>
                                            </div>
                                        <?php endforeach; ?>
                                    </dl>
                                <?php endif; ?>
                            </td>
                            <td class="listnest-list__quantity">
                                <label class="screen-reader-text" for="<?php echo esc_attr( $listnest_qty_id ); ?>">
                                    <?php
                                    /* translators: %s: product name */
                                    echo esc_html( sprintf( __( 'Quantity of %s', 'listnest' ), $listnest_row['name'] ) );
                                    ?>
                                </label>
                                <input type="number"
                                    id="<?php echo esc_attr( $listnest_qty_id ); ?>"
                                    class="input-text qty listnest-list__qty"
                                    name="listnest_items[<?php echo esc_attr( $listnest_row['item_key'] ); ?>][quantity]"
                                    value="<?php echo esc_attr( $listnest_row['quantity'] ); ?>"
                                    min="0"
                                    max="<?php echo esc_attr( $max_quantity ); ?>"
                                    step="1"
                                    inputmode="numeric" />
                            </td>
                            <td class="listnest-list__remove">
                                <button type="submit"
                                    class="listnest-list__remove-button"
                                    name="listnest_remove_item"
                                    value="<?php echo esc_attr( $listnest_row['item_key'] ); ?>"
                                    aria-label="<?php echo esc_attr( sprintf( /* translators: %s: product name */ __( 'Remove %s', 'listnest' ), $listnest_row['name'] ) ); ?>">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="listnest-list__actions">
                <button type="submit" class="<?php echo esc_attr( ListNest_Frontend::button_class( 'listnest-list__update' ) ); ?>" name="listnest_update_list" value="1"><?php esc_html_e( 'Update list', 'listnest' ); ?></button>
            </div>
        </form>
    <?php endif; ?>
</div>
