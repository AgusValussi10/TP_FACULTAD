import { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  Modal,
  FlatList,
  StatusBar,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { WALLETS, getWalletByName } from '../data/wallets';

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

const COMPARE_ROWS = [
  { key: 'commission',     label: 'Comisión',         icon: 'pricetag-outline'    },
  { key: 'dailyLimit',     label: 'Límite diario',    icon: 'trending-up-outline' },
  { key: 'monthlyLimit',   label: 'Límite mensual',   icon: 'calendar-outline'    },
  { key: 'estimatedTime',  label: 'Tiempo',           icon: 'time-outline'        },
  { key: 'currencies',     label: 'Monedas',          icon: 'cash-outline'        },
  { key: 'rating',         label: 'Rating',           icon: 'star-outline'        },
];

function formatCellValue(key, wallet) {
  if (key === 'currencies') return wallet.currencies.join(' · ');
  if (key === 'rating') return `${wallet.rating.toFixed(1)} ★`;
  return wallet[key];
}

function WalletSelector({ label, walletName, onPress }) {
  const wallet = walletName ? getWalletByName(walletName) : null;

  return (
    <TouchableOpacity style={styles.selector} onPress={onPress} activeOpacity={0.75}>
      {wallet ? (
        <>
          <View style={[styles.selectorLogo, { backgroundColor: wallet.color }]}>
            <Text style={styles.selectorLogoText}>{wallet.initials}</Text>
          </View>
          <Text style={styles.selectorName} numberOfLines={1}>{wallet.name}</Text>
          <Ionicons name="chevron-down" size={16} color={colors.textSecondary} />
        </>
      ) : (
        <>
          <View style={styles.selectorEmpty}>
            <Ionicons name="add" size={20} color={colors.textMuted} />
          </View>
          <Text style={styles.selectorPlaceholder}>{label}</Text>
          <Ionicons name="chevron-down" size={16} color={colors.textMuted} />
        </>
      )}
    </TouchableOpacity>
  );
}

function PickerModal({ visible, onClose, onSelect, excludeName }) {
  const available = WALLETS.filter(w => w.name !== excludeName);

  return (
    <Modal visible={visible} animationType="slide" transparent onRequestClose={onClose}>
      <View style={styles.modalOverlay}>
        <View style={styles.modalSheet}>
          <View style={styles.modalHeader}>
            <Text style={styles.modalTitle}>Elegir billetera</Text>
            <TouchableOpacity onPress={onClose} style={styles.modalClose}>
              <Ionicons name="close" size={22} color={colors.textPrimary} />
            </TouchableOpacity>
          </View>
          <FlatList
            data={available}
            keyExtractor={item => item.id}
            showsVerticalScrollIndicator={false}
            ItemSeparatorComponent={() => <View style={styles.pickerSeparator} />}
            renderItem={({ item }) => (
              <TouchableOpacity
                style={styles.pickerItem}
                onPress={() => { onSelect(item.name); onClose(); }}
                activeOpacity={0.7}
              >
                <View style={[styles.pickerLogo, { backgroundColor: item.color }]}>
                  <Text style={styles.pickerLogoText}>{item.initials}</Text>
                </View>
                <View style={styles.pickerInfo}>
                  <Text style={styles.pickerName}>{item.name}</Text>
                  <Text style={styles.pickerSub}>
                    {item.commission} comisión · {item.dailyLimit}/día
                  </Text>
                </View>
                <Text style={styles.pickerRating}>{item.rating.toFixed(1)} ★</Text>
              </TouchableOpacity>
            )}
          />
        </View>
      </View>
    </Modal>
  );
}

export default function WalletCompareScreen({ navigation }) {
  const [walletA, setWalletA] = useState(null);
  const [walletB, setWalletB] = useState(null);
  const [pickerTarget, setPickerTarget] = useState(null);

  const wA = walletA ? getWalletByName(walletA) : null;
  const wB = walletB ? getWalletByName(walletB) : null;
  const canCompare = wA && wB;

  const openPicker = (target) => setPickerTarget(target);
  const closePicker = () => setPickerTarget(null);
  const handleSelect = (name) => {
    if (pickerTarget === 'A') setWalletA(name);
    else setWalletB(name);
  };

  const handleCompare = () => {
    navigation.navigate('Results', { amount: 500, currency: 'BRL', country: 'Brasil' });
  };

  return (
    <SafeAreaView style={styles.safe}>
      <StatusBar barStyle="dark-content" backgroundColor={colors.background} />

      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
          <Ionicons name="arrow-back" size={24} color={colors.textPrimary} />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Comparar billeteras</Text>
        <View style={styles.headerRight} />
      </View>

      <ScrollView
        style={styles.scroll}
        contentContainerStyle={styles.scrollContent}
        showsVerticalScrollIndicator={false}
      >
        {/* Selectores */}
        <View style={styles.selectorsSection}>
          <Text style={styles.selectorsLabel}>Seleccioná dos billeteras para comparar</Text>
          <View style={styles.selectorsRow}>
            <View style={styles.selectorWrapper}>
              <Text style={styles.selectorTag}>A</Text>
              <WalletSelector
                label="Billetera A"
                walletName={walletA}
                onPress={() => openPicker('A')}
              />
            </View>

            <View style={styles.vsCircle}>
              <Text style={styles.vsText}>VS</Text>
            </View>

            <View style={styles.selectorWrapper}>
              <Text style={styles.selectorTag}>B</Text>
              <WalletSelector
                label="Billetera B"
                walletName={walletB}
                onPress={() => openPicker('B')}
              />
            </View>
          </View>
        </View>

        {/* Tabla de comparación */}
        {canCompare ? (
          <View style={styles.tableSection}>
            <Text style={styles.tableTitle}>Comparación</Text>

            {/* Encabezado */}
            <View style={styles.tableHeader}>
              <View style={styles.tableColLabel} />
              <View style={[styles.tableColA, { backgroundColor: wA.color + '22' }]}>
                <View style={[styles.tableLogoSmall, { backgroundColor: wA.color }]}>
                  <Text style={styles.tableLogoText}>{wA.initials}</Text>
                </View>
                <Text style={styles.tableHeaderName} numberOfLines={1}>{wA.name}</Text>
              </View>
              <View style={[styles.tableColB, { backgroundColor: wB.color + '22' }]}>
                <View style={[styles.tableLogoSmall, { backgroundColor: wB.color }]}>
                  <Text style={styles.tableLogoText}>{wB.initials}</Text>
                </View>
                <Text style={styles.tableHeaderName} numberOfLines={1}>{wB.name}</Text>
              </View>
            </View>

            {/* Filas */}
            {COMPARE_ROWS.map((row, index) => {
              const valA = formatCellValue(row.key, wA);
              const valB = formatCellValue(row.key, wB);
              return (
                <View
                  key={row.key}
                  style={[styles.tableRow, index % 2 === 1 && styles.tableRowAlt]}
                >
                  <View style={styles.tableColLabel}>
                    <Ionicons name={row.icon} size={14} color={colors.textMuted} />
                    <Text style={styles.tableRowLabel}>{row.label}</Text>
                  </View>
                  <View style={styles.tableColA}>
                    <Text style={styles.tableCell}>{valA}</Text>
                  </View>
                  <View style={styles.tableColB}>
                    <Text style={styles.tableCell}>{valB}</Text>
                  </View>
                </View>
              );
            })}
          </View>
        ) : (
          <View style={styles.emptyState}>
            <Ionicons name="git-compare-outline" size={56} color={colors.textMuted} />
            <Text style={styles.emptyTitle}>Elegí dos billeteras</Text>
            <Text style={styles.emptySubtitle}>
              Seleccioná las billeteras que querés comparar y vas a ver todas las diferencias de un vistazo.
            </Text>
          </View>
        )}
      </ScrollView>

      {/* Botón comparar */}
      <View style={styles.footer}>
        <TouchableOpacity
          style={[styles.compareBtn, !canCompare && styles.compareBtnDisabled]}
          onPress={handleCompare}
          disabled={!canCompare}
          activeOpacity={0.85}
        >
          <Ionicons name="bar-chart-outline" size={18} color="#ffffff" />
          <Text style={styles.compareBtnText}>COMPARAR EN RESULTADOS</Text>
        </TouchableOpacity>
      </View>

      <PickerModal
        visible={pickerTarget !== null}
        onClose={closePicker}
        onSelect={handleSelect}
        excludeName={pickerTarget === 'A' ? walletB : walletA}
      />
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
    paddingBottom: 20,
  },
  selectorsSection: {
    backgroundColor: colors.background,
    paddingHorizontal: 20,
    paddingVertical: 20,
    gap: 16,
    borderBottomWidth: 1,
    borderBottomColor: colors.divider,
  },
  selectorsLabel: {
    fontSize: 14,
    color: colors.textSecondary,
    textAlign: 'center',
  },
  selectorsRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  selectorWrapper: {
    flex: 1,
    gap: 6,
  },
  selectorTag: {
    fontSize: 11,
    fontWeight: '700',
    color: colors.textSecondary,
    textTransform: 'uppercase',
    letterSpacing: 1,
    textAlign: 'center',
  },
  selector: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    backgroundColor: colors.surface,
    borderRadius: 14,
    borderWidth: 1.5,
    borderColor: colors.border,
    padding: 12,
  },
  selectorLogo: {
    width: 36,
    height: 36,
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
  },
  selectorLogoText: {
    color: '#ffffff',
    fontSize: 12,
    fontWeight: '800',
  },
  selectorName: {
    flex: 1,
    fontSize: 14,
    fontWeight: '600',
    color: colors.textPrimary,
  },
  selectorEmpty: {
    width: 36,
    height: 36,
    borderRadius: 10,
    backgroundColor: colors.border,
    alignItems: 'center',
    justifyContent: 'center',
  },
  selectorPlaceholder: {
    flex: 1,
    fontSize: 14,
    color: colors.textMuted,
  },
  vsCircle: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: colors.surface,
    borderWidth: 1.5,
    borderColor: colors.border,
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 20,
  },
  vsText: {
    fontSize: 11,
    fontWeight: '800',
    color: colors.textSecondary,
  },
  tableSection: {
    paddingHorizontal: 20,
    paddingTop: 20,
    gap: 0,
  },
  tableTitle: {
    fontSize: 16,
    fontWeight: '700',
    color: colors.textPrimary,
    marginBottom: 12,
  },
  tableHeader: {
    flexDirection: 'row',
    borderRadius: 12,
    overflow: 'hidden',
    marginBottom: 2,
  },
  tableColLabel: {
    flex: 1.2,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    paddingHorizontal: 10,
    paddingVertical: 12,
  },
  tableColA: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 6,
    paddingVertical: 12,
    gap: 4,
  },
  tableColB: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 6,
    paddingVertical: 12,
    gap: 4,
  },
  tableLogoSmall: {
    width: 28,
    height: 28,
    borderRadius: 7,
    alignItems: 'center',
    justifyContent: 'center',
  },
  tableLogoText: {
    color: '#ffffff',
    fontSize: 10,
    fontWeight: '800',
  },
  tableHeaderName: {
    fontSize: 11,
    fontWeight: '700',
    color: colors.textPrimary,
    textAlign: 'center',
  },
  tableRow: {
    flexDirection: 'row',
    borderRadius: 8,
  },
  tableRowAlt: {
    backgroundColor: colors.surface,
  },
  tableRowLabel: {
    fontSize: 13,
    color: colors.textSecondary,
  },
  tableCell: {
    fontSize: 13,
    fontWeight: '600',
    color: colors.textPrimary,
    textAlign: 'center',
  },
  emptyState: {
    alignItems: 'center',
    paddingTop: 60,
    paddingHorizontal: 40,
    gap: 12,
  },
  emptyTitle: {
    fontSize: 18,
    fontWeight: '700',
    color: colors.textPrimary,
  },
  emptySubtitle: {
    fontSize: 14,
    color: colors.textSecondary,
    textAlign: 'center',
    lineHeight: 20,
  },
  footer: {
    paddingHorizontal: 20,
    paddingVertical: 16,
    backgroundColor: colors.background,
    borderTopWidth: 1,
    borderTopColor: colors.divider,
  },
  compareBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    backgroundColor: colors.primary,
    borderRadius: 12,
    paddingVertical: 15,
  },
  compareBtnDisabled: {
    opacity: 0.4,
  },
  compareBtnText: {
    fontSize: 14,
    fontWeight: '700',
    color: '#ffffff',
    letterSpacing: 0.5,
  },
  // Modal
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.45)',
    justifyContent: 'flex-end',
  },
  modalSheet: {
    backgroundColor: colors.background,
    borderTopLeftRadius: 20,
    borderTopRightRadius: 20,
    maxHeight: '75%',
    paddingBottom: 24,
  },
  modalHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 20,
    paddingVertical: 16,
    borderBottomWidth: 1,
    borderBottomColor: colors.divider,
  },
  modalTitle: {
    fontSize: 17,
    fontWeight: '700',
    color: colors.textPrimary,
  },
  modalClose: {
    padding: 4,
  },
  pickerSeparator: {
    height: 1,
    backgroundColor: colors.divider,
    marginLeft: 72,
  },
  pickerItem: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 20,
    paddingVertical: 14,
    gap: 14,
  },
  pickerLogo: {
    width: 40,
    height: 40,
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
  },
  pickerLogoText: {
    color: '#ffffff',
    fontSize: 13,
    fontWeight: '800',
  },
  pickerInfo: {
    flex: 1,
    gap: 3,
  },
  pickerName: {
    fontSize: 15,
    fontWeight: '700',
    color: colors.textPrimary,
  },
  pickerSub: {
    fontSize: 12,
    color: colors.textSecondary,
  },
  pickerRating: {
    fontSize: 13,
    fontWeight: '600',
    color: '#f59e0b',
  },
});
