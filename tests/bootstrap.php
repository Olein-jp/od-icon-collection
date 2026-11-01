<?php
/** ローカルの開発専用WordPressでテストする。 */
$root = getenv( 'OD_ICON_COLLECTION_WP_ROOT' ) ?: '/var/www/html';
if ( ! is_file( $root . '/wp-load.php' ) ) {
	throw new RuntimeException( '開発用WordPressのパスを指定してください。' );
}
require_once $root . '/wp-load.php';
if ( ! isset( $GLOBALS['od_icon_collection_plugin'] ) ) {
	throw new RuntimeException( '開発用サイトでプラグインを有効化してください。' );
}
