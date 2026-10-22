<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Template locator with theme overrides.
 *
 * Lookup order for each requested file name (first match wins):
 *  1. {child theme}/listnest/{name}
 *  2. {parent theme}/listnest/{name}
 *  3. {plugin}/templates/{name}
 *
 * When several names are given (e.g. a list-specific template and the
 * generic one), every name is tried in a theme directory before moving to
 * the next directory.
 */
class ListNest_Template {

    /**
     * Theme sub-directory that holds template overrides.
     */
    const THEME_DIR = 'listnest';

    /**
     * Finds the first existing template.
     *
     * @param string|string[] $template_names File names relative to the template directories.
     * @param string          $list_key       List key the template is rendered for ('' when not list specific).
     * @return string Absolute path, or '' when nothing was found.
     */
    public static function locate( $template_names, $list_key = '' ) {
        $template_names = array_filter( array_map( array( __CLASS__, 'sanitize_name' ), (array) $template_names ) );
        $located        = '';

        $theme_dirs = array_unique( array( get_stylesheet_directory(), get_template_directory() ) );
        foreach ( $theme_dirs as $theme_dir ) {
            foreach ( $template_names as $name ) {
                $file = trailingslashit( $theme_dir ) . self::THEME_DIR . '/' . $name;
                if ( is_file( $file ) ) {
                    $located = $file;
                    break 2;
                }
            }
        }

        if ( '' === $located ) {
            $located = self::locate_in_plugin( $template_names );
        }

        /**
         * Filters the resolved template path.
         *
         * @param string   $located        Absolute path ('' when nothing was found).
         * @param string[] $template_names Requested file names, most specific first.
         * @param string   $list_key       List key, or '' when not list specific.
         */
        $located = apply_filters( 'listnest_locate_template', $located, $template_names, $list_key );

        return ( is_string( $located ) && '' !== $located && is_file( $located ) ) ? $located : '';
    }

    /**
     * Looks for the templates in the plugin templates directory. Resolved
     * paths must stay inside that directory.
     *
     * @param string[] $template_names Sanitized file names.
     * @return string
     */
    private static function locate_in_plugin( $template_names ) {
        $templates_dir = realpath( LISTNEST_PATH . 'templates' );
        if ( false === $templates_dir ) {
            return '';
        }

        foreach ( $template_names as $name ) {
            $candidate = realpath( $templates_dir . DIRECTORY_SEPARATOR . $name );
            if ( false !== $candidate && 0 === strpos( $candidate, $templates_dir . DIRECTORY_SEPARATOR ) && is_file( $candidate ) ) {
                return $candidate;
            }
        }

        return '';
    }

    /**
     * Allows only plain "name.php" file names (no directories).
     *
     * @param mixed $name File name.
     * @return string
     */
    private static function sanitize_name( $name ) {
        if ( ! is_string( $name ) || ! preg_match( '/^[a-z0-9_-]+\.php$/', $name ) ) {
            return '';
        }
        return $name;
    }
}
