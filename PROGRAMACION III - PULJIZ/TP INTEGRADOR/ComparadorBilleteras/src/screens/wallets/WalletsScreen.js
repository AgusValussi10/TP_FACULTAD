import { useState, useMemo } from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  TextInput,
  TouchableOpacity,
  StatusBar,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { WALLETS } from '../../data/wallets';
import BottomNav from '../../components/BottomNav';

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

function Stars({ rating }) {
  return (
    <View style={styles.starsRow}>
      {[1, 2, 3, 4, 5].map(i => (
        <Ionicons
          key={i}
          name={i <= Math.round(rating) ? 'star' : 'star-outline'}
          size={12}
          color="#f59e0b"
        />
      ))}
      <Text style={styles.ratingText}>{rating.toFixed(1)}</Text>
    </View>
  );
}

function WalletItem({ wallet, onPress }) {
  return (
    <TouchableOpacity style={styles.item} onPress={onPress} activeOpacity={0.7}>
      <View style={[styles.logo, { backgroundColor: wallet.color }]}>
        <Text style={styles.logoText}>{wallet.initials}</Text>
      </View>

      <View style={styles.itemInfo}>
        <Text style={styles.itemName}>{wallet.name}</Text>
        <Stars rating={wallet.rating} />
        <View style={styles.flagsRow}>
          {wallet.countryFlags.slice(0, 4).map((flag, i) => (
            <Text key={i} style={styles.flag}>{flag}</Text>
          ))}
          {wallet.countryFlags.length > 4 && (
            <Text style={styles.moreFlags}>+{wallet.countryFlags.length - 4}</Text>
          )}
        </View>
      </View>

      <View style={styles.itemRight}>
        <View style={styles.currenciesCol}>
          {wallet.currencies.slice(0, 2).map(c => (
            <View key={c} style={styles.currencyBadge}>
              <Text style={styles.currencyText}>{c}</Text>
            </View>
          ))}
        </View>
        <Ionicons name="chevron-forward" size={16} color={colors.textMuted} />
      </View>
    </TouchableOpacity>
  );
}

export default function WalletsScreen({ navigation }) {
  const [query, setQuery] = useState('');
  const [searchFocused, setSearchFocused] = useState(false);

  const filtered = useMemo(() => {
    const q = query.trim().toLowerCase();
    if (!q) return WALLETS;
    return WALLETS.filter(w => w.name.toLowerCase().includes(q));
  }, [query]);

  return (
    <SafeAreaView style={styles.safe}>
      <StatusBar barStyle="dark-content" backgroundColor={colors.background} />

      <View style={styles.header}>
        <Text style={styles.headerTitle}>Billeteras</Text>
        <TouchableOpacity
          style={styles.compareBtn}
          onPress={() => navigation.navigate('WalletCompare')}
          activeOpacity={0.7}
        >
          <Ionicons name="git-compare-outline" size={20} color={colors.primary} />
        </TouchableOpacity>
      </View>

      <View style={styles.searchWrapper}>
        <View style={[styles.searchBar, searchFocused && styles.searchBarFocused]}>
          <Ionicons
            name="search-outline"
            size={18}
            color={searchFocused ? colors.primary : colors.textMuted}
          />
          <TextInput
            style={styles.searchInput}
            placeholder="Buscar billetera..."
            placeholderTextColor={colors.textMuted}
            value={query}
            onChangeText={setQuery}
            onFocus={() => setSearchFocused(true)}
            onBlur={() => setSearchFocused(false)}
            returnKeyType="search"
            autoCorrect={false}
          />
          {query.length > 0 && (
            <TouchableOpacity onPress={() => setQuery('')}>
              <Ionicons name="close-circle" size={18} color={colors.textMuted} />
            </TouchableOpacity>
          )}
        </View>
      </View>

      <FlatList
        data={filtered}
        keyExtractor={item => item.id}
        renderItem={({ item }) => (
          <WalletItem
            wallet={item}
            onPress={() => navigation.navigate('WalletDetail', { walletName: item.name })}
          />
        )}
        ItemSeparatorComponent={() => <View style={styles.separator} />}
        contentContainerStyle={styles.list}
        showsVerticalScrollIndicator={false}
        ListEmptyComponent={
          <View style={styles.empty}>
            <Ionicons name="search-outline" size={48} color={colors.textMuted} />
            <Text style={styles.emptyText}>Sin resultados para "{query}"</Text>
          </View>
        }
      />

      <BottomNav active="Wallets" navigation={navigation} />
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
  headerTitle: {
    fontSize: 18,
    fontWeight: '700',
    color: colors.textPrimary,
  },
  compareBtn: {
    padding: 6,
  },
  searchWrapper: {
    paddingHorizontal: 20,
    paddingVertical: 12,
    backgroundColor: colors.background,
    borderBottomWidth: 1,
    borderBottomColor: colors.divider,
  },
  searchBar: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    backgroundColor: colors.surface,
    borderRadius: 14,
    borderWidth: 1.5,
    borderColor: colors.border,
    paddingHorizontal: 14,
    height: 44,
  },
  searchBarFocused: {
    borderColor: colors.borderFocus,
    backgroundColor: colors.background,
  },
  searchInput: {
    flex: 1,
    fontSize: 15,
    color: colors.textPrimary,
  },
  list: {
    paddingHorizontal: 20,
    paddingTop: 12,
    paddingBottom: 16,
  },
  separator: {
    height: 1,
    backgroundColor: colors.divider,
    marginLeft: 76,
  },
  item: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 14,
    gap: 14,
    backgroundColor: colors.background,
  },
  logo: {
    width: 48,
    height: 48,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  logoText: {
    color: '#ffffff',
    fontSize: 14,
    fontWeight: '800',
    letterSpacing: 0.5,
  },
  itemInfo: {
    flex: 1,
    gap: 4,
  },
  itemName: {
    fontSize: 16,
    fontWeight: '700',
    color: colors.textPrimary,
  },
  starsRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 2,
  },
  ratingText: {
    fontSize: 12,
    color: colors.textSecondary,
    marginLeft: 4,
  },
  flagsRow: {
    flexDirection: 'row',
    gap: 2,
    marginTop: 2,
  },
  flag: {
    fontSize: 14,
  },
  moreFlags: {
    fontSize: 11,
    color: colors.textMuted,
    alignSelf: 'center',
  },
  itemRight: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  currenciesCol: {
    gap: 4,
    alignItems: 'flex-end',
  },
  currencyBadge: {
    backgroundColor: '#eff6ff',
    borderRadius: 6,
    paddingHorizontal: 7,
    paddingVertical: 2,
  },
  currencyText: {
    fontSize: 11,
    fontWeight: '700',
    color: colors.primary,
  },
  empty: {
    alignItems: 'center',
    paddingTop: 60,
    gap: 12,
  },
  emptyText: {
    fontSize: 15,
    color: colors.textSecondary,
  },
});
