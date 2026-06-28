import {
  View,
  Text,
  StyleSheet,
  TextInput,
  TouchableOpacity,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { useState } from 'react';
import * as AuthSession from 'expo-auth-session';
import * as WebBrowser from 'expo-web-browser';
import Constants, { ExecutionEnvironment } from 'expo-constants';
import { useAuth } from '../context/AuthContext';

WebBrowser.maybeCompleteAuthSession();

// Desktop app client — no requiere client_secret, usa PKCE
// El redirect URI está registrado en AndroidManifest.xml como intent-filter
const DESKTOP_CLIENT_ID =
  '276300901779-kdko463f6u3hq58fv0r0duattluo7l1m.apps.googleusercontent.com';
const NATIVE_REDIRECT =
  'com.googleusercontent.apps.276300901779-kdko463f6u3hq58fv0r0duattluo7l1m:/';

const GOOGLE_DISCOVERY = {
  authorizationEndpoint: 'https://accounts.google.com/o/oauth2/v2/auth',
  tokenEndpoint: 'https://oauth2.googleapis.com/token',
};

const isExpoGo =
  Constants.executionEnvironment === ExecutionEnvironment.StoreClient;

const colors = {
  primary: '#3b82f6',
  background: '#ffffff',
  surface: '#f8f9fa',
  border: '#dee2e6',
  borderFocus: '#3b82f6',
  textPrimary: '#1a1a1a',
  textSecondary: '#6c757d',
  textMuted: '#adb5bd',
  divider: '#e0e0e0',
};

function getFirebaseError(code) {
  const map = {
    'auth/user-not-found': 'No existe una cuenta con este email',
    'auth/wrong-password': 'Contraseña incorrecta',
    'auth/invalid-email': 'El email no tiene un formato válido',
    'auth/invalid-credential': 'Email o contraseña incorrectos',
    'auth/too-many-requests': 'Demasiados intentos. Esperá unos minutos',
    'auth/network-request-failed': 'Error de conexión. Verificá tu internet',
    'auth/user-disabled': 'Esta cuenta fue deshabilitada',
  };
  return map[code] || 'Ocurrió un error. Intentá de nuevo';
}

export default function LoginScreen({ navigation }) {
  const { signInWithEmail, signInWithGoogleCredential } = useAuth();

  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [emailFocused, setEmailFocused] = useState(false);
  const [passwordFocused, setPasswordFocused] = useState(false);

  // Flujo OAuth para APK: Desktop client + PKCE + scheme nativo
  const [request, , promptAsync] = AuthSession.useAuthRequest(
    {
      clientId: DESKTOP_CLIENT_ID,
      redirectUri: NATIVE_REDIRECT,
      responseType: AuthSession.ResponseType.Code,
      scopes: ['openid', 'email', 'profile'],
      usePKCE: true,
    },
    GOOGLE_DISCOVERY
  );

  const handleLogin = async () => {
    if (!email.trim() || !password) {
      setError('Completá todos los campos');
      return;
    }
    setError('');
    setLoading(true);
    try {
      await signInWithEmail(email.trim(), password);
      navigation.replace('Home');
    } catch (e) {
      setError(getFirebaseError(e.code));
    } finally {
      setLoading(false);
    }
  };

  const handleGoogleLogin = async () => {
    // En Expo Go el scheme nativo no está registrado → informar al usuario
    if (isExpoGo) {
      Alert.alert(
        'Disponible en la app instalada',
        'El inicio de sesión con Google requiere la app instalada como APK. En Expo Go usá email y contraseña.',
        [{ text: 'Entendido' }]
      );
      return;
    }

    if (!request) return;
    setError('');
    setLoading(true);
    try {
      const result = await promptAsync();
      if (result.type === 'success') {
        const tokenResponse = await AuthSession.exchangeCodeAsync(
          {
            clientId: DESKTOP_CLIENT_ID,
            redirectUri: NATIVE_REDIRECT,
            code: result.params.code,
            extraParams: { code_verifier: request.codeVerifier },
          },
          { tokenEndpoint: GOOGLE_DISCOVERY.tokenEndpoint }
        );
        const idToken = tokenResponse.idToken ?? tokenResponse.params?.id_token;
        if (!idToken) throw new Error('No se recibió id_token de Google');
        await signInWithGoogleCredential(idToken);
        navigation.replace('Home');
      } else if (result.type === 'error') {
        setError('Error al iniciar sesión con Google');
      }
    } catch (e) {
      console.error('[Google Sign-In]', e);
      setError('No se pudo iniciar sesión con Google. Intentá de nuevo.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <SafeAreaView style={styles.safe}>
      <KeyboardAvoidingView
        style={styles.flex}
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
      >
        <ScrollView
          contentContainerStyle={styles.content}
          showsVerticalScrollIndicator={false}
          keyboardShouldPersistTaps="handled"
        >
          <View style={styles.logoBlock}>
            <View style={styles.logoIcon}>
              <Ionicons name="card" size={36} color={colors.primary} />
            </View>
            <Text style={styles.appName}>ComparaBilleteras</Text>
            <Text style={styles.tagline}>
              Iniciá sesión para guardar{'\n'}tus alertas y favoritos
            </Text>
          </View>

          <View style={styles.form}>
            {error ? (
              <View style={styles.errorBox}>
                <Ionicons name="alert-circle-outline" size={16} color="#ef4444" />
                <Text style={styles.errorText}>{error}</Text>
              </View>
            ) : null}

            <View style={[styles.inputGroup, emailFocused && styles.inputGroupFocused]}>
              <Ionicons
                name="mail-outline"
                size={20}
                color={emailFocused ? colors.primary : colors.textSecondary}
                style={styles.inputIcon}
              />
              <TextInput
                style={styles.input}
                placeholder="Email"
                placeholderTextColor={colors.textMuted}
                value={email}
                onChangeText={setEmail}
                onFocus={() => setEmailFocused(true)}
                onBlur={() => setEmailFocused(false)}
                keyboardType="email-address"
                autoCapitalize="none"
                autoCorrect={false}
              />
            </View>

            <View style={[styles.inputGroup, passwordFocused && styles.inputGroupFocused]}>
              <Ionicons
                name="lock-closed-outline"
                size={20}
                color={passwordFocused ? colors.primary : colors.textSecondary}
                style={styles.inputIcon}
              />
              <TextInput
                style={styles.input}
                placeholder="Contraseña"
                placeholderTextColor={colors.textMuted}
                value={password}
                onChangeText={setPassword}
                onFocus={() => setPasswordFocused(true)}
                onBlur={() => setPasswordFocused(false)}
                secureTextEntry={!showPassword}
              />
              <TouchableOpacity
                onPress={() => setShowPassword(v => !v)}
                style={styles.eyeBtn}
              >
                <Ionicons
                  name={showPassword ? 'eye-off-outline' : 'eye-outline'}
                  size={20}
                  color={colors.textSecondary}
                />
              </TouchableOpacity>
            </View>

            <TouchableOpacity
              style={styles.forgotLink}
              onPress={() => navigation.navigate('ForgotPassword')}
            >
              <Text style={styles.forgotText}>¿Olvidaste tu contraseña?</Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={[styles.primaryBtn, loading && styles.primaryBtnDisabled]}
              onPress={handleLogin}
              disabled={loading}
              activeOpacity={0.85}
            >
              <Text style={styles.primaryBtnText}>
                {loading ? 'INGRESANDO...' : 'INICIAR SESIÓN'}
              </Text>
            </TouchableOpacity>

            <View style={styles.dividerRow}>
              <View style={styles.dividerLine} />
              <Text style={styles.dividerLabel}>o continuá con</Text>
              <View style={styles.dividerLine} />
            </View>

            <TouchableOpacity
              style={[styles.googleBtn, loading && styles.googleBtnDisabled]}
              onPress={handleGoogleLogin}
              disabled={loading}
              activeOpacity={0.8}
            >
              <Ionicons name="logo-google" size={20} color={colors.textPrimary} />
              <Text style={styles.googleBtnText}>Continuar con Google</Text>
            </TouchableOpacity>
          </View>

          <View style={styles.footer}>
            <Text style={styles.footerText}>¿No tenés cuenta? </Text>
            <TouchableOpacity onPress={() => navigation.navigate('Register')}>
              <Text style={styles.footerLink}>Registrate</Text>
            </TouchableOpacity>
          </View>
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: colors.background },
  flex: { flex: 1 },
  content: {
    flexGrow: 1,
    paddingHorizontal: 20,
    paddingTop: 40,
    paddingBottom: 32,
    justifyContent: 'center',
  },
  logoBlock: { alignItems: 'center', marginBottom: 40, gap: 8 },
  logoIcon: {
    width: 72,
    height: 72,
    borderRadius: 20,
    backgroundColor: '#eff6ff',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 4,
  },
  appName: { fontSize: 24, fontWeight: '700', color: colors.textPrimary },
  tagline: {
    fontSize: 14,
    color: colors.textSecondary,
    textAlign: 'center',
    lineHeight: 20,
  },
  form: { gap: 12 },
  errorBox: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    backgroundColor: '#fef2f2',
    borderRadius: 12,
    borderWidth: 1,
    borderColor: '#fecaca',
    paddingHorizontal: 14,
    paddingVertical: 10,
  },
  errorText: { flex: 1, fontSize: 14, color: '#ef4444' },
  inputGroup: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderRadius: 16,
    borderWidth: 1.5,
    borderColor: colors.border,
    paddingHorizontal: 14,
    height: 52,
  },
  inputGroupFocused: {
    borderColor: colors.borderFocus,
    backgroundColor: colors.background,
  },
  inputIcon: { marginRight: 10 },
  input: { flex: 1, fontSize: 16, color: colors.textPrimary },
  eyeBtn: { padding: 4, marginLeft: 4 },
  forgotLink: { alignSelf: 'flex-end', marginTop: 2 },
  forgotText: { fontSize: 14, color: colors.primary, fontWeight: '500' },
  primaryBtn: {
    backgroundColor: colors.primary,
    borderRadius: 12,
    height: 52,
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 4,
  },
  primaryBtnDisabled: { opacity: 0.6 },
  primaryBtnText: {
    fontSize: 15,
    fontWeight: '700',
    color: '#ffffff',
    letterSpacing: 0.5,
  },
  dividerRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    marginVertical: 4,
  },
  dividerLine: { flex: 1, height: 1, backgroundColor: colors.divider },
  dividerLabel: { fontSize: 13, color: colors.textSecondary },
  googleBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 10,
    borderRadius: 12,
    borderWidth: 1.5,
    borderColor: colors.border,
    height: 52,
    backgroundColor: colors.background,
  },
  googleBtnDisabled: { opacity: 0.5 },
  googleBtnText: { fontSize: 15, fontWeight: '600', color: colors.textPrimary },
  footer: {
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
    marginTop: 32,
  },
  footerText: { fontSize: 14, color: colors.textSecondary },
  footerLink: { fontSize: 14, color: colors.primary, fontWeight: '600' },
});
