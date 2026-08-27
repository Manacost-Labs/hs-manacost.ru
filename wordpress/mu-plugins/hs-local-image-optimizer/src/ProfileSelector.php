<?php

declare(strict_types=1);

final class HS_Local_Image_Profile_Selector
{
    /**
     * @param array{mime?: string, filename?: string, post_type?: string, has_alpha?: bool} $context
     * @return array{
     *     name: string,
     *     webp: array{enabled: bool, quality: int, lossless: bool},
     *     avif: array{enabled: bool, quantizer: int},
     *     minimum_saving_percent: int
     * }
     */
    public static function select(array $context): array
    {
        $mime = strtolower((string) ($context['mime'] ?? ''));
        $filename = strtolower(basename((string) ($context['filename'] ?? '')));
        $postType = strtolower((string) ($context['post_type'] ?? ''));
        $hasAlpha = (bool) ($context['has_alpha'] ?? false);

        if (
            in_array($postType, ['hs_deck', 'deck', 'koloda'], true)
            || preg_match('/^(?:deck|koloda|колода)-/iu', $filename) === 1
        ) {
            return self::profile('deck', 90, false, false, 24, 5);
        }

        if ($mime === 'image/png' && $hasAlpha) {
            return self::profile('transparent_ui', 100, true, false, 24, 5);
        }

        if ($mime === 'image/png') {
            return self::profile('text_graphic', 90, false, false, 24, 5);
        }

        return self::profile('photo_art', 88, false, true, 24, 8);
    }

    /**
     * @return array{
     *     name: string,
     *     webp: array{enabled: bool, quality: int, lossless: bool},
     *     avif: array{enabled: bool, quantizer: int},
     *     minimum_saving_percent: int
     * }
     */
    private static function profile(
        string $name,
        int $webpQuality,
        bool $webpLossless,
        bool $avifEnabled,
        int $avifQuantizer,
        int $minimumSavingPercent
    ): array {
        return [
            'name' => $name,
            'webp' => [
                'enabled' => true,
                'quality' => $webpQuality,
                'lossless' => $webpLossless,
            ],
            'avif' => [
                'enabled' => $avifEnabled,
                'quantizer' => $avifQuantizer,
            ],
            'minimum_saving_percent' => $minimumSavingPercent,
        ];
    }
}
