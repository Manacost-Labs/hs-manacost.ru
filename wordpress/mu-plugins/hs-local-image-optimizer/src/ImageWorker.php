<?php

declare(strict_types=1);

final class HS_Local_Image_Worker
{
    public function __construct(
        private readonly string $cwebpBinary = '/usr/bin/cwebp',
        private readonly string $avifencBinary = '/usr/bin/avifenc',
        private readonly int $timeoutSeconds = 180
    ) {
    }

    /**
     * Encode adjacent sidecars while preserving the source byte-for-byte.
     *
     * @param array{mime?: string, filename?: string, post_type?: string, has_alpha?: bool} $context
     * @return array{status: string, profile?: string, webp?: array<string, mixed>, avif?: array<string, mixed>}
     */
    public function process(string $source, array $context = []): array
    {
        if (!is_file($source) || !is_readable($source)) {
            return ['status' => 'failed_missing_source'];
        }

        $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION));
        if (!in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
            return ['status' => 'skipped_unsupported_source'];
        }

        $imageInfo = @getimagesize($source);
        if ($imageInfo === false) {
            return ['status' => 'failed_invalid_source'];
        }

        $context['mime'] = (string) ($context['mime'] ?? ($imageInfo['mime'] ?? ''));
        $context['filename'] = (string) ($context['filename'] ?? $source);
        if ($extension === 'png' && !array_key_exists('has_alpha', $context)) {
            $context['has_alpha'] = $this->pngHasAlpha($source);
        }

        $profile = HS_Local_Image_Profile_Selector::select($context);
        $result = ['status' => 'processed', 'profile' => $profile['name']];

        $webpDestination = $source . '.webp';
        $result['webp'] = $this->encodeWebp($source, $webpDestination, $profile);

        if (!$profile['avif']['enabled']) {
            $result['avif'] = ['status' => 'skipped_profile'];
            return $result;
        }

        $baseline = is_readable($webpDestination) ? $webpDestination : $source;
        $result['avif'] = $this->encodeAvif($source, $source . '.avif', $baseline, $profile);

        return $result;
    }

    /** @param array<string, mixed> $profile */
    private function encodeWebp(string $source, string $destination, array $profile): array
    {
        if ($this->isCurrent($source, $destination)) {
            return ['status' => 'skipped_up_to_date'];
        }

        $candidate = $this->candidatePath($destination);
        $command = HS_Local_Image_Encoder_Command::webp(
            $this->cwebpBinary,
            $source,
            $candidate,
            $profile['webp']
        );
        $process = HS_Local_Image_Process_Runner::run($command, $this->timeoutSeconds);
        if ($process['status'] !== 'success') {
            $this->discard($candidate);
            return $process;
        }

        return HS_Local_Image_Candidate_Publisher::publish(
            $source,
            $candidate,
            $destination,
            (int) $profile['minimum_saving_percent']
        );
    }

    /** @param array<string, mixed> $profile */
    private function encodeAvif(string $source, string $destination, string $baseline, array $profile): array
    {
        if ($this->isCurrent($source, $destination)) {
            return ['status' => 'skipped_up_to_date'];
        }

        $candidate = $this->candidatePath($destination);
        $command = HS_Local_Image_Encoder_Command::avif(
            $this->avifencBinary,
            $source,
            $candidate,
            [
                'quantizer' => (int) $profile['avif']['quantizer'],
                'jobs' => 4,
                'speed' => 4,
            ]
        );
        $process = HS_Local_Image_Process_Runner::run($command, $this->timeoutSeconds);
        if ($process['status'] !== 'success') {
            $this->discard($candidate);
            return $process;
        }

        return HS_Local_Image_Candidate_Publisher::publish(
            $source,
            $candidate,
            $destination,
            (int) $profile['minimum_saving_percent'],
            $baseline
        );
    }

    private function isCurrent(string $source, string $destination): bool
    {
        return is_file($destination)
            && (int) filesize($destination) > 0
            && (int) filemtime($destination) >= (int) filemtime($source);
    }

    private function candidatePath(string $destination): string
    {
        return $destination . '.hs-local-' . bin2hex(random_bytes(6)) . '.tmp' . '.' . pathinfo($destination, PATHINFO_EXTENSION);
    }

    private function discard(string $path): void
    {
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function pngHasAlpha(string $source): bool
    {
        $handle = @fopen($source, 'rb');
        if ($handle === false) {
            return false;
        }

        try {
            $header = (string) fread($handle, 26);
            if (strlen($header) < 26 || substr($header, 1, 3) !== 'PNG') {
                return false;
            }

            $colorType = ord($header[25]);
            if ($colorType === 4 || $colorType === 6) {
                return true;
            }

            // Indexed and grayscale PNG files can carry transparency in a
            // tRNS chunk even though the IHDR color type has no alpha channel.
            fseek($handle, 8);
            while (!feof($handle)) {
                $lengthBytes = fread($handle, 4);
                $type = fread($handle, 4);
                if (strlen((string) $lengthBytes) !== 4 || strlen((string) $type) !== 4) {
                    break;
                }

                $length = unpack('Nlength', $lengthBytes)['length'];
                if ($type === 'tRNS') {
                    return true;
                }
                if ($type === 'IDAT' || $type === 'IEND' || $length > 16_777_216) {
                    break;
                }

                fseek($handle, $length + 4, SEEK_CUR);
            }

            return false;
        } finally {
            fclose($handle);
        }
    }
}
