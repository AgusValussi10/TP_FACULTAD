import { useState, useRef, useCallback } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  Animated,
  StatusBar,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { useFocusEffect } from '@react-navigation/native';
import BottomNav from '../../components/BottomNav';
import NumericKeyboard from '../../components/NumericKeyboard';
import { useAuth } from '../../context/AuthContext';
import { getHistorial } from '../../services/api';

const colors = {
  primary: '#3b82f6',
  primaryDark: '#2563eb',
  success: '#10b981',
  brasil: '#009c3b',
  brasilSoft: '#f0fdf4',
  background: '#ffffff',
  surface: '#f8f9fa',
  border: '#dee2e6',
  textPrimary: '#1a1a1a',
  textSecondary: '#6c757d',
  textMuted: '#adb5bd',
  divider: '#e0e0e0',
};

const DESTINATION = {
  flag: '🇧🇷',
  name: 'Brasil',
  method: 'PIX',
  currency: 'BRL',
  prefix: 'R$',
  subtitle: 'Transferencia instantánea',
};

function formatFecha(isoString) {
  const diffH = Math.floor((Date.now() - new Date(isoString)) / 3600000);
  const diffD = Math.floor(diffH / 24);
  if (diffH < 1) return 'Hace menos de 1 hora';
  if (diffH < 24) return `Hace ${diffH}hs`;
  if (diffD === 1) return 'Ayer';
  if (diffD < 7) return `Hace ${diffD} días`;
  return `Hace ${Math.floor(diffD / 7)} semana${Math.floor(diffD / 7) > 1 ? 's' : ''}`;
}

function formatAmount(val) {
  if (!val) return '';
  const [int, dec] = val.split('.');
  const intFormatted = parseInt(int || '0', 10).toLocaleString('es-AR');
  return dec !== undefined ? `${intFormatted},${dec}` : intFormatted;
}

export default function HomeScreen({ navigation }) {
  const { apiToken } = useAuth();
  const [amount, setAmount] = useState('');
  const [keyboardVisible, setKeyboardVisible] = useState(false);
  const [recentQueries, setRecentQueries] = useState([]);
  const buttonScale = useRef(new Animated.Value(1)).current;

  const loadRecent = useCallback(async () => {
    if (!apiToken) return;
    try {
      const { resultados } = await getHistorial(apiToken, 1, 3);
      setRecentQueries(resultados.map(r => ({
        id: String(r.id),
        text: `${r.monto} ${r.moneda_destino ?? 'BRL'} — ${formatFecha(r.consultado_en)}`,
        monto: r.monto,
        moneda_destino: r.moneda_destino ?? 'BRL',
      })));
    } catch {
      setRecentQueries([]);
    }
  }, [apiToken]);

  useFocusEffect(useCallback(() => { loadRecent(); }, [loadRecent]));

  const animateButton = (callback) => {
    Animated.sequence([
      Animated.timing(buttonScale, { toValue: 0.96, duration: 80, useNativeDriver: true }),
      Animated.timing(buttonScale, { toValue: 1, duration: 80, useNativeDriver: true }),
    ]).start(callback);
  };

  const handleCompare = () => {
    if (!amount || parseFloat(amount) <= 0) return;
    animateButton(() => {
      navigation.navigate('Results', {
        amount: parseFloat(amount),
        currency: DESTINATION.currency,
        country: DESTINATION.name,
      });
    });
  };

  return (
    <SafeAreaView style={styles.safe}>
      <StatusBar barStyle="dark-content" backgroundColor={colors.background} />

      <View style={styles.header}>
        <TouchableOpacity style={styles.headerIcon} onPress={() => navigation.navigate('Settings')}>
          <Ionicons name="menu-outline" size={26} color={colors.textPrimary} />
        </TouchableOpacity>
        <TouchableOpacity style={styles.headerIcon}>
          <Ionicons name="home" size={26} color={colors.primary} />
        </TouchableOpacity>
        <TouchableOpacity style={styles.headerIcon} onPress={() => navigation.navigate('Profile')}>
          <Ionicons name="person-circle-outline" size={26} color={colors.textPrimary} />
        </TouchableOpacity>
      </View>

      <ScrollView
        style={styles.scroll}
        contentContainerStyle={styles.scrollContent}
        keyboardShouldPersistTaps="handled"
      >
        <Text style={styles.title}>Pagá en Brasil{'\n'}al mejor cambio</Text>

        <View style={styles.destinationCard}>
          <Text style={styles.flag}>{DESTINATION.flag}</Text>
          <View style={styles.destinationInfo}>
            <View style={styles.destinationRow}>
              <Text style={styles.destinationName}>
                {DESTINATION.name} — {DESTINATION.method}
              </Text>
              <View style={styles.badge}>
                <Text style={styles.badgeText}>Activo</Text>
              </View>
            </View>
            <Text style={styles.destinationSubtitle}>{DESTINATION.subtitle}</Text>
          </View>
        </View>

        <Text style={styles.label}>¿Cuánto vas a pagar?</Text>
        <TouchableOpacity
          style={styles.amountContainer}
          onPress={() => setKeyboardVisible(true)}
          activeOpacity={0.7}
        >
          <Text style={styles.currencyPrefix}>{DESTINATION.prefix}</Text>
          {amount ? (
            <Text style={styles.amountText}>{formatAmount(amount)}</Text>
          ) : (
            <Text style={styles.amountPlaceholder}>500,00</Text>
          )}
        </TouchableOpacity>

        <Animated.View style={{ transform: [{ scale: buttonScale }] }}>
          <TouchableOpacity
            style={[styles.button, !amount && styles.buttonDisabled]}
            onPress={handleCompare}
            activeOpacity={1}
          >
            <Text style={styles.buttonText}>COMPARAR AHORA</Text>
          </TouchableOpacity>
        </Animated.View>

        <View style={styles.divider} />

        <Text style={styles.sectionTitle}>Últimas consultas</Text>
        {recentQueries.length === 0 ? (
          <Text style={styles.recentEmpty}>Todavía no hiciste consultas</Text>
        ) : (
          recentQueries.map((item) => (
            <TouchableOpacity
              key={item.id}
              style={styles.recentItem}
              activeOpacity={0.7}
              onPress={() => navigation.navigate('Results', {
                amount: item.monto,
                currency: item.moneda_destino,
                country: 'Brasil',
                skipSave: true,
              })}
            >
              <View style={styles.bullet} />
              <Text style={styles.recentText}>{item.text}</Text>
            </TouchableOpacity>
          ))
        )}
      </ScrollView>

      <BottomNav active="Home" navigation={navigation} />

      <NumericKeyboard
        visible={keyboardVisible}
        onClose={() => setKeyboardVisible(false)}
        onConfirm={(val) => setAmount(val)}
        initialValue={amount}
        prefix={DESTINATION.prefix}
      />
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
  headerIcon: { padding: 4 },
  scroll: { flex: 1 },
  scrollContent: { paddingHorizontal: 20, paddingTop: 28, paddingBottom: 24 },
  title: {
    fontSize: 32,
    fontWeight: '700',
    color: colors.textPrimary,
    marginBottom: 28,
    lineHeight: 40,
  },
  destinationCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: colors.brasilSoft,
    borderRadius: 16,
    borderWidth: 1.5,
    borderColor: colors.brasil,
    padding: 16,
    marginBottom: 24,
    gap: 12,
  },
  destinationInfo: { flex: 1 },
  destinationRow: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  flag: { fontSize: 26 },
  destinationName: { fontSize: 16, fontWeight: '600', color: colors.textPrimary },
  destinationSubtitle: { fontSize: 12, color: colors.textSecondary, marginTop: 2 },
  badge: { backgroundColor: colors.brasil, borderRadius: 6, paddingHorizontal: 7, paddingVertical: 2 },
  badgeText: { fontSize: 11, fontWeight: '700', color: '#ffffff' },
  label: { fontSize: 14, color: colors.textSecondary, marginBottom: 10, fontWeight: '500' },
  amountContainer: {
    backgroundColor: colors.surface,
    borderRadius: 16,
    borderWidth: 1.5,
    borderColor: colors.border,
    paddingHorizontal: 20,
    paddingVertical: 20,
    marginBottom: 20,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  currencyPrefix: { fontSize: 28, fontWeight: '700', color: colors.textSecondary },
  amountText: { flex: 1, fontSize: 32, fontWeight: '700', color: colors.textPrimary, letterSpacing: -1 },
  amountPlaceholder: { flex: 1, fontSize: 32, fontWeight: '700', color: colors.textMuted, letterSpacing: -1 },
  button: {
    backgroundColor: colors.primary,
    borderRadius: 12,
    paddingVertical: 18,
    alignItems: 'center',
    marginBottom: 24,
  },
  buttonDisabled: { backgroundColor: colors.textMuted },
  buttonText: { color: '#ffffff', fontSize: 16, fontWeight: '700', letterSpacing: 1 },
  divider: { height: 1, backgroundColor: colors.divider, marginBottom: 20 },
  sectionTitle: { fontSize: 16, fontWeight: '600', color: colors.textPrimary, marginBottom: 14 },
  recentItem: { flexDirection: 'row', alignItems: 'center', marginBottom: 12, gap: 10 },
  bullet: { width: 8, height: 8, borderRadius: 4, backgroundColor: colors.primary },
  recentText: { fontSize: 14, color: colors.textSecondary },
  recentEmpty: { fontSize: 14, color: colors.textMuted, fontStyle: 'italic' },
});
