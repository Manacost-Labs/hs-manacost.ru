<?php

declare(strict_types=1);

final class HS_Local_Image_Admin_Media_UI
{
    /** @var array<int, array<string, mixed>> */
    private static array $cache = [];

    public static function boot(): void
    {
        add_filter('manage_media_columns', [self::class, 'addColumn']);
        add_action('manage_media_custom_column', [self::class, 'renderColumn'], 10, 2);
        add_filter('attachment_fields_to_edit', [self::class, 'addAttachmentField'], 20, 2);
        add_filter('wp_prepare_attachment_for_js', [self::class, 'addJsData'], 20, 3);
        add_action('admin_enqueue_scripts', [self::class, 'enqueueAssets']);
    }

    /** @param array<string, string> $columns */
    public static function addColumn(array $columns): array
    {
        $columns['hs_image_savings'] = 'Сжатие изображения';
        return $columns;
    }

    public static function renderColumn(string $columnName, int $attachmentId): void
    {
        if ($columnName !== 'hs_image_savings') {
            return;
        }

        echo self::render(self::attachmentStats($attachmentId), true); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    /**
     * @param array<string, array<string, mixed>> $fields
     * @return array<string, array<string, mixed>>
     */
    public static function addAttachmentField(array $fields, WP_Post $attachment): array
    {
        if (!wp_attachment_is_image($attachment)) {
            return $fields;
        }

        $fields['hs_image_savings'] = [
            'label' => 'Сжатие изображения',
            'input' => 'html',
            'html' => self::render(self::attachmentStats((int) $attachment->ID)),
            'helps' => '«Посетитель скачает» относится к главному изображению. «Все размеры WordPress» — сумма оригинала и миниатюр.',
        ];

        return $fields;
    }

    /**
     * @param array<string, mixed> $response
     * @param array<string, mixed> $meta
     * @return array<string, mixed>
     */
    public static function addJsData(array $response, WP_Post $attachment, mixed $meta): array
    {
        if (!wp_attachment_is_image($attachment)) {
            return $response;
        }

        $response['hsImageSavings'] = self::badge(self::attachmentStats((int) $attachment->ID));
        return $response;
    }

    public static function enqueueAssets(string $hookSuffix): void
    {
        if ($hookSuffix !== 'upload.php') {
            return;
        }

        wp_add_inline_style(
            'common',
            '.column-hs_image_savings{width:300px}.hs-image-savings{font-size:12px;line-height:1.5}'
            . '.hs-image-savings__headline{font-size:13px}.hs-image-savings__saving{color:#008a20;font-weight:700}'
            . '.hs-image-savings__meta,.hs-image-savings__note{color:#50575e}'
            . '.hs-image-savings__aggregate{margin-top:5px;padding-top:5px;border-top:1px solid #dcdcde}'
            . '.hs-image-savings__history{margin-top:4px;color:#646970}.hs-image-savings--empty{color:#646970}'
            . '.attachment-preview{position:relative}'
            . '.hs-image-savings-badge{position:absolute;z-index:5;right:6px;top:6px;padding:3px 7px;'
            . 'border-radius:12px;background:#135e96;color:#fff;font-size:11px;font-weight:600;line-height:1.4;'
            . 'box-shadow:0 1px 3px rgba(0,0,0,.3);pointer-events:none}'
            . '.hs-image-savings-badge--empty{background:#50575e;color:#fff}'
        );

        wp_add_inline_script(
            'media-views',
            <<<'JS'
(function () {
    'use strict';

    function paintSavingsBadges() {
        if (!window.wp || !wp.media || typeof wp.media.attachment !== 'function') {
            return;
        }

        document.querySelectorAll('.attachments .attachment[data-id]').forEach(function (tile) {
            var preview = tile.querySelector('.attachment-preview');
            if (!preview) {
                return;
            }

            var model = wp.media.attachment(parseInt(tile.getAttribute('data-id'), 10));
            var stats = model && model.get('hsImageSavings');
            if (!stats || !stats.label) {
                return;
            }

            var badge = preview.querySelector('.hs-image-savings-badge');
            if (!badge) {
                badge = document.createElement('span');
                preview.appendChild(badge);
            }
            var className = 'hs-image-savings-badge' + (stats.available ? '' : ' hs-image-savings-badge--empty');
            if (badge.className !== className) {
                badge.className = className;
            }
            if (badge.textContent !== stats.label) {
                badge.textContent = stats.label;
            }
            if (badge.title !== (stats.title || '')) {
                badge.title = stats.title || '';
            }
        });
    }

    document.addEventListener('DOMContentLoaded', paintSavingsBadges);
    document.addEventListener('readystatechange', paintSavingsBadges);
    if (window.jQuery) {
        window.jQuery(document).ajaxComplete(paintSavingsBadges);
    }

    var startObserver = function () {
        if (!document.body || !window.MutationObserver) {
            return;
        }
        new MutationObserver(paintSavingsBadges).observe(document.body, {childList: true, subtree: true});
        paintSavingsBadges();
    };
    if (document.body) {
        startObserver();
    } else {
        document.addEventListener('DOMContentLoaded', startObserver);
    }
}());
JS
        );
    }

    /** @return array{label: string, title: string, available: bool} */
    public static function badge(array $stats): array
    {
        if (($stats['status'] ?? '') !== 'available') {
            return ['label' => 'Нет данных', 'title' => 'Статистика сжатия недоступна', 'available' => false];
        }

        $primary = isset($stats['primary']) && is_array($stats['primary']) ? $stats['primary'] : $stats;

        if ((int) ($primary['avif_files'] ?? 0) > 0) {
            $percent = (float) $primary['avif_saved_percent'];
            return [
                'label' => 'AVIF ' . size_format((int) $primary['avif_bytes'], 0) . ' · −' . self::percent($percent),
                'title' => self::variantTitle('AVIF', $primary, 'avif'),
                'available' => true,
            ];
        }

        if ((int) ($primary['webp_files'] ?? 0) > 0) {
            $percent = (float) $primary['webp_saved_percent'];
            return [
                'label' => 'WebP ' . size_format((int) $primary['webp_bytes'], 0) . ' · −' . self::percent($percent),
                'title' => self::variantTitle('WebP', $primary, 'webp'),
                'available' => true,
            ];
        }

        $imagify = $stats['imagify'] ?? [];
        if (is_array($imagify) && (float) ($imagify['percent'] ?? 0) > 0) {
            return [
                'label' => 'Imagify −' . self::percent((float) $imagify['percent']),
                'title' => 'Экономия по сохранённой статистике Imagify',
                'available' => true,
            ];
        }

        return ['label' => 'Без sidecar', 'title' => 'WebP/AVIF ещё не созданы', 'available' => false];
    }

    /** @return array<string, mixed> */
    private static function attachmentStats(int $attachmentId): array
    {
        if (isset(self::$cache[$attachmentId])) {
            return self::$cache[$attachmentId];
        }

        $attachedFile = get_attached_file($attachmentId, true);
        $metadata = wp_get_attachment_metadata($attachmentId);
        $files = is_string($attachedFile)
            ? HS_Local_Image_Attachment_Files::collect($attachedFile, is_array($metadata) ? $metadata : [])
            : [];
        $stats = HS_Local_Image_Admin_Stats::calculate($files);
        $stats['primary'] = is_string($attachedFile)
            ? HS_Local_Image_Admin_Stats::calculate([$attachedFile])
            : HS_Local_Image_Admin_Stats::calculate([]);

        $imagifyData = get_post_meta($attachmentId, '_imagify_data', true);
        $imagifyStats = is_array($imagifyData) && isset($imagifyData['stats']) && is_array($imagifyData['stats'])
            ? $imagifyData['stats']
            : [];
        if ((float) ($imagifyStats['percent'] ?? 0) > 0) {
            $stats['imagify'] = [
                'original_size' => max(0, (int) ($imagifyStats['original_size'] ?? 0)),
                'optimized_size' => max(0, (int) ($imagifyStats['optimized_size'] ?? 0)),
                'percent' => round((float) $imagifyStats['percent'], 1),
            ];
        }

        self::$cache[$attachmentId] = $stats;
        return $stats;
    }

    private static function render(array $stats, bool $compact = false): string
    {
        if (($stats['status'] ?? '') !== 'available') {
            return '<span class="hs-image-savings hs-image-savings--empty">Нет данных</span>';
        }

        $primary = isset($stats['primary']) && is_array($stats['primary']) ? $stats['primary'] : $stats;
        [$format, $key] = self::preferredVariant($primary);
        $lines = [];

        if ($key !== '') {
            $lines[] = '<div class="hs-image-savings__headline"><strong>Посетитель скачает:</strong> '
                . esc_html($format) . ' ' . esc_html(size_format((int) $primary[$key . '_bytes'], 1))
                . ' <span class="hs-image-savings__saving">(−'
                . esc_html(self::percent((float) $primary[$key . '_saved_percent'])) . ')</span></div>';
            $lines[] = '<div class="hs-image-savings__meta">Оригинал сохранён: '
                . esc_html(size_format((int) $primary['source_bytes'], 1)) . '</div>';
        } else {
            $lines[] = '<div class="hs-image-savings__headline"><strong>Посетитель скачает оригинал:</strong> '
                . esc_html(size_format((int) $primary['source_bytes'], 1)) . '</div>';
            $lines[] = '<div class="hs-image-savings__meta">WebP/AVIF ещё не созданы</div>';
        }

        $aggregateFormat = $key !== '' ? $key : ((int) $stats['webp_files'] > 0 ? 'webp' : '');
        if ($aggregateFormat !== '') {
            $aggregateLabel = strtoupper($aggregateFormat);
            $lines[] = '<div class="hs-image-savings__aggregate"><strong>Все размеры WordPress ('
                . esc_html((string) $stats['files']) . ' файлов):</strong><br>'
                . esc_html(size_format((int) $stats['source_bytes'], 1)) . ' → '
                . esc_html(size_format((int) $stats[$aggregateFormat . '_bytes'], 1))
                . ' через ' . esc_html($aggregateLabel) . ' · −'
                . esc_html(self::percent((float) $stats[$aggregateFormat . '_saved_percent'])) . '</div>';
        }

        if (!$compact) {
            $lines[] = '<div class="hs-image-savings__note">Большой оригинал остаётся на диске как резервный; современный браузер получает WebP/AVIF.</div>';
        }
        if (isset($stats['imagify']) && is_array($stats['imagify'])) {
            $imagify = $stats['imagify'];
            $lines[] = '<div class="hs-image-savings__history"><strong>История Imagify:</strong> −' . esc_html(self::percent((float) $imagify['percent']))
                . ' (' . esc_html(size_format((int) $imagify['original_size'], 1)) . ' → '
                . esc_html(size_format((int) $imagify['optimized_size'], 1)) . ')</div>';
        }

        return '<div class="hs-image-savings">' . implode('', $lines) . '</div>';
    }

    /** @return array{0: string, 1: string} */
    private static function preferredVariant(array $stats): array
    {
        if ((int) ($stats['avif_files'] ?? 0) > 0) {
            return ['AVIF', 'avif'];
        }
        if ((int) ($stats['webp_files'] ?? 0) > 0) {
            return ['WebP', 'webp'];
        }
        return ['', ''];
    }

    private static function variantTitle(string $label, array $stats, string $key): string
    {
        return $label . ': ' . size_format((int) $stats['source_bytes'], 1) . ' → '
            . size_format((int) $stats[$key . '_bytes'], 1)
            . ', сэкономлено ' . size_format((int) $stats[$key . '_saved_bytes'], 1);
    }

    private static function percent(float $value): string
    {
        return number_format_i18n(max(0, $value), 1) . '%';
    }
}
