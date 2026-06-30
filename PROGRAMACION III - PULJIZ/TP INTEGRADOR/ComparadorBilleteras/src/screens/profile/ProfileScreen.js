import { useState, useCallback } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  StatusBar,
  ActivityIndicator,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { useFocusEffect } from '@react-navigation/native';
import BottomNav from '../../components/BottomNav';
import { useAuth } from '../../context/AuthContext';
import { getHistorial } from '../../services/api';

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

function formatFecha(isoString) {
  const date = new Date(isoString);
  const now = new Date();
  const diffH = Math.floor((now - date) / 3600000);
  const diffD = Math.floor(diffH / 24);
  if (diffH < 1) return 'Hace menos de 1 hora';
  if (diffH < 24) return `Hace ${diffH}hs`;
  if (diffD === 1) return 'Ayer';
  if (diffD < 7) return `Hace ${diffD} días`;
  return `Hace ${Math.floor(diffD / 7)} semana${Math.floor(diffD / 7) > 1 ? 's' : ''}`;
}

function MenuRow({ icon, label, value, onPress, danger }) {
  return (
    <TouchableOpacity style={styles.menuRow} onPress={onPress} activeOpacity={0.7}>
      <Ionicons
        name={icon}
        size={20}
        color={danger ? '#ef4444' : colors.textSecondary}
        style={styles.menuIcon}
      />
      <Text style={[styles.menuLabel, danger && styles.menuLabelDanger]}>{label}</Text>
      {value ? <Text style={styles.menuValue}>{value}</Text> : null}
      {!danger && <Ionicons name="chevron-forward" size={16} color={colors.textMuted} />}
    </TouchableOpacity>
  );
}

export default function ProfileScreen({ navigation }) {
  const { user, apiToken, signOut } = useAuth();
  const [recentHistory, setRecentHistory] = useState([]);
  const [loadingHistory, setLoadingHistory] = useState(true);

  const displayName = user?.displayName || user?.email?.split('@')[0] || 'Usuario';
  const email = user?.email || '';
  const initial = displayName.charAt(0).toUpperCase();

  const loadHistory = useCallback(async () => {
    if (!apiToken) { setLoadingHistory(false); return; }
    try {
      const rows = await getHistorial(apiToken);
      setRecentHistory(rows.slice(0, 3).map(r => ({
        id: String(r.id),
        amount: r.monto,
        currency: r.moneda_destino ?? 'BRL',
        bestProvider: r.mejor_billetera ?? '—',
        date: formatFecha(r.consultado_en),
      })));
    } catch {
      setRecentHistory([]);
    } finally {
      setLoadingHistory(false);
    }
  }, [apiToken]);

  useFocusEffect(useCallback(() => { loadHistory(); }, [loadHistory]));

  const handleSignOut = async () => {
    await signOut();
    navigation.replace('Login');
  };

  return (
    <SafeAreaView style={styles.safe}>
      <StatusBar barStyle="dark-content" backgroundColor={colors.background} />

      <View style={styles.header}>
        <Text style={styles.headerTitle}>Mi Perfil</Text>
        <TouchableOpacity style={styles.editBtn} onPress={() => navigation.navigate('EditProfile')}>
          <Ionicons name="pencil-outline" size={20} color={colors.primary} />
        </TouchableOpacity>
      </View>

      <ScrollView
        style={styles.scroll}
        showsVerticalScrollIndicator={false}
        contentContainerStyle={styles.scrollContent}
      >
        {/* Avatar block */}
        <View style={styles.avatarBlock}>
          <View style={styles.avatar}>
            <Text style={styles.avatarInitial}>{initial}</Text>
          </View>
          <Text style={styles.userName}>{displayName}</Text>
          <Text style={styles.userEmail}>{email}</Text>
          <View style={styles.countryBadge}>
            <Text style={styles.countryBadgeText}>🇦🇷 Argentina — ARS</Text>
          </View>
        </View>

        {/* Quick settings */}
        <Text style={styles.sectionLabel}>Configuración rápida</Text>
        <View style={styles.card}>
          <MenuRow icon="cash-outline" label="Moneda base" value="ARS" />
          <View style={styles.cardDivider} />
          <MenuRow icon="location-outline" label="País" value="Argentina" />
        </View>

        {/* Navigation to Settings and Favorites */}
        <Text style={styles.sectionLabel}>Mi cuenta</Text>
        <View style={styles.card}>
          <MenuRow
            icon="create-outline"
            label="Editar perfil"
            onPress={() => navigation.navigate('EditProfile')}
          />
          <View style={styles.cardDivider} />
          <MenuRow
            icon="settings-outline"
            label="Configuración"
            onPress={() => navigation.navigate('Settings')}
          />
          <View style={styles.cardDivider} />
          <MenuRow
            icon="star-outline"
            label="Favoritos"
            onPress={() => navigation.navigate('Favorites')}
          />
          <View style={styles.cardDivider} />
          <MenuRow
            icon="time-outline"
            label="Ver todo el historial"
            onPress={() => navigation.navigate('History')}
          />
        </View>

        {/* Recent searches */}
        <Text style={styles.sectionLabel}>Búsquedas recientes</Text>
        <View style={styles.card}>
          {loadingHistory ? (
            <View style={styles.loadingRow}>
              <ActivityIndicator size="small" color={colors.primary} />
            </View>
          ) : recentHistory.length === 0 ? (
            <View style={styles.emptyRow}>
              <Text style={styles.emptyRowText}>Sin búsquedas aún</Text>
            </View>
          ) : (
            recentHistory.map((item, index) => (
              <View key={item.id}>
                {index > 0 && <View style={styles.cardDivider} />}
                <View style={styles.searchRow}>
                  <View style={styles.searchDot} />
                  <View style={styles.searchInfo}>
                    <Text style={styles.searchAmount}>
                      {item.amount} {item.currency}
                    </Text>
                    <Text style={styles.searchProvider}>{item.bestProvider}</Text>
                  </View>
                  <Text style={styles.searchDate}>{item.date}</Text>
                </View>
              </View>
            ))
          )}
        </View>

        {/* Logout */}
        <TouchableOpacity style={styles.logoutBtn} onPress={handleSignOut} activeOpacity={0.8}>
          <Text style={styles.logoutText}>CERRAR SESIÓN</Text>
        </TouchableOpacity>
      </ScrollView>

      <BottomNav active="Profile" navigation={navigation} />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: colors.surface },
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
  headerTitle: { fontSize: 18, fontWeight: '700', color: colors.textPrimary },
  editBtn: { padding: 4 },
  scroll: { flex: 1 },
  scrollContent: { paddingHorizontal: 20, paddingBottom: 24 },
  avatarBlock: { alignItems: 'center', paddingVertical: 28, gap: 6 },
  avatar: {
    width: 72,
    height: 72,
    borderRadius: 36,
    backgroundColor: colors.primary,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 4,
  },
  avatarInitial: { fontSize: 32, fontWeight: '700', color: '#ffffff' },
  userName: { fontSize: 20, fontWeight: '700', color: colors.textPrimary },
  userEmail: { fontSize: 14, color: colors.textSecondary },
  countryBadge: {
    backgroundColor: '#eff6ff',
    borderRadius: 20,
    paddingHorizontal: 14,
    paddingVertical: 6,
    marginTop: 4,
  },
  countryBadgeText: { fontSize: 13, fontWeight: '600', color: colors.primary },
  sectionLabel: {
    fontSize: 13,
    fontWeight: '600',
    color: colors.textSecondary,
    textTransform: 'uppercase',
    letterSpacing: 0.5,
    marginBottom: 8,
    marginTop: 16,
  },
  card: {
    backgroundColor: colors.background,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: colors.border,
    overflow: 'hidden',
  },
  cardDivider: { height: 1, backgroundColor: colors.divider, marginLeft: 52 },
  menuRow: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingVertical: 14,
    gap: 12,
  },
  menuIcon: { width: 24 },
  menuLabel: { flex: 1, fontSize: 16, color: colors.textPrimary },
  menuLabelDanger: { color: '#ef4444' },
  menuValue: { fontSize: 16, color: colors.textSecondary, marginRight: 4 },
  loadingRow: { paddingVertical: 20, alignItems: 'center' },
  emptyRow: { paddingVertical: 16, paddingHorizontal: 16 },
  emptyRowText: { fontSize: 14, color: colors.textMuted },
  searchRow: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingVertical: 12,
    gap: 12,
  },
  searchDot: { width: 10, height: 10, borderRadius: 5, backgroundColor: colors.primary },
  searchInfo: { flex: 1, gap: 2 },
  searchAmount: { fontSize: 15, fontWeight: '600', color: colors.textPrimary },
  searchProvider: { fontSize: 13, color: colors.textSecondary },
  searchDate: { fontSize: 12, color: colors.textMuted },
  logoutBtn: {
    marginTop: 24,
    borderRadius: 12,
    borderWidth: 1.5,
    borderColor: '#ef4444',
    paddingVertical: 14,
    alignItems: 'center',
  },
  logoutText: { fontSize: 14, fontWeight: '700', color: '#ef4444', letterSpacing: 0.5 },
});
