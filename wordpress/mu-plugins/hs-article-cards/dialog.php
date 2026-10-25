<?php
/**
 * Native modal picker. All controls are separate from the post save form.
 *
 * @package HSManacost
 */

defined( 'ABSPATH' ) || exit;
?>
<dialog id="hs-article-cards-dialog" aria-labelledby="hs-ac-title">
	<div class="hs-ac-header">
		<h2 id="hs-ac-title"><?php esc_html_e( 'Вставить статью', 'hs-article-cards' ); ?></h2>
		<button type="button" class="button" id="hs-ac-close"><?php esc_html_e( 'Закрыть', 'hs-article-cards' ); ?></button>
	</div>
	<div class="hs-ac-body">
		<div class="hs-ac-filters">
			<label for="hs-ac-source"><?php esc_html_e( 'Источник', 'hs-article-cards' ); ?>
				<select id="hs-ac-source"><option value="manacost">Manacost</option><option value="koloda">Koloda Hearthstone</option></select>
			</label>
			<label for="hs-ac-category"><?php esc_html_e( 'Категория', 'hs-article-cards' ); ?>
				<select id="hs-ac-category"><option value="0"><?php esc_html_e( 'Все категории', 'hs-article-cards' ); ?></option></select>
			</label>
			<label for="hs-ac-search"><?php esc_html_e( 'Поиск статей', 'hs-article-cards' ); ?>
				<input id="hs-ac-search" type="search" maxlength="100" placeholder="<?php esc_attr_e( 'Название или ключевые слова', 'hs-article-cards' ); ?>" />
			</label>
		</div>
		<button type="button" class="button" id="hs-ac-more-categories" hidden><?php esc_html_e( 'Ещё категории', 'hs-article-cards' ); ?></button>
		<p id="hs-ac-category-status" role="status"></p>
		<p id="hs-ac-status" role="status" aria-live="polite"></p>
		<button type="button" class="button" id="hs-ac-retry" hidden><?php esc_html_e( 'Повторить поиск', 'hs-article-cards' ); ?></button>
		<div class="hs-ac-columns">
			<section aria-label="<?php esc_attr_e( 'Результаты поиска', 'hs-article-cards' ); ?>">
				<div id="hs-ac-results"></div>
				<nav class="hs-ac-pagination" aria-label="<?php esc_attr_e( 'Страницы результатов', 'hs-article-cards' ); ?>">
					<button type="button" class="button" id="hs-ac-prev"><?php esc_html_e( 'Назад', 'hs-article-cards' ); ?></button>
					<span id="hs-ac-page"></span>
					<button type="button" class="button" id="hs-ac-next"><?php esc_html_e( 'Далее', 'hs-article-cards' ); ?></button>
				</nav>
			</section>
			<section class="hs-ac-preview-panel" aria-labelledby="hs-ac-preview-title">
				<h3 id="hs-ac-preview-title"><?php esc_html_e( 'Предпросмотр', 'hs-article-cards' ); ?></h3>
				<label for="hs-ac-layout"><?php esc_html_e( 'Вид карточки', 'hs-article-cards' ); ?>
					<select id="hs-ac-layout"><option value="compact"><?php esc_html_e( 'Компактный', 'hs-article-cards' ); ?></option><option value="cover"><?php esc_html_e( 'Большая обложка', 'hs-article-cards' ); ?></option></select>
				</label>
				<div id="hs-ac-preview"></div>
				<p class="description"><?php esc_html_e( 'Картинка, название и дата. Адрес сайта скрыт; карточка откроет статью в новой вкладке.', 'hs-article-cards' ); ?></p>
			</section>
		</div>
	</div>
	<div class="hs-ac-footer">
		<span><?php esc_html_e( 'Карточка вставится в выбранное место текста.', 'hs-article-cards' ); ?></span>
		<button type="button" class="button button-primary" id="hs-ac-insert" disabled><?php esc_html_e( 'Вставить карточку', 'hs-article-cards' ); ?></button>
	</div>
</dialog>
