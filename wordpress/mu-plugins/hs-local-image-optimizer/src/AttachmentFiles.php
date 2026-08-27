<?php

declare(strict_types=1);

final class HS_Local_Image_Attachment_Files
{
    /**
     * Return the original upload and every WordPress-generated sub-size.
     * Paths from metadata are reduced to basenames so malformed metadata cannot
     * escape the attachment directory.
     *
     * @param array{sizes?: array<string, array{file?: string}>, original_image?: string} $metadata
     * @return list<string>
     */
    public static function collect(string $attachedFile, array $metadata): array
    {
        if ($attachedFile === '') {
            return [];
        }

        $directory = dirname($attachedFile);
        $files = [];

        self::append($files, $attachedFile);

        if (!empty($metadata['original_image'])) {
            self::append($files, $directory . DIRECTORY_SEPARATOR . basename((string) $metadata['original_image']));
        }

        foreach (($metadata['sizes'] ?? []) as $size) {
            if (!empty($size['file'])) {
                self::append($files, $directory . DIRECTORY_SEPARATOR . basename((string) $size['file']));
            }
        }

        return array_values($files);
    }

    /** @param array<string, string> $files */
    private static function append(array &$files, string $path): void
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        // cwebp and avifenc consume the site's canonical JPEG/PNG uploads.
        // Excluding existing next-generation formats prevents .webp.webp and
        // .avif.webp recursion when WordPress metadata contains such files.
        if (!in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
            return;
        }

        $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $files[$normalized] = $normalized;
    }
}
