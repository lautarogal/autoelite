document.addEventListener('DOMContentLoaded', async () => {
  const { esc, money, get, showFlash } = Portal;
  const root = document.getElementById('recibo-root');
  const notFound = '<section class="panel"><h2>Recibo no encontrado</h2><p>La venta solicitada no existe o no se pudo consultar.</p><a class="btn" href="ventas.html">Volver a ventas</a></section>';
  let r = null;
  try { r = (await get('api/recibo.php', { id: new URLSearchParams(location.search).get('id') || 0 })).item; }
  catch (e) { showFlash('error', e.message); }
  if (!r) { root.innerHTML = notFound; return; }
  const recargo = parseFloat(r.Recargo || 0);
  const total = parseFloat(r.Precio_Venta) + parseFloat(r.Impuestos) + recargo;
  const cuotas = parseInt(r.Cuotas, 10) || 0;
  const credito = r.Metodo_Pago === 'Credito';
  root.innerHTML = `<div class="receipt-toolbar"><a class="btn btn-secondary" href="ventas.html">← Volver a ventas</a><button class="btn" type="button" onclick="window.print()">Imprimir / Guardar como PDF</button></div><article class="recibo-box"><header class="recibo-head"><div><div class="recibo-logo">AutoElite <span>Motors</span></div><div class="recibo-sub">Concesionaria · Buenos Aires, Argentina</div></div><div class="recibo-title"><h3>RECIBO INTERNO</h3><p>OPERACIÓN DE VENTA</p></div></header><div class="receipt-meta"><span>RECIBO N.º <strong>${String(r.ID_Venta).padStart(8, '0')}</strong></span><span>Fecha: <strong>${esc(r.Fecha)}</strong></span></div><h2 class="recibo-cliente">${esc(r.Cliente)}</h2><p class="recibo-linea">Vehículo: ${esc(r.Marca + ' ' + r.Modelo)} · Año ${esc(r.Anio || 'No informado')}</p><p class="recibo-linea">Patente: ${esc(r.VIN)}</p><p class="recibo-linea">Método de pago: ${credito ? 'Crédito' + (cuotas ? ' en ' + cuotas + ' cuota' + (cuotas === 1 ? '' : 's') + ' de ' + money(total / cuotas) : '') + (recargo > 0 ? ' (con recargo)' : ' (sin recargo)') : 'Contado'}</p><p class="recibo-linea">Vendedor responsable: ${esc(r.Vendedor)}</p><div class="receipt-totals"><div><span>Precio de venta</span><strong>${money(r.Precio_Venta)}</strong></div><div><span>Impuestos</span><strong>${money(r.Impuestos)}</strong></div>${credito && recargo > 0 ? `<div><span>Recargo por financiación</span><strong>${money(recargo)}</strong></div>` : ''}<div class="grand-total"><span>TOTAL</span><strong>${money(total)}</strong></div></div><p class="receipt-disclaimer">Documento interno de constancia de operación. No constituye factura fiscal ni comprobante fiscal autorizado.</p><div class="recibo-firmas"><div>Firma del vendedor</div><div>Firma del cliente</div></div></article>`;
});
