import {
  View,
  Text,
  StyleSheet,
  TextInput,
  TouchableOpacity,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { useState } from 'react';
import { useAuth } from '../../context/AuthContext';

const colors = {
  primary: '#3b82f6',
  primaryDark: '#2563eb',
  success: '#10b981',
  background: '#ffffff',
  surface: '#f8f9fa',
  border: '#dee2e6',
  borderFocus: '#3b82f6',
  textPrimary: '#1a1a1a',
  textSecondary: '#6c757d',
  textMuted: '#adb5bd',
  divider: '#e0e0e0',
};

const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

// Funcion para la pantalla de recuperación de contraseña.
function getFirebaseError(code) {
  const map = {
    'auth/user-not-found': 'No existe una cuenta con este email',
    'auth/invalid-email': 'El email no tiene un formato válido',
    'auth/too-many-requests': 'Demasiados intentos. Esperá unos minutos',
    'auth/network-request-failed': 'Error de conexión. Verificá tu internet',
  };
  return map[code] || 'Ocurrió un error. Intentá de nuevo';
}

export default function ForgotPasswordScreen({ navigation }) {
  const { resetPassword } = useAuth();

  const [email, setEmail] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [sent, setSent] = useState(false);
  const [emailFocused, setEmailFocused] = useState(false);

  const handleSend = async () => {
    if (!EMAIL_REGEX.test(email.trim())) {
      setError('Ingresá un email con formato válido');
      return;
    }
    setError('');
    setLoading(true);
    try {
      await resetPassword(email.trim());
      setSent(true);
    } catch (e) {
      setError(getFirebaseError(e.code));
    } finally {
      setLoading(false);
    }
  };

  return (
    <SafeAreaView style={styles.safe}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
          <Ionicons name="arrow-back" size={24} color={colors.textPrimary} />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Recuperar contraseña</Text>
        <View style={styles.headerRight} />
      </View>

      <KeyboardAvoidingView
        style={styles.flex}
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
      >
        <ScrollView
          contentContainerStyle={styles.content}
          showsVerticalScrollIndicator={false}
          keyboardShouldPersistTaps="handled"
        >
          {sent ? (
            <View style={styles.successContainer}>
              <View style={styles.successIcon}>
                <Ionicons name="checkmark-circle" size={64} color={colors.success} />
              </View>
              <Text style={styles.successTitle}>¡Listo!</Text>
              <Text style={styles.successMessage}>
                Revisá tu casilla, te enviamos un link para recuperar tu contraseña.
              </Text>
              <Text style={styles.emailHighlight}>{email.trim()}</Text>
              <Text style={styles.successHint}>
                Si no lo encontrás, revisá la carpeta de spam.
              </Text>
              <TouchableOpacity
                style={styles.backToLoginBtn}
                onPress={() => navigation.goBack()}
              >
                <Ionicons name="arrow-back" size={16} color={colors.primary} />
                <Text style={styles.backToLoginText}>Volver al login</Text>
              </TouchableOpacity>
            </View>
          ) : (
            <View style={styles.formContainer}>
              <View style={styles.iconBlock}>
                <View style={styles.keyIcon}>
                  <Ionicons name="key-outline" size={40} color={colors.textSecondary} />
                </View>
              </View>

              <Text style={styles.subtitle}>
                Ingresá tu email y te enviamos un link para resetear tu contraseña.
              </Text>

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

              <TouchableOpacity
                style={[styles.primaryBtn, loading && styles.primaryBtnDisabled]}
                onPress={handleSend}
                disabled={loading}
                activeOpacity={0.85}
              >
                <Text style={styles.primaryBtnText}>
                  {loading ? 'ENVIANDO...' : 'ENVIAR LINK'}
                </Text>
              </TouchableOpacity>

              <TouchableOpacity
                style={styles.backToLoginBtn}
                onPress={() => navigation.goBack()}
              >
                <Ionicons name="arrow-back" size={16} color={colors.primary} />
                <Text style={styles.backToLoginText}>Volver al login</Text>
              </TouchableOpacity>
            </View>
          )}
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: colors.background },
  flex: { flex: 1 },
  header: {
    height: 64,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 20,
    backgroundColor: colors.background,
    borderBottomWidth: 1,
    borderBottomColor: colors.divider,
  },
  backBtn: { width: 36, height: 36, alignItems: 'center', justifyContent: 'center' },
  headerTitle: { fontSize: 18, fontWeight: '700', color: colors.textPrimary },
  headerRight: { width: 36 },
  content: { flexGrow: 1, paddingHorizontal: 20, paddingTop: 40, paddingBottom: 32 },
  formContainer: { gap: 16 },
  iconBlock: { alignItems: 'center', marginBottom: 8 },
  keyIcon: {
    width: 96,
    height: 96,
    borderRadius: 48,
    backgroundColor: colors.surface,
    alignItems: 'center',
    justifyContent: 'center',
  },
  subtitle: { fontSize: 15, color: colors.textSecondary, lineHeight: 22, textAlign: 'center' },
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
  inputGroupFocused: { borderColor: colors.borderFocus, backgroundColor: colors.background },
  inputIcon: { marginRight: 10 },
  input: { flex: 1, fontSize: 16, color: colors.textPrimary },
  primaryBtn: {
    backgroundColor: colors.primary,
    borderRadius: 12,
    height: 52,
    alignItems: 'center',
    justifyContent: 'center',
  },
  primaryBtnDisabled: { opacity: 0.6 },
  primaryBtnText: { fontSize: 15, fontWeight: '700', color: '#ffffff', letterSpacing: 0.5 },
  backToLoginBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 6,
    paddingVertical: 8,
    marginTop: 4,
  },
  backToLoginText: { fontSize: 14, color: colors.primary, fontWeight: '500' },
  successContainer: { flex: 1, alignItems: 'center', paddingTop: 20, gap: 16 },
  successIcon: { marginBottom: 8 },
  successTitle: { fontSize: 28, fontWeight: '700', color: colors.textPrimary },
  successMessage: { fontSize: 15, color: colors.textSecondary, textAlign: 'center', lineHeight: 22 },
  emailHighlight: { fontSize: 15, fontWeight: '600', color: colors.primary },
  successHint: { fontSize: 13, color: colors.textMuted, textAlign: 'center' },
});
