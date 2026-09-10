import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';

const loader = new URL('../../wordpress/mu-plugins/hs-manacost-reader/comments-loader.php', import.meta.url).pathname;
const fixture = `define('ABSPATH','/fixture/');
define('HS_MANACOST_READER_COMMENTS_ENABLED', true);
function wp_get_environment_type(){return $GLOBALS['argv'][2];}
function home_url(){return 'https://test.hs-manacost.ru';}
function add_action(...$args){}
function add_filter($hook,$callback){$GLOBALS['filters'][$hook]=$callback;}
$GLOBALS['filters']=array();
require $argv[1]; hs_reader_comments_bootstrap();
$result=array();
foreach(array('rocket_exclude_js','rocket_exclude_css','rocket_delay_js_exclusions','rocket_rucss_external_exclusions') as $hook){
  $result[$hook]=isset($GLOBALS['filters'][$hook]) ? call_user_func($GLOBALS['filters'][$hook],array('/existing/file.js')) : array();
}
echo json_encode($result);`;
for (const environment of ['production', 'staging']) {
  const rules = JSON.parse(execFileSync('php', ['-r', fixture, loader, environment], { encoding: 'utf8' }));
  for (const [hook, patterns] of Object.entries(rules)) {
    if (environment === 'production') { assert.deepEqual(patterns, [], 'staging adapter must not change production optimization'); continue; }
    assert.ok(patterns.includes('/existing/file.js'), `${hook} preserves existing exclusions`);
    for (const file of ['ui.css', 'comments.css', 'comments.js', 'public-profile.js', 'reader.js', 'profile-editor.js']) {
      const url = `https://test.hs-manacost.ru/wp-content/mu-plugins/hs-manacost-reader/${file}?ver=0.7.8`;
      assert.ok(patterns.some(pattern => new RegExp(pattern).test(url)), `${hook} protects the versioned Reader bundle ${file}`);
    }
    assert.equal(patterns.some(pattern => new RegExp(pattern).test('https://test.hs-manacost.ru/wp-content/themes/Newspaper_new/style.css')), false, 'theme assets stay optimized');
  }
}
console.log('comments-assets: pass');
