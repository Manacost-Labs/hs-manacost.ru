/* global wp, hsApiGalleryEditor */
(function () {
  'use strict';
  const { __, sprintf } = wp.i18n;
  const config = hsApiGalleryEditor;
  const dialog = document.getElementById('hs-api-gallery-dialog');
  if (!dialog) return;
  const byId = id => document.getElementById(`hs-api-gallery-${id}`);
  const selected = new Map();
  const imported = new Map();
  const cache = new Map();
  let rendered = new Map();
  const previewSources = new WeakMap();
  const previews = 'IntersectionObserver' in window ? new IntersectionObserver(entries => {
    for (const entry of entries) {
      if (entry.isIntersecting && entry.target.isConnected) {
        entry.target.src = previewSources.get(entry.target); previews.unobserve(entry.target);
      }
    }
  }, { root: dialog.querySelector('.hs-api-gallery__catalog'), rootMargin: '160px 0px' }) : null;
  let catalog = { items: [], page: 0, next: true };
  let generation = 0;
  let session = 0;
  let loading = false;
  let searchRequested = false;
  let importing = false;
  let limit = 80;
  const labels = {
    card: __('Карта', 'manacost'), hero: __('Герой', 'manacost'), static: __('Облик', 'manacost'),
    diamond: __('Алмазная', 'manacost'), art: __('Иллюстрация', 'manacost'), full_art: __('Полная иллюстрация', 'manacost'),
    golden: __('Золотая', 'manacost'), signature: __('Особая', 'manacost'), framed: __('С рамкой', 'manacost'),
    horizontal: __('Горизонтальная иллюстрация', 'manacost'), crop: __('Фрагмент', 'manacost'), wiki: __('Изображение Wiki', 'manacost'),
  };
  const category = () => `${byId('library').value}:${byId('format').value}`;
  const itemKey = (library, item, variant) => `${library}:${item.id}:${variant}`;
  const status = text => { byId('status').textContent = text; };

  function preview(image, url, eager = false) {
    previewSources.set(image, url);
    if (previews && !eager) previews.observe(image);
    else image.src = url;
  }

  async function request(action, values) {
    const response = await fetch(config.url, {
      method: 'POST', credentials: 'same-origin',
      body: new URLSearchParams({ action, nonce: config.nonce, post_id: config.postId, ...values }),
      signal: AbortSignal.timeout(45000),
    });
    let json;
    try { json = await response.json(); } catch { throw new Error(__('Запрос не завершился. Повторите попытку.', 'manacost')); }
    if (!response.ok || !json.success) throw new Error(json.data?.message || __('Ошибка загрузки.', 'manacost'));
    return json.data;
  }

  function updateSelection() {
    byId('count').textContent = sprintf(__('Выбрано: %d из 40', 'manacost'), selected.size);
    byId('create').disabled = !selected.size || importing;
    byId('clear').disabled = !selected.size || importing;
    byId('empty').hidden = Boolean(selected.size);
    byId('selected').replaceChildren();
    for (const [key, item] of selected) {
      const remove = document.createElement('button');
      remove.type = 'button'; remove.className = 'button'; remove.disabled = importing;
      const thumbnail = document.createElement('img'); thumbnail.src = item.images[item.variant]; thumbnail.alt = '';
      const name = document.createElement('span'); name.textContent = `${item.name} · ${labels[item.variant]}`;
      const cross = document.createElement('span'); cross.textContent = '×'; cross.setAttribute('aria-hidden', 'true');
      remove.append(thumbnail, name, cross);
      remove.setAttribute('aria-label', sprintf(__('Убрать: %s', 'manacost'), `${item.name} · ${labels[item.variant]}`));
      remove.onclick = () => { selected.delete(key); render(); (byId('selected').querySelector('button') || byId('query')).focus(); };
      byId('selected').append(remove);
    }
  }

  function card(item, eager) {
    const library = byId('library').value;
    const node = document.createElement('div'); node.className = 'hs-api-gallery__item';
    const image = document.createElement('img'); image.alt = ''; image.decoding = 'async';
    const picture = document.createElement('span'); picture.className = 'hs-api-gallery__preview'; picture.append(image);
    let retry;
    image.onerror = () => {
      image.hidden = true;
      const message = document.createElement('span'); message.textContent = __('Изображение недоступно', 'manacost');
      if (!picture.querySelector('span')) picture.append(message);
      if (!retry) {
        retry = document.createElement('button'); retry.type = 'button'; retry.className = 'button hs-api-gallery__retry';
        retry.textContent = __('Повторить загрузку', 'manacost');
        retry.setAttribute('aria-label', sprintf(__('Повторить загрузку: %s', 'manacost'), item.name));
        retry.onclick = () => { image.hidden = false; picture.querySelector('span')?.remove(); retry.hidden = true; image.removeAttribute('src'); image.src = previewSources.get(image); };
        node.append(retry);
      }
      retry.disabled = importing;
      retry.hidden = false;
    };
    image.onload = () => { image.hidden = false; picture.querySelector('span')?.remove(); if (retry) retry.hidden = true; };
    const choice = document.createElement('select');
    choice.setAttribute('aria-label', sprintf(__('Изображение: %s', 'manacost'), item.name));
    for (const variant of Object.keys(item.images)) choice.add(new Option(labels[variant] || variant, variant));
    const current = [...selected.values()].find(s => s.library === library && s.id === item.id);
    if (current) choice.value = current.variant;
    preview(image, item.images[choice.value], eager);
    const label = document.createElement('label'); label.className = 'hs-api-gallery__choice';
    const check = document.createElement('input'); check.type = 'checkbox';
    check.checked = selected.has(itemKey(library, item, choice.value)); check.disabled = importing;
    const name = document.createElement('span'); name.className = 'hs-api-gallery__name';
    name.append(check, document.createTextNode(item.name)); label.append(picture, name);
    node.classList.toggle('is-selected', check.checked); choice.disabled = importing;
    check.onchange = () => {
      const key = itemKey(library, item, choice.value);
      if (check.checked && selected.size >= 40) { check.checked = false; status(__('Можно выбрать до 40 изображений за один раз.', 'manacost')); return; }
      if (check.checked) selected.set(key, { ...item, library, variant: choice.value });
      else selected.delete(key);
      node.classList.toggle('is-selected', check.checked);
      updateSelection();
    };
    choice.onchange = () => { preview(image, item.images[choice.value], true); check.checked = selected.has(itemKey(library, item, choice.value)); node.classList.toggle('is-selected', check.checked); };
    if (choice.options.length > 1) node.append(label, choice);
    else {
      const variant = document.createElement('span'); variant.className = 'hs-api-gallery__variant'; variant.textContent = labels[choice.value] || choice.value;
      node.append(label, variant);
    }
    return {node, check, choice, image, library, item};
  }

  function filtered() {
    const query = byId('query').value.trim().toLocaleLowerCase();
    return catalog.items.filter(item => !query || `${item.name} ${item.id}`.toLocaleLowerCase().includes(query));
  }

  function render() {
    const items = filtered();
    const next = new Map();
    const nodes = items.slice(0, limit).map((item, index) => {
      const entry = rendered.get(item) || card(item, index < 4);
      entry.check.checked = selected.has(itemKey(entry.library, item, entry.choice.value));
      entry.check.disabled = importing; entry.choice.disabled = importing;
      const retry = entry.node.querySelector('.hs-api-gallery__retry'); if (retry) retry.disabled = importing;
      entry.node.classList.toggle('is-selected', entry.check.checked);
      next.set(item, entry); return entry.node;
    });
    for (const [item, entry] of rendered) { if (!next.has(item) && previews) previews.unobserve(entry.image); }
    const results = byId('results');
    if (results.children.length !== nodes.length || nodes.some((node, index) => results.children[index] !== node)) results.replaceChildren(...nodes);
    rendered = next;
    byId('more').hidden = loading || (!catalog.next && items.length <= limit);
    updateSelection();
  }

  async function nextPage(token) {
    const data = await request('hs_api_gallery_catalog', { library: byId('library').value, format: byId('format').value, page: catalog.page + 1 });
    if (token !== generation || !dialog.open) return false;
    catalog.items.push(...data.items); catalog.page += 1; catalog.next = data.has_next && catalog.page < 200;
    catalog.stale = catalog.stale || data.stale;
    cache.set(category(), catalog);
    return true;
  }

  async function load(search = false) {
    if (importing) return;
    searchRequested = search;
    if (loading) return;
    const token = generation;
    loading = true; render();
    try {
      do {
        status(sprintf(__('Загрузка библиотеки… Получено объектов: %d', 'manacost'), catalog.items.length));
        if (!await nextPage(token)) return;
        render();
      } while (searchRequested && byId('query').value.trim() && catalog.next && token === generation);
      status(catalog.stale ? __('API недоступен: показана сохранённая копия библиотеки.', 'manacost') :
        (filtered().length ? sprintf(__('Найдено: %d', 'manacost'), filtered().length) : __('По вашему запросу ничего не найдено.', 'manacost')));
    } catch (error) {
      if (token === generation) status(error.message);
    } finally {
      if (token === generation) { loading = false; render(); }
    }
  }

  function switchCategory() {
    generation += 1; loading = false; limit = 80;
    catalog = cache.get(category()) || { items: [], page: 0, next: true };
    byId('format-label').hidden = byId('library').value !== 'constructed-cards';
    dialog.classList.toggle('has-format', !byId('format-label').hidden);
    render();
    if (!catalog.page) load(Boolean(byId('query').value.trim()));
    else status(sprintf(__('Загружено: %d', 'manacost'), catalog.items.length));
  }

  byId('open').onclick = () => {
    session += 1; selected.clear(); byId('query').value = ''; limit = 80;
    dialog.showModal(); switchCategory(); byId('query').focus();
  };
  byId('close').onclick = () => dialog.close();
  dialog.addEventListener('close', () => { session += 1; generation += 1; loading = false; });
  byId('clear').onclick = () => { selected.clear(); render(); byId('query').focus(); };
  byId('library').onchange = switchCategory;
  byId('format').onchange = switchCategory;
  byId('search').onsubmit = event => {
    event.preventDefault(); limit = 80; render();
    if (catalog.next) load(true);
    else status(filtered().length ? sprintf(__('Найдено: %d', 'manacost'), filtered().length) : __('По вашему запросу ничего не найдено.', 'manacost'));
  };
  byId('more').onclick = () => { limit += 80; if (filtered().length >= limit || !catalog.next) render(); else load(); };
  byId('create').onclick = async () => {
    const items = [...selected.entries()];
    if (!items.length || importing) return;
    const token = session;
    importing = true; generation += 1; loading = false; render();
    byId('library').disabled = true; byId('format').disabled = true;
    const ids = [];
    try {
      let next = 0, completed = 0, failure;
      const progress = () => status(sprintf(__('Сохраняем изображения в медиатеку: %1$d из %2$d…', 'manacost'), completed, items.length));
      progress();
      const upload = async () => {
        while (next < items.length && dialog.open && token === session && !failure) {
          const index = next++, [key, item] = items[index];
          try {
            if (!imported.has(key)) {
              const data = await request('hs_api_gallery_import', { library: item.library, object_id: item.id, variant: item.variant });
              imported.set(key, data.attachment_id);
            }
            ids[index] = imported.get(key); completed += 1;
            if (dialog.open && token === session) progress();
          } catch (error) { failure ||= error; }
        }
      };
      // Bound server/image-processing work; drain in-flight writes before retrying.
      await Promise.all(Array.from({ length: Math.min(2, items.length) }, upload));
      if (failure) throw failure;
      if (!dialog.open || token !== session) return;
      const ratings = byId('ratings').checked ? '1' : '0';
      dialog.close();
      const frame = wp.media.gallery.edit(`[gallery ids="${ids.join(',')}" columns="3" size="medium" link="file" hs_ratings="${ratings}"]`);
      frame.state('gallery-edit').on('update', selection => {
        wp.media.editor.insert(wp.media.gallery.shortcode(selection).string());
        selected.clear(); updateSelection();
      });
    } catch (error) { if (token === session) status(`${error.message} ${__('Выбор сохранён — можно повторить загрузку.', 'manacost')}`); }
    finally { importing = false; byId('library').disabled = false; byId('format').disabled = false; render(); }
  };

  // Keep the custom attribute through WordPress/TinyMCE's normal gallery editor.
  wp.media.gallery.defaults.hs_ratings = '0';
  const Base = wp.media.view.Settings.Gallery;
  wp.media.view.Settings.Gallery = Base.extend({
    events: { ...Base.prototype.events, 'change .hs-gallery-ratings-toggle': 'toggleRatings', 'change [data-setting="td_select_gallery_slide"]': 'galleryType' },
    render() {
      Base.prototype.render.apply(this, arguments);
      const label = document.createElement('label'); label.className = 'setting';
      const input = document.createElement('input'); input.type = 'checkbox'; input.className = 'hs-gallery-ratings-toggle';
      input.checked = String(this.model.get('hs_ratings')) === '1';
      label.append(input, document.createTextNode(__('Оценки читателей под изображениями (обычная галерея WordPress)', 'manacost')));
      this.el.append(label);
      return this;
    },
    toggleRatings(event) {
      this.model.set('hs_ratings', event.target.checked ? '1' : '0');
      if (event.target.checked) this.model.set('td_select_gallery_slide', '');
    },
    galleryType(event) {
      if (event.target.value === 'slide') {
        this.model.set('hs_ratings', '0');
        this.el.querySelector('.hs-gallery-ratings-toggle').checked = false;
      }
    },
  });
  updateSelection();
}());
