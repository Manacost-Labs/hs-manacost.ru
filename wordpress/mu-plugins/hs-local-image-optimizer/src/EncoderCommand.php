<?php

declare(strict_types=1);

final class HS_Local_Image_Encoder_Command
{
    /**
     * Build an argv list for cwebp. Returning an array lets proc_open execute
     * the encoder without a shell, so filenames cannot become shell syntax.
     *
     * @param array{quality: int, lossless: bool} $settings
     * @return list<string>
     */
    public static function webp(string $binary, string $source, string $destination, array $settings): array
    {
        $quality = max(1, min(100, (int) $settings['quality']));
        $command = [
            $binary,
            '-quiet',
            '-mt',
            '-m',
            '6',
        ];

        if ($settings['lossless']) {
            $command[] = '-lossless';
            $command[] = '-q';
            $command[] = '100';
        } else {
            $command[] = '-q';
            $command[] = (string) $quality;
            $command[] = '-pass';
            $command[] = '10';
            $command[] = '-sharp_yuv';
        }

        $command[] = '-metadata';
        $command[] = 'icc';
        $command[] = $source;
        $command[] = '-o';
        $command[] = $destination;

        return $command;
    }

    /**
     * Build an argv list for avifenc. ICC is intentionally retained while
     * camera EXIF and editing XMP metadata are discarded.
     *
     * @param array{quantizer: int, jobs?: int, speed?: int} $settings
     * @return list<string>
     */
    public static function avif(string $binary, string $source, string $destination, array $settings): array
    {
        $quantizer = max(0, min(63, (int) $settings['quantizer']));
        $jobs = max(1, min(8, (int) ($settings['jobs'] ?? 4)));
        $speed = max(0, min(10, (int) ($settings['speed'] ?? 4)));

        return [
            $binary,
            '--jobs',
            (string) $jobs,
            '--speed',
            (string) $speed,
            '--min',
            (string) $quantizer,
            '--max',
            (string) $quantizer,
            '--ignore-exif',
            '--ignore-xmp',
            '--',
            $source,
            $destination,
        ];
    }
}
