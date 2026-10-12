from __future__ import annotations

import json
import subprocess
import unittest
from html.parser import HTMLParser
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "wordpress/mu-plugins/manacost-performance-optimizer.php"


def thumbnail(index: int, *, style: str | None = None, extra: str = "", content: str = "") -> str:
    style = style if style is not None else f"background-image: url('https://hs-manacost.ru/uploads/{index}.png')"
    return f'<span class="entry-thumb td-thumb-css" style="{style}" {extra}>{content}</span>'


class Images(HTMLParser):
    def __init__(self, markup: str) -> None:
        super().__init__()
        self.images: list[dict[str, str | None]] = []
        self.feed(markup)

    def handle_starttag(self, tag: str, attrs: list[tuple[str, str | None]]) -> None:
        if tag == "img":
            self.images.append(dict(attrs))


class ThumbnailLoadingTest(unittest.TestCase):
    def render(self, markup: str, context: dict | None = None) -> str:
        # Real PHP transformation, with only WordPress's escaping boundary stubbed.
        setup = ""
        expression = "Manacost_Thumbnail_Loading::optimize_html($html)"
        if context is not None:
            for function in ['is_admin', 'is_user_logged_in', 'wp_doing_ajax', 'is_feed', 'is_preview', 'is_robots', 'is_trackback', 'is_front_page', 'is_home']:
                setup += f"function {function}() {{ return {json.dumps(context.get(function, False))}; }}"
            setup += "function home_url() { return " + json.dumps('https://' + context.get('host', 'hs-manacost.ru')) + "; }"
            setup += "function wp_parse_url($u,$c=-1){return parse_url($u,$c);}function wp_unslash($u){return stripslashes($u);}function sanitize_text_field($u){return trim(strip_tags($u));}"
            setup += "$_SERVER['REQUEST_URI']=" + json.dumps(context.get('path', '/page/2/')) + ";"
            for constant, value in context.get('constants', {}).items():
                setup += f"define('{constant}',{json.dumps(value)});"
            expression = "(function() use ($html) { ob_start(); $level=ob_get_level(); Manacost_Performance_Optimizer::start(); echo $html; if(ob_get_level()>$level){ob_end_flush();} return ob_get_clean(); })()"
        script = f"""
        define('ABSPATH', '/');
        function add_action($tag, $callback, $priority = 10, $accepted_args = 1) {{}}
        function esc_url($value) {{ return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }}
        function esc_attr($value) {{ return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }}
        {setup}
        $html = {json.dumps(markup, ensure_ascii=False)};
        require {json.dumps(str(PLUGIN))};
        echo json_encode(class_exists('Manacost_Thumbnail_Loading')
            ? {expression} : $html,
            JSON_UNESCAPED_UNICODE);
        """
        result = subprocess.run(["php", "-r", script], capture_output=True, text=True)
        self.assertEqual(result.returncode, 0, result.stderr)
        return json.loads(result.stdout)

    def page(self, body: str) -> str:
        return '<html><head><style id="fonts">h1{font-family:Ubuntu}</style></head><body>' + body + '</body></html>'

    def test_fourteen_first_thumbnails_are_unchanged_and_later_urls_are_exact(self) -> None:
        early = ''.join(thumbnail(i) for i in range(14))
        result = self.render(self.page(early + thumbnail(14) + thumbnail(15)))
        self.assertIn(early, result)
        images = Images(result).images
        self.assertEqual([image['src'] for image in images], ['https://hs-manacost.ru/uploads/14.png', 'https://hs-manacost.ru/uploads/15.png'])
        for image in images:
            self.assertEqual(image['loading'], 'lazy')
            self.assertEqual(image['alt'], '')
            self.assertEqual(image['aria-hidden'], 'true')
            self.assertIn('skip-lazy', image['class'])
            self.assertEqual(image['data-no-lazy'], '1')

    def test_promoted_images_count_toward_the_eager_budget(self) -> None:
        promoted = '<span class="entry-thumb td-thumb-css manacost-lcp-thumb"><picture><img src="hero.png" loading="eager"></picture></span>'
        early = promoted * 2 + ''.join(thumbnail(i) for i in range(12))
        result = self.render(self.page(early + thumbnail(14)))
        self.assertIn(early, result)
        self.assertEqual(len(Images(result).images), 3)

    def test_fonts_ads_scripts_and_other_styles_survive(self) -> None:
        untouched = '<link rel="stylesheet" href="fonts.css"><a class="ad"><img src="banner.png"></a><script>window.partnerAd=true;</script>'
        candidate = thumbnail(14, style="border-radius: 9px; background-image: url('https://hs-manacost.ru/uploads/14.png'); background-position: center top; opacity: .9")
        result = self.render(self.page(untouched + ''.join(thumbnail(i) for i in range(14)) + candidate))
        self.assertIn(untouched, result)
        self.assertIn('<style id="fonts">h1{font-family:Ubuntu}</style>', result)
        self.assertIn('border-radius: 9px', result)
        self.assertIn('opacity: .9', result)
        self.assertIn('--manacost-thumbnail-position:center top', result)
        self.assertIn('object-position:var(--manacost-thumbnail-position,center top)', result)

    def test_query_parameters_and_html_entities_preserve_url(self) -> None:
        candidate = thumbnail(14, style="background-image:url(&quot;https://hs-manacost.ru/uploads/14.png?a=1&amp;b=2&quot;)")
        result = self.render(self.page(''.join(thumbnail(i) for i in range(14)) + candidate))
        self.assertEqual(Images(result).images[0]['src'], 'https://hs-manacost.ru/uploads/14.png?a=1&b=2')

    def test_comments_scripts_textareas_templates_and_quoted_attributes_are_untouched(self) -> None:
        example = thumbnail(99)
        protected = '<!--' + example + '--><script>' + json.dumps(example) + '</script><style>/*' + example + '*/</style><textarea>' + example + '</textarea><noscript>' + example + '</noscript><div data-example="' + example.replace('"', '&quot;') + '"></div>'
        result = self.render(self.page(''.join(thumbnail(i) for i in range(14)) + protected + thumbnail(14)))
        self.assertIn(protected, result)
        self.assertEqual(len(Images(result).images), 1)

    def test_existing_loaders_retina_nested_and_unsafe_backgrounds_are_untouched(self) -> None:
        candidates = [
            thumbnail(14, extra='data-bg="existing.png"'),
            thumbnail(14, extra='data-img-url="existing.png"'),
            thumbnail(14, extra='data-type="css_image"'),
            thumbnail(14, content='<em>nested</em>'),
            thumbnail(14).replace('td-thumb-css', 'td-thumb-css td-retina-123'),
            thumbnail(14, style="background-image:url('javascript:alert(1)')"),
            thumbnail(14, style="background-image:url('https://hs-manacost.ru/a.png'),url('https://hs-manacost.ru/b.png')"),
            thumbnail(14, style="background-image:url('https://hs-manacost.ru/a.png');background-size:contain"),
        ]
        for candidate in candidates:
            with self.subTest(candidate=candidate):
                page = self.page(''.join(thumbnail(i) for i in range(14)) + candidate)
                self.assertEqual(self.render(page), page)

    def test_missing_head_and_template_documents_fail_open(self) -> None:
        body = ''.join(thumbnail(i) for i in range(16))
        self.assertEqual(self.render(body), body)
        page = self.page('<template><template></template>' + body + '</template>')
        self.assertEqual(self.render(page), page)

    def test_composer_positions_keep_the_same_css_cascade_and_media(self) -> None:
        css = '<style>.tdi_30 .entry-thumb{background-position:30% 40% !important;}.tdi_29 .entry-thumb{background-position: center 30%;}@media(max-width:767px){.tdi_29 .entry-thumb{background-position:left top;}}</style>'
        result = self.render(self.page(css + ''.join(thumbnail(i) for i in range(16))))
        self.assertIn('--manacost-thumbnail-position:30% 40% !important;', result)
        self.assertIn('--manacost-thumbnail-position:center 30%;background-position: center 30%;', result)
        self.assertIn('--manacost-thumbnail-position:left top;background-position:left top;', result)
        self.assertIn('object-position:var(--manacost-thumbnail-position,center center)', result)

    def test_css_mirroring_does_not_touch_style_strings_in_scripts_or_comments(self) -> None:
        literal = '<style>.entry-thumb{background-position:center 30%;}</style>'
        script = '<script>const example=' + json.dumps(literal) + ';</script>'
        comment = '<!--' + literal + '-->'
        result = self.render(self.page(script + comment + ''.join(thumbnail(i) for i in range(16))))
        self.assertIn(script, result)
        self.assertIn(comment, result)

    def test_public_hosts_private_contexts_and_rollback(self) -> None:
        page = self.page(''.join(thumbnail(i) for i in range(16)))
        for host in ['hs-manacost.ru', 'hs-manacost.com', 'test.hs-manacost.ru']:
            with self.subTest(host=host):
                self.assertEqual(len(Images(self.render(page, {'host': host})).images), 2)
        excluded = [{'host': 'other.example'}, {'is_admin': True}, {'is_user_logged_in': True}, {'wp_doing_ajax': True}, {'is_preview': True}, {'constants': {'REST_REQUEST': True}}, {'constants': {'MANACOST_PERF_ENABLED': False}}]
        excluded += [{'path': path} for path in ['/wp-admin/', '/wp-login.php', '/wp-json/test', '/reader-api/bootstrap', '/account/']]
        for context in excluded:
            with self.subTest(context=context):
                self.assertEqual(self.render(page, context), page)
        result = self.render(page, {'constants': {'MANACOST_NATIVE_THUMBNAILS_ENABLED': False}})
        self.assertIn(''.join(thumbnail(i) for i in range(16)), result)
        self.assertEqual(Images(result).images, [])

    def test_no_work_and_repeated_processing_are_idempotent(self) -> None:
        simple = self.page(''.join(thumbnail(i) for i in range(14)))
        self.assertEqual(self.render(simple), simple)
        changed = self.render(self.page(''.join(thumbnail(i) for i in range(16))))
        self.assertEqual(self.render(changed), changed)


if __name__ == '__main__':
    unittest.main()
