<?php
/** Integration regressions. Run only in the seeded Docker test environment. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || 'ListNest Test' !== get_option( 'blogname' ) ) {
    throw new RuntimeException( 'ListNest test environment required.' );
}
$ajax = ListNest_Plugin::instance()->get_component( 'ajax' );
$resolve = new ReflectionMethod( $ajax, 'resolve_product' );
$resolve->setAccessible( true );
$attributes = new ReflectionMethod( $ajax, 'sanitize_attributes' );
$attributes->setAccessible( true );
$parent = wc_get_product( get_page_by_path( 'koszulka-wariantowa', OBJECT, 'product' )->ID );
$variation = wc_get_product( $parent->get_children()[0] );
$simple = wc_get_product( get_page_by_path( 'kubek-prosty', OBJECT, 'product' )->ID );
$checks = 0;
$assert = function ( $condition, $message ) use ( &$checks ) {
    if ( ! $condition ) {
        throw new RuntimeException( $message );
    }
    ++$checks;
    WP_CLI::log( 'PASS: ' . $message );
};
$assert( false === $resolve->invoke( $ajax, $parent->get_id(), 0 ), 'Variable product requires a variation' );
$assert( false !== $resolve->invoke( $ajax, $simple->get_id(), 0 ), 'Simple product accepted' );
$assert( false === $resolve->invoke( $ajax, $simple->get_id(), $variation->get_id() ), 'Unrelated variation rejected' );
$assert( false === $attributes->invoke( $ajax, array(), $variation ), 'Missing any attribute rejected' );
$assert( false === $attributes->invoke( $ajax, array( 'attribute_rozmiar' => 'XL' ), $variation ), 'Unknown option rejected' );
$assert( false === $attributes->invoke( $ajax, array( 'attribute_rozmiar' => array( 'L' ) ), $variation ), 'Array option rejected' );
$valid = $attributes->invoke( $ajax, array( 'attribute_rozmiar' => 'L' ), $variation );
$assert( is_array( $valid ) && 'L' === $valid['attribute_rozmiar'], 'Allowed any option accepted' );
$full = array_merge( $variation->get_variation_attributes(), array( 'attribute_rozmiar' => 'L' ) );
$assert( $valid === $attributes->invoke( $ajax, $full, $variation ), 'Fixed attributes canonicalized' );
$wrong = $full;
$wrong['attribute_pa_kolor'] = 'unknown';
$assert( false === $attributes->invoke( $ajax, $wrong, $variation ), 'Contradictory fixed attribute rejected' );
WP_CLI::success( $checks . ' regression checks passed.' );
