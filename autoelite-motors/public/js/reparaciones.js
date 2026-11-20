document.addEventListener('DOMContentLoaded', async () => {
  const { esc, money, today, get, showFlash } = Portal;
  const form = document.getElementById('repair-form');
  form.querySelector('#fecha_ingreso').value = today();
  const ESTADOS = ['Pendiente', 'En Diagnostico', 'En Reparacion', 'Finalizada', 'Entregada'];
  const options = (rows, value, label) => '<option value="">Seleccionar</option>' + rows.map((r) => `<option value="${esc(r[value])}">${esc(label(r))}</option>`).join('');

  try {
    const d = await get('api/reparaciones.php');
    form.querySelector('#cliente').innerHTML = options(d.clientes, 'ID_Cliente', (c) => c.Nombre_Completo_Razon_Social);
    form.querySelector('#vin').innerHTML = options(d.vehiculos, 'VIN', (v) => v.Marca + ' ' + v.Modelo + ' · ' + v.VIN + ' · ' + v.Estado);
    form.querySelector('#mecanico').innerHTML = options(d.mecanicos, 'ID_Mecanico', (m) => m.Nombre);
    document.getElementById('repair-notice').hidden = !!(d.clientes.length && d.vehiculos.length && d.mecanicos.length);

    document.getElementById('reparaciones-body').innerHTML = d.items.map((o) => `<tr><td>#${esc(o.ID_OR)}</td><td>${esc(o.Fecha_Apertura)}</td><td>${o.Fecha_Cierre ? esc(o.Fecha_Cierre) : '—'}</td><td>${esc(o.Cliente)}</td><td>${esc(o.Marca + ' ' + o.Modelo)}</td><td>${esc(o.Mecanico)}</td><td>${esc(o.Diagnostico)}</td><td>${money(o.Costo_Total)}</td><td><form method="post" class="inline-form" data-api="api/reparaciones.php" data-redirect="reparaciones.html"><input type="hidden" name="action" value="reparacion_estado"><input type="hidden" name="id" value="${esc(o.ID_OR)}"><select name="estado" aria-label="Estado de orden #${esc(o.ID_OR)}">${ESTADOS.map((s) => `<option${o.Estado === s ? ' selected' : ''}>${esc(s)}</option>`).join('')}</select><button class="btn btn-small">Guardar</button></form></td></tr>`).join('');
    document.getElementById('empty').hidden = d.items.length > 0;
  } catch (e) {
    document.getElementById('repair-notice').hidden = false;
    document.getElementById('empty').hidden = false;
    showFlash('error', e.message);
  }
});
