/**
 * Pruebas E2E de RFG04 (regla RN03): edad mínima para admitir en Nivel Inicial.
 * Automatizan los casos de prueba CP-RFG04-01 y CP-RFG04-02 tal como los hace
 * el administrador en el navegador.
 *
 * Cada prueba crea su propia solicitud (con un nombre único) por el mismo
 * endpoint que usa el formulario de la landing, así no depende de datos previos.
 */

const ADMIN = { usuario: 'admin', password: 'admin', rol: 'Docentes / Personal / Autoridades' };

/** Letras al azar: guardar.php solo acepta letras en el nombre. */
function letras(cantidad) {
  const abc = 'abcdefghijklmnopqrstuvwxyz';
  let texto = '';
  for (let i = 0; i < cantidad; i++) {
    texto += abc[Math.floor(Math.random() * abc.length)];
  }
  return texto;
}

/** Condición previa: una solicitud pendiente de Nivel Inicial. */
function crearSolicitud(nombre, fechaNacimiento) {
  cy.request({
    method: 'POST',
    url: '/inscripciones/guardar.php',
    form: true,
    body: {
      nombre_alumno: nombre,
      apellido_alumno: 'Cypress',
      fecha_nacimiento: fechaNacimiento,
      nivel_educativo: 'Inicial',
      nombre_tutor: 'Tutor Cypress',
      telefono: '3624000000',
      email: 'cypress@test.com',
      comentarios: 'Creada por Cypress',
    },
  }).its('body.success').should('eq', true);
}

function ingresarComoAdmin() {
  cy.request({ method: 'POST', url: '/auth/login.php', form: true, body: ADMIN })
    .its('body.success').should('eq', true);
  cy.visit('/portals/portal_admin.php');
}

/** Fila de la tabla de inscripciones de una solicitud ("Cypress, <nombre>"). */
function filaDe(nombre) {
  return cy.contains('tr', `Cypress, ${nombre}`);
}

describe('RFG04 · Admisión con validación de edad mínima (Nivel Inicial)', () => {
  it('CP-RFG04-01: no permite admitir a un alumno de 2 años', () => {
    const nombre = 'Menor' + letras(6);
    crearSolicitud(nombre, '2024-03-15');
    ingresarComoAdmin();

    // Paso 2: la solicitud está pendiente
    filaDe(nombre).as('fila').invoke('text').should('match', /pendiente/i);

    // Paso 3: el modal avisa y bloquea el botón
    cy.get('@fila').within(() => cy.contains('button', 'Admitir').click());
    cy.get('#overlay-admitir').should('have.class', 'open');
    cy.get('#modal-requisito')
      .should('contain', 'No cumple la edad mínima para Nivel Inicial')
      .and('contain', 'tiene 2 años');
    cy.get('#btn-confirmar').should('be.disabled');

    cy.get('@fila').invoke('attr', 'id').then((idFila) => {
      const id = idFila.replace('row-', '');

      // Criterio 2: aunque alguien saltee el modal, el servidor rechaza la admisión
      cy.request({
        method: 'POST',
        url: '/inscripciones/admitir.php',
        form: true,
        body: {
          id,
          usuario: 'cypress.' + nombre.toLowerCase(),
          password: 'clave123',
          nombre: nombre + ' Cypress',
          nivel_anterior_confirmado: '0',
          curso_id: '0',
        },
      }).its('body').should((respuesta) => {
        expect(respuesta.success).to.eq(false);
        expect(respuesta.message).to.contain('edad mínima');
      });

      // Paso 4: después de recargar sigue pendiente
      cy.reload();
      cy.get(`#estado-${id}`).invoke('text').should('match', /pendiente/i);
    });
  });

  it('CP-RFG04-02: admite a un alumno de 4 años', () => {
    const nombre = 'Mayor' + letras(6);
    crearSolicitud(nombre, '2022-05-10');
    ingresarComoAdmin();

    filaDe(nombre).within(() => cy.contains('button', 'Admitir').click());
    cy.get('#modal-requisito').should('contain', 'Cumple la edad mínima');
    cy.get('#btn-confirmar').should('not.be.disabled');

    cy.get('#modal-password').type('clave123');
    cy.get('#btn-confirmar').click();

    cy.get('#overlay-admitir').should('not.have.class', 'open');
    filaDe(nombre).invoke('text').should('match', /admitido/i);
  });
});
