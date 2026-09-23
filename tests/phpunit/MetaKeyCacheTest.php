<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

final class MetaKeyCacheTest extends TestCase
{
    public static function scenarios(): array
    {
        return array_map(static fn(string $name): array => [$name], [
            'warm', 'empty', 'upstream', 'frontend', 'limit', 'invalid_limit',
            'add', 'delete', 'delete_all', 'private_value', 'public_value',
            'rename_private', 'rename_failure', 'failed_rename_filter',
            'query_failure', 'cache_failure', 'race', 'blog', 'expiry',
            'default_limit', 'upper_limit', 'coerced_limit',
        ]);
    }

    #[DataProvider('scenarios')]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testCacheContract(string $scenario): void
    {
        // Reuse the existing real-plugin regression fixture with isolated globals.
        // PHPUnit now collects source coverage for the same mutation scenarios.
        global $hooks, $cache, $admin, $blog, $now, $cache_writes, $generation, $wpdb;
        $argv = [__FILE__, dirname(__DIR__, 2) . '/wordpress/mu-plugins/hs-admin-meta-key-cache.php', $scenario];
        $this->expectOutputString("PASS\n");
        require dirname(__DIR__) . '/fixtures/admin-meta-key-cache.php';
    }
}
