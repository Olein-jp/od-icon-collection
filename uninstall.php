<?php
/**
 * アンインストール時に設定だけを削除する。投稿本文には触れない。
 *
 * @package OD_Icon_Collection
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( is_multisite() ) {
	$od_icon_collection_offset = 0;
	do {
		$od_icon_collection_sites = get_sites(
			array(
				'fields' => 'ids',
				'number' => 100,
				'offset' => $od_icon_collection_offset,
			)
		);
		foreach ( $od_icon_collection_sites as $od_icon_collection_site ) {
			switch_to_blog( $od_icon_collection_site );
			delete_option( 'od_icon_collection_enabled' );
			restore_current_blog();
		}
		$od_icon_collection_offset += 100;
		$od_icon_collection_count   = count( $od_icon_collection_sites );
	} while ( 100 === $od_icon_collection_count );
} else {
	delete_option( 'od_icon_collection_enabled' );
}
