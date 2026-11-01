<?php
/**
 * プラグインの起動と互換性判定。
 *
 * @package OD_Icon_Collection
 */

namespace OD_Icon_Collection;

defined( 'ABSPATH' ) || exit;

/** 標準APIの存在を確認して起動する。 */
final class Plugin {
	/**
	 * 登録管理。
	 *
	 * @var Collection_Registry
	 */
	public $registry;
	/**
	 * 設定画面。
	 *
	 * @var Settings
	 */
	public $settings;

	/** フックを登録する。 */
	public function __construct() {
		$this->registry = new Collection_Registry();
		$this->settings = new Settings( $this->registry );
		add_action( 'init', array( $this->registry, 'register' ) );
		add_action( 'admin_init', array( $this->settings, 'register' ) );
		add_action( 'admin_menu', array( $this->settings, 'menu' ) );
		add_action( 'admin_notices', array( $this, 'notice' ) );
	}

	/**
	 * WordPressのバージョンと公開APIを確認する。
	 *
	 * @return bool
	 */
	public static function supported() {
		global $wp_version;
		return version_compare( $wp_version, '7.1', '>=' ) && function_exists( 'wp_register_icon_collection' ) && function_exists( 'wp_register_icon' );
	}

	/** 未対応環境では管理者へ案内する。 */
	public function notice() {
		if ( ! self::supported() && current_user_can( 'manage_options' ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'OD Icon Collection requires WordPress 7.1 or later with the SVG Icon Registration API.', 'od-icon-collection' ) . '</p></div>';
		}
	}
}
