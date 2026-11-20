document.addEventListener('DOMContentLoaded', async () => {
  const { esc, money, int, get, showFlash } = Portal;
  const box = document.getElementById('ultimas');
  const empty = '<p class="empty-state">Todavía no hay ventas registradas.</p>';
  try {
    const d = await get('api/dashboard.php');
    document.querySelectorAll('[data-stat]').forEach((el) => { el.textContent = int(d[el.dataset.stat]); });
    box.innerHTML = !d.ultimas.length ? empty : `<div class="table-wrap"><table><thead><tr><th>N.º</th><th>Fecha</th><th>Cliente</th><th>Vehículo</th><th>Importe</th><th></th></tr></thead><tbody>${d.ultimas.map((v) => `<tr><td>#${esc(v.ID_Venta)}</td><td>${esc(v.Fecha)}</td><td>${esc(v.Cliente)}</td><td>${esc(v.Marca + ' ' + v.Modelo)}</td><td class="price">${money(v.Precio_Venta)}</td><td><a class="text-link" href="recibo.html?id=${encodeURIComponent(v.ID_Venta)}">Ver recibo</a></td></tr>`).join('')}</tbody></table></div>`;
  } catch (e) {
    document.querySelectorAll('[data-stat]').forEach((el) => { el.textContent = int(0); });
    box.innerHTML = empty;
    showFlash('error', e.message);
  }
});
