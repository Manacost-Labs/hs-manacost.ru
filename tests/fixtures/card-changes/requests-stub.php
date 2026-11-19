<?php
// Stand-in for the WordPress Requests library: only its boundary (request_multiple, Response) is replaced.
namespace WpOrg\Requests {
    class Response {
        public $status_code = 200;
        public $headers = [];
        public $body = '';
    }
    class Requests {
        public static function request_multiple($requests, $options = []) {
            return \Requests_Stub::run($requests, $options);
        }
    }
}
namespace {
    class Requests_Stub {
        public static $mode = 'ok';
        public static $seen = [];
        public static function run($requests, $options) {
            self::$seen[] = ['requests' => $requests, 'options' => $options];
            if (self::$mode === 'throw') throw new \RuntimeException('curl error 28: operation timed out');
            $out = [];
            foreach ($requests as $id => $request) {
                $response = new \WpOrg\Requests\Response();
                $response->headers = ['content-type' => 'application/json; charset=utf-8'];
                $response->body = file_get_contents(__DIR__ . '/constructed_card-BE_285.json');
                $out[$id] = $response;
            }
            return $out;
        }
    }
}
