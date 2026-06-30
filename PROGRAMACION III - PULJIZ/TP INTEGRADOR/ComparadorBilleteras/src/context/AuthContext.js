import { createContext, useContext, useState, useEffect } from 'react';
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
import { auth } from '../config/firebase';

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

  useEffect(() => {
    const unsubscribe = onAuthStateChanged(auth, async (firebaseUser) => {
      if (firebaseUser && !firebaseUser.emailVerified) {
        await firebaseSignOut(auth);
        setUser(null);
      } else {
        setUser(firebaseUser ?? null);
      }
    });
    return unsubscribe;
  }, []);

  const signInWithEmail = (email, password) =>
    signInWithEmailAndPassword(auth, email, password);

  const register = async (email, password, name) => {
    const cred = await createUserWithEmailAndPassword(auth, email, password);
    if (name) await updateProfile(cred.user, { displayName: name });
    await sendEmailVerification(cred.user);
    await firebaseSignOut(auth);
    return cred;
  };

  const resendVerification = () => {
    if (!auth.currentUser) throw new Error('No hay sesión activa');
    return sendEmailVerification(auth.currentUser);
  };

  const signInWithGoogleCredential = (idToken) => {
    const credential = GoogleAuthProvider.credential(idToken);
    return signInWithCredential(auth, credential);
  };

  const resetPassword = (email) => sendPasswordResetEmail(auth, email);

  const signOut = () => firebaseSignOut(auth);

  return (
    <AuthContext.Provider
      value={{ user, loading: user === undefined, signInWithEmail, register, resendVerification, signInWithGoogleCredential, resetPassword, signOut }}
    >
      {children}
    </AuthContext.Provider>
  );
}

export const useAuth = () => useContext(AuthContext);
