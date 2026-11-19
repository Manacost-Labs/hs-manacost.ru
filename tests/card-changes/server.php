<?php
// Local HTTP stand-in for the history API, used to exercise the real WordPress transport (Requests): redirects, size and time limits, 404/5xx.
$path = (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
file_put_contents('/tmp/hs-cc-server-hits.log', $path . "\n", FILE_APPEND);
if (!preg_match('#/card-history/[a-z_]+/(\w+)$#', $path, $m)) { http_response_code(404); return; }
$fixture = file_get_contents(__DIR__ . '/card-changes/constructed_card-BE_285.json');
$json = static function (string $body) { header('Content-Type: application/json; charset=utf-8'); echo $body; };
switch ($m[1]) {
    case 'SCN_OK': $json($fixture); break;
    case 'SCN_404': http_response_code(404); break;
    case 'SCN_500': http_response_code(500); header('Retry-After: 120'); break;
    case 'SCN_302': header('Location: http://' . $_SERVER['HTTP_HOST'] . '/redirected', true, 302); break;
    case 'SCN_BIG': $json('{"data":{"versions":[],"pad":"' . str_repeat('a', 700000) . '"}}'); break;
    case 'SCN_SLOW': sleep(9); $json($fixture); break;
    default:
        // Start and end of each slow request, so the test can prove the client's requests overlapped at the server.
        $started = microtime(true);
        usleep(1200000);
        file_put_contents('/tmp/hs-cc-server-spans.log', $m[1] . ' ' . $started . ' ' . microtime(true) . "\n", FILE_APPEND | LOCK_EX);
        $json($fixture);
}
