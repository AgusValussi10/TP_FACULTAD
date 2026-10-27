// Configuración de Cypress: pruebas E2E del Sistema de Gestión "Educar para Transformar".
// La app tiene que estar corriendo en local (php -S localhost:8080 en la carpeta del sistema).
const { defineConfig } = require('cypress');

module.exports = defineConfig({
  e2e: {
    baseUrl: 'http://localhost:8080',
    specPattern: 'cypress/e2e/**/*.cy.js',
    supportFile: false,
    video: false,
    screenshotOnRunFailure: true,
    viewportWidth: 1366,
    viewportHeight: 768,
  },
});
