import { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  SafeAreaView,
  StatusBar,
  Modal,
  Linking,
  Alert,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { buildResults, formatARS, getWalletMeta } from '../data/wallets';

const colors = {
  primary: '#3b82f6',
  success: '#10b981',
  successSoft: '#f0fdf4',
  background: '#ffffff',
  surface: '#f8f9fa',
  border: '#dee2e6',
  textPrimary: '#1a1a1a',
  textSecondary: '#6c757d',
  textMuted: '#adb5bd',
  divider: '#e0e0e0',
};

const formatRate = (rate) => `${rate.toFixed(2)}`;

export default function CompareScreen({ route, navigation }) {
  const { amount, currency = 'BRL', initialWallet1, initialWallet2 } = route.params;
  const allResults = buildResults(amount, currency);

  const [wallet1, setWallet1] = useState(
    allResults.find((r) => r.name === initialWallet1?.name) ?? allResults[0]
  );
  const [wallet2, setWallet2] = useState(
    allResults.find((r) => r.name === initialWallet2?.name) ?? allResults[1]
  );
  const [pickerFor, setPickerFor] = useState(null); // 1 | 2 | null

  const handlePick = (wallet) => {
    if (pickerFor === 1) setWallet1(wallet);
    else if (pickerFor === 2) setWallet2(wallet);
    setPickerFor(null);
  };

  const meta1 = getWalletMeta(wallet1.name);
  const meta2 = getWalletMeta(wallet2.name);

  const winner = wallet1.price <= wallet2.price ? wallet1 : wallet2;
  const loser = winner === wallet1 ? wallet2 : wallet1;
  const diff = loser.price - winner.price;

  const w1Wins = wallet1.price <= wallet2.price;
  const w2Wins = !w1Wins || wallet1.price === wallet2.price;

  const rows = [
    {
      label: 'Precio total',
      v1: formatARS(wallet1.price),
      v2: formatARS(wallet2.price),
      w1: w1Wins,
      w2: w2Wins,
    },
    {
      label: 'Tipo de cambio',
      v1: formatRate(wallet1.rate),
      v2: formatRate(wallet2.rate),
      w1: wallet1.rate <= wallet2.rate,
      w2: wallet2.rate <= wallet1.rate,
    },
    {
      label: 'Comisión',
      v1: meta1.commission,
      v2: meta2.commission,
      w1: parseFloat(meta1.commission) <= parseFloat(meta2.commission),
      w2: parseFloat(meta2.commission) <= parseFloat(meta1.commission),
    },
    {
      label: 'Límite diario',
      v1: meta1.dailyLimit,
      v2: meta2.dailyLimit,
      w1: parseInt(meta1.dailyLimit.replace(/[^0-9]/g, '')) >= parseInt(meta2.dailyLimit.replace(/[^0-9]/g, '')),
      w2: parseInt(meta2.dailyLimit.replace(/[^0-9]/g, '')) >= parseInt(meta1.dailyLimit.replace(/[^0-9]/g, '')),
    },
    {
      label: 'Tiempo',
      v1: meta1.estimatedTime,
      v2: meta2.estimatedTime,
      w1: meta1.estimatedTime === 'Instantáneo',
      w2: meta2.estimatedTime === 'Instantáneo',
    },
  ];

  const handleChoose = () => {
    const meta = getWalletMeta(winner.name);
    if (!meta.appUrl) {
      Alert.alert('Sin link', 'No tenemos un link configurado para esta billetera.');
      return;
    }
    Linking.openURL(meta.appUrl).catch(() =>
      Alert.alert('Error', 'No se pudo abrir el link.')
    );
  };

  return (
    <SafeAreaView style={styles.safe}>
      <StatusBar barStyle="dark-content" backgroundColor={colors.background} />

      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity style={styles.headerIcon} onPress={() => navigation.goBack()}>
          <Ionicons name="arrow-back" size={24} color={colors.textPrimary} />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Comparar</Text>
        <View style={styles.headerIcon} />
      </View>

      <ScrollView
        style={styles.scroll}
        contentContainerStyle={styles.scrollContent}
        showsVerticalScrollIndicator={false}
      >
        <Text style={styles.context}>
          Pagando {amount} {currency}
        </Text>

        {/* Selector de billeteras */}
        <View style={styles.walletSelectors}>
          <WalletColumn wallet={wallet1} meta={meta1} onPress={() => setPickerFor(1)} />
          <View style={styles.vsContainer}>
            <Text style={styles.vsText}>VS</Text>
          </View>
          <WalletColumn wallet={wallet2} meta={meta2} onPress={() => setPickerFor(2)} />
        </View>

        {/* Tabla comparativa */}
        <View style={styles.table}>
          <View style={styles.tableHeader}>
            <Text style={[styles.tableCell, styles.tableHeaderText, { flex: 1.3 }]}>
              Criterio
            </Text>
            <Text style={[styles.tableCell, styles.tableHeaderText]}>{wallet1.name}</Text>
            <Text style={[styles.tableCell, styles.tableHeaderText]}>{wallet2.name}</Text>
          </View>

          {rows.map((row, i) => (
            <View
              key={row.label}
              style={[styles.tableRow, i === rows.length - 1 && styles.tableRowLast]}
            >
              <Text style={[styles.tableCell, styles.tableLabel, { flex: 1.3 }]}>
                {row.label}
              </Text>
              <Cell value={row.v1} isWinner={row.w1} />
              <Cell value={row.v2} isWinner={row.w2} />
            </View>
          ))}
        </View>

        {/* Resumen */}
        {diff > 0 ? (
          <View style={styles.summary}>
            <Ionicons name="trophy" size={20} color={colors.success} />
            <Text style={styles.summaryText}>
              <Text style={styles.summaryWinner}>{winner.name}</Text> te da{' '}
              <Text style={styles.summaryAmount}>{formatARS(diff)}</Text> más
            </Text>
          </View>
        ) : (
          <View style={styles.summary}>
            <Ionicons name="information-circle" size={20} color={colors.primary} />
            <Text style={styles.summaryText}>Ambas opciones tienen el mismo precio</Text>
          </View>
        )}

        {/* Botón */}
        <TouchableOpacity
          style={[styles.chooseButton, { backgroundColor: getWalletMeta(winner.name).color }]}
          onPress={handleChoose}
          activeOpacity={0.85}
        >
          <Text style={styles.chooseButtonText}>ELEGIR {winner.name.toUpperCase()}</Text>
          <Ionicons name="arrow-forward" size={18} color="#ffffff" />
        </TouchableOpacity>
      </ScrollView>

      {/* Modal selector */}
      <Modal
        visible={pickerFor !== null}
        transparent
        animationType="slide"
        onRequestClose={() => setPickerFor(null)}
      >
        <TouchableOpacity
          style={styles.modalOverlay}
          activeOpacity={1}
          onPress={() => setPickerFor(null)}
        >
          <TouchableOpacity
            activeOpacity={1}
            style={styles.modalSheet}
            onPress={(e) => e.stopPropagation()}
          >
            <View style={styles.modalHandle} />
            <Text style={styles.modalTitle}>Elegí una billetera</Text>
            <ScrollView showsVerticalScrollIndicator={false}>
              {allResults.map((wallet) => {
                const m = getWalletMeta(wallet.name);
                const isUsed =
                  (pickerFor === 1 && wallet.name === wallet2.name) ||
                  (pickerFor === 2 && wallet.name === wallet1.name);
                return (
                  <TouchableOpacity
                    key={wallet.id}
                    style={[styles.modalOption, isUsed && styles.modalOptionDisabled]}
                    onPress={() => !isUsed && handlePick(wallet)}
                    activeOpacity={isUsed ? 1 : 0.7}
                    disabled={isUsed}
                  >
                    <View style={[styles.modalLogo, { backgroundColor: m.color }]}>
                      <Text style={styles.modalLogoText}>{m.initials}</Text>
                    </View>
                    <View style={{ flex: 1 }}>
                      <Text style={[styles.modalName, isUsed && styles.modalTextDisabled]}>
                        {wallet.name}
                      </Text>
                      <Text style={styles.modalPrice}>{formatARS(wallet.price)}</Text>
                    </View>
                    {isUsed && <Text style={styles.modalUsedTag}>en uso</Text>}
                  </TouchableOpacity>
                );
              })}
            </ScrollView>
          </TouchableOpacity>
        </TouchableOpacity>
      </Modal>
    </SafeAreaView>
  );
}

function WalletColumn({ wallet, meta, onPress }) {
  return (
    <TouchableOpacity style={styles.walletColumn} onPress={onPress} activeOpacity={0.7}>
      <View style={[styles.walletLogo, { backgroundColor: meta.color }]}>
        <Text style={styles.walletLogoText}>{meta.initials}</Text>
      </View>
      <Text style={styles.walletName} numberOfLines={1}>
        {wallet.name}
      </Text>
      <Text style={styles.walletPrice}>{formatARS(wallet.price)}</Text>
      <View style={styles.changeBadge}>
        <Ionicons name="swap-horizontal" size={11} color={colors.primary} />
        <Text style={styles.changeBadgeText}>Cambiar</Text>
      </View>
    </TouchableOpacity>
  );
}

function Cell({ value, isWinner }) {
  return (
    <View style={[styles.tableCell, styles.cellValue, isWinner && styles.cellWinner]}>
      <Text style={[styles.cellText, isWinner && styles.cellTextWinner]}>{value}</Text>
      {isWinner && <Ionicons name="checkmark-circle" size={13} color={colors.success} />}
    </View>
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
  context: {
    fontSize: 14,
    color: colors.textSecondary,
    marginBottom: 18,
  },
  walletSelectors: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 24,
    gap: 8,
  },
  walletColumn: {
    flex: 1,
    backgroundColor: colors.surface,
    borderRadius: 14,
    borderWidth: 1.5,
    borderColor: colors.border,
    padding: 14,
    alignItems: 'center',
    gap: 8,
  },
  walletLogo: {
    width: 48,
    height: 48,
    borderRadius: 24,
    alignItems: 'center',
    justifyContent: 'center',
  },
  walletLogoText: {
    color: '#ffffff',
    fontSize: 16,
    fontWeight: '800',
  },
  walletName: {
    fontSize: 14,
    fontWeight: '700',
    color: colors.textPrimary,
    textAlign: 'center',
  },
  walletPrice: {
    fontSize: 13,
    color: colors.textSecondary,
    fontWeight: '600',
  },
  changeBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: '#eff6ff',
    borderRadius: 6,
    paddingHorizontal: 8,
    paddingVertical: 3,
    marginTop: 4,
  },
  changeBadgeText: {
    fontSize: 10,
    fontWeight: '700',
    color: colors.primary,
  },
  vsContainer: {
    paddingHorizontal: 4,
  },
  vsText: {
    fontSize: 14,
    fontWeight: '800',
    color: colors.textMuted,
  },
  table: {
    backgroundColor: colors.background,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: colors.border,
    overflow: 'hidden',
    marginBottom: 20,
  },
  tableHeader: {
    flexDirection: 'row',
    backgroundColor: colors.surface,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  tableHeaderText: {
    fontSize: 11,
    fontWeight: '700',
    color: colors.textSecondary,
    textTransform: 'uppercase',
    letterSpacing: 0.5,
  },
  tableRow: {
    flexDirection: 'row',
    borderBottomWidth: 1,
    borderBottomColor: colors.divider,
  },
  tableRowLast: {
    borderBottomWidth: 0,
  },
  tableCell: {
    flex: 1,
    paddingVertical: 14,
    paddingHorizontal: 8,
    justifyContent: 'center',
  },
  tableLabel: {
    fontSize: 12,
    color: colors.textSecondary,
    fontWeight: '500',
  },
  cellValue: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 4,
  },
  cellText: {
    fontSize: 13,
    fontWeight: '600',
    color: colors.textPrimary,
    textAlign: 'center',
  },
  cellWinner: {
    backgroundColor: colors.successSoft,
  },
  cellTextWinner: {
    color: colors.success,
    fontWeight: '700',
  },
  summary: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    paddingVertical: 14,
    marginBottom: 16,
  },
  summaryText: {
    fontSize: 14,
    color: colors.textPrimary,
    textAlign: 'center',
  },
  summaryWinner: {
    fontWeight: '700',
  },
  summaryAmount: {
    fontWeight: '700',
    color: colors.success,
  },
  chooseButton: {
    borderRadius: 12,
    paddingVertical: 18,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 10,
  },
  chooseButtonText: {
    color: '#ffffff',
    fontSize: 14,
    fontWeight: '700',
    letterSpacing: 0.5,
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.4)',
    justifyContent: 'flex-end',
  },
  modalSheet: {
    backgroundColor: colors.background,
    borderTopLeftRadius: 20,
    borderTopRightRadius: 20,
    paddingHorizontal: 20,
    paddingTop: 12,
    paddingBottom: 28,
    maxHeight: '70%',
  },
  modalHandle: {
    width: 40,
    height: 4,
    borderRadius: 2,
    backgroundColor: colors.border,
    alignSelf: 'center',
    marginBottom: 16,
  },
  modalTitle: {
    fontSize: 18,
    fontWeight: '700',
    color: colors.textPrimary,
    marginBottom: 16,
  },
  modalOption: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 12,
    borderRadius: 12,
    gap: 12,
    marginBottom: 6,
    backgroundColor: colors.surface,
  },
  modalOptionDisabled: {
    opacity: 0.4,
  },
  modalLogo: {
    width: 36,
    height: 36,
    borderRadius: 18,
    alignItems: 'center',
    justifyContent: 'center',
  },
  modalLogoText: {
    color: '#ffffff',
    fontSize: 12,
    fontWeight: '800',
  },
  modalName: {
    fontSize: 14,
    fontWeight: '600',
    color: colors.textPrimary,
  },
  modalTextDisabled: {
    color: colors.textMuted,
  },
  modalPrice: {
    fontSize: 12,
    color: colors.textSecondary,
    marginTop: 2,
  },
  modalUsedTag: {
    fontSize: 11,
    color: colors.textMuted,
    fontStyle: 'italic',
  },
});
