import { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  TouchableOpacity,
  StatusBar,
  ActivityIndicator,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import BottomNav from '../../components/BottomNav';
import { useAuth } from '../../context/AuthContext';
import { getHistorial } from '../../services/api';

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

const CURRENCY_FLAG = { BRL: '🇧🇷', USD: '🇺🇸', EUR: '🇪🇺' };
const CURRENCY_METHOD = { BRL: 'PIX', USD: 'USD', EUR: 'EUR' };

function formatARS(amount) {
  return `$ ${Math.round(amount).toLocaleString('es-AR')} ARS`;
}

function formatFecha(isoString) {
  const date = new Date(isoString);
  const now = new Date();
  const diffMs = now - date;
  const diffH = Math.floor(diffMs / 3600000);
  const diffD = Math.floor(diffH / 24);
  if (diffH < 1) return 'Hace menos de 1 hora';
  if (diffH < 24) return `Hace ${diffH}hs`;
  if (diffD === 1) return 'Ayer';
  if (diffD < 7) return `Hace ${diffD} días`;
  if (diffD < 14) return 'Hace 1 semana';
  return `Hace ${Math.floor(diffD / 7)} semanas`;
}

function HistoryItem({ item, onRepeat }) {
  return (
    <TouchableOpacity style={styles.item} onPress={onRepeat} activeOpacity={0.7}>
      <View style={styles.itemFlag}>
        <Text style={styles.flagText}>{CURRENCY_FLAG[item.currency] ?? '🌍'}</Text>
      </View>
      <View style={styles.itemInfo}>
        <View style={styles.itemRow}>
          <Text style={styles.itemAmount}>
            {item.amount} {item.currency}
          </Text>
          <Text style={styles.itemMethod}>{CURRENCY_METHOD[item.currency] ?? item.currency}</Text>
        </View>
        <Text style={styles.itemBest}>
          Mejor: {item.bestProvider} · {formatARS(item.bestPrice)}
        </Text>
        <Text style={styles.itemDate}>{item.date}</Text>
      </View>
      <Ionicons name="chevron-forward" size={18} color={colors.textMuted} />
    </TouchableOpacity>
  );
}

function EmptyState() {
  return (
    <View style={styles.empty}>
      <Ionicons name="time-outline" size={56} color={colors.textMuted} />
      <Text style={styles.emptyTitle}>Sin consultas aún</Text>
      <Text style={styles.emptySubtitle}>
        Tus comparaciones anteriores van a aparecer acá
      </Text>
    </View>
  );
}

export default function HistoryScreen({ navigation }) {
  const { apiToken } = useAuth();
  const [history, setHistory] = useState([]);
  const [loading, setLoading] = useState(true);

  const load = useCallback(async () => {
    if (!apiToken) { setLoading(false); return; }
    try {
      const rows = await getHistorial(apiToken);
      const mapped = rows.map(r => ({
        id: String(r.id),
        amount: r.monto,
        currency: r.moneda_destino,
        country: r.moneda_destino === 'BRL' ? 'Brasil' : r.moneda_destino,
        bestProvider: r.mejor_billetera ?? '—',
        bestPrice: r.total_ars ?? 0,
        date: formatFecha(r.consultado_en),
      }));
      setHistory(mapped);
    } catch {
      setHistory([]);
    } finally {
      setLoading(false);
    }
  }, [apiToken]);

  useEffect(() => { load(); }, [load]);

  const handleRepeat = (item) => {
    navigation.navigate('Results', {
      amount: item.amount,
      currency: item.currency,
      country: item.country,
    });
  };

  return (
    <SafeAreaView style={styles.safe}>
      <StatusBar barStyle="dark-content" backgroundColor={colors.background} />

      <View style={styles.header}>
        <View style={styles.headerIcon} />
        <Text style={styles.headerTitle}>Historial</Text>
        <View style={styles.headerIcon} />
      </View>

      {loading ? (
        <View style={styles.centered}>
          <ActivityIndicator size="large" color={colors.primary} />
        </View>
      ) : (
        <FlatList
          data={history}
          keyExtractor={(item) => item.id}
          renderItem={({ item }) => (
            <HistoryItem item={item} onRepeat={() => handleRepeat(item)} />
          )}
          ListEmptyComponent={<EmptyState />}
          ItemSeparatorComponent={() => <View style={styles.separator} />}
          contentContainerStyle={
            history.length === 0 ? styles.emptyContainer : styles.listContent
          }
          showsVerticalScrollIndicator={false}
        />
      )}

      <BottomNav active="History" navigation={navigation} />
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
    borderBottomColor: colors.divider,
  },
  headerIcon: { padding: 4, width: 36 },
  headerTitle: { fontSize: 18, fontWeight: '700', color: colors.textPrimary },
  centered: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  listContent: { paddingVertical: 8 },
  item: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 20,
    paddingVertical: 14,
    gap: 14,
  },
  itemFlag: {
    width: 44,
    height: 44,
    borderRadius: 12,
    backgroundColor: colors.surface,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: colors.border,
  },
  flagText: { fontSize: 22 },
  itemInfo: { flex: 1, gap: 3 },
  itemRow: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  itemAmount: { fontSize: 16, fontWeight: '700', color: colors.textPrimary },
  itemMethod: {
    fontSize: 12,
    fontWeight: '600',
    color: colors.primary,
    backgroundColor: '#eff6ff',
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 4,
  },
  itemBest: { fontSize: 13, color: colors.success, fontWeight: '500' },
  itemDate: { fontSize: 12, color: colors.textMuted },
  separator: { height: 1, backgroundColor: colors.divider, marginLeft: 78 },
  emptyContainer: { flex: 1 },
  empty: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 40,
    gap: 12,
  },
  emptyTitle: { fontSize: 18, fontWeight: '700', color: colors.textPrimary },
  emptySubtitle: { fontSize: 14, color: colors.textSecondary, textAlign: 'center', lineHeight: 20 },
});
