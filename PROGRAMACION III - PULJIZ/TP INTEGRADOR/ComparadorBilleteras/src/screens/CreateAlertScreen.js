import { useState } from 'react';
import { View, Text, StyleSheet, ScrollView, TouchableOpacity, TextInput, StatusBar, Modal, Alert } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { WALLET_META, getWalletMeta } from '../data/wallets';

const colors = {
  primary: '#3b82f6',
  primaryDark: '#2563eb',
  background: '#ffffff',
  surface: '#f8f9fa',
  border: '#dee2e6',
  borderFocus: '#3b82f6',
  textPrimary: '#1a1a1a',
  textSecondary: '#6c757d',
  textMuted: '#adb5bd',
  divider: '#e0e0e0',
};

const WALLET_NAMES = Object.keys(WALLET_META);

export default function CreateAlertScreen({ navigation }) {
  const [selectedWallet, setSelectedWallet] = useState(WALLET_NAMES[0]);
  const [conditionType, setConditionType] = useState('supere');
  const [targetValue, setTargetValue] = useState('');
  const [pickerVisible, setPickerVisible] = useState(false);

  const conditionLabel = conditionType === 'supere' ? 'supere' : 'baje de';
  const previewText =
    selectedWallet && targetValue
      ? `Te avisaremos cuando ${selectedWallet} ${conditionLabel} $ ${targetValue} ARS por BRL`
      : 'Completá el formulario para ver una vista previa';

  const handleSave = () => {
    if (!targetValue) {
      Alert.alert('Valor requerido', 'Ingresá el valor objetivo de la alerta.');
      return;
    }
    Alert.alert('Alerta guardada', `Tu alerta para ${selectedWallet} fue creada.`, [
      { text: 'OK', onPress: () => navigation.goBack() },
    ]);
  };

  return (
    <SafeAreaView style={styles.safe}>
      <StatusBar barStyle="dark-content" />
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()} activeOpacity={0.7}>
          <Ionicons name="close" size={24} color={colors.textPrimary} />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Nueva alerta</Text>
        <View style={{ width: 24 }} />
      </View>

      <ScrollView style={styles.scroll} contentContainerStyle={styles.scrollContent}>
        {/* Selector billetera */}
        <Text style={styles.fieldLabel}>Billetera</Text>
        <TouchableOpacity
          style={styles.selector}
          onPress={() => setPickerVisible(true)}
          activeOpacity={0.7}
        >
          <View style={styles.selectorLeft}>
            <View style={[styles.selectorLogo, { backgroundColor: getWalletMeta(selectedWallet).color }]}>
              <Text style={styles.selectorLogoText}>{getWalletMeta(selectedWallet).initials}</Text>
            </View>
            <Text style={styles.selectorText}>{selectedWallet}</Text>
          </View>
          <Ionicons name="chevron-down" size={18} color={colors.textSecondary} />
        </TouchableOpacity>

        {/* Par monedas */}
        <Text style={styles.fieldLabel}>Par de monedas</Text>
        <View style={styles.fixedField}>
          <Text style={styles.fixedFieldText}>🇦🇷 ARS / 🇧🇷 BRL</Text>
        </View>

        {/* Tipo condición */}
        <Text style={styles.fieldLabel}>Condición</Text>
        <View style={styles.radioGroup}>
          {[
            { value: 'supere', label: 'Cuando supere...' },
            { value: 'baje', label: 'Cuando baje de...' },
          ].map((opt) => (
            <TouchableOpacity
              key={opt.value}
              style={[styles.radioOption, conditionType === opt.value && styles.radioOptionActive]}
              onPress={() => setConditionType(opt.value)}
              activeOpacity={0.7}
            >
              <View style={[styles.radioCircle, conditionType === opt.value && styles.radioCircleActive]}>
                {conditionType === opt.value && <View style={styles.radioInner} />}
              </View>
              <Text style={[styles.radioLabel, conditionType === opt.value && styles.radioLabelActive]}>
                {opt.label}
              </Text>
            </TouchableOpacity>
          ))}
        </View>

        {/* Valor objetivo */}
        <Text style={styles.fieldLabel}>Valor objetivo</Text>
        <View style={styles.inputRow}>
          <Text style={styles.inputPrefix}>$</Text>
          <TextInput
            style={styles.valueInput}
            placeholder="975,00"
            placeholderTextColor={colors.textMuted}
            keyboardType="numeric"
            value={targetValue}
            onChangeText={setTargetValue}
          />
          <Text style={styles.inputSuffix}>ARS</Text>
        </View>

        {/* Preview */}
        <View style={styles.preview}>
          <Ionicons name="notifications-outline" size={18} color={colors.primary} style={{ marginTop: 1 }} />
          <Text style={styles.previewText}>{previewText}</Text>
        </View>
      </ScrollView>

      <View style={styles.footer}>
        <TouchableOpacity style={styles.saveButton} onPress={handleSave} activeOpacity={0.7}>
          <Text style={styles.saveButtonText}>GUARDAR ALERTA</Text>
        </TouchableOpacity>
      </View>

      {/* Modal selector billetera */}
      <Modal
        visible={pickerVisible}
        transparent
        animationType="slide"
        onRequestClose={() => setPickerVisible(false)}
      >
        <TouchableOpacity
          style={styles.overlay}
          activeOpacity={1}
          onPress={() => setPickerVisible(false)}
        />
        <View style={styles.pickerSheet}>
          <View style={styles.pickerHandle} />
          <Text style={styles.pickerTitle}>Elegí una billetera</Text>
          <ScrollView>
            {WALLET_NAMES.map((name) => {
              const m = getWalletMeta(name);
              return (
                <TouchableOpacity
                  key={name}
                  style={[styles.pickerItem, name === selectedWallet && styles.pickerItemActive]}
                  onPress={() => { setSelectedWallet(name); setPickerVisible(false); }}
                  activeOpacity={0.7}
                >
                  <View style={[styles.pickerLogo, { backgroundColor: m.color }]}>
                    <Text style={styles.pickerLogoText}>{m.initials}</Text>
                  </View>
                  <Text style={[styles.pickerItemText, name === selectedWallet && styles.pickerItemTextActive]}>
                    {name}
                  </Text>
                  {name === selectedWallet && (
                    <Ionicons name="checkmark" size={18} color={colors.primary} />
                  )}
                </TouchableOpacity>
              );
            })}
          </ScrollView>
        </View>
      </Modal>
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
    padding: 20,
    gap: 8,
    paddingBottom: 32,
  },
  fieldLabel: {
    fontSize: 14,
    fontWeight: '600',
    color: colors.textSecondary,
    marginTop: 12,
    marginBottom: 4,
  },
  selector: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: colors.surface,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: colors.border,
    padding: 14,
  },
  selectorLeft: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  selectorLogo: {
    width: 32,
    height: 32,
    borderRadius: 16,
    justifyContent: 'center',
    alignItems: 'center',
  },
  selectorLogoText: {
    color: '#ffffff',
    fontSize: 10,
    fontWeight: '700',
  },
  selectorText: {
    fontSize: 15,
    fontWeight: '600',
    color: colors.textPrimary,
  },
  fixedField: {
    backgroundColor: colors.surface,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: colors.border,
    padding: 14,
  },
  fixedFieldText: {
    fontSize: 15,
    color: colors.textSecondary,
  },
  radioGroup: {
    gap: 8,
  },
  radioOption: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: colors.border,
    padding: 14,
    gap: 10,
  },
  radioOptionActive: {
    borderColor: colors.primary,
    backgroundColor: '#eff6ff',
  },
  radioCircle: {
    width: 18,
    height: 18,
    borderRadius: 9,
    borderWidth: 2,
    borderColor: colors.border,
    justifyContent: 'center',
    alignItems: 'center',
  },
  radioCircleActive: {
    borderColor: colors.primary,
  },
  radioInner: {
    width: 8,
    height: 8,
    borderRadius: 4,
    backgroundColor: colors.primary,
  },
  radioLabel: {
    fontSize: 15,
    color: colors.textSecondary,
  },
  radioLabelActive: {
    color: colors.textPrimary,
    fontWeight: '600',
  },
  inputRow: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: colors.border,
    paddingHorizontal: 14,
  },
  inputPrefix: {
    fontSize: 18,
    color: colors.textSecondary,
    marginRight: 6,
  },
  valueInput: {
    flex: 1,
    height: 52,
    fontSize: 22,
    fontWeight: '700',
    color: colors.textPrimary,
  },
  inputSuffix: {
    fontSize: 14,
    color: colors.textMuted,
    fontWeight: '600',
  },
  preview: {
    flexDirection: 'row',
    gap: 10,
    backgroundColor: '#eff6ff',
    borderRadius: 12,
    borderWidth: 1,
    borderColor: '#bfdbfe',
    padding: 14,
    marginTop: 8,
  },
  previewText: {
    flex: 1,
    fontSize: 14,
    color: colors.textPrimary,
    lineHeight: 20,
  },
  footer: {
    padding: 16,
    borderTopWidth: 1,
    borderTopColor: colors.divider,
    backgroundColor: colors.background,
  },
  saveButton: {
    backgroundColor: colors.primary,
    borderRadius: 12,
    paddingVertical: 16,
    alignItems: 'center',
  },
  saveButtonText: {
    color: '#ffffff',
    fontSize: 16,
    fontWeight: '700',
    letterSpacing: 0.5,
  },
  overlay: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.4)',
  },
  pickerSheet: {
    backgroundColor: colors.background,
    borderTopLeftRadius: 20,
    borderTopRightRadius: 20,
    maxHeight: '60%',
    paddingBottom: 24,
  },
  pickerHandle: {
    width: 36,
    height: 4,
    backgroundColor: colors.border,
    borderRadius: 2,
    alignSelf: 'center',
    marginTop: 12,
    marginBottom: 8,
  },
  pickerTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: colors.textPrimary,
    paddingHorizontal: 20,
    paddingVertical: 12,
    borderBottomWidth: 1,
    borderBottomColor: colors.divider,
  },
  pickerItem: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 20,
    paddingVertical: 14,
    gap: 12,
    borderBottomWidth: 1,
    borderBottomColor: colors.divider,
  },
  pickerItemActive: {
    backgroundColor: '#eff6ff',
  },
  pickerLogo: {
    width: 36,
    height: 36,
    borderRadius: 18,
    justifyContent: 'center',
    alignItems: 'center',
  },
  pickerLogoText: {
    color: '#ffffff',
    fontSize: 10,
    fontWeight: '700',
  },
  pickerItemText: {
    flex: 1,
    fontSize: 15,
    color: colors.textPrimary,
  },
  pickerItemTextActive: {
    fontWeight: '600',
    color: colors.primary,
  },
});
