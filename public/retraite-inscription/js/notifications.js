/* ═══════════════════════════════════════════
   SweetAlert2 · toasts & modales (sans alert natif)
═══════════════════════════════════════════ */
'use strict';

function retraiteEscapeForHtml(s) {
  const d = document.createElement('div');
  d.textContent = s == null ? '' : String(s);
  return d.innerHTML;
}

/** @param {{ title?: string, text?: string, html?: string, footer?: string, persistent?: boolean }} opts */
function retraiteNotifyError(opts) {
  const title = opts.title || 'Une erreur est survenue';
  const persistent = opts.persistent !== false;

  if (typeof Swal !== 'undefined') {
    return Swal.fire({
      icon: 'error',
      title,
      text: opts.text || undefined,
      html: opts.html || undefined,
      footer: opts.footer
        ? `<small style="display:block;text-align:left;opacity:.85">${retraiteEscapeForHtml(opts.footer)}</small>`
        : undefined,
      confirmButtonText: 'Fermer',
      confirmButtonColor: '#c45c22',
      allowOutsideClick: true,
      timer: persistent ? undefined : 12000,
      timerProgressBar: !persistent,
    });
  }

  window.alert((title ? title + '\n\n' : '') + (opts.text || opts.html || ''));
}

/** Toast durable (reste affiché jusqu’au clic « OK » si timer absent) */
function retraiteNotifyToast(message, variant) {
  const icon =
    variant === 'success'
      ? 'success'
      : variant === 'error'
        ? 'error'
      : variant === 'warning'
        ? 'warning'
        : variant === 'info'
          ? 'info'
          : 'info';
  const color =
    variant === 'success'
      ? '#2E7D32'
      : variant === 'error'
        ? '#C62828'
        : variant === 'warning'
          ? '#D4772C'
          : '#1565C0';

  if (typeof Swal !== 'undefined') {
    return Swal.fire({
      toast: true,
      position: 'top-end',
      icon,
      iconColor: color,
      color,
      title: message,
      showConfirmButton: true,
      confirmButtonText: 'OK',
      confirmButtonColor: color,
      timer: variant === 'success' ? 20000 : undefined,
      timerProgressBar: variant === 'success',
    });
  }

  window.alert(message);
}

window.retraiteNotifyError = retraiteNotifyError;
window.retraiteNotifyToast = retraiteNotifyToast;
