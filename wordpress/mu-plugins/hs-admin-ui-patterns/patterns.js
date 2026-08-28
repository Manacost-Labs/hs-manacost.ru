(() => {
  'use strict';

  const root = document.querySelector('.hs-ui-patterns');
  if (!root) return;

  const dialog = root.querySelector('.hs-ui-dialog');
  const openDialog = root.querySelector('.hs-ui-open-dialog');
  const cancelDialog = root.querySelector('.hs-ui-cancel-dialog');
  if (dialog instanceof HTMLDialogElement && openDialog instanceof HTMLButtonElement) {
    openDialog.addEventListener('click', () => dialog.showModal());
    dialog.addEventListener('close', () => openDialog.focus());
    cancelDialog?.addEventListener('click', () => dialog.close());
  }

  const saveButton = root.querySelector('.hs-ui-save-demo');
  const saveStatus = root.querySelector('.hs-ui-save-status');
  if (saveButton instanceof HTMLButtonElement && saveStatus instanceof HTMLElement) {
    saveButton.addEventListener('click', () => {
      saveButton.disabled = true;
      saveButton.setAttribute('aria-busy', 'true');
      saveStatus.textContent = 'Сохраняем…';
      window.setTimeout(() => {
        saveButton.disabled = false;
        saveButton.removeAttribute('aria-busy');
        saveStatus.textContent = 'Изменения сохранены';
      }, 600);
    });
  }
})();
