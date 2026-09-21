import { initializeApp } from 'firebase/app';
import { initializeAuth, getReactNativePersistence } from 'firebase/auth';
import AsyncStorage from '@react-native-async-storage/async-storage';

// ─── CONFIGURACIÓN DE FIREBASE ────────────────────────────────────────────────
// 1. Ir a https://console.firebase.google.com
// 2. Crear proyecto → "ComparaBilleteras"
// 3. Agregar app web (</>) → copiar el firebaseConfig de abajo
// 4. Authentication → Activar: Email/Contraseña y Google
// ─────────────────────────────────────────────────────────────────────────────
const firebaseConfig = {
  apiKey: 'AIzaSyA3jpt2roBRuyQ9Ni8Aszgiq7U-BwrM0go',
  authDomain: 'comparabilleteras-1e6b7.firebaseapp.com',
  projectId: 'comparabilleteras-1e6b7',
  storageBucket: 'comparabilleteras-1e6b7.firebasestorage.app',
  messagingSenderId: '276300901779',
  appId: '1:276300901779:web:92d7393b6569f09dc20c66',
};

// ─── GOOGLE SIGN-IN (Web Client ID) ──────────────────────────────────────────
// Firebase Console → Authentication → Sign-in method → Google
// → Expandir → copiar "ID de cliente web"
export const GOOGLE_WEB_CLIENT_ID = '276300901779-k09s2dj857ds18efarm268bdj7rmau1g.apps.googleusercontent.com';

// ─── SETUP DE EXPO AUTH PROXY ─────────────────────────────────────────────────
// Para que Google Sign-In funcione en desarrollo:
// 1. Correr: npx expo whoami (anotá tu usuario Expo)
// 2. En Google Cloud Console → Credentials → editar el cliente OAuth web de Firebase
// 3. Agregar en "Authorized redirect URIs":
//    https://auth.expo.io/@TU_USUARIO_EXPO/ComparadorBilleteras
// ─────────────────────────────────────────────────────────────────────────────

// Inicializa la app de Firebase con la config del proyecto
const app = initializeApp(firebaseConfig);

// Instancia de auth de Firebase, persistiendo la sesión en AsyncStorage (necesario en React Native)
export const auth = initializeAuth(app, {
  persistence: getReactNativePersistence(AsyncStorage),
});
