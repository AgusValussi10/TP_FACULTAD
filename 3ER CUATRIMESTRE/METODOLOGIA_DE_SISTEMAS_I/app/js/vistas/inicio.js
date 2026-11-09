// Inicio: resumen del alumno elegido (deuda, próximo vencimiento, inscripciones, avisos).
import { api } from '../api.js';
import { html, crudo, pesos, mensaje } from '../util.js';
import { estadoInstalacion } from '../instalar.js';

export async function render(main, ctx) {
  const alumno = ctx.alumno();
  if (!alumno) {
    main.innerHTML = html`<div class="tarjeta"><h1>Sin alumnos vinculados</h1>
      <p>Tu cuenta no tiene alumnos asociados. Comunicate con la administración de la escuela.</p></div>`;
    return;
  }
  const r = await api('inicio.php?alumno_id=' + encodeURIComponent(alumno.id));
  if (!r.success) {
    main.innerHTML = '';
    mensaje(main, r.message);
    return;
  }
  const { deuda, pagos_en_curso: enCurso, deportes, servicios, notificaciones } = r.data;

  let estadoDeuda;
  if (deuda.vencido > 0) {
    estadoDeuda = html`<span class="badge badge-rojo">Vencido: ${pesos(deuda.vencido)}</span>`;
  } else if (deuda.pendiente > 0) {
    estadoDeuda = html`<span class="badge badge-amarillo">Al día, con saldo a pagar</span>`;
  } else {
    estadoDeuda = html`<span class="badge badge-verde">Sin deuda</span>`;
  }

  const sugerirInstalar = ['disponible', 'ios'].includes(estadoInstalacion())
    ? html`<div class="alerta alerta-info">📲 Podés instalar la app en tu teléfono desde <a href="#/cuenta">Cuenta</a>.</div>`
    : '';

  main.innerHTML = html`
    <h1>Hola, ${ctx.estado.usuario.nombre.split(' ')[0]}</h1>
    <p class="sub">${alumno.nombre}${alumno.curso ? ' · ' + alumno.curso : ''}</p>
    ${crudo(sugerirInstalar)}

    <section class="tarjeta" aria-labelledby="t-deuda">
      <h2 id="t-deuda"><span aria-hidden="true">💳</span> Saldo a pagar</h2>
      <p class="cifra">${pesos(deuda.pendiente)}</p>
      <p>${crudo(estadoDeuda)}</p>
      ${crudo(deuda.proximo_vencimiento_ar
        ? html`<p class="sub">Próximo vencimiento: <strong>${deuda.proximo_vencimiento_ar}</strong></p>` : '')}
      ${crudo(deuda.en_revision > 0
        ? html`<p class="sub">En revisión por la escuela: <strong>${pesos(deuda.en_revision)}</strong></p>` : '')}
      ${crudo(enCurso > 0 ? html`<p class="sub">Pagos en curso: ${enCurso}</p>` : '')}
    </section>

    <section class="tarjeta" aria-labelledby="t-insc">
      <h2 id="t-insc"><span aria-hidden="true">🏅</span> Inscripciones activas</h2>
      ${crudo(deportes.length || servicios.length ? html`<ul class="lista">
        ${deportes.map((d) => crudo(html`<li><strong>${d.nombre}</strong><br>
          <span class="sub">${d.horario}${d.profesor ? ' · Prof. ' + d.profesor : ''}</span></li>`))}
        ${servicios.map((s) => crudo(html`<li><strong>${s.servicio}</strong> · ${s.periodo}
          ${s.detalle ? crudo(html`<br><span class="sub">${s.detalle}</span>`) : ''}</li>`))}
      </ul>` : html`<p class="vacio">No tiene deportes ni servicios este mes.</p>`)}
    </section>

    <section class="tarjeta" aria-labelledby="t-not">
      <h2 id="t-not"><span aria-hidden="true">🔔</span> Últimos avisos</h2>
      ${crudo(notificaciones.length ? html`<ul class="lista">
        ${notificaciones.map((n) => crudo(html`<li>${n.mensaje}<br><span class="sub">${n.fecha}</span></li>`))}
      </ul>` : html`<p class="vacio">No hay avisos.</p>`)}
    </section>`;
}
