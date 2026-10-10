// Vorschläge beim Tippen im Katalog-Suchfeld: füllt die zugehörige Datenliste (native Bedienung, auch per Tastatur und Screenreader).
const input = document.querySelector('input[data-suggest-url]');
const list = input ? document.getElementById(input.getAttribute('list') || '') : null;

if (input && list) {
  let timer = null;
  let controller = null;

  input.addEventListener('input', () => {
    const term = input.value.trim();

    window.clearTimeout(timer);

    if (term.length < 2) {
      list.replaceChildren();
      return;
    }

    timer = window.setTimeout(async () => {
      if (controller) {
        controller.abort();
      }

      controller = new AbortController();

      try {
        const response = await fetch(input.dataset.suggestUrl + '?q=' + encodeURIComponent(term), {
          headers: { Accept: 'application/json' },
          signal: controller.signal,
        });

        if (!response.ok) {
          return;
        }

        const data = await response.json();
        list.replaceChildren(
          ...(data.suggestions || []).map((text) => {
            const option = document.createElement('option');
            option.value = text;

            return option;
          }),
        );
      } catch (error) {
        // Vorschläge sind eine Zugabe; ohne sie funktioniert die Suche unverändert.
      }
    }, 250);
  });
}
