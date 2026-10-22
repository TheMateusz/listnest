<?php
if ( ! defined('ABSPATH') ) {
    exit;
}

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

        ?>
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
                <?php esc_html_e('Change the button to your own text, and when clicked, move to the list', 'listnest'); ?>
            </label><br/>
            <label>
                <input type="radio" name="listnest_list_option" value="popup"
                    <?php checked($selected_option, 'popup'); ?> />
                <?php esc_html_e('View popup', 'listnest'); ?>
            </label>
        </p>

        <div id="redirect-fields" style="display: none;">
            <hr/>
            <p><strong><?php esc_html_e('Redirection settings', 'listnest'); ?>:</strong></p>
            <p>
                <?php
                wp_dropdown_pages(array(
                    'name'             => 'listnest_list_redirect_page',
                    'selected'         => $redirect_page,
                    'show_option_none' => __('- Choose a side -', 'listnest'),
                ));
                ?>
            </p>
        </div>

        <div id="change-text-fields" style="display: none;">
            <hr/>
            <p><strong><?php esc_html_e('Settings for changing the button text:', 'listnest'); ?></strong></p>
            <p>
                <label for="listnest_list_button_text"><?php esc_html_e('Button text:', 'listnest'); ?></label><br/>
                <input type="text" name="listnest_list_button_text" id="listnest_list_button_text"
                       placeholder="<?php esc_attr_e('See list', 'listnest'); ?>"
                       value="<?php echo esc_attr($button_text); ?>" style="width: 300px;" />
            </p>
            <p><strong><?php esc_html_e('Redirection settings', 'listnest'); ?>:</strong></p>
            <p>
                <?php
                wp_dropdown_pages(array(
                    'name'             => 'listnest_list_button_page',
                    'selected'         => $button_page,
                    'show_option_none' => __('- Choose a side -', 'listnest'),
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
                       placeholder="<?php esc_attr_e('Popup title', 'listnest'); ?>"
                       style="width: 300px;" />
            </p>
            <p>
                <label for="listnest_list_popup_btn1_text"><?php esc_html_e('Closing button text:', 'listnest'); ?></label><br/>
                <input type="text" name="listnest_list_popup_btn1_text" id="listnest_list_popup_btn1_text"
                       value="<?php echo esc_attr($popup_btn1_text); ?>"
                       placeholder="<?php esc_attr_e('Close', 'listnest'); ?>"
                       style="width: 300px;" />
                <br/>
            </p>
            <p>
                <label for="listnest_list_popup_btn2_text"><?php esc_html_e('The text of the button to go to the list:', 'listnest'); ?></label><br/>
                <input type="text" name="listnest_list_popup_btn2_text" id="listnest_list_popup_btn2_text"
                       value="<?php echo esc_attr($popup_btn2_text); ?>"
                          placeholder="<?php esc_attr_e('Go to list', 'listnest'); ?>"
                       style="width: 300px;" />
                <br/>
            </p>
            <p><strong><?php esc_html_e('Redirection settings', 'listnest'); ?>:</strong></p>
            <p>
                <?php
                wp_dropdown_pages(array(
                    'name'             => 'listnest_list_popup_btn2_page',
                    'selected'         => $popup_btn2_page,
                    'show_option_none' => __('- Choose a side -', 'listnest'),
                ));
                ?>
            </p>
        </div>

        <hr/>
        <p>Jeśli chcesz, żeby tłumaczenie było dynamiczne, zgodne z językiem strony, pozostaw pole puste. Tłumaczenie zostanie pobrane z ustawień językowych WordPressa. Jeśli chcesz możesz je zmienić przy pomocy pluginu Loco Translate.</p>
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
