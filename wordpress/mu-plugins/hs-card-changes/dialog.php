<?php
/**
 * Native modal composer. All controls are separate from the post save form.
 *
 * @package HSManacost
 */

defined( 'ABSPATH' ) || exit;
?>
<dialog id="hs-card-changes-dialog" aria-labelledby="hs-cc-title">
	<div class="hs-ccd-header">
		<h2 id="hs-cc-title"><?php esc_html_e( 'Изменения карт', 'hs-card-changes' ); ?></h2>
		<button type="button" class="button" id="hs-cc-close"><?php esc_html_e( 'Закрыть', 'hs-card-changes' ); ?></button>
	</div>
	<div class="hs-ccd-body">
		<section class="hs-ccd-find" aria-labelledby="hs-cc-find-title">
			<h3 id="hs-cc-find-title"><?php esc_html_e( 'Найти карту', 'hs-card-changes' ); ?></h3>
			<label for="hs-cc-search"><?php esc_html_e( 'Название по-русски', 'hs-card-changes' ); ?>
				<input id="hs-cc-search" type="search" maxlength="60" autocomplete="off" placeholder="<?php esc_attr_e( 'Например, Матриарх', 'hs-card-changes' ); ?>" />
			</label>
			<label for="hs-cc-kind"><?php esc_html_e( 'Где искать', 'hs-card-changes' ); ?>
				<select id="hs-cc-kind">
					<option value="all"><?php esc_html_e( 'Constructed и Поля сражений', 'hs-card-changes' ); ?></option>
					<option value="constructed"><?php esc_html_e( 'Только Constructed', 'hs-card-changes' ); ?></option>
					<option value="bg"><?php esc_html_e( 'Только Поля сражений', 'hs-card-changes' ); ?></option>
				</select>
			</label>
			<label for="hs-cc-id"><?php esc_html_e( 'Не нашли? Идентификатор карты', 'hs-card-changes' ); ?>
				<input id="hs-cc-id" maxlength="120" autocomplete="off" placeholder="BG36_362" />
			</label>
			<button type="button" class="button" id="hs-cc-add-id"><?php esc_html_e( 'Добавить по идентификатору', 'hs-card-changes' ); ?></button>
			<label for="hs-cc-date"><?php esc_html_e( 'Дата и время патча (UTC)', 'hs-card-changes' ); ?>
				<input id="hs-cc-date" type="datetime-local" />
			</label>
			<p class="description"><?php esc_html_e( 'По умолчанию — дата публикации статьи. До — последняя версия карты до этого момента, После — первая версия с другим изображением. История ведётся с 11 октября 2026.', 'hs-card-changes' ); ?></p>
			<p id="hs-cc-status" role="status" aria-live="polite"></p>
			<ul id="hs-cc-results" class="hs-ccd-results"></ul>
		</section>
		<section class="hs-ccd-items" aria-labelledby="hs-cc-items-title">
			<h3 id="hs-cc-items-title"><?php esc_html_e( 'Изменения в статье', 'hs-card-changes' ); ?></h3>
			<p id="hs-cc-items-status" role="status" aria-live="polite"></p>
			<ol id="hs-cc-items" class="hs-ccd-list"></ol>
			<div class="hs-ccd-layout">
				<label for="hs-cc-columns"><?php esc_html_e( 'В ряд', 'hs-card-changes' ); ?>
					<select id="hs-cc-columns"><option value="1"><?php esc_html_e( '1 — по одному в ряд', 'hs-card-changes' ); ?></option><option value="2" selected><?php esc_html_e( '2 в ряд', 'hs-card-changes' ); ?></option><option value="3"><?php esc_html_e( '3 в ряд', 'hs-card-changes' ); ?></option></select>
				</label>
				<label for="hs-cc-size"><?php esc_html_e( 'Размер карт', 'hs-card-changes' ); ?>
					<select id="hs-cc-size"><option value="s"><?php esc_html_e( 'Маленькие', 'hs-card-changes' ); ?></option><option value="m" selected><?php esc_html_e( 'Средние', 'hs-card-changes' ); ?></option><option value="l"><?php esc_html_e( 'Крупные', 'hs-card-changes' ); ?></option></select>
				</label>
				<label for="hs-cc-captions" class="hs-ccd-check"><input id="hs-cc-captions" type="checkbox" /> <?php esc_html_e( 'Подписи «До» и «После»', 'hs-card-changes' ); ?></label>
			</div>
			<p class="description"><?php esc_html_e( 'На экранах до 720 px карты всегда идут по одной в ряд. Клик по изменению в статье откроет «До» и «После» рядом.', 'hs-card-changes' ); ?></p>
		</section>
		<section class="hs-ccd-preview" aria-labelledby="hs-cc-preview-title">
			<h3 id="hs-cc-preview-title"><?php esc_html_e( 'Предпросмотр', 'hs-card-changes' ); ?></h3>
			<div id="hs-cc-fit"><div id="hs-cc-preview"></div></div>
		</section>
	</div>
	<div class="hs-ccd-footer">
		<span><?php esc_html_e( 'Вставится шорткод в выбранное место текста.', 'hs-card-changes' ); ?></span>
		<button type="button" class="button button-primary" id="hs-cc-insert" disabled><?php esc_html_e( 'Вставить изменения', 'hs-card-changes' ); ?></button>
	</div>
</dialog>
