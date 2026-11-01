<?php
/**
 * コレクション定義と登録順序の管理。
 *
 * @package OD_Icon_Collection
 */

namespace OD_Icon_Collection;

defined( 'ABSPATH' ) || exit;

/** コレクションの登録と結果を管理する。 */
final class Collection_Registry {
	/**
	 * 検証済み定義。
	 *
	 * @var array|null
	 */
	private $definitions;
	/**
	 * 登録集計。
	 *
	 * @var array
	 */
	public $results = array();
	/**
	 * ローダー。
	 *
	 * @var Icon_Loader
	 */
	public $loader;
	/**
	 * 重複実行防止。
	 *
	 * @var bool
	 */
	private $registered = false;

	/** ローダーを準備する。 */
	public function __construct() {
		$this->loader = new Icon_Loader();
	}

	/**
	 * フィルター適用後の定義を一度だけ検証する。
	 *
	 * @return array
	 */
	public function definitions() {
		if ( null !== $this->definitions ) {
			return $this->definitions;
		}
		$definitions = array();
		$metadata    = json_decode( file_get_contents( __DIR__ . '/collections.json' ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- 同梱定義。
		foreach ( (array) $metadata as $item ) {
			$base          = dirname( __DIR__ ) . '/assets/icons/' . $item['id'];
			$definitions[] = array_merge(
				$item,
				array(
					'icons_directory' => $base . '/svg',
					'manifest_path'   => $base . '/manifest.json',
				)
			);
		}
		/**
		 * ローカルのマニフェストとSVGディレクトリを持つコレクションを追加・変更する。
		 *
		 * @param array $definitions コレクション定義の一覧.
		 */
		$filtered          = apply_filters( 'od_icon_collection_collections', $definitions );
		$this->definitions = array();
		foreach ( is_array( $filtered ) ? $filtered : array() as $definition ) {
			$valid = Validator::collection( $definition );
			if ( false === $valid || isset( $this->definitions[ $valid['slug'] ] ) ) {
				self::diagnostic( '不正または重複したコレクション定義を拒否しました。' );
				continue;
			}
			$this->definitions[ $valid['slug'] ] = $valid;
		}
		return $this->definitions;
	}

	/**
	 * 保存されていなければ全コレクションを有効とする。
	 *
	 * @return array
	 */
	public function enabled() {
		$stored = get_option( 'od_icon_collection_enabled', null );
		$slugs  = array_keys( $this->definitions() );
		return null === $stored ? $slugs : array_values( array_intersect( $slugs, is_array( $stored ) ? $stored : array() ) );
	}

	/** 有効な定義を判定してから、コレクション、個別アイコンの順に登録する。 */
	public function register() {
		if ( $this->registered || ! Plugin::supported() ) {
			return;
		}
		$this->registered = true;
		$enabled          = $this->enabled();
		foreach ( $this->definitions() as $slug => $definition ) {
			if ( ! in_array( $slug, $enabled, true ) ) {
				continue;
			}
			if ( ! wp_register_icon_collection(
				$slug,
				array(
					'label'       => $definition['label'],
					'description' => $definition['description'],
				)
			) ) {
				$this->results[ $slug ] = array(
					'target'  => 0,
					'success' => 0,
					'failure' => 1,
					'errors'  => array( 'コレクションの登録に失敗しました。' ),
				);
			} else {
				$this->results[ $slug ] = $this->loader->register( $definition );
			}
			foreach ( array_unique( $this->results[ $slug ]['errors'] ) as $error ) {
				self::diagnostic( $slug . ': ' . $error );
			}
		}
	}

	/**
	 * 開発時に診断を残す。
	 *
	 * @param string $message 診断内容.
	 */
	public static function diagnostic( $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- 開発時の登録失敗を記録する仕様。
			error_log( 'OD Icon Collection: ' . $message );
		}
	}
}
