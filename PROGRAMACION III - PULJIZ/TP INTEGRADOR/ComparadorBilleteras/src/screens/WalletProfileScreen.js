import { View, Text, StyleSheet, ScrollView, TouchableOpacity, StatusBar, Linking, Alert } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { getWalletMeta } from '../data/wallets';

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

const HOW_IT_WORKS = {
  'Mercado Pago': 'Vinculá tu cuenta de Mercado Pago Argentina y pagá en cualquier comercio de Brasil que acepte PIX. El tipo de cambio se aplica al momento de la transacción.',
  'Ualá': 'Con tu tarjeta Ualá podés hacer pagos via PIX en Brasil directamente desde la app. El débito se hace en ARS al tipo de cambio del día.',
  'Bimo': 'Bimo permite transferencias internacionales y pagos PIX. Operá desde Argentina y pagá en reales de forma instantánea.',
  'Prex': 'Con la tarjeta Prex podés pagar en Brasil en comercios habilitados. El saldo se gestiona en ARS y la conversión es automática.',
  'Naranja X': 'Naranja X permite realizar pagos internacionales via PIX usando el saldo en ARS de tu cuenta. Sin necesidad de cambiar divisas previamente.',
  'Brubank': 'Brubank ofrece pagos internacionales en Brasil con su tarjeta Visa. La conversión ARS→BRL se hace automáticamente al mejor tipo de cambio disponible.',
  'Personal Pay': 'Personal Pay funciona como billetera digital para pagos en Brasil. Vinculá tu cuenta y pagá en pesos argentinos con conversión automática.',
  'Lemon Cash': 'Lemon Cash permite pagos en Brasil usando crypto o ARS. La app convierte automáticamente al tipo de cambio más conveniente.',
  'Modo': 'Modo es la billetera interoperativa de los bancos argentinos. Permite pagos PIX en Brasil desde tu cuenta bancaria en ARS.',
  'Cuenta DNI': 'Cuenta DNI del Banco Provincia permite pagos en Brasil via PIX directamente desde tu cuenta. El tipo de cambio lo fija el banco al momento del pago.',
};

export default function WalletProfileScreen({ route, navigation }) {
  const { walletName } = route.params;
  const meta = getWalletMeta(walletName);

  const handleOpenSite = () => {
    if (!meta.appUrl) {
      Alert.alert('Sin link', 'No tenemos un sitio configurado para esta billetera.');
      return;
    }
    Linking.openURL(meta.appUrl).catch(() =>
      Alert.alert('Error', 'No se pudo abrir el link.')
    );
  };

  return (
    <SafeAreaView style={styles.safe}>
      <StatusBar barStyle="dark-content" />
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()} activeOpacity={0.7}>
          <Ionicons name="arrow-back" size={24} color={colors.textPrimary} />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>{walletName}</Text>
        <View style={{ width: 24 }} />
      </View>

      <ScrollView style={styles.scroll} contentContainerStyle={styles.scrollContent}>
        {/* Hero */}
        <View style={styles.hero}>
          <View style={[styles.logo, { backgroundColor: meta.color }]}>
            <Text style={styles.logoText}>{meta.initials}</Text>
          </View>
          <Text style={styles.heroName}>{walletName}</Text>
          <Text style={styles.heroDesc}>{meta.description}</Text>
        </View>

        {/* Monedas disponibles */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Monedas disponibles</Text>
          <View style={styles.chipRow}>
            <View style={styles.chip}>
              <Text style={styles.chipText}>🇧🇷 BRL</Text>
            </View>
          </View>
        </View>

        <View style={styles.divider} />

        {/* Cómo funciona */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>¿Cómo funciona el envío?</Text>
          <Text style={styles.bodyText}>
            {HOW_IT_WORKS[walletName] ?? 'Realizá pagos PIX en Brasil directamente desde tu billetera argentina. La conversión ARS→BRL se aplica automáticamente al tipo de cambio vigente.'}
          </Text>
        </View>

        <View style={styles.divider} />

        {/* Pros y contras */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Pros y contras</Text>
          <View style={styles.prosConsList}>
            {meta.commission === '0%' && (
              <View style={styles.prosConsItem}>
                <Text style={styles.proIcon}>✅</Text>
                <Text style={styles.prosConsText}>Sin comisión para PIX</Text>
              </View>
            )}
            <View style={styles.prosConsItem}>
              <Text style={styles.proIcon}>✅</Text>
              <Text style={styles.prosConsText}>Instantáneo 24/7</Text>
            </View>
            <View style={styles.prosConsItem}>
              <Text style={styles.conIcon}>❌</Text>
              <Text style={styles.prosConsText}>Límite {meta.dailyLimit}/día</Text>
            </View>
            <View style={styles.prosConsItem}>
              <Text style={styles.conIcon}>❌</Text>
              <Text style={styles.prosConsText}>Requiere cuenta verificada</Text>
            </View>
          </View>
        </View>

        <TouchableOpacity style={styles.siteButton} onPress={handleOpenSite} activeOpacity={0.7}>
          <Text style={styles.siteButtonText}>IR AL SITIO OFICIAL</Text>
          <Ionicons name="open-outline" size={16} color={colors.primary} />
        </TouchableOpacity>
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
    borderBottomColor: colors.border,
  },
  headerTitle: {
    fontSize: 18,
    fontWeight: '600',
    color: colors.textPrimary,
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
    paddingHorizontal: 20,
  },
  logo: {
    width: 80,
    height: 80,
    borderRadius: 40,
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 14,
  },
  logoText: {
    color: '#ffffff',
    fontSize: 22,
    fontWeight: '700',
  },
  heroName: {
    fontSize: 28,
    fontWeight: '700',
    color: colors.textPrimary,
    marginBottom: 6,
  },
  heroDesc: {
    fontSize: 14,
    color: colors.textSecondary,
    textAlign: 'center',
    lineHeight: 20,
  },
  section: {
    paddingHorizontal: 20,
    paddingVertical: 16,
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: colors.textPrimary,
    marginBottom: 12,
  },
  chipRow: {
    flexDirection: 'row',
    gap: 8,
  },
  chip: {
    backgroundColor: colors.surface,
    borderRadius: 20,
    paddingHorizontal: 14,
    paddingVertical: 6,
    borderWidth: 1,
    borderColor: colors.border,
  },
  chipText: {
    fontSize: 14,
    fontWeight: '600',
    color: colors.textPrimary,
  },
  divider: {
    height: 1,
    backgroundColor: colors.divider,
    marginHorizontal: 20,
  },
  bodyText: {
    fontSize: 15,
    color: colors.textSecondary,
    lineHeight: 22,
  },
  prosConsList: {
    gap: 10,
  },
  prosConsItem: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  proIcon: {
    fontSize: 16,
  },
  conIcon: {
    fontSize: 16,
  },
  prosConsText: {
    fontSize: 15,
    color: colors.textPrimary,
  },
  siteButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    marginHorizontal: 20,
    marginTop: 24,
    borderWidth: 2,
    borderColor: colors.primary,
    borderRadius: 12,
    paddingVertical: 14,
    gap: 8,
  },
  siteButtonText: {
    fontSize: 15,
    fontWeight: '700',
    color: colors.primary,
    letterSpacing: 0.5,
  },
});
