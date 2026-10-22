<?php
/**
 * Built-in inquiry form, shown below the list or by [listnest_form list="..."].
 *
 * Override: copy to yourtheme/listnest/inquiry-form.php
 * (or yourtheme/listnest/{list_key}-inquiry-form.php for one list).
 *
 * The form must keep: the list field, the nonce field, the honeypot field
 * and the listnest_name / listnest_email / listnest_phone / listnest_message /
 * listnest_consent inputs.
 *
 * Available variables:
 *
 * @var string $list_key        List key (list post slug).
 * @var string $form_id         Element ID of the form wrapper (redirect anchor).
 * @var array  $values          name, email, phone, message (strings), consent (bool).
 * @var array  $errors          Field name => message; 'form' holds a general error.
 * @var bool   $show_form       Whether to show the form (false right after sending).
 * @var bool   $sent            Whether the inquiry has just been sent.
 * @var string $success_message Thank-you message (plain text).
 * @var string $consent_text    Consent label (sanitized HTML); '' when there is no consent checkbox.
 * @var string $action_url      Form action URL.
 * @var string $list_field      Name of the hidden list field.
 * @var string $nonce_action    Nonce action.
 * @var string $nonce_field     Nonce field name.
 * @var string $honeypot        Name of the honeypot field.
 *
 * @package ListNest
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$listnest_field_attrs = static function ( $field ) use ( $errors, $form_id ) {
    if ( empty( $errors[ $field ] ) ) {
        return '';
    }
    return ' aria-invalid="true" aria-describedby="' . esc_attr( $form_id . '-' . $field . '-error' ) . '"';
};

$listnest_field_error = static function ( $field ) use ( $errors, $form_id ) {
    if ( empty( $errors[ $field ] ) ) {
        return;
    }
    echo '<span class="listnest-form__error" id="' . esc_attr( $form_id . '-' . $field . '-error' ) . '">' . esc_html( $errors[ $field ] ) . '</span>';
};
?>
<div class="listnest listnest-form-wrap" id="<?php echo esc_attr( $form_id ); ?>">
    <?php if ( $sent ) : ?>
        <p class="listnest-notice listnest-notice--success" role="status"><?php echo esc_html( $success_message ); ?></p>
    <?php endif; ?>

    <?php if ( $show_form ) : ?>
        <form method="post" action="<?php echo esc_url( $action_url ); ?>" class="listnest-form" novalidate>
            <h2 class="listnest-form__title"><?php esc_html_e( 'Send your inquiry', 'listnest' ); ?></h2>

            <?php if ( ! empty( $errors ) ) : ?>
                <div class="listnest-notice listnest-notice--error" role="alert">
                    <?php if ( ! empty( $errors['form'] ) ) : ?>
                        <p><?php echo esc_html( $errors['form'] ); ?></p>
                    <?php endif; ?>
                    <?php if ( count( array_diff_key( $errors, array( 'form' => true ) ) ) ) : ?>
                        <p><?php esc_html_e( 'Please correct the highlighted fields.', 'listnest' ); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <input type="hidden" name="<?php echo esc_attr( $list_field ); ?>" value="<?php echo esc_attr( $list_key ); ?>" />
            <?php wp_nonce_field( $nonce_action, $nonce_field ); ?>

            <div class="listnest-form__hp" aria-hidden="true">
                <label for="<?php echo esc_attr( $form_id . '-hp' ); ?>"><?php esc_html_e( 'Leave this field empty', 'listnest' ); ?></label>
                <input type="text" id="<?php echo esc_attr( $form_id . '-hp' ); ?>" name="<?php echo esc_attr( $honeypot ); ?>" value="" tabindex="-1" autocomplete="off" />
            </div>

            <div class="listnest-form__grid">
                <p class="listnest-form__field<?php echo empty( $errors['name'] ) ? '' : ' listnest-form__field--invalid'; ?>">
                    <label for="<?php echo esc_attr( $form_id . '-name' ); ?>">
                        <?php esc_html_e( 'Name', 'listnest' ); ?> <span class="listnest-form__required" aria-hidden="true">*</span>
                    </label>
                    <input type="text" id="<?php echo esc_attr( $form_id . '-name' ); ?>" name="listnest_name"
                        value="<?php echo esc_attr( $values['name'] ); ?>" autocomplete="name" maxlength="100" required<?php echo $listnest_field_attrs( 'name' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the closure. ?> />
                    <?php $listnest_field_error( 'name' ); ?>
                </p>

                <p class="listnest-form__field<?php echo empty( $errors['email'] ) ? '' : ' listnest-form__field--invalid'; ?>">
                    <label for="<?php echo esc_attr( $form_id . '-email' ); ?>">
                        <?php esc_html_e( 'Email', 'listnest' ); ?> <span class="listnest-form__required" aria-hidden="true">*</span>
                    </label>
                    <input type="email" id="<?php echo esc_attr( $form_id . '-email' ); ?>" name="listnest_email"
                        value="<?php echo esc_attr( $values['email'] ); ?>" autocomplete="email" required<?php echo $listnest_field_attrs( 'email' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the closure. ?> />
                    <?php $listnest_field_error( 'email' ); ?>
                </p>

                <p class="listnest-form__field<?php echo empty( $errors['phone'] ) ? '' : ' listnest-form__field--invalid'; ?>">
                    <label for="<?php echo esc_attr( $form_id . '-phone' ); ?>">
                        <?php esc_html_e( 'Phone', 'listnest' ); ?> <span class="listnest-form__optional"><?php esc_html_e( '(optional)', 'listnest' ); ?></span>
                    </label>
                    <input type="tel" id="<?php echo esc_attr( $form_id . '-phone' ); ?>" name="listnest_phone"
                        value="<?php echo esc_attr( $values['phone'] ); ?>" autocomplete="tel" maxlength="30"<?php echo $listnest_field_attrs( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the closure. ?> />
                    <?php $listnest_field_error( 'phone' ); ?>
                </p>
            </div>

            <p class="listnest-form__field<?php echo empty( $errors['message'] ) ? '' : ' listnest-form__field--invalid'; ?>">
                <label for="<?php echo esc_attr( $form_id . '-message' ); ?>">
                    <?php esc_html_e( 'Message', 'listnest' ); ?> <span class="listnest-form__optional"><?php esc_html_e( '(optional)', 'listnest' ); ?></span>
                </label>
                <textarea id="<?php echo esc_attr( $form_id . '-message' ); ?>" name="listnest_message" rows="5" maxlength="5000"<?php echo $listnest_field_attrs( 'message' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the closure. ?>><?php echo esc_textarea( $values['message'] ); ?></textarea>
                <?php $listnest_field_error( 'message' ); ?>
            </p>

            <?php if ( '' !== $consent_text ) : ?>
                <p class="listnest-form__field listnest-form__field--consent<?php echo empty( $errors['consent'] ) ? '' : ' listnest-form__field--invalid'; ?>">
                    <input type="checkbox" id="<?php echo esc_attr( $form_id . '-consent' ); ?>" name="listnest_consent" value="1"
                        <?php checked( $values['consent'] ); ?> required<?php echo $listnest_field_attrs( 'consent' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the closure. ?> />
                    <label for="<?php echo esc_attr( $form_id . '-consent' ); ?>">
                        <?php echo wp_kses( $consent_text, ListNest_Inquiries::get_consent_allowed_html() ); ?>
                        <span class="listnest-form__required" aria-hidden="true">*</span>
                    </label>
                    <?php $listnest_field_error( 'consent' ); ?>
                </p>
            <?php endif; ?>

            <p class="listnest-form__actions">
                <button type="submit" class="button listnest-form__submit<?php echo ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) ? ' wp-element-button' : ''; ?>"><?php esc_html_e( 'Send inquiry', 'listnest' ); ?></button>
            </p>
        </form>
    <?php endif; ?>
</div>
