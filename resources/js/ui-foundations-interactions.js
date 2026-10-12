/**
 * Opt-in enhancements for the component lab: character counts and local file choice.
 * No upload, no backend calls and no persisted data.
 */
function updateCharacterCount(field) {
  const outputId = field.getAttribute('data-ui-char-count');
  if (!outputId) return;
  const output = document.getElementById(outputId);
  if (!(output instanceof HTMLOutputElement)) return;
  const max = Number(field.maxLength);
  if (!Number.isFinite(max) || max <= 0) return;
  output.textContent = field.value.length + ' / ' + max + ' Zeichen';
}

document.querySelectorAll('textarea[data-ui-char-count]').forEach(updateCharacterCount);
document.addEventListener('input', (event) => {
  if (event.target instanceof HTMLTextAreaElement) updateCharacterCount(event.target);
});

function matchesAccept(file, accept) {
  if (!accept) return true;
  return accept.split(',').map((v) => v.trim().toLowerCase()).filter(Boolean).some((rule) => {
    if (rule.startsWith('.')) return file.name.toLowerCase().endsWith(rule);
    if (rule.endsWith('/*')) return file.type.toLowerCase().startsWith(rule.slice(0, -1));
    return file.type.toLowerCase() === rule;
  });
}

function showChosenFiles(zone) {
  const input = zone.querySelector('[data-ui-file-input]');
  const output = zone.querySelector('[data-ui-file-feedback]');
  if (!(input instanceof HTMLInputElement) || !output) return;
  output.removeAttribute('data-ui-invalid');
  const files = [...(input.files || [])];
  if (files.length === 0) {
    output.textContent = 'Noch keine Datei ausgewählt.';
    return;
  }
  if (files.some((file) => !matchesAccept(file, input.accept))) {
    output.textContent = 'Dateityp nicht zulässig. Bitte eine passende Datei auswählen.';
    output.setAttribute('data-ui-invalid', 'true');
    return;
  }
  output.textContent = files.length === 1
    ? 'Ausgewählt: ' + files[0].name
    : files.length + ' Dateien ausgewählt: ' + files.map((f) => f.name).join(', ');
}

document.querySelectorAll('[data-ui-file-zone]').forEach(showChosenFiles);
document.addEventListener('change', (event) => {
  if (!event.target.matches?.('[data-ui-file-input]')) return;
  const zone = event.target.closest('[data-ui-file-zone]');
  if (zone) showChosenFiles(zone);
});

document.addEventListener('dragover', (event) => {
  const zone = event.target.closest?.('[data-ui-file-zone]');
  if (!zone || !event.dataTransfer?.types.includes('Files')) return;
  event.preventDefault();
  zone.querySelector('.bc-file-drop-zone__surface')?.classList.add('bc-file-drop-zone__surface--drag');
});
document.addEventListener('dragleave', (event) => {
  const zone = event.target.closest?.('[data-ui-file-zone]');
  if (zone && !zone.contains(event.relatedTarget)) {
    zone.querySelector('.bc-file-drop-zone__surface')?.classList.remove('bc-file-drop-zone__surface--drag');
  }
});
document.addEventListener('drop', (event) => {
  const zone = event.target.closest?.('[data-ui-file-zone]');
  if (!zone) return;
  event.preventDefault();
  zone.querySelector('.bc-file-drop-zone__surface')?.classList.remove('bc-file-drop-zone__surface--drag');
  const input = zone.querySelector('[data-ui-file-input]');
  const output = zone.querySelector('[data-ui-file-feedback]');
  if (!(input instanceof HTMLInputElement) || input.disabled) return;
  const files = [...(event.dataTransfer?.files || [])];
  if (files.length === 0) return;
  if ((!input.multiple && files.length > 1) || files.some((file) => !matchesAccept(file, input.accept))) {
    if (output) {
      output.textContent = 'Diese Auswahl ist nicht zulässig. Bitte Dateityp und Anzahl prüfen.';
      output.setAttribute('data-ui-invalid', 'true');
    }
    return;
  }
  // DataTransfer is progressive enhancement: native file selection always remains.
  try {
    const transfer = new DataTransfer();
    files.forEach((file) => transfer.items.add(file));
    input.files = transfer.files;
    showChosenFiles(zone);
  } catch (_) {
    if (output) output.textContent = 'Bitte die Datei über „Datei auswählen“ hinzufügen.';
  }
});
