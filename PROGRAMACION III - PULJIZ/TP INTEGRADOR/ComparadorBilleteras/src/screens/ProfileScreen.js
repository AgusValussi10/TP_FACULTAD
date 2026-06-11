import { View, Text, StyleSheet, ScrollView, TouchableOpacity, StatusBar, Alert } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { useAuth } from '../context/AuthContext';
import BottomNav from '../components/BottomNav';

const colors = {
  primary: '#3b82f6',
  danger: '#ef4444',
  background: '#ffffff',
  surface: '#f8f9fa',
  border: '#dee2e6',
  textPrimary: '#1a1a1a',
  textSecondary: '#6c757d',
  textMuted: '#adb5bd',
  divider: '#e0e0e0',
};

const RECENT_SEARCHES = [
  { id: 1, amount: 500, currency: 'BRL', wallet: 'Mercado Pago', when: 'hace 2hs' },
  { id: 2, amount: 1000, currency: 'BRL', wallet: 'Ualá', when: 'ayer' },
  { id: 3, amount: 250, currency: 'BRL', wallet: 'Bimo', when: 'hace 3 días' },
];

export default function ProfileScreen({ navigation }) {
  const { user, signOut } = useAuth();

  const displayName = user?.displayName || user?.email || 'Invitado';
  const initial = displayName.charAt(0).toUpperCase();
  const email = user?.email ?? null;

  const handleSignOut = () => {
    Alert.alert('Cerrar sesión', '¿Seguro que querés cerrar sesión?', [
      { text: 'Cancelar', style: 'cancel' },
      {
        text: 'Cerrar sesión',
        style: 'destructive',
        onPress: async () => {
          await signOut();
          navigation.replace('Login');
        },
      },
    ]);
  };

  return (
    <SafeAreaView style={styles.safe}>
      <StatusBar barStyle="dark-content" />
      <View style={styles.header}>
        <Text style={styles.headerTitle}>Mi Perfil</Text>
        <Ionicons name="pencil-outline" size={22} color={colors.textMuted} />
      </View>

      <ScrollView style={styles.scroll} contentContainerStyle={styles.scrollContent}>
        {/* Avatar */}
        <View style={styles.avatarBlock}>
          <View style={styles.avatar}>
            <Text style={styles.avatarText}>{initial}</Text>
          </View>
          <Text style={styles.displayName}>{displayName}</Text>
          {email && <Text style={styles.email}>{email}</Text>}
          <View style={styles.countryBadge}>
            <Text style={styles.countryBadgeText}>🇦🇷 Argentina — ARS</Text>
          </View>
        </View>

        {/* Configuración rápida */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Configuración rápida</Text>
          <View style={styles.configRow}>
            <Text style={styles.configLabel}>Moneda base</Text>
            <Text style={styles.configValue}>ARS</Text>
          </View>
          <View style={styles.divider} />
          <View style={styles.configRow}>
            <Text style={styles.configLabel}>País</Text>
            <Text style={styles.configValue}>Argentina</Text>
          </View>
        </View>

        {/* Búsquedas recientes */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Búsquedas guardadas</Text>
          {RECENT_SEARCHES.map((item) => (
            <View key={item.id} style={styles.searchItem}>
              <View style={styles.searchDot} />
              <Text style={styles.searchText}>
                {item.amount} {item.currency} — {item.wallet} — {item.when}
              </Text>
            </View>
          ))}
        </View>

        {/* Cerrar sesión */}
        {user && (
          <TouchableOpacity style={styles.signOutButton} onPress={handleSignOut} activeOpacity={0.7}>
            <Ionicons name="log-out-outline" size={18} color={colors.danger} />
            <Text style={styles.signOutText}>CERRAR SESIÓN</Text>
          </TouchableOpacity>
        )}
      </ScrollView>

      <BottomNav active="Profile" navigation={navigation} />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: {
    flex: 1,
    backgroundColor: colors.background,
  },
  header: {
    height: 64,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 20,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  headerTitle: {
    fontSize: 24,
    fontWeight: '700',
    color: colors.textPrimary,
  },
  scroll: {
    flex: 1,
  },
  scrollContent: {
    paddingBottom: 32,
  },
  avatarBlock: {
    alignItems: 'center',
    paddingVertical: 28,
    paddingHorizontal: 20,
  },
  avatar: {
    width: 72,
    height: 72,
    borderRadius: 36,
    backgroundColor: colors.primary,
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 12,
  },
  avatarText: {
    color: '#ffffff',
    fontSize: 28,
    fontWeight: '700',
  },
  displayName: {
    fontSize: 20,
    fontWeight: '700',
    color: colors.textPrimary,
  },
  email: {
    fontSize: 14,
    color: colors.textSecondary,
    marginTop: 4,
  },
  countryBadge: {
    marginTop: 10,
    backgroundColor: colors.surface,
    borderRadius: 20,
    paddingHorizontal: 14,
    paddingVertical: 6,
    borderWidth: 1,
    borderColor: colors.border,
  },
  countryBadgeText: {
    fontSize: 13,
    fontWeight: '600',
    color: colors.textSecondary,
  },
  section: {
    paddingHorizontal: 20,
    paddingTop: 8,
    paddingBottom: 16,
    borderTopWidth: 1,
    borderTopColor: colors.divider,
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: colors.textPrimary,
    paddingVertical: 14,
  },
  configRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: 10,
  },
  configLabel: {
    fontSize: 15,
    color: colors.textSecondary,
  },
  configValue: {
    fontSize: 15,
    fontWeight: '600',
    color: colors.textPrimary,
  },
  divider: {
    height: 1,
    backgroundColor: colors.divider,
  },
  searchItem: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    paddingVertical: 8,
  },
  searchDot: {
    width: 8,
    height: 8,
    borderRadius: 4,
    backgroundColor: colors.primary,
  },
  searchText: {
    fontSize: 14,
    color: colors.textSecondary,
  },
  signOutButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    marginHorizontal: 20,
    marginTop: 8,
    borderWidth: 2,
    borderColor: colors.danger,
    borderRadius: 12,
    paddingVertical: 14,
  },
  signOutText: {
    fontSize: 15,
    fontWeight: '700',
    color: colors.danger,
    letterSpacing: 0.5,
  },
});
