/**
 * Components-first design lab: interactive selection demonstration.
 * No form, route, fetch or backend write. No action on production data.
 */
function updateSelection(demo) {
  const rows = [...demo.querySelectorAll('[data-ui-select-row]')];
  const selectAll = demo.querySelector('[data-ui-select-all]');
  const selected = rows.filter((item) => item.checked).length;
  const counter = demo.querySelector('[data-ui-bulk-bar] .bc-bulk-action-bar__summary strong');
  const total = demo.querySelector('[data-ui-bulk-bar] .bc-bulk-action-bar__summary span');
  const clear = demo.querySelector('[data-ui-bulk-clear]');

  if (counter) counter.textContent = selected + ' ausgewählt';
  if (total) total.textContent = 'von ' + rows.length;
  if (selectAll) {
    selectAll.checked = rows.length > 0 && selected === rows.length;
    selectAll.indeterminate = selected > 0 && selected < rows.length;
  }
  if (clear) clear.disabled = selected === 0;
}

document.querySelectorAll('[data-ui-bulk-demo]').forEach(updateSelection);

document.addEventListener('change', (event) => {
  const checkbox = event.target;
  if (!(checkbox instanceof HTMLInputElement)) return;
  const demo = checkbox.closest('[data-ui-bulk-demo]');
  if (!demo) return;

  if (checkbox.matches('[data-ui-select-all]')) {
    demo.querySelectorAll('[data-ui-select-row]').forEach((item) => {
      item.checked = checkbox.checked;
    });
  }

  if (checkbox.matches('[data-ui-select-row], [data-ui-select-all]')) updateSelection(demo);
});

document.addEventListener('click', (event) => {
  const clear = event.target.closest('[data-ui-bulk-clear]');
  if (!clear) return;
  const demo = clear.closest('[data-ui-bulk-demo]');
  if (!demo) return;

  demo.querySelectorAll('[data-ui-select-row]').forEach((item) => {
    item.checked = false;
  });
  updateSelection(demo);
  demo.querySelector('[data-ui-select-all]')?.focus();
});
