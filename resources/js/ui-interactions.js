/**
 * Reusable lightweight progressive enhancement for the universal UI components.
 * No production business logic, no network requests, no state persistence.
 * Actual confirmation on production forms remains the existing [data-confirm] system.
 */
const dialogTriggers = new WeakMap();

document.addEventListener('click', (event) => {
  const trigger = event.target.closest('[data-ui-dialog-open]');
  if (trigger) {
    const id = trigger.getAttribute('data-ui-dialog-open');
    const dialog = id ? document.getElementById(id) : null;
    if (!(dialog instanceof HTMLDialogElement)) return;
    event.preventDefault();
    if (!dialog.open) {
      dialogTriggers.set(dialog, trigger);
      dialog.showModal();
      dialog.querySelector('[data-ui-dialog-close]')?.focus();
    }
    return;
  }

  const closeButton = event.target.closest('[data-ui-dialog-close]');
  if (closeButton) {
    const dialog = closeButton.closest('dialog.bc-ui-dialog');
    if (dialog?.open) dialog.close();
    return;
  }

  const dismiss = event.target.closest('[data-ui-toast-dismiss]');
  if (dismiss) {
    const toast = dismiss.closest('.bc-ui-toast');
    if (!toast) return;
    const next = toast.nextElementSibling;
    toast.hidden = true;
    if (next instanceof HTMLElement) {
      const nextFocusable = next.querySelector('button, a, input, [tabindex="0"]');
      if (nextFocusable instanceof HTMLElement) nextFocusable.focus();
    }
    return;
  }

  document.querySelectorAll('details[data-ui-dismissable][open]').forEach((details) => {
    if (!details.contains(event.target)) details.open = false;
  });
});

// Escape closes action menus/popovers with sensible focus restoration.
// Native <dialog> handles Escape and its focus trap on its own.
document.addEventListener('keydown', (event) => {
  if (event.key !== 'Escape') return;
  const focused = document.activeElement;
  const menus = [...document.querySelectorAll('details[data-ui-dismissable][open]')];
  const activeMenu = menus.find((details) => details.contains(focused)) || menus.at(-1);
  if (!activeMenu) return;
  activeMenu.open = false;
  activeMenu.querySelector('summary')?.focus();
});

// When another menu opens, dismiss the previously open menus.
// This is navigation, not an ARIA role=menu application command widget.
document.addEventListener('toggle', (event) => {
  const opened = event.target;
  if (!opened.matches?.('details[data-ui-dismissable]') || !opened.open) return;
  document.querySelectorAll('details[data-ui-dismissable][open]').forEach((details) => {
    if (details !== opened) details.open = false;
  });
}, true);

document.addEventListener('close', (event) => {
  const dialog = event.target;
  if (!(dialog instanceof HTMLDialogElement) || !dialog.matches('.bc-ui-dialog')) return;
  const trigger = dialogTriggers.get(dialog);
  if (trigger?.isConnected) trigger.focus();
  dialogTriggers.delete(dialog);
}, true);
