import { useEffect, useRef } from 'react';
import { View, Text, StyleSheet, TouchableOpacity, StatusBar, Animated } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';

const colors = {
  primary: '#3b82f6',
  background: '#ffffff',
  border: '#dee2e6',
  textPrimary: '#1a1a1a',
  skeleton: '#e0e0e0',
};

// Componente que renderiza un placeholder animado (parpadeo continuo) para simular contenido cargando
function Skeleton({ width, height, style }) {
  const opacity = useRef(new Animated.Value(1)).current;

  // Anima la opacidad en loop para el efecto de "shimmer" del skeleton
  useEffect(() => {
    Animated.loop(
      Animated.sequence([
        Animated.timing(opacity, { toValue: 0.3, duration: 600, useNativeDriver: true }),
        Animated.timing(opacity, { toValue: 1, duration: 600, useNativeDriver: true }),
      ])
    ).start();
  }, []);

  return (
    <Animated.View
      style={[
        { width, height, backgroundColor: colors.skeleton, borderRadius: 6 },
        { opacity },
        style,
      ]}
    />
  );
}

// Pantalla que muestra skeleton loaders mientras se cargan los resultados de cotizaciones
export default function LoadingResultsScreen({ navigation }) {
  return (
    <SafeAreaView style={styles.safe}>
      <StatusBar barStyle="dark-content" />
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()} activeOpacity={0.7}>
          <Ionicons name="arrow-back" size={24} color={colors.textPrimary} />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Resultados</Text>
        <Ionicons name="git-compare-outline" size={22} color={colors.textPrimary} />
      </View>

      <View style={styles.scroll}>
        <Skeleton width={120} height={14} style={{ marginBottom: 20 }} />

        <View style={styles.card}>
          <Skeleton width={60} height={20} style={{ marginBottom: 12 }} />
          <View style={styles.row}>
            <Skeleton width={40} height={40} style={{ borderRadius: 20 }} />
            <Skeleton width={100} height={16} style={{ marginLeft: 12 }} />
          </View>
          <Skeleton width={200} height={32} style={{ marginTop: 12 }} />
          <Skeleton width={150} height={14} style={{ marginTop: 8 }} />
        </View>

        {[0, 1].map((i) => (
          <View key={i} style={[styles.card, styles.cardSmall]}>
            <View style={styles.row}>
              <Skeleton width={40} height={40} style={{ borderRadius: 20 }} />
              <View style={{ marginLeft: 12, gap: 8 }}>
                <Skeleton width={100} height={14} />
                <Skeleton width={160} height={22} />
              </View>
            </View>
          </View>
        ))}
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: colors.background },
  header: {
    height: 64,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 20,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  headerTitle: { fontSize: 18, fontWeight: '600', color: colors.textPrimary },
  scroll: { flex: 1, padding: 20 },
  card: {
    backgroundColor: '#fff',
    borderRadius: 16,
    borderWidth: 1,
    borderColor: colors.border,
    padding: 20,
    marginBottom: 16,
  },
  cardSmall: { paddingVertical: 16 },
  row: { flexDirection: 'row', alignItems: 'center' },
});
