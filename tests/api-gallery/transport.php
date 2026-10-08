<?php
/** External provider fixture; all WordPress/media/vote code runs unchanged. */
if (wp_get_environment_type() !== 'local' || wp_parse_url(home_url(), PHP_URL_HOST) !== '127.0.0.1') {
    throw new RuntimeException('Disposable loopback site required');
}
add_filter('pre_http_request', static function ($previous, $args, $url) {
    if (wp_parse_url($url, PHP_URL_HOST)==='hearthstone.wiki.gg' && str_starts_with(wp_parse_url($url, PHP_URL_PATH)??'', '/wiki/Special:Redirect/file/TEST_DIAMOND_')) {
        return ['headers'=>['location'=>str_replace('/wiki/Special:Redirect/file/','/images/',$url)],'body'=>'','response'=>['code'=>301,'message'=>'Redirect'],'cookies'=>[]];
    }
    if (wp_parse_url($url, PHP_URL_HOST)==='hearthstone.wiki.gg' && str_starts_with(wp_parse_url($url, PHP_URL_PATH)??'', '/images/TEST_DIAMOND_')) {
        $file=WP_CONTENT_DIR.'/uploads/hs-gallery-fixture.png';
        if (isset($args['filename'])) { copy($file,$args['filename']); }
        return ['headers'=>['content-type'=>'image/png'],'body'=>file_get_contents($file),'response'=>['code'=>200,'message'=>'OK'],'cookies'=>[]];
    }
    if (wp_parse_url($url, PHP_URL_HOST) !== 'api.kolodahearthstone.com') { return $previous; }
    $file = WP_CONTENT_DIR . '/uploads/hs-gallery-fixture.png';
    $path = wp_parse_url($url, PHP_URL_PATH);
    foreach (['TEST_HTML','TEST_LARGE','TEST_REDIRECT'] as $fault) {
        if (str_ends_with($path,'/'.$fault)) {
            return ['headers'=>['content-type'=>'application/json'],'body'=>wp_json_encode(['data'=>['card_id'=>$fault,'name'=>['ru'=>'Invalid provider image'],'images'=>['card'=>'https://api.kolodahearthstone.com/uploads/hs-gallery-fault/'.$fault.'.png']]]),'response'=>['code'=>200,'message'=>'OK'],'cookies'=>[]];
        }
    }
    if (str_starts_with($path,'/uploads/hs-gallery-fault/')) {
        if (str_contains($path,'TEST_REDIRECT')) {
            return ['headers'=>['location'=>'http://127.0.0.1/private.png'],'body'=>'','response'=>['code'=>302,'message'=>'Redirect'],'cookies'=>[]];
        }
        $body=str_contains($path,'TEST_LARGE') ? str_repeat('x',8388609) : '<html>Not an image</html>';
        if (isset($args['filename'])) { file_put_contents($args['filename'],$body); }
        return ['headers'=>['content-type'=>'image/png'],'body'=>'','response'=>['code'=>200,'message'=>'OK'],'cookies'=>[]];
    }
    if (str_starts_with($path, '/uploads/hs-gallery-fixture/')) {
        if (isset($args['filename'])) { copy($file, $args['filename']); }
        return ['headers'=>[], 'body'=>file_get_contents($file), 'response'=>['code'=>200,'message'=>'OK'], 'cookies'=>[]];
    }
    $rows=[];
    if (str_contains($path, '/diamond-cards') || str_contains($path, '/trinkets')) {
        parse_str(wp_parse_url($url, PHP_URL_QUERY) ?? '', $query);
        $page=max(1,(int)($query['page']??1));
        foreach (range(($page-1)*100+1,min($page*100,137)) as $number) {
            $rows[]=str_contains($path, '/diamond-cards') ?
                ['base_card'=>['card_id'=>'TEST_DIAMOND_'.$number], 'name'=>['ru'=>'Алмазная карта '.$number], 'images'=>['diamond'=>'https://hearthstone.wiki.gg/wiki/Special:Redirect/file/TEST_DIAMOND_'.$number.'_Premium2.png']] :
                ['card_id'=>'TEST_TRINKET_'.$number, 'name'=>['ru'=>'Аксессуар '.$number], 'images'=>['card'=>'https://api.kolodahearthstone.com/uploads/hs-gallery-fixture/TEST_TRINKET_'.$number.'.png']];
        }
        $data=$rows;
        foreach ($rows as $row) { if (($row['card_id']??$row['base_card']['card_id'])===basename($path)) { $data=$row; break; } }
        return ['headers'=>['content-type'=>'application/json'], 'body'=>wp_json_encode(['data'=>$data,'pagination'=>['has_next'=>$page===1]]),'response'=>['code'=>200,'message'=>'OK'],'cookies'=>[]];
    }
    foreach ([1,2] as $number) {
        $rows[]=['card_id'=>'TEST_CARD_'.$number,'name'=>['ru'=>'Тестовая карта '.$number], 'images'=>['card'=>'https://api.kolodahearthstone.com/uploads/hs-gallery-fixture/TEST_CARD_'.$number.'.png']];
    }
    $id = basename($path);
    $body = ['data'=>$rows, 'pagination'=>['has_next'=>false]];
    foreach ($rows as $row) { if ($id === $row['card_id']) { $body=['data'=>$row]; } }
    return ['headers'=>['content-type'=>'application/json'], 'body'=>wp_json_encode($body), 'response'=>['code'=>200,'message'=>'OK'], 'cookies'=>[]];
},10,3);
// Local-only request metrics; the AJAX body and application logic are unchanged.
add_action('admin_init',static function () {
    if (!wp_doing_ajax() || ($_POST['action']??'')!=='hs_api_gallery_catalog') { return; }
    ob_start(static function ($body) {
        global $wpdb;
        header('X-HS-Gallery-Queries: '.$wpdb->num_queries);
        header('X-HS-Gallery-Memory: '.(memory_get_peak_usage(true)/MB_IN_BYTES));
        return $body;
    });
});
