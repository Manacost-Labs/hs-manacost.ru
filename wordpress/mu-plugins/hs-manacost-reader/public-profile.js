(() => {
  'use strict';
  const root = document.querySelector('[data-mc-public-profile]');
  const id = root?.dataset.readerId;
  if (!root || !/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i.test(id)) return;
  const $ = selector => root.querySelector(selector);
  const status = $('[data-public-profile-status]'), content = $('[data-public-profile-content]');
  const image = $('[data-public-profile-avatar]');
  const placeholder = $('[data-public-profile-placeholder]');
  const socials = $('[data-public-profile-socials]');
  const socialLinks = [
    { node: $('[data-public-profile-twitch]'), service: 'twitch' },
    { node: $('[data-public-profile-youtube]'), service: 'youtube' },
  ];
  const classes = { 'death-knight': 'Рыцарь смерти', 'demon-hunter': 'Охотник на демонов', druid: 'Друид', hunter: 'Охотник', mage: 'Маг', paladin: 'Паладин', priest: 'Жрец', rogue: 'Разбойник', shaman: 'Шаман', warlock: 'Чернокнижник', warrior: 'Воин' };
  let controller = null, generation = 0;
  function safeSocialUrl(value, service) {
    if (value === null || value === undefined) return null;
    if (typeof value !== 'string' || value.length > 200 || /[\u0000-\u001f\u007f]/.test(value)) return null;
    let url; try { url = new URL(value); } catch { return null; }
    if (url.protocol !== 'https:' || url.username || url.password || url.port || url.search || url.hash) return null;
    const host = url.hostname.toLowerCase();
    if (service === 'twitch') {
      if (!/^(?:www\.)?twitch\.tv$/i.test(host)) return null;
      const match = url.pathname.match(/^\/([a-z0-9_]{4,25})\/?$/i);
      return match ? `https://www.twitch.tv/${match[1].toLowerCase()}` : null;
    }
    if (!/^(?:www\.|m\.)?youtube\.com$/i.test(host)) return null;
    const handle = url.pathname.match(/^\/@([a-z0-9_.-]{3,30})$/i);
    if (handle) return `https://www.youtube.com/@${handle[1]}`;
    const channel = url.pathname.match(/^\/channel\/(UC[a-z0-9_-]{22})$/i);
    return channel ? `https://www.youtube.com/channel/${channel[1]}` : null;
  }
  function clear() {
    content.hidden = true; image.hidden = true; image.removeAttribute('src');
    placeholder.textContent = 'М'; placeholder.hidden = false;
    for (const selector of ['[data-public-profile-name]', '[data-public-profile-bio]', '[data-public-profile-class]']) $(selector).textContent = '';
    $('[data-public-profile-paid]').hidden = true;
    socials.hidden = true;
    for (const social of socialLinks) { social.node.hidden = true; social.node.removeAttribute('href'); }
  }
  async function load() {
    controller?.abort(); const ticket = ++generation;
    const request = new AbortController(); controller = request;
    const deadline = setTimeout(() => request.abort(), 7000);
    clear(); status.textContent = 'Загружаем профиль…';
    try {
      const response = await fetch(`/reader-api/v1/readers/${id}`, { credentials: 'same-origin', cache: 'no-store', signal: request.signal });
      const data = await response.json(); if (ticket !== generation) return;
      if (response.status === 503) throw new Error('unavailable');
      const profile = data?.profile;
      if (!response.ok || profile?.id !== id || profile.profileUrl !== `/account/?reader=${id}`
        || typeof profile.name !== 'string' || profile.name.length > 160 || typeof profile.bio !== 'string'
        || Array.from(profile.bio).length > 280 || typeof profile.paidSubscriber !== 'boolean') throw new Error('not_found');
      $('[data-public-profile-name]').textContent = profile.name;
      placeholder.textContent = Array.from(profile.name)[0] || 'М';
      $('[data-public-profile-bio]').textContent = profile.bio;
      $('[data-public-profile-class]').textContent = classes[profile.favoriteClass] ? `Любимый класс: ${classes[profile.favoriteClass]}` : '';

      let socialCount = 0;
      for (const social of socialLinks) {
        const href = safeSocialUrl(profile[`${social.service}Url`], social.service);
        social.node.hidden = !href;
        if (href) { social.node.href = href; socialCount += 1; }
        else social.node.removeAttribute('href');
      }
      socials.hidden = socialCount === 0;
      if (typeof profile.avatarVersion === 'string' && /^[A-Za-z0-9_-]{32}$/.test(profile.avatarVersion)
        && profile.avatarUrl === `/reader-api/v1/readers/${id}/avatar?v=${profile.avatarVersion}`) {
        image.src = profile.avatarUrl; image.hidden = false; placeholder.hidden = true;
      }
      $('[data-public-profile-paid]').hidden = profile.paidSubscriber !== true;
      status.textContent = ''; content.hidden = false;
    } catch (error) {
      if (ticket !== generation) return;
      clear(); status.textContent = error.message === 'unavailable' ? 'Сервис профилей временно недоступен. Повторите попытку позже.' : 'Профиль недоступен.';
    } finally { clearTimeout(deadline); if (ticket === generation) controller = null; }
  }
  image.addEventListener('error', () => { image.hidden = true; image.removeAttribute('src'); placeholder.hidden = false; });
  addEventListener('pagehide', () => { generation++; controller?.abort(); clear(); });
  addEventListener('pageshow', event => { if (event.persisted) void load(); });
  void load();
})();
