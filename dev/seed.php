<?php
/**
 * Development only: test data for wp-env.
 * Run: wp-env run cli wp eval-file wp-content/listnest-dev/seed.php
 */

update_option( 'permalink_structure', '/%postname%/' );
update_option( 'woocommerce_coming_soon', 'no' );
update_option( 'woocommerce_store_pages_only', 'no' );
update_option( 'blogname', 'ListNest Test' );
$placeholder = absint( get_option( 'woocommerce_placeholder_image', 0 ) );
if ( $placeholder && ! file_exists( (string) get_attached_file( $placeholder ) ) ) {
    update_option( 'woocommerce_placeholder_image', 0 );
}

function listnest_seed_page( $slug, $title, $content ) {
    $page = get_page_by_path( $slug );
    $args = array(
        'post_type'    => 'page',
        'post_status'  => 'publish',
        'post_name'    => $slug,
        'post_title'   => $title,
        'post_content' => $content,
    );
    if ( $page ) {
        $args['ID'] = $page->ID;
    }
    return wp_insert_post( $args );
}

function listnest_seed_list( $slug, $title, array $meta ) {
    $existing = get_posts( array( 'post_type' => 'listnest_list', 'name' => $slug, 'post_status' => 'any', 'fields' => 'ids' ) );
    $id       = $existing ? $existing[0] : wp_insert_post( array( 'post_type' => 'listnest_list', 'post_status' => 'publish', 'post_name' => $slug, 'post_title' => $title ) );
    foreach ( $meta as $key => $value ) {
        update_post_meta( $id, '_listnest_list_' . $key, $value );
    }
    return $id;
}

/* Products */
if ( ! get_page_by_path( 'kubek-prosty', OBJECT, 'product' ) ) {
    $simple = new WC_Product_Simple();
    $simple->set_name( 'Kubek prosty' );
    $simple->set_slug( 'kubek-prosty' );
    $simple->set_regular_price( '25' );
    $simple->set_sku( 'KUB-1' );
    $simple->save();
}

if ( ! get_page_by_path( 'koszulka-wariantowa', OBJECT, 'product' ) ) {
    // Global attribute pa_kolor.
    if ( ! taxonomy_exists( 'pa_kolor' ) ) {
        wc_create_attribute( array( 'name' => 'Kolor', 'slug' => 'kolor' ) );
        register_taxonomy( 'pa_kolor', 'product' );
    }
    foreach ( array( 'Czerwony', 'Niebieski' ) as $term ) {
        if ( ! term_exists( $term, 'pa_kolor' ) ) {
            wp_insert_term( $term, 'pa_kolor' );
        }
    }

    $kolor = new WC_Product_Attribute();
    $kolor->set_id( wc_attribute_taxonomy_id_by_name( 'pa_kolor' ) );
    $kolor->set_name( 'pa_kolor' );
    $kolor->set_options( array( get_term_by( 'name', 'Czerwony', 'pa_kolor' )->term_id, get_term_by( 'name', 'Niebieski', 'pa_kolor' )->term_id ) );
    $kolor->set_visible( true );
    $kolor->set_variation( true );

    $rozmiar = new WC_Product_Attribute();
    $rozmiar->set_name( 'Rozmiar' );
    $rozmiar->set_options( array( 'S', 'M', 'L' ) );
    $rozmiar->set_visible( true );
    $rozmiar->set_variation( true );

    $variable = new WC_Product_Variable();
    $variable->set_name( 'Koszulka wariantowa' );
    $variable->set_slug( 'koszulka-wariantowa' );
    $variable->set_attributes( array( $kolor, $rozmiar ) );
    $parent_id = $variable->save();

    foreach ( array( 'czerwony', 'niebieski' ) as $color ) {
        $variation = new WC_Product_Variation();
        $variation->set_parent_id( $parent_id );
        $variation->set_regular_price( '50' );
        // Rozmiar left as "any" to test customer-chosen attributes.
        $variation->set_attributes( array( 'pa_kolor' => $color, 'rozmiar' => '' ) );
        $variation->save();
    }
}

/* Pages */
$list_page = listnest_seed_page( 'probki', 'Moje próbki', '[listnest list_key="probki"]' );
$cf7_page  = listnest_seed_page( 'zapytanie', 'Zapytanie CF7', '' );
listnest_seed_page( 'formularz', 'Formularz shortcode', '<p>Formularz w innym miejscu:</p>[listnest_form list="probki"]' );

/* Lists */
listnest_seed_list( 'probki', 'Próbki', array(
    'option'            => 'popup',
    'auto_button'       => '1',
    'add_button_label'  => 'Dodaj do próbek',
    'popup_btn2_page'   => $list_page,
    'form'              => 'builtin',
    'form_recipient'    => 'sklep@example.com, biuro@example.com',
    'form_subject'      => 'Próbki od {name} ({list}) - {site}',
    'form_copy'         => '1',
    'form_consent'      => '',
) );

listnest_seed_list( 'zapytanie', 'Zapytanie ofertowe', array(
    'option'           => 'change_text',
    'auto_button'      => '1',
    'add_button_label' => 'Do zapytania',
    'button_page'      => $cf7_page,
    'form'             => 'cf7',
) );

/* CF7 form */
$forms = get_posts( array( 'post_type' => 'wpcf7_contact_form', 'title' => 'ListNest CF7', 'fields' => 'ids' ) );
if ( ! $forms && class_exists( 'WPCF7_ContactForm' ) ) {
    $form = WPCF7_ContactForm::get_template( array( 'title' => 'ListNest CF7' ) );
    $form->set_properties( array(
        'form' => "[listnest_products list:zapytanie]\n<label>Imię [text* your-name]</label>\n<label>E-mail [email* your-email]</label>\n[submit \"Wyślij\"]",
        'mail' => array(
            'subject'            => 'CF7 zapytanie',
            'sender'             => 'ListNest <wordpress@localhost>',
            'recipient'          => 'cf7@example.com',
            'body'               => "Od: [your-name] [your-email]\n\n[listnest_products]",
            'additional_headers' => '',
            'attachments'        => '',
            'use_html'           => true,
            'exclude_blank'      => false,
        ),
    ) );
    $form->save();
    $form_id = $form->id();
} else {
    $form_id = $forms[0];
}
listnest_seed_page( 'zapytanie', 'Zapytanie CF7', '[listnest list_key="zapytanie"]' . "\n\n" . '[contact-form-7 id="' . $form_id . '"]' );

flush_rewrite_rules();
WP_CLI::success( 'Seeded. List page: ' . get_permalink( $list_page ) );
