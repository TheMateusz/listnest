<?php
if ( ! defined('ABSPATH') ) {
    exit;
}

/**
 * List post type (listnest_list) and its settings metabox.
 *
 * The post slug is the list key used by the shortcodes and the CF7 tags.
 */
class ListNest_CPT {

    /**
     * Registers hooks. Called once by ListNest_Plugin.
     */
    public function register_hooks() {
        add_action('init', array($this, 'register_lists_post_type'));
        add_action('add_meta_boxes', array($this, 'add_list_meta_box'));
        add_action('save_post_listnest_list', array($this, 'save_list_meta_box'), 10, 2);

        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    public function register_lists_post_type() {
        $labels = array(
            'name'          => __('Lists', 'listnest'),
            'singular_name' => __('List', 'listnest'),
            'menu_name'     => __('Lists', 'listnest'),
            'all_items'     => __('All Lists', 'listnest'),
            'add_new_item'  => __('Add New List', 'listnest'),
            'edit_item'     => __('Edit List', 'listnest'),
            'new_item'      => __('New List', 'listnest'),
            'view_item'     => __('View List', 'listnest'),
            'search_items'  => __('Search Lists', 'listnest'),
            'not_found'     => __('No lists found', 'listnest'),
        );


        $args = array(
            'labels'        => $labels,
            'public'        => false,
            'show_ui'       => true,
            'show_in_menu'  => true,
            'menu_position' => 25,
            'menu_icon'     => 'dashicons-list-view',
            'supports'      => array('title'),
        );
        register_post_type('listnest_list', $args);
    }

    public function add_list_meta_box() {
        add_meta_box(
            'listnest_list_meta_box_id',
            __('List options', 'listnest'),
            array($this, 'render_list_meta_box'),
            'listnest_list',
            'normal',
            'default'
        );
    }

    public function render_list_meta_box($post) {
        wp_nonce_field('listnest_list_meta_box', 'listnest_list_meta_box_nonce');

        $selected_option = get_post_meta($post->ID, '_listnest_list_option', true);

        $redirect_page = get_post_meta($post->ID, '_listnest_list_redirect_page', true);
        $button_text   = get_post_meta($post->ID, '_listnest_list_button_text', true);
        $button_page    = get_post_meta($post->ID, '_listnest_list_button_page', true);

        $popup_title     = get_post_meta($post->ID, '_listnest_list_popup_title', true);
        $popup_btn1_text = get_post_meta($post->ID, '_listnest_list_popup_btn1_text', true);
        $popup_btn2_text = get_post_meta($post->ID, '_listnest_list_popup_btn2_text', true);
        $popup_btn2_page  = get_post_meta($post->ID, '_listnest_list_popup_btn2_page', true);

        $auto_button  = get_post_meta($post->ID, '_listnest_list_auto_button', true);
        $button_label = get_post_meta($post->ID, '_listnest_list_add_button_label', true);

        $form_type      = get_post_meta($post->ID, '_listnest_list_form', true);
        $form_recipient = get_post_meta($post->ID, '_listnest_list_form_recipient', true);
        $form_subject   = get_post_meta($post->ID, '_listnest_list_form_subject', true);
        $form_copy      = get_post_meta($post->ID, '_listnest_list_form_copy', true);
        $form_consent   = get_post_meta($post->ID, '_listnest_list_form_consent', true);

        ?>
        <p><strong><?php esc_html_e('"Add to list" button', 'listnest'); ?></strong></p>
        <p>
            <label>
                <input type="checkbox" name="listnest_list_auto_button" value="1" <?php checked($auto_button, '1'); ?> />
                <?php esc_html_e('Show button on product pages', 'listnest'); ?>
            </label>
        </p>
        <p>
            <label for="listnest_list_add_button_label"><?php esc_html_e('Button label:', 'listnest'); ?></label><br/>
            <input type="text" name="listnest_list_add_button_label" id="listnest_list_add_button_label"
                   placeholder="<?php esc_attr_e('Add to list', 'listnest'); ?>"
                   value="<?php echo esc_attr($button_label); ?>" style="width: 300px;" />
        </p>
        <p class="description">
            <?php
            printf(
                /* translators: %s: shortcode example */
                esc_html__('To place the button anywhere else, use the shortcode %s.', 'listnest'),
                '<code>[listnest_button list="' . esc_html($post->post_name ? $post->post_name : 'list-key') . '"]</code>'
            );
            ?>
        </p>
        <hr/>

        <p><strong><?php esc_html_e('Select an action after adding a product to this list:', 'listnest'); ?></strong></p>
        <p>
            <label>
                <input type="radio" name="listnest_list_option" value="redirect"
                    <?php checked($selected_option, 'redirect'); ?> />
                <?php esc_html_e('Redirect to the page of your choice', 'listnest'); ?>
            </label><br/>
            <label>
                <input type="radio" name="listnest_list_option" value="change_text"
                    <?php checked($selected_option, 'change_text'); ?> />
                <?php esc_html_e('Replace the button with a link to a page of your choice (e.g. the list), using your own text', 'listnest'); ?>
            </label><br/>
            <label>
                <input type="radio" name="listnest_list_option" value="popup"
                    <?php checked($selected_option, 'popup'); ?> />
                <?php esc_html_e('Show a confirmation popup', 'listnest'); ?>
            </label>
        </p>

        <div id="redirect-fields" style="display: none;">
            <hr/>
            <p><strong><?php esc_html_e('Target page:', 'listnest'); ?></strong></p>
            <p>
                <?php
                wp_dropdown_pages(array(
                    'name'             => 'listnest_list_redirect_page',
                    'selected'         => $redirect_page,
                    'show_option_none' => __('— Select a page —', 'listnest'),
                ));
                ?>
            </p>
        </div>

        <div id="change-text-fields" style="display: none;">
            <hr/>
            <p><strong><?php esc_html_e('Link button settings:', 'listnest'); ?></strong></p>
            <p>
                <label for="listnest_list_button_text"><?php esc_html_e('Button text:', 'listnest'); ?></label><br/>
                <input type="text" name="listnest_list_button_text" id="listnest_list_button_text"
                       placeholder="<?php esc_attr_e('See list', 'listnest'); ?>"
                       value="<?php echo esc_attr($button_text); ?>" style="width: 300px;" />
            </p>
            <p><strong><?php esc_html_e('Target page:', 'listnest'); ?></strong></p>
            <p>
                <?php
                wp_dropdown_pages(array(
                    'name'             => 'listnest_list_button_page',
                    'selected'         => $button_page,
                    'show_option_none' => __('— Select a page —', 'listnest'),
                ));
                ?>
            </p>
        </div>

        <div id="popup-fields" style="display: none;">
            <hr/>
            <p><strong><?php esc_html_e('Popup settings:', 'listnest'); ?></strong></p>
            <p>
                <label for="listnest_list_popup_title">
                    <?php esc_html_e('Popup title:', 'listnest'); ?>
                </label><br/>
                <input type="text" name="listnest_list_popup_title" id="listnest_list_popup_title"
                       value="<?php echo esc_attr($popup_title); ?>"
                       placeholder="<?php esc_attr_e('Product added to the list', 'listnest'); ?>"
                       style="width: 300px;" />
            </p>
            <p>
                <label for="listnest_list_popup_btn1_text"><?php esc_html_e('Closing button text:', 'listnest'); ?></label><br/>
                <input type="text" name="listnest_list_popup_btn1_text" id="listnest_list_popup_btn1_text"
                       value="<?php echo esc_attr($popup_btn1_text); ?>"
                       placeholder="<?php esc_attr_e('Continue shopping', 'listnest'); ?>"
                       style="width: 300px;" />
                <br/>
            </p>
            <p>
                <label for="listnest_list_popup_btn2_text"><?php esc_html_e('"Go to list" button text:', 'listnest'); ?></label><br/>
                <input type="text" name="listnest_list_popup_btn2_text" id="listnest_list_popup_btn2_text"
                       value="<?php echo esc_attr($popup_btn2_text); ?>"
                          placeholder="<?php esc_attr_e('Go to list', 'listnest'); ?>"
                       style="width: 300px;" />
                <br/>
            </p>
            <p><strong><?php esc_html_e('Target page:', 'listnest'); ?></strong></p>
            <p>
                <?php
                wp_dropdown_pages(array(
                    'name'             => 'listnest_list_popup_btn2_page',
                    'selected'         => $popup_btn2_page,
                    'show_option_none' => __('— Select a page —', 'listnest'),
                ));
                ?>
            </p>
        </div>

        <hr/>
        <p class="description"><?php esc_html_e('Leave text fields empty to use the default, translatable text.', 'listnest'); ?></p>
        <p class="description">
            <?php
            $list_key_example = $post->post_name ? $post->post_name : 'list-key';
            printf(
                /* translators: %s: shortcode example */
                esc_html__('To display the list, use the shortcode %s.', 'listnest'),
                '<code>[listnest list_key="' . esc_html($list_key_example) . '"]</code>'
            );
            ?>
        </p>
        <hr/>
        <p><strong><?php esc_html_e('Inquiry form:', 'listnest'); ?></strong></p>
        <p>
            <label>
                <input type="radio" name="listnest_list_form" value="" <?php checked($form_type, ''); ?> />
                <?php esc_html_e('None', 'listnest'); ?>
            </label><br/>
            <label>
                <input type="radio" name="listnest_list_form" value="builtin" <?php checked($form_type, 'builtin'); ?> />
                <?php esc_html_e('Built-in form (shown below the list)', 'listnest'); ?>
            </label><br/>
            <label>
                <input type="radio" name="listnest_list_form" value="cf7" <?php checked($form_type, 'cf7'); ?> />
                <?php esc_html_e('Contact Form 7', 'listnest'); ?>
            </label>
        </p>

        <div id="form-builtin-fields" style="display: none;">
            <p>
                <label for="listnest_list_form_recipient"><?php esc_html_e('Send inquiries to:', 'listnest'); ?></label><br/>
                <input type="text" name="listnest_list_form_recipient" id="listnest_list_form_recipient"
                       value="<?php echo esc_attr($form_recipient); ?>"
                       placeholder="<?php echo esc_attr(get_option('admin_email')); ?>"
                       style="width: 300px;" />
                <br/><span class="description"><?php esc_html_e('Separate multiple addresses with commas. Default: the site admin email.', 'listnest'); ?></span>
            </p>
            <p>
                <label for="listnest_list_form_subject"><?php esc_html_e('Email subject:', 'listnest'); ?></label><br/>
                <input type="text" name="listnest_list_form_subject" id="listnest_list_form_subject"
                       value="<?php echo esc_attr($form_subject); ?>"
                       placeholder="<?php esc_attr_e('New inquiry: {list}', 'listnest'); ?>"
                       style="width: 300px;" />
                <br/><span class="description">
                    <?php
                    printf(
                        /* translators: %s: list of placeholders */
                        esc_html__('Placeholders: %s', 'listnest'),
                        '<code>{list}</code> <code>{name}</code> <code>{site}</code>'
                    );
                    ?>
                </span>
            </p>
            <p>
                <label>
                    <input type="checkbox" name="listnest_list_form_copy" value="1" <?php checked($form_copy, '1'); ?> />
                    <?php esc_html_e('Send a copy to the customer', 'listnest'); ?>
                </label>
            </p>
            <p>
                <label for="listnest_list_form_consent"><?php esc_html_e('Consent checkbox text:', 'listnest'); ?></label><br/>
                <textarea name="listnest_list_form_consent" id="listnest_list_form_consent" rows="3" style="width: 100%; max-width: 600px;"
                          placeholder="<?php esc_attr_e('I agree to the processing of my personal data in order to handle this inquiry.', 'listnest'); ?>"><?php echo esc_textarea($form_consent); ?></textarea>
                <br/><span class="description"><?php esc_html_e('Links are allowed. Leave empty to use the default text with a link to your privacy policy page.', 'listnest'); ?></span>
            </p>
            <p class="description">
                <?php
                printf(
                    /* translators: 1: shortcode example, 2: admin menu path */
                    esc_html__('To place the form somewhere else, use %1$s. Received inquiries are saved under %2$s.', 'listnest'),
                    '<code>[listnest_form list="' . esc_html($list_key_example) . '"]</code>',
                    '<a href="' . esc_url(admin_url('edit.php?post_type=listnest_inquiry')) . '">' . esc_html__('Lists → Inquiries', 'listnest') . '</a>'
                );
                ?>
            </p>
        </div>

        <div id="form-cf7-fields" style="display: none;">
            <p class="description">
                <?php
                printf(
                    /* translators: 1: Contact Form 7 form tag, 2: Contact Form 7 mail tag */
                    esc_html__('Contact Form 7: add %1$s to the form and %2$s to the mail body.', 'listnest'),
                    '<code>[listnest_products list:' . esc_html($list_key_example) . ']</code>',
                    '<code>[listnest_products]</code>'
                );
                ?>
            </p>
            <?php if (! defined('WPCF7_VERSION')) : ?>
                <p class="description" style="color: #b32d2e;"><?php esc_html_e('Contact Form 7 is not active.', 'listnest'); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }

    public function save_list_meta_box($post_id, $post) {
        if (
            ! isset($_POST['listnest_list_meta_box_nonce']) ||
            ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['listnest_list_meta_box_nonce'])), 'listnest_list_meta_box')
        ) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (wp_is_post_revision($post_id)) {
            return;
        }
        if ($post->post_type !== 'listnest_list') {
            return;
        }
        if (! current_user_can('edit_post', $post_id)) {
            return;
        }

        $allowed_options = array('redirect', 'change_text', 'popup');
        $option          = isset($_POST['listnest_list_option']) ? sanitize_key(wp_unslash($_POST['listnest_list_option'])) : '';
        if (! in_array($option, $allowed_options, true)) {
            $option = '';
        }

        $redirect_page = isset($_POST['listnest_list_redirect_page']) ? $this->sanitize_page_id((int) wp_unslash($_POST['listnest_list_redirect_page'])) : 0;
        $button_text   = isset($_POST['listnest_list_button_text']) ? sanitize_text_field(wp_unslash($_POST['listnest_list_button_text'])) : '';
        $button_page   = isset($_POST['listnest_list_button_page']) ? $this->sanitize_page_id((int) wp_unslash($_POST['listnest_list_button_page'])) : 0;

        $popup_title     = isset($_POST['listnest_list_popup_title']) ? sanitize_text_field(wp_unslash($_POST['listnest_list_popup_title'])) : '';
        $popup_btn1_text = isset($_POST['listnest_list_popup_btn1_text']) ? sanitize_text_field(wp_unslash($_POST['listnest_list_popup_btn1_text'])) : '';
        $popup_btn2_text = isset($_POST['listnest_list_popup_btn2_text']) ? sanitize_text_field(wp_unslash($_POST['listnest_list_popup_btn2_text'])) : '';
        $popup_btn2_page = isset($_POST['listnest_list_popup_btn2_page']) ? $this->sanitize_page_id((int) wp_unslash($_POST['listnest_list_popup_btn2_page'])) : 0;

        $auto_button  = (isset($_POST['listnest_list_auto_button']) && '1' === sanitize_key(wp_unslash($_POST['listnest_list_auto_button']))) ? '1' : '0';
        $button_label = isset($_POST['listnest_list_add_button_label']) ? sanitize_text_field(wp_unslash($_POST['listnest_list_add_button_label'])) : '';

        $form_type = isset($_POST['listnest_list_form']) ? sanitize_key(wp_unslash($_POST['listnest_list_form'])) : '';
        if (! in_array($form_type, array('builtin', 'cf7'), true)) {
            $form_type = '';
        }

        $form_recipients = array();
        if (isset($_POST['listnest_list_form_recipient'])) {
            foreach (explode(',', sanitize_text_field(wp_unslash($_POST['listnest_list_form_recipient']))) as $email) {
                $email = sanitize_email(trim($email));
                if (is_email($email)) {
                    $form_recipients[] = $email;
                }
            }
        }

        $form_subject = isset($_POST['listnest_list_form_subject']) ? sanitize_text_field(wp_unslash($_POST['listnest_list_form_subject'])) : '';
        $form_copy    = (isset($_POST['listnest_list_form_copy']) && '1' === sanitize_key(wp_unslash($_POST['listnest_list_form_copy']))) ? '1' : '0';
        $form_consent = isset($_POST['listnest_list_form_consent']) ? trim(wp_kses(wp_unslash($_POST['listnest_list_form_consent']), ListNest_Inquiries::get_consent_allowed_html())) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized with wp_kses().

        update_post_meta($post_id, '_listnest_list_form', $form_type);
        update_post_meta($post_id, '_listnest_list_form_recipient', implode(', ', $form_recipients));
        update_post_meta($post_id, '_listnest_list_form_subject', $form_subject);
        update_post_meta($post_id, '_listnest_list_form_copy', $form_copy);
        update_post_meta($post_id, '_listnest_list_form_consent', $form_consent);

        update_post_meta($post_id, '_listnest_list_auto_button', $auto_button);
        update_post_meta($post_id, '_listnest_list_add_button_label', $button_label);
        update_post_meta($post_id, '_listnest_list_option', $option);
        update_post_meta($post_id, '_listnest_list_redirect_page', $redirect_page);
        update_post_meta($post_id, '_listnest_list_button_text', $button_text);
        update_post_meta($post_id, '_listnest_list_button_page', $button_page);

        update_post_meta($post_id, '_listnest_list_popup_title', $popup_title);
        update_post_meta($post_id, '_listnest_list_popup_btn1_text', $popup_btn1_text);
        update_post_meta($post_id, '_listnest_list_popup_btn2_text', $popup_btn2_text);
        update_post_meta($post_id, '_listnest_list_popup_btn2_page', $popup_btn2_page);
    }

    /**
     * Page ID from a wp_dropdown_pages() field; "none" (-1) and invalid values become 0.
     *
     * @param int $page_id Raw page ID.
     * @return int
     */
    private function sanitize_page_id($page_id) {
        $page_id = (int) $page_id;
        return $page_id > 0 ? $page_id : 0;
    }

    /**
     * Returns the ID of the published list with the given key (post slug),
     * or 0 when there is none.
     *
     * @param string $list_key List key.
     * @return int
     */
    public static function get_published_list_id($list_key) {
        static $cache = array();

        $list_key = sanitize_key($list_key);
        if ('' === $list_key) {
            return 0;
        }

        if (! isset($cache[$list_key])) {
            $ids = get_posts(array(
                'post_type'        => 'listnest_list',
                'post_status'      => 'publish',
                'name'             => $list_key,
                'numberposts'      => 1,
                'fields'           => 'ids',
                'no_found_rows'    => true,
                'suppress_filters' => true,
            ));
            $cache[$list_key] = ! empty($ids) ? (int) $ids[0] : 0;
        }

        return $cache[$list_key];
    }

    public function enqueue_admin_scripts($hook) {
        global $post;
        if ( $post && $post->post_type === 'listnest_list' ) {
            wp_enqueue_script(
                'listnest-admin-metabox',
                LISTNEST_URL . 'assets/js/admin-metabox.js',
                array(),
                LISTNEST_VERSION,
                true
            );
        }
    }

}
