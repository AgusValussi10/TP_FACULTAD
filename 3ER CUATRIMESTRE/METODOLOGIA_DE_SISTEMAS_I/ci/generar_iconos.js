// Genera los íconos de la PWA (app/icons/) a partir de assets/logo.png.
// Uso (desde la carpeta del sistema, con sharp instalado vía `npm install`):
//   node ci/generar_iconos.js
// - icon-192/512: logo centrado sobre blanco, con un margen chico.
// - maskable-192/512: logo dentro de la zona segura (80 % central) sobre blanco (el logo no tiene transparencia),
//   porque Android recorta el ícono en círculo o "squircle".
// - apple-touch-icon (180): iOS no usa el manifest para el ícono de inicio.
// Ojo: el logo original mide 262×231, así que en 512 se ve algo suavizado.
const path = require('path');
const sharp = require('sharp');

const raiz = path.join(__dirname, '..');
const logo = path.join(raiz, 'assets', 'logo.png');
const salida = path.join(raiz, 'app', 'icons');

async function icono(nombre, tamanio, fondo, proporcion) {
  const lado = Math.round(tamanio * proporcion);
  const imagen = await sharp(logo).resize(lado, lado, { fit: 'contain', background: fondo }).toBuffer();
  await sharp({ create: { width: tamanio, height: tamanio, channels: 4, background: fondo } })
    .composite([{ input: imagen, gravity: 'center' }])
    .png()
    .toFile(path.join(salida, nombre));
  console.log('ok', nombre);
}

(async () => {
  const blanco = '#FFFFFF';
  const marca = '#FFFFFF'; // el logo no tiene transparencia: con otro fondo quedaría un recuadro blanco
  await icono('icon-192.png', 192, blanco, 0.86);
  await icono('icon-512.png', 512, blanco, 0.86);
  await icono('maskable-192.png', 192, marca, 0.62);
  await icono('maskable-512.png', 512, marca, 0.62);
  await icono('apple-touch-icon.png', 180, blanco, 0.86);
})().catch((e) => {
  console.error(e);
  process.exit(1);
});
