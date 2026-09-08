import pathlib
import unittest

ROOT = pathlib.Path(__file__).parents[2]
PHP = ROOT / 'wordpress/mu-plugins/hs-manacost-reader/account.php'
JS = ROOT / 'wordpress/mu-plugins/hs-manacost-reader/reader.js'
CSS = ROOT / 'wordpress/mu-plugins/hs-manacost-reader/reader.css'

class ReaderUiContractTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.php, cls.js, cls.css = PHP.read_text(), JS.read_text(), CSS.read_text()
    def test_shell_is_cache_safe_and_escaped(self):
        self.assertIn('hs_manacost_reader_account_shell', self.php); self.assertIn('esc_attr( $public[', self.php)
        self.assertNotIn('wp_get_current_user', self.php); self.assertNotIn('get_current_user_id', self.php); self.assertNotIn('comment', self.php.lower())
    def test_account_headings_and_live_status_are_semantic(self):
        self.assertRegex(self.php, r'<h1[^>]*>Кабинет читателя</h1>')
        self.assertRegex(self.php, r'<h2[^>]*id="mc-reader-profile-title"[^>]*>Профиль</h2>')
        self.assertRegex(self.php, r'<h2[^>]*id="mc-reader-saved-title"[^>]*>Сохранённые статьи</h2>')
        self.assertIn('aria-labelledby="mc-reader-profile-title"', self.php)
        self.assertIn('aria-labelledby="mc-reader-saved-title"', self.php)
        self.assertIn('data-reader-status role="status" aria-live="polite"', self.php)
    def test_auth_contract_and_no_private_html_injection(self):
        for value in ("credentials: 'same-origin'", "cache: 'no-store'", 'response.status === 200', 'response.status === 401', 'response.status === 503', 'response.status !== 204', 'X-Reader-CSRF', 'textContent'):
            self.assertIn(value, self.js)
        self.assertNotIn('innerHTML', self.js); self.assertNotIn('localStorage', self.js)
    def test_profile_link_expiry_and_private_state(self):
        for value in ("url.origin === 'https://hearthpulse.net'", '! url.username', '! url.password', 'data.profileUrl', 'identity.replaceChildren()', 'actions.replaceChildren()', "window.addEventListener( 'pageshow'", "window.addEventListener( 'focus'"):
            self.assertIn(value, self.js)
    def test_native_actions_and_stale_request_guards(self):
        self.assertIn("node.type = 'button'", self.js)
        self.assertNotIn("role = 'button'", self.js)
        for value in ('const requestController = new AbortController()', 'const requestGeneration = ++generation', 'current( requestController, requestGeneration )', 'logoutInFlight = true'):
            self.assertIn(value, self.js)
    def test_copy_is_public_and_honest(self):
        self.assertIn('Ваш профиль Манакоста со входом через HearthPulse.', self.php)
        self.assertIn('Сохранение статей появится здесь в следующем обновлении.', self.php)
        self.assertNotIn('reader API', self.php)
    def test_responsive_accessible_geometry(self):
        self.assertRegex(self.css, r'--mc-reader-(?:navy|panel|blue|text|muted)\s*:')
        self.assertRegex(self.css, r'min-(?:height|block-size)\s*:\s*44px')
        self.assertRegex(self.css, r'overflow-wrap\s*:\s*anywhere')
        self.assertIn('focus-visible', self.css); self.assertIn('prefers-reduced-motion', self.css)
        self.assertNotIn('@import', self.css); self.assertNotIn('url(http', self.css)
