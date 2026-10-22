<?php
/**
 * Confirmation dialog shown after a product is added to a list whose
 * option is "popup". Printed once in the footer; the title, button texts
 * and link are filled in by the script from the list settings.
 *
 * Override: copy to yourtheme/listnest/popup.php
 *
 * Keep the IDs and the data-listnest-close attributes; the script relies on
 * them. The dialog is hidden with the "hidden" attribute while closed.
 *
 * Available variables: none.
 *
 * @package ListNest
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div id="listnest-popup" class="listnest listnest-popup" hidden>
    <div class="listnest-popup__backdrop" data-listnest-close></div>
    <div class="listnest-popup__dialog" role="dialog" aria-modal="true" aria-labelledby="listnest-popup-title" tabindex="-1">
        <button type="button" class="listnest-popup__close" data-listnest-close aria-label="<?php esc_attr_e( 'Close dialog', 'listnest' ); ?>">
            <span aria-hidden="true">&times;</span>
        </button>
        <h2 id="listnest-popup-title" class="listnest-popup__title"><?php esc_html_e( 'Product added to the list', 'listnest' ); ?></h2>
        <div class="listnest-popup__actions">
            <button type="button" id="listnest-popup-btn1" class="<?php echo esc_attr( ListNest_Frontend::button_class( 'listnest-popup__button listnest-popup__secondary' ) ); ?>" data-listnest-close><?php esc_html_e( 'Continue shopping', 'listnest' ); ?></button>
            <a href="#" id="listnest-popup-btn2" class="<?php echo esc_attr( ListNest_Frontend::button_class( 'listnest-popup__button listnest-popup__primary' ) ); ?>"><?php esc_html_e( 'Go to list', 'listnest' ); ?></a>
        </div>
    </div>
</div>
