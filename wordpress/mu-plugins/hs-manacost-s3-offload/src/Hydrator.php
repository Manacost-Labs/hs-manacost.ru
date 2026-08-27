<?php

declare(strict_types=1);

namespace HsManacost\S3Offload;

use Closure;
use Throwable;

final class Hydrator
{
    private readonly Closure $downloader;

    public function __construct(callable $downloader)
    {
        $this->downloader = Closure::fromCallable($downloader);
    }

    public function restore(
        string $candidate,
        string $baseDirectory,
        string $publicBaseUrl,
        string $objectPrefix
    ): bool {
        if (is_file($candidate) && !is_link($candidate)) {
            return true;
        }

        $relative = PathPolicy::relativeImagePath($candidate, $baseDirectory);
        if ($relative === null || is_link($candidate)) {
            return false;
        }

        $directory = dirname($candidate);
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            return false;
        }

        $lockPath = $candidate . '.hs-s3.lock';
        $lock = fopen($lockPath, 'c');
        if ($lock === false) {
            return false;
        }

        $temporary = null;

        try {
            if (!flock($lock, LOCK_EX)) {
                return false;
            }

            if (is_file($candidate) && !is_link($candidate)) {
                return true;
            }

            $temporary = $candidate . '.hs-s3-' . bin2hex(random_bytes(8)) . '.tmp';
            $url = PathPolicy::objectUrl($publicBaseUrl, $objectPrefix, $relative);
            $downloaded = ($this->downloader)($url, $temporary);

            if (!$downloaded || !is_file($temporary) || filesize($temporary) === 0) {
                return false;
            }

            if (is_file($candidate) && !is_link($candidate)) {
                return true;
            }

            chmod($temporary, 0644);

            return rename($temporary, $candidate);
        } catch (Throwable) {
            return false;
        } finally {
            if ($temporary !== null && is_file($temporary)) {
                unlink($temporary);
            }
            flock($lock, LOCK_UN);
            fclose($lock);
            if (is_file($lockPath)) {
                unlink($lockPath);
            }
        }
    }
}
