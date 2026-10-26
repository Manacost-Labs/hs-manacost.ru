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
				<select id="hs-ac-source"><option value="manacost" <?php selected( ! HS_Article_Cards_Catalog::is_remote( 'manacost' ) ); ?>>Manacost</option><option value="koloda" <?php selected( ! HS_Article_Cards_Catalog::is_remote( 'koloda' ) ); ?>>Koloda Hearthstone</option></select>
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
				<details id="hs-ac-recent" hidden><summary><?php esc_html_e( 'Недавно вставленные в этом редакторе', 'hs-article-cards' ); ?></summary><div id="hs-ac-recent-results"></div></details>
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
					<select id="hs-ac-layout"><option value="compact"><?php esc_html_e( 'Компактный', 'hs-article-cards' ); ?></option><option value="cover"><?php esc_html_e( 'Большая обложка', 'hs-article-cards' ); ?></option><option value="grid"><?php esc_html_e( 'Сетка статей', 'hs-article-cards' ); ?></option><option value="list"><?php esc_html_e( 'Список с миниатюрами', 'hs-article-cards' ); ?></option><option value="folded"><?php esc_html_e( 'Стопка карточек · Folded', 'hs-article-cards' ); ?></option></select>
				</label>
				<div class="hs-ac-settings">
					<?php
					$hs_ac_labels = array(
						'columns' => __( 'Колонки сетки', 'hs-article-cards' ),
						'image'   => __( 'Размер изображения', 'hs-article-cards' ),
						'spacing' => __( 'Отступы', 'hs-article-cards' ),
						'radius'  => __( 'Углы изображения', 'hs-article-cards' ),
						'date'    => __( 'Дата публикации', 'hs-article-cards' ),
						'width'   => __( 'Ширина блока', 'hs-article-cards' ),
					);
					foreach ( HS_Article_Cards_Render::presets() as $hs_ac_key => $hs_ac_choices ) :
						?>
						<label for="hs-ac-setting-<?php echo esc_attr( $hs_ac_key ); ?>"><?php echo esc_html( $hs_ac_labels[ $hs_ac_key ] ); ?>
							<select id="hs-ac-setting-<?php echo esc_attr( $hs_ac_key ); ?>">
								<?php foreach ( $hs_ac_choices as $hs_ac_value => $hs_ac_label ) : ?>
									<option value="<?php echo esc_attr( (string) $hs_ac_value ); ?>"><?php echo esc_html( $hs_ac_label ); ?></option>
								<?php endforeach; ?>
							</select>
						</label>
					<?php endforeach; ?>
				</div>
				<button type="button" class="button" id="hs-ac-reset-settings"><?php esc_html_e( 'Сбросить оформление', 'hs-article-cards' ); ?></button>
				<p class="description"><?php esc_html_e( 'На узком экране колонки складываются в один столбец.', 'hs-article-cards' ); ?></p>
				<h3 id="hs-ac-selection-title"><?php esc_html_e( 'Выбранные статьи', 'hs-article-cards' ); ?></h3>
				<p id="hs-ac-selection-status" role="status"></p>
				<ol id="hs-ac-selected" aria-labelledby="hs-ac-selection-title"></ol>
				<div id="hs-ac-preview"></div>
				<p class="description"><?php esc_html_e( 'Адрес сайта скрыт. Каждая карточка откроет статью в новой вкладке. Настройки применяются только к этой вставке.', 'hs-article-cards' ); ?></p>
			</section>
		</div>
	</div>
	<div class="hs-ac-footer">
		<span><?php esc_html_e( 'Подборка вставится в выбранное место текста.', 'hs-article-cards' ); ?></span>
		<button type="button" class="button button-primary" id="hs-ac-insert" disabled><?php esc_html_e( 'Вставить карточки', 'hs-article-cards' ); ?></button>
	</div>
</dialog>
