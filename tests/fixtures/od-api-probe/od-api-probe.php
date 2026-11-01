<?php
/**
 * Plugin Name: OD API Probe（開発検証専用）
 * Description: 公式アイコンAPIの検証用。配布ZIPには同梱しない。
 * Author: Koji Kuno
 * Author URI: https://olein-design.com
 */

/** コレクションと1件のアイコンを登録する。 */
function od_icon_collection_probe_register() {
	if ( function_exists( 'wp_register_icon_collection' ) && wp_register_icon_collection( 'od-api-probe', array( 'label' => 'OD API Probe', 'description' => '公式APIの検証用コレクション' ) ) ) {
		wp_register_icon( 'od-api-probe/home', array( 'label' => 'Probe Home', 'file_path' => __DIR__ . '/probe.svg' ) );
	}
}
if ( did_action( 'init' ) ) {
	od_icon_collection_probe_register();
} else {
	add_action( 'init', 'od_icon_collection_probe_register' );
}
