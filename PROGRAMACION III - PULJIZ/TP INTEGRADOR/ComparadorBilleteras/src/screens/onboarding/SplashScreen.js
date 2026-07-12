import { useEffect, useRef, useState } from 'react';
import { View, Text, StyleSheet, Animated, StatusBar, Image } from 'react-native';
import { useAuth } from '../../context/AuthContext';

const colors = {
  primary: '#3b82f6',
  white: '#ffffff',
  whiteSoft: 'rgba(255,255,255,0.8)',
};

export default function SplashScreen({ navigation }) {
  const { user, loading } = useAuth();
  const [timerDone, setTimerDone] = useState(false);

  const logoScale = useRef(new Animated.Value(0.8)).current;
  const logoOpacity = useRef(new Animated.Value(0)).current;
  const dot1 = useRef(new Animated.Value(0.3)).current;
  const dot2 = useRef(new Animated.Value(0.3)).current;
  const dot3 = useRef(new Animated.Value(0.3)).current;

  useEffect(() => {
    Animated.parallel([
      Animated.timing(logoOpacity, { toValue: 1, duration: 600, useNativeDriver: true }),
      Animated.spring(logoScale, { toValue: 1, friction: 5, useNativeDriver: true }),
    ]).start();

    const animateDot = (dot, delay) =>
      Animated.loop(
        Animated.sequence([
          Animated.delay(delay),
          Animated.timing(dot, { toValue: 1, duration: 400, useNativeDriver: true }),
          Animated.timing(dot, { toValue: 0.3, duration: 400, useNativeDriver: true }),
        ])
      ).start();

    animateDot(dot1, 0);
    animateDot(dot2, 200);
    animateDot(dot3, 400);

    const timer = setTimeout(() => setTimerDone(true), 2500);
    return () => clearTimeout(timer);
  }, []);

  useEffect(() => {
    if (!timerDone || loading) return;
    if (user) {
      navigation.replace('Home');
    } else {
      navigation.replace('Onboarding');
    }
  }, [timerDone, loading, user]);

  return (
    <View style={styles.container}>
      <StatusBar barStyle="light-content" backgroundColor={colors.primary} />

      <Animated.View
        style={[styles.logoContainer, { opacity: logoOpacity, transform: [{ scale: logoScale }] }]}
      >
        <Image
          source={require('../../../assets/icon.jpg')}
          style={styles.logoImage}
        />
        <Text style={styles.name}>BrasilPagos</Text>
        <Text style={styles.tagline}>Comparador de billeteras para Brasil</Text>
      </Animated.View>

      <View style={styles.dotsContainer}>
        <Animated.View style={[styles.dot, { opacity: dot1 }]} />
        <Animated.View style={[styles.dot, { opacity: dot2 }]} />
        <Animated.View style={[styles.dot, { opacity: dot3 }]} />
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.primary,
    alignItems: 'center',
    justifyContent: 'center',
  },
  logoContainer: {
    alignItems: 'center',
  },
  logoImage: {
    width: 130,
    height: 130,
    borderRadius: 30,
    marginBottom: 20,
  },
  name: {
    fontSize: 30,
    fontWeight: '800',
    color: colors.white,
    marginBottom: 6,
    letterSpacing: 0.5,
  },
  tagline: {
    fontSize: 14,
    color: colors.whiteSoft,
    letterSpacing: 0.3,
  },
  dotsContainer: {
    position: 'absolute',
    bottom: 80,
    flexDirection: 'row',
    gap: 8,
  },
  dot: {
    width: 8,
    height: 8,
    borderRadius: 4,
    backgroundColor: colors.white,
  },
});
