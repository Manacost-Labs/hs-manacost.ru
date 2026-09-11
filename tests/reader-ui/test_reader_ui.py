import pathlib
import hashlib
import json
import subprocess
import unittest
from urllib.parse import urlparse

ROOT = pathlib.Path(__file__).parents[2]
PHP = ROOT / 'wordpress/mu-plugins/hs-manacost-reader/account.php'
JS = ROOT / 'wordpress/mu-plugins/hs-manacost-reader/reader.js'
PROFILE_JS = ROOT / 'wordpress/mu-plugins/hs-manacost-reader/profile-editor.js'
CSS = ROOT / 'wordpress/mu-plugins/hs-manacost-reader/reader.css'

class ReaderUiContractTests(unittest.TestCase):
    def test_invalid_public_profile_keeps_shared_styles_without_private_editor(self):
        fixture = "define('ABSPATH','/fixture/'); function hs_reader_public_profile_request(){return true;} function hs_reader_public_profile_id(){return '';} require $argv[1]; echo hs_manacost_reader_account_shell();"
        html = subprocess.run(['php', '-r', fixture, str(PHP)], capture_output=True, text=True, check=True).stdout
        self.assertIn('class="mc-reader-ui mc-public-profile"', html)
        self.assertIn('class="mc-public-profile__back"', html)
        self.assertIn('Профиль недоступен', html)
        self.assertNotIn('data-reader-profile-editor', html)
        self.assertNotIn('data-mc-reader-root', html)

    def test_shared_ui_is_an_explicit_dependency_and_single_token_owner(self):
        for path in (ROOT / 'wordpress/mu-plugins/hs-manacost-reader.php', PHP.parent / 'comments-loader.php'):
            source = path.read_text()
            self.assertIn("$base . 'ui.css'", source)
            self.assertIn("array( 'hs-manacost-reader-ui' )", source)
        shared = (PHP.parent / 'ui.css').read_text()
        self.assertNotIn('--mc-ui-surface:', CSS.read_text())
        comments = (PHP.parent / 'comments.css').read_text()
        self.assertIn('.mc-reader-ui.mc-comments {', comments)
        self.assertIn('--mc-ui-surface: #ffffff;', comments)
        self.assertIn('--mc-ui-text: #152d3a;', comments)
        self.assertIn('--mc-ui-accent: #78530e;', comments)
        self.assertIn('--mc-ui-on-accent: #ffffff;', comments)
        self.assertIn('--mc-ui-border: #6f8791;', comments)
        self.assertIn('background: transparent;', comments)
        self.assertIn('color-scheme: light;', comments)
        self.assertIn('--mc-ui-surface:', shared)
        self.assertIn('--mc-ui-on-accent:', shared)
        self.assertIn('.mc-ui-control', shared)
        self.assertIn('.mc-ui-button:disabled', shared)

    def test_reader_assets_use_their_content_version_to_invalidate_stale_browser_bundles(self):
        loader = ROOT / 'wordpress/mu-plugins/hs-manacost-reader.php'
        comments_loader = PHP.parent / 'comments-loader.php'
        fixture = r'''define('ABSPATH', '/fixture/');
define('HS_MANACOST_READER_COMMENTS_ENABLED', true);
define('HS_MANACOST_READER_COMMENT_POSTS', array(17));
define('HS_MANACOST_READER_EDITORIAL_KEY', str_repeat('x', 43));
function add_action(...$args) {} function add_filter(...$args) {}
function wp_get_environment_type() { return 'staging'; }
function home_url($path = '') { return 'https://test.hs-manacost.ru' . $path; }
function get_page_by_path($path) { $page = new WP_Post(); $page->ID = 22; $page->post_status = 'publish'; $page->post_content = '[hs_manacost_reader_account]'; return $page; }
function has_shortcode(...$args) { return true; }
function is_page($id) { return $GLOBALS['account']; }
function is_singular($type) { return ! $GLOBALS['account']; }
function get_the_ID() { return 17; }
function content_url($path) { return '/wp-content/' . $path; }
function wp_enqueue_style($handle, $source = '', $dependencies = array(), $version = false) { $GLOBALS['assets'][] = array($source, $version); }
function wp_enqueue_script($handle, $source = '', $dependencies = array(), $version = false) { $GLOBALS['assets'][] = array($source, $version); }
class WP_Post { public $ID; public $post_status; public $post_content; public $post_type = 'post'; public $post_password = ''; public $post_title = 'Тест'; }
function get_post($id) { $post = new WP_Post(); $post->ID = $id; $post->post_status = 'publish'; $post->post_content = 'Открытая статья'; return $post; }
function get_permalink($post) { return 'https://test.hs-manacost.ru/test-article/'; }
function wp_parse_url($url, $component = -1) { return parse_url($url, $component); }
function wp_strip_all_tags($text) { return strip_tags($text); }
function __($text, $domain = '') { return $text; }
function wp_unslash($value) { return $value; }
function sanitize_text_field($value) { return strip_tags($value); }
$GLOBALS['assets'] = array(); $GLOBALS['account'] = true;
require $argv[1]; require $argv[2];
hs_manacost_reader_assets();
$GLOBALS['account'] = false; hs_reader_comments_assets();
echo json_encode($GLOBALS['assets']);'''
        result = subprocess.run(
            ['php', '-r', fixture, str(loader), str(comments_loader)],
            capture_output=True,
            text=True,
            check=True,
        )
        assets = json.loads(result.stdout)
        expected = {
            file.name: hashlib.sha256(file.read_bytes()).hexdigest()[:12]
            for file in (PHP.parent / name for name in (
                'ui.css', 'reader.css', 'comments.css', 'profile-editor.js',
                'reader.js', 'community-ui.js', 'comments.js',
                'article-favorite.css', 'article-favorite.js',
            ))
        }
        self.assertEqual(len(assets), 10)
        for source, version in assets:
            filename = pathlib.Path(urlparse(source).path).name
            self.assertEqual(version, expected[filename], filename)

    def test_community_bundle_is_ordered_and_has_no_client_role_claim(self):
        loader = (PHP.parent / 'comments-loader.php').read_text()
        community = (PHP.parent / 'community-ui.js').read_text()
        self.assertIn("'hs-manacost-reader-community-ui'", loader)
        self.assertIn("$public ? array() : array( 'hs-manacost-reader-community-ui' )", loader)
        self.assertLess(loader.index("$base . 'community-ui.js'"), loader.index("$base . ( $public ? 'public-profile.js' : 'comments.js' )"))
        self.assertIn("'/reader-api/v1/community/me'", community)
        self.assertIn('/reaction', community)
        self.assertIn("'X-Reader-CSRF'", (PHP.parent / 'comments.js').read_text())
        self.assertNotIn('actor:', community)
        self.assertNotIn('role:', community)
        for label in ('Нравится', 'Спасибо', 'Огонь', 'Удалить комментарий', 'Запретить комментировать', 'Разрешить комментировать'):
            self.assertIn(label, community)
        self.assertIn('Администратор', (PHP.parent / 'comments.js').read_text())

    @classmethod
    def setUpClass(cls):
        cls.php, cls.js, cls.css = PHP.read_text(), JS.read_text(), CSS.read_text() + (PHP.parent / 'ui.css').read_text()
        cls.profile_js = PROFILE_JS.read_text() if PROFILE_JS.exists() else ''
    def test_shell_is_cache_safe_and_escaped(self):
        self.assertIn('hs_manacost_reader_account_shell', self.php); self.assertIn('esc_attr( $public[', self.php)
        for native_api in ('wp_get_current_user', 'get_current_user_id', 'wp_insert_comment', 'wp_list_comments', 'comment_form'):
            self.assertNotIn(native_api, self.php)

    def test_account_notice_matches_the_actual_community_flag(self):
        fixture = "define('ABSPATH','/fixture/'); function esc_attr($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');} function esc_html($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');} function esc_html__($s,$domain=''){return esc_html($s);} "
        for enabled in (False, True):
            setup = fixture + 'function hs_reader_comments_enabled(){return ' + ('true' if enabled else 'false') + ';} require $argv[1]; echo hs_manacost_reader_account_shell();'
            html = subprocess.run(['php', '-r', setup, str(PHP)], capture_output=True, text=True, check=True).stdout
            self.assertEqual('Комментарии сейчас недоступны.' in html, not enabled)
            self.assertEqual('Комментарии публикуются сразу.' in html, enabled)
            self.assertEqual('aria-labelledby="mc-reader-publication-title" hidden' in html, not enabled)
    def test_account_headings_and_live_status_are_semantic(self):
        self.assertIn('<p class="mc-reader__masthead-kicker">Профиль Манакоста</p>', self.php)
        self.assertIn('<h1 class="mc-reader__eyebrow">Кабинет</h1>', self.php)
        self.assertIn('<p class="mc-reader__profile-kicker">Ваш профиль</p>', self.php)
        self.assertIn('id="mc-reader-profile-title"', self.php)
        self.assertIn('data-reader-identity', self.php)
        self.assertIn('hs_manacost_reader_author_mark', self.php)
        self.assertIn("'twitch'  => 'Открыть Twitch-канал'", self.php)
        self.assertIn("'youtube' => 'Открыть YouTube-канал'", self.php)
        self.assertIn('mc-reader__social-label', self.php)
        self.assertIn('<details class="mc-reader__account-menu"', self.php)
        self.assertIn("hs_manacost_reader_account_icon( 'account' )", self.php)
        self.assertIn("hs_manacost_reader_account_icon( 'chevron' )", self.php)
        self.assertNotIn('mc-reader-saved-title', self.php)
        self.assertIn('aria-labelledby="mc-reader-profile-title"', self.php)
        self.assertIn('data-reader-status role="status" aria-live="polite"', self.php)

    def test_saved_articles_follow_the_profile_without_a_tab_switcher(self):
        self.assertIn('data-reader-profile-overview', self.php)
        self.assertIn('data-reader-favorites', self.php)
        self.assertIn('data-reader-favorites-sentinel', self.php)
        self.assertLess(self.php.index('data-reader-profile-overview'), self.php.index('data-reader-favorites'))
        for obsolete_tab_markup in ('data-reader-tabs', 'data-reader-tab-profile', 'data-reader-tab-favorites', 'role="tablist"', 'role="tabpanel"'):
            self.assertNotIn(obsolete_tab_markup, self.php)
    def test_auth_contract_and_no_private_html_injection(self):
        for value in ("credentials: 'same-origin'", "cache: 'no-store'", 'response.status === 200', 'response.status === 401', 'response.status === 503', 'response.status !== 204', 'X-Reader-CSRF', 'textContent'):
            self.assertIn(value, self.js)
        scripts = self.js + self.profile_js
        self.assertNotIn('innerHTML', scripts); self.assertNotIn('localStorage', scripts)
    def test_profile_editor_contract_is_explicit_and_same_origin(self):
        for value in ('data-profile-endpoint', 'data-avatar-endpoint', 'data-reader-profile-editor',
                      'data-reader-display-name', 'data-reader-bio', 'data-reader-favorite-class',
                      'data-reader-avatar-input', 'data-reader-remove-avatar', 'data-reader-twitch',
                      'data-reader-youtube', 'hs_manacost_reader_author_mark'):
            self.assertIn(value, self.php)
        self.assertNotIn('data-reader-socials', self.php)
        for value in ("method: 'PATCH'", "method: 'PUT'", "method: 'DELETE'",
                      "'X-Reader-CSRF'", "'X-Reader-Profile-Version'", "credentials: 'same-origin'",
                      "cache: 'no-store'", "'/reader-api/v1/profile'", "'/reader-api/v1/profile/avatar'"):
            self.assertIn(value, self.js + self.profile_js)
    def test_profile_editor_has_client_limits_and_safe_avatar_lifecycle(self):
        self.assertNotIn('maxlength=', self.php)
        self.assertIn('codepointLength( value.displayName ) > 40', self.profile_js)
        self.assertIn('codepointLength( value.bio ) > 280', self.profile_js)
        self.assertIn('aria-describedby="mc-reader-avatar-help"', self.php)
        for value in ('image/jpeg', 'image/png', 'image/webp', '4 * 1024 * 1024',
                      'URL.createObjectURL', 'URL.revokeObjectURL', 'overflow-wrap'):
            self.assertIn(value, self.profile_js + self.css)
        self.assertNotIn('data:', self.profile_js)
        self.assertNotIn('http://', self.profile_js)
    def test_session_refresh_and_mutation_races_preserve_safe_state(self):
        for value in ('currentCsrfToken', 'profile.version < knownVersion', 'options.onMutationStart()',
                      "view.image.getAttribute( 'src' )", 'profileEditor.isBusy()', 'setBusy( false )',
                      'logoutGeneration', 'actions.replaceChildren()'):
            self.assertIn(value, self.js + self.profile_js)
        self.assertIn("error.message === 'invalid_profile'", self.js)
        self.assertIn("options.onRefresh( { preserveDraft: true, acceptVersion: true } )", self.profile_js)
        self.assertIn('editorStatus.textContent === message', self.profile_js)
    def test_profile_editor_explains_scope_and_unavailable_comments(self):
        self.assertIn('Профиль Манакоста не изменяет профиль HearthPulse.', self.php)
        self.assertIn('Комментарии сейчас недоступны.', self.php)
        self.assertIn('Изменить профиль', self.php)
        self.assertIn('Где меня найти', self.php)
        self.assertIn('Обновить в комментариях', self.php)
        self.assertNotIn('data-reader-public-consent', self.php)
        self.assertIn('Twitch / YouTube', self.php)
        self.assertIn('data-reader-favorites', self.php)
        self.assertNotIn('data-reader-tab-favorites', self.php)
        self.assertIn('/reader-api/v1/favorites', self.js)
        self.assertIn('Личная подборка', self.php)
        self.assertIn('IntersectionObserver', self.js)
    def test_profile_link_expiry_and_private_state(self):
        for value in ("url.origin === 'https://hearthpulse.net'", '! url.username', '! url.password', 'data.profileUrl', 'identity.replaceChildren()', 'actions.replaceChildren()', "window.addEventListener( 'pageshow'", "window.addEventListener( 'focus'"):
            self.assertIn(value, self.js)
    def test_native_actions_and_stale_request_guards(self):
        self.assertIn("node.type = 'button'", self.js)
        self.assertNotIn("role = 'button'", self.js)
        for value in ('const requestController = new AbortController()', 'const requestGeneration = ++generation', 'current( requestController, requestGeneration )', 'logoutInFlight = true'):
            self.assertIn(value, self.js)
    def test_copy_is_public_and_honest(self):
        self.assertIn('Профиль Манакоста', self.php)
        self.assertIn('Кабинет', self.php)
        self.assertNotIn('Закладки пока недоступны.', self.php)
        self.assertNotIn('reader API', self.php)
    def test_responsive_accessible_geometry(self):
        self.assertRegex(self.css, r'--mc-reader-(?:navy|slate|ice|muted|gold|blue|panel|line|panel-quiet)\s*:')
        for selector in ('.mc-reader__title-group', '.mc-reader__profile-kicker', '.mc-reader__profile'):
            self.assertIn(selector, self.css)
        self.assertRegex(self.css, r'min-(?:height|block-size)\s*:\s*44px')
        self.assertRegex(self.css, r'overflow-wrap\s*:\s*anywhere')
        self.assertIn('focus-visible', self.css); self.assertIn('prefers-reduced-motion', self.css)
        self.assertNotIn('@import', self.css); self.assertNotIn('url(http', self.css)
        self.assertIn('.mc-reader__social-fields', self.css)
        self.assertIn('.mc-reader__author-mark', self.css)
        self.assertNotIn('.mc-reader__social-link', self.css)

    def test_account_controls_use_self_contained_icons_without_new_dependencies(self):
        self.assertIn('function hs_manacost_reader_account_icon', self.php)
        self.assertIn('aria-hidden="true"', self.php)
        for icon in ('account', 'edit', 'camera', 'trash', 'check', 'retry', 'youtube', 'twitch', 'back'):
            self.assertIn("'" + icon + "'", self.php)
        self.assertIn('.mc-reader__icon', self.css)
        self.assertNotIn('url(http', self.php)

    def test_public_article_eligibility_is_memoized_only_within_the_request(self):
        editorial = PHP.parent / 'comments-editorial.php'
        fixture = r'''define('ABSPATH', '/fixture/');
define('HS_MANACOST_READER_COMMENTS_ENABLED', true);
define('HS_MANACOST_READER_COMMENT_POSTS', array(17));
function wp_get_environment_type() { return 'staging'; }
function home_url($path = '') { return 'https://test.hs-manacost.ru' . $path; }
class WP_Post { public $ID; public $post_type = 'post'; public $post_status = 'publish'; public $post_password = ''; public $post_content = 'Открытая статья'; public $post_title = 'Тест'; }
$GLOBALS['get_post_calls'] = 0;
function get_post($id) { $GLOBALS['get_post_calls']++; $post = new WP_Post(); $post->ID = $id; return $post; }
function get_permalink($post) { return 'https://test.hs-manacost.ru/test-article/'; }
function wp_parse_url($url, $component = -1) { return parse_url($url, $component); }
function wp_strip_all_tags($value) { return strip_tags($value); }
require $argv[1];
$thread = hs_reader_comment_article(17);
$favorite = hs_reader_favorite_article(17);
echo json_encode(array('calls' => $GLOBALS['get_post_calls'], 'thread' => $thread['allowed'], 'favorite' => $favorite['allowed']));'''
        result = subprocess.run(['php', '-r', fixture, str(editorial)], capture_output=True, text=True, check=True)
        response = json.loads(result.stdout)
        self.assertEqual(response, {'calls': 1, 'thread': True, 'favorite': True})
