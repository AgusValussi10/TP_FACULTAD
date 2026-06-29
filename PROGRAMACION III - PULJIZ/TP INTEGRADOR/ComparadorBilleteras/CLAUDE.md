# CLAUDE.md — Comparador de Billeteras
> Guía completa para Claude Code. Leé esto antes de tocar cualquier archivo.

---

## 🧠 ¿Qué es esta app?

Una app mobile (React Native + Expo SDK 51) **dedicada exclusivamente a usuarios argentinos que viajan a Brasil** y necesitan saber, en el momento exacto del pago, **qué billetera virtual argentina les conviene más para pagar vía PIX**.

**Caso de uso único (MVP):**
- Usuario: argentino en Brasil
- Origen: ARS (Argentina)
- Destino: BRL (Brasil) vía PIX
- Billeteras: argentinas (Mercado Pago, Ualá, Brubank, Bimo, etc.)
- Acción: ingresa monto en R$, ve ranking ordenado por mejor cotización ARS → BRL

**Filosofía:** evitar que el usuario tenga que abrir 10 apps distintas para comparar cotizaciones. La app lo hace por él en segundos.

**Roadmap futuro (no MVP):** evaluar si vale la pena estandarizar para más países destino (USA, Europa) u otros pares de monedas. Por ahora todo el código y UI debe estar optimizado para el caso AR → BR.

---

## 🛠️ Stack técnico

| Tecnología | Versión | Notas |
|---|---|---|
| React Native | 0.81.5 | Actualizado desde 0.74.5 en migración a SDK 54 |
| Expo SDK | 54 | Objetivo: Expo Go. NO usar `expo prebuild` |
| React | 19.1.0 | Actualizado desde 18.2.0 |
| React Navigation | 6.x | Stack + Bottom Tabs — compatible con React 19, no requirió v7 |
| react-native-screens | ~4.16.0 | Actualizado desde 3.31.1 |
| react-native-gesture-handler | ~2.28.0 | Actualizado desde 2.16.1 |
| react-native-safe-area-context | ~5.6.0 | Actualizado desde 4.10.5 |
| expo-status-bar | ~3.0.9 | Actualizado desde 1.12.1 |
| babel-preset-expo | ~54.0.10 | Movido a dependencies (antes implícito) |

### Migración SDK 51 → 54 (junio 2026)

**Qué cambió:**
1. `npm install expo@^54.0.35` → `npx expo install --fix` alineó automáticamente todas las deps nativas.
2. `babel.config.js` — sin cambios, `babel-preset-expo` sigue siendo el único preset.
3. React Navigation v6 — **no se subió a v7**: sus `peerDependencies` aceptan `react: "*"`, funciona con React 19.
4. `expo-status-bar` pasó de `~1.12.1` a `~3.0.9`.
5. `babel-preset-expo` aparece explícitamente en `dependencies` (antes lo manejaba expo internamente).

**expo-doctor:** 17/18 ✅ — el único aviso es el check de CNG (`android/` presente con config en `app.json`), que es esperado y no afecta Expo Go.

**Bundle verificado:** `npx expo export --platform android` compiló 958 módulos sin errores (exit 0).

**Para correr en Expo Go:**
```bash
npx expo start -c
# Escaneá el QR con Expo Go (SDK 54 compatible)
```

---

## 📁 Estructura de carpetas

```
ComparadorBilleteras/
├── src/
│   ├── screens/
│   │   ├── onboarding/       ← SplashScreen, OnboardingScreen, LoadingSplashScreen
│   │   ├── auth/             ← LoginScreen, RegisterScreen, ForgotPasswordScreen, EmailVerificationScreen
│   │   ├── home/             ← HomeScreen
│   │   ├── results/          ← ResultsScreen, EmptyResultsScreen, LoadingResultsScreen
│   │   ├── wallets/          ← WalletsScreen, WalletDetailScreen, WalletProfileScreen, WalletCompareScreen
│   │   ├── alerts/           ← AlertsScreen, CreateAlertScreen, PushNotificationScreen
│   │   ├── profile/          ← ProfileScreen, EditProfileScreen, SettingsScreen, FavoritesScreen, HistoryScreen
│   │   └── error/            ← ErrorScreen
│   ├── components/           ← Componentes reutilizables
│   │   ├── BottomNav.js
│   │   ├── NumericKeyboard.js
│   │   └── modals/
│   │       └── ExternalRedirectModal.js
│   ├── navigation/           ← Configuración de navegación
│   │   └── AppNavigator.js
│   ├── theme/
│   │   └── colors.js         ← Paleta centralizada (opcional, cada pantalla también define colors inline)
│   ├── config/
│   │   └── firebase.js       ← Config Firebase + GOOGLE_WEB_CLIENT_ID
│   ├── context/
│   │   └── AuthContext.js    ← AuthProvider, useAuth, getAuthErrorMessage
│   └── data/                 ← Datos compartidos (billeteras, helpers)
│       └── wallets.js
├── App.js                    ← Entry point, monta AuthProvider + Navigator
├── android/
│   ├── app/src/main/AndroidManifest.xml  ← Intent filter Google OAuth
│   ├── gradle/wrapper/gradle-wrapper.properties  ← Gradle 8.8
│   └── local.properties  ← sdk.dir configurado
└── package.json
```

---

## 🎨 Design System

### Paleta de colores
```javascript
const colors = {
  primary: '#3b82f6',       // Azul principal — botones, acentos
  primaryDark: '#2563eb',   // Azul hover/pressed
  success: '#10b981',       // Verde — mejor opción, ahorro
  background: '#ffffff',    // Fondo de pantallas
  surface: '#f8f9fa',       // Fondo de inputs y cards
  border: '#dee2e6',        // Bordes generales
  borderFocus: '#3b82f6',   // Borde en focus/hover
  textPrimary: '#1a1a1a',   // Texto principal
  textSecondary: '#6c757d', // Texto secundario/placeholder
  textMuted: '#adb5bd',     // Texto muy suave
  divider: '#e0e0e0',       // Líneas divisoras
}
```

### Tipografía
- Títulos grandes: `fontSize: 32, fontWeight: '700'`
- Títulos sección: `fontSize: 16, fontWeight: '600'`
- Precio principal: `fontSize: 32, fontWeight: '700', letterSpacing: -1`
- Nombre proveedor: `fontSize: 20, fontWeight: '700'`
- Body: `fontSize: 16`
- Labels: `fontSize: 14, color: textSecondary`
- Micro: `fontSize: 12`

### Bordes y radios
- Cards grandes: `borderRadius: 16`
- Botones: `borderRadius: 12`
- Badges: `borderRadius: 6`
- Inputs: `borderRadius: 16`

### Espaciado
- Padding horizontal de pantalla: `20px`
- Gap entre cards: `16px`
- Padding interno de cards: `20px`

---

## 📱 Pantallas

> **23 pantallas en alcance** (25 originales menos 2 removidas por foco MVP en AR→BR PIX). Las marcadas con ✅ están implementadas. Las demás están especificadas y pendientes.

---

### 🗂️ BLOQUE 1 — Onboarding

### Pantalla 1 — Splash / Bienvenida (`SplashScreen.js`) ✅

**Descripción:** Primer impacto visual. Se muestra 2.5 segundos y navega automáticamente a Onboarding.

**Estructura:**
```
Fondo (azul primario #3b82f6, centrado)
  ├── Logo (ícono 💳 grande, 80px, color blanco) — fade-in + scale spring
  ├── Nombre: "ComparaBilleteras" (28px bold, blanco)
  ├── Tagline: "El mejor cambio para Brasil 🇧🇷" (14px, blanco 80%)
  └── Loading indicator (3 puntos animados con loop opacity, abajo centrado)
```

---

### Pantalla 2 — ~~Selección de país de residencia~~ (REMOVIDA)

**Estado:** Fuera de alcance del MVP. El usuario es siempre argentino → no se pide país de residencia. El flujo de onboarding va directo Splash → Onboarding → Home.

---

### Pantalla 3 — Tutorial / Carrusel de funciones (`OnboardingScreen.js`) ✅

**Descripción:** 3 slides que explican qué hace la app. FlatList horizontal pageable con dots de paginación animados (el activo se estira a 24px).

**Estructura:**
```
Slides (FlatList horizontal, paginado, ancho completo)
  ├── Slide 1
  │     ├── Ilustración: 🇧🇷 (ícono grande, 80px, fondo azul claro circular 160px)
  │     ├── Título: "Pagá en Brasil con la mejor cotización"
  │     └── Subtítulo: "Compará billeteras argentinas y elegí la que más rinde para tu PIX"
  ├── Slide 2
  │     ├── Ilustración: 🔔
  │     ├── Título: "Alertas de precio"
  │     └── Subtítulo: "Recibí una notificación cuando la cotización llegue a tu objetivo"
  └── Slide 3
        ├── Ilustración: 📊
        ├── Título: "Siempre actualizado"
        └── Subtítulo: "Cotizaciones en tiempo real de las billeteras más usadas de Argentina"

Dots de paginación (3 puntos, el activo azul y más ancho)

Botones
  ├── "Omitir" (texto gris, izquierda) — solo en slides 1-2
  └── "Siguiente →" / "COMENZAR" (azul, derecha — flex full en último slide)
```

---

### 🏠 BLOQUE 2 — Flujo principal (core)

### Pantalla 4 — Home / Comparador (`HomeScreen.js`) ✅

**Descripción:** Brasil PIX es el destino fijo (sin selector). El monto se ingresa abriendo el `NumericKeyboardScreen` (no usa teclado del sistema).

**Estructura:**
```
Header (64px)
  ├── Ícono menú (izquierda)
  ├── Ícono home (centro, activo)
  └── Ícono perfil (derecha)

Contenido (scrollable)
  ├── Título: "Pagá en Brasil\nal mejor cambio" (32px bold)
  ├── Card destino (borde verde Brasil, fondo verde claro, NO clickeable)
  │     ├── 🇧🇷 Brasil — PIX + Badge verde "Activo"
  │     └── Subtítulo: "Transferencia instantánea"
  ├── Label: "¿Cuánto vas a pagar?"
  ├── Amount Container (clickeable → abre NumericKeyboardScreen)
  │     ├── Prefijo: R$
  │     └── Monto formateado (32px bold) o placeholder "500,00"
  ├── Botón primario: "COMPARAR AHORA" (azul, full width, animación de scale)
  ├── Divider
  └── Últimas consultas
        ├── Bullet azul + "500 BRL - Hace 2hs"
        ├── Bullet azul + "1000 BRL - Ayer"
        └── Bullet azul + "250 BRL - Hace 3 días"

Bottom Navigation (70px, vía BottomNav.js)
  ├── 📊 Comparar (activo)
  ├── 💳 Billeteras
  ├── 📈 Historial
  ├── 🔔 Alertas
  └── ℹ️ Info
```

---

### Pantalla 5 — Teclado numérico emergente (`NumericKeyboardScreen.js`) ✅

**Descripción:** Modal bottom sheet que aparece al tocar el campo de monto. Teclado custom sin usar el teclado del sistema. Reusable: recibe `visible`, `onClose`, `onConfirm`, `initialValue`, `prefix`.

**Estructura:**
```
Overlay semitransparente (toca afuera para cerrar)

Bottom Sheet
  ├── Display del monto actual
  │     ├── Prefijo moneda (R$, USD…)
  │     └── Número grande (48px bold)
  │
  ├── Teclado (grid 3x4)
  │     ├── [1] [2] [3]
  │     ├── [4] [5] [6]
  │     ├── [7] [8] [9]
  │     └── [.] [0] [⌫]
  │
  └── Botón "CONFIRMAR" (azul, full width)
```

---

### Pantalla 6 — ~~Selector de país destino~~ (REMOVIDA)

**Estado:** Fuera de alcance del MVP. Brasil PIX es el único destino soportado, queda fijo en HomeScreen sin selector. Si se expande el alcance a otros destinos en el futuro, reintroducir esta pantalla.

---

### Pantalla 7 — Resultados (`ResultsScreen.js`) ✅

**Recibe como parámetros:** `amount` (número), `currency` (string), `country` (string)

**Estructura:**
```
Header (64px)
  ├── Botón atrás ← (izquierda)
  ├── Título: "Resultados" (centro)
  └── Ícono compare (derecha) → abre CompareScreen con top 2

Contenido (scrollable)
  ├── Contexto: "Pagando 500 BRL" + Chip "⚖ Comparar 2"
  │
  ├── Card MEJOR OPCIÓN (borde verde, fondo verde claro)
  │     ├── Badge verde: "💚 Mejor opción"
  │     ├── Logo (color marca, iniciales)
  │     ├── Nombre (20px bold)
  │     ├── Precio (32px bold)
  │     ├── Ahorro vs peor opción (verde)
  │     └── Link "Ver más →" → WalletDetailScreen
  │
  ├── Cards 2-10 (borde gris estándar)
  │     ├── Logo + nombre + precio (24px)
  │     ├── Ahorro vs peor opción
  │     └── Link "Ver más →"
  │
  └── Botones de acción
        ├── "COMPARTIR" (secundario, gris) — usa Share API
        └── "NUEVA" (primario, azul) — vuelve al Home

Animaciones: cards con stagger 80ms (fade-in + translateY 24→0)
```

---

### Pantalla 8 — Resultados — estado vacío (`EmptyResultsScreen.js`)

**Descripción:** Se muestra cuando ninguna billetera tiene cotización disponible para la moneda seleccionada. El "empty state" es componente de diseño valorado en UX.

**Estructura:**
```
Header (igual a ResultsScreen)
  ├── Botón atrás ←
  ├── Título: "Resultados"
  └── Ícono ⋮

Contenido centrado (vertical)
  ├── Ilustración: 🔍 (ícono grande gris, 80px)
  ├── Título: "Sin cotizaciones disponibles" (20px bold)
  ├── Subtítulo: "Ninguna billetera tiene disponible\nesta moneda en este momento." (gris)
  ├── Sugerencia: "Intentá con BRL o USD, que tienen\nmás cobertura." (gris suave, 12px)
  └── Botón "NUEVA CONSULTA" (azul, ancho 200px)
```

---

### Pantalla 9 — Resultados — estado de carga (`LoadingResultsScreen.js`)

**Descripción:** Skeleton loaders mientras se buscan cotizaciones. Muestra consideración del UX real.

**Estructura:**
```
Header (igual a ResultsScreen)

Contenido
  ├── Skeleton contexto (barra gris 120px × 14px, radio 6)
  │
  ├── Skeleton Card 1 (más grande, simula bestCard)
  │     ├── Skeleton badge (60px × 20px)
  │     ├── Skeleton logo (40×40 redondo) + barra nombre (100px)
  │     ├── Skeleton precio (200px × 32px)
  │     └── Skeleton ahorro (150px × 14px)
  │
  ├── Skeleton Card 2 (tamaño normal)
  │     ├── Skeleton logo + nombre
  │     └── Skeleton precio
  │
  └── Skeleton Card 3 (idéntico a 2)

Nota visual: todas las barras skeleton son gris #e0e0e0, con shimmer animado (loop)
```

---

### 🔍 BLOQUE 3 — Detalle y comparación profunda

### Pantalla 10 — Detalle de billetera (`WalletDetailScreen.js`) ✅

**Descripción:** Aparece al tocar "Ver más →" en una card de resultados. Muestra tipo de cambio exacto, comisiones, límites y tiempo estimado. Botón "IR A LA APP" usa `Linking.openURL` con la URL oficial de la billetera. Botón compartir usa la `Share` API.

**Recibe como parámetro:** `wallet` (objeto completo con price, rate, savings, etc.), `amount` (number), `currency` (string)

**Estructura:**
```
Header
  ├── Botón atrás ←
  ├── Título: nombre de billetera (ej: "Mercado Pago")
  └── Ícono compartir

Contenido (scrollable)
  ├── Bloque identificación
  │     ├── Logo circular (color de la billetera, iniciales)
  │     ├── Nombre grande (24px bold)
  │     └── Badge "💚 Mejor opción" (si aplica)
  │
  ├── Tipo de cambio
  │     ├── Label: "Tipo de cambio hoy"
  │     └── Valor: "$ 970,46 ARS / BRL" (32px bold, primary)
  │
  ├── Tabla de detalles (filas clave:valor)
  │     ├── 💸 Comisión:          0%
  │     ├── 📋 Límite diario:     R$ 5.000
  │     ├── ⏱️ Tiempo estimado:   Instantáneo
  │     └── 🌍 Países destino:    Brasil (PIX)
  │
  ├── Total a pagar
  │     └── "Pagás: $ 485.230 ARS" (grande, bold, success)
  │
  └── Botón "IR A LA APP DE MERCADO PAGO →" (azul, full width)
```

---

### Pantalla 11 — Comparación lado a lado (`CompareScreen.js`) ✅

**Descripción:** El usuario selecciona 2 billeteras para verlas en tabla comparativa. Feature diferenciador del MVP. Tocando cada columna se abre un modal con el listado de las 10 billeteras para hacer swap (la billetera ya usada en la otra columna queda deshabilitada).

**Recibe como parámetros:** `amount` (number), `currency` (string), `initialWallet1`, `initialWallet2` (objetos del array de resultados — pueden omitirse, defaultea a top 1 y 2)

**Estructura:**
```
Header
  ├── Botón atrás ←
  └── Título: "Comparar"

Selector de billeteras
  ├── Columna A: [Logo MP] Mercado Pago  ←→  Columna B: [Logo UA] Ualá
  └── (tocar cada columna para cambiar billetera)

Tabla comparativa
  ┌─────────────────┬──────────────┬──────────────┐
  │ Criterio        │ Mercado Pago │ Ualá         │
  ├─────────────────┼──────────────┼──────────────┤
  │ Precio total    │ $485.230 ✅  │ $492.180     │  ← highlight verde en ganador
  │ Tipo de cambio  │ 970,46 ✅    │ 984,36       │
  │ Comisión        │ 0% ✅        │ 0%           │
  │ Límite diario   │ R$ 5.000     │ R$ 3.000 ✅  │
  │ Tiempo          │ Instant. ✅  │ Instant. ✅  │
  └─────────────────┴──────────────┴──────────────┘

Resumen: "Mercado Pago te da $6.950 ARS más" (verde, bold)

Botón "ELEGIR MERCADO PAGO →" (azul)
```

---

### Pantalla 12 — Historial de cotización (`HistoryScreen.js`) ✅

---

### 🔔 BLOQUE 4 — Alertas

### Pantalla 13 — Lista de alertas configuradas (`AlertsScreen.js`)

**Descripción:** Pantalla principal de la sección 🔔 del nav. Lista de alertas activas del usuario.

**Estructura:**
```
Header
  ├── Título: "Mis Alertas" (izquierda, 24px bold)
  └── Botón "+" (derecha, primary) → navega a CreateAlert

Contenido (scrollable)
  ├── Card alerta 1 (activa)
  │     ├── Logo billetera + nombre: "Mercado Pago"
  │     ├── Condición: "BRL supere $ 975 ARS" (14px)
  │     ├── Badge verde: "Activa"
  │     └── Toggle ON (derecha)
  │
  ├── Card alerta 2 (pausada)
  │     ├── Logo + nombre: "Ualá"
  │     ├── Condición: "BRL baje de $ 960 ARS"
  │     ├── Badge gris: "Pausada"
  │     └── Toggle OFF
  │
  └── Empty state (si no hay alertas)
        ├── Ícono 🔕 (grande gris)
        ├── "Todavía no tenés alertas"
        └── Botón "CREAR PRIMERA ALERTA"
```

---

### Pantalla 14 — Crear nueva alerta (`CreateAlertScreen.js`)

**Descripción:** Formulario para configurar una alerta. Pantalla del task flow crítico.

**Estructura:**
```
Header
  ├── Botón cancelar ✕ (izquierda)
  └── Título: "Nueva alerta"

Formulario (scrollable)
  ├── Campo: Billetera
  │     └── Selector tipo dropdown (ej: Mercado Pago + logo)
  │
  ├── Campo: Par de monedas
  │     └── "ARS / BRL" (selector)
  │
  ├── Campo: Tipo de condición
  │     ├── Radio: "Cuando supere..." (selected)
  │     └── Radio: "Cuando baje de..."
  │
  ├── Campo: Valor objetivo
  │     └── Input numérico grande: "$ 975,00 ARS" (fondo gris)
  │
  └── Preview de la alerta
        └── "Te avisaremos cuando Mercado Pago supere $ 975 ARS por BRL"

Botón "GUARDAR ALERTA" (azul, full width, fijo abajo)
```

---

### Pantalla 15 — Notificación push recibida (`PushNotificationScreen.js`)

**Descripción:** Mockup estático de cómo se ve la alerta cuando se dispara. Puede mostrarse como screenshot de pantalla bloqueada + banner.

**Estructura:**
```
Fondo: pantalla bloqueada (gris oscuro, hora 09:41)

Notificación banner (blanco, radio 14)
  ├── Ícono app ComparaBilleteras (pequeño, izquierda)
  ├── Título: "ComparaBilleteras" (bold, 13px)
  ├── Cuerpo: "💱 Mercado Pago superó tu objetivo:
  │            $ 978 ARS/BRL — ¡Es un buen momento!"
  └── Timestamp: "hace un momento"

Nota al pie: "Deslizá para abrir la app"
```

---

### 💳 BLOQUE 5 — Billeteras / Directorio

### Pantalla 16 — Listado de billeteras (`WalletsScreen.js`)

**Descripción:** Sección del ícono 💳 del nav. Directorio completo de billeteras con info resumida.

**Estructura:**
```
Header
  ├── Título: "Billeteras" (24px bold)
  └── Buscador (input con ícono lupa)

Lista (ScrollView)
  ├── Card Mercado Pago
  │     ├── Logo (MP azul, 48px)
  │     ├── Nombre: "Mercado Pago" (bold)
  │     ├── Países: "🇦🇷 🇧🇷 🇺🇾" (emojis pequeños)
  │     ├── Rating: ⭐ 4.8 (texto amarillo)
  │     └── Botón "Ver perfil →" (link azul)
  │
  ├── Card Ualá (mismo formato)
  ├── Card Bimo
  ├── Card Prex
  ├── Card Naranja X
  ├── Card Brubank
  ├── Card Personal Pay
  ├── Card Lemon Cash
  ├── Card Modo
  └── Card Cuenta DNI
```

---

### Pantalla 17 — Perfil de billetera (`WalletProfileScreen.js`)

**Descripción:** Info estática detallada de una billetera: monedas, cómo funciona el envío, pros/contras, link oficial.

**Recibe como parámetro:** `walletName` (string)

**Estructura:**
```
Header
  ├── Botón atrás ←
  └── Título: nombre de billetera

Hero
  ├── Logo grande (80px, color de marca)
  ├── Nombre (28px bold)
  └── Descripción corta (gris, 14px)

Sección "Monedas disponibles"
  └── Chips: [BRL] [USD] [EUR] (badges redondeados)

Sección "¿Cómo funciona el envío?"
  └── Texto explicativo (3-4 líneas)

Sección "Pros y contras"
  ├── ✅ Sin comisión para PIX
  ├── ✅ Instantáneo 24/7
  ├── ❌ Límite R$ 5.000/día
  └── ❌ Requiere cuenta verificada

Botón "IR AL SITIO OFICIAL" (outline azul)
```

---

### 👤 BLOQUE 6 — Perfil y configuración

### Pantalla 18 — Perfil del usuario (`ProfileScreen.js`)

**Descripción:** Nombre, moneda base, país. Historial de búsquedas guardadas.

**Estructura:**
```
Header
  ├── Título: "Mi Perfil"
  └── Botón editar (ícono lápiz)

Bloque usuario
  ├── Avatar (círculo azul con inicial, 72px)
  ├── Nombre: "Juan Pérez" (20px bold)
  ├── Email: "juan@email.com" (gris)
  └── Badge: "🇦🇷 Argentina — ARS"

Sección "Configuración rápida"
  ├── Moneda base:  ARS   [cambiar]
  └── País:         Argentina [cambiar]

Sección "Búsquedas guardadas"
  ├── 🔵 500 BRL — Mercado Pago — hace 2hs
  ├── 🔵 1000 BRL — Ualá — ayer
  └── 🔵 250 BRL — Bimo — hace 3 días

Botón "CERRAR SESIÓN" (rojo outline, al fondo)
```

---

### Pantalla 19 — Configuración / Ajustes (`SettingsScreen.js`)

**Descripción:** Lista de settings de la app.

**Estructura:**
```
Header
  ├── Botón atrás ←
  └── Título: "Configuración"

Sección "Cuenta"
  ├── Moneda predeterminada    ARS  [>]
  └── País de residencia       Argentina [>]

Sección "Notificaciones"
  ├── Alertas de precio        [Toggle ON]
  ├── Resumen semanal          [Toggle OFF]
  └── Novedades de la app      [Toggle ON]

Sección "Apariencia"
  ├── Tema                     Claro [>]
  └── Idioma                   Español [>]

Sección "Acerca de"
  ├── Versión                  1.0.0
  ├── Términos y condiciones   [>]
  └── Política de privacidad   [>]
```

---

### Pantalla 20 — Favoritos (`FavoritesScreen.js`)

**Descripción:** Billeteras o pares de monedas guardados para acceso rápido desde el home.

**Estructura:**
```
Header
  ├── Título: "Favoritos" (24px bold)
  └── Botón "Gestionar" (texto azul)

Sección "Billeteras favoritas"
  ├── Card compacta: [Logo MP] Mercado Pago  ★ [eliminar]
  └── Card compacta: [Logo UA] Ualá           ★ [eliminar]

Sección "Pares frecuentes"
  ├── Chip: 🇦🇷 ARS / 🇧🇷 BRL  [x]
  └── Chip: 🇦🇷 ARS / 🇺🇸 USD  [x]

Botón "AGREGAR FAVORITO +" (outline azul, centrado)

Empty state (si vacío)
  ├── Ícono ⭐ gris grande
  └── "Guardá tus billeteras favoritas para comparar más rápido"
```

---

### 🔐 BLOQUE 7 — Autenticación

### Pantalla 21 — Login / Registro (`LoginScreen.js`)

**Descripción:** Acceso a cuenta. Justifica el perfil persistente y las alertas personalizadas.

**Estructura:**
```
Fondo blanco, sin header

Bloque superior (logo + título)
  ├── Logo app (48px, azul)
  ├── "ComparaBilleteras" (24px bold)
  └── "Iniciá sesión para guardar\ntus alertas y favoritos" (gris)

Formulario
  ├── Input: Email (ícono sobres)
  ├── Input: Contraseña (ícono candado, toggle mostrar/ocultar)
  └── Link: "¿Olvidaste tu contraseña?" (azul, derecha)

Botón "INICIAR SESIÓN" (azul, full width)

Divider "— o continuá con —"

Botón "Continuar con Google" (outline, ícono G)

Footer
  └── "¿No tenés cuenta? Registrate" (link azul)
```

---

### Pantalla 22 — Recuperar contraseña (`ForgotPasswordScreen.js`)

**Descripción:** Flujo de reset. Muestra manejo de errores y estados.

**Estructura:**
```
Header
  ├── Botón atrás ←
  └── Título: "Recuperar contraseña"

Estado inicial
  ├── Ícono 🔑 (grande, gris)
  ├── Subtítulo: "Ingresá tu email y te enviamos\nun link para resetear tu contraseña"
  ├── Input: Email
  └── Botón "ENVIAR LINK" (azul)

Estado éxito (después de enviar)
  ├── Ícono ✅ (grande, verde)
  ├── Título: "¡Listo!"
  ├── "Revisá tu email juan@email.com\npara el link de recuperación"
  └── Link: "¿No llegó? Reenviá el email"
```

---

### 🌟 BLOQUE 8 — Extras

### Pantalla 23 — Error / Sin conexión (`ErrorScreen.js`)

**Descripción:** Estado de red perdida con botón "Reintentar". Importante para mostrar manejo de errores.

**Estructura:**
```
Centrado (sin header)

  ├── Ícono 📶 con X (grande, gris, 80px)
  ├── Título: "Sin conexión" (20px bold)
  ├── Subtítulo: "Revisá tu conexión a internet\ne intentá de nuevo." (gris)
  └── Botón "REINTENTAR" (azul, ancho 200px)

Footer
  └── "Si el problema persiste, contactanos" (gris, 12px)
```

---

### Pantalla 24 — Modal de confirmación redirección (`ExternalRedirectModal.js`)

**Descripción:** Antes de redirigir al usuario a una app externa. Buena práctica de UX.

**Estructura:**
```
Overlay semitransparente oscuro

Modal centrado (radio 20, fondo blanco)
  ├── Ícono de la billetera (MP azul, 48px)
  ├── Título: "¿Ir a Mercado Pago?" (18px bold)
  ├── Cuerpo: "Vas a salir de ComparaBilleteras
  │            y abriremos la app de Mercado Pago
  │            para que completes tu transferencia."
  │
  └── Botones
        ├── "Cancelar" (outline gris, flex 1)
        └── "Continuar →" (azul, flex 1)
```

---

### Pantalla 25 — Splash de carga inicial + versión (`LoadingSplashScreen.js`)

**Descripción:** Loading screen con animación de logo. Cierra el flujo completo desde que se abre la app hasta que se usa.

**Estructura:**
```
Fondo azul primario (#3b82f6), pantalla completa

Centrado vertical
  ├── Logo animado (💳 con fade-in + escala 0.8→1.0)
  ├── Nombre: "ComparaBilleteras" (24px bold, blanco)
  └── Barra de progreso
        ├── Fondo: blanco 20%
        └── Relleno: blanco, animado de 0 a 100%

Footer
  └── Versión: "v1.0.0" (blanco 60%, 12px, parte baja)
```

---

## 🗺️ Mapa de navegación completo

```
SplashScreen (1)
  └─► OnboardingScreen (3)
        └─► HomeScreen (4) ←── Pantalla principal (Brasil PIX fijo)
              ├── [tocar monto] → NumericKeyboardScreen (5) [modal]
              └── [COMPARAR]    → ResultsScreen (7)
                                           ├── [empty]    → EmptyResultsScreen (8)
                                           ├── [loading]  → LoadingResultsScreen (9)
                                           ├── [Ver más]  → WalletDetailScreen (10)
                                           │                    └── [IR A LA APP] → ExternalRedirectModal (24) [modal]
                                           └── [Comparar] → CompareScreen (11)

Bottom Navigation
  ├── 📊 Comparar    → HomeScreen (4)
  ├── 💳 Billeteras  → WalletsScreen (16)
  │                       └── [Ver perfil] → WalletProfileScreen (17)
  ├── 📈 Historial   → HistoryScreen (12)
  ├── 🔔 Alertas     → AlertsScreen (13)
  │                       └── [+] → CreateAlertScreen (14)
  │                             (Notificación recibida) → PushNotificationScreen (15)
  └── ℹ️ Info        → ProfileScreen (18)
                           ├── [Ajustes] → SettingsScreen (19)
                           └── [Favoritos] → FavoritesScreen (20)

Flujo Auth (opcional)
  LoginScreen (21)
    ├── [ok]              → HomeScreen (4)
    └── [olvidé clave]    → ForgotPasswordScreen (22)

Estados globales
  ├── Sin conexión        → ErrorScreen (23)
  └── Carga inicial       → LoadingSplashScreen (25)
```

---

## 🧩 Componentes y módulos compartidos

### `src/components/BottomNav.js`
Barra de navegación inferior usada por las pantallas con tabs (Home, History, etc.). Props:
- `active` (string) — nombre de la pantalla activa
- `navigation` — objeto de React Navigation

### `src/data/wallets.js`
Datos y helpers centralizados. Exporta:
- `WALLET_META` — meta de cada billetera: `color`, `initials`, `description`, `commission`, `dailyLimit`, `estimatedTime`, `appUrl`
- `PROVIDERS` — tarifas por moneda (clave `BRL` por ahora)
- `formatARS(amount)` — formatea a `$ 12.345 ARS`
- `buildResults(amount, currency = 'BRL')` — devuelve array ordenado con `price`, `savings`, `savingsPct`, `isBest`
- `getWalletMeta(name)` — meta con fallback si el nombre no está

### `src/config/firebase.js`
Inicializa Firebase y exporta:
- `auth` — instancia de Firebase Auth con persistencia AsyncStorage
- `GOOGLE_WEB_CLIENT_ID` — Web Client ID de Firebase para Google Sign-In

### `src/context/AuthContext.js`
Context de autenticación. Exporta:
- `AuthProvider` — wrappea la app en `App.js`
- `useAuth()` — hook que devuelve `{ user, signInWithEmail, signInWithGoogleCredential, signOut, loading }`
- `getAuthErrorMessage(err)` — traduce errores de Firebase a mensajes en español

> `NumericKeyboard` y `ExternalRedirectModal` viven en `src/components/` y `src/components/modals/` respectivamente — fueron extraídos de sus pantallas originales. Las cards de proveedor (`ProviderCard`) viven inline dentro de su pantalla por simplicidad.

---

## ⚙️ Configuración importante

### gradle-wrapper.properties
```properties
distributionUrl=https\://services.gradle.org/distributions/gradle-8.3-all.zip
```
> ⚠️ NO cambiar a 8.8+ — rompe la compilación con expo-modules-core

### gradle.properties
```properties
reactNativeArchitectures=x86_64
```
> Solo x86_64 para el emulador. Para producción agregar arm64-v8a

### local.properties
```properties
sdk.dir=C\:\\Users\\Agustin\\AppData\\Local\\Android\\Sdk
```

### app.json — scheme
```json
"scheme": "comparadorbilleteras"
```
Requerido por `expo-auth-session` para manejar deep links OAuth.

### Firebase (src/config/firebase.js)
- Proyecto: `comparabilleteras-1e6b7`
- Auth habilitado: Email/Contraseña + Google
- Web Client ID exportado como `GOOGLE_WEB_CLIENT_ID`

### Google OAuth — configuración en Google Cloud Console
Proyecto: `ComparaBilleteras` (ID: `276300901779`)

Clientes OAuth configurados:
| Tipo | Client ID | Uso |
|---|---|---|
| Web (Firebase auto) | `276300901779-k09s2dj857ds18efarm268bdj7rmau1g` | Firebase Auth handler |
| **Desktop app** | `276300901779-kdko463f6u3hq58fv0r0duattluo7l1m` | Google Sign-In desde APK de desarrollo |

> ⚠️ **IMPORTANTE:** El cliente tipo **Desktop app** es el que funciona con `expo-auth-session` en un APK de desarrollo. Los tipos Android y Web fallan en este contexto. NO usar `useProxy: true` — en APK conectado a Metro local, `makeRedirectUri` devuelve `exp://127.0.0.1:8081` (ignorando el parámetro `native`), así que el redirect URI se hardcodea directamente.

**Redirect URI del Desktop client:**
```
com.googleusercontent.apps.276300901779-kdko463f6u3hq58fv0r0duattluo7l1m:/
```

**Intent filter en AndroidManifest.xml** (registra el scheme para que Android intercepte el redirect):
```xml
<intent-filter>
  <action android:name="android.intent.action.VIEW"/>
  <category android:name="android.intent.category.DEFAULT"/>
  <category android:name="android.intent.category.BROWSABLE"/>
  <data android:scheme="com.googleusercontent.apps.276300901779-kdko463f6u3hq58fv0r0duattluo7l1m"/>
</intent-filter>
```

**Authorized redirect URIs en el Web client (Firebase):**
```
https://comparabilleteras-1e6b7.firebaseapp.com/__/auth/handler
https://auth.expo.io/@agusvalussi10/ComparadorBilleteras
```

**Usuarios de prueba OAuth:** `agusvalussi10@gmail.com`

**Debug keystore SHA-1** (para referencia futura):
```
97:C8:EF:3B:1B:EF:1A:6B:B3:46:DD:12:E8:0E:4F:F2:E3:4D:47:98
```

### Problema de paths largos en Windows (BUILD)
El path largo del proyecto hace que `react-native-screens` falle al compilar C++ con CMake ("ninja: error: manifest 'build.ninja' still dirty"). La solución es usar un junction point con path corto:

```powershell
# Crear junction (solo la primera vez)
New-Item -ItemType Directory -Path "C:\dev" -Force
cmd /c mklink /J "C:\dev\CB" "c:\Users\Agustin\Escritorio\TP_FACULTAD-1\PROGRAMACION III - PULJIZ\TP INTEGRADOR\ComparadorBilleteras"

# Compilar SIEMPRE desde C:\dev\CB\android
cd C:\dev\CB\android
.\gradlew.bat app:assembleDebug
```

---

## 🚀 Comandos útiles

```bash
# Levantar Metro para Expo Go (target actual)
npx expo start -c

# Verificar bundle sin correr servidor
npx expo export --platform android

# Verificar estado de dependencias
npx expo-doctor

# --- Comandos APK (desactivados — target es Expo Go) ---
# cd android && .\gradlew.bat app:assembleDebug
# adb install app\build\outputs\apk\debug\app-debug.apk
# cd android && .\gradlew.bat clean
```

> 💡 Los cambios solo en JS (pantallas, lógica) se aplican con `r` en Metro sin recompilar. Solo recompilá cuando cambiés archivos nativos (AndroidManifest.xml, package.json con nuevos módulos nativos, etc.).

# Ver en celular físico

```powershell
# Opción 1 — mismo WiFi que la PC (más rápido)
npx expo start --clear

# Opción 2 — tunnel (funciona con datos móviles o redes distintas)
npx expo start --tunnel --clear
```

Instalá **Expo Go** desde Play Store y escaneá el QR. La app funciona completa **excepto el login con Google**.

### ⚠️ Por qué Google Sign-In no funciona en Expo Go

El flujo OAuth de Google abre Chrome, el usuario acepta, y Google redirige al scheme `com.googleusercontent.apps.276300901779-kdko463f6u3hq58fv0r0duattluo7l1m:/`. Ese scheme está registrado en el `AndroidManifest.xml` del APK custom, entonces Android sabe que tiene que interceptar esa URL y volver a la app.

Expo Go **no tiene ese intent filter registrado**, por lo que Chrome simplemente navega a esa URL como un link normal y la app nunca se entera del redirect. El login con Google queda colgado en el browser.

**Para el TP:** alcanza con aclarar que Google Sign-In funciona en el APK custom instalado directamente. El login con email/contraseña funciona perfecto en Expo Go.

### Para demostrar Google Sign-In en celular físico

Compilar el APK con soporte ARM64 e instalar por USB:

```powershell
# 1. Agregar arm64-v8a en android/gradle.properties
#    reactNativeArchitectures=x86_64,arm64-v8a

# 2. Compilar desde el junction (path corto)
cd C:\dev\CB\android
.\gradlew.bat app:assembleDebug

# 3. Instalar en el celular conectado por USB (con depuración USB habilitada)
adb install -r C:\dev\CB\android\app\build\outputs\apk\debug\app-debug.apk
```

> El primer build con arm64 tarda más porque compila código nativo para dos arquitecturas. Una vez instalado el APK, Google Sign-In funciona igual que en el emulador.

---

## 📋 Estado actual del proyecto

### Infraestructura
- [x] Setup inicial con Expo SDK 51 → migrado a **SDK 54** (junio 2026)
- [x] Dependencias instaladas (React Navigation v6, gesture handler, screens, safe area)
- [x] Emulador Pixel 8 API 36 funcionando
- [x] Hot reload funcionando (Metro + Expo Go)
- [x] Estructura de carpetas creada (`src/screens`, `src/components`, `src/navigation`, `src/config`, `src/context`)
- [x] `AppNavigator.js` — Stack con 23 rutas registradas
- [x] `BottomNav.js` — todos los tabs conectados: 📊 Home, 💳 Wallets, 📈 History, 🔔 Alerts, ℹ️ Profile
- [x] 10 billeteras argentinas con datos completos en `src/data/wallets.js` (`WALLETS`, `PROVIDERS`, `WALLET_META`)
- [x] Firebase Auth configurado (email/contraseña + Google Sign-In)
- [x] Junction `C:\dev\CB` creado para resolver el problema de path largo en Windows
- [x] Intent filter Google OAuth en AndroidManifest.xml

### Pantallas implementadas (23/23 en alcance MVP)

> Pantallas 2 y 6 fueron removidas del scope (ver notas en las secciones correspondientes arriba).

- [x] `SplashScreen.js` — Pantalla 1: logo + tagline, auto-navega al onboarding
- [x] `OnboardingScreen.js` — Pantalla 3: carrusel 3 slides
- [x] `HomeScreen.js` — Pantalla 4: comparador Brasil PIX fijo, input de monto
- [x] `NumericKeyboardScreen.js` — Pantalla 5: teclado numérico custom (modal)
- [x] `ResultsScreen.js` — Pantalla 7: ranking de 10 billeteras con logos, ahorro y animaciones
- [x] `EmptyResultsScreen.js` — Pantalla 8: estado vacío sin cotizaciones
- [x] `LoadingResultsScreen.js` — Pantalla 9: skeleton loaders de carga
- [x] `WalletDetailScreen.js` — Pantalla 10: detalle de billetera (tipo de cambio, límites, comisiones)
- [x] `WalletCompareScreen.js` — Pantalla 11: tabla comparativa lado a lado de 2 billeteras (ruta: `WalletCompare`)
- [x] `HistoryScreen.js` — Pantalla 12: historial de consultas con datos mock
- [x] `AlertsScreen.js` — Pantalla 13: lista de alertas configuradas
- [x] `CreateAlertScreen.js` — Pantalla 14: formulario para nueva alerta
- [x] `PushNotificationScreen.js` — Pantalla 15: mockup de pantalla bloqueada con banner
- [x] `WalletsScreen.js` — Pantalla 16: directorio de billeteras con rating, países y buscador
- [x] `WalletProfileScreen.js` — Pantalla 17: perfil estático de billetera
- [x] `ProfileScreen.js` — Pantalla 18: perfil del usuario
- [x] `SettingsScreen.js` — Pantalla 19: ajustes con toggles
- [x] `FavoritesScreen.js` — Pantalla 20: billeteras y pares favoritos
- [x] `LoginScreen.js` — Pantalla 21: login email/contraseña
- [x] `ForgotPasswordScreen.js` — Pantalla 22: recuperar contraseña
- [x] `ErrorScreen.js` — Pantalla 23: sin conexión con botón reintentar
- [x] `ExternalRedirectModal.js` — Pantalla 24: componente modal en `src/components/`
- [x] `LoadingSplashScreen.js` — Pantalla 25: splash azul con animación y barra de progreso

### Pantallas extra (fuera del spec original)
- [x] `RegisterScreen.js` — Registro de cuenta nueva con email/contraseña
- [x] `EditProfileScreen.js` — Edición de datos del perfil
- [x] `EmailVerificationScreen.js` — Verificación de email post-registro

### Archivo legacy
- ⚠️ `CompareScreen.js` — versión anterior de la pantalla 11, **no registrada en AppNavigator**. Puede eliminarse; la activa es `WalletCompareScreen.js`.

### Bugs conocidos pendientes de fix
- [ ] **WalletDetailScreen params**: `ResultsScreen` navega con `{ wallet, amount, currency }` (objeto wallet completo) pero `WalletDetailScreen` espera `{ walletName }` (string). Hay que alinear uno de los dos.
- [ ] **Historial persistente**: sin AsyncStorage, el historial se pierde al cerrar la app.
- [ ] **API real de cotizaciones**: actualmente los rates son datos hardcodeados en `PROVIDERS`.

---

## 🗂️ Archivos clave

| Archivo | Pantalla | Estado |
|---|---|---|
| `src/navigation/AppNavigator.js` | Navigator principal | ✅ |
| `src/components/BottomNav.js` | Barra de navegación inferior | ✅ |
| `src/components/NumericKeyboard.js` | 5 — Teclado numérico (modal) | ✅ |
| `src/components/modals/ExternalRedirectModal.js` | 24 — Confirmación redirección | ✅ |
| `src/data/wallets.js` | Datos y helpers centralizados | ✅ |
| `src/screens/onboarding/SplashScreen.js` | 1 — Splash | ✅ |
| `src/screens/onboarding/OnboardingScreen.js` | 3 — Tutorial carrusel | ✅ |
| `src/screens/onboarding/LoadingSplashScreen.js` | 25 — Splash de carga | ✅ |
| `src/screens/auth/LoginScreen.js` | 21 — Login | ✅ |
| `src/screens/auth/RegisterScreen.js` | Extra — Registro | ✅ |
| `src/screens/auth/ForgotPasswordScreen.js` | 22 — Recuperar contraseña | ✅ |
| `src/screens/auth/EmailVerificationScreen.js` | Extra — Verificación email | ✅ |
| `src/screens/home/HomeScreen.js` | 4 — Comparador (Brasil fijo) | ✅ |
| `src/screens/results/ResultsScreen.js` | 7 — Resultados | ✅ |
| `src/screens/results/EmptyResultsScreen.js` | 8 — Resultados vacíos | ✅ |
| `src/screens/results/LoadingResultsScreen.js` | 9 — Skeleton carga | ✅ |
| `src/screens/wallets/WalletsScreen.js` | 16 — Directorio billeteras | ✅ |
| `src/screens/wallets/WalletDetailScreen.js` | 10 — Detalle billetera | ✅ ⚠️ ver bugs |
| `src/screens/wallets/WalletProfileScreen.js` | 17 — Perfil billetera | ✅ |
| `src/screens/wallets/WalletCompareScreen.js` | 11 — Comparación 2 billeteras | ✅ |
| `src/screens/alerts/AlertsScreen.js` | 13 — Lista alertas | ✅ |
| `src/screens/alerts/CreateAlertScreen.js` | 14 — Crear alerta | ✅ |
| `src/screens/alerts/PushNotificationScreen.js` | 15 — Notificación push | ✅ |
| `src/screens/profile/HistoryScreen.js` | 12 — Historial | ✅ |
| `src/screens/profile/ProfileScreen.js` | 18 — Perfil usuario | ✅ |
| `src/screens/profile/EditProfileScreen.js` | Extra — Editar perfil | ✅ |
| `src/screens/profile/SettingsScreen.js` | 19 — Configuración | ✅ |
| `src/screens/profile/FavoritesScreen.js` | 20 — Favoritos | ✅ |
| `src/screens/error/ErrorScreen.js` | 23 — Sin conexión | ✅ |

---

## 🔜 Próximos pasos

1. **Fix bug params WalletDetail**: alinear `ResultsScreen` (que pasa `{ wallet }`) con `WalletDetailScreen` (que espera `{ walletName }`). Lo más limpio es que ResultsScreen pase `walletName: wallet.name`.
2. **Historial persistente**: usar `AsyncStorage` para guardar cada búsqueda al navegar a Results.
3. **API real de cotizaciones**: reemplazar los rates hardcodeados en `PROVIDERS` por llamadas a una API.

---

## 💡 Notas para Claude Code

- Siempre usar `StyleSheet.create()` para estilos, nunca inline
- Los colores van en un objeto `colors` al tope del archivo o en `src/theme/colors.js`
- Usar `SafeAreaView` de `react-native-safe-area-context`, no de `react-native`
- Para navegación usar `useNavigation()` hook dentro de componentes
- Los parámetros entre pantallas van con `navigation.navigate('ResultsScreen', { amount, currency })`
- Respetar el design system definido arriba — mismos colores, radios y tipografía
- Para acceder al usuario autenticado usar `const { user } = useAuth()` — `user` es el objeto Firebase User (puede ser `null` si no hay sesión)
- **COMPILAR SIEMPRE desde `C:\dev\CB\android`**, nunca desde el path largo original
- Si hay cambios en `AndroidManifest.xml` o nuevos módulos nativos → recompilar APK. Si es solo JS → `r` en Metro alcanza
- Google Sign-In usa el cliente **Desktop app** (`276300901779-kdko463f6u3hq58fv0r0duattluo7l1m`) con redirect hardcodeado — NO intentar usar `makeRedirectUri({ useProxy: true })` en este setup, devuelve `exp://127.0.0.1:8081` ignorando el parámetro

---

## 🖥️ Cómo abrir el proyecto en otra PC

### Requisitos previos
1. **Node.js** — descargar desde https://nodejs.org (versión LTS)
2. **Android Studio** — descargar desde https://developer.android.com/studio
   - Al instalar, asegurarse de incluir: Android SDK, Android Emulator, Android Virtual Device
3. **JDK 17** — viene incluido con Android Studio

### Configuración del SDK
En Android Studio → SDK Manager → instalar:
- Android SDK Platform 35 (o la más reciente)
- Android SDK Build-Tools
- CMake 3.22.1
- NDK

### Pasos

```bash
# 1. Clonar o copiar el proyecto a la PC nueva

# 2. Instalar dependencias
npm install

# 3. Crear android/local.properties con la ruta del SDK
#    (reemplazar con la ruta real en la PC nueva)
echo sdk.dir=C\:\\Users\\TU_USUARIO\\AppData\\Local\\Android\\Sdk > android/local.properties

# 4. Crear el emulador en Android Studio
#    Device Manager → Create Virtual Device → Pixel 8 → API 36

# 5. Levantar Metro (Terminal 1)
npx expo start --clear

# 6. Compilar e instalar APK (Terminal 2)
cd android
.\gradlew.bat app:assembleDebug
adb install -r app\build\outputs\apk\debug\app-debug.apk

# 7. Abrir la app "ComparadorBilleteras" en el emulador
#    Desde ese momento los cambios se recargan con R en Metro
```

### Notas importantes
- El primer build tarda ~5 minutos (descarga Gradle y compila código nativo)
- Gradle debe ser **8.8** — no cambiar `gradle-wrapper.properties`
- **Path largo en Windows:** el path del proyecto tiene espacios y es muy largo, lo que hace fallar la compilación de C++ de `react-native-screens` con CMake. Solución: crear un junction point antes de compilar:
  ```powershell
  New-Item -ItemType Directory -Path "C:\dev" -Force
  cmd /c mklink /J "C:\dev\CB" "RUTA_COMPLETA_AL_PROYECTO"
  # Compilar siempre desde C:\dev\CB\android
  ```
- Solo compilar para arquitectura **x86_64** (para emulador) — ya está configurado en `gradle.properties`
- Para builds futuros (solo cambios JS): solo necesitás Metro corriendo + presionar `r`