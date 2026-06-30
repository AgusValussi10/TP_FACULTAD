import { useState, useEffect, useCallback } from 'react';
import { View, Text, StyleSheet, ScrollView, TouchableOpacity, StatusBar, Switch, ActivityIndicator } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { getWalletMeta } from '../../data/wallets';
import BottomNav from '../../components/BottomNav';
import { useAuth } from '../../context/AuthContext';
import { getAlertas, toggleAlerta } from '../../services/api';

const colors = {
  primary: '#3b82f6',
  success: '#10b981',
  background: '#ffffff',
  surface: '#f8f9fa',
  border: '#dee2e6',
  textPrimary: '#1a1a1a',
  textSecondary: '#6c757d',
  textMuted: '#adb5bd',
  divider: '#e0e0e0',
};

function buildConditionLabel(condicion, valorObjetivo) {
  const valor = `$ ${Number(valorObjetivo).toLocaleString('es-AR')} ARS`;
  return condicion === 'supera' ? `BRL supere ${valor}` : `BRL baje de ${valor}`;
}

export default function AlertsScreen({ navigation }) {
  const { apiToken } = useAuth();
  const [alerts, setAlerts] = useState([]);
  const [loading, setLoading] = useState(true);

  const load = useCallback(async () => {
    if (!apiToken) { setLoading(false); return; }
    try {
      const rows = await getAlertas(apiToken);
      const mapped = rows.map(r => ({
        id: r.id,
        wallet: r.billetera_nombre,
        colorHex: r.color_hex,
        condition: buildConditionLabel(r.condicion, r.valor_objetivo),
        active: Boolean(r.activa),
      }));
      setAlerts(mapped);
    } catch {
      setAlerts([]);
    } finally {
      setLoading(false);
    }
  }, [apiToken]);

  useEffect(() => { load(); }, [load]);

  const handleToggle = async (id) => {
    const current = alerts.find(a => a.id === id);
    if (!current) return;
    const newActive = !current.active;
    setAlerts(prev => prev.map(a => a.id === id ? { ...a, active: newActive } : a));
    try {
      await toggleAlerta(apiToken, id, newActive);
    } catch {
      setAlerts(prev => prev.map(a => a.id === id ? { ...a, active: current.active } : a));
    }
  };

  return (
    <SafeAreaView style={styles.safe}>
      <StatusBar barStyle="dark-content" />
      <View style={styles.header}>
        <Text style={styles.headerTitle}>Mis Alertas</Text>
        <TouchableOpacity
          style={styles.addButton}
          onPress={() => navigation.navigate('CreateAlert')}
          activeOpacity={0.7}
        >
          <Ionicons name="add" size={22} color="#ffffff" />
        </TouchableOpacity>
      </View>

      {loading ? (
        <View style={styles.centered}>
          <ActivityIndicator size="large" color={colors.primary} />
        </View>
      ) : (
        <ScrollView style={styles.scroll} contentContainerStyle={styles.scrollContent}>
          {alerts.length === 0 && (
            <View style={styles.empty}>
              <Ionicons name="notifications-off-outline" size={56} color={colors.textMuted} />
              <Text style={styles.emptyTitle}>Sin alertas</Text>
              <Text style={styles.emptySubtitle}>Creá una alerta para recibir notificaciones cuando cambie la cotización</Text>
            </View>
          )}
          {alerts.map((alert) => {
            const meta = getWalletMeta(alert.wallet);
            return (
              <View key={alert.id} style={styles.card}>
                <View style={styles.cardLeft}>
                  <View style={[styles.logo, { backgroundColor: alert.colorHex ?? meta.color }]}>
                    <Text style={styles.logoText}>{meta.initials}</Text>
                  </View>
                  <View style={styles.alertInfo}>
                    <Text style={styles.walletName}>{alert.wallet}</Text>
                    <Text style={styles.condition}>{alert.condition}</Text>
                    <View style={[styles.badge, alert.active ? styles.badgeActive : styles.badgePaused]}>
                      <Text style={[styles.badgeText, alert.active ? styles.badgeTextActive : styles.badgeTextPaused]}>
                        {alert.active ? 'Activa' : 'Pausada'}
                      </Text>
                    </View>
                  </View>
                </View>
                <Switch
                  value={alert.active}
                  onValueChange={() => handleToggle(alert.id)}
                  trackColor={{ false: colors.border, true: colors.success }}
                  thumbColor="#ffffff"
                />
              </View>
            );
          })}
        </ScrollView>
      )}

      <BottomNav active="Alerts" navigation={navigation} />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: colors.background },
  header: {
    height: 64,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 20,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  headerTitle: { fontSize: 24, fontWeight: '700', color: colors.textPrimary },
  addButton: {
    backgroundColor: colors.primary,
    borderRadius: 10,
    width: 36,
    height: 36,
    justifyContent: 'center',
    alignItems: 'center',
  },
  centered: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  scroll: { flex: 1 },
  scrollContent: { padding: 16, gap: 12 },
  empty: { alignItems: 'center', paddingTop: 60, gap: 12, paddingHorizontal: 32 },
  emptyTitle: { fontSize: 18, fontWeight: '700', color: colors.textPrimary },
  emptySubtitle: { fontSize: 14, color: colors.textSecondary, textAlign: 'center', lineHeight: 20 },
  card: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: colors.background,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: colors.border,
    padding: 16,
  },
  cardLeft: { flexDirection: 'row', alignItems: 'center', flex: 1, marginRight: 12 },
  logo: { width: 44, height: 44, borderRadius: 22, justifyContent: 'center', alignItems: 'center' },
  logoText: { color: '#ffffff', fontSize: 12, fontWeight: '700' },
  alertInfo: { flex: 1, marginLeft: 12, gap: 4 },
  walletName: { fontSize: 15, fontWeight: '700', color: colors.textPrimary },
  condition: { fontSize: 13, color: colors.textSecondary },
  badge: { alignSelf: 'flex-start', borderRadius: 6, paddingHorizontal: 8, paddingVertical: 2, marginTop: 4 },
  badgeActive: { backgroundColor: '#dcfce7' },
  badgePaused: { backgroundColor: colors.surface },
  badgeText: { fontSize: 11, fontWeight: '600' },
  badgeTextActive: { color: colors.success },
  badgeTextPaused: { color: colors.textMuted },
});
