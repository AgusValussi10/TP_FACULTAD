# TFI — Unión del Sur App · CLAUDE.md

## Proyecto
Sistema de gestión integral para la **Fundación Unión del Sur** (fútbol formativo, Resistencia, Chaco).  
Reemplaza planillas Excel/WhatsApp con una app móvil multiplataforma + panel web de administración.

## Equipo
| Alumno | Legajo | Módulo asignado |
|--------|--------|-----------------|
| Cantero Juan Manuel | 30.331 | Cuotas y Pagos + Descuento hermanos + Panel reportes |
| **Valussi Agustín** | **30.353** | **Categorías y Jugadores + Seguro y Carnet de liga** |
| Escobar Alabert Lucas | 30.379 | Asistencia + Partidos y Resultados + integración app móvil |

## Stack tecnológico
| Capa | Tecnología |
|------|-----------|
| Backend | .NET 10 / ASP.NET Core Web API |
| Data access | Dapper + MySqlConnector (sin ORM) |
| Frontend móvil | React Native + Expo 57 |
| Frontend web admin | HTML5 + CSS3 + Vanilla JS (SPA sin framework) |
| Base de datos | MySQL |
| Auth | JWT (BCrypt para hash de passwords) |
| IA | Anthropic Claude API (claude-haiku-4-5-20251001) |
| Diseño | Figma |
| Versionado | GitHub |
| Gestión | Jira |

## Estructura de directorios
```
TFI/
├── APP MOBILE/
│   ├── logo.png                  → Logo oficial del club (fuente para iconos de app y web)
│   ├── backend/                  → ASP.NET Core Web API (.NET 10)
│   │   ├── Controllers/          → 9 controllers HTTP
│   │   ├── Models/               → Categoria, Usuario, Jugador, Cuota, Seguro, CarnetLiga, Asistencia, Partido, Goleador
│   │   ├── DTOs/                 → LoginRequest, LoginResponse
│   │   ├── Data/
│   │   │   └── InitDb.cs         → Crea DB si no existe, tablas IF NOT EXISTS, seed demo
│   │   ├── Properties/
│   │   │   └── launchSettings.json → http: 0.0.0.0:5089 (accesible desde red local)
│   │   ├── Program.cs            → Configura JWT, CORS, DI, llama InitDb
│   │   └── appsettings.json      → Connection string, JWT config, Anthropic key
│   ├── mobile/                   → React Native + Expo 57
│   │   ├── App.js                → Stack navigator: Login → Categorias → Jugadores
│   │   └── src/
│   │       ├── screens/
│   │       │   ├── LoginScreen.js      → Fiel al Figma (azul navy, chips de rol rápido)
│   │       │   ├── CategoriasScreen.js → Lista categorías con DT + cant. jugadores, tappable
│   │       │   └── JugadoresScreen.js  → Padrón por categoría con buscador
│   │       └── services/
│   │           └── api.js              → BASE_URL configurable, authService, categoriasService, jugadoresService
│   ├── database/
│   │   └── init.sql              → Script manual alternativo (no usar si InitDb funciona)
│   └── CLAUDE.md                 → Este archivo
└── web-admin/                    → Panel web para administradores
    ├── assets/
    │   └── logo.png              → Logo del club (copiado desde APP MOBILE/logo.png)
    ├── login.html
    ├── index.html                → SPA principal (sidebar + contenido)
    ├── css/styles.css
    └── js/
        ├── api.js                → HTTP client, JWT helpers, formatters
        ├── app.js                → Router SPA, modal, toast, nav
        ├── reportes.js           → Dashboard KPIs + Chart.js
        ├── categorias.js
        ├── jugadores.js          → Lista + Ficha + Alta
        ├── cuotas.js             → Generar período + registrar pago
        ├── seguro.js
        ├── carnets.js
        ├── asistencia.js
        ├── partidos.js
        └── ia.js                 → Generador de recordatorios IA
```

## Arquitectura backend
- **Patrón:** Controllers delgados → Dapper queries directas (sin capa de repositorio separada)
- **Conexión DB:** `MySqlConnection` creada en cada método vía `IConfiguration`
- **Auth:** JWT Bearer con roles (`admin`, `entrenador`, `familia`)
- **CORS:** AllowAnyOrigin para desarrollo local
- **InitDb:** Crea todas las tablas con `CREATE TABLE IF NOT EXISTS` + seed datos demo

## Roles de usuario
1. **Administración** — acceso total (todas las categorías, jugadores, cuotas, seguros, carnets, reportes)
2. **Entrenador** — solo su/s categoría/s asignada/s → jugadores → asistencia → partidos
3. **Familia** — solo sus hijos → estado de cuota → seguro/carnet → asistencia → resultados

## Schema de base de datos (union_del_sur)

### Tablas principales
| Tabla | Descripción |
|-------|-------------|
| `usuarios` | id, nombre, email, password_hash, rol ENUM, activo, created_at |
| `categorias` | id, nombre, anio_nacimiento_desde, anio_nacimiento_hasta, entrenador_id FK, activo |
| `grupos_familiares` | id, nombre_contacto, telefono, email, activo |
| `jugadores` | id, nombre, apellido, dni UNIQUE, fecha_nacimiento, categoria_id FK, grupo_familiar_id FK, activo, fecha_alta |
| `cuotas` | id, jugador_id FK, periodo_mes, periodo_anio, monto, descuento_hermanos, estado ENUM, fecha_vencimiento — UNIQUE(jugador_id, mes, anio) |
| `pagos` | id, cuota_id FK, monto_pagado, fecha_pago, metodo_pago, registrado_por_id FK |
| `seguros` | id, jugador_id FK, numero_poliza, vigente_desde, vigente_hasta, estado ENUM |
| `carnets_liga` | id, jugador_id FK, numero_carnet, temporada, estado ENUM, fecha_emision, fecha_vencimiento |
| `asistencias` | id, jugador_id FK, categoria_id FK, fecha, presente — UNIQUE(jugador_id, fecha) |
| `partidos` | id, categoria_id FK, rival, fecha, hora, lugar ENUM, resultado_local, resultado_visitante, estado ENUM |
| `goleadores` | id, partido_id FK, jugador_id FK, cantidad |

### Seed inicial (generado por InitDb al arrancar — idempotente)
- Usuario admin: `admin@uniondelsur.com` / `Admin123!` / rol `admin`
- 6 categorías (si no existen): nombres editados manualmente en la DB
- 30 jugadores demo (5 por categoría): INSERT IGNORE por DNI, no duplica aunque ya haya jugadores manuales

### Datos de familia cargados en la DB (05/10/2026, vía API, no los crea InitDb)
- Los 28 jugadores existentes tienen madre/padre de contacto (nombre, teléfono 362…, email — todos datos de ejemplo); ninguno quedó sin `grupo_familiar_id`.
- Casos de hermanos (misma familia, categorías distintas, 10% desc.): Laura González → Tomás González (9na) + Benjamín Pérez (8va); Marcela López → Santiago López (9na) + Thiago Sánchez (8va); Carolina Herrera → Matías Herrera (Escuelita) + Gastón Morales (7ma) + Darío Suárez (6ta); Romina Ríos → Rodrigo Ríos (Escuelita) + Cristian Ramos (6ta).
- Cantero conserva su familia original ("Silvia - Mama Jugador"). Los hermanos tienen apellidos distintos a propósito (no se modificaron apellidos).
- Si se recrea la DB, estos vínculos se pierden (InitDb solo siembra jugadores sin familia).

## API REST — endpoints implementados

### Auth
- `POST /api/auth/login` → `{email, password}` → `{token, nombre, rol}`

### Categorías `[Authorize]`
- `GET /api/categorias` — lista con entrenador y cantidad de jugadores
- `POST /api/categorias` `[admin]` — crear
- `PUT /api/categorias/{id}` `[admin]` — actualizar
- `DELETE /api/categorias/{id}` `[admin]` — soft delete

### Jugadores `[Authorize]`
- `GET /api/jugadores?categoriaId=&busqueda=` — padrón con filtros
- `GET /api/jugadores/{id}` — ficha completa (+ cuotas, seguro, carnet, asistencias)
- `GET /api/jugadores/grupos?busqueda=` — familias existentes con integrantes (para vincular hermanos)
- `POST /api/jugadores` `[admin]` — alta; `grupoFamiliar` anidado (familia nueva) o `grupoFamiliarId` (vincular hermano)
- `PUT /api/jugadores/{id}` `[admin]` — editar (acepta `grupoFamiliar`/`grupoFamiliarId` para re-vincular)
- `DELETE /api/jugadores/{id}` `[admin]` — soft delete

### Cuotas `[Authorize]`
- `GET /api/cuotas?mes=&anio=&categoriaId=` — cuotas del período
- `POST /api/cuotas/generar` `[admin]` — genera para todos los activos (aplica 10% descuento hermanos)
- `POST /api/cuotas/{id}/pagar` `[admin]` — registrar pago

### Seguros `[Authorize]`
- `GET /api/seguros?vencimientoProximo=true` — lista (filtro opcional vence en 15 días)
- `POST /api/seguros` / `PUT /api/seguros/{id}` `[admin]`

### Carnets `[Authorize]`
- `GET /api/carnets?temporada=` — lista
- `POST /api/carnets` / `PUT /api/carnets/{id}` `[admin]`

### Asistencia `[Authorize]`
- `GET /api/asistencia?categoriaId=&fecha=` — lista jugadores con estado del día
- `POST /api/asistencia` — guardar registros (INSERT ... ON DUPLICATE KEY UPDATE)

### Partidos `[Authorize]`
- `GET /api/partidos?categoriaId=&estado=` — lista
- `POST /api/partidos` `[admin,entrenador]`
- `PUT /api/partidos/{id}/resultado` `[admin,entrenador]` — cargar resultado + goleadores

### Reportes `[admin]`
- `GET /api/reportes/dashboard` — totalJugadores, cuotasPendientes, jugadoresSinSeguro, próximoPartido, morosidadPorCategoria, últimosPartidos
- `GET /api/reportes/morosidad` — lista detallada morosos

### IA `[admin]`
- `POST /api/ia/recordatorio` → `{tipo, jugadorIds[]}` → `{mensaje}` (usa Claude si hay API key, sino template)

## Pantallas Figma (18 en total)
Diseño: https://www.figma.com/design/wesJXtRoba8kTICHIogqlS/

### Login
- `00 — Login`

### Administración
- `A1 — Panel de reportes` → web-admin: sección Dashboard
- `A2 — Categorías` → web-admin: sección Categorías
- `A3 — Jugadores` → web-admin: sección Jugadores (vista lista)
- `A4 — Ficha del jugador` → web-admin: sección Jugadores (vista ficha)
- `A5 — Alta de jugador` → web-admin: sección Jugadores (vista alta)
- `A6 — Cuotas del período` → web-admin: sección Cuotas
- `A7 — Registrar pago` → web-admin: modal en sección Cuotas
- `A8 — Seguro y carnets` → web-admin: secciones Seguros + Carnets
- `A9 — Recordatorio IA` → web-admin: sección Recordatorio IA

### Entrenador
- `E1 — Inicio entrenador` (categorías asignadas)
- `E2 — Tomar asistencia` → web-admin: sección Asistencia
- `E3 — Partidos` → web-admin: sección Partidos
- `E4 — Cargar resultado` → web-admin: modal en sección Partidos

### Familia
- `F1 — Inicio familia` (estado de cuenta, hijos)
- `F2 — Estado de cuenta` (cuotas por período)
- `F3 — Partidos y resultados`
- `F4 — Asistente IA` (chat con Claude/OpenAI)

## Navegación app móvil
- Bottom tab bar que varía por rol: 4 tabs Admin, 3 tabs Entrenador, 4 tabs Familia

## Cómo correr el proyecto

### Backend
1. Iniciar MySQL via **XAMPP Control Panel** → click **Start** en MySQL
2. `cd "APP MOBILE/backend"` → `dotnet run --launch-profile http`
   - Arranca en `http://0.0.0.0:5089` (accesible desde red local y dispositivos)
   - InitDb crea la DB `union_del_sur` si no existe, luego tablas y seed
3. Verificar: abrir `http://localhost:5089/api/categorias` → debe devolver 401

### App móvil (React Native + Expo)
1. `cd "APP MOBILE/mobile"` → `npx expo start --clear`
2. Escanear QR con **Expo Go** (teléfono en la misma red WiFi que la PC)
3. Si da error de conexión: verificar IP en `mobile/src/services/api.js` → `BASE_URL`
   - IP actual configurada: `192.168.0.118:5089`
   - Si cambia la red, actualizar esa IP con la IP local de la PC
4. Credenciales: `admin@uniondelsur.com` / `Admin123!`

> **Importante:** `@react-native-async-storage/async-storage` debe instalarse con `npx expo install`, no con `npm install`. Si da "Native module is null", reinstalar con `npx expo install @react-native-async-storage/async-storage`.

### Panel web admin
1. Abrir `web-admin/login.html` en el browser (con el backend corriendo)
2. Usuario demo: `admin@uniondelsur.com` / contraseña: `Admin123!`

## Estado actual de la app móvil (sesión 05/10/2026)
| Pantalla | Estado |
|----------|--------|
| `00 — Login` | ✅ Funcionando — fiel al Figma, chips de acceso rápido por rol |
| `A2 — Categorías` | ✅ Funcionando — muestra DT, cant. jugadores, navega al tocar |
| `A3 — Jugadores` | ✅ Funcionando — padrón por categoría, buscador, badge de hermanos, refresca al volver |
| `A4 — Ficha del jugador` | ✅ Funcionando — datos, familia, hermanos vinculados (navegables), seguro/carnet/cuota, dar de baja |
| `A5 — Alta/edición de jugador` | ✅ Funcionando — valida edad vs categoría; vínculo familiar: sin vínculo / es hermano de… (busca familias) / familia nueva |
| Resto de pantallas | Pendiente (cuotas, seguros/carnets, panel, entrenador, familia) |

## IA — configuración
- En `appsettings.json`, poner la API key en `"Anthropic": { "ApiKey": "sk-ant-..." }`
- Sin API key: el endpoint `/api/ia/recordatorio` devuelve un mensaje template predefinido
- Modelo: `claude-haiku-4-5-20251001`
- Prompt en español rioplatense, contexto de club de fútbol infantil

## Reglas de negocio implementadas
- **Descuento hermanos:** 10% automático si el jugador comparte `grupo_familiar_id` con otro jugador activo
- **Alerta seguros:** endpoint filtra seguros que vencen en ≤15 días (`DATE_ADD(CURDATE(), INTERVAL 15 DAY)`)
- **Cuotas únicas:** constraint `UNIQUE(jugador_id, periodo_mes, periodo_anio)` — `INSERT IGNORE` evita duplicados al regenerar
- **Asistencia única por día:** constraint `UNIQUE(jugador_id, fecha)` — `ON DUPLICATE KEY UPDATE` para editar

## Assets y branding
- **Logo fuente:** `APP MOBILE/logo.png` — archivo PNG oficial del club
- **Web-admin:** logo copiado a `web-admin/assets/logo.png`; usado como favicon (`<link rel="icon">`) en `login.html` e `index.html`; reemplaza el emoji ⚽ en la pantalla de login (80×80px) y en el sidebar (48×48px via `.sidebar-logo-img` en styles.css)
- **App móvil (Expo):** `mobile/assets/icon.png`, `favicon.png`, `splash-icon.png` y `android-icon-foreground.png` reemplazados por el logo del club; `android-icon-background.png` y `android-icon-monochrome.png` sin cambios

## Notas de desarrollo
- La app móvil (React Native) consume la misma API REST vía JWT
- Los tokens incluyen claims: `sub` (userId), `name`, `email`, `role`
- CORS está configurado para AllowAnyOrigin — en producción restringir al dominio del web-admin
- El web-admin almacena el JWT en `localStorage` bajo la key `jwt_token`
- Si `Anthropic:ApiKey` está vacío, `IaController` cae al mensaje template sin error
