document.addEventListener('DOMContentLoaded', async () => {
  const { esc, money, today, get, showFlash } = Portal;
  const form = document.getElementById('sale-form');
  const vehicle = form.querySelector('#vin');
  const price = form.querySelector('#precio_venta');
  form.querySelector('#fecha').value = today();

  if (vehicle && price) vehicle.addEventListener('change', () => {
    const option = vehicle.options[vehicle.selectedIndex];
    if (option && option.dataset.price && !price.value) price.value = option.dataset.price;
  });

  const metodo = form.querySelector('#metodo_pago');
  const credito = form.querySelector('#credito-fields');
  const cuotas = form.querySelector('#cuotas');
  const conRecargo = form.querySelector('#con_recargo');
  const recargoField = form.querySelector('#recargo-field');
  const recargo = form.querySelector('#recargo');
  const impuestos = form.querySelector('#impuestos');
  const resumen = form.querySelector('#credito-resumen');
  const num = (el) => parseFloat(el.value) || 0;

  const updateCredit = () => {
    const isCredit = metodo.value === 'Credito';
    credito.hidden = !isCredit;
    cuotas.required = isCredit;
    recargoField.hidden = !isCredit || conRecargo.value !== '1';
    recargo.required = isCredit && conRecargo.value === '1';
    if (recargoField.hidden) recargo.value = 0;
    if (!isCredit) { resumen.hidden = true; return; }
    const total = num(price) + num(impuestos) + num(recargo);
    const n = parseInt(cuotas.value, 10);
    resumen.hidden = !(total > 0);
    resumen.textContent = 'Total a financiar: ' + money(total) + (n > 0 ? ' · ' + n + ' cuota' + (n === 1 ? '' : 's') + ' de ' + money(total / n) : '');
  };
  [metodo, cuotas, conRecargo, recargo, impuestos, price].forEach((el) => { el.addEventListener('input', updateCredit); el.addEventListener('change', updateCredit); });
  updateCredit();

  const options = (rows, value, label, first) => `<option value="${first[0]}">${first[1]}</option>` + rows.map((r) => `<option value="${esc(r[value])}">${esc(label(r))}</option>`).join('');

  try {
    const d = await get('api/ventas.php');
    form.querySelector('#cliente').innerHTML = options(d.clientes, 'ID_Cliente', (c) => c.Nombre_Completo_Razon_Social, ['', 'Seleccionar cliente']);
    vehicle.innerHTML = '<option value="">Seleccionar vehículo</option>' + d.vehiculos.map((v) => `<option value="${esc(v.VIN)}" data-price="${esc(v.Precio)}">${esc(v.Marca + ' ' + v.Modelo + ' · ' + v.VIN + ' · ' + money(v.Precio))}</option>`).join('');
    form.querySelector('#vendedor').innerHTML = options(d.vendedores, 'ID_Vendedor', (a) => a.Nombre, ['', 'Seleccionar asesor']);
    form.querySelector('#id_fi').innerHTML = options(d.gerentes, 'ID_FI', (g) => g.Nombre, ['0', 'Sin asignar']);

    const incomplete = !d.clientes.length || !d.vehiculos.length || !d.vendedores.length;
    document.getElementById('sale-notice').hidden = !incomplete;
    document.getElementById('sale-submit').disabled = incomplete;

    document.getElementById('ventas-body').innerHTML = d.items.map((v) => `<tr><td>#${esc(v.ID_Venta)}</td><td>${esc(v.Fecha)}</td><td>${esc(v.Cliente)}</td><td>${esc(v.Marca + ' ' + v.Modelo)}</td><td>${esc(v.Vendedor)}</td><td>${v.Metodo_Pago === 'Credito' ? 'Crédito' + (v.Cuotas ? ' · ' + esc(v.Cuotas) + ' cuota' + (Number(v.Cuotas) === 1 ? '' : 's') : '') : 'Contado'}</td><td class="price">${money(parseFloat(v.Precio_Venta) + parseFloat(v.Impuestos) + parseFloat(v.Recargo || 0))}</td><td><a class="text-link" href="recibo.html?id=${encodeURIComponent(v.ID_Venta)}">Ver / imprimir</a></td></tr>`).join('');
    document.getElementById('empty').hidden = d.items.length > 0;
  } catch (e) {
    document.getElementById('sale-notice').hidden = false;
    document.getElementById('sale-submit').disabled = true;
    document.getElementById('empty').hidden = false;
    showFlash('error', e.message);
  }
});
