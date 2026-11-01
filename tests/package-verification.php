<?php
/** 配布ZIPを独立インストールした開発環境だけで実行する検証。 */
$mode=$args[0]??'initial';
$key='od_icon_collection_package_verification';
if(isset($args[1]) && get_bloginfo('version')!==$args[1]) { throw new RuntimeException('WordPressの検証バージョンが不一致です'); }
if('initial'===$mode) {
 $blocks=array();
 foreach(array('od-feathericon/home','od-fontawesome-free/regular-heart','od-material-symbols/outlined-search') as $index=>$name) {
  $blocks[]=serialize_block(array('blockName'=>'core/icon','attrs'=>array('icon'=>$name,'style'=>array('color'=>array('text'=>'#123456'),'dimensions'=>array('width'=>(48+16*$index).'px'))),'innerBlocks'=>array(),'innerHTML'=>'','innerContent'=>array()));
 }
 $content=implode("\n",$blocks);
 $id=wp_insert_post(array('post_title'=>'配布ZIP検証','post_status'=>'publish','post_content'=>wp_slash($content)));
 update_option('od_icon_collection_enabled',array('od-feathericon','od-fontawesome-free','od-material-symbols'));
 update_option($key,array('id'=>$id,'hash'=>hash('sha256',get_post_field('post_content',$id))));
}
$state=get_option($key);
if(!$state || hash('sha256',get_post_field('post_content',$state['id']))!==$state['hash']) { throw new RuntimeException('保存済み本文が変化しました'); }
$content=get_post_field('post_content',$state['id']);
$html=do_blocks($content);
$count=substr_count($html,'<svg');
if('uninstalled'===$mode) {
 if(0!==$count || null!==get_option('od_icon_collection_enabled',null)) { throw new RuntimeException('設定の削除または未登録時の表示が不正です'); }
} else {
 if(3!==$count || false===strpos($html,'#123456')) { throw new RuntimeException('描画できません'); }
 foreach(array('48px','64px','80px') as $width) { if(false===strpos($html,$width)) { throw new RuntimeException('サイズ不一致'); } }
 $registry=$GLOBALS['od_icon_collection_plugin']->registry;
 if(6681!==array_sum(array_column($registry->results,'success'))) { throw new RuntimeException('登録数不一致'); }
 $base=WP_PLUGIN_DIR.'/od-icon-collection';
 foreach(array('node_modules','tests','scripts','.cache') as $file) { if(file_exists($base.'/'.$file)) { throw new RuntimeException('開発ファイルが混入しています'); } }
 if(file_exists($base.'/vendor/phpunit') || !file_exists($base.'/licenses/CC-BY-4.0.txt')) { throw new RuntimeException('配布依存・ライセンスが不正です'); }
}
echo wp_json_encode(array('mode'=>$mode,'wordpress'=>get_bloginfo('version'),'php'=>PHP_VERSION,'svg_count'=>$count,'content_unchanged'=>true,'url'=>get_permalink($state['id'])))."\n";
