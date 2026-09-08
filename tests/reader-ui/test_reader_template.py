import json
import pathlib
import subprocess
import unittest

ROOT = pathlib.Path(__file__).parents[2]
LOADER = ROOT / 'wordpress/mu-plugins/hs-manacost-reader.php'


class ReaderTemplateTests(unittest.TestCase):
    def test_template_only_for_explicit_enabled_account_page(self):
        fixture = r'''
define('ABSPATH', '/fixture/');
define('HS_MANACOST_READER_ENABLED', $argv[2] === 'enabled');
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
echo json_encode($results);
'''
        for mode in ('enabled', 'disabled'):
            with self.subTest(mode=mode):
                result = subprocess.run(['php', '-r', fixture, str(LOADER), mode],
                                        check=True, capture_output=True, text=True)
                templates = json.loads(result.stdout)
                expected = str(LOADER.parent / 'hs-manacost-reader/page.php') if mode == 'enabled' else '/theme/page.php'
                self.assertEqual(templates, [expected, '/theme/article.php', '/theme/page.php', '/theme/page.php', '/theme/page.php'])

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
        template = LOADER.parent / 'hs-manacost-reader/page.php'
        result = subprocess.run(['php', '-r', fixture, str(template)], check=True, capture_output=True, text=True)
        self.assertIn('<header>Site navigation</header>', result.stdout)
        self.assertIn('<footer>Site links</footer>', result.stdout)
        self.assertEqual(result.stdout.count('Filtered page content'), 1)
        self.assertEqual(result.stdout.count('<main'), 1)
