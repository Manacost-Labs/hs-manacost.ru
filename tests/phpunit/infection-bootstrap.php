<?php
// Infection needs the real class for reflection while generating mutations.
// Runtime behavior remains covered by the isolated WordPress fixture tests.
define('ABSPATH', __DIR__);
function add_filter(...$args): void {}
function add_action(...$args): void {}
require dirname(__DIR__, 2) . '/wordpress/mu-plugins/hs-admin-meta-key-cache.php';
