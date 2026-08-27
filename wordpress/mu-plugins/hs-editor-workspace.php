<?php
/**
 * HS Editor Workspace — рабочее место автора на экране записи.
 *
 * Фаза 1. Убирает лишние панели из-под редактора и опускает SEO вниз.
 * Фаза 2. Собирает три панели HS Tooltip в одну липкую панель с вкладками.
 *
 * Ничего не удаляется безвозвратно: каждая скрытая панель возвращается
 * галочкой в «Настройках экрана», данные записей не затрагиваются.
 *
 * Раскатка постепенная — список учёток в hs_ew_rollout_user_ids().
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Учётки, на которые раскатано рабочее место.
 *
 * Фаза 4: добавить сюда 49 (ArsenalFK).
 */
function hs_ew_rollout_user_ids(): array {
	return [
		1,  // ArdasmanLL
		48, // alexey_03
	];
}

function hs_ew_is_enabled(): bool {
	$user_id = get_current_user_id();

	return $user_id > 0 && in_array( $user_id, hs_ew_rollout_user_ids(), true );
}

/**
 * Типы записей, где работает рабочее место.
 */
function hs_ew_post_types(): array {
	return [ 'post', 'page' ];
}

/**
 * Панели, скрытые по умолчанию.
 *
 * Это именно скрытие, а не удаление: галочка в «Настройках экрана» остаётся
 * и возвращает панель на место.
 */
function hs_ew_hidden_boxes(): array {
	return [
		'postexcerpt',               // Отрывок
		'slugdiv',                   // Ярлык
		'trackbacksdiv',             // Отправить обратные ссылки
		'postcustom',                // Произвольные поля
		'commentsdiv',               // Комментарии
		'commentstatusdiv',          // Обсуждение
		'members-cp',                // Content Permissions (Members)
		'perfmatters',               // Perfmatters
		'rocket_post_exclude',       // WP Rocket Options
		'compatibility_fixture_bad', // Bad Fixture (wp-manacost-decks_old)
	];
}

/**
 * Дефолтный набор скрытых панелей.
 */
add_filter(
	'default_hidden_meta_boxes',
	static function ( $hidden, $screen ) {
		if ( ! hs_ew_is_enabled() || ! $screen instanceof WP_Screen ) {
			return $hidden;
		}

		if ( ! in_array( $screen->id, hs_ew_post_types(), true ) ) {
			return $hidden;
		}

		return array_values( array_unique( array_merge( (array) $hidden, hs_ew_hidden_boxes() ) ) );
	},
	10,
	2
);

/**
 * AIOSEO по умолчанию встаёт сразу под редактором (normal/high) — опускаем вниз.
 *
 * Фильтр предусмотрен самим плагином, файл AIOSEO не трогаем.
 */
add_filter(
	'aioseo_post_metabox_priority',
	static function ( $priority ) {
		return hs_ew_is_enabled() ? 'low' : $priority;
	},
	99
);

/**
 * hs-admin-load-trim.php вырезает метабокс WP Rocket целиком, из-за чего его
 * нельзя вернуть галочкой. Снимаем вырезку — панель будет просто скрыта.
 */
add_action(
	'add_meta_boxes',
	static function ( $post_type ) {
		if ( ! hs_ew_is_enabled() || ! in_array( $post_type, hs_ew_post_types(), true ) ) {
			return;
		}

		remove_all_filters( 'rocket_metabox_options_post_types' );
	},
	1
);

/**
 * Перестановка и слияние панелей.
 *
 * Вешаемся на add_meta_boxes_{$post_type}, а не на общий add_meta_boxes:
 * он срабатывает последним, и только там видны панели, зарегистрированные
 * именно на нём — например, Manacost SEO Addon.
 */
foreach ( hs_ew_post_types() as $hs_ew_post_type ) {
	add_action( 'add_meta_boxes_' . $hs_ew_post_type, 'hs_ew_rearrange_boxes', 9999 );
}
unset( $hs_ew_post_type );

function hs_ew_rearrange_boxes( $post ): void {
	if ( ! hs_ew_is_enabled() || ! $post instanceof WP_Post ) {
		return;
	}

	$post_type = $post->post_type;

	if ( ! in_array( $post_type, hs_ew_post_types(), true ) ) {
		return;
	}

	hs_ew_move_meta_box( $post_type, 'manacost-aioseo-addon', 'normal', 'low' );
	hs_ew_merge_tooltip_boxes( $post_type );
}

/**
 * Переносит метабокс в другой контекст/приоритет, не трогая его callback.
 */
function hs_ew_move_meta_box( string $screen, string $box_id, string $to_context, string $to_priority ): bool {
	global $wp_meta_boxes;

	if ( empty( $wp_meta_boxes[ $screen ] ) || ! is_array( $wp_meta_boxes[ $screen ] ) ) {
		return false;
	}

	foreach ( $wp_meta_boxes[ $screen ] as $context => $priorities ) {
		foreach ( (array) $priorities as $priority => $boxes ) {
			if ( empty( $boxes[ $box_id ] ) ) {
				continue;
			}

			$box = $boxes[ $box_id ];
			unset( $wp_meta_boxes[ $screen ][ $context ][ $priority ][ $box_id ] );
			$wp_meta_boxes[ $screen ][ $to_context ][ $to_priority ][ $box_id ] = $box;

			return true;
		}
	}

	return false;
}

/**
 * Три панели HS Tooltip и функции их отрисовки.
 */
function hs_ew_tooltip_parts(): array {
	return [
		'hs_tooltip_search'     => 'hs_smart_tooltip_render_search_box',
		'hs_tooltip_bgs_search' => 'hs_smart_tooltip_render_bgs_search_box',
		'hs_tooltip_toggle'     => 'hs_smart_tooltip_render_toggle_box',
	];
}

/**
 * Плагин активен и его функции отрисовки на месте.
 *
 * Если плагин выключат или переименуют функции — экран остаётся как был.
 */
function hs_ew_tooltip_available(): bool {
	foreach ( hs_ew_tooltip_parts() as $render_function ) {
		if ( ! function_exists( $render_function ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Схлопывает три панели HS Tooltip в одну.
 *
 * Разметку не переписываем — зовём те же функции отрисовки, поэтому ID полей
 * (#hs-tooltip-search и прочие) сохраняются и hs-tooltip-admin.js продолжает
 * работать без изменений.
 */
function hs_ew_merge_tooltip_boxes( string $screen ): void {
	if ( ! hs_ew_tooltip_available() ) {
		return;
	}

	foreach ( array_keys( hs_ew_tooltip_parts() ) as $box_id ) {
		remove_meta_box( $box_id, $screen, 'side' );
	}

	add_meta_box(
		'hs_tooltip_workspace',
		'HS Tooltip — вставка карт',
		'hs_ew_render_tooltip_workspace',
		$screen,
		'side',
		'high'
	);
}

function hs_ew_render_tooltip_workspace( WP_Post $post ): void {
	if ( ! hs_ew_tooltip_available() ) {
		return;
	}
	?>
	<div class="hs-ew-tabs" data-hs-ew-tabs>
		<div class="hs-ew-tabbar" role="tablist">
			<button type="button" class="hs-ew-tab is-active" data-hs-ew-tab="cards" role="tab" aria-selected="true">
				<?php esc_html_e( 'Карты', 'hs-smart-tooltip' ); ?>
			</button>
			<button type="button" class="hs-ew-tab" data-hs-ew-tab="bgs" role="tab" aria-selected="false">
				<?php esc_html_e( 'Battlegrounds', 'hs-smart-tooltip' ); ?>
			</button>

			<?php /* Без name — это настройка интерфейса, в форму записи не уходит. */ ?>
			<label class="hs-ew-pin" title="Панель останется на экране при прокрутке">
				<input type="checkbox" class="hs-ew-pin-input" checked>
				<span><?php esc_html_e( 'Закрепить', 'hs-smart-tooltip' ); ?></span>
			</label>
		</div>

		<div class="hs-ew-panel is-active" data-hs-ew-panel="cards">
			<?php hs_smart_tooltip_render_search_box( $post ); ?>
		</div>

		<div class="hs-ew-panel" data-hs-ew-panel="bgs">
			<?php hs_smart_tooltip_render_bgs_search_box( $post ); ?>
		</div>

		<div class="hs-ew-extra">
			<?php hs_smart_tooltip_render_toggle_box( $post ); ?>
		</div>
	</div>
	<?php
}

/**
 * Стили и скрипт — только на экране редактирования записи.
 */
add_action(
	'admin_enqueue_scripts',
	static function ( $hook ) {
		if ( ! hs_ew_is_enabled() || ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
			return;
		}

		$assets = [
			'css' => __DIR__ . '/hs-editor-workspace/workspace.css',
			'js'  => __DIR__ . '/hs-editor-workspace/workspace.js',
		];

		if ( file_exists( $assets['css'] ) ) {
			wp_enqueue_style(
				'hs-editor-workspace',
				plugins_url( 'hs-editor-workspace/workspace.css', __FILE__ ),
				[],
				(string) filemtime( $assets['css'] )
			);
		}

		if ( file_exists( $assets['js'] ) ) {
			wp_enqueue_script(
				'hs-editor-workspace',
				plugins_url( 'hs-editor-workspace/workspace.js', __FILE__ ),
				[],
				(string) filemtime( $assets['js'] ),
				true
			);
		}
	}
);
