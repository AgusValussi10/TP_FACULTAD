import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { useState } from 'react';

const colors = {
  primary: '#3b82f6',
  primaryDark: '#2563eb',
  success: '#10b981',
  background: '#ffffff',
  surface: '#f8f9fa',
  border: '#dee2e6',
  textPrimary: '#1a1a1a',
  textSecondary: '#6c757d',
  textMuted: '#adb5bd',
  divider: '#e0e0e0',
};

// Pantalla que avisa que se envió el email de verificación y permite reenviarlo
export default function EmailVerificationScreen({ navigation, route }) {
  const email = route.params?.email ?? '';
  const [resending, setResending] = useState(false);

  // Función para reenviar el email de verificación (mock: solo muestra el aviso)
  const handleResend = async () => {
    setResending(true);
    try {
      Alert.alert(
        'Email enviado',
        'Revisá tu casilla de correo y seguí las instrucciones.',
        [{ text: 'OK' }]
      );
    } catch {
      Alert.alert('Error', 'No se pudo reenviar el email. Intentá de nuevo.');
    } finally {
      setResending(false);
    }
  };

  return (
    <SafeAreaView style={styles.safe}>
      <View style={styles.header}>
        <Text style={styles.headerTitle}>Verificá tu cuenta</Text>
      </View>

      <View style={styles.content}>
        <View style={styles.iconWrapper}>
          <View style={styles.iconOuter}>
            <View style={styles.iconInner}>
              <Ionicons name="mail" size={52} color={colors.primary} />
            </View>
          </View>
          <View style={styles.checkBadge}>
            <Ionicons name="send" size={14} color="#ffffff" />
          </View>
        </View>

        <Text style={styles.title}>Email enviado</Text>

        <Text style={styles.description}>
          Te mandamos un link de verificación a:
        </Text>

        <View style={styles.emailBadge}>
          <Ionicons name="mail-outline" size={16} color={colors.primary} />
          <Text style={styles.emailText}>
            {email || 'tu casilla de correo'}
          </Text>
        </View>

        <Text style={styles.hint}>
          Abrí el link del email para activar tu cuenta. Si no lo encontrás, revisá la carpeta de spam.
        </Text>

        <View style={styles.actions}>
          <TouchableOpacity
            style={[styles.outlineBtn, resending && styles.outlineBtnDisabled]}
            onPress={handleResend}
            disabled={resending}
            activeOpacity={0.8}
          >
            <Ionicons name="refresh-outline" size={18} color={colors.primary} />
            <Text style={styles.outlineBtnText}>
              {resending ? 'Reenviando...' : 'Reenviar email'}
            </Text>
          </TouchableOpacity>

          <TouchableOpacity
            style={styles.primaryBtn}
            onPress={() => navigation.replace('Login')}
            activeOpacity={0.85}
          >
            <Text style={styles.primaryBtnText}>Ya verifiqué, ir al login</Text>
            <Ionicons name="arrow-forward" size={18} color="#ffffff" />
          </TouchableOpacity>
        </View>

        <Text style={styles.footerNote}>
          Una vez verificado tu email, iniciá sesión con tu email y contraseña para acceder a la app.
        </Text>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: colors.background },
  header: {
    height: 64,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 20,
    backgroundColor: colors.background,
    borderBottomWidth: 1,
    borderBottomColor: colors.divider,
  },
  headerTitle: { fontSize: 18, fontWeight: '700', color: colors.textPrimary },
  content: { flex: 1, alignItems: 'center', justifyContent: 'center', paddingHorizontal: 32, gap: 16 },
  iconWrapper: { position: 'relative', marginBottom: 8 },
  iconOuter: {
    width: 120,
    height: 120,
    borderRadius: 60,
    backgroundColor: '#eff6ff',
    alignItems: 'center',
    justifyContent: 'center',
  },
  iconInner: {
    width: 88,
    height: 88,
    borderRadius: 44,
    backgroundColor: '#dbeafe',
    alignItems: 'center',
    justifyContent: 'center',
  },
  checkBadge: {
    position: 'absolute',
    bottom: 4,
    right: 4,
    width: 28,
    height: 28,
    borderRadius: 14,
    backgroundColor: colors.primary,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 2,
    borderColor: colors.background,
  },
  title: { fontSize: 28, fontWeight: '700', color: colors.textPrimary, textAlign: 'center' },
  description: { fontSize: 15, color: colors.textSecondary, textAlign: 'center' },
  emailBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    backgroundColor: '#eff6ff',
    borderRadius: 12,
    paddingHorizontal: 16,
    paddingVertical: 10,
  },
  emailText: { fontSize: 14, fontWeight: '600', color: colors.primary },
  hint: { fontSize: 13, color: colors.textMuted, textAlign: 'center', lineHeight: 19 },
  actions: { width: '100%', gap: 12, marginTop: 8 },
  outlineBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    borderRadius: 12,
    borderWidth: 1.5,
    borderColor: colors.primary,
    height: 52,
  },
  outlineBtnDisabled: { opacity: 0.6 },
  outlineBtnText: { fontSize: 15, fontWeight: '600', color: colors.primary },
  primaryBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    backgroundColor: colors.primary,
    borderRadius: 12,
    height: 52,
  },
  primaryBtnText: { fontSize: 15, fontWeight: '700', color: '#ffffff' },
  footerNote: { fontSize: 12, color: colors.textMuted, textAlign: 'center', lineHeight: 18, marginTop: 8 },
});
