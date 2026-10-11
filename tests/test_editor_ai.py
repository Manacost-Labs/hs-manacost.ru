"""Editor suggestions preserve markup, facts and catalog-confirmed identities."""
import json
from pathlib import Path
import subprocess
import unittest

ROOT = Path(__file__).resolve().parents[1]


class EditorAiPolicy(unittest.TestCase):
    def run_content(self, source, edits, mode):
        script = """<?php
define('ABSPATH', '/unused/');
class WP_Error { public $code; public function __construct($c,$m=''){$this->code=$c;} }
function is_wp_error($v){return $v instanceof WP_Error;}
function __($s,$d=''){return $s;}
require 'wordpress/mu-plugins/hs-editor-ai/class-content.php';
$input=json_decode(stream_get_contents(STDIN),true);
$cards=[['tag'=>'hs_card','id'=>'EX1_116','name'=>'Лирой Дженкинс'],
['tag'=>'hs_bg','id'=>'BG_001','name'=>'Лирой Дженкинс']];
$value=Manacost\\EditorAi\\Content::apply($input['source'],$input['edits'],$input['mode'],$cards);
echo json_encode(is_wp_error($value)?['error'=>$value->code]:['text'=>$value]);
"""
        fixture = ROOT / '.artifacts' / 'editor-ai-policy.php'
        fixture.parent.mkdir(exist_ok=True)
        fixture.write_text(script)
        result = subprocess.run(['php', str(fixture)], cwd=ROOT,
                                input=json.dumps({'source': source, 'edits': edits, 'mode': mode}),
                                text=True, capture_output=True)
        self.assertEqual(result.returncode, 0, result.stderr)
        return json.loads(result.stdout)

    def provider_request(self, stop='end_turn', text='{"edits":[]}', review=False):
        script = r"""
class WP_Error { public function __construct(public $code,public $message=''){} }
function __($s,$d=''){return $s;}
function is_wp_error($v){return $v instanceof WP_Error;}
function get_transient($s){return false;}
function wp_json_encode($s,$flags=0){return json_encode($s,$flags);}
function wp_safe_remote_post($url,$args){global $request,$response; $request=json_decode($args['body'],true);return $response;}
function wp_remote_retrieve_response_code($r){return 200;}
function wp_remote_retrieve_body($r){return json_encode($r);}
define('ABSPATH','/unused/');putenv('HS_EDITOR_AI_API_KEY=fixture-only');
require 'wordpress/mu-plugins/hs-editor-ai/class-provider.php';
$input=json_decode(stream_get_contents(STDIN),true);
$response=['stop_reason'=>$input['stop'],'content'=>[['type'=>'thinking','thinking'=>'private internal data'],['type'=>'text','text'=>$input['text']]]];
$value=Manacost\EditorAi\Provider::complete('fixture',[], $input['review']);
echo json_encode(['request'=>$request,'result'=>is_wp_error($value)?['error'=>$value->code]:$value]);
"""
        result = subprocess.run(['php','-r',script],cwd=ROOT,input=json.dumps({'stop':stop,'text':text,'review':review}),text=True,capture_output=True)
        self.assertEqual(result.returncode,0,result.stderr)
        return json.loads(result.stdout)

    def test_provider_enforces_edit_schema_and_separate_review_schema(self):
        for review in (False, True):
            request = self.provider_request(review=review)['request']
            self.assertEqual(request['model'], 'claude-opus-5-5')
            self.assertEqual(request['output_config']['format']['type'], 'json_schema')
            schema = request['output_config']['format']['schema']
            self.assertFalse(schema['additionalProperties'])
            self.assertEqual(schema['required'], ['approved'] if review else ['edits'])
            if not review:
                edit = schema['properties']['edits']['items']
                self.assertEqual(edit['required'], ['segment','replacement'])
                self.assertFalse(edit['additionalProperties'])
                self.assertEqual(edit['properties']['segment']['type'], 'integer')

    def test_provider_rejects_truncated_and_malformed_output(self):
        for stop,text in [('max_tokens','{"edits":[]}'),('refusal','{"edits":[]}'),('end_turn','not JSON')]:
            self.assertEqual(self.provider_request(stop,text)['result'], {'error':'editor_ai_response'})
        self.assertEqual(self.provider_request(text='```json\n{"edits":[]}\n```')['result'], {'edits':[]})

    def test_card_mode_only_wraps_catalog_confirmed_original_words(self):
        old = '<p>Лирой Дженкинс — финишер.</p>'
        edited = '[hs_card id="EX1_116"]Лирой Дженкинс[/hs_card] — финишер.'
        self.assertEqual(self.run_content(old, [{'segment': 2, 'replacement': edited}], 'cards'),
                         {'text': '<p>' + edited + '</p>'})

    def test_battlegrounds_shortcode_is_separate_from_constructed(self):
        edited = '[hs_bg id="BG_001"]Лирой Дженкинс[/hs_bg]'
        self.assertEqual(self.run_content('Лирой Дженкинс', [{'segment': 0, 'replacement': edited}], 'cards'), {'text': edited})

    def test_card_mode_rejects_unverified_ids_and_rewritten_words(self):
        for replacement in ['[hs_card id="UNKNOWN"]Лирой[/hs_card]',
                            '[hs_card id="BG_001"]Лирой[/hs_card]',
                            '[hs_card id="EX1_116"]Лирой[/hs_card] лучший']:
            self.assertIn('error', self.run_content('Лирой', [{'segment': 0, 'replacement': replacement}], 'cards'))

    def test_existing_shortcodes_urls_entities_and_tags_are_immutable(self):
        for source in ['[hs_card id="EX1_116"]Лирой[/hs_card]', '<img src="x.png">',
                       'https://example.org/card', '&nbsp;', '<!-- wp:paragraph -->',
                       '<script>danger()</script>']:
            self.assertIn('error', self.run_content(source, [{'segment': 0, 'replacement': 'другое'}], 'editor'))

    def test_proofreading_accepts_typo_and_punctuation_without_rewording(self):
        self.assertEqual(self.run_content('Играйте эту карту она стоит 3 мнаны.',
                         [{'segment': 0, 'replacement': 'Играйте эту карту: она стоит 3 маны.'}], 'proofread'),
                         {'text': 'Играйте эту карту: она стоит 3 маны.'})

    def test_proofreading_rejects_rewording(self):
        self.assertIn('error', self.run_content('Играйте эту карту.',
                      [{'segment': 0, 'replacement': 'Лучше использовать другое существо.'}], 'proofread'))

    def test_all_editing_preserves_numbers_and_prevents_html_injection(self):
        self.assertIn('error', self.run_content('Стоит 3 маны.',
                      [{'segment': 0, 'replacement': 'Стоит 4 маны.'}], 'editor'))
        self.assertIn('error', self.run_content('Стоит 3 маны.',
                      [{'segment': 0, 'replacement': '<script>alert(1)</script> 3'}], 'editor'))

    def test_duplicate_or_out_of_range_segments_fail_atomically(self):
        for edits in [[{'segment': 99, 'replacement': 'Текст'}],
                      [{'segment': 0, 'replacement': 'Текст!'}, {'segment': 0, 'replacement': 'Текст?'}]]:
            self.assertIn('error', self.run_content('Текст.', edits, 'editor'))


if __name__ == '__main__':
    unittest.main()
