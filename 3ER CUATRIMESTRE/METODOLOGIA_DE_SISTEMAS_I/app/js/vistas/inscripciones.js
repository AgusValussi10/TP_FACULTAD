// Inscripciones (RFGm09/10/11): mis inscripciones; deportes (hasta 2, con cupo y sin choque
// de horario); comedor y transporte por recorrido, mes a mes. El alumno solo consulta.
import { api } from '../api.js';
import { html, crudo, pesos, mensaje } from '../util.js';

function valor(v) {
  return v === null || v === undefined ? 'Sin valor cargado' : pesos(v) + ' por mes';
}

function resumen(datos) {
  const deportes = datos.deportes.filter((d) => d.inscripto);
  const filas = [
    ...deportes.map((d) => crudo(html`<li class="item"><span><strong>${d.nombre}</strong><br>
      <span class="sub">${d.horario}${d.profesor ? ' · Prof. ' + d.profesor : ''}</span></span>
      <span class="item-der">${valor(d.valor)}</span></li>`)),
    ...datos.servicios.map((s) => crudo(html`<li class="item"><span><strong>${s.servicio === 'comedor' ? 'Comedor' : 'Transporte'}</strong> · ${s.periodo}
      ${crudo(s.recorrido ? html`<br><span class="sub">${s.recorrido}</span>` : '')}</span>
      <span class="item-der">${valor(s.valor)}</span></li>`)),
  ];
  return html`<section class="tarjeta" aria-labelledby="t-mis">
    <h2 id="t-mis"><span aria-hidden="true">📋</span> Mis inscripciones</h2>
    ${crudo(filas.length ? html`<ul class="lista">${filas}</ul>` : html`<p class="vacio">No tiene deportes ni servicios reservados.</p>`)}
    <p class="sub">Los deportes se cobran desde el mes siguiente a la inscripción; comedor y transporte, en la factura del mes reservado.</p>
  </section>`;
}

function tarjetaDeporte(d, puedeModificar) {
  let accion = '';
  if (puedeModificar && d.inscripto) {
    accion = html`<button type="button" class="btn btn-sec" data-deporte="${d.id}" data-accion="baja">Dar de baja</button>`;
  } else if (puedeModificar && d.puede) {
    accion = html`<button type="button" class="btn" data-deporte="${d.id}" data-accion="inscribir">Inscribir</button>`;
  }
  const estado = d.inscripto
    ? html`<span class="badge badge-verde">Inscripto</span>`
    : (d.motivo ? html`<span class="badge badge-gris">${d.motivo}</span>` : '');
  return html`<li class="deporte">
    <div class="cuota-cabecera"><strong>${d.nombre}</strong>${crudo(estado)}</div>
    <p class="sub">${d.horario}${d.profesor ? ' · Prof. ' + d.profesor : ''}</p>
    <div class="fila"><span>Valor</span><span>${valor(d.valor)}</span></div>
    <div class="fila"><span>Cupo</span><span>${d.disponibles} de ${d.cupo} lugares libres</span></div>
    ${crudo(accion)}
  </li>`;
}

function tarjetaMes(m, datos, puedeModificar) {
  const comedor = datos.servicios.find((s) => s.servicio === 'comedor' && s.mes === m.mes && s.anio === m.anio);
  const transporte = datos.servicios.find((s) => s.servicio === 'transporte' && s.mes === m.mes && s.anio === m.anio);
  const clave = `${m.anio}-${m.mes}`;
  const opciones = datos.recorridos.map((r) => crudo(html`<option value="${r.id}"
    ${crudo(transporte && transporte.recorrido_id === r.id ? 'selected' : '')}>${r.nombre} · ${valor(r.valor)}</option>`));

  const botonesComedor = !puedeModificar ? '' : (comedor
    ? html`<button type="button" class="btn btn-sec" data-servicio="comedor" data-accion="cancelar" data-mes="${clave}">Cancelar comedor</button>`
    : html`<button type="button" class="btn" data-servicio="comedor" data-accion="reservar" data-mes="${clave}">Reservar comedor</button>`);
  const controlTransporte = !puedeModificar ? '' : html`
    <div class="campo"><label for="rec-${clave}">Recorrido del transporte</label>
      <select id="rec-${clave}"><option value="">Sin transporte</option>${opciones}</select></div>
    <button type="button" class="btn" data-servicio="transporte" data-accion="reservar" data-mes="${clave}">
      ${transporte ? 'Cambiar recorrido' : 'Reservar transporte'}</button>
    ${crudo(transporte ? html`<button type="button" class="btn btn-sec" data-servicio="transporte" data-accion="cancelar" data-mes="${clave}">Cancelar transporte</button>` : '')}`;

  return html`<li class="mes-servicios">
    <div class="cuota-cabecera"><strong>${m.periodo}</strong><span class="sub">Hasta el ${m.plazo}</span></div>
    <div class="fila"><span>🍽️ Comedor (${valor(datos.valor_comedor)})</span>
      <span>${comedor ? crudo(html`<span class="badge badge-verde">Reservado</span>`) : 'No'}</span></div>
    ${crudo(botonesComedor)}
    <div class="fila"><span>🚌 Transporte</span>
      <span>${transporte ? crudo(html`<span class="badge badge-verde">${transporte.recorrido || 'Reservado'}</span>`) : 'No'}</span></div>
    ${crudo(controlTransporte)}
  </li>`;
}

export async function render(main, ctx) {
  const alumno = ctx.alumno();
  if (!alumno) {
    main.innerHTML = html`<p class="vacio">No hay alumnos vinculados.</p>`;
    return;
  }
  const r = await api('inscripciones.php?alumno_id=' + encodeURIComponent(alumno.id));
  if (!r.success) {
    main.innerHTML = '';
    mensaje(main, r.message);
    return;
  }
  const datos = r.data;
  const puede = datos.puede_modificar;

  main.innerHTML = html`
    <h1>Inscripciones</h1>
    <p class="sub">${alumno.nombre}${alumno.curso ? ' · ' + alumno.curso : ''}</p>
    <div id="msg" aria-live="polite"></div>
    ${crudo(resumen(datos))}

    <section aria-labelledby="t-dep">
      <h2 id="t-dep">Deportes <span class="sub">(hasta ${datos.max_deportes} a la vez)</span></h2>
      <ul class="lista-tarjetas">${datos.deportes.map((d) => crudo(tarjetaDeporte(d, puede)))}</ul>
    </section>

    <section aria-labelledby="t-serv">
      <h2 id="t-serv">Comedor y transporte</h2>
      <p class="sub">Se reserva mes a mes, hasta el día ${datos.dia_limite} del mes anterior.</p>
      ${crudo(datos.meses.length
        ? html`<ul class="lista-tarjetas">${datos.meses.map((m) => crudo(tarjetaMes(m, datos, puede)))}</ul>`
        : html`<p class="vacio tarjeta">No hay meses abiertos para reservar.</p>`)}
    </section>`;

  if (!puede) return;
  const msg = document.getElementById('msg');
  main.addEventListener('click', async (e) => {
    const btn = e.target.closest('button[data-accion]');
    if (!btn) return;
    let ruta;
    const datosPost = { alumno_id: String(alumno.id), accion: btn.dataset.accion };
    if (btn.dataset.deporte) {
      if (btn.dataset.accion === 'baja' && !confirm('¿Dar de baja este deporte?')) return;
      ruta = 'deporte.php';
      datosPost.deporte_id = btn.dataset.deporte;
    } else {
      const [anio, mes] = btn.dataset.mes.split('-');
      ruta = 'servicio.php';
      Object.assign(datosPost, { servicio: btn.dataset.servicio, mes, anio });
      if (btn.dataset.servicio === 'transporte' && btn.dataset.accion === 'reservar') {
        datosPost.recorrido_id = document.getElementById('rec-' + btn.dataset.mes).value;
        if (!datosPost.recorrido_id) {
          mensaje(msg, 'Elegí un recorrido para reservar el transporte.');
          msg.scrollIntoView({ block: 'center' });
          return;
        }
      }
    }
    btn.disabled = true;
    const res = await api(ruta, datosPost);
    if (res.success) {
      ctx.estado.avisoInscripciones = res.message;
      ctx.refrescar();
      return;
    }
    btn.disabled = false;
    mensaje(msg, res.message);
    msg.scrollIntoView({ block: 'center' });
  });

  if (ctx.estado.avisoInscripciones) {
    mensaje(msg, ctx.estado.avisoInscripciones, 'ok');
    ctx.estado.avisoInscripciones = '';
  }
}
