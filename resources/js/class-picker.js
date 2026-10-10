// Klassenauswahl der Leselisten: Auswahlfeld, gewählte Klassen als Liste darunter, mit X wieder entfernbar.
function setup(root) {
  const select = root.querySelector('[data-class-picker-select]');
  const list = root.querySelector('[data-class-picker-list]');
  const empty = root.querySelector('[data-class-picker-empty]');

  if (!select || !list) {
    return;
  }

  const refresh = () => {
    if (empty) {
      empty.hidden = list.children.length > 0;
    }
  };

  const option = (id) => Array.from(select.options).find((candidate) => candidate.value === id);

  select.addEventListener('change', () => {
    const chosen = option(select.value);

    if (!chosen || chosen.value === '' || chosen.disabled) {
      select.value = '';
      return;
    }

    const item = document.createElement('li');
    item.className = 'bc-class-picker__item';
    item.dataset.classId = chosen.value;

    const label = document.createElement('span');
    label.textContent = chosen.textContent.trim();

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'school_class_ids[]';
    input.value = chosen.value;

    const remove = document.createElement('button');
    remove.type = 'button';
    remove.className = 'bc-class-picker__remove';
    remove.setAttribute('data-class-remove', '');
    remove.setAttribute('aria-label', 'Klasse ' + label.textContent + ' entfernen');
    remove.textContent = '\u00d7';

    item.append(label, input, remove);
    list.append(item);
    chosen.disabled = true;
    select.value = '';
    refresh();
    select.focus();
  });

  list.addEventListener('click', (event) => {
    const button = event.target.closest('[data-class-remove]');

    if (!button) {
      return;
    }

    const item = button.closest('li');
    const restored = option(item.dataset.classId);

    if (restored) {
      restored.disabled = false;
    }

    item.remove();
    refresh();
    select.focus();
  });
}

document.querySelectorAll('[data-class-picker]').forEach(setup);
