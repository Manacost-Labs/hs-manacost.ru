<?php

declare(strict_types=1);

final class HS_Local_Image_Admin_Stats
{
    /**
     * Calculate effective transfer sizes for clients that support WebP or
     * AVIF. Missing variants correctly fall back to the next available file.
     *
     * @param list<string> $sourceFiles
     * @return array{
     *     status: string,
     *     files: int,
     *     source_bytes: int,
     *     webp_bytes: int,
     *     webp_files: int,
     *     webp_saved_bytes: int,
     *     webp_saved_percent: float,
     *     avif_bytes: int,
     *     avif_files: int,
     *     avif_saved_bytes: int,
     *     avif_saved_percent: float
     * }
     */
    public static function calculate(array $sourceFiles): array
    {
        $sourceBytes = 0;
        $webpBytes = 0;
        $avifBytes = 0;
        $files = 0;
        $webpFiles = 0;
        $avifFiles = 0;

        foreach (array_values(array_unique($sourceFiles)) as $source) {
            if (!is_file($source) || !is_readable($source)) {
                continue;
            }

            $sourceSize = max(0, (int) filesize($source));
            if ($sourceSize === 0) {
                continue;
            }

            $webp = $source . '.webp';
            $avif = $source . '.avif';
            $webpSize = self::variantSize($webp);
            $avifSize = self::variantSize($avif);

            $files++;
            $sourceBytes += $sourceSize;
            if ($webpSize !== null) {
                $webpFiles++;
                $webpBytes += $webpSize;
            } else {
                $webpBytes += $sourceSize;
            }

            if ($avifSize !== null) {
                $avifFiles++;
                $avifBytes += $avifSize;
            } elseif ($webpSize !== null) {
                $avifBytes += $webpSize;
            } else {
                $avifBytes += $sourceSize;
            }
        }

        if ($files === 0 || $sourceBytes === 0) {
            return self::emptyResult();
        }

        [$webpSavedBytes, $webpSavedPercent] = self::saving($sourceBytes, $webpBytes);
        [$avifSavedBytes, $avifSavedPercent] = self::saving($sourceBytes, $avifBytes);

        return [
            'status' => 'available',
            'files' => $files,
            'source_bytes' => $sourceBytes,
            'webp_bytes' => $webpBytes,
            'webp_files' => $webpFiles,
            'webp_saved_bytes' => $webpSavedBytes,
            'webp_saved_percent' => $webpSavedPercent,
            'avif_bytes' => $avifBytes,
            'avif_files' => $avifFiles,
            'avif_saved_bytes' => $avifSavedBytes,
            'avif_saved_percent' => $avifSavedPercent,
        ];
    }

    private static function variantSize(string $path): ?int
    {
        if (!is_file($path) || !is_readable($path)) {
            return null;
        }

        $bytes = (int) filesize($path);
        return $bytes > 0 ? $bytes : null;
    }

    /** @return array{0: int, 1: float} */
    private static function saving(int $sourceBytes, int $variantBytes): array
    {
        $savedBytes = max(0, $sourceBytes - $variantBytes);
        $savedPercent = round(($savedBytes / $sourceBytes) * 100, 1);
        return [$savedBytes, $savedPercent];
    }

    /** @return array<string, int|float|string> */
    private static function emptyResult(): array
    {
        return [
            'status' => 'unavailable',
            'files' => 0,
            'source_bytes' => 0,
            'webp_bytes' => 0,
            'webp_files' => 0,
            'webp_saved_bytes' => 0,
            'webp_saved_percent' => 0.0,
            'avif_bytes' => 0,
            'avif_files' => 0,
            'avif_saved_bytes' => 0,
            'avif_saved_percent' => 0.0,
        ];
    }
}
