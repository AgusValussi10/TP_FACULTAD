import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  StatusBar,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { getWalletByName } from '../data/wallets';

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

function Stars({ rating }) {
  return (
    <View style={styles.starsRow}>
      {[1, 2, 3, 4, 5].map(i => (
        <Ionicons
          key={i}
          name={i <= Math.round(rating) ? 'star' : 'star-outline'}
          size={14}
          color="#f59e0b"
        />
      ))}
      <Text style={styles.ratingLabel}>{rating.toFixed(1)}</Text>
    </View>
  );
}

function DetailRow({ icon, label, value }) {
  return (
    <View style={styles.detailRow}>
      <Ionicons name={icon} size={18} color={colors.textSecondary} style={styles.detailIcon} />
      <Text style={styles.detailLabel}>{label}</Text>
      <Text style={styles.detailValue}>{value}</Text>
    </View>
  );
}

export default function WalletDetailScreen({ route, navigation }) {
  const { walletName } = route.params;
  const wallet = getWalletByName(walletName);

  if (!wallet) {
    return (
      <SafeAreaView style={styles.safe}>
        <View style={styles.header}>
          <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
            <Ionicons name="arrow-back" size={24} color={colors.textPrimary} />
          </TouchableOpacity>
          <Text style={styles.headerTitle}>Billetera</Text>
          <View style={styles.headerRight} />
        </View>
        <View style={styles.notFound}>
          <Text style={styles.notFoundText}>Billetera no encontrada</Text>
        </View>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={styles.safe}>
      <StatusBar barStyle="dark-content" backgroundColor={colors.background} />

      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
          <Ionicons name="arrow-back" size={24} color={colors.textPrimary} />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>{wallet.name}</Text>
        <View style={styles.headerRight} />
      </View>

      <ScrollView
        style={styles.scroll}
        contentContainerStyle={styles.scrollContent}
        showsVerticalScrollIndicator={false}
      >
        {/* Hero */}
        <View style={styles.hero}>
          <View style={[styles.heroLogo, { backgroundColor: wallet.color }]}>
            <Text style={styles.heroLogoText}>{wallet.initials}</Text>
          </View>
          <Text style={styles.heroName}>{wallet.name}</Text>
          <Stars rating={wallet.rating} />
          <Text style={styles.ratingCount}>
            {wallet.ratingCount.toLocaleString('es-AR')} opiniones
          </Text>
        </View>

        {/* Descripción */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Sobre esta billetera</Text>
          <Text style={styles.description}>{wallet.description}</Text>
        </View>

        {/* Datos clave */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Condiciones de transferencia</Text>
          <View style={styles.card}>
            <DetailRow icon="pricetag-outline"    label="Comisión"         value={wallet.commission} />
            <View style={styles.rowDivider} />
            <DetailRow icon="trending-up-outline" label="Límite diario"    value={wallet.dailyLimit} />
            <View style={styles.rowDivider} />
            <DetailRow icon="calendar-outline"    label="Límite mensual"   value={wallet.monthlyLimit} />
            <View style={styles.rowDivider} />
            <DetailRow icon="time-outline"        label="Tiempo estimado"  value={wallet.estimatedTime} />
          </View>
        </View>

        {/* Monedas */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Monedas disponibles</Text>
          <View style={styles.chipsRow}>
            {wallet.currencies.map(c => (
              <View key={c} style={styles.chip}>
                <Text style={styles.chipText}>{c}</Text>
              </View>
            ))}
          </View>
        </View>

        {/* Países */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Países de destino</Text>
          <View style={styles.chipsRow}>
            {wallet.countryFlags.map((flag, i) => (
              <View key={i} style={styles.flagChip}>
                <Text style={styles.flagChipText}>{flag}</Text>
              </View>
            ))}
          </View>
        </View>

        {/* Comisión detallada */}
        <View style={styles.infoBox}>
          <Ionicons name="information-circle-outline" size={18} color={colors.primary} />
          <Text style={styles.infoText}>{wallet.commissionDetail}</Text>
        </View>

        {/* Botones */}
        <View style={styles.actions}>
          <TouchableOpacity
            style={styles.outlineBtn}
            onPress={() => navigation.navigate('WalletProfile', { walletName: wallet.name })}
            activeOpacity={0.8}
          >
            <Ionicons name="document-text-outline" size={18} color={colors.primary} />
            <Text style={styles.outlineBtnText}>Ver perfil completo</Text>
          </TouchableOpacity>

          <TouchableOpacity
            style={styles.primaryBtn}
            onPress={() => navigation.navigate('Home')}
            activeOpacity={0.85}
          >
            <Ionicons name="git-compare-outline" size={18} color="#ffffff" />
            <Text style={styles.primaryBtnText}>Comparar</Text>
          </TouchableOpacity>
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: {
    flex: 1,
    backgroundColor: colors.surface,
  },
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
  backBtn: {
    width: 36,
    height: 36,
    alignItems: 'center',
    justifyContent: 'center',
  },
  headerTitle: {
    fontSize: 18,
    fontWeight: '700',
    color: colors.textPrimary,
  },
  headerRight: {
    width: 36,
  },
  scroll: {
    flex: 1,
  },
  scrollContent: {
    paddingBottom: 32,
  },
  hero: {
    alignItems: 'center',
    paddingVertical: 28,
    backgroundColor: colors.background,
    borderBottomWidth: 1,
    borderBottomColor: colors.divider,
    gap: 6,
  },
  heroLogo: {
    width: 72,
    height: 72,
    borderRadius: 18,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 4,
  },
  heroLogoText: {
    color: '#ffffff',
    fontSize: 24,
    fontWeight: '800',
    letterSpacing: 1,
  },
  heroName: {
    fontSize: 22,
    fontWeight: '700',
    color: colors.textPrimary,
  },
  starsRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 3,
  },
  ratingLabel: {
    fontSize: 14,
    color: colors.textSecondary,
    marginLeft: 4,
    fontWeight: '600',
  },
  ratingCount: {
    fontSize: 12,
    color: colors.textMuted,
  },
  section: {
    paddingHorizontal: 20,
    paddingTop: 20,
    gap: 10,
  },
  sectionTitle: {
    fontSize: 13,
    fontWeight: '700',
    color: colors.textSecondary,
    textTransform: 'uppercase',
    letterSpacing: 0.5,
  },
  description: {
    fontSize: 15,
    color: colors.textPrimary,
    lineHeight: 22,
  },
  card: {
    backgroundColor: colors.background,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: colors.border,
    overflow: 'hidden',
  },
  detailRow: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingVertical: 13,
    gap: 12,
  },
  detailIcon: {
    width: 20,
  },
  detailLabel: {
    flex: 1,
    fontSize: 15,
    color: colors.textPrimary,
  },
  detailValue: {
    fontSize: 15,
    fontWeight: '600',
    color: colors.textPrimary,
  },
  rowDivider: {
    height: 1,
    backgroundColor: colors.divider,
    marginLeft: 48,
  },
  chipsRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
  },
  chip: {
    backgroundColor: '#eff6ff',
    borderRadius: 8,
    paddingHorizontal: 14,
    paddingVertical: 6,
  },
  chipText: {
    fontSize: 13,
    fontWeight: '700',
    color: colors.primary,
  },
  flagChip: {
    backgroundColor: colors.surface,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: colors.border,
    paddingHorizontal: 10,
    paddingVertical: 6,
  },
  flagChipText: {
    fontSize: 18,
  },
  infoBox: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 10,
    backgroundColor: '#eff6ff',
    borderRadius: 12,
    borderWidth: 1,
    borderColor: '#bfdbfe',
    marginHorizontal: 20,
    marginTop: 20,
    padding: 14,
  },
  infoText: {
    flex: 1,
    fontSize: 13,
    color: colors.textSecondary,
    lineHeight: 19,
  },
  actions: {
    flexDirection: 'row',
    gap: 12,
    paddingHorizontal: 20,
    paddingTop: 24,
  },
  outlineBtn: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    borderRadius: 12,
    borderWidth: 1.5,
    borderColor: colors.primary,
    paddingVertical: 14,
  },
  outlineBtnText: {
    fontSize: 14,
    fontWeight: '600',
    color: colors.primary,
  },
  primaryBtn: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    borderRadius: 12,
    backgroundColor: colors.primary,
    paddingVertical: 14,
  },
  primaryBtnText: {
    fontSize: 14,
    fontWeight: '700',
    color: '#ffffff',
  },
  notFound: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  notFoundText: {
    fontSize: 16,
    color: colors.textSecondary,
  },
});
