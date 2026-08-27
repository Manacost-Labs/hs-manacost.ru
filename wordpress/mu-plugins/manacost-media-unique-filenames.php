<?php
/**
 * Plugin Name: Manacost Media Unique Filenames
 * Description: Keeps WordPress upload names unique after older media files have been offloaded from local storage.
 * Version: 1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class Manacost_Media_Unique_Filenames
{
    /** @var array<string, array<int, string>> */
    private static array $attachment_filename_cache = [];

    public static function bootstrap(): void
    {
        // WordPress uses this list to avoid future image-subsize collisions.
        // https://developer.wordpress.org/reference/hooks/pre_wp_unique_filename_file_list/
        add_filter('pre_wp_unique_filename_file_list', [__CLASS__, 'existing_files'], 10, 3);

        // Core checks exact collisions only on the local filesystem. Recheck its final
        // candidate against attachment metadata because offloaded originals are absent locally.
        add_filter('wp_unique_filename', [__CLASS__, 'ensure_unique'], 10, 6);
    }

    /**
     * @param array<int, string>|null $files
     * @return array<int, string>|null
     */
    public static function existing_files($files, string $dir, string $filename): ?array
    {
        $uploads          = wp_get_upload_dir();
        $base_directory   = isset($uploads['basedir']) ? (string) $uploads['basedir'] : '';
        $relative_directory = self::relative_upload_directory($dir, $base_directory);

        if ($relative_directory === null) {
            return is_array($files) ? $files : null;
        }

        $local_files = is_array($files) ? $files : self::local_files($dir);

        return array_values(array_unique(array_merge(
            $local_files,
            self::attachment_filenames($relative_directory)
        )));
    }

    /**
     * @param callable|null        $unique_filename_callback
     * @param array<string, string> $alt_filenames
     * @param int|string           $number
     */
    public static function ensure_unique(
        string $filename,
        string $ext,
        string $dir,
        $unique_filename_callback,
        array $alt_filenames,
        $number
    ): string {
        unset($unique_filename_callback);

        $files = self::existing_files(null, $dir, $filename);
        if ($files === null || $ext === '') {
            return $filename;
        }

        $extension = self::filename_extension($filename, $ext);
        $attempts  = 0;
        $limit     = count($files) + 1;

        while ($attempts <= $limit && self::has_collision($filename, $alt_filenames, $files, $dir)) {
            $new_number = (int) $number + 1;
            $filename   = self::add_number($filename, $extension, $number, $new_number);

            foreach ($alt_filenames as $alt_ext => $alt_filename) {
                $alt_filenames[$alt_ext] = self::add_number($alt_filename, $alt_ext, $number, $new_number);
            }

            $number = $new_number;
            ++$attempts;
        }

        return $filename;
    }

    /**
     * @param array<string, string> $alt_filenames
     * @param array<int, string>    $files
     */
    private static function has_collision(
        string $filename,
        array $alt_filenames,
        array $files,
        string $dir
    ): bool {
        foreach (array_merge([$filename], array_values($alt_filenames)) as $candidate) {
            if (in_array($candidate, $files, true) || file_exists(rtrim($dir, '/\\') . '/' . $candidate)) {
                return true;
            }

            if (function_exists('_wp_check_existing_file_names')
                && _wp_check_existing_file_names($candidate, $files)) {
                return true;
            }
        }

        return false;
    }

    /** @param int|string $number */
    private static function add_number(string $filename, string $ext, $number, int $new_number): string
    {
        return str_replace(
            ["-{$number}{$ext}", "{$number}{$ext}"],
            "-{$new_number}{$ext}",
            $filename
        );
    }

    private static function filename_extension(string $filename, string $fallback): string
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        return $extension === '' ? $fallback : '.' . $extension;
    }

    /** @return array<int, string> */
    private static function local_files(string $dir): array
    {
        if (!is_dir($dir)) {
            return [];
        }

        $files = @scandir($dir);
        return is_array($files) ? array_values($files) : [];
    }

    private static function relative_upload_directory(string $dir, string $base_directory): ?string
    {
        if ($dir === '' || $base_directory === '' || str_contains($dir . $base_directory, "\0")) {
            return null;
        }

        $dir            = rtrim(str_replace('\\', '/', $dir), '/');
        $base_directory = rtrim(str_replace('\\', '/', $base_directory), '/');

        if ($dir === $base_directory) {
            return '';
        }

        $prefix = $base_directory . '/';
        if (!str_starts_with($dir, $prefix)) {
            return null;
        }

        $relative = substr($dir, strlen($prefix));
        foreach (explode('/', $relative) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return null;
            }
        }

        return $relative;
    }

    /** @return array<int, string> */
    private static function attachment_filenames(string $relative_directory): array
    {
        global $wpdb;

        if (!is_object($wpdb)
            || !isset($wpdb->postmeta)
            || !is_string($wpdb->postmeta)
            || !method_exists($wpdb, 'esc_like')
            || !method_exists($wpdb, 'prepare')
            || !method_exists($wpdb, 'get_col')) {
            return [];
        }

        $cache_key = $wpdb->postmeta . '|' . $relative_directory;
        if (isset(self::$attachment_filename_cache[$cache_key])) {
            return self::$attachment_filename_cache[$cache_key];
        }

        $prefix  = $relative_directory === '' ? '' : $relative_directory . '/';
        $pattern = $wpdb->esc_like($prefix) . '%';
        $query   = $wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value LIKE %s",
            '_wp_attached_file',
            $pattern
        );
        $paths   = $wpdb->get_col($query);

        if (!is_array($paths)) {
            return [];
        }

        $filenames = [];
        foreach ($paths as $path) {
            if (!is_string($path) || $path === '' || str_contains($path, "\0")) {
                continue;
            }

            $path      = ltrim(str_replace('\\', '/', $path), '/');
            $directory = dirname($path);
            $directory = $directory === '.' ? '' : $directory;

            if ($directory === $relative_directory) {
                $filenames[] = basename($path);
            }
        }

        self::$attachment_filename_cache[$cache_key] = array_values(array_unique($filenames));
        return self::$attachment_filename_cache[$cache_key];
    }
}

Manacost_Media_Unique_Filenames::bootstrap();
