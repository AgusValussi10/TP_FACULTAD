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

const FIREBASE_ERRORS = {
  'auth/invalid-email': 'Email inválido.',
  'auth/user-not-found': 'No existe cuenta con ese email.',
  'auth/wrong-password': 'Contraseña incorrecta.',
  'auth/invalid-credential': 'Email o contraseña incorrectos.',
  'auth/user-disabled': 'Esta cuenta está deshabilitada.',
  'auth/too-many-requests': 'Demasiados intentos. Intentá más tarde.',
  'auth/network-request-failed': 'Sin conexión. Revisá tu internet.',
};

export function getAuthErrorMessage(error) {
  return FIREBASE_ERRORS[error?.code] ?? 'Ocurrió un error. Intentá de nuevo.';
}

export function AuthProvider({ children }) {
  const [user, setUser] = useState(undefined);
  const [apiToken, setApiToken] = useState(null);
  const triggeredAlertsRef = useRef(new Set());

  useEffect(() => {
    const unsubscribe = onAuthStateChanged(auth, async (firebaseUser) => {
      if (firebaseUser && !firebaseUser.emailVerified) {
        await firebaseSignOut(auth);
        setUser(null);
        return;
      }

      setUser(firebaseUser ?? null);

      if (firebaseUser) {
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

  const register = async (email, password, name) => {
    const cred = await createUserWithEmailAndPassword(auth, email, password);
    if (name) await updateProfile(cred.user, { displayName: name });
    await sendEmailVerification(cred.user);
    try {
      await registerBackend(name ?? email, email, password);
    } catch {
      // backend no disponible — continúa
    }
    await firebaseSignOut(auth);
    return cred;
  };

  const resendVerification = () => {
    if (!auth.currentUser) throw new Error('No hay sesión activa');
    return sendEmailVerification(auth.currentUser);
  };

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

    async function checkAlertas() {
      try {
        const [alertas, data] = await Promise.all([
          getAlertas(apiToken),
          getCotizaciones(1),
        ]);
        const tasas = {};
        for (const r of data.resultados ?? []) {
          tasas[r.billetera_id] = r.tasa;
        }
        for (const alerta of alertas) {
          if (!alerta.activa || triggeredAlertsRef.current.has(alerta.id)) continue;
          const tasa = tasas[alerta.billetera_id];
          if (!tasa) continue;
          const disparada = alerta.condicion === 'supera'
            ? tasa >= alerta.valor_objetivo
            : tasa <= alerta.valor_objetivo;
          if (disparada) {
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

  const resetPassword = (email) => sendPasswordResetEmail(auth, email);

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

export const useAuth = () => useContext(AuthContext);
