document.addEventListener('DOMContentLoaded', async () => {
  const { esc, money, int, get, showFlash } = Portal;
  const params = new URLSearchParams(location.search);
  const q = (params.get('q') || '').trim();
  const editVin = params.get('edit') || '';
  const form = document.getElementById('vehiculo-form');
  document.getElementById('search-input').value = q;
  document.getElementById('clear-search').hidden = q === '';

  const tipoSel = form.elements.tipo_vehiculo;
  const garantiaField = document.getElementById('garantia-field');
  const toggleGarantia = () => {
    const aplica = tipoSel.value === 'Nuevo';
    garantiaField.hidden = !aplica;
    if (!aplica) form.elements.garantia.value = '';
  };
  tipoSel.addEventListener('change', toggleGarantia);

  const fill = (v) => {
    document.getElementById('form-title').textContent = 'Editar vehículo';
    form.elements.action.value = 'vehiculo_editar';
    form.elements.vin.readOnly = true;
    const map = { vin: 'VIN', marca: 'Marca', modelo: 'Modelo', anio: 'Anio', kilometraje: 'Kilometraje', tipo_vehiculo: 'Tipo_Vehiculo', precio: 'Precio', garantia: 'Garantia', estado: 'Estado' };
    Object.entries(map).forEach(([field, col]) => { form.elements[field].value = v[col] ?? (field === 'kilometraje' ? 0 : ''); });
    toggleGarantia();
    document.getElementById('estado-field').hidden = false;
    document.getElementById('form-submit').textContent = 'Guardar cambios';
    document.getElementById('cancel-edit').hidden = false;
  };

  const render = (items) => {
    document.getElementById('count').textContent = items.length;
    document.getElementById('vehiculos-body').innerHTML = items.map((v) => `<tr><td class="mono">${esc(v.VIN)}</td><td>${esc(v.Marca + ' ' + v.Modelo)}</td><td>${esc(v.Anio)}</td><td>${int(v.Kilometraje)}</td><td>${esc(v.Tipo_Vehiculo)}</td><td><span class="status status-${esc(String(v.Estado).replace(/ /g, '-').toLowerCase())}">${esc(v.Estado)}</span></td><td class="price">${money(v.Precio)}</td><td class="actions"><a class="text-link" href="vehiculos.html?edit=${encodeURIComponent(v.VIN)}">Editar</a><form method="post" data-api="api/vehiculos.php" data-redirect="vehiculos.html" data-confirm="¿Eliminar este vehículo? Si tiene ventas u órdenes asociadas, la base rechazará la operación."><input type="hidden" name="action" value="vehiculo_eliminar"><input type="hidden" name="vin" value="${esc(v.VIN)}"><button class="link-danger" type="submit">Eliminar</button></form></td></tr>`).join('');
    document.getElementById('empty').hidden = items.length > 0;
  };

  toggleGarantia();
  try {
    const d = await get('api/vehiculos.php', { q, edit: editVin });
    if (d.edit) fill(d.edit);
    render(d.items);
  } catch (e) { render([]); showFlash('error', e.message); }
});
