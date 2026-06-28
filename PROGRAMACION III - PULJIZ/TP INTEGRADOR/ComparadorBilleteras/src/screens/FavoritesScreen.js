
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

const INIT_WALLETS = [
  { id: '1', name: 'Mercado Pago', initials: 'MP', color: '#3b82f6' },
  { id: '2', name: 'Ualá', initials: 'UA', color: '#8b5cf6' },
];

const INIT_PAIRS = [
  { id: '1', label: '🇦🇷 ARS / 🇧🇷 BRL' },
  { id: '2', label: '🇦🇷 ARS / 🇺🇸 USD' },
];

export default function FavoritesScreen({ navigation }) {
  const [wallets, setWallets] = useState(INIT_WALLETS);
  const [pairs, setPairs] = useState(INIT_PAIRS);

  const removeWallet = (id) => setWallets((prev) => prev.filter((w) => w.id !== id));
  const removePair = (id) => setPairs((prev) => prev.filter((p) => p.id !== id));

  const isEmpty = wallets.length === 0 && pairs.length === 0;

  return (
    <SafeAreaView style={styles.safe}>
      <StatusBar barStyle="dark-content" backgroundColor={colors.background} />

      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
          <Ionicons name="arrow-back" size={22} color={colors.textPrimary} />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Favoritos</Text>
        <TouchableOpacity style={styles.manageBtn}>
          <Text style={styles.manageBtnText}>Gestionar</Text>
        </TouchableOpacity>
      </View>

      {isEmpty ? (
        <View style={styles.emptyContainer}>
          <Ionicons name="star-outline" size={64} color={colors.textMuted} />
          <Text style={styles.emptyText}>
            Guardá tus billeteras favoritas para comparar más rápido
          </Text>
        </View>
      ) : (
        <ScrollView
          style={styles.scroll}
          showsVerticalScrollIndicator={false}
          contentContainerStyle={styles.scrollContent}
        >
          {wallets.length > 0 && (
            <>
              <Text style={styles.sectionTitle}>Billeteras favoritas</Text>
              {wallets.map((wallet) => (
                <View key={wallet.id} style={styles.walletCard}>
                  <View style={[styles.walletLogo, { backgroundColor: wallet.color }]}>
                    <Text style={styles.walletInitials}>{wallet.initials}</Text>
                  </View>
                  <Text style={styles.walletName}>{wallet.name}</Text>
                  <Ionicons name="star" size={18} color="#f59e0b" />
                  <TouchableOpacity
                    onPress={() => removeWallet(wallet.id)}
                    style={styles.removeBtn}
                  >
                    <Ionicons name="close-circle" size={20} color={colors.textMuted} />
                  </TouchableOpacity>
                </View>
              ))}
            </>
          )}

          {pairs.length > 0 && (
            <>
              <Text style={styles.sectionTitle}>Pares frecuentes</Text>
              <View style={styles.pairsRow}>
                {pairs.map((pair) => (
                  <View key={pair.id} style={styles.pairChip}>
                    <Text style={styles.pairLabel}>{pair.label}</Text>
                    <TouchableOpacity onPress={() => removePair(pair.id)}>
                      <Ionicons name="close" size={14} color={colors.textSecondary} />
                    </TouchableOpacity>
                  </View>
                ))}
              </View>
            </>
          )}

          <TouchableOpacity style={styles.addBtn}>
            <Ionicons name="add" size={18} color={colors.primary} />
            <Text style={styles.addBtnText}>AGREGAR FAVORITO +</Text>
          </TouchableOpacity>
        </ScrollView>
      )}
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
  backBtn: {
    padding: 4,
    width: 36,
  },
  headerTitle: {
    fontSize: 18,
    fontWeight: '700',
    color: colors.textPrimary,
  },
  manageBtn: {
    padding: 4,
  },
  manageBtnText: {
    fontSize: 14,
    fontWeight: '600',
    color: colors.primary,
  },
  emptyContainer: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 40,
    gap: 16,
  },
  emptyText: {
    fontSize: 15,
    color: colors.textSecondary,
    textAlign: 'center',
    lineHeight: 22,
  },
  scroll: {
    flex: 1,
  },
  scrollContent: {
    padding: 20,
    paddingBottom: 32,
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: colors.textPrimary,
    marginBottom: 12,
    marginTop: 8,
  },
  walletCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderRadius: 12,
    padding: 14,
    borderWidth: 1,
    borderColor: colors.border,
    gap: 12,
    marginBottom: 8,
  },
  walletLogo: {
    width: 40,
    height: 40,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  walletInitials: {
    fontSize: 14,
    fontWeight: '700',
    color: '#ffffff',
  },
  walletName: {
    flex: 1,
    fontSize: 16,
    fontWeight: '600',
    color: colors.textPrimary,
  },
  removeBtn: {
    padding: 2,
  },
  pairsRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 10,
    marginBottom: 24,
  },
  pairChip: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#eff6ff',
    borderRadius: 20,
    paddingHorizontal: 14,
    paddingVertical: 8,
    borderWidth: 1,
    borderColor: '#bfdbfe',
    gap: 6,
  },
  pairLabel: {
    fontSize: 14,
    fontWeight: '500',
    color: colors.primary,
  },
  addBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 12,
    borderWidth: 1.5,
    borderColor: colors.primary,
    paddingVertical: 14,
    gap: 8,
    marginTop: 8,
  },
  addBtnText: {
    fontSize: 14,
    fontWeight: '700',
    color: colors.primary,
    letterSpacing: 0.5,
  },
});
