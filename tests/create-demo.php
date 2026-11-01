<?php
/** ブラウザー検証用の下書きを作成する。公開サイトでは実行しない。 */
$id=wp_insert_post(array('post_title'=>'OD Icon Collection 動作検証','post_status'=>'draft','post_content'=>'<!-- wp:icon {"icon":"core/info"} /-->' . "\n" . '<!-- wp:icon {"icon":"core/info"} /-->' . "\n" . '<!-- wp:icon {"icon":"core/info"} /-->'));
update_post_meta($id,'_od_icon_collection_test',true);
echo admin_url('post.php?post='.$id.'&action=edit')."\n";
