<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Display helpers for stored list items, shared by the list template
 * ([listnest] shortcode) and the Contact Form 7 integration.
 */
class ListNest_Item {

    /**
     * Prepares display data for each stored list item.
     *
     * Each row: item_key, product (variation or simple product, null when
     * missing), parent (parent/simple product, null when missing), name
     * (plain text, '' when missing), permalink ('' when the product is not
     * visible), image (HTML), attributes (list of array( label, value )),
     * quantity.
     *
     * @param array $items           Stored list items keyed by item key.
     * @param bool  $include_missing Whether to keep items whose product no longer exists
     *                               (product/parent null, name ''). Default false: skipped.
     * @return array
     */
    public static function prepare_rows( $items, $include_missing = false ) {
        $rows = array();

        foreach ( (array) $items as $item_key => $data ) {
            if ( ! is_array( $data ) ) {
                continue;
            }

            $product_id   = isset( $data['product_id'] ) ? absint( $data['product_id'] ) : 0;
            $variation_id = isset( $data['variation_id'] ) ? absint( $data['variation_id'] ) : 0;
            $parent       = $product_id ? wc_get_product( $product_id ) : false;
            $product      = $variation_id ? wc_get_product( $variation_id ) : $parent;
            $stored       = ( ! empty( $data['attributes'] ) && is_array( $data['attributes'] ) ) ? $data['attributes'] : array();
            $quantity     = isset( $data['quantity'] ) ? max( 1, absint( $data['quantity'] ) ) : 1;

            if ( ! $product ) {
                if ( $include_missing ) {
                    $rows[] = array(
                        'item_key'   => (string) $item_key,
                        'product'    => null,
                        'parent'     => null,
                        'name'       => '',
                        'permalink'  => '',
                        'image'      => '',
                        'attributes' => self::get_attribute_rows( null, null, $stored ),
                        'quantity'   => $quantity,
                    );
                }
                continue;
            }
            if ( ! $parent ) {
                $parent = $product;
            }

            $rows[] = array(
                'item_key'   => (string) $item_key,
                'product'    => $product,
                'parent'     => $parent,
                'name'       => $parent->get_name(),
                'permalink'  => $parent->is_visible() ? $product->get_permalink() : '',
                'image'      => $product->get_image( 'woocommerce_thumbnail' ),
                'attributes' => self::get_attribute_rows( $product, $parent, $stored ),
                'quantity'   => $quantity,
            );
        }

        return $rows;
    }

    /**
     * Human readable attributes of a list item.
     *
     * Values fixed on the variation are merged with the values chosen by
     * the customer ("any" attributes). Labels come from wc_attribute_label()
     * and taxonomy values are resolved to term names.
     *
     * @param WC_Product|null $product Variation or simple product (null when missing).
     * @param WC_Product|null $parent  Parent product (null when missing).
     * @param array           $stored  Stored attributes (attribute_* => value).
     * @return array List of array( 'label' => string, 'value' => string ).
     */
    public static function get_attribute_rows( $product, $parent, array $stored ) {
        $values = array();
        if ( $product instanceof WC_Product && $product->is_type( 'variation' ) ) {
            $values = $product->get_variation_attributes();
        }
        foreach ( $stored as $key => $value ) {
            if ( is_string( $key ) && is_scalar( $value ) && '' !== (string) $value ) {
                $values[ $key ] = (string) $value;
            }
        }

        $rows = array();
        foreach ( $values as $key => $value ) {
            if ( '' === (string) $value ) {
                continue;
            }

            $key  = (string) $key;
            $name = ( 0 === strpos( $key, 'attribute_' ) ) ? substr( $key, strlen( 'attribute_' ) ) : $key;

            if ( taxonomy_exists( $name ) ) {
                $term  = get_term_by( 'slug', $value, $name );
                $value = ( $term && ! is_wp_error( $term ) ) ? $term->name : $value;
            }

            $rows[] = array(
                'label' => $parent instanceof WC_Product ? wc_attribute_label( $name, $parent ) : wc_attribute_label( $name ),
                'value' => (string) $value,
            );
        }

        return $rows;
    }
}
