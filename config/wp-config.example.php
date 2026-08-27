<?php
/**
 * Безопасный шаблон. Скопируйте его за пределами Git в wp-config.php и
 * передайте реальные значения через окружение или защищённый secret store.
 */

$required_env = static function (string $name): string {
    $value = getenv($name);
    if ($value === false || $value === '') {
        throw new RuntimeException("Required environment variable is missing: {$name}");
    }

    return $value;
};

define('DB_NAME', $required_env('WP_DB_NAME'));
define('DB_USER', $required_env('WP_DB_USER'));
define('DB_PASSWORD', $required_env('WP_DB_PASSWORD'));
define('DB_HOST', getenv('WP_DB_HOST') ?: 'localhost');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', '');

define('AUTH_KEY', $required_env('WP_AUTH_KEY'));
define('SECURE_AUTH_KEY', $required_env('WP_SECURE_AUTH_KEY'));
define('LOGGED_IN_KEY', $required_env('WP_LOGGED_IN_KEY'));
define('NONCE_KEY', $required_env('WP_NONCE_KEY'));
define('AUTH_SALT', $required_env('WP_AUTH_SALT'));
define('SECURE_AUTH_SALT', $required_env('WP_SECURE_AUTH_SALT'));
define('LOGGED_IN_SALT', $required_env('WP_LOGGED_IN_SALT'));
define('NONCE_SALT', $required_env('WP_NONCE_SALT'));

$table_prefix = getenv('WP_TABLE_PREFIX') ?: 'wp_';

define('WP_ENVIRONMENT_TYPE', getenv('WP_ENVIRONMENT_TYPE') ?: 'production');
define('WP_HOME', getenv('WP_HOME') ?: 'https://hs-manacost.ru');
define('WP_SITEURL', getenv('WP_SITEURL') ?: WP_HOME);
define('DISALLOW_FILE_MODS', true);
define('WP_CACHE', true);
define('WP_DEBUG', filter_var(getenv('WP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOLEAN));
define('WP_DEBUG_DISPLAY', false);

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

require_once ABSPATH . 'wp-settings.php';

