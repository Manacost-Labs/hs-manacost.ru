(() => {
  'use strict';
  const uuid = /^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;
  const kinds = Object.freeze([
    ['like', 'Нравится', 'M5 12.5h3v7H5zM10 19.5h6.5a2 2 0 0 0 2-1.6l1-5A2 2 0 0 0 17.5 10H14l.5-3.1A2.4 2.4 0 0 0 12.2 4L9.5 10v9.5Z'],
    ['thanks', 'Спасибо', 'M12 20S4 15 4 9.5a4.5 4.5 0 0 1 8-2.8 4.5 4.5 0 0 1 8 2.8C20 15 12 20 12 20Z'],
    ['fire', 'Огонь', 'M12 21c4 0 6.5-2.5 6.5-6.3 0-3-2-4.8-4.3-7.7-.1 2.4-1.1 3.9-2.7 4.9C12 8.5 9.5 7 8.3 5c-1.1 3-2.8 5.3-2.8 9.7C5.5 18.5 8 21 12 21Z'],
  ]);
  const element = (tag, className, text) => {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text !== undefined) node.textContent = text;
    return node;
  };
  const validReactions = reactions => Array.isArray(reactions) && reactions.length === 3
    && kinds.every(([kind]) => reactions.some(value => value?.kind === kind && Number.isSafeInteger(value.count)
      && value.count >= 0 && typeof value.selected === 'boolean'))
    && reactions.filter(value => value.selected).length <= 1;
  const validBan = ban => ban && (ban.id === null || uuid.test(ban.id)) && typeof ban.blocked === 'boolean'
    && Number.isSafeInteger(ban.version) && ban.version >= 0;
  const icon = path => {
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.classList.add('mc-comments__reaction-icon'); svg.setAttribute('viewBox', '0 0 24 24');
    svg.setAttribute('aria-hidden', 'true'); svg.setAttribute('focusable', 'false');
    const shape = document.createElementNS('http://www.w3.org/2000/svg', 'path'); shape.setAttribute('d', path);
    svg.append(shape); return svg;
  };

  function create(binding) {
    let session = null, checked = false, permissionPending = null;
    let moderator = false;
    let loaded = '', loading = null;
    const reacting = new Set(), generations = new Map(), moderating = new Set();
    const commentPath = id => '/reader-api/v1/comments/' + id;
    const adminPath = id => '/reader-api/v1/moderation/comments/' + id;
    const banPath = id => '/reader-api/v1/moderation/readers/' + id + '/ban';

    function clearAdmin() {
      moderator = false;
      binding.root.querySelectorAll('[data-community-admin]').forEach(node => node.remove());
    }
    function reset() {
      session = binding.sessionKey(); checked = false; permissionPending = null;
      loaded = ''; loading = null;
      reacting.clear(); generations.clear(); moderating.clear(); clearAdmin(); binding.setCommentingBlocked(false);
    }
    async function authority() {
      const ticket = binding.sessionKey();
      if (!binding.getMe()) { clearAdmin(); return false; }
      const { response, data } = await binding.request('/reader-api/v1/community/me');
      if (ticket !== binding.sessionKey()) throw new Error('stale');
      if (response.status === 401) { binding.expired(); throw new Error('stale'); }
      checked = true;
      if (!response.ok || typeof data?.canModerateComments !== 'boolean' || typeof data?.commentingBlocked !== 'boolean') {
        clearAdmin(); throw new Error();
      }
      moderator = data.canModerateComments;
      binding.setCommentingBlocked(data.commentingBlocked);
      if (!moderator) clearAdmin();
      return moderator;
    }
    async function requireModerator() {
      try { return await authority(); }
      catch (error) {
        if (error.message !== 'stale') binding.say('Не удалось проверить права администратора. Повторите попытку.');
        return false;
      }
    }
    function authError(response) {
      if (response.status === 401) { binding.expired(); return true; }
      if (response.status === 403) { clearAdmin(); binding.say('Права администратора больше недоступны.'); return true; }
      return false;
    }
    function reactionButton(item, kind, label, path) {
      const value = item.reactions.find(reaction => reaction.kind === kind);
      const button = element('button', 'mc-comments__reaction');
      button.type = 'button'; button.dataset.reaction = kind; button.disabled = reacting.has(item.id);
      button.setAttribute('aria-pressed', String(value.selected)); button.setAttribute('aria-label', label + ': ' + value.count);
      button.append(icon(path), element('span', '', label), element('span', 'mc-comments__reaction-count', String(value.count)));
      button.addEventListener('click', () => { void react(item, kind); });
      return button;
    }
    function reactionGroup(item) {
      if (!validReactions(item.reactions)) return null;
      const group = element('div', 'mc-comments__reactions'); group.setAttribute('role', 'group');
      group.setAttribute('aria-label', 'Реакции на комментарий');
      group.setAttribute('aria-busy', String(reacting.has(item.id)));
      group.append(...kinds.map(args => reactionButton(item, ...args)));
      return group;
    }
    function optimisticReactions(reactions, next) {
      const selected = reactions.find(value => value.selected)?.kind ?? null;
      return reactions.map(value => {
        if (next === null && value.kind === selected) return { ...value, selected: false, count: Math.max(0, value.count - 1) };
        if (next !== null && value.kind === next) return { ...value, selected: true, count: value.count + 1 };
        if (next !== null && value.selected) return { ...value, selected: false, count: Math.max(0, value.count - 1) };
        return { ...value };
      });
    }
    function patchReactions(id) {
      const item = binding.getRows().find(value => value.id === id);
      const node = binding.root.querySelector('[data-comment-id="' + id + '"]');
      if (!item || !node) return;
      const prior = node.querySelector('.mc-comments__reactions');
      const next = item.status === 'published' ? reactionGroup(item) : null;
      if (prior && next) prior.replaceWith(next);
      else if (prior) prior.remove();
      else if (next) node.insertBefore(next, node.querySelector('[data-community-admin]'));
    }
    function reactionItems(idle = false) {
      return binding.getRows().filter(item => uuid.test(item.id) && item.status === 'published'
        && validReactions(item.reactions) && (!idle || !reacting.has(item.id)));
    }
    const selection = (items = reactionItems()) => binding.sessionKey() + '|' + items.map(item => item.id + ':'
      + (item.reactions.find(value => value.selected)?.kind ?? '')).join(',');
    async function hydrateReactions() {
      if (!binding.getMe() || loading) return;
      const items = reactionItems(true);
      if (!items.length) return;
      const requested = selection(items);
      if (requested === loaded) return;
      const ticket = binding.sessionKey();
      let skipped = false;
      loading = (async () => {
        for (let offset = 0; offset < items.length; offset += 50) {
          const batch = items.slice(offset, offset + 50);
          const prior = new Map(batch.map(item => [item.id, generations.get(item.id) ?? 0]));
          const query = new URLSearchParams(batch.map(item => ['comment', item.id]));
          const { response, data } = await binding.request('/reader-api/v1/community/reactions?' + query);
          if (ticket !== binding.sessionKey()) throw new Error('stale');
          if (response.status === 401) { binding.expired(); throw new Error('stale'); }
          const ids = new Set();
          if (!response.ok || !Array.isArray(data?.items) || data.items.length !== batch.length
            || !data.items.every(item => uuid.test(item?.commentId) && batch.some(row => row.id === item.commentId)
              && !ids.has(item.commentId) && ids.add(item.commentId) && validReactions(item.reactions))) {
            throw new Error();
          }
          for (const item of data.items) {
            if (!reacting.has(item.commentId)
              && (generations.get(item.commentId) ?? 0) === prior.get(item.commentId)) {
              binding.updateReactions(item.commentId, item.reactions);
            } else skipped = true;
          }
        }
        if (!skipped) loaded = selection();
      })().catch(error => {
        if (error.message !== 'stale') loaded = '';
      }).finally(() => {
        loading = null;
        const latest = selection();
        if (ticket === binding.sessionKey() && latest !== loaded && (skipped || latest !== requested)) void hydrateReactions();
      });
    }
    async function react(item, kind) {
      if (!binding.getMe()) { binding.offerLogin(); return; }
      if (reacting.has(item.id) || !validReactions(item.reactions)) return;
      const ticket = binding.sessionKey();
      const reaction = item.reactions.some(value => value.kind === kind && value.selected) ? null : kind;
      const previous = item.reactions.map(value => ({ ...value }));
      generations.set(item.id, (generations.get(item.id) ?? 0) + 1);
      let saved = false;
      item.reactions = optimisticReactions(previous, reaction);
      reacting.add(item.id); patchReactions(item.id);
      try {
        const { response, data } = await binding.request(commentPath(item.id) + '/reaction', {
          method: 'PUT', headers: binding.write(), body: JSON.stringify({ reaction }),
        });
        if (response.status === 401) { binding.expired(); return; }
        if (!response.ok) {
          item.reactions = previous; patchReactions(item.id);
          binding.say(response.status === 429 ? 'Слишком много реакций. Подождите немного.'
            : response.status === 404 ? 'Комментарий больше недоступен.' : 'Не удалось сохранить реакцию. Попробуйте ещё раз.');
          return;
        }
        if (!validReactions(data?.reactions)) throw new Error();
        binding.updateReactions(item.id, data.reactions);
        saved = true;
        loaded = selection();
      } catch (error) {
        if (error.message !== 'stale') {
          item.reactions = previous; patchReactions(item.id);
          binding.say('Не удалось сохранить реакцию. Попробуйте ещё раз.');
        }
      }
      finally {
        if (ticket === binding.sessionKey()) {
          reacting.delete(item.id); patchReactions(item.id);
          if (!saved) { loaded = ''; void hydrateReactions(); }
          binding.root.querySelector('[data-comment-id="' + item.id + '"] [data-reaction="' + kind + '"]')?.focus({ preventScroll: true });
        }
      }
    }
    async function moderate(item, action) {
      if (moderating.has(item.id)) return;
      const ticket = binding.sessionKey();
      moderating.add(item.id); renderControls();
      try {
        if (!await requireModerator()) return;
        await action();
      } catch (error) { if (error.message !== 'stale') binding.say('Не удалось выполнить действие. Попробуйте ещё раз.'); }
      finally { if (ticket === binding.sessionKey()) { moderating.delete(item.id); renderControls(); } }
    }
    async function remove(item) {
      if (!confirm('Удалить комментарий? Это действие нельзя отменить.')) return;
      const { response, data } = await binding.request(adminPath(item.id), {
        method: 'DELETE', headers: binding.write(), body: JSON.stringify({ version: item.version }),
      });
      if (authError(response)) return;
      if (response.status === 409) { await binding.reload(); binding.say('Комментарий изменился. Список обновлён.'); return; }
      if (!response.ok || data?.id !== item.id || data.status !== 'deleted' || !Number.isSafeInteger(data.version)) throw new Error();
      binding.replaceComment(item.id, { status: 'deleted', body: null, author: null, version: data.version });
      binding.say('Комментарий удалён.');
    }
    async function ban(item) {
      let result = await binding.request(banPath(item.author.id));
      if (authError(result.response)) return;
      if (!result.response.ok || !validBan(result.data?.ban)) throw new Error();
      if (result.data.ban.blocked) { binding.say('У этого читателя уже отключены комментарии.'); return; }
      if (!confirm('Запретить ' + item.author.name + ' комментировать?')) return;
      result = await binding.request(banPath(item.author.id), {
        method: 'PUT', headers: binding.write(), body: JSON.stringify({ version: result.data.ban.version }),
      });
      if (authError(result.response)) return;
      if (result.response.status === 409) { binding.say('Статус изменился. Повторите действие, чтобы проверить его.'); return; }
      if (!result.response.ok || !validBan(result.data?.ban) || !result.data.ban.blocked) throw new Error();
      binding.say('Комментирование запрещено.');
    }
    function adminActions(item) {
      const details = element('details', 'mc-comments__moderation-actions'); details.dataset.communityAdmin = '';
      details.append(element('summary', '', 'Модерация'));
      const menu = element('div', 'mc-comments__moderation-menu');
      for (const [label, action] of [['Удалить комментарий', remove], ['Запретить комментировать', ban]]) {
        const button = element('button', 'mc-ui-button mc-ui-button--text', label);
        button.type = 'button'; button.disabled = moderating.has(item.id);
        button.addEventListener('click', () => { void moderate(item, () => action(item)); }); menu.append(button);
      }
      details.append(menu); return details;
    }
    function bansPanel() {
      const details = element('details', 'mc-comments__bans'); details.dataset.communityAdmin = '';
      const list = element('ul'), more = element('button', 'mc-ui-button mc-ui-button--secondary', 'Показать ещё блокировки');
      more.type = 'button'; more.hidden = true;
      details.append(element('summary', '', 'Заблокированные читатели'), list, more);
      let cursor = null, loading = false;
      async function load(append = false) {
        if (loading) return;
        loading = true; more.disabled = true;
        try {
          if (!await requireModerator() || !details.isConnected) return;
          const result = await binding.request('/reader-api/v1/moderation/bans' + (append && cursor ? '?cursor=' + cursor : ''));
          if (authError(result.response)) return;
          const data = result.data;
          if (!result.response.ok || !Array.isArray(data?.items) || data.items.length > 20
            || !data.items.every(item => validBan(item) && uuid.test(item.id) && item.blocked)
            || (data.nextCursor !== null && (!uuid.test(data.nextCursor) || data.nextCursor === cursor))) throw new Error();
          if (!append) list.replaceChildren();
          for (const item of data.items) list.append(banRow(item));
          cursor = data.nextCursor; more.hidden = !cursor;
          if (!list.children.length) list.append(element('li', '', 'Заблокированных читателей нет.'));
        } catch (error) {
          if (error.message !== 'stale') binding.say('Не удалось загрузить блокировки. Закройте и откройте список, чтобы повторить.');
        } finally { loading = false; more.disabled = false; }
      }
      function banRow(item) {
        const row = element('li');
        row.append(element('span', '', typeof item.profile?.name === 'string' ? item.profile.name : 'Читатель'));
        const button = element('button', 'mc-ui-button mc-ui-button--text', 'Разрешить комментировать'); button.type = 'button';
        button.addEventListener('click', async () => {
          if (button.disabled) return;
          button.disabled = true;
          try {
            if (!await requireModerator()) return;
            const result = await binding.request('/reader-api/v1/moderation/bans/' + item.id, {
              method: 'PUT', headers: binding.write(), body: JSON.stringify({ version: item.version }),
            });
            if (authError(result.response)) return;
            if (result.response.status === 409) { cursor = null; await load(); binding.say('Список обновлён. Повторите действие при необходимости.'); return; }
            if (!result.response.ok || !validBan(result.data?.ban) || result.data.ban.blocked) throw new Error();
            cursor = null; await load(); binding.say('Комментирование снова разрешено.');
          } catch (error) { if (error.message !== 'stale') binding.say('Не удалось снять запрет. Попробуйте ещё раз.'); }
          finally { button.disabled = false; }
        });
        row.append(button); return row;
      }
      more.addEventListener('click', () => { void load(true); });
      details.addEventListener('toggle', () => { if (details.open) { cursor = null; void load(); } });
      return details;
    }
    function renderControls() {
      binding.root.querySelectorAll('[data-comments-list] .mc-comments__reactions, [data-comments-list] [data-community-admin]').forEach(node => node.remove());
      for (const item of binding.getRows()) {
        if (!uuid.test(item.id) || item.status !== 'published') continue;
        const node = binding.root.querySelector('[data-comment-id="' + item.id + '"]'); if (!node) continue;
        const group = reactionGroup(item); if (group) node.append(group);
        if (moderator) node.append(adminActions(item));
      }
      if (moderator && !binding.root.querySelector('.mc-comments__bans')) binding.root.append(bansPanel());
    }
    function render() {
      if (session !== binding.sessionKey()) reset();
      renderControls();
      void hydrateReactions();
      if (!binding.getMe() || checked || permissionPending) return;
      const ticket = binding.sessionKey();
      permissionPending = authority().then(renderControls).catch(error => {
        if (error.message !== 'stale') binding.say('Настройки сообщества временно недоступны.');
      }).finally(() => { if (ticket === binding.sessionKey()) permissionPending = null; });
    }
    return { render, reset, patchReactions };
  }
  window.hsManacostReaderCommunity = Object.freeze({ create });
})();
