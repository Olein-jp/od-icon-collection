<?php
/**
 * Plugin Name: OD Icon Collection
 * Plugin URI: https://github.com/Olein-jp/od-icon-collection
 * Description: An icon collection for WordPress.
 * Version: 1.1.0
 * Requires at least: 7.1
 * Requires PHP: 7.4
 * Author: Koji Kuno
 * Author URI: https://olein-design.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: od-icon-collection
 * Domain Path: /languages
 * Update URI: https://github.com/Olein-jp/od-icon-collection
 *
 * @package OD_Icon_Collection
 */

defined( 'ABSPATH' ) || exit;

/**
 * 翻訳ファイルの場所を登録する。
 *
 * @return void
 */
function od_icon_collection_load_textdomain() {
	load_plugin_textdomain( 'od-icon-collection', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'od_icon_collection_load_textdomain' );

/**
 * GitHub Releases 経由の更新を登録する。
 *
 * @return void
 */
function od_icon_collection_initialize_updater() {
	$autoload = __DIR__ . '/vendor/autoload.php';
	if ( ! file_exists( $autoload ) ) {
		add_action( 'admin_notices', 'od_icon_collection_missing_dependencies_notice' );
		return;
	}

	require_once $autoload;

	new \Inc2734\WP_GitHub_Plugin_Updater\Bootstrap(
		plugin_basename( __FILE__ ),
		'Olein-jp',
		'od-icon-collection'
	);
}
add_action( 'init', 'od_icon_collection_initialize_updater' );

/**
 * 開発用チェックアウトで依存関係が不足している場合に案内する。
 *
 * @return void
 */
function od_icon_collection_missing_dependencies_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	?>
	<div class="notice notice-error"><p><?php esc_html_e( 'OD Icon Collection: Install Composer dependencies before using GitHub updates.', 'od-icon-collection' ); ?></p></div>
	<?php
}

foreach ( array( 'validator', 'icon-loader', 'collection-registry', 'settings', 'plugin' ) as $od_icon_collection_class ) {
	require_once __DIR__ . '/includes/class-' . $od_icon_collection_class . '.php';
}
unset( $od_icon_collection_class );
$GLOBALS['od_icon_collection_plugin'] = new \OD_Icon_Collection\Plugin();
