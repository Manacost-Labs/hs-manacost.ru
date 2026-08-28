<?php
/**
 * Admin UI pattern showcase implementation.
 *
 * @package HS_Manacost
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register and render the staging-only pattern library.
 */
final class HS_Admin_UI_Patterns {
	private const VERSION = '1.0.0';

	/**
	 * Hook suffix of the registered showcase screen.
	 *
	 * @var string
	 */
	private static string $hook_suffix = '';

	/**
	 * Whether the showcase may be exposed in this environment.
	 */
	private static function is_available(): bool {
		return in_array( wp_get_environment_type(), array( 'local', 'staging' ), true );
	}

	/**
	 * Register the showcase below Tools.
	 *
	 * @return void
	 */
	public static function register_menu(): void {
		if ( ! self::is_available() ) {
			return;
		}

		self::$hook_suffix = (string) add_management_page(
			__( 'UI-паттерны Manacost', 'hs-manacost' ),
			__( 'UI-паттерны', 'hs-manacost' ),
			'manage_options',
			'hs-admin-ui-patterns',
			array( self::class, 'render' )
		);
	}

	/**
	 * Load assets only on the owned showcase screen.
	 *
	 * @param string $hook_suffix Current admin screen hook.
	 * @return void
	 */
	public static function enqueue_assets( string $hook_suffix ): void {
		if ( '' === self::$hook_suffix || self::$hook_suffix !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'hs-admin-ui-patterns',
			plugins_url( 'patterns.css', __FILE__ ),
			array(),
			self::VERSION
		);
		wp_enqueue_script(
			'hs-admin-ui-patterns',
			plugins_url( 'patterns.js', __FILE__ ),
			array(),
			self::VERSION,
			true
		);
	}

	/**
	 * Render static, non-destructive examples of the approved patterns.
	 *
	 * @return void
	 */
	public static function render(): void {
		if ( ! self::is_available() || ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'У вас нет доступа к библиотеке интерфейсных паттернов.', 'hs-manacost' ), '', array( 'response' => 403 ) );
		}
		?>
		<div class="wrap hs-ui-patterns">
			<header class="hs-ui-page-header">
				<div>
					<h1><?php esc_html_e( 'UI-паттерны Manacost', 'hs-manacost' ); ?></h1>
					<p class="hs-ui-lead"><?php esc_html_e( 'Эталон состояний, форм и списков для собственных экранов WordPress.', 'hs-manacost' ); ?></p>
				</div>
				<button type="button" class="button button-primary"><?php esc_html_e( 'Основное действие', 'hs-manacost' ); ?></button>
			</header>

			<div class="notice notice-info inline hs-ui-notice" role="status">
				<p><strong><?php esc_html_e( 'Тестовый экран.', 'hs-manacost' ); ?></strong> <?php esc_html_e( 'Он доступен только на local и staging и ничего не записывает.', 'hs-manacost' ); ?></p>
			</div>

			<section class="hs-ui-section" aria-labelledby="hs-ui-form-title">
				<div class="hs-ui-section-heading">
					<div>
						<h2 id="hs-ui-form-title"><?php esc_html_e( 'Форма и обратная связь', 'hs-manacost' ); ?></h2>
						<p><?php esc_html_e( 'Видимая подпись, подсказка, ошибка рядом с полем и постоянный статус сохранения.', 'hs-manacost' ); ?></p>
					</div>
					<span class="hs-ui-status hs-ui-status--draft"><?php esc_html_e( 'Черновик', 'hs-manacost' ); ?></span>
				</div>
				<div class="hs-ui-form-grid">
					<div class="hs-ui-field">
						<label for="hs-ui-title"><strong><?php esc_html_e( 'Название материала', 'hs-manacost' ); ?></strong></label>
						<input id="hs-ui-title" class="regular-text" type="text" value="Гайд по новой мете" aria-describedby="hs-ui-title-help">
						<p id="hs-ui-title-help" class="description"><?php esc_html_e( 'Короткое и понятное название без служебных пометок.', 'hs-manacost' ); ?></p>
					</div>
					<div class="hs-ui-field hs-ui-field--error">
						<label for="hs-ui-link"><strong><?php esc_html_e( 'Ссылка на источник', 'hs-manacost' ); ?></strong></label>
						<input id="hs-ui-link" class="regular-text" type="url" value="example" aria-invalid="true" aria-describedby="hs-ui-link-error">
						<p id="hs-ui-link-error" class="hs-ui-field-error"><?php esc_html_e( 'Укажите полную ссылку, начинающуюся с https://.', 'hs-manacost' ); ?></p>
					</div>
				</div>
				<div class="hs-ui-actions">
					<button type="button" class="button button-primary hs-ui-save-demo"><?php esc_html_e( 'Сохранить изменения', 'hs-manacost' ); ?></button>
					<button type="button" class="button"><?php esc_html_e( 'Предпросмотр', 'hs-manacost' ); ?></button>
					<span class="hs-ui-save-status" role="status" aria-live="polite"><?php esc_html_e( 'Изменений нет', 'hs-manacost' ); ?></span>
				</div>
			</section>

			<section class="hs-ui-section" aria-labelledby="hs-ui-list-title">
				<div class="hs-ui-section-heading">
					<div>
						<h2 id="hs-ui-list-title"><?php esc_html_e( 'Фильтры и адаптивный список', 'hs-manacost' ); ?></h2>
						<p><?php esc_html_e( 'Фильтры остаются компактными, а на телефоне строки превращаются в читаемые блоки.', 'hs-manacost' ); ?></p>
					</div>
					<strong><?php esc_html_e( '24 результата', 'hs-manacost' ); ?></strong>
				</div>
				<div class="hs-ui-filters" role="group" aria-label="<?php esc_attr_e( 'Фильтры списка', 'hs-manacost' ); ?>">
					<label for="hs-ui-search" class="screen-reader-text"><?php esc_html_e( 'Поиск материалов', 'hs-manacost' ); ?></label>
					<input id="hs-ui-search" type="search" placeholder="<?php esc_attr_e( 'Поиск по названию', 'hs-manacost' ); ?>">
					<label for="hs-ui-state" class="screen-reader-text"><?php esc_html_e( 'Статус материала', 'hs-manacost' ); ?></label>
					<select id="hs-ui-state">
						<option><?php esc_html_e( 'Все статусы', 'hs-manacost' ); ?></option>
						<option><?php esc_html_e( 'Черновики', 'hs-manacost' ); ?></option>
						<option><?php esc_html_e( 'Опубликованные', 'hs-manacost' ); ?></option>
					</select>
					<button type="button" class="button"><?php esc_html_e( 'Применить', 'hs-manacost' ); ?></button>
					<button type="button" class="button-link"><?php esc_html_e( 'Сбросить', 'hs-manacost' ); ?></button>
				</div>
				<table class="widefat striped hs-ui-table">
					<caption class="screen-reader-text"><?php esc_html_e( 'Пример списка редакционных материалов', 'hs-manacost' ); ?></caption>
					<thead><tr><th scope="col"><?php esc_html_e( 'Материал', 'hs-manacost' ); ?></th><th scope="col"><?php esc_html_e( 'Статус', 'hs-manacost' ); ?></th><th scope="col"><?php esc_html_e( 'Автор', 'hs-manacost' ); ?></th><th scope="col"><?php esc_html_e( 'Обновлено', 'hs-manacost' ); ?></th></tr></thead>
					<tbody>
						<tr><td data-label="<?php esc_attr_e( 'Материал', 'hs-manacost' ); ?>"><a href="#"><strong><?php esc_html_e( 'Гайд по новой мете', 'hs-manacost' ); ?></strong></a></td><td data-label="<?php esc_attr_e( 'Статус', 'hs-manacost' ); ?>"><span class="hs-ui-status hs-ui-status--ready"><?php esc_html_e( 'Готово', 'hs-manacost' ); ?></span></td><td data-label="<?php esc_attr_e( 'Автор', 'hs-manacost' ); ?>"><?php esc_html_e( 'Редактор', 'hs-manacost' ); ?></td><td data-label="<?php esc_attr_e( 'Обновлено', 'hs-manacost' ); ?>"><?php esc_html_e( 'сегодня, 14:20', 'hs-manacost' ); ?></td></tr>
						<tr><td data-label="<?php esc_attr_e( 'Материал', 'hs-manacost' ); ?>"><a href="#"><strong><?php esc_html_e( 'Обзор обновления Hearthstone', 'hs-manacost' ); ?></strong></a></td><td data-label="<?php esc_attr_e( 'Статус', 'hs-manacost' ); ?>"><span class="hs-ui-status hs-ui-status--review"><?php esc_html_e( 'На проверке', 'hs-manacost' ); ?></span></td><td data-label="<?php esc_attr_e( 'Автор', 'hs-manacost' ); ?>"><?php esc_html_e( 'Автор', 'hs-manacost' ); ?></td><td data-label="<?php esc_attr_e( 'Обновлено', 'hs-manacost' ); ?>"><?php esc_html_e( 'вчера, 19:05', 'hs-manacost' ); ?></td></tr>
					</tbody>
				</table>
				<nav class="hs-ui-pagination" aria-label="<?php esc_attr_e( 'Страницы результатов', 'hs-manacost' ); ?>"><span aria-current="page">1</span><a href="#">2</a><a href="#"><?php esc_html_e( 'Следующая', 'hs-manacost' ); ?></a></nav>
			</section>

			<section class="hs-ui-section" aria-labelledby="hs-ui-states-title">
				<h2 id="hs-ui-states-title"><?php esc_html_e( 'Пустое и ошибочное состояния', 'hs-manacost' ); ?></h2>
				<div class="hs-ui-state-grid">
					<div class="hs-ui-state"><h3><?php esc_html_e( 'Материалов пока нет', 'hs-manacost' ); ?></h3><p><?php esc_html_e( 'Создайте первый материал — он появится в этом списке.', 'hs-manacost' ); ?></p><button type="button" class="button button-primary"><?php esc_html_e( 'Создать материал', 'hs-manacost' ); ?></button></div>
					<div class="hs-ui-state hs-ui-state--error"><h3><?php esc_html_e( 'Не удалось загрузить список', 'hs-manacost' ); ?></h3><p><?php esc_html_e( 'Проверьте соединение и повторите запрос. Введённые данные сохранены.', 'hs-manacost' ); ?></p><button type="button" class="button"><?php esc_html_e( 'Повторить', 'hs-manacost' ); ?></button></div>
				</div>
			</section>

			<section class="hs-ui-section" aria-labelledby="hs-ui-dialog-title">
				<h2 id="hs-ui-dialog-title"><?php esc_html_e( 'Подтверждение опасного действия', 'hs-manacost' ); ?></h2>
				<p><?php esc_html_e( 'Отмена остаётся безопасным действием по умолчанию, а последствие названо прямо.', 'hs-manacost' ); ?></p>
				<button type="button" class="button hs-ui-open-dialog"><?php esc_html_e( 'Показать подтверждение', 'hs-manacost' ); ?></button>
				<dialog class="hs-ui-dialog" aria-labelledby="hs-ui-dialog-heading" aria-describedby="hs-ui-dialog-description">
					<h2 id="hs-ui-dialog-heading"><?php esc_html_e( 'Удалить черновик «Гайд по новой мете»?', 'hs-manacost' ); ?></h2>
					<p id="hs-ui-dialog-description"><?php esc_html_e( 'Черновик переместится в корзину, откуда его можно восстановить.', 'hs-manacost' ); ?></p>
					<div class="hs-ui-dialog-actions"><button type="button" class="button hs-ui-cancel-dialog" autofocus><?php esc_html_e( 'Отмена', 'hs-manacost' ); ?></button><button type="button" class="button button-link-delete"><?php esc_html_e( 'Переместить в корзину', 'hs-manacost' ); ?></button></div>
				</dialog>
			</section>
		</div>
		<?php
	}
}

add_action( 'admin_menu', array( HS_Admin_UI_Patterns::class, 'register_menu' ) );
add_action( 'admin_enqueue_scripts', array( HS_Admin_UI_Patterns::class, 'enqueue_assets' ) );
