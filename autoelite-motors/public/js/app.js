/* Utilidades compartidas del portal: navegación, mensajes, CSRF y envío de formularios a la API PHP. */
const Portal = (() => {
  const ENTITIES = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
  const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ENTITIES[c]);
  const money = (v) => '$ ' + Number(v || 0).toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  const int = (v) => Number(v || 0).toLocaleString('de-DE');
  const today = () => {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
  };

  async function parse(res) {
    const text = await res.text();
    try { return JSON.parse(text); }
    catch (e) {
      if (/<\?php/.test(text)) throw new Error('PHP no se está ejecutando. Abrí el sistema desde Apache: http://localhost/autoelite-motors/ (no con doble clic ni con Live Server).');
      if (res.status === 404) throw new Error('No se encontró la API (' + res.url + '). La carpeta debe llamarse autoelite-motors y estar dentro de htdocs.');
      if (res.status >= 500) throw new Error('Error interno del servidor (HTTP ' + res.status + '). Revisá que MySQL esté iniciado, que la base esté importada y el log de errores de Apache/PHP.');
      throw new Error('No se pudo conectar con el servidor. Verificá que Apache y MySQL estén iniciados.');
    }
  }

  async function get(url, params = {}) {
    const qs = new URLSearchParams();
    Object.entries(params).forEach(([k, v]) => { if (v !== '' && v != null) qs.set(k, v); });
    const res = await fetch(qs.toString() ? `${url}?${qs}` : url, { credentials: 'same-origin' });
    const json = await parse(res);
    if (!json.ok) throw new Error(json.message || 'No se pudieron cargar los datos.');
    return json;
  }

  const csrf = fetch('api/csrf.php', { credentials: 'same-origin' })
    .then((r) => r.json()).then((j) => j.token || '').catch(() => '');

  const setFlash = (type, message) => { try { sessionStorage.setItem('_flash', JSON.stringify({ type, message })); } catch (e) {} };
  const takeFlash = () => {
    try { const raw = sessionStorage.getItem('_flash'); sessionStorage.removeItem('_flash'); return raw ? JSON.parse(raw) : null; }
    catch (e) { return null; }
  };
  const showFlash = (type, message) => {
    const main = document.querySelector('main.container');
    if (!main) return;
    let el = main.querySelector(':scope > .alert');
    if (!el) { el = document.createElement('div'); el.setAttribute('role', 'status'); main.prepend(el); }
    el.className = `alert alert-${type}`;
    el.textContent = message;
  };

  /* Todo formulario con data-api se envía por fetch al endpoint PHP indicado. */
  document.addEventListener('submit', async (ev) => {
    const form = ev.target.closest('form[data-api]');
    if (!form) return;
    ev.preventDefault();
    if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) return;
    if (ev.submitter) ev.submitter.disabled = true;
    const back = form.dataset.redirect || 'index.html';
    try {
      const fd = new FormData(form);
      fd.set('_csrf', await csrf);
      const res = await fetch(form.dataset.api, { method: 'POST', body: fd, credentials: 'same-origin' });
      const json = await parse(res);
      setFlash(json.ok ? 'success' : 'error', json.message || '');
      window.location.href = (json.ok && json.redirect) || back;
    } catch (e) {
      setFlash('error', e.message);
      window.location.href = back;
    }
  });

  document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.querySelector('.nav-toggle');
    const nav = document.querySelector('.main-nav');
    if (toggle && nav) toggle.addEventListener('click', () => {
      const open = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', String(open));
    });
    const year = document.getElementById('year');
    if (year) year.textContent = new Date().getFullYear();
    const flash = takeFlash();
    if (flash && flash.message) showFlash(flash.type, flash.message);
  });

  return { esc, money, int, today, get, setFlash, showFlash };
})();
