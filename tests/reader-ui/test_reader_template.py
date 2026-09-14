import base64
import json
import pathlib
import re
import subprocess
import tempfile
import unittest

ROOT = pathlib.Path(__file__).parents[2]
LOADER = ROOT / 'wordpress/mu-plugins/hs-manacost-reader.php'


class ReaderTemplateTests(unittest.TestCase):
    def test_account_cache_bypass_matches_only_the_reader_route(self):
        fixture = r'''
define('ABSPATH', '/fixture/');
function add_action(...$args) {}
function add_filter(...$args) {}
function wp_unslash($value) { return stripslashes($value); }
require $argv[1];
$_SERVER['REQUEST_URI'] = $argv[2];
echo hs_manacost_reader_is_account_request() ? 'yes' : 'no';
'''
        cases = {
            '/account/': 'yes',
            '/account': 'yes',
            '/Account/': 'yes',
            '/ACCOUNT': 'yes',
            '/%61ccount/': 'yes',
            '/%41CCOUNT/?reader=public-id': 'yes',
            '/account/?reader=public-id': 'yes',
            '//account//': 'yes',
            '/./account/': 'yes',
            '/news/../account/': 'yes',
            '/account/profile/': 'no',
            '/Account/profile/': 'no',
            '/news/account/': 'no',
            '/account%2F': 'yes',
            '/%252561ccount/': 'no',
        }
        for uri, expected in cases.items():
            with self.subTest(uri=uri):
                result = subprocess.run(
                    ['php', '-r', fixture, str(LOADER), uri],
                    check=True,
                    capture_output=True,
                    text=True,
                )
                self.assertEqual(result.stdout, expected)

    def test_reader_application_host_allowlist_is_exact(self):
        fixture = r'''
define('ABSPATH', '/fixture/');
function add_action(...$args) {}
function add_filter(...$args) {}
function wp_unslash($value) { return stripslashes($value); }
$_SERVER['HTTP_HOST'] = $argv[2];
require $argv[1];
echo hs_manacost_reader_is_application_host() ? 'yes' : 'no';
'''
        cases = {
            'hs-manacost.ru': 'yes',
            'HS-MANACOST.RU:443': 'yes',
            'test.hs-manacost.ru.': 'yes',
            'hs-manacost.com': 'no',
            'www.hs-manacost.ru': 'no',
            'hs-manacost.ru.example': 'no',
            '': 'no',
        }
        for host, expected in cases.items():
            with self.subTest(host=host):
                result = subprocess.run(
                    ['php', '-r', fixture, str(LOADER), host],
                    check=True,
                    capture_output=True,
                    text=True,
                )
                self.assertEqual(result.stdout, expected)

    def test_account_marker_detection_does_not_require_shortcode_registration(self):
        fixture = r'''
define('ABSPATH', '/fixture/');
function add_action(...$args) {}
function add_filter(...$args) {}
require $argv[1];
echo hs_manacost_reader_content_has_account_shortcode($argv[2]) ? 'yes' : 'no';
'''
        cases = {
            '[hs_manacost_reader_account]': 'yes',
            '[hs_manacost_reader_account /]': 'yes',
            '[hs_manacost_reader_account mode="compact"]': 'yes',
            '[[hs_manacost_reader_account]]': 'no',
            '[hs_manacost_reader_accounting]': 'no',
            'hs_manacost_reader_account': 'no',
        }
        for content, expected in cases.items():
            with self.subTest(content=content):
                result = subprocess.run(
                    ['php', '-r', fixture, str(LOADER), content],
                    check=True,
                    capture_output=True,
                    text=True,
                )
                self.assertEqual(result.stdout, expected)

    def test_account_is_a_permanent_wp_rocket_cache_reject(self):
        fixture = r'''
define('ABSPATH', '/fixture/');
function add_action(...$args) {}
function add_filter($name, $callback, ...$args) { $GLOBALS['filters'][$name] = $callback; }
require $argv[1];
$callback = $GLOBALS['filters']['rocket_cache_reject_uri'] ?? null;
echo json_encode([
    'registered' => is_callable($callback),
    'patterns' => is_callable($callback) ? $callback(['/existing']) : [],
]);
'''
        result = subprocess.run(
            ['php', '-r', fixture, str(LOADER)],
            check=True,
            capture_output=True,
            text=True,
        )
        payload = json.loads(result.stdout)
        self.assertTrue(payload['registered'])
        self.assertEqual(payload['patterns'][0], '/existing')
        self.assertNotRegex(payload['patterns'][-1], r'[\[\]^]')
        pattern = re.compile(payload['patterns'][-1], re.IGNORECASE)
        for uri in (
            '/account',
            '/account/',
            '/Account/',
            '/%61ccount/',
            '/account%2F',
            '//account//',
            '/./account/',
            '/news/../account/',
            '/news/account/',
            '/account/.',
            '/account/./',
            '/account/topic/..',
            '/news/../account/.',
            '/account/profile/',
        ):
            with self.subTest(uri=uri):
                self.assertIsNotNone(pattern.fullmatch(uri))
        for uri in ('/accounting/', '/news/my-account/'):
            with self.subTest(uri=uri):
                self.assertIsNone(pattern.fullmatch(uri))

    def test_wp_rocket_buffer_rejects_every_alias_with_the_account_cache_key(self):
        loader_fixture = r'''
define('ABSPATH', '/fixture/');
function add_action(...$args) {}
function add_filter($name, $callback, ...$args) { $GLOBALS['filters'][$name] = $callback; }
require $argv[1];
$callback = $GLOBALS['filters']['rocket_cache_reject_uri'];
$patterns = $callback([]);
echo end($patterns);
'''
        pattern = subprocess.run(
            ['php', '-r', loader_fixture, str(LOADER)],
            check=True,
            capture_output=True,
            text=True,
        ).stdout
        plugin = ROOT / 'wordpress/plugins/wp-rocket/inc/classes'
        fixture = r'''
require $argv[1];
require $argv[2];
require $argv[3];
$config = new WP_Rocket\Buffer\Config([
    'config_dir_path' => $argv[4],
    'server' => ['HTTP_HOST' => 'hs-manacost.ru', 'REQUEST_URI' => $argv[5]],
]);
$tests = new WP_Rocket\Buffer\Tests($config, ['tests' => ['uri']]);
echo json_encode([
    'allowed' => $tests->can_process_uri(),
    'base' => $tests->get_request_uri_base(),
    'clean' => $tests->get_clean_request_uri(),
]);
'''
        aliases = (
            '/account/',
            '//account//',
            '/./account/',
            '/news/../account/',
            '/account/.',
            '/account/./',
            '/account/topic/..',
            '/news/../account/.',
        )
        with tempfile.TemporaryDirectory() as config_dir:
            encoded = base64.b64encode(pattern.encode()).decode()
            pathlib.Path(config_dir, 'hs-manacost.ru.php').write_text(
                "<?php\n$rocket_cache_reject_uri = base64_decode('" + encoded + "');\n"
            )
            for uri in aliases:
                with self.subTest(uri=uri):
                    result = subprocess.run(
                        [
                            'php',
                            '-r',
                            fixture,
                            str(plugin / 'traits/trait-memoize.php'),
                            str(plugin / 'Buffer/class-config.php'),
                            str(plugin / 'Buffer/class-tests.php'),
                            config_dir,
                            uri,
                        ],
                        check=True,
                        capture_output=True,
                        text=True,
                    )
                    payload = json.loads(result.stdout)
                    self.assertFalse(payload['allowed'])
                    self.assertEqual(payload['clean'].rstrip('/'), '/account')

    def test_account_bootstrap_disables_every_shared_cache_layer(self):
        fixture = r'''
define('ABSPATH', '/fixture/');
define('HS_MANACOST_READER_ENABLED', true);
$_SERVER['HTTP_HOST'] = 'hs-manacost.ru';
$_SERVER['REQUEST_URI'] = $argv[2];
function add_action(...$args) {}
function add_shortcode(...$args) {}
function add_filter(...$args) {}
function wp_unslash($value) { return stripslashes($value); }
require $argv[1];
hs_manacost_reader_bootstrap();
echo json_encode([
    'page' => defined('DONOTCACHEPAGE') && DONOTCACHEPAGE === true,
    'cdn' => defined('DONOTCDN') && DONOTCDN === true,
    'object' => defined('DONOTCACHEOBJECT') && DONOTCACHEOBJECT === true,
]);
'''
        account = subprocess.run(
            ['php', '-r', fixture, str(LOADER), '/account/'],
            check=True,
            capture_output=True,
            text=True,
        )
        article = subprocess.run(
            ['php', '-r', fixture, str(LOADER), '/some-article/'],
            check=True,
            capture_output=True,
            text=True,
        )
        self.assertEqual(json.loads(account.stdout), {'page': True, 'cdn': True, 'object': True})
        self.assertEqual(json.loads(article.stdout), {'page': False, 'cdn': False, 'object': False})

    def test_query_alias_and_mirror_account_fail_closed(self):
        fixture = r'''
define('ABSPATH', '/fixture/');
define('HS_MANACOST_READER_ENABLED', true);
$_SERVER['HTTP_HOST'] = $argv[2];
$_SERVER['REQUEST_URI'] = $argv[3];
parse_str((string) parse_url($argv[3], PHP_URL_QUERY), $_GET);
class WP_Post {
    public $ID = 42;
    public $post_status = 'publish';
    public $post_content = '[hs_manacost_reader_account]';
}
class QueryFixture {
    public $is404 = false;
    public function set_404() { $this->is404 = true; }
}
$GLOBALS['wp_query'] = new QueryFixture();
function add_action(...$args) {}
function add_filter($name, $callback, ...$args) { $GLOBALS['filters'][$name][] = $callback; }
function __return_false() { return false; }
function wp_unslash($value) { return stripslashes($value); }
function sanitize_text_field($value) { return trim(strip_tags($value)); }
function get_page_by_path($path) { return $GLOBALS['slug_lookup'] ? new WP_Post() : null; }
function get_post($id) { return (int) $id === 42 ? new WP_Post() : null; }
function has_shortcode($content, $name) { return $GLOBALS['shortcode_registered']; }
function is_page($id) { return $GLOBALS['resolved']; }
function nocache_headers() { $GLOBALS['nocache'] = true; }
function status_header($status) { $GLOBALS['status'] = $status; }
$GLOBALS['resolved'] = $argv[4] === 'resolved';
$GLOBALS['slug_lookup'] = $argv[4] !== 'missing';
$GLOBALS['shortcode_registered'] = $argv[4] !== 'missing';
require $argv[1];
hs_manacost_reader_account_route_policy();
$canonical = 'unfiltered';
if (!empty($GLOBALS['filters']['redirect_canonical'])) {
    $callback = end($GLOBALS['filters']['redirect_canonical']);
    $canonical = $callback('https://hs-manacost.ru/account/');
}
echo json_encode([
    'status' => $GLOBALS['status'] ?? 200,
    'is404' => $GLOBALS['wp_query']->is404,
    'nocache' => $GLOBALS['nocache'] ?? false,
    'page' => defined('DONOTCACHEPAGE') && DONOTCACHEPAGE === true,
    'canonical' => $canonical,
]);
'''
        cases = (
            ('hs-manacost.ru', '/account/', 'resolved', 200, False, True),
            ('hs-manacost.ru', '/Account/', 'resolved', 200, False, True),
            ('hs-manacost.ru', '/%61ccount/', 'resolved', 200, False, True),
            ('hs-manacost.ru', '//account//', 'resolved', 200, False, True),
            ('hs-manacost.ru', '/./account/', 'resolved', 200, False, True),
            ('hs-manacost.ru', '/news/../account/', 'resolved', 200, False, True),
            ('hs-manacost.ru', '/?pagename=account&lang=en', 'resolved', 404, True, True),
            ('hs-manacost.ru', '/?page_id=42', 'resolved', 404, True, True),
            ('hs-manacost.ru', '/?pagename=account&lang=en', 'other', 404, True, True),
            ('hs-manacost.ru', '/?page_id=42', 'other', 404, True, True),
            ('hs-manacost.com', '/account/', 'resolved', 404, True, True),
            ('hs-manacost.com', '/?pagename=account&lang=en', 'other', 404, True, True),
            ('hs-manacost.com', '/?page_id=42', 'other', 404, True, True),
            ('hs-manacost.com', '/?pagename=account&lang=en', 'missing', 404, True, True),
            ('hs-manacost.com', '/?page_id=42', 'missing', 404, True, True),
            ('hs-manacost.ru', '/?page_id=43', 'other', 200, False, False),
            ('hs-manacost.com', '/?pagename=accounting', 'other', 200, False, False),
            ('hs-manacost.com', '/news/', 'other', 200, False, False),
        )
        for host, uri, resolved, status, blocked, private in cases:
            with self.subTest(host=host, uri=uri):
                result = subprocess.run(
                    ['php', '-r', fixture, str(LOADER), host, uri, resolved],
                    check=True,
                    capture_output=True,
                    text=True,
                )
                payload = json.loads(result.stdout)
                self.assertEqual(payload['status'], status)
                self.assertEqual(payload['is404'], blocked)
                self.assertEqual(payload['nocache'], private)
                self.assertEqual(payload['page'], private)
                self.assertEqual(payload['canonical'], False if private else 'unfiltered')

    def test_account_cache_headers_are_private_and_not_indexable(self):
        fixture = r'''
define('ABSPATH', '/fixture/');
function add_action(...$args) {}
function add_filter(...$args) {}
require $argv[1];
echo json_encode(hs_manacost_reader_private_headers());
'''
        result = subprocess.run(
            ['php', '-r', fixture, str(LOADER)],
            check=True,
            capture_output=True,
            text=True,
        )
        self.assertEqual(
            json.loads(result.stdout),
            {
                'Cache-Control': 'private, no-store, no-cache, must-revalidate, max-age=0',
                'Pragma': 'no-cache',
                'Expires': 'Wed, 11 Jan 1984 05:00:00 GMT',
                'Surrogate-Control': 'no-store',
                'X-Robots-Tag': 'noindex, nofollow',
            },
        )

    def test_resolved_query_alias_gets_late_no_store_defense(self):
        fixture = r'''
define('ABSPATH', '/fixture/');
define('HS_MANACOST_READER_ENABLED', true);
$_SERVER['HTTP_HOST'] = 'hs-manacost.ru';
$_SERVER['REQUEST_URI'] = '/?pagename=account&preview=true&lang=en';
class WP_Post {
    public $ID = 42;
    public $post_status = 'publish';
    public $post_content = '[hs_manacost_reader_account]';
}
function add_action(...$args) {}
function add_filter(...$args) {}
function wp_unslash($value) { return stripslashes($value); }
function get_page_by_path($path) { return new WP_Post(); }
function has_shortcode($content, $name) { return true; }
function is_page($id) { return $id === 42; }
function nocache_headers() { $GLOBALS['nocache'] = true; }
require $argv[1];
hs_manacost_reader_cache_policy();
echo json_encode([
    'page' => defined('DONOTCACHEPAGE') && DONOTCACHEPAGE === true,
    'cdn' => defined('DONOTCDN') && DONOTCDN === true,
    'object' => defined('DONOTCACHEOBJECT') && DONOTCACHEOBJECT === true,
    'nocache' => $GLOBALS['nocache'] ?? false,
]);
'''
        result = subprocess.run(
            ['php', '-r', fixture, str(LOADER)],
            check=True,
            capture_output=True,
            text=True,
        )
        self.assertEqual(
            json.loads(result.stdout),
            {'page': True, 'cdn': True, 'object': True, 'nocache': True},
        )

    def test_account_shell_is_confined_to_the_account_path(self):
        account = LOADER.parent / 'hs-manacost-reader/account.php'
        fixture = r'''
define('ABSPATH', '/fixture/');
function hs_manacost_reader_is_account_request() { return false; }
require $argv[1];
echo hs_manacost_reader_account_shell() === '' ? 'empty' : 'rendered';
'''
        result = subprocess.run(
            ['php', '-r', fixture, str(account)],
            check=True,
            capture_output=True,
            text=True,
        )
        self.assertEqual(result.stdout, 'empty')

    def test_template_survives_active_composer_remapping(self):
        composer = ROOT / 'wordpress/plugins/td-composer'
        source = (composer / 'td-composer.php').read_text()
        # Execute the installed vendor callback, without booting the whole plugin.
        callback = re.search(r'function tdc_template_include\(\$template\) \{.*?^}', source, re.M | re.S)
        self.assertIsNotNone(callback)
        self.assertRegex(source, r"add_filter\(\s*'template_include',\s*'tdc_template_include',\s*99\s*\)")
        fixture = r'''
define('ABSPATH', '/fixture/');
define('HS_MANACOST_READER_ENABLED', true);
$_SERVER['HTTP_HOST'] = 'hs-manacost.ru';
$_SERVER['REQUEST_URI'] = '/account/';
define('TDC_PATH_LEGACY', $argv[2] . '/legacy/Newspaper');
define('STYLESHEETPATH', '/fixture/theme');
class WP_Post {
    public $ID = 42;
    public $post_status = 'publish';
    public $post_content = '[hs_manacost_reader_account]';
}
function add_action(...$args) {}
function add_shortcode(...$args) {}
function add_filter($name, $callback, $priority = 10, ...$args) { $GLOBALS['filters'][$name][$priority][] = $callback; }
function get_page_by_path($path) { return new WP_Post(); }
function has_shortcode($content, $name) { return true; }
function is_page($id) { return $id === 42; }
function wp_basename($path) { return basename($path); }
function is_child_theme() { return false; }
function wp_unslash($value) { return stripslashes($value); }
require $argv[1];
hs_manacost_reader_bootstrap();
add_filter('template_include', 'tdc_template_include', 99);
$filters = $GLOBALS['filters']['template_include'];
ksort($filters);
$template = '/theme/page.php';
foreach ($filters as $callbacks) {
    foreach ($callbacks as $callback) { $template = $callback($template); }
}
echo $template;
'''
        result = subprocess.run(['php', '-r', callback.group(0) + fixture, str(LOADER), str(composer)],
                                check=True, capture_output=True, text=True)
        self.assertEqual(pathlib.Path(result.stdout).parent, LOADER.parent / 'hs-manacost-reader')

    def test_template_only_for_explicit_enabled_account_page(self):
        fixture = r'''
define('ABSPATH', '/fixture/');
define('HS_MANACOST_READER_ENABLED', $argv[2] === 'enabled');
$_SERVER['HTTP_HOST'] = 'hs-manacost.ru';
$_SERVER['REQUEST_URI'] = '/account/';
class WP_Post {
    public $ID = 42;
    public $post_status = 'publish';
    public $post_content = '[hs_manacost_reader_account]';
}
function add_action(...$args) {}
function add_shortcode(...$args) {}
function add_filter($name, $callback, ...$args) { $GLOBALS['filters'][$name] = $callback; }
function get_page_by_path($path) { return $GLOBALS['page']; }
function has_shortcode($content, $name) { return strpos($content, '[' . $name . ']') !== false; }
function is_page($id) { return $GLOBALS['current_id'] === $id; }
function wp_unslash($value) { return stripslashes($value); }
require $argv[1];
hs_manacost_reader_bootstrap();
$GLOBALS['page'] = new WP_Post();
$GLOBALS['current_id'] = 42;
$callback = $GLOBALS['filters']['template_include'] ?? fn($template) => $template;
$results = [$callback('/theme/page.php')];
$GLOBALS['current_id'] = 99;
$results[] = $callback('/theme/article.php');
$GLOBALS['current_id'] = 42;
$GLOBALS['page']->post_status = 'draft';
$results[] = $callback('/theme/page.php');
$GLOBALS['page']->post_status = 'publish';
$GLOBALS['page']->post_content = 'Existing unrelated account page';
$results[] = $callback('/theme/page.php');
$GLOBALS['page'] = null;
$results[] = $callback('/theme/page.php');
$_SERVER['REQUEST_URI'] = '/?pagename=account&preview=true&lang=en';
$GLOBALS['page'] = new WP_Post();
$results[] = $callback('/theme/page.php');
echo json_encode($results);
'''
        for mode in ('enabled', 'disabled'):
            with self.subTest(mode=mode):
                result = subprocess.run(['php', '-r', fixture, str(LOADER), mode],
                                        check=True, capture_output=True, text=True)
                templates = json.loads(result.stdout)
                expected = str(LOADER.parent / 'hs-manacost-reader/reader-account-page.php') if mode == 'enabled' else '/theme/page.php'
                self.assertEqual(templates, [expected, '/theme/article.php', '/theme/page.php', '/theme/page.php', '/theme/page.php', '/theme/page.php'])

    def test_page_keeps_theme_shell_and_normal_content_pipeline(self):
        fixture = r'''
define('ABSPATH', '/fixture/');
function get_header() { echo '<header>Site navigation</header>'; }
function get_footer() { echo '<footer>Site links</footer>'; }
function have_posts() { static $calls = 0; return $calls++ === 0; }
function the_post() {}
function the_content() { echo '<section>Filtered page content</section>'; }
function get_sidebar() { throw new Exception('Editorial sidebar must not render in the account template'); }
require $argv[1];
'''
        template = LOADER.parent / 'hs-manacost-reader/reader-account-page.php'
        result = subprocess.run(['php', '-r', fixture, str(template)], check=True, capture_output=True, text=True)
        self.assertIn('<header>Site navigation</header>', result.stdout)
        self.assertIn('<footer>Site links</footer>', result.stdout)
        self.assertEqual(result.stdout.count('Filtered page content'), 1)
        self.assertEqual(result.stdout.count('<main'), 1)
        self.assertIn('class="td-main-content-wrap td-container-wrap mc-reader-page"', result.stdout)
