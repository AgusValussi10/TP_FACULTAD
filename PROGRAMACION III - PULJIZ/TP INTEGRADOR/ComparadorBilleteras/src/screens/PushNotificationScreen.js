import { View, Text, StyleSheet, TouchableOpacity, StatusBar } from 'react-native';
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

export default function PushNotificationScreen({ navigation }) {
  return (
    <SafeAreaView style={styles.safe}>
      <StatusBar barStyle="light-content" backgroundColor="#1a1a2e" />

      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
          <Ionicons name="arrow-back" size={22} color="#ffffff" />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Vista previa de notificación</Text>
        <View style={styles.headerSpacer} />
      </View>

      <View style={styles.lockedScreen}>
        <View style={styles.timeContainer}>
          <Text style={styles.time}>09:41</Text>
          <Text style={styles.date}>lunes, 10 de junio</Text>
        </View>

        <View style={styles.notificationBanner}>
          <View style={styles.bannerHeader}>
            <View style={styles.appIconContainer}>
              <Ionicons name="card" size={14} color={colors.primary} />
            </View>
            <Text style={styles.appName}>ComparaBilleteras</Text>
            <Text style={styles.timestamp}>hace un momento</Text>
          </View>
          <Text style={styles.bannerBody}>
            {'💱 Mercado Pago superó tu objetivo:\n$ 978 ARS/BRL — ¡Es un buen momento!'}
          </Text>
        </View>

        <View style={styles.footer}>
          <Text style={styles.footerText}>Deslizá para abrir la app</Text>
          <Ionicons name="chevron-up" size={16} color="rgba(255,255,255,0.6)" />
        </View>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: {
    flex: 1,
    backgroundColor: '#1a1a2e',
  },
  header: {
    height: 64,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 20,
  },
  backBtn: {
    padding: 4,
    width: 36,
  },
  headerTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: '#ffffff',
  },
  headerSpacer: {
    width: 36,
  },
  lockedScreen: {
    flex: 1,
    alignItems: 'center',
    paddingTop: 40,
    paddingHorizontal: 20,
  },
  timeContainer: {
    alignItems: 'center',
    marginBottom: 48,
  },
  time: {
    fontSize: 72,
    fontWeight: '200',
    color: '#ffffff',
    letterSpacing: -2,
  },
  date: {
    fontSize: 16,
    color: 'rgba(255,255,255,0.8)',
    marginTop: 4,
  },
  notificationBanner: {
    width: '100%',
    backgroundColor: 'rgba(255,255,255,0.95)',
    borderRadius: 14,
    padding: 14,
    gap: 6,
  },
  bannerHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  appIconContainer: {
    width: 22,
    height: 22,
    borderRadius: 6,
    backgroundColor: '#eff6ff',
    alignItems: 'center',
    justifyContent: 'center',
  },
  appName: {
    fontSize: 13,
    fontWeight: '700',
    color: colors.textPrimary,
    flex: 1,
  },
  timestamp: {
    fontSize: 12,
    color: colors.textMuted,
  },
  bannerBody: {
    fontSize: 13,
    color: colors.textPrimary,
    lineHeight: 18,
  },
  footer: {
    position: 'absolute',
    bottom: 40,
    alignItems: 'center',
    gap: 6,
  },
  footerText: {
    fontSize: 14,
    color: 'rgba(255,255,255,0.6)',
  },
});
