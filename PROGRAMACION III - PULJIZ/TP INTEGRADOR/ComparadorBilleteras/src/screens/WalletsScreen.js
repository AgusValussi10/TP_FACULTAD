import { useState } from 'react';
import { View, Text, StyleSheet, ScrollView, TouchableOpacity, TextInput, StatusBar } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { WALLET_META } from '../data/wallets';
import BottomNav from '../components/BottomNav';

const colors = {
  primary: '#3b82f6',
  background: '#ffffff',
  surface: '#f8f9fa',
  border: '#dee2e6',
  textPrimary: '#1a1a1a',
  textSecondary: '#6c757d',
  textMuted: '#adb5bd',
  divider: '#e0e0e0',
};

const RATINGS = {
  'Mercado Pago': 4.8,
  'Ualá': 4.6,
  'Bimo': 4.3,
  'Prex': 4.1,
  'Naranja X': 4.4,
  'Brubank': 4.5,
  'Personal Pay': 4.2,
  'Lemon Cash': 4.7,
  'Modo': 4.0,
  'Cuenta DNI': 3.9,
};

export default function WalletsScreen({ navigation }) {
  const [searchText, setSearchText] = useState('');

  const wallets = Object.entries(WALLET_META).filter(([name]) =>
    name.toLowerCase().includes(searchText.toLowerCase())
  );

  return (
    <SafeAreaView style={styles.safe}>
      <StatusBar barStyle="dark-content" />
      <View style={styles.header}>
        <Text style={styles.headerTitle}>Billeteras</Text>
      </View>

      <View style={styles.searchContainer}>
        <Ionicons name="search" size={18} color={colors.textMuted} style={styles.searchIcon} />
        <TextInput
          style={styles.searchInput}
          placeholder="Buscar billetera..."
          placeholderTextColor={colors.textMuted}
          value={searchText}
          onChangeText={setSearchText}
        />
      </View>

      <ScrollView style={styles.scroll} contentContainerStyle={styles.scrollContent}>
        {wallets.map(([name, meta]) => (
          <View key={name} style={styles.card}>
            <View style={styles.cardRow}>
              <View style={[styles.logo, { backgroundColor: meta.color }]}>
                <Text style={styles.logoText}>{meta.initials}</Text>
              </View>
              <View style={styles.cardInfo}>
                <Text style={styles.walletName}>{name}</Text>
                <View style={styles.metaRow}>
                  <Text style={styles.countries}>🇦🇷 🇧🇷</Text>
                  <Text style={styles.rating}>⭐ {RATINGS[name] ?? 4.0}</Text>
                </View>
              </View>
              <TouchableOpacity
                style={styles.profileBtn}
                onPress={() => navigation.navigate('WalletProfile', { walletName: name })}
                activeOpacity={0.7}
              >
                <Text style={styles.profileBtnText}>Ver perfil</Text>
                <Ionicons name="chevron-forward" size={14} color={colors.primary} />
              </TouchableOpacity>
            </View>
          </View>
        ))}
      </ScrollView>

      <BottomNav active="Wallets" navigation={navigation} />
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
    justifyContent: 'center',
    paddingHorizontal: 20,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  headerTitle: {
    fontSize: 24,
    fontWeight: '700',
    color: colors.textPrimary,
  },
  searchContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    margin: 16,
    backgroundColor: colors.surface,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: colors.border,
    paddingHorizontal: 12,
  },
  searchIcon: {
    marginRight: 8,
  },
  searchInput: {
    flex: 1,
    height: 44,
    fontSize: 15,
    color: colors.textPrimary,
  },
  scroll: {
    flex: 1,
  },
  scrollContent: {
    paddingHorizontal: 16,
    paddingBottom: 16,
    gap: 12,
  },
  card: {
    backgroundColor: colors.background,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: colors.border,
    padding: 16,
  },
  cardRow: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  logo: {
    width: 48,
    height: 48,
    borderRadius: 24,
    justifyContent: 'center',
    alignItems: 'center',
  },
  logoText: {
    color: '#ffffff',
    fontSize: 14,
    fontWeight: '700',
  },
  cardInfo: {
    flex: 1,
    marginLeft: 12,
  },
  walletName: {
    fontSize: 16,
    fontWeight: '700',
    color: colors.textPrimary,
  },
  metaRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    marginTop: 4,
  },
  countries: {
    fontSize: 14,
  },
  rating: {
    fontSize: 13,
    color: colors.textSecondary,
  },
  profileBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 2,
  },
  profileBtnText: {
    fontSize: 13,
    fontWeight: '600',
    color: colors.primary,
  },
});
