import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  SafeAreaView,
  StatusBar,
  Linking,
  Alert,
  Share,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { formatARS, getWalletMeta } from '../data/wallets';

const colors = {
  primary: '#3b82f6',
  success: '#10b981',
  successSoft: '#f0fdf4',
  background: '#ffffff',
  surface: '#f8f9fa',
  border: '#dee2e6',
  textPrimary: '#1a1a1a',
  textSecondary: '#6c757d',
  divider: '#e0e0e0',
};

const formatRate = (rate) => `$ ${rate.toFixed(2)} ARS`;

export default function WalletDetailScreen({ route, navigation }) {
  const { wallet, amount, currency } = route.params;
  const meta = getWalletMeta(wallet.name);

  const handleOpenApp = () => {
    if (!meta.appUrl) {
      Alert.alert('Sin link', 'No tenemos un link configurado para esta billetera.');
      return;
    }
    Linking.openURL(meta.appUrl).catch(() =>
      Alert.alert('Error', 'No se pudo abrir el link.')
    );
  };

  const handleShare = async () => {
    try {
      await Share.share({
        message:
          `${wallet.name} — Cotización ARS/${currency}\n` +
          `Tipo de cambio: ${formatRate(wallet.rate)} / ${currency}\n` +
          `Pagando ${amount} ${currency} = ${formatARS(wallet.price)}`,
      });
    } catch {
      // ignore
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
        <Text style={styles.headerTitle} numberOfLines={1}>
          {wallet.name}
        </Text>
        <TouchableOpacity style={styles.headerIcon} onPress={handleShare}>
          <Ionicons name="share-outline" size={22} color={colors.textPrimary} />
        </TouchableOpacity>
      </View>

      <ScrollView
        style={styles.scroll}
        contentContainerStyle={styles.scrollContent}
        showsVerticalScrollIndicator={false}
      >
        {/* Identificación */}
        <View style={styles.identityBlock}>
          <View style={[styles.logo, { backgroundColor: meta.color }]}>
            <Text style={styles.logoText}>{meta.initials}</Text>
          </View>
          <Text style={styles.walletName}>{wallet.name}</Text>
          {wallet.isBest && (
            <View style={styles.bestBadge}>
              <Text style={styles.bestBadgeText}>💚 Mejor opción</Text>
            </View>
          )}
          {meta.description ? (
            <Text style={styles.description}>{meta.description}</Text>
          ) : null}
        </View>

        {/* Tipo de cambio */}
        <View style={styles.rateBlock}>
          <Text style={styles.rateLabel}>Tipo de cambio hoy</Text>
          <Text style={styles.rateValue}>
            {formatRate(wallet.rate)} / {currency}
          </Text>
        </View>

        {/* Detalles */}
        <View style={styles.detailsCard}>
          <DetailRow icon="cash-outline" label="Comisión" value={meta.commission} />
          <Divider />
          <DetailRow icon="document-text-outline" label="Límite diario" value={meta.dailyLimit} />
          <Divider />
          <DetailRow icon="time-outline" label="Tiempo estimado" value={meta.estimatedTime} />
          <Divider />
          <DetailRow icon="earth-outline" label="País destino" value="Brasil (PIX)" />
        </View>

        {/* Total a pagar */}
        <View style={styles.totalCard}>
          <Text style={styles.totalLabel}>Pagás</Text>
          <Text style={styles.totalValue}>{formatARS(wallet.price)}</Text>
          {wallet.savings !== null && wallet.savings > 0 && (
            <Text style={styles.totalSavings}>
              ✓ Ahorrás {formatARS(wallet.savings)} ({wallet.savingsPct.toFixed(1)}%)
            </Text>
          )}
        </View>

        {/* Botón ir a la app */}
        <TouchableOpacity
          style={[styles.appButton, { backgroundColor: meta.color }]}
          onPress={handleOpenApp}
          activeOpacity={0.85}
        >
          <Text style={styles.appButtonText}>IR A LA APP DE {wallet.name.toUpperCase()}</Text>
          <Ionicons name="arrow-forward" size={18} color="#ffffff" />
        </TouchableOpacity>
      </ScrollView>
    </SafeAreaView>
  );
}

function DetailRow({ icon, label, value }) {
  return (
    <View style={styles.row}>
      <View style={styles.rowLeft}>
        <Ionicons name={icon} size={18} color={colors.textSecondary} />
        <Text style={styles.rowLabel}>{label}</Text>
      </View>
      <Text style={styles.rowValue}>{value}</Text>
    </View>
  );
}

function Divider() {
  return <View style={styles.divider} />;
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
    flex: 1,
    textAlign: 'center',
    fontSize: 18,
    fontWeight: '700',
    color: colors.textPrimary,
  },
  scroll: {
    flex: 1,
  },
  scrollContent: {
    paddingHorizontal: 20,
    paddingTop: 24,
    paddingBottom: 32,
  },
  identityBlock: {
    alignItems: 'center',
    marginBottom: 28,
  },
  logo: {
    width: 72,
    height: 72,
    borderRadius: 36,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 14,
  },
  logoText: {
    color: '#ffffff',
    fontSize: 22,
    fontWeight: '800',
    letterSpacing: 1,
  },
  walletName: {
    fontSize: 24,
    fontWeight: '700',
    color: colors.textPrimary,
    marginBottom: 8,
  },
  bestBadge: {
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
  description: {
    fontSize: 13,
    color: colors.textSecondary,
    textAlign: 'center',
    paddingHorizontal: 16,
    lineHeight: 18,
  },
  rateBlock: {
    backgroundColor: colors.surface,
    borderRadius: 16,
    padding: 20,
    marginBottom: 16,
    alignItems: 'center',
  },
  rateLabel: {
    fontSize: 13,
    color: colors.textSecondary,
    marginBottom: 6,
    fontWeight: '500',
  },
  rateValue: {
    fontSize: 28,
    fontWeight: '700',
    color: colors.primary,
    letterSpacing: -0.5,
  },
  detailsCard: {
    backgroundColor: colors.background,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: colors.border,
    paddingHorizontal: 16,
    marginBottom: 16,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingVertical: 14,
  },
  rowLeft: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  rowLabel: {
    fontSize: 14,
    color: colors.textSecondary,
  },
  rowValue: {
    fontSize: 14,
    fontWeight: '600',
    color: colors.textPrimary,
  },
  divider: {
    height: 1,
    backgroundColor: colors.divider,
  },
  totalCard: {
    backgroundColor: colors.successSoft,
    borderRadius: 16,
    borderWidth: 1.5,
    borderColor: colors.success,
    padding: 20,
    alignItems: 'center',
    marginBottom: 20,
  },
  totalLabel: {
    fontSize: 13,
    color: colors.textSecondary,
    marginBottom: 4,
    fontWeight: '500',
  },
  totalValue: {
    fontSize: 32,
    fontWeight: '700',
    color: colors.textPrimary,
    letterSpacing: -1,
    marginBottom: 6,
  },
  totalSavings: {
    fontSize: 13,
    color: colors.success,
    fontWeight: '600',
  },
  appButton: {
    borderRadius: 12,
    paddingVertical: 18,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 10,
  },
  appButtonText: {
    color: '#ffffff',
    fontSize: 14,
    fontWeight: '700',
    letterSpacing: 0.5,
  },
});
