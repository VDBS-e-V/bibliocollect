/**
 * K17 progressive enhancement: form-field rows and local transfer selections only.
 * No requests, storage, authorizations, imports or batch jobs.
 */
document.querySelectorAll('[data-ui-repeater]').forEach((root) => {
  const rows = root.querySelector('[data-ui-repeater-rows]');
  const template = root.querySelector('[data-ui-repeater-template]');
  const add = root.querySelector('[data-ui-repeater-add]');
  const empty = root.querySelector('[data-ui-repeater-empty]');
  const status = root.querySelector('[data-ui-repeater-status]');
  if (!rows || !template || !add || !status) return;
  const min = Math.max(0, Number(root.dataset.uiRepeaterMin) || 0);
  const max = Math.max(min, Number(root.dataset.uiRepeaterMax) || 0);
  let nextIndex = Math.max(0, Number(root.dataset.uiRepeaterNext) || 0);
  const entries = () => [...rows.querySelectorAll('[data-ui-repeater-row]')];
  const update = () => {
    const count = entries().length;
    add.disabled = count >= max;
    root.querySelectorAll('[data-ui-repeater-remove]').forEach((button) => {
      if (button.closest('template')) return;
      button.disabled = count <= min;
    });
    empty.hidden = count !== 0;
    status.textContent = count + (count === 1 ? ' Eintrag' : ' Einträge');
    entries().forEach((row, index) => {
      const label = row.querySelector('[data-ui-repeater-number], .bc-repeater-field__row-label');
      if (label) label.textContent = 'Eintrag ' + (index + 1);
      const remove = row.querySelector('[data-ui-repeater-remove]');
      if (remove) remove.setAttribute('aria-label', 'Eintrag ' + (index + 1) + ' entfernen');
    });
  };
  add.addEventListener('click', () => {
    if (entries().length >= max) return;
    const clone = template.content.cloneNode(true);
    clone.querySelectorAll('[id], [name], [for]').forEach((node) => {
      for (const attr of ['id', 'name', 'for']) {
        if (node.hasAttribute(attr)) node.setAttribute(attr, node.getAttribute(attr).replaceAll('__INDEX__', String(nextIndex)));
      }
    });
    rows.append(clone);
    nextIndex += 1;
    update();
    rows.lastElementChild?.querySelector('input,select,textarea')?.focus();
  });
  rows.addEventListener('click', (event) => {
    const button = event.target.closest('[data-ui-repeater-remove]');
    if (!button || entries().length <= min) return;
    const row = button.closest('[data-ui-repeater-row]');
    row?.remove();
    update();
    add.focus();
  });
  update();
});

document.querySelectorAll('[data-ui-transfer-list]').forEach((root) => {
  const available = root.querySelector('[data-ui-transfer-available]');
  const chosen = root.querySelector('[data-ui-transfer-selected]');
  const add = root.querySelector('[data-ui-transfer-add]');
  const remove = root.querySelector('[data-ui-transfer-remove]');
  const hidden = root.querySelector('[data-ui-transfer-hidden]');
  const status = root.querySelector('[data-ui-transfer-status]');
  if (!available || !chosen || !add || !remove || !hidden || !status) return;
  const sync = () => {
    const inputs = [...chosen.options].map((option) => {
      const field = document.createElement('input');
      field.type = 'hidden';
      field.name = root.dataset.uiTransferName;
      field.value = option.value;
      return field;
    });
    hidden.replaceChildren(...inputs);
    status.textContent = chosen.options.length + ' ausgewählt';
    add.disabled = ![...available.selectedOptions].some((item) => !item.disabled);
    remove.disabled = ![...chosen.selectedOptions].some((item) => !item.disabled);
  };
  const move = (source, target) => {
    [...source.selectedOptions].filter((option) => !option.disabled).forEach((option) => {
      option.selected = false;
      target.append(option);
    });
    sync();
    target.focus();
  };
  available.addEventListener('change', sync);
  chosen.addEventListener('change', sync);
  add.addEventListener('click', () => move(available, chosen));
  remove.addEventListener('click', () => move(chosen, available));
  sync();
});
