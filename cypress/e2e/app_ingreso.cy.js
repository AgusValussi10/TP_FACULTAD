/**
 * Parte 3 — App móvil (PWA), Paso 2 (RFGm01/RFGm02): ingreso, selector de hijo,
 * cuenta, cambio de contraseña, cierre de sesión y sesión vencida.
 *
 * Requiere la base educar_test con seed_parte3.php (laura.martinez tiene dos hijos:
 * ana.garcia y matias.vega). Ver 4TO CUATRIMESTRE/METODOLOGIA_DE_SISTEMAS_II/pruebas-app/README.md.
 *
 * Tiempos de espera altos SOLO en este spec: php -S en Windows atiende de a un pedido
 * y un ingreso puede tardar 10–25 s (en Render no pasa).
 */

const ESPERA = { defaultCommandTimeout: 25000, pageLoadTimeout: 90000, requestTimeout: 25000 };

function ingresar(usuario, password) {
  cy.visit('/app/');
  cy.get('#usuario').type(usuario);
  cy.get('#password').type(password, { log: false });
  cy.contains('button', 'Ingresar').click();
}

/** La página no puede ser más ancha que la pantalla del celular. */
function sinScrollHorizontal() {
  cy.document().then((doc) => {
    expect(doc.documentElement.scrollWidth, 'ancho de la página').to.be.at.most(390);
  });
}

describe('App · ingreso y cuenta (RFGm01/RFGm02)', ESPERA, () => {
  beforeEach(() => cy.viewport(390, 844));

  it('primera visita: lo tipeado no se borra cuando el service worker toma el control', () => {
    // Partir de una visita "nueva": sin service worker registrado.
    cy.visit('/app/');
    cy.window().then((win) => win.navigator.serviceWorker.getRegistrations()
      .then((regs) => Promise.all(regs.map((r) => r.unregister()))));
    cy.reload();
    cy.get('#usuario').type('laura.martinez');
    cy.wait(4000); // margen para que el service worker se instale y tome el control
    cy.get('#usuario').should('have.value', 'laura.martinez');
    cy.window().then((win) => win.navigator.serviceWorker.getRegistration('/app/'))
      .should('not.be.undefined');
  });

  it('docente rechazado con mensaje claro', () => {
    ingresar('maria.rodriguez', 'docente123');
    // El subtítulo del login ya dice "familias y alumnos": se busca el mensaje de rechazo.
    cy.get('.alerta-error').should('contain', 'ingresan desde la web');
    sinScrollHorizontal();
  });

  it('padre con dos hijos: inicio, cambio de hijo, cuenta, validación de contraseña y salir', () => {
    ingresar('laura.martinez', 'padre123');
    cy.contains('Saldo a pagar');
    cy.contains('Natación');
    cy.get('#selector-hijo').should('be.visible');
    cy.get('#sel-alumno option').should('have.length', 2);
    sinScrollHorizontal();

    cy.get('#sel-alumno option').eq(1).then((op) => cy.get('#sel-alumno').select(op.val()));
    cy.contains('Fútbol');

    cy.contains('nav a', 'Cuenta').click();
    cy.contains('Cambiar contraseña');
    sinScrollHorizontal();
    cy.get('#actual').type('padre123', { log: false });
    cy.get('#nueva').type('corta');
    cy.get('#confirmacion').type('corta');
    cy.contains('button', 'Guardar contraseña').click();
    cy.contains('al menos 8');

    cy.contains('button', 'Cerrar sesión').click();
    cy.get('#usuario').should('be.visible');
    cy.request({ url: '/app/api/sesion.php', failOnStatusCode: false }).its('status').should('eq', 401);
  });

  it('alumno: entra a lo suyo, sin selector de hijo', () => {
    ingresar('ana.garcia', 'alumno123');
    cy.contains('Saldo a pagar');
    cy.get('#selector-hijo').should('not.be.visible');
  });

  it('sesión vencida: vuelve al login con aviso', () => {
    ingresar('ana.garcia', 'alumno123');
    cy.contains('Saldo a pagar');
    cy.clearCookies();
    cy.contains('nav a', 'Cuenta').click();
    cy.contains('nav a', 'Inicio').click();
    cy.contains('Tu sesión venció');
    cy.get('#usuario').should('be.visible');
  });
});
