import { createContext, useContext, useState, useEffect, useRef } from 'react';
import { Alert } from 'react-native';
import {
  onAuthStateChanged,
  signInWithEmailAndPassword,
  createUserWithEmailAndPassword,
  sendEmailVerification,
  updateProfile,
  signOut as firebaseSignOut,
  sendPasswordResetEmail,
  GoogleAuthProvider,
  signInWithCredential,
} from 'firebase/auth';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { auth } from '../config/firebase';
import { loginBackend, registerBackend, firebaseLoginBackend, getAlertas, getCotizaciones, toggleAlerta } from '../services/api';

const AuthContext = createContext(null);

// Mapeo de códigos de error de Firebase Auth a mensajes legibles en español
const FIREBASE_ERRORS = {
  'auth/invalid-email': 'Email inválido.',
  'auth/user-not-found': 'No existe cuenta con ese email.',
  'auth/wrong-password': 'Contraseña incorrecta.',
  'auth/invalid-credential': 'Email o contraseña incorrectos.',
  'auth/user-disabled': 'Esta cuenta está deshabilitada.',
  'auth/too-many-requests': 'Demasiados intentos. Intentá más tarde.',
  'auth/network-request-failed': 'Sin conexión. Revisá tu internet.',
};

// Función para traducir un error de Firebase a un mensaje amigable, con fallback genérico
export function getAuthErrorMessage(error) {
  return FIREBASE_ERRORS[error?.code] ?? 'Ocurrió un error. Intentá de nuevo.';
}

// Componente que provee el contexto de autenticación (usuario Firebase + JWT del backend) a toda la app
export function AuthProvider({ children }) {
  const [user, setUser] = useState(undefined);
  const [apiToken, setApiToken] = useState(null);
  const triggeredAlertsRef = useRef(new Set());

  // Escucha cambios de sesión de Firebase; si hay sesión, restaura o renueva el JWT del backend (puente Firebase↔backend)
  useEffect(() => {
    const unsubscribe = onAuthStateChanged(auth, async (firebaseUser) => {
      // Si el email no está verificado, se cierra la sesión automáticamente
      if (firebaseUser && !firebaseUser.emailVerified) {
        await firebaseSignOut(auth);
        setUser(null);
        return;
      }

      setUser(firebaseUser ?? null);

      if (firebaseUser) {
        // Si ya hay un JWT guardado (AsyncStorage) lo reutiliza, sino lo pide de nuevo con el ID token de Firebase
        const saved = await AsyncStorage.getItem('apiToken');
        if (saved) {
          setApiToken(saved);
        } else {
          try {
            const idToken = await firebaseUser.getIdToken();
            const { token } = await firebaseLoginBackend(idToken);
            setApiToken(token);
            await AsyncStorage.setItem('apiToken', token);
          } catch {
            // backend no disponible o Firebase Admin no configurado
          }
        }
      }
    });
    return unsubscribe;
  }, []);

  // Función para loguear con email/contraseña: primero Firebase, después intercambia por el JWT propio del backend
  const signInWithEmail = async (email, password) => {
    const cred = await signInWithEmailAndPassword(auth, email, password);
    try {
      const { token } = await loginBackend(email, password);
      setApiToken(token);
      await AsyncStorage.setItem('apiToken', token);
    } catch {
      // backend no disponible — continúa sin token
    }
    return cred;
  };

  // Función para registrar un usuario nuevo: crea cuenta en Firebase, envía verificación y crea el usuario en MySQL
  const register = async (email, password, name) => {
    const cred = await createUserWithEmailAndPassword(auth, email, password);
    if (name) await updateProfile(cred.user, { displayName: name });
    await sendEmailVerification(cred.user);
    try {
      await registerBackend(name ?? email, email, password);
    } catch {
      // backend no disponible — continúa
    }
    // Cierra la sesión de Firebase para forzar el flujo de verificación antes de dejar entrar
    await firebaseSignOut(auth);
    return cred;
  };

  // Función para reenviar el email de verificación de Firebase
  const resendVerification = () => {
    if (!auth.currentUser) throw new Error('No hay sesión activa');
    return sendEmailVerification(auth.currentUser);
  };

  // Función para loguear con Google: intercambia las credenciales de Google por sesión Firebase y JWT del backend
  const signInWithGoogleCredential = async (idToken) => {
    const credential = GoogleAuthProvider.credential(idToken);
    const cred = await signInWithCredential(auth, credential);
    try {
      const { token } = await firebaseLoginBackend(idToken);
      setApiToken(token);
      await AsyncStorage.setItem('apiToken', token);
    } catch {
      // backend no disponible
    }
    return cred;
  };

  // Polling de alertas: chequea cada 60s mientras haya sesión activa
  useEffect(() => {
    if (!apiToken) return;

    // Función para comparar las cotizaciones actuales contra las alertas activas y disparar notificación si se cumple la condición
    async function checkAlertas() {
      try {
        const [alertas, data] = await Promise.all([
          getAlertas(apiToken),
          getCotizaciones(1),
        ]);
        // Arma un mapa billetera_id -> tasa actual para lookup rápido
        const tasas = {};
        for (const r of data.resultados ?? []) {
          tasas[r.billetera_id] = r.tasa;
        }
        for (const alerta of alertas) {
          // Salta alertas pausadas o ya disparadas en esta sesión
          if (!alerta.activa || triggeredAlertsRef.current.has(alerta.id)) continue;
          const tasa = tasas[alerta.billetera_id];
          if (!tasa) continue;
          // Evalúa la condición configurada por el usuario (supera / cae por debajo del valor objetivo)
          const disparada = alerta.condicion === 'supera'
            ? tasa >= alerta.valor_objetivo
            : tasa <= alerta.valor_objetivo;
          if (disparada) {
            // Marca la alerta como ya disparada (evita repetir notificación) y la pausa en el backend
            triggeredAlertsRef.current.add(alerta.id);
            toggleAlerta(apiToken, alerta.id, false).catch(() => {});
            Alert.alert(
              '🔔 Alerta activada',
              `${alerta.billetera_nombre}: la tasa actual es $${Number(tasa).toLocaleString('es-AR')} ARS/BRL`,
            );
          }
        }
      } catch {}
    }

    checkAlertas();
    const interval = setInterval(checkAlertas, 60000);
    return () => clearInterval(interval);
  }, [apiToken]);

  // Función para enviar el email de reseteo de contraseña de Firebase
  const resetPassword = (email) => sendPasswordResetEmail(auth, email);

  // Función para cerrar sesión: limpia Firebase y el JWT del backend guardado localmente
  const signOut = async () => {
    await firebaseSignOut(auth);
    setApiToken(null);
    await AsyncStorage.removeItem('apiToken');
  };

  return (
    <AuthContext.Provider
      value={{ user, loading: user === undefined, apiToken, signInWithEmail, register, resendVerification, signInWithGoogleCredential, resetPassword, signOut }}
    >
      {children}
    </AuthContext.Provider>
  );
}

// Función para consumir el contexto de autenticación desde cualquier pantalla
export const useAuth = () => useContext(AuthContext);
