<?php
/** 開発用の検証投稿だけでライフサイクルを検証する。 */
$id=(int)($args[1]??13);
if(!get_post_meta($id,'_od_icon_collection_test',true)) { throw new RuntimeException('専用の検証投稿が必要です'); }
$state=__DIR__.'/../.cache/lifecycle.json';
$content=get_post_field('post_content',$id);
$mode=$args[0]??'snapshot';
if('snapshot'===$mode) {
 file_put_contents($state,json_encode(array('content'=>$content,'option'=>get_option('od_icon_collection_enabled',null),'theme'=>get_stylesheet())));
 echo "検証前の本文・設定・テーマを記録しました。\n";
 return;
}
$before=json_decode(file_get_contents($state),true);
if($before['content']!==$content) { throw new RuntimeException('本文が変更されています'); }
$expected=(int)($args[2]??3);
$html=do_blocks($content);
$actual=substr_count($html,'<svg');
if($actual!==$expected) { throw new RuntimeException("SVG数が不一致: {$actual} != {$expected}"); }
if(!WP_Icon_Collections_Registry::get_instance()->is_registered('core')) { throw new RuntimeException('標準コレクションが消えています'); }
echo json_encode(array('mode'=>$mode,'post'=>$id,'svg_count'=>$actual,'content_unchanged'=>true,'theme'=>get_stylesheet(),'option'=>get_option('od_icon_collection_enabled',null)),JSON_UNESCAPED_UNICODE)."\n";
