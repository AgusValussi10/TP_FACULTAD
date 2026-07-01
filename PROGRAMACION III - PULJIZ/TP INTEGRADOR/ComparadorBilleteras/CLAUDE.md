# CLAUDE.md — BrasilPagos

App mobile (React Native + Expo SDK 54) para usuarios argentinos que viajan a Brasil. Compara cotizaciones ARS→BRL de billeteras virtuales argentinas para pagos vía PIX.

---

## Criterios de evaluación del TP

| Criterio | Pts | Estado |
|---|---|---|
| 1. Modelo de datos (tablas, relaciones, normalización, scripts) | 25 | ✅ Completo — 13 billeteras |
| 2. API REST y lógica de negocio (endpoints, validaciones, errores) | 30 | ✅ Completo |
| 3. UI e integración con la API | 20 | ✅ Completo — app conectada a la API |
| 4. Operaciones maestro-detalle | 10 | ✅ BD + UI conectada a API |
| 5. Seguridad y control de acceso (auth, JWT, protección de rutas) | 10 | ✅ Completo |
| 6. Documentación técnica | 5 | ✅ Completo — README.md |

---

## Stack técnico

### Frontend (app mobile)
| Tecnología | Versión |
|---|---|
| React Native | 0.81.5 |
| Expo SDK | 54 |
| React | 19.1.0 |
| React Navigation | 6.x |
| Firebase Auth | email/contraseña + Google Sign-In |

### Backend (API REST)
| Tecnología | Versión |
|---|---|
| Node.js | 22.x |
| Express | 4.x |
| MySQL | 8.x |
| mysql2 | driver con pool de conexiones |
| JWT (jsonwebtoken) | autenticación stateless |
| bcryptjs | hash de contraseñas |

---

## Estructura del proyecto

```
ComparadorBilleteras/
├── src/
│   ├── screens/
│   │   ├── onboarding/    SplashScreen, OnboardingScreen, LoadingSplashScreen
│   │   ├── auth/          LoginScreen, RegisterScreen, ForgotPasswordScreen, EmailVerificationScreen
│   │   ├── home/          HomeScreen
│   │   ├── results/       ResultsScreen, EmptyResultsScreen, LoadingResultsScreen
│   │   ├── wallets/       WalletsScreen, WalletDetailScreen, WalletProfileScreen, WalletCompareScreen
│   │   ├── alerts/        AlertsScreen, CreateAlertScreen, PushNotificationScreen
│   │   ├── profile/       ProfileScreen, EditProfileScreen, SettingsScreen, FavoritesScreen, HistoryScreen
│   │   └── error/         ErrorScreen
│   ├── components/        BottomNav.js (pill activo + anim. suave), NumericKeyboard.js, modals/ExternalRedirectModal.js
│   ├── navigation/        AppNavigator.js
│   ├── config/            firebase.js
│   ├── context/           AuthContext.js  ← guarda apiToken + puente Firebase↔backend
│   ├── services/
│   │   └── api.js         ← capa HTTP: todas las funciones fetch a la API REST
│   └── data/              wallets.js (WALLET_META + WALLETS — 13 billeteras, fallback local)
├── server/                ← API REST Node.js + Express
│   ├── index.js           entrada, monta Express
│   ├── db.js              pool de conexiones MySQL
│   ├── .env               credenciales (no commitear)
│   ├── middleware/
│   │   └── auth.js        verifica JWT en header Authorization: Bearer
│   ├── routes/
│   │   ├── auth.js        POST /register, POST /login, GET /me
│   │   ├── cotizaciones.js GET /?monto=, GET /historial, POST / (admin)
│   │   ├── billeteras.js  GET /, GET /:id
│   │   ├── alertas.js     GET /, POST /, PATCH /:id, DELETE /:id
│   │   ├── historial.js   GET /, POST /
│   │   ├── favoritos.js   GET /, POST /, DELETE /:billetera_id
│   │   └── admin.js       rutas admin (ver abajo)
│   ├── firebaseAdmin.js   inicializa Firebase Admin SDK (requiere serviceAccountKey.json)
│   └── admin/
│       └── index.html     panel web — tabs: Dashboard, Cotizaciones, Billeteras, Usuarios
├── database/
│   ├── brasilpagos_schema.sql   CREATE DATABASE + 11 tablas + FK + índices
│   └── brasilpagos_datos.sql    seed: 13 billeteras (incl. AstroPay, belo, Cocos Capital)
├── App.js
└── android/
```

---

## Base de datos MySQL

**Base de datos:** `brasilpagos`

### Tablas y relaciones maestro-detalle

```
usuarios
  └── alertas (1:N)
  └── historial_consultas (1:N)
  └── favoritos (1:N)

billeteras  ← MAESTRO principal
  └── billetera_paises (1:N)       países destino soportados
  └── billetera_monedas (1:N)      monedas aceptadas
  └── billetera_condiciones (1:1)  límites y comisiones
  └── billetera_pros_contras (1:N) pros y contras
  └── billetera_requisitos (1:N)   requisitos de apertura
  └── cotizaciones (1:N)           historial de tasas ARS/BRL
  └── resenas (1:N)                opiniones de usuarios
```

**VIEW `cotizaciones_actuales`:** devuelve la última tasa registrada por billetera, ordenada de mejor a peor. La API la consulta directamente.

### Comandos MySQL útiles

```sql
-- Ver ranking actual
SELECT nombre, tasa, comision_pct FROM cotizaciones_actuales;

-- Actualizar tasas manualmente (reemplazar valores)
INSERT INTO cotizaciones (billetera_id, moneda_origen, moneda_destino, tasa, registrado_en) VALUES
(1, 'ARS', 'BRL', 970.46, NOW()),  -- Mercado Pago
(2, 'ARS', 'BRL', 984.36, NOW()),  -- Ualá
-- ... resto de billeteras
```

---

## API REST

**Base URL:** `http://localhost:3000`
**Panel admin:** `http://localhost:3000/admin` — tabs: Dashboard · Cotizaciones · Billeteras (toggle visible/oculta) · Usuarios (CRUD + Firebase sync)

### Endpoints públicos (sin token)

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/api/cotizaciones?monto=500` | Ranking de billeteras para el monto dado |
| GET | `/api/cotizaciones/historial?billetera_id=1` | Historial de tasas de una billetera |
| GET | `/api/billeteras` | Listado de todas las billeteras |
| GET | `/api/billeteras/:id` | Detalle completo de una billetera |
| POST | `/api/auth/register` | Crear cuenta `{ nombre, email, password }` |
| POST | `/api/auth/login` | Login → devuelve JWT `{ token, usuario }` |
| POST | `/api/auth/firebase-login` | Intercambia Firebase ID token por JWT propio `{ idToken }` — crea usuario MySQL si no existe. Usa Firebase Admin SDK si hay serviceAccountKey.json, sino verifica vía API pública de Google |

### Endpoints protegidos (requieren `Authorization: Bearer <token>`)

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/api/auth/me` | Datos del usuario autenticado |
| GET | `/api/alertas` | Alertas del usuario |
| POST | `/api/alertas` | Crear alerta `{ billetera_id, condicion, valor_objetivo }` |
| PATCH | `/api/alertas/:id` | Activar/pausar alerta `{ activa: true/false }` |
| DELETE | `/api/alertas/:id` | Eliminar alerta |
| GET | `/api/historial` | Historial de consultas del usuario |
| POST | `/api/historial` | Guardar consulta `{ monto, moneda_destino, mejor_billetera_id, mejor_tasa, total_ars }` |
| GET | `/api/favoritos` | Billeteras favoritas del usuario (solo activas) |
| POST | `/api/favoritos` | Agregar favorito `{ billetera_id }` — INSERT IGNORE (no duplica) |
| DELETE | `/api/favoritos/:billetera_id` | Quitar favorito |
| POST | `/api/resenas` | Crear reseña `{ billetera_id, calificacion (1-5), comentario }` — inserta en `resenas` y recalcula `rating_promedio` + `cantidad_resenas` en `billeteras` |

### Endpoints admin (requieren header `X-Admin-Key`)

| Método | Ruta | Descripción |
|---|---|---|
| POST | `/api/cotizaciones` | Cargar nuevas tasas `{ tasas: [{ billetera_id, tasa }] }` |
| GET | `/api/admin/stats` | Stats del dashboard (usuarios, billeteras, consultas, alertas, hoy) |
| GET | `/api/admin/billeteras` | Todas las billeteras sin filtrar `activa` |
| PATCH | `/api/admin/billeteras/:id/toggle` | Mostrar/ocultar billetera en la app |
| PUT | `/api/admin/billeteras/:id/rating` | Actualizar rating y cantidad de reseñas `{ rating_promedio, cantidad_resenas }` |
| GET | `/api/admin/usuarios` | Listado de usuarios MySQL |
| POST | `/api/admin/usuarios` | Crear usuario en MySQL + Firebase `{ nombre, email, password, pais_residencia }` |
| PUT | `/api/admin/usuarios/:id` | Editar nombre/email en MySQL y Firebase (sincroniza displayName) |
| DELETE | `/api/admin/usuarios/:id` | Eliminar usuario de MySQL y Firebase (si existe). Si no está en Firebase, elimina solo MySQL sin error |
| GET | `/api/admin/firebase-usuarios` | Unión Firebase + MySQL: usuarios Firebase con estado MySQL + usuarios solo-MySQL |
| POST | `/api/admin/sincronizar-usuario` | Crear registro MySQL para usuario que solo existe en Firebase |

### Levantar el servidor

```cmd
cd server
node index.js
```

---

## Seguridad

- **Contraseñas:** hasheadas con `bcryptjs` (salt rounds: 10). Nunca se almacena la contraseña en texto plano.
- **JWT:** firmado con `JWT_SECRET` del `.env`, expira en 7 días. El middleware `auth.js` lo verifica en cada ruta protegida.
- **Firebase Auth:** login con email/contraseña y Google. El email debe estar verificado para poder entrar a la app (`emailVerified` chequeado en login y en `onAuthStateChanged`). Si el email no está verificado, Firebase cierra la sesión automáticamente.
- **Panel admin:** protegido con `X-Admin-Key` (mismo valor que `JWT_SECRET`).

### Flujo de autenticación (Firebase + backend JWT)

La app tiene **dos sistemas de auth en paralelo**:
- **Firebase Auth** → controla acceso a la app (verificación de email)
- **Backend JWT** → controla acceso a los endpoints protegidos de la API REST

```
Registro:
  → Firebase crea cuenta → envía email de verificación → backend crea usuario en MySQL
  → cierra sesión Firebase → va a EmailVerificationScreen

Login (email/contraseña):
  → Firebase login (chequea emailVerified)
  → Si OK → llama a POST /api/auth/login → obtiene JWT → guarda en AsyncStorage
  → JWT disponible en AuthContext como apiToken para todas las pantallas

Login con Google:
  → Firebase Sign-In con ID token de Google
  → Llama a POST /api/auth/firebase-login con el ID token → obtiene JWT → guarda en AsyncStorage

App reabierta / token faltante:
  → onAuthStateChanged detecta sesión Firebase activa
  → Si hay token en AsyncStorage → lo restaura
  → Si NO hay token → llama a POST /api/auth/firebase-login con getIdToken() de Firebase
  → Esto cubre: servidor caído durante login, reinstalación de app, login con Google
```

### Fix aplicado — Nombres de billeteras BD vs. WALLET_META

La BD almacena algunos nombres distintos a las keys de `WALLET_META` (ej. `"Cocos"` en vez de `"Cocos Capital"`). Fix en `wallets.js`:
- `getCanonicalName(name)` — si no hay match exacto, busca una key donde uno empiece con el otro (case-insensitive). Usado en `ResultsScreen` al mapear resultados de la API para normalizar el nombre mostrado.
- `getWalletMeta(name)` — idem, usa el mismo match parcial como fallback antes de devolver el objeto vacío.
- `WALLET_META` tiene `androidPackage` para las 13 billeteras (obtenidos vía `adb shell pm list packages`).

### Bug conocido y fix aplicado — Content-Type en requests con token

El helper `request()` en `api.js` tenía un bug: al hacer `...options` después de definir `headers`, el spread pisaba el `Content-Type: application/json` con solo `{ Authorization: ... }`. Todos los POST protegidos (historial, alertas, firebase-login) fallaban con "Body requerido". Fix: destructurar `headers` de options antes del spread.

```js
// CORRECTO (aplicado)
const { headers: extraHeaders, ...rest } = options;
fetch(url, { headers: { 'Content-Type': 'application/json', ...extraHeaders }, ...rest });
```

---

## Pantallas (23 implementadas)

### Onboarding
- **SplashScreen** — logo real (`assets/icon.jpg`) animado con scale+opacity, fondo azul, 2.5s, navega a Onboarding. Sin emojis.
- **OnboardingScreen** — carrusel 3 slides. Layout: mitad superior con fondo de color suave + tarjeta cuadrada con Ionicons (azul/verde/naranja); mitad inferior con badge de categoría, título y subtítulo. Dots y botón "Siguiente" cambian de color según slide activo. Usa `SafeAreaView` de `react-native-safe-area-context`.
- **LoadingSplashScreen** — splash azul con barra de progreso

### Auth
- **LoginScreen** — email/contraseña + Google Sign-In, chequea emailVerified
- **RegisterScreen** — formulario con validación, envía email de verificación. Fix: `includeFontPadding: false` + `textAlignVertical: center` en inputs de contraseña para evitar placeholder desalineado en Android.
- **EmailVerificationScreen** — avisa que se envió el mail, redirige al Login
- **ForgotPasswordScreen** — envía link de reset por email

### Flujo principal
- **HomeScreen** — destino Brasil PIX fijo, input de monto via teclado custom. Muestra últimas 3 consultas reales del usuario (desde API, con `useFocusEffect`). Tocar una consulta reciente navega a Results con `skipSave: true`.
- **NumericKeyboard** (componente) — bottom sheet con teclado numérico custom
- **ResultsScreen** — ranking animado de billeteras (desde API), con ahorro vs peor opción. Guarda en historial automáticamente cuando ambos `apiToken` y `bestResult` están disponibles (dos efectos separados para evitar race condition). Acepta `skipSave: true` en params para no guardar (usado desde Historial y consultas recientes del Home). La card ganadora tiene dos botones diferenciados: "Ver detalles" (navega directo a WalletProfile) y "Ir a [nombre]" (abre `ExternalRedirectModal` → `Linking.openURL(appUrl)`). Las cards restantes tienen "Ver más →" que también navega directo a WalletProfile. Brubank abre directo por App Links; otras billeteras abren su sitio web.
- **EmptyResultsScreen** — estado vacío cuando no hay cotizaciones
- **LoadingResultsScreen** — skeleton loaders mientras carga

### Billeteras
- **WalletsScreen** — directorio con buscador, rating y países. Lista solo billeteras activas (desde API). Tap en cualquier item navega directo a WalletProfile.
- **WalletDetailScreen** — pantalla de detalle (comisión, límites, monedas, países). Ya no es el destino primario desde Results ni Wallets; sigue registrada en el navegador.
- **WalletProfileScreen** — perfil completo: descripción, comisiones, pros/contras, requisitos, países. Estrella en header para agregar/quitar favoritos. Botón "IR A LA APP DE …" vía `ExternalRedirectModal`. Es el destino primario desde Results y Wallets. Incluye dos funciones conectadas a la BD:
  - **Gráfico de evolución de la tasa** — barras puras (sin librería), muestra hasta 20 registros desde `GET /api/cotizaciones/historial`, con rango min/max y fechas.
  - **Reseñas reales** — carga `resenas` desde `GET /api/billeteras/:id`. Botón "Escribir reseña" (solo usuarios logueados) abre form inline con selector de estrellas + campo de texto. Al enviar llama a `POST /api/resenas`; el rating del hero se actualiza con el nuevo promedio.
- **WalletCompareScreen** — tabla comparativa lado a lado de 2 billeteras. Picker carga solo billeteras activas desde API.

### Alertas
- **AlertsScreen** — lista de alertas con toggle activa/pausada. Recarga con `useFocusEffect`.
- **CreateAlertScreen** — formulario para nueva alerta con preview. Picker carga solo billeteras activas desde API.
- **PushNotificationScreen** — mockup estático de notificación en pantalla bloqueada
- **Polling en tiempo real** — `AuthContext` corre `setInterval` cada 60s mientras haya sesión. Compara cotizaciones actuales contra alertas activas del usuario. Cuando se cumple la condición: pausa la alerta automáticamente (`PATCH activa=false`) y muestra `Alert.alert`. Una notificación por alerta por sesión (ref de IDs ya disparados).

### Perfil
- **ProfileScreen** — muestra nombre/email/inicial del usuario Firebase real. Carga últimas 3 consultas desde API con `useFocusEffect`. Link a historial completo.
- **EditProfileScreen** — edición de nombre e info
- **HistoryScreen** — historial de consultas desde API con `useFocusEffect`
- **SettingsScreen** — toggles de notificaciones, idioma, tema
- **FavoritesScreen** — favoritos reales del usuario desde API con `useFocusEffect`. Quitar favorito persiste en BD con confirmación. Estado vacío cuando no hay favoritos.

### Globales
- **ErrorScreen** — sin conexión con botón reintentar
- **ExternalRedirectModal** (componente) — confirma antes de abrir app externa

---

## Design system

```javascript
const colors = {
  primary: '#3b82f6',       // Azul — botones, acentos
  primaryDark: '#2563eb',   // Azul pressed
  success: '#10b981',       // Verde — mejor opción
  background: '#ffffff',
  surface: '#f8f9fa',       // Inputs y cards
  border: '#dee2e6',
  textPrimary: '#1a1a1a',
  textSecondary: '#6c757d',
  textMuted: '#adb5bd',
  divider: '#e0e0e0',
}
```

- Cards: `borderRadius: 16` — Botones: `borderRadius: 12` — Inputs: `borderRadius: 16`
- Padding horizontal de pantalla: `20px` — Gap entre cards: `16px`
- `SafeAreaView` siempre de `react-native-safe-area-context`, nunca de `react-native`
- Estilos siempre con `StyleSheet.create()`, nunca inline

---

## Configuración importante

### Firebase
- Proyecto: `comparabilleteras-1e6b7`
- Google OAuth usa cliente **Desktop app** (`276300901779-kdko463f6u3hq58fv0r0duattluo7l1m`)
- Redirect URI hardcodeado: `com.googleusercontent.apps.276300901779-kdko463f6u3hq58fv0r0duattluo7l1m:/`
- Google Sign-In **no funciona en Expo Go** (scheme no registrado) — funciona en APK custom
- **Firebase Admin SDK:** requiere `server/serviceAccountKey.json` (bajar de Firebase Console → Configuración → Cuentas de servicio → Generar clave privada). Sin este archivo el panel muestra solo usuarios MySQL. El endpoint `/api/auth/firebase-login` tiene fallback automático a la API pública de Google si no hay serviceAccountKey.json.

### Android build
- Gradle: **8.3** — NO subir a 8.8+ (rompe expo-modules-core)
- Junction `C:\dev\CB` → path corto para evitar fallo de CMake en Windows
- **Compilar siempre desde `C:\dev\CB\android`**

### app.json — íconos en JPG
```json
"icon": "./assets/icon.jpg",
"splash": { "image": "./assets/splash.jpg" },
"android": { "adaptiveIcon": { "foregroundImage": "./assets/adaptive-icon.jpg" } }
```

---

## Comandos útiles

```bash
# App mobile — Expo Go
npx expo start -c
npx expo start --tunnel --clear   # desde celular físico / red distinta

# API
cd server && node index.js

# Build APK (desde junction)
cd C:\dev\CB\android
.\gradlew.bat app:assembleDebug
adb install -r C:\dev\CB\android\app\build\outputs\apk\debug\app-debug.apk
```

---

## Flujo para correr la app en celular físico (método definitivo: USB + adb reverse)

Este método funciona en cualquier red sin depender del WiFi.

```cmd
# 1. Levantar el servidor
cd server
node index.js

# 2. Conectar el celular por USB con Depuración USB activada
#    (Ajustes → Acerca del teléfono → tocar "Número de compilación" 7 veces
#     → Opciones de desarrollador → Depuración USB: ON)

# 3. Verificar que el celu aparece
adb devices
# debe mostrar algo como: 4eddc151   device

# 4. Hacer el reverse del puerto (repetir si se desconecta el cable)
adb -s 4eddc151 reverse tcp:3000 tcp:3000
# imprime: 3000

# 5. Levantar Metro
npx expo start -c

# 6. Escanear el QR con Expo Go en el celular
```

La URL en `src/services/api.js` debe ser `http://localhost:3000` (ya configurada).

### Importar BD en notebook nueva
```sql
-- En MySQL Workbench, ejecutar en orden:
-- 1. brasilpagos_schema.sql  (incluye el VIEW cotizaciones_actuales)
-- 2. brasilpagos_datos.sql   (incluye las 13 billeteras)
```

### Si cambia el ID del dispositivo (otra notebook, otro cable)
```cmd
adb devices   # ver el nuevo ID
adb -s NUEVO_ID reverse tcp:3000 tcp:3000
```

---

## Pendiente

### Documentación técnica (criterio 6 — 5 pts)
- [x] README con arquitectura, instrucciones de instalación y descripción de endpoints

### Funcionalidad en BD sin UI
- [x] **Reseñas** — `POST /api/resenas` implementado. `WalletProfileScreen` muestra reseñas reales y permite crear nuevas con form inline (estrellas + comentario). Rating se recalcula automáticamente en BD.
- [x] **Gráfico de historial de cotizaciones** — sección "Evolución de la tasa" en `WalletProfileScreen` con gráfico de barras puro (sin librerías). Consume `GET /api/cotizaciones/historial`.

### Notificaciones
- [ ] `PushNotificationScreen` es un mockup estático — las alertas no disparan notificaciones reales cuando la app está cerrada. El polling actual solo funciona con la app abierta.

### UX / navegación
- [x] `WalletDetailScreen` eliminada del stack — removida de `AppNavigator.js` (import + `Stack.Screen`). El archivo queda en disco por si se necesita.
- [x] `WalletCompareScreen` ya tenía dos entry points (ícono en header de `WalletsScreen` + ícono/chip "Comparar 2" en `ResultsScreen`). Fix: ahora recibe `route` y pre-selecciona `initialWallet1` / `initialWallet2` cuando viene desde `ResultsScreen`.

### Diseño / Figma

El archivo Figma tiene 3 páginas: **Pantallas**, **Componentes**, **Bases**.
Link: `https://www.figma.com/design/Hmg85ALG6apYw9dMgdTDZX/TP-Integrador---Módulo-4---Piel-Visual`

Cambios pendientes de reflejar en Figma:

#### Página "Pantallas"
- [x] **WalletProfileScreen** — actualizado: frame extendido a 1220px. Agregado: ☆ favorito en header, rating "★ 4.8 · 124 reseñas" en hero, sección "Evolución de la tasa" con gráfico de barras (8 puntos, última barra azul sólida, rango y fechas), sección "Opiniones" con 2 review cards reales (avatar, nombre, estrellas doradas, fecha, comentario) + link "+ Escribir reseña", form inline "Tu reseña" con selector 5 estrellas + input + botón Enviar.
- [x] **OnboardingScreen** — actualizado: 3 frames (Slide 1 azul, Slide 2 verde, Slide 3 naranja). Layout: mitad superior con fondo suave + tarjeta cuadrada (borderRadius 40); mitad inferior con badge, título Extra Bold 30px, subtítulo gris 15px. Dots coloreados según slide activo, botón "COMENZAR" full-width en slide 3.
- [x] **BottomNav** — actualizado en HomeScreen + frame "BottomNav-Component" con 2 estados (Home activo / Wallets activo). Pill: 48×36 borderRadius 18 fill #eff6ff, ícono activo azul #3b82f6, inactivo gris #adb5bd, sin labels.
- [x] **ResultsScreen** — card ganadora actualizada: botón outline "Ver detalles" (→ WalletProfile) + botón azul "Ir a Mercado Pago →" (→ ExternalRedirectModal). Cards restantes mantienen "Ver mas >".
- [x] **WalletCompareScreen** — verificado: diseño ya es correcto (pickers, tabla, banner ganador, botón). Los cambios (API activas + route params pre-selección) son lógica interna, sin impacto visual.

#### Página "Componentes"
- [x] `BottomNav` agregado como componente con 2 estados (Home-Active, Wallets-Active). Pill 48×36 r=18 fill #eff6ff.
- [x] `ExternalRedirectModal` agregado: overlay oscuro + card con avatar, título, body, botones Cancelar / Continuar →.
- [ ] `NumericKeyboard` — existe como frame en Pantallas/Extras (11-NumericKeyboardScreen), no como componente reutilizable en Componentes.

#### Página "Bases"
- [x] Design system completo agregado: 13 colores con swatches 56×56px (Primary → Divider), 6 niveles tipográficos (Display 30px Bold → Tiny 10px Regular) con nota descriptiva al lado, escala de espaciado 4px–48px alineados al fondo, 6 border radius con cajas de ejemplo (4 / 8 / 12 / 16 / 18 / 50%). Layout en 4 secciones verticales separadas por dividers, sin superposición.

### Exposición en notebook
- [ ] Instalar Node.js 22, MySQL 8, MySQL Workbench, Android Studio
- [ ] Copiar proyecto, importar BD con los scripts SQL, configurar `.env`
- [ ] Copiar `serviceAccountKey.json` en `server/` para habilitar Firebase Admin
- [ ] Cambiar `API_BASE_URL` en `api.js` si se usa celular físico
- [ ] Verificar que Metro + servidor corren bien antes de la expo
