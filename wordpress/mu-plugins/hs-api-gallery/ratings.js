/* global hsGalleryRatings, wp */
(function () {
  'use strict';
  const config = hsGalleryRatings;
  const { __, sprintf } = wp.i18n;
  const groups = new Map();
  const empty = { average: 0, count: 0, mine: 0 };
  const text = (node, value) => { node.querySelector('.hs-gallery-rating__summary').textContent = value; };
  function show(node, score) {
    node.dataset.mine = score.mine;
    node.querySelectorAll('[data-score]').forEach(button => {
      const value = Number(button.dataset.score);
      button.classList.toggle('is-filled', value <= score.mine);
      button.setAttribute('aria-pressed', String(value === score.mine));
      button.disabled = false;
    });
    node.querySelector('.hs-gallery-rating__remove').hidden = !score.mine;
    const average = Number(score.average).toLocaleString('ru-RU', { maximumFractionDigits: 2 });
    text(node, score.count ? `${average} / 5 · ${sprintf(__('Голосов: %d', 'manacost'), score.count)}${score.mine ? ` · ${sprintf(__('Ваша оценка: %d', 'manacost'), score.mine)}` : ''}` : __('Пока нет оценок', 'manacost'));
  }
  async function request(values) {
    const response = await fetch(config.url, { method: 'POST', credentials: 'same-origin', body: new URLSearchParams(values), signal: AbortSignal.timeout(15000) });
    let json;
    try { json = await response.json(); } catch { throw new Error(__('Не удалось загрузить оценки. Обновите страницу.', 'manacost')); }
    if (!response.ok || !json.success) throw new Error(json.data?.message || __('Не удалось сохранить оценку. Повторите попытку.', 'manacost'));
    return json.data;
  }
  document.querySelectorAll('.hs-gallery-rating').forEach(node => {
    const post = node.dataset.post;
    if (!groups.has(post)) groups.set(post, []);
    groups.get(post).push(node);
  });
  for (const [post, nodes] of groups) {
    if (config.preview) { nodes.forEach(node => text(node, __('Оценки станут доступны после публикации.', 'manacost'))); continue; }
    let nonce;
    request({ action: 'hs_api_gallery_state', post_id: post }).then(state => {
      nonce = state.nonce;
      nodes.forEach(node => show(node, state.scores[node.dataset.image] || empty));
    }).catch(error => nodes.forEach(node => text(node, error.message)));
    nodes.forEach(node => node.addEventListener('click', async event => {
      const button = event.target.closest('button');
      if (!button || button.disabled || !nonce || node.dataset.busy === '1') return;
      const related = nodes.filter(item => item.dataset.image === node.dataset.image);
      related.forEach(item => { item.dataset.busy = '1'; item.querySelectorAll('button').forEach(control => { control.disabled = true; }); });
      try {
        const score = await request({ action: 'hs_api_gallery_vote', post_id: post, image_id: node.dataset.image, score: button.dataset.score || '0', nonce });
        related.forEach(item => show(item, score));
      } catch (error) { related.forEach(item => text(item, error.message)); }
      finally { related.forEach(item => { delete item.dataset.busy; item.querySelectorAll('button').forEach(control => { control.disabled = false; }); }); }
    }));
  }
}());
