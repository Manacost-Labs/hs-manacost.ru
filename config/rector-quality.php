<?php

use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Identical\StrStartsWithRector;

return RectorConfig::configure()
    ->withPaths([__DIR__ . '/../wordpress/mu-plugins'])
    ->withRules([StrStartsWithRector::class]);
