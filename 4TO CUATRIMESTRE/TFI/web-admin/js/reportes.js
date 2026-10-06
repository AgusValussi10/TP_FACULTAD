let dashboardChart = null;

async function renderReportes() {
  const area = document.getElementById('content-area');
  try {
    const d = await apiFetch('/reportes/dashboard');

    const prox = d.proximoPartido;
    const proxHtml = prox
      ? `<strong>${prox.rival}</strong> · ${formatFecha(prox.fecha)} ${prox.hora} · ${prox.categoria} · ${prox.lugar === 'local' ? '🏠 Local' : '✈️ Visitante'}`
      : '<span style="color:var(--text-muted)">Sin partidos programados</span>';

    area.innerHTML = `
      <div class="kpi-grid">
        <div class="kpi-card">
          <div class="kpi-icon verde">👥</div>
          <div><div class="kpi-value" style="color:var(--primary)">${d.totalJugadores}</div><div class="kpi-label">Jugadores activos</div></div>
        </div>
        <div class="kpi-card">
          <div class="kpi-icon naranja">💰</div>
          <div><div class="kpi-value" style="color:var(--warning)">${d.cuotasPendientes}</div><div class="kpi-label">Cuotas pendientes</div></div>
        </div>
        <div class="kpi-card">
          <div class="kpi-icon rojo">🛡️</div>
          <div><div class="kpi-value" style="color:var(--danger)">${d.jugadoresSinSeguro}</div><div class="kpi-label">Sin seguro vigente</div></div>
        </div>
        <div class="kpi-card">
          <div class="kpi-icon azul">⚽</div>
          <div><div class="kpi-label" style="font-weight:700;font-size:13px;color:var(--text);margin-bottom:4px">Próximo partido</div><div style="font-size:13px">${proxHtml}</div></div>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
        <div class="card">
          <div class="card-title">Morosidad por categoría</div>
          <div class="chart-container"><canvas id="chart-morosidad"></canvas></div>
        </div>
        <div class="card">
          <div class="card-title">Últimos partidos</div>
          <div class="table-wrap">
            ${renderTable(
              ['Categoría','Rival','Resultado','Fecha'],
              d.ultimosPartidos,
              p => `<tr>
                <td>${p.categoria}</td>
                <td>${p.rival}</td>
                <td><strong>${p.local ?? '—'} - ${p.visitante ?? '—'}</strong></td>
                <td>${formatFecha(p.fecha)}</td>
              </tr>`
            )}
          </div>
        </div>
      </div>`;

    if (dashboardChart) { dashboardChart.destroy(); dashboardChart = null; }
    const cats = d.morosidadPorCategoria || [];
    const ctx = document.getElementById('chart-morosidad');
    if (ctx && cats.length) {
      dashboardChart = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: cats.map(c => c.categoria),
          datasets: [{
            label: 'Morosos',
            data: cats.map(c => c.morosos),
            backgroundColor: '#4CAF50aa',
            borderColor: '#2d6e2d',
            borderWidth: 2,
            borderRadius: 6
          },{
            label: 'Total',
            data: cats.map(c => c.totalJugadores),
            backgroundColor: '#dde5dd88',
            borderColor: '#aaa',
            borderWidth: 1,
            borderRadius: 6
          }]
        },
        options: {
          responsive: true, maintainAspectRatio: false,
          plugins: { legend: { position: 'bottom' } },
          scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
      });
    }
  } catch (err) {
    area.innerHTML = `<div class="alert alert-warning">⚠️ ${err.message}</div>`;
  }
}
