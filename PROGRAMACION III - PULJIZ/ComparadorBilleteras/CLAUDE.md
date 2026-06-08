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

### Pantalla 1 — Home / Comparador (`HomeScreen.js`)

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

### Pantalla 2 — Resultados (`ResultsScreen.js`)

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

- [x] Setup inicial con Expo SDK 51
- [x] Dependencias instaladas (React Navigation v6, gesture handler, screens, safe area)
- [x] Emulador Pixel 8 API 36 funcionando
- [x] Hot reload funcionando
- [x] Estructura de carpetas creada (`src/screens`, `src/components`, `src/navigation`)
- [ ] HomeScreen implementada
- [ ] ResultsScreen implementada
- [ ] Navegación configurada
- [ ] Datos mock de cotizaciones
- [ ] Integración con API real de cotizaciones

---

## 🔜 Próximos pasos sugeridos

1. Crear `src/navigation/AppNavigator.js` con Stack Navigator
2. Crear `src/screens/HomeScreen.js` según wireframe
3. Crear `src/screens/ResultsScreen.js` según wireframe
4. Extraer componentes reutilizables (`ProviderCard`, `CountrySelector`)
5. Agregar datos mock de cotizaciones en `src/data/mockData.js`
6. Conectar navegación entre pantallas

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