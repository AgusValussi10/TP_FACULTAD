import { useEffect, useRef } from 'react';
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
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';

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

const PROVIDER_META = {
  'Mercado Pago': { color: '#00b1ea', initials: 'MP' },
  'Ualá':         { color: '#7c3aed', initials: 'UA' },
  'Bimo':         { color: '#f59e0b', initials: 'BI' },
  'Prex':         { color: '#06b6d4', initials: 'PX' },
  'Naranja X':    { color: '#f97316', initials: 'NX' },
  'Brubank':      { color: '#3b82f6', initials: 'BB' },
  'Personal Pay': { color: '#8b5cf6', initials: 'PP' },
  'Lemon Cash':   { color: '#84cc16', initials: 'LC' },
  'Modo':         { color: '#ec4899', initials: 'MO' },
  'Cuenta DNI':   { color: '#0ea5e9', initials: 'CD' },
};

const PROVIDERS = {
  BRL: [
    { id: '1',  name: 'Mercado Pago',  rate: 970.46  },
    { id: '2',  name: 'Ualá',          rate: 984.36  },
    { id: '3',  name: 'Bimo',          rate: 988.90  },
    { id: '4',  name: 'Prex',          rate: 997.40  },
    { id: '5',  name: 'Naranja X',     rate: 993.20  },
    { id: '6',  name: 'Brubank',       rate: 1000.46 },
    { id: '7',  name: 'Personal Pay',  rate: 1005.80 },
    { id: '8',  name: 'Lemon Cash',    rate: 1010.50 },
    { id: '9',  name: 'Modo',          rate: 1015.30 },
    { id: '10', name: 'Cuenta DNI',    rate: 1020.75 },
  ],
  USD: [
    { id: '1',  name: 'Mercado Pago',  rate: 1050.00 },
    { id: '2',  name: 'Bimo',          rate: 1055.00 },
    { id: '3',  name: 'Lemon Cash',    rate: 1058.30 },
    { id: '4',  name: 'Ualá',          rate: 1060.50 },
    { id: '5',  name: 'Naranja X',     rate: 1065.20 },
    { id: '6',  name: 'Personal Pay',  rate: 1070.00 },
    { id: '7',  name: 'Brubank',       rate: 1075.00 },
    { id: '8',  name: 'Prex',          rate: 1077.50 },
    { id: '9',  name: 'Modo',          rate: 1080.00 },
    { id: '10', name: 'Cuenta DNI',    rate: 1085.40 },
  ],
  EUR: [
    { id: '1',  name: 'Mercado Pago',  rate: 1150.00 },
    { id: '2',  name: 'Bimo',          rate: 1155.00 },
    { id: '3',  name: 'Lemon Cash',    rate: 1158.50 },
    { id: '4',  name: 'Ualá',          rate: 1160.00 },
    { id: '5',  name: 'Naranja X',     rate: 1165.00 },
    { id: '6',  name: 'Personal Pay',  rate: 1170.00 },
    { id: '7',  name: 'Brubank',       rate: 1175.00 },
    { id: '8',  name: 'Prex',          rate: 1177.50 },
    { id: '9',  name: 'Modo',          rate: 1180.00 },
    { id: '10', name: 'Cuenta DNI',    rate: 1185.40 },
  ],
};

function formatARS(amount) {
  return `$ ${Math.round(amount).toLocaleString('es-AR')} ARS`;
}

function buildResults(amount, currency) {
  const providers = PROVIDERS[currency] ?? PROVIDERS['BRL'];
  const sorted = [...providers].sort((a, b) => a.rate - b.rate);
  const worstPrice = sorted[sorted.length - 1].rate * amount;

  return sorted.map((p, index) => {
    const price = p.rate * amount;
    const savings = worstPrice - price;
    const savingsPct = (savings / worstPrice) * 100;
    return {
      ...p,
      price,
      savings: index < sorted.length - 1 ? savings : null,
      savingsPct: index < sorted.length - 1 ? savingsPct : null,
      isBest: index === 0,
    };
  });
}

function ProviderLogo({ name }) {
  const meta = PROVIDER_META[name] ?? { color: '#6c757d', initials: name.slice(0, 2).toUpperCase() };
  return (
    <View style={[styles.providerLogo, { backgroundColor: meta.color }]}>
      <Text style={styles.providerLogoText}>{meta.initials}</Text>
    </View>
  );
}

function ProviderCard({ item, onSeeMore }) {
  if (item.isBest) {
    return (
      <View style={[styles.card, styles.bestCard]}>
        <View style={styles.bestBadge}>
          <Text style={styles.bestBadgeText}>💚 Mejor opción</Text>
        </View>
        <View style={styles.cardHeader}>
          <ProviderLogo name={item.name} />
          <Text style={styles.providerName}>{item.name}</Text>
        </View>
        <Text style={styles.priceText}>{formatARS(item.price)}</Text>
        {item.savings !== null && (
          <Text style={styles.savingsText}>
            ✓ Ahorrás {formatARS(item.savings)} ({item.savingsPct.toFixed(1)}%)
          </Text>
        )}
        <TouchableOpacity onPress={onSeeMore}>
          <Text style={styles.seeMoreLink}>Ver más →</Text>
        </TouchableOpacity>
      </View>
    );
  }

  return (
    <View style={styles.card}>
      <View style={styles.cardHeader}>
        <ProviderLogo name={item.name} />
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
  const { amount, currency, country } = route.params;
  const results = buildResults(amount, currency);
  const best = results[0];

  const cardAnims = useRef(results.map(() => ({
    opacity: new Animated.Value(0),
    translateY: new Animated.Value(24),
  }))).current;

  useEffect(() => {
    Animated.stagger(
      80,
      cardAnims.map(({ opacity, translateY }) =>
        Animated.parallel([
          Animated.timing(opacity, { toValue: 1, duration: 300, useNativeDriver: true }),
          Animated.timing(translateY, { toValue: 0, duration: 300, useNativeDriver: true }),
        ])
      )
    ).start();
  }, []);

  const handleShare = async () => {
    try {
      await Share.share({
        message:
          `Comparador de Billeteras 💳\n` +
          `Pagando ${amount} ${currency} (${country})\n\n` +
          results
            .map((r, i) => `${i + 1}. ${r.name}: ${formatARS(r.price)}`)
            .join('\n') +
          `\n\nMejor opción: ${best.name}`,
      });
    } catch {
      Alert.alert('Error', 'No se pudo compartir.');
    }
  };

  return (
    <SafeAreaView style={styles.safe}>
      <StatusBar barStyle="dark-content" backgroundColor={colors.background} />

      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity style={styles.headerIcon} onPress={() => navigation.goBack()}>
          <Ionicons name="arrow-back" size={24} color={colors.textPrimary} />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Resultados</Text>
        <TouchableOpacity style={styles.headerIcon}>
          <Ionicons name="ellipsis-vertical" size={22} color={colors.textPrimary} />
        </TouchableOpacity>
      </View>

      <ScrollView
        style={styles.scroll}
        contentContainerStyle={styles.scrollContent}
        showsVerticalScrollIndicator={false}
      >
        <Text style={styles.contextLabel}>
          Pagando {amount} {currency}
        </Text>

        {results.map((item, index) => (
          <Animated.View
            key={item.id}
            style={{
              opacity: cardAnims[index].opacity,
              transform: [{ translateY: cardAnims[index].translateY }],
            }}
          >
            <ProviderCard
              item={item}
              onSeeMore={() => Alert.alert(item.name, `Cotización: ${item.rate} ARS/${currency}`)}
            />
          </Animated.View>
        ))}

        {/* Botones de acción */}
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
    borderBottomColor: colors.divider,
  },
  headerIcon: {
    padding: 4,
    width: 36,
  },
  headerTitle: {
    fontSize: 18,
    fontWeight: '700',
    color: colors.textPrimary,
  },
  scroll: {
    flex: 1,
  },
  scrollContent: {
    paddingHorizontal: 20,
    paddingTop: 20,
    paddingBottom: 32,
  },
  contextLabel: {
    fontSize: 14,
    color: colors.textSecondary,
    marginBottom: 16,
  },

  // Logo
  providerLogo: {
    width: 40,
    height: 40,
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
  },
  providerLogoText: {
    color: '#ffffff',
    fontSize: 13,
    fontWeight: '800',
    letterSpacing: 0.5,
  },
  cardHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    marginBottom: 10,
  },

  // Cards
  card: {
    backgroundColor: colors.background,
    borderRadius: 16,
    borderWidth: 1.5,
    borderColor: colors.border,
    padding: 20,
    marginBottom: 16,
  },
  bestCard: {
    borderColor: colors.success,
    backgroundColor: '#f0fdf4',
  },
  bestBadge: {
    alignSelf: 'flex-start',
    backgroundColor: '#dcfce7',
    borderRadius: 6,
    paddingHorizontal: 10,
    paddingVertical: 4,
    marginBottom: 12,
  },
  bestBadgeText: {
    fontSize: 12,
    fontWeight: '600',
    color: colors.success,
  },
  providerName: {
    fontSize: 20,
    fontWeight: '700',
    color: colors.textPrimary,
    flex: 1,
  },
  priceText: {
    fontSize: 32,
    fontWeight: '700',
    color: colors.textPrimary,
    letterSpacing: -1,
    marginBottom: 8,
  },
  priceTextNormal: {
    fontSize: 24,
    fontWeight: '700',
    color: colors.textPrimary,
    letterSpacing: -0.5,
    marginBottom: 6,
  },
  savingsText: {
    fontSize: 14,
    color: colors.success,
    fontWeight: '600',
    marginBottom: 12,
  },
  savingsTextNormal: {
    fontSize: 13,
    color: colors.textSecondary,
    marginBottom: 10,
  },
  seeMoreLink: {
    fontSize: 14,
    color: colors.primary,
    fontWeight: '600',
  },

  // Botones
  actions: {
    flexDirection: 'row',
    gap: 12,
    marginTop: 8,
  },
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
  buttonSecondaryText: {
    fontSize: 14,
    fontWeight: '700',
    color: colors.textSecondary,
    letterSpacing: 0.5,
  },
  buttonPrimary: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 12,
    paddingVertical: 16,
    backgroundColor: colors.primary,
  },
  buttonPrimaryText: {
    fontSize: 14,
    fontWeight: '700',
    color: '#ffffff',
    letterSpacing: 0.5,
  },
});
