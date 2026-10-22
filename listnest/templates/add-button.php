<?php
/**
 * "Add to list" button.
 *
 * Override: copy to yourtheme/listnest/add-button.php
 * (or yourtheme/listnest/{list_key}-add-button.php for one list).
 *
 * Keep the listnest-add-button class and the data-listnest-type and
 * data-product-id attributes; the script relies on them. The element with
 * the listnest-feedback class receives status messages.
 *
 * Available variables:
 *
 * @var string     $list_key   List key (list post slug).
 * @var WC_Product $product    Product the button adds.
 * @var int        $product_id Product ID.
 * @var string     $label      Button label (plain text, not escaped).
 * @var string     $classes    Button CSS classes (sanitized, space separated).
 *
 * @package ListNest
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="listnest listnest-button-wrap">
    <button type="button"
        class="<?php echo esc_attr( $classes ); ?>"
        data-listnest-type="<?php echo esc_attr( $list_key ); ?>"
        data-product-id="<?php echo esc_attr( $product_id ); ?>"><?php echo esc_html( $label ); ?></button>
    <span class="listnest-feedback" role="status" aria-live="polite"></span>
</div>
