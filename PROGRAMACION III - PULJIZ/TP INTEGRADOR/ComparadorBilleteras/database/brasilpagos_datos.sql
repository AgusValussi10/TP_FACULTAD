-- =============================================================
--  BrasilPagos — Datos iniciales (seed)
--  Ejecutar DESPUÉS de brasilpagos_schema.sql
-- =============================================================

USE brasilpagos;

-- =============================================================
--  admin_usuarios (las dos únicas cuentas del panel admin)
--  Contraseña de ambas: "admin" (hasheada con bcrypt, salt rounds 10)
-- =============================================================
INSERT INTO admin_usuarios (usuario, password_hash, nombre_visible) VALUES
('fabri', '$2b$10$eAzB06w8R0upl.ZFyHBO9eGUo5JyfHLlwN8t4U7S017fnMVX7IlQq', 'Fabri'),
('agus',  '$2b$10$hwEMzMmKrk5ZTyGMOO6Nt.ccxecd49SfUpCxcHJRg3DlCNkjANo9e', 'Agus');

-- =============================================================
--  billeteras
-- =============================================================
INSERT INTO billeteras (id, nombre, iniciales, color_hex, descripcion, url_oficial, rating_promedio, cantidad_resenas) VALUES
(1,  'Mercado Pago',  'MP', '#00b1ea', 'La billetera más usada de Argentina. Sin comisión para PIX.',         'https://mercadopago.com.ar',                    4.8, 142300),
(2,  'Ualá',          'UA', '#7c3aed', 'Tarjeta y billetera virtual con cuenta gratis.',                      'https://uala.com.ar',                           4.5,  58200),
(3,  'Bimo',          'BI', '#f59e0b', 'Billetera del Banco Macro con foco en pagos rápidos.',                'https://bimo.com.ar',                           4.2,  12400),
(4,  'Prex',          'PX', '#06b6d4', 'Tarjeta Mastercard prepaga con app integrada.',                       'https://prexcard.com.ar',                       4.3,   9800),
(5,  'Naranja X',     'NX', '#f97316', 'Cuenta digital de Grupo Naranja con beneficios.',                     'https://naranjax.com',                          4.1,  21000),
(6,  'Brubank',       'BB', '#3b82f6', 'Banco 100% digital con cuenta gratuita.',                             'https://brubank.com',                           4.6,  34500),
(7,  'Personal Pay',  'PP', '#8b5cf6', 'Billetera virtual de Telecom Personal.',                              'https://personalpay.com.ar',                    4.0,   7600),
(8,  'Lemon Cash',    'LC', '#84cc16', 'Billetera cripto y fiat con tarjeta Visa.',                           'https://lemon.me',                              4.4,  18900),
(9,  'Modo',          'MO', '#ec4899', 'Plataforma de pagos de los bancos argentinos.',                       'https://modo.com.ar',                           3.9,   5200),
(10, 'Cuenta DNI',    'CD', '#0ea5e9', 'Billetera del Banco Provincia para todos los argentinos.',            'https://cuentadni.bancoprovincia.com.ar',        4.2,  29100);

-- =============================================================
--  billetera_paises
-- =============================================================
INSERT INTO billetera_paises (billetera_id, codigo_pais, metodo_pago) VALUES
-- Mercado Pago
(1, 'AR', 'Transferencia'), (1, 'BR', 'PIX'), (1, 'UY', 'Transferencia'), (1, 'MX', 'Transferencia'),
-- Ualá
(2, 'AR', 'Transferencia'), (2, 'BR', 'PIX'), (2, 'CO', 'Transferencia'),
-- Bimo
(3, 'AR', 'Transferencia'), (3, 'BR', 'PIX'),
-- Prex
(4, 'AR', 'Transferencia'), (4, 'BR', 'PIX'), (4, 'UY', 'Transferencia'),
-- Naranja X
(5, 'AR', 'Transferencia'), (5, 'BR', 'PIX'),
-- Brubank
(6, 'AR', 'Transferencia'), (6, 'BR', 'PIX'),
-- Personal Pay
(7, 'AR', 'Transferencia'), (7, 'BR', 'PIX'),
-- Lemon Cash
(8, 'AR', 'Transferencia'), (8, 'BR', 'PIX'),
-- Modo
(9, 'AR', 'Transferencia'), (9, 'BR', 'PIX'),
-- Cuenta DNI
(10, 'AR', 'Transferencia'), (10, 'BR', 'PIX');

-- =============================================================
--  billetera_monedas
-- =============================================================
INSERT INTO billetera_monedas (billetera_id, moneda) VALUES
(1, 'ARS'), (1, 'BRL'), (1, 'USD'),
(2, 'ARS'), (2, 'BRL'), (2, 'USD'),
(3, 'ARS'), (3, 'BRL'),
(4, 'ARS'), (4, 'BRL'), (4, 'USD'),
(5, 'ARS'), (5, 'BRL'),
(6, 'ARS'), (6, 'BRL'), (6, 'USD'),
(7, 'ARS'), (7, 'BRL'),
(8, 'ARS'), (8, 'BRL'), (8, 'USD'),
(9, 'ARS'), (9, 'BRL'),
(10, 'ARS'), (10, 'BRL');

-- =============================================================
--  billetera_condiciones
-- =============================================================
INSERT INTO billetera_condiciones (billetera_id, comision_pct, limite_diario_brl, limite_mensual_brl, tiempo_estimado, detalle_comision) VALUES
(1,  0.00, 5000.00, 50000.00, 'Instantáneo',   'Sin comisión para transferencias vía PIX. Aplica el tipo de cambio oficial del momento.'),
(2,  0.00, 3000.00, 30000.00, 'Instantáneo',   'Sin comisión para PIX. El tipo de cambio se aplica al momento del envío.'),
(3,  0.00, 2500.00, 25000.00, 'Instantáneo',   'Sin comisión. Requiere cuenta Macro verificada.'),
(4,  0.50, 3000.00, 30000.00, 'Instantáneo',   'Comisión del 0,5% sobre el monto enviado en BRL.'),
(5,  0.00, 4000.00, 40000.00, 'Instantáneo',   'Sin comisión para transferencias PIX internacionales.'),
(6,  0.00, 3500.00, 35000.00, 'Instantáneo',   'Sin comisión. Tipo de cambio actualizado en tiempo real.'),
(7,  0.50, 2000.00, 20000.00, 'Instantáneo',   'Comisión del 0,5%. Disponible solo para clientes Personal.'),
(8,  1.00, 5000.00, 50000.00, 'Hasta 5 min',   'Comisión del 1% sobre el monto en BRL. Conversión vía USDT.'),
(9,  0.00, 3000.00, 30000.00, 'Instantáneo',   'Sin comisión. Requiere cuenta bancaria verificada en Argentina.'),
(10, 0.00, 2000.00, 20000.00, 'Instantáneo',   'Sin comisión. Disponible para mayores de 13 años con DNI argentino.');

-- =============================================================
--  billetera_pros_contras
-- =============================================================
INSERT INTO billetera_pros_contras (billetera_id, tipo, descripcion, orden) VALUES
-- Mercado Pago
(1, 'pro',    'Sin comisión para PIX',         1),
(1, 'pro',    'Límite diario alto',             2),
(1, 'pro',    'Disponible 24/7',                3),
(1, 'contra', 'Requiere cuenta verificada',     1),
(1, 'contra', 'Tipo de cambio variable',        2),
-- Ualá
(2, 'pro',    'Sin comisión',                   1),
(2, 'pro',    'App muy intuitiva',               2),
(2, 'pro',    'Tarjeta Mastercard incluida',    3),
(2, 'contra', 'Límite diario más bajo',          1),
(2, 'contra', 'Solo 3 países destino',          2),
-- Bimo
(3, 'pro',    'Sin comisión',                   1),
(3, 'pro',    'Respaldo del Banco Macro',        2),
(3, 'pro',    'Transferencia instantánea',       3),
(3, 'contra', 'Requiere cuenta en Banco Macro', 1),
(3, 'contra', 'Límite diario bajo',              2),
-- Prex
(4, 'pro',    'Tarjeta Mastercard física',       1),
(4, 'pro',    'Disponible en Uruguay',           2),
(4, 'pro',    'App moderna',                     3),
(4, 'contra', 'Comisión del 0,5%',              1),
(4, 'contra', 'Menos conocida en Argentina',    2),
-- Naranja X
(5, 'pro',    'Sin comisión',                   1),
(5, 'pro',    'Buen límite diario',              2),
(5, 'pro',    'Beneficios con comercios',        3),
(5, 'contra', 'Solo Brasil como destino',        1),
(5, 'contra', 'Requiere cuenta activa Naranja', 2),
-- Brubank
(6, 'pro',    'Sin comisión',                   1),
(6, 'pro',    'Banco regulado por BCRA',         2),
(6, 'pro',    'Cotización en tiempo real',       3),
(6, 'contra', 'Solo para usuarios Brubank',      1),
(6, 'contra', 'Proceso de alta lleva días',      2),
-- Personal Pay
(7, 'pro',    'Integrado con servicios Personal',1),
(7, 'pro',    'Transferencia instantánea',       2),
(7, 'contra', 'Solo para clientes de Telecom',  1),
(7, 'contra', 'Comisión del 0,5%',              2),
(7, 'contra', 'Límite diario bajo',              3),
-- Lemon Cash
(8, 'pro',    'Límite diario alto',              1),
(8, 'pro',    'Tarjeta Visa incluida',           2),
(8, 'pro',    'Soporte cripto y fiat',           3),
(8, 'contra', 'Comisión del 1%',                1),
(8, 'contra', 'Tiempo de hasta 5 minutos',       2),
(8, 'contra', 'Conversión vía cripto puede variar',3),
-- Modo
(9, 'pro',    'Sin comisión',                   1),
(9, 'pro',    'Respaldo de bancos argentinos',   2),
(9, 'pro',    'Transferencia instantánea',       3),
(9, 'contra', 'Requiere cuenta bancaria activa', 1),
(9, 'contra', 'Menos funcionalidades',           2),
-- Cuenta DNI
(10, 'pro',    'Sin comisión',                  1),
(10, 'pro',    'Disponible desde los 13 años',  2),
(10, 'pro',    'Respaldo Banco Provincia',       3),
(10, 'contra', 'Límite diario muy bajo',         1),
(10, 'contra', 'Solo para provincia de Buenos Aires',2);

-- =============================================================
--  billetera_requisitos
-- =============================================================
INSERT INTO billetera_requisitos (billetera_id, descripcion, orden) VALUES
(1,  'DNI argentino',                     1), (1,  'Cuenta de Mercado Pago activa', 2), (1,  'Verificación de identidad',      3),
(2,  'DNI argentino',                     1), (2,  'Mayor de 13 años',              2), (2,  'Celular con chip activo',         3),
(3,  'Cuenta en Banco Macro',             1), (3,  'DNI argentino',                 2), (3,  'Verificación biométrica',         3),
(4,  'DNI argentino',                     1), (4,  'Mayor de 18 años',              2), (4,  'Dirección de email válida',       3),
(5,  'DNI argentino',                     1), (5,  'Cuenta Naranja X activa',        2), (5,  'Email verificado',                3),
(6,  'DNI argentino',                     1), (6,  'Mayor de 18 años',              2), (6,  'Selfie de validación',            3),
(7,  'Línea de celular Personal activa',  1), (7,  'DNI argentino',                 2),
(8,  'DNI argentino',                     1), (8,  'Mayor de 18 años',              2), (8,  'Verificación KYC completa',       3),
(9,  'Cuenta bancaria argentina',         1), (9,  'DNI argentino',                 2), (9,  'Cuenta verificada en el banco',   3),
(10, 'DNI argentino',                     1), (10, 'Mayor de 13 años',              2), (10, 'Domicilio en Argentina',          3);

-- =============================================================
--  cotizaciones (tasas actuales + historial simulado)
--  tasa = cuántos ARS cuesta 1 BRL en esa billetera
-- =============================================================
INSERT INTO cotizaciones (billetera_id, moneda_origen, moneda_destino, tasa, registrado_en) VALUES
-- Tasas del 30/06/2026 (actuales)
(1,  'ARS', 'BRL',  970.46, '2026-06-30 09:00:00'),
(2,  'ARS', 'BRL',  984.36, '2026-06-30 09:00:00'),
(3,  'ARS', 'BRL',  988.90, '2026-06-30 09:00:00'),
(4,  'ARS', 'BRL',  997.40, '2026-06-30 09:00:00'),
(5,  'ARS', 'BRL',  993.20, '2026-06-30 09:00:00'),
(6,  'ARS', 'BRL', 1000.46, '2026-06-30 09:00:00'),
(7,  'ARS', 'BRL', 1005.80, '2026-06-30 09:00:00'),
(8,  'ARS', 'BRL', 1010.50, '2026-06-30 09:00:00'),
(9,  'ARS', 'BRL', 1015.30, '2026-06-30 09:00:00'),
(10, 'ARS', 'BRL', 1020.75, '2026-06-30 09:00:00'),
-- Tasas del 29/06/2026
(1,  'ARS', 'BRL',  965.20, '2026-06-29 09:00:00'),
(2,  'ARS', 'BRL',  979.10, '2026-06-29 09:00:00'),
(6,  'ARS', 'BRL',  995.80, '2026-06-29 09:00:00'),
-- Tasas del 28/06/2026
(1,  'ARS', 'BRL',  960.00, '2026-06-28 09:00:00'),
(2,  'ARS', 'BRL',  975.50, '2026-06-28 09:00:00'),
(6,  'ARS', 'BRL',  990.30, '2026-06-28 09:00:00');

-- =============================================================
--  usuarios (datos de prueba — passwords son hashes ficticios)
-- =============================================================
INSERT INTO usuarios (id, nombre, email, password_hash, pais_residencia, moneda_base, email_verificado) VALUES
(1, 'Juan Pérez',    'juan@email.com',    '$2b$10$hashficticio1111111111111111111111111111111', 'AR', 'ARS', 1),
(2, 'María García',  'maria@email.com',   '$2b$10$hashficticio2222222222222222222222222222222', 'AR', 'ARS', 1),
(3, 'Carlos López',  'carlos@email.com',  '$2b$10$hashficticio3333333333333333333333333333333', 'AR', 'ARS', 0);

-- =============================================================
--  resenas
-- =============================================================
INSERT INTO resenas (billetera_id, usuario_id, autor_nombre, calificacion, comentario, fecha_resena) VALUES
(1, NULL, 'Lucía M.',     5, 'Muy fácil de usar, el PIX llegó en segundos.',                          '2025-06-15'),
(1, NULL, 'Tomás R.',     4, 'La cotización es buena, aunque varía bastante.',                         '2025-05-20'),
(2, NULL, 'Sofía G.',     5, 'Usé Ualá para pagar en Brasil, llegó al instante.',                      '2025-06-10'),
(2, NULL, 'Martín L.',    4, 'Buena app, el límite diario queda corto para viajes largos.',             '2025-04-05'),
(3, NULL, 'Diego F.',     4, 'Funciona bien para pagos chicos en Brasil.',                              '2025-05-12'),
(3, NULL, 'Ana P.',       4, 'Confiable, aunque el límite es bajo para compras grandes.',               '2025-03-28'),
(4, NULL, 'Camila V.',    4, 'La tarjeta física es muy útil para Brasil.',                              '2025-06-18'),
(4, NULL, 'Nicolás S.',   5, 'La comisión es mínima y el servicio es excelente.',                       '2025-05-30'),
(5, NULL, 'Valentina H.', 4, 'Muy buena para viajes a Brasil, sin costo extra.',                        '2025-06-22'),
(5, NULL, 'Rodrigo B.',   4, 'Fácil de usar, aunque la app a veces tarda.',                             '2025-04-14'),
(6, NULL, 'Julia C.',     5, 'El mejor banco digital de Argentina. PIX instantáneo.',                   '2025-06-25'),
(6, NULL, 'Ignacio M.',   4, 'Muy confiable. El alta es lenta pero después funciona genial.',           '2025-05-08'),
(7, NULL, 'Federico A.',  4, 'Práctica si ya usás Personal. La comisión no molesta.',                   '2025-05-15'),
(7, NULL, 'Laura T.',     4, 'Funciona bien, aunque el límite es muy bajo.',                            '2025-03-10'),
(8, NULL, 'Matías E.',    4, 'El límite alto es ideal para viajes largos. La comisión es aceptable.',   '2025-06-05'),
(8, NULL, 'Florencia K.', 5, 'Me encanta la app, aunque el PIX tardó 3 minutos.',                      '2025-04-20'),
(9, NULL, 'Pablo N.',     4, 'Confiable por el respaldo bancario. La app puede mejorar.',               '2025-05-25'),
(9, NULL, 'Gabriela O.',  3, 'Funciona, aunque la experiencia de usuario no es la mejor.',              '2025-04-30'),
(10, NULL,'Belén Q.',     4, 'Ideal para pagos pequeños en Brasil, sin comisión.',                      '2025-06-12'),
(10, NULL,'Sebastián W.', 4, 'El límite bajo es la única contra. Por lo demás, perfecto.',              '2025-03-22');

-- =============================================================
--  alertas (datos de prueba)
-- =============================================================
INSERT INTO alertas (usuario_id, billetera_id, moneda_origen, moneda_destino, condicion, valor_objetivo, activa) VALUES
(1, 1, 'ARS', 'BRL', 'supera',  975.00, 1),
(1, 2, 'ARS', 'BRL', 'baja_de', 960.00, 0),
(2, 6, 'ARS', 'BRL', 'supera',  990.00, 1);

-- =============================================================
--  historial_consultas (datos de prueba)
-- =============================================================
INSERT INTO historial_consultas (usuario_id, monto, moneda_destino, mejor_billetera_id, mejor_tasa, total_ars, consultado_en) VALUES
(1, 500.00,  'BRL', 1,  970.46,  485230.00, '2026-06-30 08:30:00'),
(1, 1000.00, 'BRL', 1,  970.46,  970460.00, '2026-06-29 14:15:00'),
(1, 250.00,  'BRL', 1,  965.20,  241300.00, '2026-06-28 10:00:00'),
(2, 800.00,  'BRL', 1,  970.46,  776368.00, '2026-06-30 07:45:00');

-- =============================================================
--  favoritos (datos de prueba)
-- =============================================================
INSERT INTO favoritos (usuario_id, billetera_id) VALUES
(1, 1), (1, 2),
(2, 1), (2, 6);

-- =============================================================
--  Nuevas billeteras: AstroPay, belo, Cocos Capital
-- =============================================================
INSERT INTO billeteras (id, nombre, iniciales, color_hex, descripcion, url_oficial, rating_promedio, cantidad_resenas) VALUES
(11, 'AstroPay',     'AP', '#1a1a2e', 'Monedero virtual internacional para pagos en línea y PIX.',               'https://astropay.com',   4.1,  8900),
(12, 'belo',         'BE', '#6c63ff', 'Billetera cripto y fiat con PIX en reales desde ARS.',                    'https://belo.app',       4.3, 14200),
(13, 'Cocos Capital','CC', '#0d9488', 'Plataforma de inversiones con billetera para pagos internacionales.',      'https://cocos.capital',  4.0,  5300);

INSERT INTO billetera_condiciones (billetera_id, comision_pct, limite_diario_brl, limite_mensual_brl, tiempo_estimado, detalle_comision) VALUES
(11, 1.50,  3000.00, 30000.00, 'Instantáneo',   'Comisión del 1,5% sobre el monto en BRL.'),
(12, 0.00,  4000.00, 40000.00, 'Instantáneo',   'Sin comisión para transferencias PIX.'),
(13, 0.50,  2500.00, 25000.00, 'Hasta 10 min',  'Comisión del 0,5% aplicada al monto convertido.');

INSERT INTO billetera_paises (billetera_id, codigo_pais, metodo_pago) VALUES
(11, 'AR', 'Transferencia'), (11, 'BR', 'PIX'),
(12, 'AR', 'Transferencia'), (12, 'BR', 'PIX'),
(13, 'AR', 'Transferencia'), (13, 'BR', 'PIX');

INSERT INTO billetera_monedas (billetera_id, moneda) VALUES
(11, 'BRL'), (11, 'ARS'), (11, 'USD'),
(12, 'BRL'), (12, 'ARS'), (12, 'USD'),
(13, 'BRL'), (13, 'ARS');

INSERT INTO billetera_pros_contras (billetera_id, tipo, descripcion, orden) VALUES
(11, 'pro',    'Acepta múltiples monedas',           1),
(11, 'pro',    'Disponible en más de 150 países',    2),
(11, 'contra', 'Comisión del 1,5%',                  1),
(11, 'contra', 'Menos conocida localmente',          2),
(12, 'pro',    'Sin comisión para PIX',              1),
(12, 'pro',    'Integra cripto y fiat',              2),
(12, 'pro',    'App muy intuitiva',                  3),
(12, 'contra', 'Requiere verificación KYC',          1),
(13, 'pro',    'Excelente para inversores',          1),
(13, 'pro',    'Sin comisión sobre rendimientos',    2),
(13, 'contra', 'Proceso de alta complejo',           1),
(13, 'contra', 'Tiempo de hasta 10 minutos',         2);

INSERT INTO billetera_requisitos (billetera_id, descripcion, orden) VALUES
(11, 'Email válido',            1),
(11, 'Verificación de identidad', 2),
(12, 'DNI argentino',           1),
(12, 'Mayor de 18 años',        2),
(12, 'KYC completo',            3),
(13, 'DNI argentino',           1),
(13, 'Mayor de 18 años',        2),
(13, 'Cuenta verificada',       3);

INSERT INTO resenas (billetera_id, usuario_id, autor_nombre, calificacion, comentario, fecha_resena) VALUES
(11, NULL, 'Marcos T.',   4, 'Muy útil para pagos internacionales, aunque la comisión molesta un poco.', '2025-05-20'),
(11, NULL, 'Carla M.',    4, 'Fácil de usar y disponible en muchos países.',                             '2025-04-10'),
(12, NULL, 'Luciano P.',  5, 'La mejor billetera cripto para PIX. Llegó al instante.',                   '2025-06-15'),
(12, NULL, 'Agustina R.', 4, 'Sin comisión y muy rápida. El KYC tardó pero valió la pena.',              '2025-05-02'),
(13, NULL, 'Esteban V.',  4, 'Ideal si ya usás Cocos para inversiones. El proceso de alta es largo.',    '2025-06-08'),
(13, NULL, 'Natalia C.',  4, 'Buena tasa, aunque el tiempo de acreditación puede llegar a 10 minutos.',  '2025-04-25');

INSERT INTO cotizaciones (billetera_id, moneda_origen, moneda_destino, tasa) VALUES
(11, 'ARS', 'BRL', 1008.50),
(12, 'ARS', 'BRL',  995.20),
(13, 'ARS', 'BRL', 1002.80);
