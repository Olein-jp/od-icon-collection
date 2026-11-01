<?php
/** 実際のWordPress APIを使う登録・設定・RESTの回帰テスト。 */
use OD_Icon_Collection\Collection_Registry;
use OD_Icon_Collection\Icon_Loader;
use OD_Icon_Collection\Plugin;
use OD_Icon_Collection\Settings;
use OD_Icon_Collection\Validator;
use PHPUnit\Framework\TestCase;

final class PluginTest extends TestCase {
	private $option;
	private $user;
	private $filter;
	private $temporary;

	protected function setUp(): void {
		$this->option = get_option( 'od_icon_collection_enabled', null );
		$this->user = get_current_user_id();
		delete_option( 'od_icon_collection_enabled' );
		$this->temporary = sys_get_temp_dir() . '/od-icons-' . uniqid();
		mkdir( $this->temporary );
	}

	protected function tearDown(): void {
		if ( null === $this->option ) { delete_option( 'od_icon_collection_enabled' ); }
		else { update_option( 'od_icon_collection_enabled', $this->option ); }
		wp_set_current_user( $this->user );
		if ( $this->filter ) { remove_filter( 'od_icon_collection_collections', $this->filter ); }
		foreach ( glob( $this->temporary . '/*' ) as $file ) { unlink( $file ); }
		rmdir( $this->temporary );
	}

	private function definition( $icons = null ) {
		copy( __DIR__ . '/fixtures/od-api-probe/probe.svg', $this->temporary . '/home.svg' );
		file_put_contents( $this->temporary . '/manifest.json', json_encode( array( 'collection'=>'od-test', 'source_version'=>'test', 'icons'=>$icons ?? array( array('name'=>'home','label'=>'Home','file'=>'home.svg') ) ) ) );
		return array( 'slug'=>'od-test','label'=>'検証用','description'=>'テストコレクション','icons_directory'=>$this->temporary,'manifest_path'=>$this->temporary.'/manifest.json' );
	}

	public function test_all_collections_registered_and_idempotent(): void {
		$registry = $GLOBALS['od_icon_collection_plugin']->registry;
		$this->assertCount( 3, $registry->results );
		foreach ( $registry->results as $result ) {
			$this->assertGreaterThan( 0, $result['success'] );
			$this->assertSame( $result['target'], $result['success'] );
			$this->assertSame( 0, $result['failure'] );
		}
		$before = $registry->results;
		$registry->register();
		$this->assertSame( $before, $registry->results );
	}

	public function test_names_and_paths(): void {
		foreach ( array( '../home','Home','a/b','a.svg','-home','home-' ) as $name ) { $this->assertFalse( Validator::name( $name ) ); }
		$definition=$this->definition();
		$this->assertIsArray( Validator::collection( $definition ) );
		$bad=$definition; $bad['license']=array(); $this->assertFalse( Validator::collection( $bad ) );
		foreach ( array( 'core','Bad','od-a/b','od-' ) as $slug ) { $bad=$definition; $bad['slug']=$slug; $this->assertFalse( Validator::collection( $bad ) ); }
		foreach ( array( '../home.svg','/home.svg','https://example.com/a.svg','a.php','missing.svg' ) as $file ) { $this->assertFalse( Validator::icon_path( $file,$this->temporary ) ); }
		$this->assertFalse( Validator::path( 'php://filter/resource=/etc/passwd','json' ) );
		$this->assertFalse( Validator::path( '//host/share/file.json','json' ) );
		$this->assertFalse( Validator::path( $this->temporary.'/../home.svg','svg' ) );
		symlink( __FILE__, $this->temporary.'/escape.svg' );
		$this->assertFalse( Validator::icon_path( 'escape.svg',$this->temporary ) );
	}

	public function test_filter_and_duplicate_definitions(): void {
		$definition=$this->definition();
		$this->filter=static function() use ($definition) { return array($definition,$definition,array('slug'=>'od-invalid')); };
		add_filter('od_icon_collection_collections',$this->filter);
		$registry=new Collection_Registry();
		$this->assertCount(1,$registry->definitions());
		$registry->register();
		$this->assertSame(1,$registry->results['od-test']['success']);
		wp_unregister_icon_collection('od-test');
	}

	public function test_disabled_collection_is_not_registered(): void {
		$definition=$this->definition();
		$this->filter=static function() use ($definition) { return array($definition); };
		add_filter('od_icon_collection_collections',$this->filter);
		update_option('od_icon_collection_enabled',array());
		$registry=new Collection_Registry();
		$registry->register();
		$this->assertSame(array(),$registry->results);
		$this->assertFalse(WP_Icon_Collections_Registry::get_instance()->is_registered('od-test'));
	}

	public function test_invalid_metadata_and_missing_files(): void {
		$icons=array(array('name'=>'home','label'=>'Home','file'=>'home.svg'),array('name'=>'home','label'=>'Duplicate','file'=>'home.svg'),array('name'=>'missing','label'=>'Missing','file'=>'missing.svg'),array('name'=>'../bad','label'=>'Bad','file'=>'home.svg'));
		$definition=$this->definition($icons);
		wp_register_icon_collection('od-test',array('label'=>'検証用'));
		$result=(new Icon_Loader())->register($definition);
		$this->assertSame(1,$result['success']);
		$this->assertSame(3,$result['failure']);
		wp_unregister_icon_collection('od-test');
		file_put_contents($this->temporary.'/manifest.json','{bad');
		$this->assertFalse((new Icon_Loader())->manifest($definition));
	}

	public function test_collection_failure_stops_icon_registration(): void {
		$definition=$this->definition();
		wp_register_icon_collection('od-test',array('label'=>'既存'));
		$this->filter=static function() use ($definition) { return array($definition); };
		add_filter('od_icon_collection_collections',$this->filter);
		add_filter('doing_it_wrong_trigger_error','__return_false');
		$registry=new Collection_Registry();
		$registry->register();
		remove_filter('doing_it_wrong_trigger_error','__return_false');
		$this->assertSame(0,$registry->results['od-test']['success']);
		$this->assertFalse(WP_Icons_Registry::get_instance()->is_registered('od-test/home'));
		wp_unregister_icon_collection('od-test');
	}

	public function test_settings_whitelist_and_permissions(): void {
		$settings=new Settings(new Collection_Registry());
		$admin=get_users(array('role'=>'administrator','number'=>1));
		wp_set_current_user($admin[0]->ID);
		$this->assertSame(array('od-feathericon'),$settings->sanitize(array('od-feathericon','../bad','od-feathericon',array())));
		$this->assertSame(array(),$settings->sanitize('invalid'));
		wp_set_current_user(0);
		$this->assertCount(3,$settings->sanitize(array()));
		$settings->register();
		$this->assertFalse(get_registered_settings()['od_icon_collection_enabled']['show_in_rest']);
	}

	public function test_rest_collections_search_and_permissions(): void {
		wp_set_current_user(0);
		$this->assertSame(401,rest_do_request('/wp/v2/icons')->get_status());
		$admin=get_users(array('role'=>'administrator','number'=>1));
		wp_set_current_user($admin[0]->ID);
		foreach($GLOBALS['od_icon_collection_plugin']->registry->definitions() as $slug=>$definition) {
			$request=new WP_REST_Request('GET','/wp/v2/icons');
			$request->set_query_params(array('collection'=>$slug));
			$response=rest_do_request($request);
			$this->assertSame(200,$response->get_status());
			$this->assertSame($GLOBALS['od_icon_collection_plugin']->registry->results[$slug]['success'],count($response->get_data()));
			$request->set_param('search','home');
			$response=rest_do_request($request);
			$this->assertNotEmpty($response->get_data());
			foreach($response->get_data() as $icon) { $this->assertSame($slug,$icon['collection']); $this->assertStringContainsString('home',strtolower($icon['name'].' '.$icon['label'])); }
		}
	}

	public function test_saved_block_attributes_and_rendering(): void {
		foreach(array('od-feathericon/home','od-fontawesome-free/solid-heart','od-fontawesome-free/regular-heart','od-material-symbols/outlined-search') as $name) {
			$block='<!-- wp:icon '.wp_json_encode(array('icon'=>$name,'rotation'=>90,'flipHorizontal'=>true,'style'=>array('color'=>array('text'=>'#cc0000'),'dimensions'=>array('width'=>'48px'))), JSON_UNESCAPED_SLASHES).' /-->';
			$id=wp_insert_post(array('post_title'=>'OD自動テスト','post_status'=>'draft','post_content'=>wp_slash($block)));
			try {
				$this->assertSame($block,get_post_field('post_content',$id));
				$this->assertSame($name,parse_blocks(get_post_field('post_content',$id))[0]['attrs']['icon']);
				$html=do_blocks(get_post_field('post_content',$id));
				foreach(array('<svg','<path','currentColor','#cc0000','48px','rotate: 90deg','is-flip-horizontal') as $expected) { $this->assertStringContainsString($expected,$html); }
			} finally { wp_delete_post($id,true); }
		}
	}

	public function test_unsupported_environment_is_safe(): void {
		global $wp_version;
		$actual=$wp_version;
		try {
			$wp_version='6.8';
			$this->assertFalse(Plugin::supported());
			$registry=new Collection_Registry();
			$registry->register();
			$this->assertSame(array(),$registry->results);
			$admin=get_users(array('role'=>'administrator','number'=>1));
			wp_set_current_user($admin[0]->ID);
			ob_start();
			$GLOBALS['od_icon_collection_plugin']->notice();
			$this->assertStringContainsString('notice-error',ob_get_clean());
			wp_set_current_user(0);
			ob_start();
			$GLOBALS['od_icon_collection_plugin']->notice();
			$this->assertSame('',ob_get_clean());
		} finally { $wp_version=$actual; }
	}

	public function test_uninstall_removes_only_plugin_settings(): void {
		$id=wp_insert_post(array('post_title'=>'ODアンインストール検証','post_status'=>'draft','post_content'=>'<!-- wp:icon {"icon":"od-feathericon/home"} /-->'));
		$content=get_post_field('post_content',$id);
		update_option('od_icon_collection_enabled',array('od-feathericon'));
		if(!defined('WP_UNINSTALL_PLUGIN')) { define('WP_UNINSTALL_PLUGIN','od-icon-collection/od-icon-collection.php'); }
		try {
			require dirname(__DIR__).'/uninstall.php';
			$this->assertNull(get_option('od_icon_collection_enabled',null));
			$this->assertSame($content,get_post_field('post_content',$id));
		} finally { wp_delete_post($id,true); }
	}

	public function test_all_svgs_survive_core_sanitization(): void {
		foreach($GLOBALS['od_icon_collection_plugin']->registry->definitions() as $slug=>$definition) {
			$data=$GLOBALS['od_icon_collection_plugin']->registry->loader->manifest($definition);
			foreach($data['icons'] as $icon) {
				$registered=WP_Icons_Registry::get_instance()->get_registered_icon($slug.'/'.$icon['name']);
				$this->assertSame($icon['label'],$registered['label']);
				$this->assertMatchesRegularExpression('/^[A-Za-z0-9 ]+$/D',$registered['label']);
				$content=$registered['content'];
				$source=file_get_contents($definition['icons_directory'].'/'.$icon['file']);
				$this->assertSame(str_replace('viewBox=', 'viewbox=', $source),$content,$slug.'/'.$icon['name']);
			}
		}
	}
}
