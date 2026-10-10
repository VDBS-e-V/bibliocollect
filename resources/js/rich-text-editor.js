// Texteditor (TinyMCE, selbst gehostet, GPL-Modus) für Textfelder mit `data-rich-text`.
// Wird nur auf Seiten geladen, die ein solches Feld haben (siehe app.js). Ohne JavaScript oder bei einem Ladefehler bleibt das
// normale Textfeld stehen; der Server bereinigt den Text in jedem Fall (RichTextSanitizer).
import tinymce from 'tinymce/tinymce';

// Die Sprachdatei und die Skins nutzen das globale `tinymce`.
window.tinymce = tinymce;

await Promise.all([
  import('tinymce/models/dom'),
  import('tinymce/themes/silver'),
  import('tinymce/icons/default'),
  import('tinymce/plugins/lists'),
  import('tinymce/plugins/link'),
  import('tinymce/plugins/autolink'),
  import('tinymce/plugins/table'),
  import('tinymce/skins/ui/oxide/skin.js'),
  import('tinymce/skins/ui/oxide/content.js'),
  import('tinymce/skins/content/default/content.js'),
  import('tinymce/skins/ui/oxide-dark/skin.js'),
  import('tinymce/skins/ui/oxide-dark/content.js'),
  import('tinymce/skins/content/dark/content.js'),
  import('tinymce-i18n/langs8/de.js'),
]);

const FRAME_TITLE = 'Textfeld mit Formatierung. Alt+F10 öffnet die Werkzeugleiste, Alt+0 die Hilfe.';

const CONTENT_STYLE = `
  body { font-family: Lato, Arial, sans-serif; font-size: 16px; line-height: 1.6; color: #1d1d1d; margin: 1rem; }
  h2, h3, h4 { line-height: 1.25; margin: 1.4em 0 .5em; }
  p, ul, ol, blockquote { margin: 0 0 1em; }
  table { border-collapse: collapse; }
  th, td { border: 1px solid #8a8a8a; padding: .4em .6em; text-align: left; }
  blockquote { border-left: 4px solid #2dc08e; padding-left: 1em; color: #444; }
  a { color: #04704f; }
`;

/** Farben im dunklen Design (entsprechen den Tokens in resources/css/tokens.css). */
const DARK_STYLE = `
  body { background: #261926; color: #f1f4f2; }
  th, td { border-color: #a49ca3; }
  blockquote { border-left-color: #40cd9a; color: #d8d2d7; }
  a { color: #40cd9a; }
`;

const isDark = () => document.documentElement.hasAttribute('data-vdbs-theme');

/** Erlaubte Elemente: spiegelt die Liste des Servers (App\Modules\Content\Services\RichTextSanitizer). */
const VALID_ELEMENTS = [
  'h2', 'h3', 'h4', 'p', 'br', 'strong/b', 'em/i', 'u', 's', 'ul', 'ol', 'li', 'blockquote', 'hr',
  'table', 'caption', 'thead', 'tbody', 'tr', 'th[scope|colspan|rowspan]', 'td[colspan|rowspan]',
  'a[href|title|rel]',
].join(',');

export function enhance(textarea) {
  textarea.removeAttribute('required'); // sonst blockiert der Browser das Absenden, weil das versteckte Feld nicht fokussierbar ist

  const dark = isDark();

  return tinymce.init({
    target: textarea,
    license_key: textarea.dataset.licenseKey || 'gpl',
    language: 'de',
    menubar: false,
    statusbar: false,
    promotion: false,
    height: 520,
    plugins: 'lists link autolink table',
    toolbar: 'blocks | bold italic underline | bullist numlist | blockquote hr | link table | undo redo',
    block_formats: 'Absatz=p; Überschrift 2=h2; Überschrift 3=h3; Überschrift 4=h4',
    valid_elements: VALID_ELEMENTS,
    link_title: false,
    link_default_target: false,
    link_target_list: false,
    link_assume_external_targets: 'https',
    relative_urls: false,
    convert_urls: false,
    entity_encoding: 'raw',
    browser_spellcheck: true,
    paste_data_images: false,
    content_style: CONTENT_STYLE + (dark ? DARK_STYLE : ''),
    skin: dark ? 'oxide-dark' : 'oxide',
    content_css: dark ? 'dark' : 'default',
    iframe_aria_text: FRAME_TITLE,
    setup(editor) {
      editor.on('change input undo redo', () => editor.save());
      // Der Bearbeitungsbereich ist ein Rahmen und braucht einen Namen für Screenreader.
      editor.on('init', () => editor.iframeElement?.setAttribute('title', FRAME_TITLE));
    },
  });
}

export default function init(textareas) {
  const fields = Array.from(textareas);
  let darkNow = isDark();

  // Wechselt jemand das Farbschema, wird der Editor mit dem passenden Aussehen neu aufgebaut (der Text bleibt erhalten).
  new MutationObserver(() => {
    if (isDark() === darkNow) {
      return;
    }

    darkNow = isDark();

    for (const textarea of fields) {
      const editor = tinymce.get(textarea.id);

      if (editor) {
        editor.save();
        editor.remove();
      }

      enhance(textarea);
    }
  }).observe(document.documentElement, { attributes: true, attributeFilter: ['data-vdbs-theme'] });

  return Promise.all(fields.map((textarea) => enhance(textarea)));
}
