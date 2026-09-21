import { useRef, useEffect } from 'react';
import { View, TouchableOpacity, StyleSheet, Animated, Easing } from 'react-native';
import { Ionicons } from '@expo/vector-icons';

const TABS = [
  { iconOutline: 'card-outline',               iconFilled: 'card',               screen: 'Wallets' },
  { iconOutline: 'time-outline',               iconFilled: 'time',               screen: 'History' },
  { iconOutline: 'stats-chart-outline',        iconFilled: 'stats-chart',        screen: 'Home'    },
  { iconOutline: 'notifications-outline',      iconFilled: 'notifications',      screen: 'Alerts'  },
  { iconOutline: 'information-circle-outline', iconFilled: 'information-circle', screen: 'Profile' },
];

const colors = {
  primary:      '#3b82f6',
  primaryLight: '#eff6ff',
  textMuted:    '#adb5bd',
  divider:      '#e0e0e0',
  background:   '#ffffff',
};

// Componente que renderiza un ícono de tab individual, con animación de "pop" al activarse
function TabItem({ tab, isActive, onPress }) {
  const scale = useRef(new Animated.Value(1)).current;

  // Anima un pequeño rebote de escala cada vez que este tab pasa a estar activo
  useEffect(() => {
    if (isActive) {
      Animated.sequence([
        Animated.timing(scale, { toValue: 1.15, duration: 130, easing: Easing.out(Easing.ease), useNativeDriver: true }),
        Animated.timing(scale, { toValue: 1,    duration: 180, easing: Easing.inOut(Easing.ease), useNativeDriver: true }),
      ]).start();
    }
  }, [isActive]);

  return (
    <TouchableOpacity style={styles.tab} onPress={onPress} activeOpacity={0.7}>
      <Animated.View
        style={[
          styles.iconWrap,
          isActive && styles.iconWrapActive,
          { transform: [{ scale }] },
        ]}
      >
        <Ionicons
          name={isActive ? tab.iconFilled : tab.iconOutline}
          size={22}
          color={isActive ? colors.primary : colors.textMuted}
        />
      </Animated.View>
    </TouchableOpacity>
  );
}

// Componente de barra de navegación inferior con 5 tabs y pill activo animado
export default function BottomNav({ active, navigation }) {
  return (
    <View style={styles.container}>
      {TABS.map((tab) => (
        <TabItem
          key={tab.screen}
          tab={tab}
          isActive={tab.screen === active}
          onPress={() => tab.screen && tab.screen !== active && navigation.navigate(tab.screen)}
        />
      ))}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    height: 70,
    flexDirection: 'row',
    borderTopWidth: 1,
    borderTopColor: colors.divider,
    backgroundColor: colors.background,
  },
  tab: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  iconWrap: {
    width: 48,
    height: 36,
    borderRadius: 18,
    alignItems: 'center',
    justifyContent: 'center',
  },
  iconWrapActive: {
    backgroundColor: colors.primaryLight,
  },
});
