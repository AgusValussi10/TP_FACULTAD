/**
 * Parte 3 — App móvil (PWA), Paso 3 (RFGm03/RFGm04): cuotas, facturas con filtro de
 * fechas, detalle y PDF, e historial de deuda por ítem.
 *
 * Requiere la base educar_test con seed_parte3.php (ana.garcia, hija de laura.martinez,
 * tiene una cuota vencida, una por vencer, una pagada y facturas de los últimos 3 meses).
 *
 * Tiempos de espera altos SOLO en este spec: php -S en Windows atiende de a un pedido.
 */

const ESPERA = { defaultCommandTimeout: 25000, pageLoadTimeout: 90000, requestTimeout: 25000 };

function ingresar(usuario, password) {
  cy.visit('/app/');
  cy.get('#usuario').type(usuario);
  cy.get('#password').type(password, { log: false });
  cy.contains('button', 'Ingresar').click();
  cy.contains('Saldo a pagar');
}

function sinScrollHorizontal() {
  cy.document().then((doc) => {
    expect(doc.documentElement.scrollWidth, 'ancho de la página').to.be.at.most(390);
  });
}

/** El enlace devuelve un PDF (con la sesión del navegador). */
function esPdf(selector) {
  cy.get(selector).first().invoke('attr', 'href').then((href) => {
    cy.request(href).its('headers').its('content-type').should('eq', 'application/pdf');
  });
}

describe('App · cuotas, facturas y deuda (RFGm03/RFGm04)', ESPERA, () => {
  beforeEach(() => cy.viewport(390, 844));

  it('cuotas: vencidas en rojo, por vencer en amarillo y pagadas con recibo', () => {
    ingresar('laura.martinez', 'padre123');
    cy.contains('nav a', 'Cuotas').click();
    cy.contains('h1', 'Cuotas');
    cy.get('.cuota-impaga .badge-rojo').should('exist');
    cy.get('.cuota-proxima_a_vencer .badge-amarillo').should('exist');
    sinScrollHorizontal();
    cy.contains('summary', 'Pagadas').click();
    esPdf('a:contains("Recibo")');
  });

  it('facturas: vencida marcada, detalle con pagos y PDF', () => {
    ingresar('laura.martinez', 'padre123');
    cy.contains('nav a', 'Facturas').click();
    cy.get('.factura').should('have.length.at.least', 1);
    cy.get('.factura .badge-rojo').contains('Vencida');
    sinScrollHorizontal();
    cy.get('.factura summary').first().click();
    cy.contains('h3', 'Pagos');
    esPdf('a:contains("Descargar PDF")');
  });

  it('facturas: el filtro de fechas acota el listado y rechaza un rango invertido', () => {
    ingresar('laura.martinez', 'padre123');
    cy.contains('nav a', 'Facturas').click();
    cy.get('.factura').its('length').then((todas) => {
      // Solo el mes actual: tiene que haber menos facturas que con el rango por defecto.
      const hoy = new Date();
      const mes = `${hoy.getFullYear()}-${String(hoy.getMonth() + 1).padStart(2, '0')}`;
      cy.get('#desde').clear().type(mes);
      cy.get('#hasta').clear().type(mes);
      cy.contains('button', 'Ver').click();
      cy.get('.factura').should('have.length', 1).and('have.length.lessThan', todas);
    });
    cy.get('#desde').clear().type('2026-12');
    cy.get('#hasta').clear().type('2026-01');
    cy.contains('button', 'Ver').click();
    cy.contains('no puede ser posterior');
  });

  it('historial de deuda por ítem: cada deporte aparte y desglose por mes', () => {
    ingresar('laura.martinez', 'padre123');
    cy.contains('nav a', 'Facturas').click();
    cy.get('.factura').should('exist');
    cy.contains('[role="tab"]', 'Deuda por concepto').click();
    cy.contains('h3', 'Deporte · Natación');
    cy.contains('h3', 'Comedor');
    cy.contains('summary', 'Por mes').click();
    cy.get('.mes-deuda').should('have.length.at.least', 1);
    sinScrollHorizontal();
  });

  it('alumno: ve sus cuotas pero no el botón de recibo', () => {
    ingresar('ana.garcia', 'alumno123');
    cy.contains('nav a', 'Cuotas').click();
    cy.contains('summary', 'Pagadas').click();
    cy.get('.cuota-pagada').should('exist');
    cy.contains('a', 'Recibo').should('not.exist');
  });
});
