<?php
// Deterministic public-provider boundary. Never loaded outside the disposable site.
if (wp_get_environment_type() !== 'local' || wp_parse_url(home_url(), PHP_URL_HOST) !== '127.0.0.1') {
    throw new RuntimeException('Disposable loopback WordPress required');
}
// A controlled baseline proves which block-editor errors predate this feature.
add_action('init',static function() {
    if (!isset($_GET['hs_cards_baseline'])) return;
    foreach (['admin_enqueue_scripts'=>'enqueue','admin_footer'=>'dialog','media_buttons'=>'button','add_meta_boxes'=>'block_metabox'] as $hook=>$method) {
        remove_action($hook,['HS_Article_Cards_Admin',$method]);
    }
    remove_filter('mce_css',['HS_Article_Cards_Render','editor_css']);
});
add_filter('pre_http_request', static function ($pre, $args, $url) {
    if (!str_starts_with($url, 'https://kolodahearthstone.com/wp-json/wp/v2/')) return $pre;
    parse_str((string) wp_parse_url($url, PHP_URL_QUERY), $q);
    $path = wp_parse_url($url, PHP_URL_PATH);
    if (($q['search'] ?? '') === '__fail__') return new WP_Error('http_request_failed', 'Fixture timeout');
    $pages=2;
    if (str_ends_with($path, '/categories')) {
        $items=(int)($q['page'] ?? 1)===1 ? [['id'=>14,'name'=>'Гайды']] : [['id'=>16,'name'=>'Поля Сражений']];
    } else {
        $id=(int)($q['page'] ?? 1)===2 ? 102 : 101;
        $items=[['id'=>$id,'title'=>['rendered'=>($id===101 ? 'Колода мага: ' : 'Колода воина: ') . 'гайд и советы'],'date'=>'2026-10-23T14:30:00','link'=>'https://kolodahearthstone.com/guide-'.$id.'/', '_embedded'=>['wp:featuredmedia'=>[['source_url'=>'https://kolodahearthstone.com/wp-content/uploads/card-fixture.png']]]]];
        if (($q['search'] ?? '')==='НетТакойСтатьи' || (int)($q['categories'] ?? 0)===16) $items=[];
        if (preg_match('#/posts/([0-9]+)$#',$path,$match)) { $items=$items[0]; $items['id']=(int)$match[1]; $items['link']='https://kolodahearthstone.com/guide-'.$match[1].'/'; }
    }
    return ['response'=>['code'=>200], 'headers'=>['content-type'=>'application/json','x-wp-totalpages'=>(string)$pages], 'body'=>wp_json_encode($items)];
}, 10, 3);
