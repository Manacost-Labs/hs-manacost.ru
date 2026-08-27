<?php

declare(strict_types=1);

final class HS_Local_Image_Candidate_Publisher
{
    /**
     * @return array{status: string, source_bytes?: int, candidate_bytes?: int, saving_percent?: float}
     */
    public static function publish(
        string $source,
        string $candidate,
        string $destination,
        int $minimumSavingPercent,
        ?string $sizeBaseline = null
    ): array {
        if (!is_readable($source) || !is_file($candidate)) {
            self::discard($candidate);
            return ['status' => 'failed_missing_file'];
        }

        $sourceBytes = (int) filesize($source);
        $candidateBytes = (int) filesize($candidate);
        if ($sourceBytes <= 0 || $candidateBytes <= 0) {
            self::discard($candidate);
            return ['status' => 'failed_empty_file'];
        }

        $sourceDimensions = @getimagesize($source);
        $candidateDimensions = @getimagesize($candidate);
        if (
            $sourceDimensions === false
            || $candidateDimensions === false
            || $sourceDimensions[0] !== $candidateDimensions[0]
            || $sourceDimensions[1] !== $candidateDimensions[1]
        ) {
            self::discard($candidate);
            return ['status' => 'failed_dimension_mismatch'];
        }

        $baselineBytes = $sourceBytes;
        if ($sizeBaseline !== null && is_readable($sizeBaseline)) {
            $baselineBytes = (int) filesize($sizeBaseline);
        }
        if ($baselineBytes <= 0) {
            self::discard($candidate);
            return ['status' => 'failed_empty_file'];
        }

        $savingPercent = round((1 - ($candidateBytes / $baselineBytes)) * 100, 2);
        if ($savingPercent < max(0, $minimumSavingPercent)) {
            self::discard($candidate);
            return [
                'status' => 'skipped_not_smaller',
                'source_bytes' => $sourceBytes,
                'candidate_bytes' => $candidateBytes,
                'saving_percent' => $savingPercent,
            ];
        }

        if (!@rename($candidate, $destination)) {
            self::discard($candidate);
            return ['status' => 'failed_publish'];
        }

        @chmod($destination, fileperms($source) & 0777);

        return [
            'status' => 'published',
            'source_bytes' => $sourceBytes,
            'candidate_bytes' => $candidateBytes,
            'saving_percent' => $savingPercent,
        ];
    }

    private static function discard(string $candidate): void
    {
        if (is_file($candidate)) {
            @unlink($candidate);
        }
    }
}
