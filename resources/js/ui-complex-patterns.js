// K15: behaviors remain opt-in to the components and do not implement domain actions.
const commandShortcutOrigins = new WeakMap();
document.querySelectorAll('[data-ui-command-dialog]').forEach((dialog) => {
  const search = dialog.querySelector('[data-ui-command-search]');
  const items = [...dialog.querySelectorAll('[data-ui-command-item]')];
  const empty = dialog.querySelector('[data-ui-command-empty]');
  const links = () => items.filter((item) => !item.hidden).map((item) => item.querySelector('a')).filter(Boolean);
  const filter = () => {
    const query = search.value.trim().toLocaleLowerCase();
    items.forEach((item) => { item.hidden = !item.textContent.toLocaleLowerCase().includes(query); });
    empty.hidden = items.some((item) => !item.hidden);
  };
  search.addEventListener('input', filter);
  search.addEventListener('keydown', (event) => {
    if (event.key === 'ArrowDown') {
      const first = links()[0];
      if (first) { event.preventDefault(); first.focus(); }
    }
  });
  dialog.addEventListener('keydown', (event) => {
    // Native search inputs may consume Escape to clear their text instead of closing the dialog.
    if (event.key === 'Escape' && dialog.open) {
      event.preventDefault();
      event.stopPropagation();
      dialog.close();
      return;
    }
    const current = document.activeElement;
    if (!current?.closest?.('[data-ui-command-item]')) return;
    if (event.key !== 'ArrowDown' && event.key !== 'ArrowUp') return;
    event.preventDefault();
    const options = links();
    const index = options.indexOf(current);
    const next = index + (event.key === 'ArrowDown' ? 1 : -1);
    if (next < 0) search.focus();
    else (options[next] || options[0])?.focus();
  });
  dialog.addEventListener('click', (event) => {
    if (event.target.closest('[data-ui-command-item] a')) dialog.close();
  });
  dialog.addEventListener('close', () => {
    search.value = '';
    filter();
    const origin = commandShortcutOrigins.get(dialog);
    if (origin?.isConnected) origin.focus();
    commandShortcutOrigins.delete(dialog);
  });
});
document.addEventListener('click', (event) => {
  const trigger = event.target.closest('[data-ui-dialog-open]');
  if (!trigger) return;
  const dialog = document.getElementById(trigger.getAttribute('data-ui-dialog-open'));
  if (dialog?.matches('[data-ui-command-dialog]') && dialog.open) dialog.querySelector('[data-ui-command-search]')?.focus();
});
document.addEventListener('keydown', (event) => {
  if (event.key.toLowerCase() !== 'k' || !(event.ctrlKey || event.metaKey) || event.altKey || event.shiftKey) return;
  if (event.target.closest('input,textarea,select,[contenteditable="true"]')) return;
  const wrapper = document.querySelector('[data-ui-command-shortcut]');
  const dialog = wrapper?.querySelector('[data-ui-command-dialog]');
  if (!dialog || dialog.open) return;
  event.preventDefault();
  if (document.activeElement instanceof HTMLElement) commandShortcutOrigins.set(dialog, document.activeElement);
  dialog.showModal();
  dialog.querySelector('[data-ui-command-search]')?.focus();
});
document.querySelectorAll('[data-ui-data-grid]').forEach((grid) => {
  const all = grid.querySelector('[data-ui-grid-select-all]');
  const boxes = [...grid.querySelectorAll('[data-ui-grid-item]')];
  const counter = grid.querySelector('[data-ui-grid-count]');
  if (!all || !counter) return;
  const update = () => {
    const enabled = boxes.filter((box) => !box.disabled);
    const selected = enabled.filter((box) => box.checked).length;
    all.checked = enabled.length > 0 && selected === enabled.length;
    all.indeterminate = selected > 0 && selected < enabled.length;
    all.disabled = enabled.length === 0;
    counter.textContent = selected + ' ausgewählt';
  };
  all.addEventListener('change', () => { boxes.forEach((box) => { if (!box.disabled) box.checked = all.checked; }); update(); });
  boxes.forEach((box) => box.addEventListener('change', update));
  update();
});
document.querySelectorAll('[data-ui-confirm-flow]').forEach((form) => {
  const agree = form.querySelector('[data-ui-confirm-ack]');
  const phrase = form.querySelector('[data-ui-confirm-phrase]');
  const button = form.querySelector('[data-ui-confirm-submit]');
  const status = form.querySelector('[data-ui-confirm-status]');
  const actionable = form.hasAttribute('data-ui-confirm-submittable');
  const ready = () => agree.checked && (!phrase || phrase.value.trim() === phrase.dataset.uiConfirmExpect);
  const update = () => {
    const valid = ready();
    button.disabled = !valid || !actionable;
    status.textContent = valid
      ? (actionable ? 'Voraussetzungen erfüllt. Aktion kann bestätigt werden.' : 'Voraussetzungen erfüllt; Vorschau ohne ausführbare Aktion.')
      : 'Bestätigung noch nicht vollständig.';
  };
  agree.addEventListener('change', update);
  phrase?.addEventListener('input', update);
  form.addEventListener('submit', (event) => { if (!ready() || !actionable) event.preventDefault(); });
  update();
});
