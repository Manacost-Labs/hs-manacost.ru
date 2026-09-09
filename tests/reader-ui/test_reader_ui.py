import pathlib
import subprocess
import unittest

ROOT = pathlib.Path(__file__).parents[2]
PHP = ROOT / 'wordpress/mu-plugins/hs-manacost-reader/account.php'
JS = ROOT / 'wordpress/mu-plugins/hs-manacost-reader/reader.js'
PROFILE_JS = ROOT / 'wordpress/mu-plugins/hs-manacost-reader/profile-editor.js'
CSS = ROOT / 'wordpress/mu-plugins/hs-manacost-reader/reader.css'

class ReaderUiContractTests(unittest.TestCase):
    def test_v3_account_and_comments_invalidate_old_browser_bundles(self):
        loader = (ROOT / 'wordpress/mu-plugins/hs-manacost-reader.php').read_text()
        comments_loader = (PHP.parent / 'comments-loader.php').read_text()
        self.assertIn('Version: 0.5.0', loader)
        for source in (loader, comments_loader):
            self.assertNotIn("'0.3.0'", source)
            self.assertNotIn("'0.4.0'", source)
            self.assertIn("'0.5.0'", source)

    @classmethod
    def setUpClass(cls):
        cls.php, cls.js, cls.css = PHP.read_text(), JS.read_text(), CSS.read_text()
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
            self.assertEqual('Комментарии публикуются сразу после вашего согласия.' in html, enabled)
    def test_account_headings_and_live_status_are_semantic(self):
        self.assertIn('id="mc-reader-profile-title"', self.php)
        self.assertIn('data-reader-identity', self.php)
        self.assertRegex(self.php, r'<h2[^>]*id="mc-reader-saved-title"[^>]*>Сохранённые статьи</h2>')
        self.assertIn('aria-labelledby="mc-reader-profile-title"', self.php)
        self.assertIn('aria-labelledby="mc-reader-saved-title"', self.php)
        self.assertIn('data-reader-status role="status" aria-live="polite"', self.php)
    def test_auth_contract_and_no_private_html_injection(self):
        for value in ("credentials: 'same-origin'", "cache: 'no-store'", 'response.status === 200', 'response.status === 401', 'response.status === 503', 'response.status !== 204', 'X-Reader-CSRF', 'textContent'):
            self.assertIn(value, self.js)
        scripts = self.js + self.profile_js
        self.assertNotIn('innerHTML', scripts); self.assertNotIn('localStorage', scripts)
    def test_profile_editor_contract_is_explicit_and_same_origin(self):
        for value in ('data-profile-endpoint', 'data-avatar-endpoint', 'data-reader-profile-editor',
                      'data-reader-display-name', 'data-reader-bio', 'data-reader-favorite-class',
                      'data-reader-avatar-input', 'data-reader-remove-avatar'):
            self.assertIn(value, self.php)
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
                      "avatarImage.getAttribute( 'src' )", 'profileEditor.isBusy()', 'setBusy( false )',
                      'logoutGeneration', 'actions.replaceChildren()'):
            self.assertIn(value, self.js + self.profile_js)
        self.assertIn("error.message === 'invalid_profile'", self.js)
        self.assertIn("options.onRefresh( { preserveDraft: true, acceptVersion: true } )", self.profile_js)
        self.assertIn('editorStatus.textContent === message', self.profile_js)
    def test_profile_editor_explains_scope_and_unavailable_comments(self):
        self.assertIn('Профиль Манакоста не изменяет профиль HearthPulse.', self.php)
        self.assertIn('Комментарии сейчас недоступны.', self.php)
        self.assertIn('Изменить профиль', self.php)
    def test_profile_link_expiry_and_private_state(self):
        for value in ("url.origin === 'https://hearthpulse.net'", '! url.username', '! url.password', 'data.profileUrl', 'identity.replaceChildren()', 'actions.replaceChildren()', "window.addEventListener( 'pageshow'", "window.addEventListener( 'focus'"):
            self.assertIn(value, self.js)
    def test_native_actions_and_stale_request_guards(self):
        self.assertIn("node.type = 'button'", self.js)
        self.assertNotIn("role = 'button'", self.js)
        for value in ('const requestController = new AbortController()', 'const requestGeneration = ++generation', 'current( requestController, requestGeneration )', 'logoutInFlight = true'):
            self.assertIn(value, self.js)
    def test_copy_is_public_and_honest(self):
        self.assertIn('Личный кабинет', self.php)
        self.assertIn('Закладки пока недоступны.', self.php)
        self.assertNotIn('reader API', self.php)
    def test_responsive_accessible_geometry(self):
        self.assertRegex(self.css, r'--mc-reader-(?:navy|slate|ice|muted|gold|blue)\s*:')
        self.assertRegex(self.css, r'min-(?:height|block-size)\s*:\s*44px')
        self.assertRegex(self.css, r'overflow-wrap\s*:\s*anywhere')
        self.assertIn('focus-visible', self.css); self.assertIn('prefers-reduced-motion', self.css)
        self.assertNotIn('@import', self.css); self.assertNotIn('url(http', self.css)
