const { initializeApp, getApps, cert } = require('firebase-admin/app');
const { getAuth } = require('firebase-admin/auth');
const path = require('path');
const fs   = require('fs');

const keyPath = path.join(__dirname, 'serviceAccountKey.json');

if (!fs.existsSync(keyPath)) {
  console.warn('[firebase-admin] serviceAccountKey.json no encontrado — funciones de Firebase deshabilitadas');
  module.exports = null;
} else {
  const serviceAccount = require(keyPath);
  if (!getApps().length) {
    initializeApp({ credential: cert(serviceAccount) });
  }
  module.exports = { auth: getAuth() };
}
