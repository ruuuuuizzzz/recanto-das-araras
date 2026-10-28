document.addEventListener('DOMContentLoaded', () => {
  const holder = document.getElementById('movement-chart');
  const dataNode = document.getElementById('dash-chart-data');
  if (!holder || !dataNode) return;
  let rows = [];
  try { rows = JSON.parse(dataNode.textContent); } catch (e) { holder.textContent = 'Gráfico indisponível.'; return; }
  if (!Array.isArray(rows) || !rows.length) { holder.textContent = 'Sem dados disponíveis.'; return; }
  const max = Math.max(1, ...rows.flatMap(r => [Number(r.visitantes)||0, Number(r.encomendas)||0]));
  rows.forEach(r => {
    const day = document.createElement('div'); day.className = 'chart-day';
    const bars = document.createElement('div'); bars.className = 'chart-bars';
    [['visit', 'visitantes'], ['package', 'encomendas']].forEach(([cls, key]) => {
      const val = Number(r[key]) || 0;
      const bar = document.createElement('div'); bar.className = 'chart-bar ' + cls;
      bar.style.height = Math.max(2, val / max * 150) + 'px';
      bar.title = (cls === 'visit' ? 'Visitantes: ' : 'Encomendas: ') + val;
      bars.appendChild(bar);
    });
    const label = document.createElement('span'); label.className = 'chart-label';
    const parts = String(r.dia).split('-'); label.textContent = parts.length === 3 ? parts[2] + '/' + parts[1] : String(r.dia);
    day.append(bars, label); holder.appendChild(day);
  });
});
