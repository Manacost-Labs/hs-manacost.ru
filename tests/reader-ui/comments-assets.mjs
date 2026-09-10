import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';

const root = new URL('../../wordpress/', import.meta.url).pathname;
// Load the real vendor matchers after init, as Rocket's buffer processors do.
// Stub only WordPress hooks/environment and the remote dynamic list dependency; no site or cache writes.
const fixture = String.raw`namespace WP_Rocket\Engine\Optimization\DynamicLists {
class DynamicLists { public function get_exclude_js_templates(){return array('data-no-minify');} }
}
namespace {
define('ABSPATH','/fixture/');
define('HS_MANACOST_READER_ENABLED', $argv[3] === '1');
define('HS_MANACOST_READER_COMMENTS_ENABLED', $argv[4] === '1');
define('PERFMATTERS_CACHE_URL','https://test.hs-manacost.ru/wp-content/cache/perfmatters/');
function wp_get_environment_type(){return $GLOBALS['argv'][2];}
function home_url(){return $GLOBALS['argv'][5];}
function add_filter($hook,$callback,$priority=10,...$unused){$GLOBALS['filters'][$hook][$priority][]=$callback;}
function add_action(...$args){add_filter(...$args);}
function callbacks($hook){$items=$GLOBALS['filters'][$hook]??array();ksort($items);return $items;}
function apply_filters($hook,$value,...$args){foreach(callbacks($hook) as $group){foreach($group as $callback){$value=$callback($value,...$args);}}return $value;}
function do_action($hook){foreach(callbacks($hook) as $group){foreach($group as $callback){$callback();}}}
function add_shortcode(...$args){}
function create_rocket_uniqid(){return 'fixture';}
function get_current_blog_id(){return 1;}
function rocket_get_constant($name){return '/fixture/';}
function wp_parse_url($url,$component=-1){return parse_url($url,$component);}
$GLOBALS['filters']=array();
$vendor=$argv[1].'plugins/wp-rocket/inc/';
foreach(array('classes/admin/class-options-data.php','Engine/Optimization/RegexTrait.php','Engine/Optimization/AbstractOptimization.php','Engine/Optimization/AssetsLocalCache.php','Engine/Optimization/Minify/CSS/AbstractCSSOptimization.php','Engine/Optimization/Minify/JS/AbstractJSOptimization.php') as $file){require $vendor.$file;}
foreach(array('Config.php','Utilities.php','Minify.php') as $file){require $argv[1].'plugins/perfmatters/inc/classes/'.$file;}
\Perfmatters\Config::$options=array('assets'=>array());
class ReaderCSSProbe extends \WP_Rocket\Engine\Optimization\Minify\CSS\AbstractCSSOptimization {
  public function excludes($url){return $this->is_minify_excluded_file(array(0=>'<link rel="stylesheet">','url'=>$url));}
}
class ReaderJSProbe extends \WP_Rocket\Engine\Optimization\Minify\JS\AbstractJSOptimization {
  public function excludes($url){return $this->is_minify_excluded_file(array(0=>'<script></script>','url'=>$url));}
}
require $argv[1].'mu-plugins/hs-manacost-reader.php';
do_action('plugins_loaded');
do_action('init');
  $options=new \WP_Rocket\Admin\Options_Data(array('exclude_css'=>array('/existing/file.css'),'exclude_js'=>array('/existing/file.js')));
  $cache=new \WP_Rocket\Engine\Optimization\AssetsLocalCache('/fixture/',null);
  $GLOBALS['css']=new ReaderCSSProbe($options,$cache);
  $GLOBALS['js']=new ReaderJSProbe($options,$cache,new \WP_Rocket\Engine\Optimization\DynamicLists\DynamicLists());
$result=array('rules'=>array(),'excluded'=>array(),'perfExcluded'=>array());
foreach(array('rocket_exclude_js','rocket_exclude_css','rocket_delay_js_exclusions','rocket_rucss_external_exclusions','perfmatters_minify_js_exclusions','perfmatters_minify_css_exclusions','perfmatters_delay_js_exclusions','perfmatters_rucss_excluded_stylesheets') as $hook){
  $result['rules'][$hook]=apply_filters($hook,array('/existing/file.js'));
}
foreach(array('ui.css','comments.css','comments.js','community-ui.js','public-profile.js','reader.js','profile-editor.js') as $file){
  $matcher=str_ends_with($file,'.css') ? $GLOBALS['css'] : $GLOBALS['js'];
  $result['excluded'][$file]=$matcher->excludes('https://test.hs-manacost.ru/wp-content/mu-plugins/hs-manacost-reader/'.$file.'?ver=0.7.8');
  $result['perfExcluded'][$file]=\Perfmatters\Utilities::match_in_array('https://test.hs-manacost.ru/wp-content/mu-plugins/hs-manacost-reader/'.$file.'?ver=0.7.8',\Perfmatters\Minify::get_exclusions(pathinfo($file,PATHINFO_EXTENSION)));
}
$result['themeCSS']=$GLOBALS['css']->excludes('https://test.hs-manacost.ru/wp-content/themes/Newspaper_new/style.css');
$result['themeJS']=$GLOBALS['js']->excludes('https://test.hs-manacost.ru/wp-content/themes/Newspaper_new/script.js');
$result['existingCSS']=$GLOBALS['css']->excludes('https://test.hs-manacost.ru/existing/file.css');
$result['existingJS']=$GLOBALS['js']->excludes('https://test.hs-manacost.ru/existing/file.js');
$result['perfThemeCSS']=\Perfmatters\Utilities::match_in_array('https://test.hs-manacost.ru/wp-content/themes/Newspaper_new/style.css',\Perfmatters\Minify::get_exclusions('css'));
$result['perfThemeJS']=\Perfmatters\Utilities::match_in_array('https://test.hs-manacost.ru/wp-content/themes/Newspaper_new/script.js',\Perfmatters\Minify::get_exclusions('js'));
echo json_encode($result);
}`;
for (const [environment, reader, comments, host, enabled] of [
  ['staging', '1', '1', 'https://test.hs-manacost.ru', true],
  ['production', '1', '1', 'https://test.hs-manacost.ru', false],
  ['staging', '0', '1', 'https://test.hs-manacost.ru', false],
  ['staging', '1', '0', 'https://test.hs-manacost.ru', false],
  ['staging', '1', '1', 'https://hs-manacost.ru', false],
]) {
  const result = JSON.parse(execFileSync('php', ['-r', fixture, root, environment, reader, comments, host], { encoding: 'utf8' }));
  for (const [file, excluded] of Object.entries(result.excluded)) {
    assert.equal(excluded, enabled, `real Rocket matcher: ${environment}/${reader}/${comments}/${host}/${file}`);
    assert.equal(result.perfExcluded[file], enabled, `real Perfmatters matcher: ${environment}/${reader}/${comments}/${host}/${file}`);
  }
  assert.equal(result.themeCSS, false, 'theme CSS stays optimized');
  assert.equal(result.themeJS, false, 'theme JS stays optimized');
  assert.equal(result.perfThemeCSS, false, 'Perfmatters still optimizes theme CSS');
  assert.equal(result.perfThemeJS, false, 'Perfmatters still optimizes theme JS');
  assert.equal(result.existingCSS, true, 'existing CSS exclusions survive');
  assert.equal(result.existingJS, true, 'existing JS exclusions survive');
  for (const [hook, patterns] of Object.entries(result.rules)) {
    if (!enabled) { assert.deepEqual(patterns, ['/existing/file.js'], 'inactive adapter does not change optimization'); continue; }
    assert.ok(patterns.includes('/existing/file.js'), `${hook} preserves existing exclusions`);
    for (const file of ['ui.css', 'comments.css', 'comments.js', 'community-ui.js', 'public-profile.js', 'reader.js', 'profile-editor.js']) {
      const url = `https://test.hs-manacost.ru/wp-content/mu-plugins/hs-manacost-reader/${file}?ver=0.7.8`;
      assert.ok(patterns.some(pattern => new RegExp(pattern).test(url)), `${hook} protects the versioned Reader bundle ${file}`);
    }
    assert.equal(patterns.some(pattern => new RegExp(pattern).test('https://test.hs-manacost.ru/wp-content/themes/Newspaper_new/style.css')), false, 'theme assets stay optimized');
  }
}
console.log('comments-assets: pass (real Rocket and Perfmatters matchers, 5 environment/flag boundaries)');
