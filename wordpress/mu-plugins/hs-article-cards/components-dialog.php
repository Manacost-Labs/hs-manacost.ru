<?php
/**
 * Native article component composer. All form input stays in the editor.
 *
 * @package HSManacost
 */

defined( 'ABSPATH' ) || exit;
?>
<dialog id="hs-v-dialog" aria-labelledby="hs-v-heading">
	<form id="hs-v-form">
		<header class="hs-v-header"><h2 id="hs-v-heading">Компонент Manacost</h2><button type="button" class="button" id="hs-v-close" aria-label="Закрыть">×</button></header>
		<div class="hs-v-workspace">
			<div class="hs-v-fields">
				<label for="hs-v-type">Компонент</label>
				<select id="hs-v-type"><option value="alert">Примечание</option><option value="spoiler">Спойлер</option><option value="quote">Цитата</option><option value="tabs">Вкладки</option><option value="table">Таблица</option><option value="author">Об авторе · соцсеть</option><option value="divider">Разделитель</option><option value="icon-list">Список с иконками</option><option value="steps">Шаги</option><option value="callout">Акцентный блок с кнопкой</option></select>
				<div data-for="author" hidden>
					<label for="hs-v-author-name">Имя автора</label><input id="hs-v-author-name" maxlength="120">
					<label for="hs-v-author-role">Подпись под именем</label><input id="hs-v-author-role" maxlength="200" placeholder="Автор гайдов Hearthstone">
					<label for="hs-v-author-bio">Об авторе</label><textarea id="hs-v-author-bio" rows="4" maxlength="2000"></textarea>
					<label for="hs-v-avatar">Ссылка на фото (необязательно)</label><input id="hs-v-avatar" type="url" maxlength="2048" placeholder="https://…">
					<label for="hs-v-social-label">Текст кнопки</label><input id="hs-v-social-label" maxlength="80" value="Подписаться">
					<label for="hs-v-social-url">Ссылка на соцсеть</label><input id="hs-v-social-url" maxlength="2048" placeholder="https://t.me/…" inputmode="url">
					<p>Кнопка откроет указанную страницу в новой вкладке.</p>
				</div>
				<div data-for="alert spoiler divider icon-list steps callout"><label for="hs-v-title">Заголовок</label><input id="hs-v-title" maxlength="200" value="Обратите внимание"></div>
				<div data-for="alert callout"><label for="hs-v-tone">Цвет акцента</label><select id="hs-v-tone"><option value="info">Информация</option><option value="warning">Важно</option><option value="success">Совет</option></select></div>
				<div data-for="alert spoiler quote callout"><label for="hs-v-body">Текст</label><textarea id="hs-v-body" rows="5" maxlength="10000" placeholder="Напишите текст компонента"></textarea></div>
				<div data-for="quote"><label for="hs-v-author">Автор или источник</label><input id="hs-v-author" maxlength="200"><label for="hs-v-align">Расположение</label><select id="hs-v-align"><option value="start">Слева</option><option value="center">По центру</option></select></div>
				<div data-for="divider"><label for="hs-v-divider-style">Линия</label><select id="hs-v-divider-style"><option value="line">Тонкая</option><option value="dots">Пунктир</option><option value="ornament">С ромбом</option></select><p>Подпись необязательна — оставьте заголовок пустым для линии без текста.</p></div>
				<div data-for="icon-list steps"><label for="hs-v-items">Пункты — каждый с новой строки</label><textarea id="hs-v-items" rows="6" maxlength="10000" placeholder="Первый пункт&#10;Второй пункт"></textarea><p>От 1 до 20 пунктов. Заголовок необязателен.</p></div>
				<div data-for="icon-list"><label for="hs-v-icon">Иконка</label><select id="hs-v-icon"><option value="check">Галочка</option><option value="star">Звезда</option><option value="arrow">Стрелка</option></select></div>
				<div data-for="callout"><label for="hs-v-action-label">Текст кнопки</label><input id="hs-v-action-label" maxlength="120" value="Подробнее"><label for="hs-v-action-url">Ссылка кнопки</label><input id="hs-v-action-url" maxlength="2048" inputmode="url" placeholder="https://…"><label for="hs-v-action-target">Открывать ссылку</label><select id="hs-v-action-target"><option value="_blank">В новой вкладке</option><option value="_self">В текущей вкладке</option></select></div>
				<div data-for="tabs"><p>От 2 до 6 разделов. Каждый раздел содержит заголовок и текст.</p><div id="hs-v-sections"></div><button id="hs-v-add-section" type="button" class="button">Добавить вкладку</button></div>
				<div data-for="table"><label for="hs-v-caption">Название таблицы</label><input id="hs-v-caption" maxlength="200" value="Сравнение"><div class="hs-v-table-options"><label for="hs-v-rows">Строки с данными<select id="hs-v-rows">
				<?php
				for ( $hs_v_i = 1; $hs_v_i <= 12; ++$hs_v_i ) :
					?>
					<option><?php echo esc_html( (string) $hs_v_i ); ?></option><?php endfor; ?></select></label><label for="hs-v-cols">Столбцы<select id="hs-v-cols">
					<?php
					for ( $hs_v_i = 2; $hs_v_i <= 6; ++$hs_v_i ) :
						?>
					<option><?php echo esc_html( (string) $hs_v_i ); ?></option><?php endfor; ?></select></label></div><p>Первая строка — заголовки столбцов. При уменьшении размера скрытые ячейки сохраняются до закрытия окна.</p><div id="hs-v-cells" tabindex="0" role="region" aria-label="Ячейки таблицы"></div></div>
				<label for="hs-v-density">Отступы</label><select id="hs-v-density"><option value="normal">Обычные</option><option value="compact">Компактные</option></select>
				<label for="hs-v-width">Ширина</label><select id="hs-v-width"><option value="full">По ширине текста</option><option value="narrow">Узкий блок</option></select>
			</div>
			<section class="hs-v-preview-area" aria-label="Предпросмотр"><h3>Предпросмотр</h3><div id="hs-v-preview"></div><p class="description">Тема Manacost · текст сохраняется в статье. Для настройки вставленного блока выберите его в Visual и нажмите «Компонент Manacost». В Gutenberg нажмите «Настроить компонент».</p></section>
		</div>
		<footer class="hs-v-footer"><p id="hs-v-status" role="status" aria-live="polite"></p><button id="hs-v-reset" type="button" class="button">Сбросить оформление</button><button id="hs-v-insert" type="submit" class="button button-primary">Вставить компонент</button></footer>
	</form>
</dialog>
