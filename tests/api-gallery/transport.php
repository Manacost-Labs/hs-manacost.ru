<?php
/** External provider fixture; all WordPress/media/vote code runs unchanged. */
if (wp_get_environment_type() !== 'local' || wp_parse_url(home_url(), PHP_URL_HOST) !== '127.0.0.1') {
    throw new RuntimeException('Disposable loopback site required');
}
add_filter('pre_http_request', static function ($previous, $args, $url) {
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
    foreach ([1,2] as $number) {
        $rows[]=['card_id'=>'TEST_CARD_'.$number,'name'=>['ru'=>'Тестовая карта '.$number], 'images'=>['card'=>'https://api.kolodahearthstone.com/uploads/hs-gallery-fixture/TEST_CARD_'.$number.'.png']];
    }
    $id = basename($path);
    $body = ['data'=>$rows, 'pagination'=>['has_next'=>false]];
    foreach ($rows as $row) { if ($id === $row['card_id']) { $body=['data'=>$row]; } }
    return ['headers'=>['content-type'=>'application/json'], 'body'=>wp_json_encode($body), 'response'=>['code'=>200,'message'=>'OK'], 'cookies'=>[]];
},10,3);
