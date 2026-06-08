# CLAUDE.md — Comparador de Billeteras
> Guía completa para Claude Code. Leé esto antes de tocar cualquier archivo.

---

## 🧠 ¿Qué es esta app?

Una app mobile (React Native + Expo SDK 51) que permite a usuarios argentinos **comparar cotizaciones de billeteras digitales** (Mercado Pago, Ualá, Brubank, etc.) para pagos al exterior, principalmente PIX (Brasil).

El usuario ingresa un monto en moneda extranjera, selecciona el país/método de pago, y la app muestra un ranking de proveedores ordenado por mejor cotización, indicando cuánto ahorra con cada opción.

---

## 🛠️ Stack técnico

| Tecnología | Versión | Notas |
|---|---|---|
| React Native | 0.74.5 | |
| Expo SDK | 51 | NO actualizar a 52+ sin revisar compatibilidad |
| React Navigation | 6.x | Stack + Bottom Tabs |
| react-native-screens | 3.31.1 | Compatible con Nav v6 |
| react-native-gesture-handler | 2.16.1 | |
| react-native-safe-area-context | 4.10.5 | |

---

## 📁 Estructura de carpetas

```
ComparadorBilleteras/
├── src/
│   ├── screens/          ← Una pantalla por archivo
│   │   ├── HomeScreen.js
│   │   ├── ResultsScreen.js
│   │   └── ...
│   ├── components/       ← Componentes reutilizables
│   │   ├── ProviderCard.js
│   │   ├── CountrySelector.js
│   │   └── ...
│   └── navigation/       ← Configuración de navegación
│       └── AppNavigator.js
├── App.js                ← Entry point, solo monta el Navigator
├── android/
│   ├── gradle/wrapper/gradle-wrapper.properties  ← Gradle 8.3
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

> **25 pantallas planificadas.** Las marcadas con ✅ ya tienen archivo `.js`. Las demás están estructuradas para implementar.

---

### 🗂️ BLOQUE 1 — Onboarding

### Pantalla 1 — Splash / Bienvenida (`SplashScreen.js`)

**Descripción:** Primer impacto visual. Se muestra 2-3 segundos y luego navega automáticamente.

**Estructura:**
```
Fondo (azul primario #3b82f6, centrado)
  ├── Logo (ícono 💳 grande, 80px, color blanco)
  ├── Nombre: "ComparaBilleteras" (28px bold, blanco)
  ├── Tagline: "Siempre el mejor cambio" (14px, blanco 80%)
  └── Loading indicator (puntos animados, abajo centrado)
```

---

### Pantalla 2 — Selección de país de residencia (`CountryResidenceScreen.js`)

**Descripción:** El usuario elige su país base. Define la moneda local para mostrar cotizaciones.

**Estructura:**
```
Header (sin back)
  └── Título: "¿Dónde vivís?" (32px bold)

Subtítulo: "Elegí tu país para ver cotizaciones en tu moneda" (gris)

Lista de países (scrollable)
  ├── Card Argentina 🇦🇷 — ARS (borde azul, selected por defecto)
  ├── Card Uruguay 🇺🇾 — UYU
  ├── Card Chile 🇨🇱 — CLP
  ├── Card Paraguay 🇵🇾 — PYG
  └── Card Bolivia 🇧🇴 — BOB

Botón "CONTINUAR →" (azul, full width, fijo abajo)
```

---

### Pantalla 3 — Tutorial / Carrusel de funciones (`OnboardingScreen.js`)

**Descripción:** 3 slides que explican qué hace la app. Dots de paginación. Puede contarse como 1 pantalla con 3 estados.

**Estructura:**
```
Slides (FlatList horizontal, paginado)
  ├── Slide 1
  │     ├── Ilustración: 💱 (ícono grande, 120px, fondo azul claro)
  │     ├── Título: "Compará cotizaciones"
  │     └── Subtítulo: "Encontrá la billetera que te da más pesos por tu divisa"
  ├── Slide 2
  │     ├── Ilustración: 🔔
  │     ├── Título: "Alertas de precio"
  │     └── Subtítulo: "Recibí una notificación cuando la cotización llegue a tu objetivo"
  └── Slide 3
        ├── Ilustración: 📊
        ├── Título: "Siempre actualizado"
        └── Subtítulo: "Cotizaciones en tiempo real de las billeteras más usadas de Argentina"

Dots de paginación (3 puntos, el activo azul)

Botones
  ├── "Omitir" (texto gris, izquierda) — solo en slides 1-2
  └── "Siguiente →" / "COMENZAR" (azul, derecha)
```

---

### 🏠 BLOQUE 2 — Flujo principal (core)

### Pantalla 4 — Home / Comparador (`HomeScreen.js`) ✅

**Estructura:**
```
Header (64px)
  ├── Ícono menú (izquierda)
  ├── Ícono home (centro)
  └── Ícono perfil (derecha)

Contenido (scrollable)
  ├── Título: "Comparador de\nCotizaciones" (32px bold)
  ├── Country Selector
  │     ├── Flag + nombre del país/método (ej: 🇧🇷 Brasil - PIX)
  │     └── Flecha dropdown ▼
  ├── Label: "¿Cuánto vas a pagar?"
  ├── Amount Input Container (fondo gris, centrado)
  │     ├── Monto grande (48px bold, ej: R$ 500,00)
  │     └── Placeholder: "Ejemplo: R$ 500,00"
  ├── Botón primario: "COMPARAR AHORA" (azul, full width)
  ├── Divider
  └── Últimas consultas
        ├── Bullet azul + "500 BRL - Hace 2hs"
        ├── Bullet azul + "1000 BRL - Ayer"
        └── Bullet azul + "250 BRL - Hace 3 días"

Bottom Navigation (70px)
  ├── 📊 (activo)
  ├── 💳
  ├── 📈
  ├── 🔔
  └── ℹ️
```

---

### Pantalla 5 — Teclado numérico emergente (`NumericKeyboardScreen.js`)

**Descripción:** Modal que aparece al tocar el campo de monto. Teclado custom sin usar el teclado del sistema.

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

### Pantalla 6 — Selector de país destino (`CountrySelectorScreen.js`)

**Descripción:** Modal/bottom sheet con la lista expandida de países y un buscador. Estado diferente del selector compacto del Home.

**Estructura:**
```
Bottom Sheet (altura 70% de pantalla)
  ├── Título: "Seleccioná el destino"
  ├── Buscador (input con ícono lupa)
  │
  ├── Sección "Popular"
  │     └── Card Brasil 🇧🇷 — PIX (borde verde, badge "Popular")
  │
  └── Sección "Otros destinos"
        ├── Card USA 🇺🇸 — USD
        ├── Card Europa 🇪🇺 — EUR
        ├── Card México 🇲🇽 — MXN
        └── Card Colombia 🇨🇴 — COP
```

---

### Pantalla 7 — Resultados (`ResultsScreen.js`) ✅

**Recibe como parámetros:** `amount` (número), `currency` (string), `country` (string)

**Estructura:**
```
Header (64px)
  ├── Botón atrás ← (izquierda)
  ├── Título: "Resultados" (centro)
  └── Ícono ⋮ más opciones (derecha)

Contenido (scrollable)
  ├── Contexto: "Pagando 500 BRL" (gris, 14px)
  │
  ├── Card MEJOR OPCIÓN (borde verde, fondo gradiente verde claro)
  │     ├── Badge verde: "💚 Mejor opción"
  │     ├── Nombre: "Mercado Pago" (20px bold)
  │     ├── Precio: "$ 485.230 ARS" (32px bold)
  │     ├── Ahorro: "✓ Ahorrás $15.000 (3.1%)" (verde)
  │     └── Link: "Ver más →" (azul)
  │
  ├── Card opción 2 (borde gris estándar)
  │     ├── Nombre: "Ualá"
  │     ├── Precio: "$ 492.180 ARS"
  │     ├── Ahorro: "Ahorrás $8.050 (1.6%)"
  │     └── Link: "Ver más →"
  │
  ├── Card opción 3 (borde gris estándar)
  │     ├── Nombre: "Brubank"
  │     ├── Precio: "$ 500.230 ARS"
  │     └── Link: "Ver más →"
  │
  └── Botones de acción
        ├── "COMPARTIR" (secundario, gris)
        └── "NUEVA" (primario, azul)
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

### Pantalla 10 — Detalle de billetera (`WalletDetailScreen.js`)

**Descripción:** Aparece al tocar "Ver más →" en una card de resultados. Muestra tipo de cambio exacto, comisiones, límites y tiempo estimado.

**Recibe como parámetro:** `walletName` (string), `currency` (string), `amount` (number)

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

### Pantalla 11 — Comparación lado a lado (`CompareScreen.js`)

**Descripción:** El usuario selecciona 2 billeteras para verlas en tabla comparativa. Feature diferenciador del MVP.

**Recibe como parámetros:** `wallet1`, `wallet2` (objetos con name, rate, etc.)

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
  └─► CountryResidenceScreen (2)
        └─► OnboardingScreen (3)
              └─► HomeScreen (4) ←── Pantalla principal
                    ├── [tocar monto] → NumericKeyboardScreen (5) [modal]
                    ├── [tocar país]  → CountrySelectorScreen (6) [modal]
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

## 🧩 Componentes reutilizables

### `ProviderCard`
Props:
- `provider` (string) — nombre del proveedor
- `price` (string) — monto formateado
- `savings` (string | null) — texto de ahorro
- `savingsPercent` (string | null) — porcentaje
- `isBest` (boolean) — si es la mejor opción
- `onPress` () — callback al presionar "Ver más"

### `CountrySelector`
Props:
- `selectedCountry` (object) — `{ flag, name, currency }`
- `onPress` () — abre el selector

### `AmountInput`
Props:
- `value` (string)
- `currency` (string) — prefijo (R$, USD, etc.)
- `onChange` (function)

---

## 🗺️ Navegación

```javascript
// Stack principal
AppNavigator
├── HomeScreen      (pantalla inicial)
└── ResultsScreen   (recibe params: amount, currency, country)

// Bottom tabs (dentro de HomeScreen o como wrapper)
TabNavigator
├── Tab Comparar    → HomeScreen
├── Tab Billeteras  → WalletsScreen (futuro)
├── Tab Historial   → HistoryScreen (futuro)
├── Tab Alertas     → AlertsScreen (futuro)
└── Tab Info        → InfoScreen (futuro)
```

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

---

## 🚀 Comandos útiles

```bash
# Levantar Metro (dejar corriendo siempre)
npx expo start --dev-client

# Compilar e instalar APK (si Metro ya corre)
cd android && .\gradlew.bat app:assembleDebug
adb install app\build\outputs\apk\debug\app-debug.apk

# Ver dispositivos conectados
adb devices

# Limpiar build
cd android && .\gradlew.bat clean
```

---

## 📋 Estado actual del proyecto

### Infraestructura
- [x] Setup inicial con Expo SDK 51
- [x] Dependencias instaladas (React Navigation v6, gesture handler, screens, safe area)
- [x] Emulador Pixel 8 API 36 funcionando
- [x] Hot reload funcionando (Metro + APK debug)
- [x] Estructura de carpetas creada (`src/screens`, `src/components`, `src/navigation`)
- [x] `AppNavigator.js` — Stack con Home, Results, History
- [x] `BottomNav.js` — componente compartido de navegación inferior
- [x] Brasil PIX destacado con badge "Popular" y borde verde
- [x] Animaciones: cards con entrada escalonada, botón con efecto de escala
- [x] 10 billeteras argentinas: Mercado Pago, Ualá, Bimo, Naranja X, Prex, Brubank, Personal Pay, Lemon Cash, Modo, Cuenta DNI

### Pantallas implementadas (3/25)
- [x] `HomeScreen.js` — Pantalla 4: comparador con selector de país, input de monto, últimas consultas
- [x] `ResultsScreen.js` — Pantalla 7: ranking de 10 billeteras con logos, ahorro y animaciones
- [x] `HistoryScreen.js` — Pantalla 12: historial de consultas con datos mock, tap para repetir búsqueda

### Pantallas pendientes (22/25)
- [ ] `SplashScreen.js` — Pantalla 1: logo + tagline, auto-navega al onboarding
- [ ] `CountryResidenceScreen.js` — Pantalla 2: selección de país de residencia
- [ ] `OnboardingScreen.js` — Pantalla 3: carrusel 3 slides con funciones de la app
- [ ] `NumericKeyboardScreen.js` — Pantalla 5: teclado numérico custom (modal)
- [ ] `CountrySelectorScreen.js` — Pantalla 6: selector de destino con buscador (modal)
- [ ] `EmptyResultsScreen.js` — Pantalla 8: estado vacío cuando no hay cotizaciones
- [ ] `LoadingResultsScreen.js` — Pantalla 9: skeleton loaders de carga
- [ ] `WalletDetailScreen.js` — Pantalla 10: detalle de billetera (tipo de cambio, límites, comisiones)
- [ ] `CompareScreen.js` — Pantalla 11: tabla comparativa lado a lado de 2 billeteras
- [ ] `AlertsScreen.js` — Pantalla 13: lista de alertas configuradas
- [ ] `CreateAlertScreen.js` — Pantalla 14: formulario para nueva alerta
- [ ] `PushNotificationScreen.js` — Pantalla 15: mockup de notificación push recibida
- [ ] `WalletsScreen.js` — Pantalla 16: directorio de billeteras con rating y países
- [ ] `WalletProfileScreen.js` — Pantalla 17: perfil estático de billetera (pros/contras, link)
- [ ] `ProfileScreen.js` — Pantalla 18: perfil del usuario con historial y configuración rápida
- [ ] `SettingsScreen.js` — Pantalla 19: ajustes (moneda, notificaciones, tema, idioma)
- [ ] `FavoritesScreen.js` — Pantalla 20: billeteras y pares favoritos del usuario
- [ ] `LoginScreen.js` — Pantalla 21: login con email/contraseña y Google
- [ ] `ForgotPasswordScreen.js` — Pantalla 22: recuperar contraseña (con estado de éxito)
- [ ] `ErrorScreen.js` — Pantalla 23: sin conexión con botón reintentar
- [ ] `ExternalRedirectModal.js` — Pantalla 24: confirmación antes de abrir app externa
- [ ] `LoadingSplashScreen.js` — Pantalla 25: splash de carga con barra de progreso y versión

### Funcionalidad pendiente
- [ ] Historial persistente con AsyncStorage
- [ ] Integración con API real de cotizaciones
- [ ] Actualizar `AppNavigator.js` para incluir las 22 pantallas nuevas
- [ ] Actualizar `BottomNav.js` para conectar tabs a las pantallas de sus secciones

---

## 🗂️ Archivos clave

| Archivo | Pantalla | Estado |
|---|---|---|
| `src/navigation/AppNavigator.js` | Navigator principal | ✅ |
| `src/components/BottomNav.js` | Barra de navegación inferior | ✅ |
| `src/screens/HomeScreen.js` | 4 — Comparador | ✅ |
| `src/screens/ResultsScreen.js` | 7 — Resultados | ✅ |
| `src/screens/HistoryScreen.js` | 12 — Historial | ✅ |
| `src/screens/SplashScreen.js` | 1 — Splash | ⏳ |
| `src/screens/CountryResidenceScreen.js` | 2 — País de residencia | ⏳ |
| `src/screens/OnboardingScreen.js` | 3 — Tutorial carrusel | ⏳ |
| `src/screens/NumericKeyboardScreen.js` | 5 — Teclado numérico | ⏳ |
| `src/screens/CountrySelectorScreen.js` | 6 — Selector destino | ⏳ |
| `src/screens/EmptyResultsScreen.js` | 8 — Resultados vacíos | ⏳ |
| `src/screens/LoadingResultsScreen.js` | 9 — Skeleton carga | ⏳ |
| `src/screens/WalletDetailScreen.js` | 10 — Detalle billetera | ⏳ |
| `src/screens/CompareScreen.js` | 11 — Comparación 2 billeteras | ⏳ |
| `src/screens/AlertsScreen.js` | 13 — Lista alertas | ⏳ |
| `src/screens/CreateAlertScreen.js` | 14 — Crear alerta | ⏳ |
| `src/screens/PushNotificationScreen.js` | 15 — Notificación push | ⏳ |
| `src/screens/WalletsScreen.js` | 16 — Directorio billeteras | ⏳ |
| `src/screens/WalletProfileScreen.js` | 17 — Perfil billetera | ⏳ |
| `src/screens/ProfileScreen.js` | 18 — Perfil usuario | ⏳ |
| `src/screens/SettingsScreen.js` | 19 — Configuración | ⏳ |
| `src/screens/FavoritesScreen.js` | 20 — Favoritos | ⏳ |
| `src/screens/LoginScreen.js` | 21 — Login / Registro | ⏳ |
| `src/screens/ForgotPasswordScreen.js` | 22 — Recuperar contraseña | ⏳ |
| `src/screens/ErrorScreen.js` | 23 — Sin conexión | ⏳ |
| `src/screens/ExternalRedirectModal.js` | 24 — Confirmación redirección | ⏳ |
| `src/screens/LoadingSplashScreen.js` | 25 — Splash de carga | ⏳ |

---

## 🔜 Próximos pasos sugeridos

1. Persistir historial con `AsyncStorage` (guardar cada búsqueda al navegar a Results)
2. Conectar con API real de cotizaciones
3. Pantalla de Billeteras con info y links de cada proveedor
4. Pantalla de Alertas para notificar cuando una cotización supera un umbral

---

## 💡 Notas para Claude Code

- Siempre usar `StyleSheet.create()` para estilos, nunca inline
- Los colores van en un objeto `colors` al tope del archivo o en `src/theme/colors.js`
- Usar `SafeAreaView` de `react-native-safe-area-context`, no de `react-native`
- Para navegación usar `useNavigation()` hook dentro de componentes
- Los parámetros entre pantallas van con `navigation.navigate('ResultsScreen', { amount, currency })`
- Respetar el design system definido arriba — mismos colores, radios y tipografía

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
- Solo compilar para arquitectura **x86_64** (para emulador) — ya está configurado en `gradle.properties`
- Para builds futuros (solo cambios JS): solo necesitás Metro corriendo + presionar `r`