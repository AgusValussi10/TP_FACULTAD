# Funcionalidades — Estado de implementación

Las 20 funcionalidades fueron definidas en la etapa de Arquitectura de Información (Módulo 3) a partir del card sorting con 5 participantes. Este documento cruza lo planificado contra lo implementado en la app final.

---

## Resumen

| Estado | Cantidad |
|---|---|
| Implementada | 13 |
| Implementada parcialmente | 3 |
| Roadmap (no incluida en MVP) | 4 |
| **Total planificadas** | **20** |

---

## Core — Comparación de cotizaciones

| # | Funcionalidad | Estado | Dónde |
|---|---|---|---|
| 1 | Comparación en tiempo real de tasas de cambio | ✅ | `ResultsScreen` — ranking de billeteras desde la API en tiempo real |
| 2 | Calculadora de conversión ARS → BRL vía PIX | ✅ | `HomeScreen` — teclado numérico custom, destino Brasil PIX fijo |
| 3 | Visualización de diferencial de ahorro entre opciones | ✅ | `ResultsScreen` — muestra ahorro en ARS y % vs. la peor opción |
| 4 | Histórico de cotizaciones por billetera | ✅ | `WalletProfileScreen` — gráfico de barras desde `GET /api/cotizaciones/historial` |
| 5 | Alertas de mejor momento para pagar | ✅ | `AlertsScreen` + polling cada 60s en `AuthContext`. Cuando se cumple la condición pausa la alerta automáticamente |

---

## Gestión de billeteras y preferencias

| # | Funcionalidad | Estado | Dónde |
|---|---|---|---|
| 6 | Agregar/eliminar billeteras virtuales a comparar | ⚠️ Parcial | El admin puede mostrar/ocultar billeteras desde el panel (`PATCH /api/admin/billeteras/:id/toggle`). El usuario final no agrega billeteras propias — las 13 son el catálogo fijo del MVP |
| 7 | Configuración de billeteras favoritas / más usadas | ✅ | `FavoritesScreen` + estrella en `WalletProfileScreen`. Persiste en BD (`favoritos`) |
| 8 | Selector de país / moneda de destino | ⚠️ Parcial | Fijo en Brasil / BRL por decisión de alcance del MVP (app acotada a pagos vía PIX en Brasil) |
| 9 | Configuración de notificaciones push | ⚠️ Parcial | `SettingsScreen` tiene los toggles de UI. Las notificaciones push reales cuando la app está cerrada están en roadmap; el sistema de alertas actual funciona con polling en-app |

---

## Usuario y autenticación

| # | Funcionalidad | Estado | Dónde |
|---|---|---|---|
| 10 | Registro / Login (email o social login) | ✅ | `LoginScreen` + `RegisterScreen` (email/contraseña) + Google Sign-In. Firebase Auth con verificación de email obligatoria |
| 11 | Perfil de usuario con preferencias | ✅ | `ProfileScreen` + `EditProfileScreen` + `SettingsScreen` |
| 12 | Historial de consultas realizadas | ✅ | `HistoryScreen` (completo) + últimas 3 consultas en `HomeScreen` y `ProfileScreen` |

---

## Información y contenido

| # | Funcionalidad | Estado | Dónde |
|---|---|---|---|
| 13 | Tutorial / onboarding para nuevos usuarios | ✅ | `OnboardingScreen` — carrusel de 3 slides con animaciones |
| 14 | FAQ sobre uso de billeteras y PIX | ❌ Roadmap | No incluida en el MVP |
| 15 | Información sobre comisiones por billetera | ✅ | `WalletProfileScreen` — comisión, límites, tiempo estimado, pros/contras, requisitos, países |
| 16 | Noticias / actualizaciones sobre cambios en billeteras | ❌ Roadmap | No incluida en el MVP |

---

## Funcionalidades avanzadas — Escalabilidad

| # | Funcionalidad | Estado | Dónde |
|---|---|---|---|
| 17 | Modo offline (última cotización guardada) | ❌ Roadmap | No incluida en el MVP |
| 18 | Compartir comparación con otros usuarios | ✅ | `ResultsScreen` — botón "Compartir" usa la Share API nativa del sistema |
| 19 | Widget para pantalla de inicio con mejor opción del momento | ❌ Roadmap | Requiere extensión nativa de Android/iOS, fuera del alcance del MVP |
| 20 | Sistema de reseñas / experiencias de usuarios por billetera | ✅ | `WalletProfileScreen` — lectura y creación de reseñas. `POST /api/resenas` recalcula el rating promedio en la BD automáticamente |

---

## Funcionalidades extra (no planificadas, agregadas en desarrollo)

Las siguientes funcionalidades surgieron durante la implementación y no estaban en la lista original de 20:

| Funcionalidad | Dónde |
|---|---|
| Comparador lado a lado de 2 billeteras | `WalletCompareScreen` — tabla comparativa con picker de billeteras activas |
| Panel de administración web | `http://localhost:3000/admin` — Dashboard, Cotizaciones, Billeteras, Usuarios (CRUD + sync Firebase) |
| Autenticación con Firebase Admin SDK | `server/firebaseAdmin.js` — sincronización bidireccional MySQL ↔ Firebase |
| Directorio de billeteras con buscador | `WalletsScreen` — lista con búsqueda en tiempo real, rating y países |
