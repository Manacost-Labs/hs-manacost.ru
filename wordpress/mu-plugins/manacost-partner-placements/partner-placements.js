(() => {
  'use strict';

  const initialize = root => {
    const items = [...root.querySelectorAll('.site-partnership__item')];
    const pause = root.querySelector('.site-partnership__pause');
    const next = root.querySelector('.site-partnership__next');
    const position = root.querySelector('.site-partnership__position');
    if (items.length < 2 || !pause || !next || !position) return;

    const motion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const states = items.map(() => 'pending');
    let active = 0;
    let paused = motion.matches;
    let hovered = false;
    let timer;

    const ready = () => items.map((_, index) => index).filter(index => states[index] === 'ready');
    const schedule = () => {
      window.clearTimeout(timer);
      if (paused || hovered || document.hidden || root.contains(document.activeElement) || ready().length < 2) return;
      timer = window.setTimeout(() => advance(false), 6000);
    };
    const select = index => {
      active = index;
      items.forEach((item, current) => item.classList.toggle('is-active', current === index));
      position.textContent = `${index + 1} / ${items.length}`;
      schedule();
    };
    const advance = manual => {
      const candidates = ready();
      // Manual navigation also keeps a blocked partner's plain link reachable.
      const choices = manual ? items.map((_, index) => index) : candidates;
      const current = choices.indexOf(active);
      select(choices[(current + 1) % choices.length] ?? active);
    };
    const updatePause = () => {
      pause.textContent = paused ? 'Пуск' : 'Пауза';
      pause.setAttribute('aria-pressed', String(paused));
      pause.setAttribute('aria-label', paused ? 'Возобновить смену баннеров' : 'Приостановить смену баннеров');
      schedule();
    };

    // Enhance only after the complete controller is available; without JS the
    // fixed layers keep their geometry; noscript links and focus work without JS.
    select(active);
    root.setAttribute('data-enhanced', '');
    items.forEach((item, index) => {
      const image = item.querySelector('img');
      if (!image) return;
      const settle = () => {
        states[index] = image.naturalWidth > 0 ? 'ready' : 'failed';
        image.toggleAttribute('data-failed', states[index] === 'failed');
        const available = ready();
        if (states[active] !== 'ready' && available.length && !root.contains(document.activeElement)) {
          select(available[0]);
        } else {
          schedule();
        }
      };
      image.addEventListener('load', settle);
      image.addEventListener('error', settle);
      if (image.complete) settle();
      item.addEventListener('focus', () => select(index));
    });
    next.addEventListener('click', () => advance(true));
    pause.addEventListener('click', () => {
      paused = !paused;
      updatePause();
    });
    root.addEventListener('pointerenter', event => {
      if (event.pointerType === 'touch') return;
      hovered = true;
      schedule();
    });
    root.addEventListener('pointerleave', () => {
      hovered = false;
      schedule();
    });
    root.addEventListener('focusin', schedule);
    root.addEventListener('focusout', () => window.setTimeout(schedule, 0));
    document.addEventListener('visibilitychange', schedule);
    motion.addEventListener('change', () => {
      paused = motion.matches;
      updatePause();
    });
    updatePause();
  };

  document.querySelectorAll('.site-partnership').forEach(initialize);
})();
