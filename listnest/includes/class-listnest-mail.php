<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Mail formatting helpers shared by the Contact Form 7 integration and the
 * built-in inquiry form.
 *
 * Rows are arrays with at least: name (string), attributes (list of
 * array( label, value )), quantity (int) - i.e. rows from
 * ListNest_Item::prepare_rows() or stored inquiry snapshots.
 */
class ListNest_Mail {

    /**
     * List of products as an HTML table. Inline styles only (mail clients).
     *
     * @param string $list_key List key.
     * @param array  $rows     Rows.
     * @return string Escaped HTML.
     */
    public static function products_html( $list_key, array $rows ) {
        $title = sprintf(
            /* translators: %s: list name */
            __( 'List: %s', 'listnest' ),
            self::list_title( $list_key )
        );

        $html = '<p><strong>' . esc_html( $title ) . '</strong></p>';

        if ( empty( $rows ) ) {
            return $html . '<p>' . esc_html__( 'No products on the list.', 'listnest' ) . '</p>';
        }

        $th = 'style="background:#f4f4f4;text-align:left;padding:6px;border:1px solid #ddd;"';
        $td = 'style="padding:6px;border:1px solid #ddd;vertical-align:top;"';

        $html .= '<table cellpadding="0" cellspacing="0" style="border-collapse:collapse;width:100%;max-width:600px;">';
        $html .= '<thead><tr>';
        $html .= '<th ' . $th . '>' . esc_html__( 'Product', 'listnest' ) . '</th>';
        $html .= '<th ' . $th . '>' . esc_html__( 'Attributes', 'listnest' ) . '</th>';
        $html .= '<th ' . $th . '>' . esc_html__( 'Quantity', 'listnest' ) . '</th>';
        $html .= '</tr></thead><tbody>';

        foreach ( $rows as $row ) {
            $name = '' !== (string) $row['name'] ? $row['name'] : __( 'Unknown product', 'listnest' );

            if ( empty( $row['attributes'] ) ) {
                $attributes = esc_html__( 'No attributes', 'listnest' );
            } else {
                $lines = array();
                foreach ( $row['attributes'] as $attribute ) {
                    $lines[] = esc_html( $attribute['label'] ) . ': ' . esc_html( $attribute['value'] );
                }
                $attributes = implode( '<br>', $lines );
            }

            $html .= '<tr>';
            $html .= '<td ' . $td . '>' . esc_html( $name ) . '</td>';
            $html .= '<td ' . $td . '>' . $attributes . '</td>';
            $html .= '<td ' . $td . '>' . esc_html( number_format_i18n( $row['quantity'] ) ) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        return $html;
    }

    /**
     * List of products as plain text.
     *
     * @param string $list_key List key.
     * @param array  $rows     Rows.
     * @return string
     */
    public static function products_text( $list_key, array $rows ) {
        $lines   = array();
        $lines[] = sprintf(
            /* translators: %s: list name */
            __( 'List: %s', 'listnest' ),
            self::list_title( $list_key )
        );
        $lines[] = '';

        if ( empty( $rows ) ) {
            $lines[] = __( 'No products on the list.', 'listnest' );
            return implode( "\n", $lines );
        }

        $number = 0;
        foreach ( $rows as $row ) {
            ++$number;
            $name = '' !== (string) $row['name'] ? $row['name'] : __( 'Unknown product', 'listnest' );

            $attributes = empty( $row['attributes'] )
                ? __( 'No attributes', 'listnest' )
                : self::attributes_text( $row['attributes'], '; ' );

            $lines[] = $number . '. ' . __( 'Product', 'listnest' ) . ': ' . self::plain_text( $name );
            $lines[] = '   ' . __( 'Attributes', 'listnest' ) . ': ' . self::plain_text( $attributes );
            $lines[] = '   ' . __( 'Quantity', 'listnest' ) . ': ' . number_format_i18n( $row['quantity'] );
            $lines[] = '';
        }

        return rtrim( implode( "\n", $lines ) );
    }

    /**
     * Wraps mail content in a minimal HTML document.
     *
     * @param string $content Escaped HTML.
     * @return string
     */
    public static function wrap_html( $content ) {
        return '<!DOCTYPE html><html><head><meta charset="UTF-8"></head>'
            . '<body style="font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.5;color:#1e1e1e;">'
            . $content
            . '</body></html>';
    }

    /**
     * List name (list post title), falling back to the key.
     *
     * @param string $list_key List key.
     * @return string Plain text.
     */
    public static function list_title( $list_key ) {
        $list_id = ListNest_CPT::get_published_list_id( $list_key );
        $title   = $list_id ? get_the_title( $list_id ) : '';
        $title   = self::plain_text( $title );
        return '' !== $title ? $title : (string) $list_key;
    }

    /**
     * "Label: value" pairs joined with a separator (plain text).
     *
     * @param array  $attributes List of array( label, value ).
     * @param string $separator  Separator.
     * @return string
     */
    public static function attributes_text( array $attributes, $separator ) {
        $pairs = array();
        foreach ( $attributes as $attribute ) {
            $pairs[] = $attribute['label'] . ': ' . $attribute['value'];
        }
        return implode( $separator, $pairs );
    }

    /**
     * Plain text on a single line (tags and entities removed).
     *
     * @param string $text Text.
     * @return string
     */
    public static function plain_text( $text ) {
        $text = wp_strip_all_tags( html_entity_decode( (string) $text, ENT_QUOTES, 'UTF-8' ) );
        return trim( preg_replace( '/\s+/u', ' ', $text ) );
    }
}
