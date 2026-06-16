import { useState, useRef } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TextInput,
  TouchableOpacity,
  Modal,
  Animated,
  StatusBar,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import BottomNav from '../components/BottomNav';

const colors = {
  primary: '#3b82f6',
  primaryDark: '#2563eb',
  success: '#10b981',
  background: '#ffffff',
  surface: '#f8f9fa',
  border: '#dee2e6',
  borderFocus: '#3b82f6',
  textPrimary: '#1a1a1a',
  textSecondary: '#6c757d',
  textMuted: '#adb5bd',
  divider: '#e0e0e0',
};

const COUNTRIES = [
  { id: 'br', flag: '🇧🇷', name: 'Brasil', method: 'PIX', currency: 'BRL', prefix: 'R$', popular: true, subtitle: 'Transferencia instantánea' },
  { id: 'us', flag: '🇺🇸', name: 'USA', method: 'USD', currency: 'USD', prefix: 'USD', popular: false, subtitle: 'Dólar estadounidense' },
  { id: 'eu', flag: '🇪🇺', name: 'Europa', method: 'EUR', currency: 'EUR', prefix: '€', popular: false, subtitle: 'Euro zona' },
];

const RECENT_QUERIES = [
  { id: '1', text: '500 BRL - Hace 2hs' },
  { id: '2', text: '1000 BRL - Ayer' },
  { id: '3', text: '250 BRL - Hace 3 días' },
];

const BOTTOM_TABS = [
  { icon: 'stats-chart', label: 'Comparar', active: true },
  { icon: 'card-outline', label: 'Billeteras', active: false },
  { icon: 'trending-up-outline', label: 'Historial', active: false },
  { icon: 'notifications-outline', label: 'Alertas', active: false },
  { icon: 'information-circle-outline', label: 'Info', active: false },
];

function formatAmount(val, prefix) {
  const num = parseFloat(val);
  if (isNaN(num)) return '';
  return `${prefix} ${num.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

export default function HomeScreen({ navigation }) {
  const [selectedCountry, setSelectedCountry] = useState(COUNTRIES[0]);
  const [amount, setAmount] = useState('');
  const [countryModalVisible, setCountryModalVisible] = useState(false);
  const [inputFocused, setInputFocused] = useState(false);
  const buttonScale = useRef(new Animated.Value(1)).current;

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
        currency: selectedCountry.currency,
        country: selectedCountry.name,
      });
    });
  };

  const handleCountrySelect = (country) => {
    setSelectedCountry(country);
    setAmount('');
    setCountryModalVisible(false);
  };

  return (
    <SafeAreaView style={styles.safe}>
      <StatusBar barStyle="dark-content" backgroundColor={colors.background} />

      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity style={styles.headerIcon}>
          <Ionicons name="menu-outline" size={26} color={colors.textPrimary} />
        </TouchableOpacity>
        <TouchableOpacity style={styles.headerIcon}>
          <Ionicons name="home" size={26} color={colors.primary} />
        </TouchableOpacity>
        <TouchableOpacity style={styles.headerIcon}>
          <Ionicons name="person-circle-outline" size={26} color={colors.textPrimary} />
        </TouchableOpacity>
      </View>

      {/* Contenido scrollable */}
      <ScrollView
        style={styles.scroll}
        contentContainerStyle={styles.scrollContent}
        keyboardShouldPersistTaps="handled"
      >
        <Text style={styles.title}>Comparador de{'\n'}Cotizaciones</Text>

        {/* Selector de país */}
        <TouchableOpacity
          style={[styles.countrySelector, selectedCountry.popular && styles.countrySelectorPopular]}
          onPress={() => setCountryModalVisible(true)}
          activeOpacity={0.7}
        >
          <Text style={styles.countryFlag}>{selectedCountry.flag}</Text>
          <View style={styles.countrySelectorInfo}>
            <View style={styles.countrySelectorRow}>
              <Text style={styles.countryName}>
                {selectedCountry.name} — {selectedCountry.method}
              </Text>
              {selectedCountry.popular && (
                <View style={styles.popularBadge}>
                  <Text style={styles.popularBadgeText}>Popular</Text>
                </View>
              )}
            </View>
            <Text style={styles.countrySubtitle}>{selectedCountry.subtitle}</Text>
          </View>
          <Ionicons name="chevron-down" size={18} color={colors.textSecondary} />
        </TouchableOpacity>

        {/* Input de monto */}
        <Text style={styles.label}>¿Cuánto vas a pagar?</Text>
        <View style={[styles.amountContainer, inputFocused && styles.amountContainerFocused]}>
          <Text style={styles.currencyPrefix}>{selectedCountry.prefix}</Text>
          <TextInput
            style={styles.amountInput}
            value={amount}
            onChangeText={(t) => setAmount(t.replace(/[^0-9.]/g, ''))}
            keyboardType="numeric"
            placeholder="500,00"
            placeholderTextColor={colors.textMuted}
            onFocus={() => setInputFocused(true)}
            onBlur={() => setInputFocused(false)}
            underlineColorAndroid="transparent"
          />
        </View>

        {/* Botón comparar */}
        <Animated.View style={{ transform: [{ scale: buttonScale }] }}>
          <TouchableOpacity
            style={[styles.button, !amount && styles.buttonDisabled]}
            onPress={handleCompare}
            activeOpacity={1}
          >
            <Text style={styles.buttonText}>COMPARAR AHORA</Text>
          </TouchableOpacity>
        </Animated.View>

        {/* Divider */}
        <View style={styles.divider} />

        {/* Últimas consultas */}
        <Text style={styles.sectionTitle}>Últimas consultas</Text>
        {RECENT_QUERIES.map((item) => (
          <View key={item.id} style={styles.recentItem}>
            <View style={styles.bullet} />
            <Text style={styles.recentText}>{item.text}</Text>
          </View>
        ))}
      </ScrollView>

      <BottomNav active="Home" navigation={navigation} />

      {/* Modal selector de país */}
      <Modal
        visible={countryModalVisible}
        transparent
        animationType="slide"
        onRequestClose={() => setCountryModalVisible(false)}
      >
        <TouchableOpacity
          style={styles.modalOverlay}
          activeOpacity={1}
          onPress={() => setCountryModalVisible(false)}
        >
          <View style={styles.modalSheet}>
            <Text style={styles.modalTitle}>Seleccioná el destino</Text>
            {COUNTRIES.map((country) => (
              <TouchableOpacity
                key={country.id}
                style={[
                  styles.modalOption,
                  selectedCountry.id === country.id && styles.modalOptionSelected,
                  country.popular && styles.modalOptionPopular,
                ]}
                onPress={() => handleCountrySelect(country)}
              >
                <Text style={styles.modalFlag}>{country.flag}</Text>
                <View style={{ flex: 1 }}>
                  <View style={styles.modalOptionRow}>
                    <Text style={styles.modalOptionText}>
                      {country.name} — {country.method}
                    </Text>
                    {country.popular && (
                      <View style={styles.popularBadge}>
                        <Text style={styles.popularBadgeText}>Popular</Text>
                      </View>
                    )}
                  </View>
                  <Text style={styles.modalOptionSubtitle}>{country.subtitle}</Text>
                </View>
                {selectedCountry.id === country.id && (
                  <Ionicons name="checkmark" size={20} color={colors.primary} />
                )}
              </TouchableOpacity>
            ))}
          </View>
        </TouchableOpacity>
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
    borderBottomColor: colors.divider,
  },
  headerIcon: {
    padding: 4,
  },
  scroll: {
    flex: 1,
  },
  scrollContent: {
    paddingHorizontal: 20,
    paddingTop: 28,
    paddingBottom: 24,
  },
  title: {
    fontSize: 32,
    fontWeight: '700',
    color: colors.textPrimary,
    marginBottom: 28,
    lineHeight: 40,
  },
  countrySelector: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: colors.border,
    padding: 16,
    marginBottom: 24,
    gap: 10,
  },
  countrySelectorPopular: {
    borderColor: '#009c3b',
    backgroundColor: '#f0fdf4',
  },
  countrySelectorInfo: {
    flex: 1,
  },
  countrySelectorRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  countryFlag: {
    fontSize: 22,
  },
  countryName: {
    fontSize: 16,
    fontWeight: '600',
    color: colors.textPrimary,
  },
  countrySubtitle: {
    fontSize: 12,
    color: colors.textSecondary,
    marginTop: 2,
  },
  popularBadge: {
    backgroundColor: '#009c3b',
    borderRadius: 6,
    paddingHorizontal: 7,
    paddingVertical: 2,
  },
  popularBadgeText: {
    fontSize: 11,
    fontWeight: '700',
    color: '#ffffff',
  },
  label: {
    fontSize: 14,
    color: colors.textSecondary,
    marginBottom: 10,
    fontWeight: '500',
  },
  amountContainer: {
    backgroundColor: colors.surface,
    borderRadius: 16,
    borderWidth: 1.5,
    borderColor: colors.border,
    paddingHorizontal: 20,
    paddingVertical: 16,
    marginBottom: 20,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  amountContainerFocused: {
    borderColor: colors.borderFocus,
  },
  currencyPrefix: {
    fontSize: 28,
    fontWeight: '700',
    color: colors.textSecondary,
  },
  amountInput: {
    flex: 1,
    fontSize: 32,
    fontWeight: '700',
    color: colors.textPrimary,
    letterSpacing: -1,
    padding: 0,
  },
  button: {
    backgroundColor: colors.primary,
    borderRadius: 12,
    paddingVertical: 18,
    alignItems: 'center',
    marginBottom: 24,
  },
  buttonDisabled: {
    backgroundColor: colors.textMuted,
  },
  buttonText: {
    color: '#ffffff',
    fontSize: 16,
    fontWeight: '700',
    letterSpacing: 1,
  },
  divider: {
    height: 1,
    backgroundColor: colors.divider,
    marginBottom: 20,
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: colors.textPrimary,
    marginBottom: 14,
  },
  recentItem: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 12,
    gap: 10,
  },
  bullet: {
    width: 8,
    height: 8,
    borderRadius: 4,
    backgroundColor: colors.primary,
  },
  recentText: {
    fontSize: 14,
    color: colors.textSecondary,
  },
  bottomNav: {
    height: 70,
    flexDirection: 'row',
    borderTopWidth: 1,
    borderTopColor: colors.divider,
    backgroundColor: colors.background,
  },
  bottomTab: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
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
    padding: 24,
    paddingBottom: 36,
  },
  modalTitle: {
    fontSize: 18,
    fontWeight: '700',
    color: colors.textPrimary,
    marginBottom: 20,
  },
  modalOption: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 16,
    borderRadius: 12,
    gap: 12,
    marginBottom: 8,
  },
  modalOptionSelected: {
    backgroundColor: colors.surface,
    borderWidth: 1,
    borderColor: colors.primary,
  },
  modalFlag: {
    fontSize: 24,
  },
  modalOptionRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  modalOptionText: {
    fontSize: 16,
    fontWeight: '500',
    color: colors.textPrimary,
  },
  modalOptionSubtitle: {
    fontSize: 12,
    color: colors.textSecondary,
    marginTop: 2,
  },
  modalOptionPopular: {
    borderWidth: 1,
    borderColor: '#009c3b',
    backgroundColor: '#f0fdf4',
  },
});
