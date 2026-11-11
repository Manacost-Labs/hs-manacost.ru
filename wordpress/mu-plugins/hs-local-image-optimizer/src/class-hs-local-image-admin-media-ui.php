<?php
/**
 * Compression savings in the WordPress media library.
 *
 * @package Manacost
 */

declare(strict_types=1);

/**
 * Shows what a visitor downloads for each image: list column, attachment
 * details and a badge on media-grid thumbnails.
 */
final class HS_Local_Image_Admin_Media_UI {
	/**
	 * Stored file sizes of an attachment and its sidecars. Reading them keeps
	 * media screens off the filesystem, which is a network mount on some sites.
	 */
	private const STATS_META = '_hs_local_image_optimizer_stats';

	/**
	 * Statistics already resolved during this request.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private static array $cache = array();

	/**
	 * Register media library hooks.
	 */
	public static function boot(): void {
		add_filter( 'manage_media_columns', array( self::class, 'add_column' ) );
		add_action( 'manage_media_custom_column', array( self::class, 'render_column' ), 10, 2 );
		add_filter( 'attachment_fields_to_edit', array( self::class, 'add_attachment_field' ), 20, 2 );
		add_filter( 'wp_prepare_attachment_for_js', array( self::class, 'add_js_data' ), 20, 2 );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
	}

	/**
	 * Add the savings column to the media list table.
	 *
	 * @param array<string, string> $columns List table columns.
	 *
	 * @return array<string, string>
	 */
	public static function add_column( array $columns ): array {
		$columns['hs_image_savings'] = 'Сжатие изображения';
		return $columns;
	}

	/**
	 * Print the savings column of one attachment.
	 *
	 * @param string $column_name   Column being rendered.
	 * @param int    $attachment_id Attachment post ID.
	 */
	public static function render_column( string $column_name, int $attachment_id ): void {
		if ( 'hs_image_savings' !== $column_name ) {
			return;
		}

		echo self::render( self::attachment_stats( $attachment_id ), true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render() escapes every value.
	}

	/**
	 * Add the savings breakdown to the attachment details.
	 *
	 * @param array<string, array<string, mixed>> $fields     Attachment form fields.
	 * @param WP_Post                             $attachment Attachment post.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function add_attachment_field( array $fields, WP_Post $attachment ): array {
		if ( ! wp_attachment_is_image( $attachment ) ) {
			return $fields;
		}

		$fields['hs_image_savings'] = array(
			'label' => 'Сжатие изображения',
			'input' => 'html',
			'html'  => self::render( self::attachment_stats( (int) $attachment->ID ) ),
			'helps' => '«Посетитель скачает» относится к главному изображению. «Все размеры WordPress» — сумма оригинала и миниатюр.',
		);

		return $fields;
	}

	/**
	 * Add the grid badge to the attachment model used by the media library.
	 *
	 * @param array<string, mixed> $response   Attachment data sent to JavaScript.
	 * @param WP_Post              $attachment Attachment post.
	 *
	 * @return array<string, mixed>
	 */
	public static function add_js_data( array $response, WP_Post $attachment ): array {
		if ( ! wp_attachment_is_image( $attachment ) ) {
			return $response;
		}

		$response['hsImageSavings'] = self::badge( self::attachment_stats( (int) $attachment->ID ) );
		return $response;
	}

	/**
	 * Add badge styles and the badge painter to the media library screen.
	 *
	 * @param string $hook_suffix Current admin page.
	 */
	public static function enqueue_assets( string $hook_suffix ): void {
		if ( 'upload.php' !== $hook_suffix ) {
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

    // The media grid mutates the DOM constantly (uploads, scrolling, search).
    // Coalesce every burst of changes into one repaint per animation frame.
    var paintScheduled = false;
    function schedulePaint() {
        if (paintScheduled) {
            return;
        }
        paintScheduled = true;
        (window.requestAnimationFrame || window.setTimeout)(function () {
            paintScheduled = false;
            paintSavingsBadges();
        });
    }

    var startObserver = function () {
        if (!document.body) {
            return;
        }
        if (window.MutationObserver) {
            new MutationObserver(schedulePaint).observe(document.body, {childList: true, subtree: true});
        } else if (window.jQuery) {
            window.jQuery(document).ajaxComplete(schedulePaint);
        }
        schedulePaint();
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

	/**
	 * Describe the best variant of the primary image for the grid badge.
	 *
	 * @param array<string, mixed> $stats Attachment statistics.
	 *
	 * @return array{label: string, title: string, available: bool}
	 */
	public static function badge( array $stats ): array {
		if ( 'available' !== ( $stats['status'] ?? '' ) ) {
			return array(
				'label'     => 'Нет данных',
				'title'     => 'Статистика сжатия недоступна',
				'available' => false,
			);
		}

		$primary              = isset( $stats['primary'] ) && is_array( $stats['primary'] ) ? $stats['primary'] : $stats;
		list( $format, $key ) = self::preferred_variant( $primary );
		if ( '' !== $key ) {
			return array(
				'label'     => $format . ' ' . self::bytes( (int) $primary[ $key . '_bytes' ], 0 ) . ' · −' . self::percent( (float) $primary[ $key . '_saved_percent' ] ),
				'title'     => self::variant_title( $format, $primary, $key ),
				'available' => true,
			);
		}

		$imagify = $stats['imagify'] ?? array();
		if ( is_array( $imagify ) && (float) ( $imagify['percent'] ?? 0 ) > 0 ) {
			return array(
				'label'     => 'Imagify −' . self::percent( (float) $imagify['percent'] ),
				'title'     => 'Экономия по сохранённой статистике Imagify',
				'available' => true,
			);
		}

		return array(
			'label'     => 'Без sidecar',
			'title'     => 'WebP/AVIF ещё не созданы',
			'available' => false,
		);
	}

	/**
	 * Measure the attachment files and their sidecars and store the result.
	 * The optimizer calls this whenever it finishes an attachment.
	 *
	 * @param int $attachment_id Attachment post ID.
	 *
	 * @return array<string, mixed>
	 */
	public static function refresh_stats( int $attachment_id ): array {
		$attached_file = get_attached_file( $attachment_id, true );
		$metadata      = wp_get_attachment_metadata( $attachment_id );
		$files         = is_string( $attached_file )
			? HS_Local_Image_Attachment_Files::collect( $attached_file, is_array( $metadata ) ? $metadata : array() )
			: array();

		$stats            = HS_Local_Image_Admin_Stats::calculate( $files );
		$stats['primary'] = HS_Local_Image_Admin_Stats::calculate( is_string( $attached_file ) ? array( $attached_file ) : array() );

		if ( 'available' === $stats['status'] ) {
			update_post_meta( $attachment_id, self::STATS_META, $stats );
		}
		unset( self::$cache[ $attachment_id ] );

		return $stats;
	}

	/**
	 * Resolve the statistics shown for one attachment.
	 *
	 * @param int $attachment_id Attachment post ID.
	 *
	 * @return array<string, mixed>
	 */
	private static function attachment_stats( int $attachment_id ): array {
		if ( isset( self::$cache[ $attachment_id ] ) ) {
			return self::$cache[ $attachment_id ];
		}

		$stats = get_post_meta( $attachment_id, self::STATS_META, true );
		if ( ! is_array( $stats ) || 'available' !== ( $stats['status'] ?? '' ) ) {
			// Attachments from before the stored sizes are measured once.
			$stats = self::refresh_stats( $attachment_id );
		}

		$imagify_data  = get_post_meta( $attachment_id, '_imagify_data', true );
		$imagify_stats = is_array( $imagify_data ) && isset( $imagify_data['stats'] ) && is_array( $imagify_data['stats'] )
			? $imagify_data['stats']
			: array();
		if ( (float) ( $imagify_stats['percent'] ?? 0 ) > 0 ) {
			$stats['imagify'] = array(
				'original_size'  => max( 0, (int) ( $imagify_stats['original_size'] ?? 0 ) ),
				'optimized_size' => max( 0, (int) ( $imagify_stats['optimized_size'] ?? 0 ) ),
				'percent'        => round( (float) $imagify_stats['percent'], 1 ),
			);
		}

		self::$cache[ $attachment_id ] = $stats;
		return $stats;
	}

	/**
	 * Render the savings breakdown as escaped HTML.
	 *
	 * @param array<string, mixed> $stats   Attachment statistics.
	 * @param bool                 $compact Whether to omit the explanatory note.
	 */
	private static function render( array $stats, bool $compact = false ): string {
		if ( 'available' !== ( $stats['status'] ?? '' ) ) {
			return '<span class="hs-image-savings hs-image-savings--empty">Нет данных</span>';
		}

		$primary              = isset( $stats['primary'] ) && is_array( $stats['primary'] ) ? $stats['primary'] : $stats;
		list( $format, $key ) = self::preferred_variant( $primary );
		$lines                = array();

		if ( '' !== $key ) {
			$lines[] = '<div class="hs-image-savings__headline"><strong>Посетитель скачает:</strong> '
				. esc_html( $format ) . ' ' . esc_html( self::bytes( (int) $primary[ $key . '_bytes' ], 1 ) )
				. ' <span class="hs-image-savings__saving">(−'
				. esc_html( self::percent( (float) $primary[ $key . '_saved_percent' ] ) ) . ')</span></div>';
			$lines[] = '<div class="hs-image-savings__meta">Оригинал сохранён: '
				. esc_html( self::bytes( (int) $primary['source_bytes'], 1 ) ) . '</div>';
		} else {
			$lines[] = '<div class="hs-image-savings__headline"><strong>Посетитель скачает оригинал:</strong> '
				. esc_html( self::bytes( (int) $primary['source_bytes'], 1 ) ) . '</div>';
			$lines[] = '<div class="hs-image-savings__meta">WebP/AVIF ещё не созданы</div>';
		}

		$aggregate_format = $key;
		if ( '' === $aggregate_format && (int) $stats['webp_files'] > 0 ) {
			$aggregate_format = 'webp';
		}
		if ( '' !== $aggregate_format ) {
			$lines[] = '<div class="hs-image-savings__aggregate"><strong>Все размеры WordPress ('
				. esc_html( (string) $stats['files'] ) . ' файлов):</strong><br>'
				. esc_html( self::bytes( (int) $stats['source_bytes'], 1 ) ) . ' → '
				. esc_html( self::bytes( (int) $stats[ $aggregate_format . '_bytes' ], 1 ) )
				. ' через ' . esc_html( strtoupper( $aggregate_format ) ) . ' · −'
				. esc_html( self::percent( (float) $stats[ $aggregate_format . '_saved_percent' ] ) ) . '</div>';
		}

		if ( ! $compact ) {
			$lines[] = '<div class="hs-image-savings__note">Большой оригинал остаётся на диске как резервный; современный браузер получает WebP/AVIF.</div>';
		}
		if ( isset( $stats['imagify'] ) && is_array( $stats['imagify'] ) ) {
			$imagify = $stats['imagify'];
			$lines[] = '<div class="hs-image-savings__history"><strong>История Imagify:</strong> −' . esc_html( self::percent( (float) $imagify['percent'] ) )
				. ' (' . esc_html( self::bytes( (int) $imagify['original_size'], 1 ) ) . ' → '
				. esc_html( self::bytes( (int) $imagify['optimized_size'], 1 ) ) . ')</div>';
		}

		return '<div class="hs-image-savings">' . implode( '', $lines ) . '</div>';
	}

	/**
	 * Pick the variant a modern browser receives: AVIF, then WebP.
	 *
	 * @param array<string, mixed> $stats Statistics of the primary image.
	 *
	 * @return array{0: string, 1: string} Display label and statistics key prefix.
	 */
	private static function preferred_variant( array $stats ): array {
		if ( (int) ( $stats['avif_files'] ?? 0 ) > 0 ) {
			return array( 'AVIF', 'avif' );
		}
		if ( (int) ( $stats['webp_files'] ?? 0 ) > 0 ) {
			return array( 'WebP', 'webp' );
		}
		return array( '', '' );
	}

	/**
	 * Build the badge tooltip of one variant.
	 *
	 * @param string               $label Variant display label.
	 * @param array<string, mixed> $stats Statistics of the primary image.
	 * @param string               $key   Statistics key prefix.
	 */
	private static function variant_title( string $label, array $stats, string $key ): string {
		return $label . ': ' . self::bytes( (int) $stats['source_bytes'], 1 ) . ' → '
			. self::bytes( (int) $stats[ $key . '_bytes' ], 1 )
			. ', сэкономлено ' . self::bytes( (int) $stats[ $key . '_saved_bytes' ], 1 );
	}

	/**
	 * Format a byte count for display.
	 *
	 * @param int $bytes    File size in bytes.
	 * @param int $decimals Decimal places.
	 */
	private static function bytes( int $bytes, int $decimals ): string {
		$formatted = size_format( $bytes, $decimals );
		return false === $formatted ? '0 B' : $formatted;
	}

	/**
	 * Format a saving percentage.
	 *
	 * @param float $value Percentage.
	 */
	private static function percent( float $value ): string {
		return number_format_i18n( max( 0, $value ), 1 ) . '%';
	}
}
