import { useEffect, useRef, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  StatusBar,
  Share,
  Alert,
  Animated,
  ActivityIndicator,
  Linking,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { formatARS, getWalletMeta, getCanonicalName } from '../../data/wallets';
import { getCotizaciones, saveHistorial } from '../../services/api';
import { useAuth } from '../../context/AuthContext';
import ExternalRedirectModal from '../../components/modals/ExternalRedirectModal';

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

function ProviderLogo({ name, colorHex }) {
  const meta = getWalletMeta(name);
  return (
    <View style={[styles.providerLogo, { backgroundColor: colorHex ?? meta.color }]}>
      <Text style={styles.providerLogoText}>{meta.initials}</Text>
    </View>
  );
}

function ProviderCard({ item, onSeeMore, onOpenApp }) {
  if (item.isBest) {
    return (
      <View style={[styles.card, styles.bestCard]}>
        <View style={styles.bestBadge}>
          <Text style={styles.bestBadgeText}>💚 Mejor opción</Text>
        </View>
        <View style={styles.cardHeader}>
          <ProviderLogo name={item.name} colorHex={item.colorHex} />
          <Text style={styles.providerName}>{item.name}</Text>
        </View>
        <Text style={styles.priceText}>{formatARS(item.price)}</Text>
        {item.savings !== null && (
          <Text style={styles.savingsText}>
            ✓ Ahorrás {formatARS(item.savings)} ({item.savingsPct.toFixed(1)}%)
          </Text>
        )}
        <View style={styles.bestActions}>
          <TouchableOpacity style={styles.detailsBtn} onPress={onSeeMore} activeOpacity={0.7}>
            <Text style={styles.detailsBtnText}>Ver detalles</Text>
          </TouchableOpacity>
          <TouchableOpacity style={styles.openAppBtn} onPress={onOpenApp} activeOpacity={0.8}>
            <Ionicons name="open-outline" size={15} color="#ffffff" />
            <Text style={styles.openAppBtnText}>Ir a {item.name}</Text>
          </TouchableOpacity>
        </View>
      </View>
    );
  }

  return (
    <View style={styles.card}>
      <View style={styles.cardHeader}>
        <ProviderLogo name={item.name} colorHex={item.colorHex} />
        <Text style={styles.providerName}>{item.name}</Text>
      </View>
      <Text style={styles.priceTextNormal}>{formatARS(item.price)}</Text>
      {item.savings !== null && (
        <Text style={styles.savingsTextNormal}>
          Ahorrás {formatARS(item.savings)} ({item.savingsPct.toFixed(1)}%)
        </Text>
      )}
      <TouchableOpacity onPress={onSeeMore}>
        <Text style={styles.seeMoreLink}>Ver más →</Text>
      </TouchableOpacity>
    </View>
  );
}

export default function ResultsScreen({ route, navigation }) {
  const { amount, currency, country, skipSave } = route.params;
  const { apiToken } = useAuth();

  const [results, setResults] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [bestResult, setBestResult] = useState(null);
  const [modalWallet, setModalWallet] = useState(null);

  const cardAnims = useRef([]);
  const savedRef = useRef(!!skipSave);

  useEffect(() => {
    let cancelled = false;
    async function fetchData() {
      try {
        const data = await getCotizaciones(amount);
        if (cancelled) return;

        const rows = data.resultados ?? [];
        const peorTotal = rows.length > 0 ? rows[rows.length - 1].total_ars : 1;

        const mapped = rows.map((r) => ({
          id: String(r.billetera_id),
          name: getCanonicalName(r.nombre),
          colorHex: r.color_hex,
          rate: r.tasa,
          price: r.total_ars,
          savings: r.ahorro_ars,
          savingsPct: r.ahorro_ars !== null ? (r.ahorro_ars / peorTotal) * 100 : null,
          isBest: r.es_mejor,
        }));

        setResults(mapped);
        cardAnims.current = mapped.map(() => ({
          opacity: new Animated.Value(0),
          translateY: new Animated.Value(24),
        }));

        if (mapped.length > 0) setBestResult(mapped[0]);
      } catch (err) {
        if (!cancelled) setError(err.message);
      } finally {
        if (!cancelled) setLoading(false);
      }
    }
    fetchData();
    return () => { cancelled = true; };
  }, []);

  useEffect(() => {
    if (!apiToken || !bestResult || savedRef.current) return;
    savedRef.current = true;
    saveHistorial(apiToken, {
      monto: amount,
      moneda_destino: currency,
      mejor_billetera_id: parseInt(bestResult.id),
      mejor_tasa: bestResult.rate,
      total_ars: bestResult.price,
    }).catch(() => {});
  }, [apiToken, bestResult]);

  useEffect(() => {
    if (results.length === 0 || cardAnims.current.length === 0) return;
    Animated.stagger(
      80,
      cardAnims.current.map(({ opacity, translateY }) =>
        Animated.parallel([
          Animated.timing(opacity, { toValue: 1, duration: 300, useNativeDriver: true }),
          Animated.timing(translateY, { toValue: 0, duration: 300, useNativeDriver: true }),
        ])
      )
    ).start();
  }, [results]);

  const handleShare = async () => {
    try {
      const best = results[0];
      await Share.share({
        message:
          `BrasilPagos 💳\n` +
          `Pagando ${amount} ${currency} (${country})\n\n` +
          results.map((r, i) => `${i + 1}. ${r.name}: ${formatARS(r.price)}`).join('\n') +
          `\n\nMejor opción: ${best?.name}`,
      });
    } catch {
      Alert.alert('Error', 'No se pudo compartir.');
    }
  };

  const handleSeeMore = (wallet) => {
    navigation.navigate('WalletDetail', { walletName: wallet.name, amount, currency });
  };

  const handleOpenApp = (wallet) => setModalWallet(wallet);

  const handleConfirmApp = async () => {
    const meta = modalWallet ? getWalletMeta(modalWallet.name) : null;
    setModalWallet(null);
    if (!meta) return;
    if (meta.appUrl) {
      try { await Linking.openURL(meta.appUrl); } catch { /* silent */ }
    }
  };

  const handleCompare = () => {
    navigation.navigate('WalletCompare', {
      amount,
      currency,
      initialWallet1: results[0],
      initialWallet2: results[1],
    });
  };

  if (loading) {
    return (
      <SafeAreaView style={styles.safe}>
        <StatusBar barStyle="dark-content" backgroundColor={colors.background} />
        <View style={styles.header}>
          <TouchableOpacity style={styles.headerIcon} onPress={() => navigation.goBack()}>
            <Ionicons name="arrow-back" size={24} color={colors.textPrimary} />
          </TouchableOpacity>
          <Text style={styles.headerTitle}>Resultados</Text>
          <View style={styles.headerIcon} />
        </View>
        <View style={styles.centered}>
          <ActivityIndicator size="large" color={colors.primary} />
          <Text style={styles.loadingText}>Buscando las mejores cotizaciones...</Text>
        </View>
      </SafeAreaView>
    );
  }

  if (error) {
    return (
      <SafeAreaView style={styles.safe}>
        <StatusBar barStyle="dark-content" backgroundColor={colors.background} />
        <View style={styles.header}>
          <TouchableOpacity style={styles.headerIcon} onPress={() => navigation.goBack()}>
            <Ionicons name="arrow-back" size={24} color={colors.textPrimary} />
          </TouchableOpacity>
          <Text style={styles.headerTitle}>Resultados</Text>
          <View style={styles.headerIcon} />
        </View>
        <View style={styles.centered}>
          <Ionicons name="wifi-outline" size={56} color={colors.textMuted} />
          <Text style={styles.errorText}>No se pudo conectar al servidor</Text>
          <Text style={styles.errorSub}>Verificá que el servidor esté corriendo</Text>
        </View>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={styles.safe}>
      <StatusBar barStyle="dark-content" backgroundColor={colors.background} />

      <View style={styles.header}>
        <TouchableOpacity style={styles.headerIcon} onPress={() => navigation.goBack()}>
          <Ionicons name="arrow-back" size={24} color={colors.textPrimary} />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Resultados</Text>
        <TouchableOpacity style={styles.headerIcon} onPress={handleCompare}>
          <Ionicons name="git-compare-outline" size={22} color={colors.primary} />
        </TouchableOpacity>
      </View>

      <ScrollView
        style={styles.scroll}
        contentContainerStyle={styles.scrollContent}
        showsVerticalScrollIndicator={false}
      >
        <View style={styles.contextRow}>
          <Text style={styles.contextLabel}>
            Pagando {amount} {currency}
          </Text>
          <TouchableOpacity style={styles.compareChip} onPress={handleCompare}>
            <Ionicons name="git-compare-outline" size={14} color={colors.primary} />
            <Text style={styles.compareChipText}>Comparar 2</Text>
          </TouchableOpacity>
        </View>

        {results.map((item, index) => (
          <Animated.View
            key={item.id}
            style={
              cardAnims.current[index]
                ? {
                    opacity: cardAnims.current[index].opacity,
                    transform: [{ translateY: cardAnims.current[index].translateY }],
                  }
                : {}
            }
          >
            <ProviderCard
              item={item}
              onSeeMore={() => handleSeeMore(item)}
              onOpenApp={() => handleOpenApp(item)}
            />
          </Animated.View>
        ))}

        <View style={styles.actions}>
          <TouchableOpacity style={styles.buttonSecondary} onPress={handleShare}>
            <Ionicons name="share-outline" size={18} color={colors.textSecondary} />
            <Text style={styles.buttonSecondaryText}>COMPARTIR</Text>
          </TouchableOpacity>
          <TouchableOpacity style={styles.buttonPrimary} onPress={() => navigation.goBack()}>
            <Text style={styles.buttonPrimaryText}>NUEVA</Text>
          </TouchableOpacity>
        </View>
      </ScrollView>

      <ExternalRedirectModal
        visible={!!modalWallet}
        walletName={modalWallet?.name ?? ''}
        onCancel={() => setModalWallet(null)}
        onConfirm={handleConfirmApp}
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
  headerIcon: { padding: 4, width: 36 },
  headerTitle: { fontSize: 18, fontWeight: '700', color: colors.textPrimary },
  centered: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: 16, paddingHorizontal: 40 },
  loadingText: { fontSize: 15, color: colors.textSecondary, textAlign: 'center' },
  errorText: { fontSize: 17, fontWeight: '700', color: colors.textPrimary, textAlign: 'center' },
  errorSub: { fontSize: 14, color: colors.textSecondary, textAlign: 'center' },
  scroll: { flex: 1 },
  scrollContent: { paddingHorizontal: 20, paddingTop: 20, paddingBottom: 32 },
  contextRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 16,
  },
  contextLabel: { fontSize: 14, color: colors.textSecondary },
  compareChip: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    backgroundColor: '#eff6ff',
    borderRadius: 8,
    paddingHorizontal: 10,
    paddingVertical: 5,
  },
  compareChipText: { fontSize: 12, fontWeight: '700', color: colors.primary },
  providerLogo: {
    width: 40,
    height: 40,
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
  },
  providerLogoText: { color: '#ffffff', fontSize: 13, fontWeight: '800', letterSpacing: 0.5 },
  cardHeader: { flexDirection: 'row', alignItems: 'center', gap: 12, marginBottom: 10 },
  card: {
    backgroundColor: colors.background,
    borderRadius: 16,
    borderWidth: 1.5,
    borderColor: colors.border,
    padding: 20,
    marginBottom: 16,
  },
  bestCard: { borderColor: colors.success, backgroundColor: '#f0fdf4' },
  bestBadge: {
    alignSelf: 'flex-start',
    backgroundColor: '#dcfce7',
    borderRadius: 6,
    paddingHorizontal: 10,
    paddingVertical: 4,
    marginBottom: 12,
  },
  bestBadgeText: { fontSize: 12, fontWeight: '600', color: colors.success },
  providerName: { fontSize: 20, fontWeight: '700', color: colors.textPrimary, flex: 1 },
  priceText: { fontSize: 32, fontWeight: '700', color: colors.textPrimary, letterSpacing: -1, marginBottom: 8 },
  priceTextNormal: { fontSize: 24, fontWeight: '700', color: colors.textPrimary, letterSpacing: -0.5, marginBottom: 6 },
  savingsText: { fontSize: 14, color: colors.success, fontWeight: '600', marginBottom: 12 },
  savingsTextNormal: { fontSize: 13, color: colors.textSecondary, marginBottom: 10 },
  seeMoreLink: { fontSize: 14, color: colors.primary, fontWeight: '600' },
  bestActions: { flexDirection: 'row', gap: 10, marginTop: 4 },
  detailsBtn: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 10,
    borderWidth: 1.5,
    borderColor: colors.success,
    paddingVertical: 11,
  },
  detailsBtnText: { fontSize: 13, fontWeight: '600', color: colors.success },
  openAppBtn: {
    flex: 2,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 6,
    borderRadius: 10,
    backgroundColor: colors.success,
    paddingVertical: 11,
  },
  openAppBtnText: { fontSize: 13, fontWeight: '700', color: '#ffffff' },
  actions: { flexDirection: 'row', gap: 12, marginTop: 8 },
  buttonSecondary: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 6,
    borderRadius: 12,
    borderWidth: 1.5,
    borderColor: colors.border,
    paddingVertical: 16,
    backgroundColor: colors.background,
  },
  buttonSecondaryText: { fontSize: 14, fontWeight: '700', color: colors.textSecondary, letterSpacing: 0.5 },
  buttonPrimary: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 12,
    paddingVertical: 16,
    backgroundColor: colors.primary,
  },
  buttonPrimaryText: { fontSize: 14, fontWeight: '700', color: '#ffffff', letterSpacing: 0.5 },
});
