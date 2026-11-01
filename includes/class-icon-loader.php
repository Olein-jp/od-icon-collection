<?php
/**
 * マニフェストの読み込みと個別アイコン登録。
 *
 * @package OD_Icon_Collection
 */

namespace OD_Icon_Collection;

defined( 'ABSPATH' ) || exit;

/** SVGの内容を読み込まず、ファイルパスで登録する。 */
final class Icon_Loader {
	/**
	 * リクエスト内の解析結果。
	 *
	 * @var array
	 */
	private $manifests = array();

	/**
	 * 同じマニフェストの再解析を避ける。
	 *
	 * @param array $definition 検証済み定義.
	 * @return array|false
	 */
	public function manifest( $definition ) {
		$path = $definition['manifest_path'];
		if ( ! array_key_exists( $path, $this->manifests ) ) {
			$data = false;
			if ( filesize( $path ) <= 8 * 1024 * 1024 ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- 検証済みローカルファイル。
				$data = json_decode( file_get_contents( $path ), true );
			}
			$this->manifests[ $path ] = is_array( $data ) ? $data : false;
		}
		$data = $this->manifests[ $path ];
		if ( ! is_array( $data ) || ( $data['collection'] ?? null ) !== $definition['slug'] || ! isset( $data['icons'], $data['source_version'] ) || ! is_array( $data['icons'] ) || ! is_string( $data['source_version'] ) || count( $data['icons'] ) > 20000 ) {
			return false;
		}
		return $data;
	}

	/**
	 * アイコンを検証して登録し、集計する。
	 *
	 * @param array $definition 検証済み定義.
	 * @return array
	 */
	public function register( $definition ) {
		$manifest = $this->manifest( $definition );
		$result   = array(
			'target'  => 0,
			'success' => 0,
			'failure' => 0,
			'errors'  => array(),
		);
		if ( false === $manifest ) {
			$result['failure']  = 1;
			$result['errors'][] = 'マニフェストが不正です。';
			return $result;
		}
		$result['target'] = count( $manifest['icons'] );
		$seen             = array();
		foreach ( $manifest['icons'] as $icon ) {
			$name = is_array( $icon ) ? ( $icon['name'] ?? null ) : null;
			$path = is_array( $icon ) ? Validator::icon_path( $icon['file'] ?? null, $definition['icons_directory'] ) : false;
			if ( ! Validator::name( $name ) || isset( $seen[ $name ] ) || ! isset( $icon['label'] ) || ! is_string( $icon['label'] ) || '' === trim( $icon['label'] ) || false === $path ) {
				++$result['failure'];
				$result['errors'][] = '不正なメタデータ、重複、またはSVGファイルの欠損。';
				continue;
			}
			$seen[ $name ] = true;
			if ( wp_register_icon(
				$definition['slug'] . '/' . $name,
				array(
					'label'     => $icon['label'],
					'file_path' => $path,
				)
			) ) {
				++$result['success'];
			} else {
				++$result['failure'];
				$result['errors'][] = 'アイコンの登録に失敗しました: ' . $name;
			}
		}
		return $result;
	}
}
