"""Editorial access contract, executed in PHP without production data."""
import json
from pathlib import Path
import subprocess
import unittest

ROOT = Path(__file__).resolve().parents[1]
ADAPTER = ROOT / 'wordpress/mu-plugins/hs-manacost-reader/comments-editorial.php'


class ReaderCommentsEditorialTests(unittest.TestCase):
    def evaluate(self, code, environment='staging', enabled=True, origin='https://test.hs-manacost.ru', production_gate=False):
        fixture = r'''<?php
define('ABSPATH', '/fixture/');
define('HS_MANACOST_READER_COMMENTS_ENABLED', ENABLED);
define('HS_MANACOST_READER_ALLOW_PRODUCTION_COMMUNITY', PRODUCTION_GATE);
define('HS_MANACOST_READER_COMMENT_POSTS', array(17));
define('HS_MANACOST_READER_EDITORIAL_KEY', str_repeat('x', 43));
function wp_get_environment_type() { return ENVIRONMENT; }
function home_url($path = '') { return ORIGIN . $path; }
function wp_parse_url($url, $component = -1) { return parse_url($url, $component); }
class WP_Post { public $ID = 17; public $post_type = 'post'; public $post_status = 'publish'; public $post_password = ''; public $post_title = 'Тестовая статья'; public $post_content = 'Открытая статья'; }
class WP_Error { public function __construct(public $code, public $message, public $data) {} }
function is_wp_error($value) { return $value instanceof WP_Error; }
class WP_REST_Response { public function __construct(public $data, public $status = 200, public $headers = array()) {} }
class WP_REST_Request {
    public $headers = array(); public $body = '{"ids":[17,18]}';
    function get_header($key) { return $this->headers[strtolower($key)] ?? null; }
    function get_body() { return $this->body; }
    function get_method() { return 'POST'; }
    function get_route() { return '/manacost-reader/v1/threads'; }
}
$post = new WP_Post();
function get_post($id) { global $post; return $id === 17 ? $post : null; }
$permalink = ORIGIN . '/test-article/';
function get_permalink($post) { global $permalink; return $permalink; }
function wp_strip_all_tags($text) { return strip_tags($text); }
function __($text, $domain = '') { return $text; }
require ADAPTER;
'''.replace('ENABLED);', ('true' if enabled else 'false') + ');').replace('PRODUCTION_GATE);', ('true' if production_gate else 'false') + ');').replace('ENVIRONMENT;', json.dumps(environment) + ';').replace('ORIGIN', json.dumps(origin)).replace('require ADAPTER;', 'require ' + json.dumps(str(ADAPTER)) + ';')
        result = subprocess.run(['php'], input=fixture + code, text=True, capture_output=True, check=True)
        return json.loads(result.stdout)

    def test_activation_requires_exact_staging_and_explicit_flag(self):
        self.assertTrue(self.evaluate('echo json_encode(hs_reader_comments_enabled());'))
        self.assertFalse(self.evaluate('echo json_encode(hs_reader_comments_enabled());', enabled=False))
        self.assertFalse(self.evaluate('echo json_encode(hs_reader_comments_enabled());', environment='production'))
        self.assertFalse(self.evaluate('echo json_encode(hs_reader_comments_enabled());', environment='production', origin='https://hs-manacost.ru'))
        self.assertTrue(self.evaluate('echo json_encode(hs_reader_comments_enabled());', environment='production', origin='https://hs-manacost.ru', production_gate=True))
        self.assertFalse(self.evaluate('echo json_encode(hs_reader_comments_enabled());', environment='production', origin='https://hs-manacost.com', production_gate=True))

    def test_only_reviewed_published_plain_article_is_eligible(self):
        result = self.evaluate('echo json_encode(array(hs_reader_comment_article(17), hs_reader_comment_article(18)));')
        self.assertEqual(result, [{'postId': 17, 'allowed': True, 'title': 'Тестовая статья', 'path': '/test-article/'}, {'postId': 18, 'allowed': False}])
        for mutation in ("$post->post_status='draft';", "$post->post_status='private';", "$post->post_status='future';", "$post->post_password='private';", "$post->post_type='page';", "$post->post_content='[private]hidden[/private]';"):
            self.assertEqual(self.evaluate(mutation + 'echo json_encode(hs_reader_comment_article(17));'), {'postId': 17, 'allowed': False})

    def test_reviewed_quote_shortcode_is_eligible_but_other_shortcodes_are_rejected(self):
        allowed = self.evaluate("$post->post_content='[su_quote style=\\\"default\\\"]Цитата[/su_quote]'; echo json_encode(hs_reader_comment_article(17));")
        rejected = self.evaluate("$post->post_content='[su_quote]Цитата[/su_quote][private]Скрыто[/private]'; echo json_encode(hs_reader_comment_article(17));")
        self.assertTrue(allowed['allowed'])
        self.assertEqual(rejected, {'postId': 17, 'allowed': False})

    def test_signed_batch_is_strict_no_cache_and_does_not_leak_rejected_article(self):
        code = '''$request = new WP_REST_Request();
$time = (string) time();
$request->headers['x-reader-time'] = $time;
$request->headers['x-reader-signature'] = hash_hmac('sha256', "POST\\n/manacost-reader/v1/threads\\n" . $time . "\\n" . $request->body, HS_MANACOST_READER_EDITORIAL_KEY);
echo json_encode(array(hs_reader_editorial_permission($request), hs_reader_editorial_threads($request)));'''
        permitted, response = self.evaluate(code)
        self.assertTrue(permitted)
        self.assertEqual(response['data']['site'], 'test.hs-manacost.ru')
        self.assertEqual(response['headers']['Cache-Control'], 'private, no-store')
        self.assertEqual(response['data']['threads'][1], {'postId': 18, 'allowed': False})

    def test_production_editorial_response_is_bound_to_the_exact_live_site(self):
        code = '''$request = new WP_REST_Request();
$time = (string) time();
$request->headers['x-reader-time'] = $time;
$request->headers['x-reader-signature'] = hash_hmac('sha256', "POST\\n/manacost-reader/v1/threads\\n" . $time . "\\n" . $request->body, HS_MANACOST_READER_EDITORIAL_KEY);
echo json_encode(array(hs_reader_editorial_permission($request), hs_reader_editorial_threads($request)));'''
        permitted, response = self.evaluate(code, environment='production', origin='https://hs-manacost.ru', production_gate=True)
        self.assertTrue(permitted)
        self.assertEqual(response['data']['site'], 'hs-manacost.ru')
        self.assertEqual(response['data']['threads'][0]['path'], '/test-article/')

    def test_favorites_use_the_same_signed_editorial_boundary(self):
        code = '''class FavoriteRequest extends WP_REST_Request { function get_route() { return '/manacost-reader/v1/favorites'; } }
$request = new FavoriteRequest();
$time = (string) time();
$request->headers['x-reader-time'] = $time;
$request->headers['x-reader-signature'] = hash_hmac('sha256', "POST\\n/manacost-reader/v1/favorites\\n" . $time . "\\n" . $request->body, HS_MANACOST_READER_EDITORIAL_KEY);
echo json_encode(array(hs_reader_editorial_permission($request), hs_reader_editorial_favorites($request)));'''
        permitted, response = self.evaluate(code)
        self.assertTrue(permitted)
        self.assertEqual(response['data']['threads'][0], {'postId': 17, 'allowed': True, 'title': 'Тестовая статья', 'path': '/test-article/'})
        self.assertEqual(response['data']['threads'][1], {'postId': 18, 'allowed': False})

    def test_false_or_malformed_permalink_is_an_indistinguishable_denial(self):
        for value in ('false', 'null', '17', "'https://evil.test/hidden/'", "'not a URL'"):
            self.assertEqual(self.evaluate('$permalink=' + value + '; echo json_encode(hs_reader_comment_article(17));'), {'postId': 17, 'allowed': False})
        for body in ('{}', '{"ids":[17,17]}', '{"ids":["17"]}', '{"ids":[17],"paid":true}', '{"ids":[]}'):
            result = self.evaluate('$r = new WP_REST_Request(); $r->body=' + json.dumps(body) + '; echo json_encode(hs_reader_editorial_threads($r));')
            self.assertEqual(result['data']['status'], 400)

    def test_missing_bad_expired_and_browser_signatures_are_denied(self):
        for headers in ({}, {'x-reader-signature': 'bad'}, {'x-reader-time': '1', 'x-reader-signature': 'a' * 64}, {'origin': 'https://test.hs-manacost.ru'}):
            result = self.evaluate('$r = new WP_REST_Request(); $r->headers = json_decode(' + json.dumps(json.dumps(headers)) + ', true); echo json_encode(hs_reader_editorial_permission($r));')
            self.assertEqual(result['data']['status'], 403)

    def test_loader_limits_template_and_assets_and_never_loads_private_editor_for_public_profile(self):
        loader = json.dumps(str(ROOT / 'wordpress/mu-plugins/hs-manacost-reader.php'))
        community = json.dumps(str(ROOT / 'wordpress/mu-plugins/hs-manacost-reader/comments-loader.php'))
        setup = '''
function add_action(...$args) {} function add_filter(...$args) {}
function get_page_by_path($path) { $page = new WP_Post(); $page->ID=22; $page->post_content='[hs_manacost_reader_account]'; return $page; }
function has_shortcode(...$args) { return true; }
function is_page($id) { global $account; return $account; }
function is_singular($type) { global $account; return !$account; }
function get_the_ID() { global $article_id; return $article_id; }
function content_url($path) { return '/wp-content/' . $path; }
function wp_unslash($value) { return $value; }
function sanitize_text_field($value) { return strip_tags($value); }
function wp_enqueue_style($name, ...$args) { global $assets; $assets[]=$name; }
function wp_enqueue_script($name, ...$args) { global $assets; $assets[]=$name; }
$account=false; $article_id=17; $assets=array();
''' + 'require ' + loader + '; require ' + community + ';'
        code = setup + '''
$pilot=hs_reader_comments_template('/native.php'); hs_reader_comments_assets(); $pilot_assets=$assets;
$article_id=18; $assets=array(); $outside=hs_reader_comments_template('/native.php'); hs_reader_comments_assets(); $outside_assets=$assets;
$account=true; $_GET['reader']='123e4567-e89b-42d3-a456-426614174000'; $assets=array();
hs_manacost_reader_assets(); hs_reader_comments_assets();
$public_assets=$assets; $_GET['reader']=array('malformed');
echo json_encode(array($pilot, $pilot_assets, $outside, $outside_assets, $public_assets, hs_reader_public_profile_request(), hs_reader_public_profile_id()));
'''
        pilot, assets, outside, outside_assets, public_assets, requested, invalid_id = self.evaluate(code)
        self.assertTrue(pilot.endswith('/reader-comments-page.php'))
        self.assertEqual(assets, ['hs-manacost-reader-ui', 'hs-manacost-reader-comments', 'hs-manacost-reader-favorite', 'hs-manacost-reader-favorite', 'hs-manacost-reader-community-ui', 'hs-manacost-reader-comments'])
        self.assertEqual(outside, '/native.php')
        self.assertEqual(outside_assets, [])
        self.assertEqual(public_assets, ['hs-manacost-reader-ui', 'hs-manacost-reader-public-profile', 'hs-manacost-reader-public-profile'])
        self.assertTrue(requested)
        self.assertEqual(invalid_id, '')
