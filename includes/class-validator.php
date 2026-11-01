<?php
/**
 * 定義とローカルファイルの検証。
 *
 * @package OD_Icon_Collection
 */

namespace OD_Icon_Collection;

defined( 'ABSPATH' ) || exit;

/** 入力とファイルパスの検証を担当する。 */
final class Validator {
	/**
	 * 安定した識別子か確認する。
	 *
	 * @param mixed $value 識別子.
	 * @return bool
	 */
	public static function name( $value ) {
		return is_string( $value ) && 1 === preg_match( '/^[a-z0-9](?:[a-z0-9_-]*[a-z0-9])?$/D', $value );
	}

	/**
	 * ローカルの絶対パスだけを解決する。
	 *
	 * @param mixed  $path パス.
	 * @param string $extension 必要な拡張子。空ならディレクトリ.
	 * @return string|false
	 */
	public static function path( $path, $extension = '' ) {
		if ( ! is_string( $path ) || false !== strpos( $path, "\0" ) ) {
			return false;
		}
		$normalized = wp_normalize_path( $path );
		if ( ! preg_match( '~^(?:/[^/]|[A-Za-z]:/)~', $normalized ) || preg_match( '~(?:^|/)\.\.(?:/|$)|://~', $normalized ) ) {
			return false;
		}
		$real = realpath( $path );
		if ( false === $real || ! is_readable( $real ) ) {
			return false;
		}
		$real = wp_normalize_path( $real );
		if ( '' === $extension ) {
			return is_dir( $real ) ? $real : false;
		}
		return is_file( $real ) && pathinfo( $real, PATHINFO_EXTENSION ) === $extension ? $real : false;
	}

	/**
	 * コレクションの必須項目を検証する。
	 *
	 * @param mixed $definition コレクション定義.
	 * @return array|false
	 */
	public static function collection( $definition ) {
		if ( ! is_array( $definition ) ) {
			return false;
		}
		foreach ( array( 'slug', 'label', 'description', 'icons_directory', 'manifest_path' ) as $key ) {
			if ( ! isset( $definition[ $key ] ) || ! is_string( $definition[ $key ] ) || '' === trim( $definition[ $key ] ) ) {
				return false;
			}
		}
		foreach ( array( 'id', 'license', 'license_url', 'repository_url' ) as $key ) {
			if ( isset( $definition[ $key ] ) && ! is_string( $definition[ $key ] ) ) {
				return false;
			}
		}
		if ( ! preg_match( '/^od-[a-z0-9]+(?:-[a-z0-9]+)*$/D', $definition['slug'] ) ) {
			return false;
		}
		$directory = self::path( $definition['icons_directory'] );
		$manifest  = self::path( $definition['manifest_path'], 'json' );
		if ( false === $directory || false === $manifest ) {
			return false;
		}
		$definition['icons_directory'] = $directory;
		$definition['manifest_path']   = $manifest;
		return $definition;
	}

	/**
	 * SVGが指定ディレクトリの内側にあることを確認する。
	 *
	 * @param mixed  $file マニフェストの相対ファイル名.
	 * @param string $directory 検証済みディレクトリ.
	 * @return string|false
	 */
	public static function icon_path( $file, $directory ) {
		if ( ! is_string( $file ) || ! preg_match( '/^[a-z0-9][a-z0-9_-]*\.svg$/D', $file ) ) {
			return false;
		}
		$path = self::path( $directory . '/' . $file, 'svg' );
		return false !== $path && 0 === strpos( $path, $directory . '/' ) ? $path : false;
	}
}
