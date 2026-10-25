<?php
// Only WordPress HTTP/cache/escaping boundaries are substituted here.
define('ABSPATH', '/');
define('MINUTE_IN_SECONDS', 60);
if (($argv[1] ?? '')==='disabled') define('HS_ARTICLE_CARDS_KOLODA_ENABLED',false);
$cache = []; $calls = []; $events = [];
class WP_Error {
    public function __construct(public $code, public $message = '', public $data = []) {}
    public function get_error_code() { return $this->code; }
}
function __($text, $domain = '') { return $text; }
function is_wp_error($v) { return $v instanceof WP_Error; }
function get_transient($key) { return $GLOBALS['cache'][$key] ?? false; }
function set_transient($key, $value, $ttl) { $GLOBALS['cache'][$key] = $value; }
function wp_safe_remote_get($url, $args) { $GLOBALS['calls'][] = [$url, $args]; return $GLOBALS['response']; }
function wp_remote_retrieve_body($r) { return $r['body']; }
function wp_remote_retrieve_response_code($r) { return $r['response']['code']; }
function wp_remote_retrieve_header($r, $key) { return $r['headers'][$key] ?? ''; }
function add_query_arg($query, $url) { return $url . '?' . http_build_query($query); }
function do_action($hook, ...$args) { $GLOBALS['events'][] = [$hook, $args]; }
function esc_html($v) { return htmlspecialchars($v, ENT_QUOTES); }
function esc_attr($v) { return htmlspecialchars($v, ENT_QUOTES); }
function esc_url($v) { return htmlspecialchars($v, ENT_QUOTES); }
function check($v, $message) { if (!$v) throw new RuntimeException($message); }
require dirname(__DIR__, 2) . '/wordpress/mu-plugins/hs-article-cards/remote.php';
require dirname(__DIR__, 2) . '/wordpress/mu-plugins/hs-article-cards/render.php';
$response = ['response'=>['code'=>200], 'headers'=>['content-type'=>'application/json; charset=UTF-8','x-wp-totalpages'=>'3'], 'body'=>'[{"id":12}]'];
if (($argv[1] ?? '')==='disabled') {
    check(is_wp_error(HS_Article_Cards_Remote::get('posts', [])) && $calls===[], 'Disable switch blocks requests');
    echo "PASS\n";
    exit;
}
$first = HS_Article_Cards_Remote::get('posts', ['page'=>1]);
check($first['pages'] === 3 && $first['items'][0]['id'] === 12, 'Uses provider pagination');
HS_Article_Cards_Remote::get('posts', ['page'=>1]);
check(count($calls) === 1, 'Caches duplicate GET requests');
check($calls[0][1]['redirection'] === 0 && $calls[0][1]['timeout'] <= 5, 'Bounded safe transport');
foreach (['https://localhost', '../users', 'users', 'posts/1?secret=x'] as $path) {
    check(is_wp_error(HS_Article_Cards_Remote::get($path, [])), 'Rejects arbitrary endpoints');
}
check(count($calls) === 1, 'Invalid endpoints cause no HTTP');
HS_Article_Cards_Remote::get('posts', ['per_page'=>10, '_fields'=>'id,_links,_embedded', '_embed'=>'wp:featuredmedia']);
$embedded_url = $calls[count($calls)-1][0];
check(strpos($embedded_url, '_fields=') < strpos($embedded_url, '_embed='), 'Koloda requires fields before embed to return covers');
foreach ([401,403,429,500,302] as $status) {
    $cache=[];
    $response['response']['code']=$status;
    $response['headers']['retry-after']='120';
    check(is_wp_error(HS_Article_Cards_Remote::get('posts', [])), 'HTTP errors are controlled');
    $count=count($calls);
    HS_Article_Cards_Remote::get('posts', []);
    check(count($calls)===$count, 'Failure cooldown prevents retry storms');
}
$cache=[]; $response=new WP_Error('http_request_failed','private transport details');
$failure=HS_Article_Cards_Remote::get('posts', []);
check(is_wp_error($failure) && !str_contains($failure->message,'private'), 'Transport diagnostics stay private');
foreach (['{broken', '{"id":1}', '[null]'] as $body) {
    $cache=[]; $response=['response'=>['code'=>200], 'headers'=>['content-type'=>'application/json'], 'body'=>$body];
    check(is_wp_error(HS_Article_Cards_Remote::get('posts', [])), 'Reject invalid response schema');
}
$cache=[]; $response['body']='[]';
check(HS_Article_Cards_Remote::get('posts', [])['items']===[], 'Empty result is valid');
$cache=[]; $response['body']=str_repeat('a',524289);
check(is_wp_error(HS_Article_Cards_Remote::get('posts', [])), 'Reject oversized payload');
$cache=[]; $response['body']='[]'; $response['headers']['content-type']='text/html';
check(is_wp_error(HS_Article_Cards_Remote::get('posts', [])), 'Reject non-JSON content type');
$cache=[]; $response['headers']['content-type']='application/json';
check(!is_wp_error(HS_Article_Cards_Remote::get('posts', [])), 'Recovers after failure cooldown expires');
$before=count($calls);
$article=['id'=>12,'title'=>'Маг <script> & советы','url'=>'https://kolodahearthstone.com/guide/','image'=>'https://kolodahearthstone.com/cover.png','date'=>'2026-10-25','date_label'=>'25 октября 2026'];
$html=HS_Article_Cards_Render::html($article,'compact');
check(str_contains($html,'target="_blank"') && str_contains($html,'noopener noreferrer'), 'Opens safely in new tab');
check(str_contains($html,'Маг &lt;script&gt; &amp; советы'), 'Escapes title');
check(str_contains($html,'25 октября 2026'), 'Displays publication date');
check(!str_contains(strip_tags($html),'kolodahearthstone.com'), 'Does not display domain');
check(str_contains(HS_Article_Cards_Render::html($article,'cover'),'hs-article-card--cover'), 'Supports cover layout');
check(count($calls)===$before, 'Rendering never contacts provider');
echo "PASS\n";
