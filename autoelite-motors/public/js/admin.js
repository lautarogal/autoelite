/* Panel /admin: inicio de sesión y alta de empleados. Las rutas son relativas a /admin/ (../api/...). */
document.addEventListener('DOMContentLoaded', async () => {
  const $ = (id) => document.getElementById(id);
  const API = '../api/';
  const loginPanel = $('login-panel');
  const adminPanel = $('admin-panel');
  const logoutBtn = $('logout-btn');
  const alertBox = $('alert');
  const subtitle = $('page-subtitle');
  const tipo = $('tipo');
  const empleadoForm = $('empleado-form');
  $('year').textContent = new Date().getFullYear();

  const flash = (type, message) => {
    alertBox.hidden = !message;
    alertBox.className = `alert alert-${type}`;
    alertBox.textContent = message || '';
  };

  const parse = async (res) => {
    const text = await res.text();
    try { return JSON.parse(text); }
    catch (e) {
      if (/<\?php/.test(text)) throw new Error('PHP no se está ejecutando. Abrí el sistema desde Apache: http://localhost/autoelite-motors/ (no con doble clic ni con Live Server).');
      if (res.status === 404) throw new Error('No se encontró la API (' + res.url + '). La carpeta debe llamarse autoelite-motors y estar dentro de htdocs.');
      if (res.status >= 500) throw new Error('Error interno del servidor (HTTP ' + res.status + '). Revisá que MySQL esté iniciado, que la base esté importada y el log de errores de Apache/PHP.');
      throw new Error('No se pudo conectar con el servidor. Verificá que Apache y MySQL estén iniciados.');
    }
  };

  const getJson = async (url) => parse(await fetch(API + url, { credentials: 'same-origin' }));
  const getCsrf = async () => (await getJson('csrf.php')).token || '';
  const post = async (url, form) => {
    const fd = form instanceof FormData ? form : new FormData(form);
    fd.set('_csrf', await getCsrf());
    return parse(await fetch(API + url, { method: 'POST', body: fd, credentials: 'same-origin' }));
  };

  const showLogin = () => {
    loginPanel.hidden = false; adminPanel.hidden = true; logoutBtn.hidden = true;
    subtitle.textContent = 'Acceso restringido';
    $('usuario').focus();
  };

  const showAdmin = () => {
    loginPanel.hidden = true; adminPanel.hidden = false; logoutBtn.hidden = false;
    subtitle.textContent = 'Alta, edición y baja de empleados';
    loadEmpleados();
  };

  const body = $('empleados-body');
  const cancelBtn = $('cancel-edit');
  const title = $('form-title');
  const submitBtn = $('empleado-submit');
  let items = [];
  const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  const loadEmpleados = async () => {
    try {
      const d = await getJson('admin_empleados.php');
      if (!d.ok) { flash('error', d.message || 'No se pudo cargar el listado.'); return; }
      items = d.items || [];
      $('lista-panel').hidden = false;
      $('count').textContent = items.length;
      $('empleados-empty').hidden = items.length > 0;
      body.innerHTML = items.map((r, i) => `<tr><td>${esc(r.area)}</td><td>${esc(r.Nombre)}</td><td>${esc(r.DNI)}</td><td>${esc(r.Edad)}</td><td>${esc(r.Direccion)}</td><td>${esc(r.Horario_Trabajo)}</td><td class="actions"><button type="button" class="text-link" data-edit="${i}">Editar</button> <button type="button" class="link-danger" data-del="${i}">Eliminar</button></td></tr>`).join('');
    } catch (e) { flash('error', e.message); }
  };

  const resetForm = () => {
    empleadoForm.reset();
    empleadoForm.elements.action.value = 'empleado_crear';
    empleadoForm.elements.id.disabled = true; empleadoForm.elements.id.value = '';
    tipo.disabled = false;
    title.textContent = 'Agregar empleado';
    submitBtn.textContent = 'Agregar empleado';
    cancelBtn.hidden = true;
  };

  const startEdit = (r) => {
    empleadoForm.elements.action.value = 'empleado_editar';
    empleadoForm.elements.id.disabled = false; empleadoForm.elements.id.value = r.id;
    tipo.value = r.tipo; tipo.disabled = true;
    empleadoForm.elements.nombre.value = r.Nombre ?? '';
    empleadoForm.elements.dni.value = r.DNI ?? '';
    empleadoForm.elements.edad.value = r.Edad ?? '';
    empleadoForm.elements.direccion.value = r.Direccion ?? '';
    empleadoForm.elements.horario_trabajo.value = r.Horario_Trabajo ?? '';
    title.textContent = 'Editar empleado';
    submitBtn.textContent = 'Guardar cambios';
    cancelBtn.hidden = false;
    flash('', '');
    empleadoForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
  };
  cancelBtn.addEventListener('click', resetForm);

  body.addEventListener('click', async (ev) => {
    const edit = ev.target.closest('[data-edit]'); const del = ev.target.closest('[data-del]');
    if (edit) return startEdit(items[Number(edit.dataset.edit)]);
    if (!del) return;
    const r = items[Number(del.dataset.del)];
    if (!window.confirm(`¿Eliminar a ${r.Nombre}? Esta acción no se puede deshacer.`)) return;
    const fd = new FormData();
    fd.set('action', 'empleado_eliminar'); fd.set('tipo', r.tipo); fd.set('id', r.id);
    try {
      const j = await post('admin_empleados.php', fd);
      flash(j.ok ? 'success' : 'error', j.message || (j.ok ? 'Empleado eliminado.' : 'No se pudo eliminar.'));
      if (j.ok) { if (empleadoForm.elements.id.value === String(r.id) && tipo.value === r.tipo) resetForm(); loadEmpleados(); }
    } catch (e) { flash('error', e.message); }
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });

  try {
    const s = await getJson('admin_session.php');
    s.logged ? showAdmin() : showLogin();
  } catch (e) { showLogin(); flash('error', e.message); }

  $('login-form').addEventListener('submit', async (ev) => {
    ev.preventDefault();
    const btn = $('login-submit');
    btn.disabled = true; flash('', '');
    try {
      const j = await post('admin_login.php', ev.target);
      if (j.ok) { ev.target.reset(); showAdmin(); }
      else flash('error', j.message || 'No se pudo iniciar sesión.');
    } catch (e) { flash('error', e.message); }
    btn.disabled = false;
  });

  empleadoForm.addEventListener('submit', async (ev) => {
    ev.preventDefault();
    const btn = $('empleado-submit');
    btn.disabled = true;
    try {
      const fd = new FormData(empleadoForm);
      fd.set('tipo', tipo.value); /* el área deshabilitada (edición) no se incluye sola */
      const j = await post('admin_empleados.php', fd);
      if (j.ok) {
        flash('success', j.message);
        resetForm(); loadEmpleados();
      } else {
        flash('error', j.message || 'No se pudo agregar el empleado.');
        if (/Iniciá sesión/.test(j.message || '')) showLogin();
      }
    } catch (e) { flash('error', e.message); }
    btn.disabled = false;
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });

  logoutBtn.addEventListener('click', async () => {
    try { await post('admin_logout.php', new FormData()); } catch (e) {}
    flash('', ''); showLogin();
  });
});
