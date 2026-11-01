<?php
/** Lucideの標準UI確認用の下書きをローカルに作成する。 */
$id=wp_insert_post(array('post_title'=>'Lucide 動作検証','post_status'=>'draft','post_content'=>'<!-- wp:icon {"icon":"core/info"} /-->'));
update_post_meta($id,'_od_icon_collection_test',true);
echo admin_url('post.php?post='.$id.'&action=edit')."\n";
