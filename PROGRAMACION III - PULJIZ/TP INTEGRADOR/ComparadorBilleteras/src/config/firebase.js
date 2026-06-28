import { initializeApp } from 'firebase/app';
import { getAuth } from 'firebase/auth';

// ─── Completá con los valores de tu proyecto en Firebase Console ──────────────
const firebaseConfig = {
  apiKey: 'TU_API_KEY',
  authDomain: 'TU_PROJECT_ID.firebaseapp.com',
  projectId: 'TU_PROJECT_ID',
  storageBucket: 'TU_PROJECT_ID.appspot.com',
  messagingSenderId: 'TU_SENDER_ID',
  appId: 'TU_APP_ID',
};
// ─────────────────────────────────────────────────────────────────────────────

const app = initializeApp(firebaseConfig);

// Persistencia de sesión entre reinicios de la app:
// 1) npx expo install @react-native-async-storage/async-storage
// 2) Reemplazá las dos líneas de abajo con:
//
//    import { initializeAuth, getReactNativePersistence } from 'firebase/auth';
//    import AsyncStorage from '@react-native-async-storage/async-storage';
//    export const auth = initializeAuth(app, {
//      persistence: getReactNativePersistence(AsyncStorage),
//    });

export const auth = getAuth(app);
