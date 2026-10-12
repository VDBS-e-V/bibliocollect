// Progressive enhancement: without JS the native select remains usable.
// Optional same-origin JSON endpoint: { options: [{ value, label, disabled }] }.
document.querySelectorAll('[data-ui-combobox]').forEach((root, index) => {
  const select = root.querySelector('select');
  if (!select || select.disabled) return;
  const input = document.createElement('input');
  input.type = 'search';
  input.className = 'bc-combobox__search';
  input.setAttribute('role', 'combobox');
  input.setAttribute('aria-autocomplete', 'list');
  input.setAttribute('aria-expanded', 'false');
  input.setAttribute('aria-haspopup', 'listbox');
  input.setAttribute('autocomplete', 'off');
  const list = document.createElement('ul');
  const listId = 'bc-combobox-list-' + index;
  list.id = listId;
  list.className = 'bc-combobox__options';
  list.setAttribute('role', 'listbox');
  list.hidden = true;
  input.setAttribute('aria-controls', listId);
  input.setAttribute('aria-label', root.querySelector('label')?.textContent?.trim() || 'Auswahl durchsuchen');
  if (select.getAttribute('aria-describedby')) input.setAttribute('aria-describedby', select.getAttribute('aria-describedby'));
  if (select.getAttribute('aria-invalid')) input.setAttribute('aria-invalid', select.getAttribute('aria-invalid'));
  const status = document.createElement('p');
  status.className = 'bc-combobox__status';
  status.setAttribute('role', 'status');
  status.setAttribute('aria-live', 'polite');
  const nativeOptions = () => [...select.options].filter((o) => o.value !== '').map((o) => ({ value: o.value, label: o.textContent, disabled: o.disabled }));
  let options = nativeOptions();
  let shown = [];
  let active = -1;
  let controller = null;
  let queryTimer;
  const remote = root.dataset.uiComboboxUrl;
  const minimum = Math.max(1, Number(root.dataset.uiComboboxMin) || 2);
  const selectedLabel = () => select.selectedOptions[0]?.value ? select.selectedOptions[0].textContent : '';
  const close = () => { list.hidden = true; input.setAttribute('aria-expanded', 'false'); input.removeAttribute('aria-activedescendant'); active = -1; };
  const choose = (option) => {
    if (!option || option.disabled) return;
    let target = [...select.options].find((item) => item.value === option.value);
    if (!target) {
      target = document.createElement('option');
      target.value = option.value;
      target.textContent = option.label;
      select.append(target);
    }
    select.value = target.value;
    input.value = target.textContent;
    select.dispatchEvent(new Event('change', { bubbles: true }));
    status.textContent = 'Ausgewählt: ' + input.value;
    close();
    input.focus();
  };
  const activate = (position) => {
    if (!shown.length) return;
    let candidate = ((position % shown.length) + shown.length) % shown.length;
    for (let tries = 0; tries < shown.length; tries += 1) {
      if (!shown[candidate].disabled) {
        active = candidate;
        [...list.children].forEach((node, i) => node.setAttribute('aria-selected', String(i === active)));
        const option = list.children[active];
        input.setAttribute('aria-activedescendant', option.id);
        option.scrollIntoView({ block: 'nearest' });
        return;
      }
      candidate = (candidate + 1) % shown.length;
    }
    input.removeAttribute('aria-activedescendant');
    active = -1;
  };
  const render = (values, message) => {
    shown = values.slice(0, 50);
    active = -1;
    list.replaceChildren();
    input.removeAttribute('aria-activedescendant');
    shown.forEach((option, i) => {
      const item = document.createElement('li');
      item.id = listId + '-option-' + i;
      item.className = 'bc-combobox__option';
      item.setAttribute('role', 'option');
      item.setAttribute('aria-selected', 'false');
      item.setAttribute('aria-disabled', String(!!option.disabled));
      item.textContent = option.label;
      item.addEventListener('pointerdown', (event) => event.preventDefault());
      item.addEventListener('click', () => choose(option));
      list.append(item);
    });
    status.textContent = message || (shown.length ? shown.length + ' Vorschläge' : 'Keine Treffer');
    list.hidden = !shown.length;
    input.setAttribute('aria-expanded', String(!!shown.length));
  };
  const search = (query) => {
    const needle = query.trim().toLocaleLowerCase();
    if (!remote) { render(options.filter((o) => o.label.toLocaleLowerCase().includes(needle))); return; }
    clearTimeout(queryTimer);
    controller?.abort();
    if (needle.length < minimum) { close(); status.textContent = 'Mindestens ' + minimum + ' Zeichen eingeben'; return; }
    status.textContent = 'Vorschläge werden geladen …';
    queryTimer = setTimeout(async () => {
      controller = new AbortController();
      try {
        const url = new URL(remote, window.location.href);
        if (url.origin !== window.location.origin) throw new Error('Only same-origin suggestions are supported');
        url.searchParams.set('q', query.trim());
        const response = await fetch(url, { headers: { Accept: 'application/json' }, signal: controller.signal, credentials: 'same-origin' });
        if (!response.ok) throw new Error('Suggestion request failed');
        const data = await response.json();
        const result = Array.isArray(data) ? data : data.options;
        if (!Array.isArray(result)) throw new Error('Invalid suggestion response');
        options = result.filter((item) => item && typeof item.label === 'string' && typeof item.value === 'string')
          .slice(0, 50).map((item) => ({ label: item.label, value: item.value, disabled: !!item.disabled }));
        render(options);
      } catch (error) {
        if (error.name !== 'AbortError') { close(); status.textContent = 'Vorschläge konnten nicht geladen werden'; }
      }
    }, 250);
  };
  input.addEventListener('input', () => {
    if (select.value) { select.value = ''; select.dispatchEvent(new Event('change', { bubbles: true })); }
    search(input.value);
  });
  input.addEventListener('focus', () => search(input.value));
  input.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') { if (!list.hidden) { event.preventDefault(); close(); } else { input.value = selectedLabel(); } return; }
    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
      event.preventDefault();
      if (list.hidden) { search(input.value); return; }
      const step = event.key === 'ArrowDown' ? 1 : -1;
      activate(active < 0 ? (step === 1 ? 0 : shown.length - 1) : active + step);
    }
    if (event.key === 'Enter' && !list.hidden && active >= 0) { event.preventDefault(); choose(shown[active]); }
  });
  select.addEventListener('change', () => { input.value = selectedLabel(); });
  document.addEventListener('pointerdown', (event) => { if (!root.contains(event.target)) close(); });
  select.hidden = true;
  select.setAttribute('aria-hidden', 'true');
  select.tabIndex = -1;
  input.value = selectedLabel();
  select.insertAdjacentElement('afterend', input);
  input.insertAdjacentElement('afterend', list);
  list.insertAdjacentElement('afterend', status);
});
