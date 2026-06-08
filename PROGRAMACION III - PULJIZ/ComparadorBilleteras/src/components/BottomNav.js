import { View, TouchableOpacity, StyleSheet } from 'react-native';
import { Ionicons } from '@expo/vector-icons';

const TABS = [
  { icon: 'stats-chart',              screen: 'Home'    },
  { icon: 'card-outline',             screen: null      },
  { icon: 'time-outline',             screen: 'History' },
  { icon: 'notifications-outline',    screen: null      },
  { icon: 'information-circle-outline', screen: null    },
];

const colors = {
  primary: '#3b82f6',
  textMuted: '#adb5bd',
  divider: '#e0e0e0',
  background: '#ffffff',
};

export default function BottomNav({ active, navigation }) {
  return (
    <View style={styles.container}>
      {TABS.map((tab) => {
        const isActive = tab.screen === active;
        return (
          <TouchableOpacity
            key={tab.icon}
            style={styles.tab}
            onPress={() => tab.screen && tab.screen !== active && navigation.navigate(tab.screen)}
            activeOpacity={tab.screen ? 0.7 : 1}
          >
            <Ionicons
              name={tab.icon}
              size={22}
              color={isActive ? colors.primary : colors.textMuted}
            />
          </TouchableOpacity>
        );
      })}
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
});
