<?php
/** WordPress上で実行する公式APIの検証。 */
require_once __DIR__ . '/fixtures/od-api-probe/od-api-probe.php';
wp_set_current_user( 1 );
$checks = array();
foreach ( array( '/wp/v2/icon-collections', '/wp/v2/icons?collection=od-api-probe', '/wp/v2/icons?collection=od-api-probe&search=Probe' ) as $url ) {
	$parts = wp_parse_url( $url );
	$request = new WP_REST_Request( 'GET', $parts['path'] );
	parse_str( $parts['query'] ?? '', $params );
	$request->set_query_params( $params );
	$start = microtime( true );
	$response = rest_do_request( $request );
	$checks[ $url ] = array( 'status' => $response->get_status(), 'count' => count( $response->get_data() ), 'ms' => round( ( microtime( true ) - $start ) * 1000, 2 ) );
	if ( 200 !== $response->get_status() ) { throw new RuntimeException( 'REST検証失敗' ); }
}
$block = '<!-- wp:icon {"icon":"od-api-probe/home","style":{"color":{"text":"#cc0000"},"dimensions":{"width":"48px"}},"rotation":90,"flipHorizontal":true} /-->';
$id = wp_insert_post( array( 'post_title' => 'OD Icon API 検証', 'post_content' => $block, 'post_status' => 'publish' ), true );
if ( is_wp_error( $id ) ) { throw new RuntimeException( $id->get_error_message() ); }
$saved = get_post_field( 'post_content', $id );
$html = do_blocks( $saved );
foreach ( array( 'currentColor', '#cc0000', '48px', 'rotate: 90deg', 'is-flip-horizontal', '<path' ) as $expected ) {
	if ( false === strpos( $html, $expected ) ) { throw new RuntimeException( '描画検証失敗: ' . $expected ); }
}
$checks['save-render'] = array( 'post_id' => $id, 'url' => get_permalink( $id ), 'content_unchanged' => $block === $saved, 'html' => $html );
wp_set_current_user( 0 );
$checks['anonymous-status'] = rest_do_request( new WP_REST_Request( 'GET', '/wp/v2/icons' ) )->get_status();
echo wp_json_encode( $checks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\n";
