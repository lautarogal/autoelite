document.addEventListener('DOMContentLoaded', async () => {
  const { esc, get, showFlash } = Portal;
  const params = new URLSearchParams(location.search);
  const q = (params.get('q') || '').trim();
  const editId = params.get('edit') || '';
  const form = document.getElementById('cliente-form');
  document.getElementById('search-input').value = q;
  document.getElementById('clear-search').hidden = q === '';

  const fill = (c) => {
    document.getElementById('form-title').textContent = 'Editar cliente';
    form.elements.action.value = 'cliente_editar';
    form.elements.id.disabled = false; form.elements.id.value = c.ID_Cliente;
    const map = { nombre: 'Nombre_Completo_Razon_Social', tipo_cliente: 'Tipo_Cliente', dni: 'DNI', telefono: 'Telefono', correo: 'Correo_Electronico', direccion: 'Direccion', historial: 'Historial_Crediticio', referencias: 'Referencias_Bancarias' };
    Object.entries(map).forEach(([field, col]) => { form.elements[field].value = c[col] ?? ''; });
    document.getElementById('form-submit').textContent = 'Guardar cambios';
    document.getElementById('cancel-edit').hidden = false;
  };

  const render = (items) => {
    document.getElementById('count').textContent = items.length;
    document.getElementById('clientes-body').innerHTML = items.map((c) => `<tr><td>${esc(c.ID_Cliente)}</td><td>${esc(c.Nombre_Completo_Razon_Social)}</td><td>${esc(c.DNI)}</td><td>${esc(c.Tipo_Cliente)}</td><td>${esc(c.Telefono)}</td><td>${esc(c.Correo_Electronico)}</td><td class="actions"><a class="text-link" href="clientes.html?edit=${encodeURIComponent(c.ID_Cliente)}">Editar</a><form method="post" data-api="api/clientes.php" data-redirect="clientes.html" data-confirm="¿Eliminar este cliente? La base puede impedirlo si tiene ventas u órdenes asociadas."><input type="hidden" name="action" value="cliente_eliminar"><input type="hidden" name="id" value="${esc(c.ID_Cliente)}"><button class="link-danger" type="submit">Eliminar</button></form></td></tr>`).join('');
    document.getElementById('empty').hidden = items.length > 0;
  };

  try {
    const d = await get('api/clientes.php', { q, edit: editId });
    if (d.edit) fill(d.edit);
    render(d.items);
  } catch (e) { render([]); showFlash('error', e.message); }
});
