(() => {
  'use strict';

  const initialize = root => {
    const items = [...root.querySelectorAll('.site-partnership__item')];
    if (items.length < 2) return;

    const focusedPartner = [...root.querySelectorAll('.site-partnership__links a')].indexOf(document.activeElement);
    const motion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const states = items.map(() => 'pending');
    let active = 0;
    let hovered = false;
    let timer;

    const ready = () => items.map((_, index) => index).filter(index => states[index] === 'ready');
    const schedule = () => {
      window.clearTimeout(timer);
      if (motion.matches || hovered || document.hidden || root.contains(document.activeElement) || ready().length < 2) return;
      timer = window.setTimeout(() => advance(), 6000);
    };
    const select = index => {
      active = index;
      items.forEach((item, current) => item.classList.toggle('is-active', current === index));
      schedule();
    };
    const advance = () => {
      const choices = ready();
      const current = choices.indexOf(active);
      select(choices[(current + 1) % choices.length] ?? active);
    };

    // Enhance only after the complete controller is available; without JS the
    // fixed layers keep their geometry; plain links work if the script is unavailable.
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
    motion.addEventListener('change', schedule);
    if (focusedPartner >= 0) {
      select(focusedPartner);
      items[focusedPartner].focus({ preventScroll: true });
    }
    schedule();
  };

  document.querySelectorAll('.site-partnership').forEach(initialize);
})();
