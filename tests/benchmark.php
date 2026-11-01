<?php
/** 1プロセスにつき1要求を計測する。公開サイトでは実行しない。 */
$collection=$args[0] ?? 'all';
wp_set_current_user(1);
$request=new WP_REST_Request('GET','/wp/v2/icons');
if('all'!==$collection) { $request->set_param('collection',$collection); }
if(isset($args[1])) { $request->set_param('search',$args[1]); }
$memory=memory_get_usage(true);
$start=microtime(true);
$response=rest_do_request($request);
$elapsed=microtime(true)-$start;
$data=$response->get_data();
$result=array('wordpress'=>get_bloginfo('version'),'php'=>PHP_VERSION,'collection'=>$collection,'search'=>$args[1]??'','status'=>$response->get_status(),'count'=>count($data),'rest_ms'=>round($elapsed*1000,2),'baseline_mib'=>round($memory/1048576,2),'peak_mib'=>round(memory_get_peak_usage(true)/1048576,2),'response_bytes'=>strlen(wp_json_encode($data)));
$file=__DIR__.'/../docs/benchmark-'.$collection.(isset($args[1])?'-search':'').'.json';
file_put_contents($file,wp_json_encode($result,JSON_PRETTY_PRINT)."\n");
echo wp_json_encode($result)."\n";
