document.addEventListener('DOMContentLoaded', async () => {
  const { esc, get, showFlash } = Portal;
  const cols = ['ID', 'Nombre', 'DNI', 'Edad', 'Direccion', 'Horario_Trabajo'];
  const tables = {
    vendedores: ['ID_Vendedor', ...cols.slice(1)],
    gerentes: ['ID_FI', ...cols.slice(1)],
    asesores: ['ID_Asesor_Srv', ...cols.slice(1)],
    mecanicos: ['ID_Mecanico', ...cols.slice(1)],
  };
  let data = {};
  try { data = await get('api/empleados.php'); } catch (e) { showFlash('error', e.message); }
  Object.entries(tables).forEach(([key, cols]) => {
    const rows = data[key] || [];
    document.getElementById(`${key}-body`).innerHTML = rows.map((r) => `<tr>${cols.map((c) => `<td>${esc(r[c])}</td>`).join('')}</tr>`).join('');
    document.getElementById(`${key}-empty`).hidden = rows.length > 0;
  });
});
