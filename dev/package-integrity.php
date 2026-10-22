<?php
/** Verify installed release files match the actual ZIP byte for byte. */
$zip = new ZipArchive();
if ( true !== $zip->open( WP_CONTENT_DIR . '/listnest-release/listnest-1.0.0.zip' ) ) {
    throw new RuntimeException( 'Cannot read release ZIP.' );
}
$count = 0;
for ( $i = 0; $i < $zip->numFiles; ++$i ) {
    $name = $zip->getNameIndex( $i );
    if ( '/' === substr( $name, -1 ) ) {
        continue;
    }
    if ( 0 !== strpos( $name, 'listnest/' ) || false !== strpos( $name, '..' ) ) {
        throw new RuntimeException( 'Unexpected ZIP entry.' );
    }
    $path = WP_PLUGIN_DIR . '/' . $name;
    if ( ! is_file( $path ) || hash( 'sha256', $zip->getFromIndex( $i ) ) !== hash_file( 'sha256', $path ) ) {
        throw new RuntimeException( 'Installed file differs from ZIP: ' . $name );
    }
    ++$count;
}
$zip->close();
WP_CLI::success( $count . ' installed files match the release ZIP.' );
