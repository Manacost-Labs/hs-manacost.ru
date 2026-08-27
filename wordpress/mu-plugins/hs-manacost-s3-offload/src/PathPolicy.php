<?php

declare(strict_types=1);

namespace HsManacost\S3Offload;

final class PathPolicy
{
    /** @var list<string> */
    private const IMAGE_EXTENSIONS = [
        'avif',
        'bmp',
        'gif',
        'heic',
        'heif',
        'ico',
        'jpeg',
        'jpg',
        'png',
        'svg',
        'tif',
        'tiff',
        'webp',
    ];

    public static function relativeImagePath(string $candidate, string $baseDirectory): ?string
    {
        if ($candidate === '' || $baseDirectory === '' || str_contains($candidate, "\0")) {
            return null;
        }

        $candidate = str_replace('\\', '/', $candidate);
        $baseDirectory = rtrim(str_replace('\\', '/', $baseDirectory), '/');
        $prefix = $baseDirectory . '/';

        if (!str_starts_with($candidate, $prefix)) {
            return null;
        }

        $relative = substr($candidate, strlen($prefix));
        if ($relative === '') {
            return null;
        }

        foreach (explode('/', $relative) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return null;
            }
        }

        $extension = strtolower((string) pathinfo($relative, PATHINFO_EXTENSION));
        if (!in_array($extension, self::IMAGE_EXTENSIONS, true)) {
            return null;
        }

        return $relative;
    }

    public static function objectUrl(string $publicBaseUrl, string $objectPrefix, string $relativePath): string
    {
        $segments = array_merge(
            explode('/', trim($objectPrefix, '/')),
            explode('/', ltrim($relativePath, '/'))
        );
        $encodedPath = implode('/', array_map('rawurlencode', $segments));

        return rtrim($publicBaseUrl, '/') . '/' . $encodedPath;
    }
}
