// Agrega consultas de prueba al historial de un usuario existente.
// Uso: node scripts/seed-historial-usuario.js [email] [cantidad]
require('dotenv').config({ path: require('path').join(__dirname, '../.env') });
const pool = require('../db');

const EMAIL = process.argv[2] || 'avalussi.facultad@gmail.com';
const CANTIDAD = parseInt(process.argv[3], 10) || 20;

async function main() {
  const [[usuario]] = await pool.query('SELECT id FROM usuarios WHERE email = ?', [EMAIL]);
  if (!usuario) {
    console.error(`No existe un usuario con el email ${EMAIL}`);
    process.exit(1);
  }

  const [billeteras] = await pool.query('SELECT id FROM billeteras WHERE activa = 1 ORDER BY id');
  if (billeteras.length === 0) {
    console.error('No hay billeteras activas cargadas en la base');
    process.exit(1);
  }

  const filas = [];
  for (let i = 0; i < CANTIDAD; i++) {
    const monto = Math.round((Math.random() * 900 + 100) * 100) / 100;       // 100–1000 BRL
    const mejorTasa = Math.round((Math.random() * 100 + 950) * 10000) / 10000; // ~950–1050 ARS/BRL
    const totalArs = Math.round(monto * mejorTasa * 100) / 100;
    const billetera = billeteras[i % billeteras.length];
    const diasAtras = i;                                                     // escalonadas, una por día
    filas.push([usuario.id, monto, 'BRL', billetera.id, mejorTasa, totalArs, diasAtras]);
  }

  await pool.query(
    `INSERT INTO historial_consultas
       (usuario_id, monto, moneda_destino, mejor_billetera_id, mejor_tasa, total_ars, consultado_en)
     VALUES ${filas.map(() => '(?, ?, ?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL ? DAY))').join(', ')}`,
    filas.flat()
  );

  console.log(`${CANTIDAD} consultas agregadas al historial de ${EMAIL} (usuario_id=${usuario.id})`);
  process.exit(0);
}

main().catch(err => {
  console.error(err);
  process.exit(1);
});
