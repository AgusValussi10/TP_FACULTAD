import { useState, useRef } from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  TouchableOpacity,
  StatusBar,
  Dimensions,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';

const { width: SCREEN_WIDTH, height: SCREEN_HEIGHT } = Dimensions.get('window');

const SLIDES = [
  {
    id: '1',
    icon: 'swap-horizontal-outline',
    color: '#3b82f6',
    bgColor: '#eff6ff',
    label: '13 billeteras',
    title: 'Compará billeteras\nen segundos',
    subtitle:
      'Mercado Pago, Ualá, Naranja X y más. Encontrá cuál te da más BRL por tus pesos argentinos.',
  },
  {
    id: '2',
    icon: 'trending-up-outline',
    color: '#10b981',
    bgColor: '#ecfdf5',
    label: 'Tiempo real',
    title: 'Cotizaciones\nsiempre al día',
    subtitle:
      'Tasas ARS → BRL actualizadas para que no pierdas ni un centavo en tu viaje a Brasil.',
  },
  {
    id: '3',
    icon: 'notifications-outline',
    color: '#f59e0b',
    bgColor: '#fffbeb',
    label: 'Alertas de precio',
    title: 'Tu precio objetivo,\ntu momento ideal',
    subtitle:
      'Configurá una alerta y te avisamos cuando la cotización llegue al valor que querés.',
  },
];

// Pantalla que muestra el carrusel de onboarding de 3 slides antes del login
export default function OnboardingScreen({ navigation }) {
  const [currentIndex, setCurrentIndex] = useState(0);
  const flatListRef = useRef(null);

  // Función para avanzar al siguiente slide, o ir al login si ya estamos en el último
  const handleNext = () => {
    if (currentIndex < SLIDES.length - 1) {
      const nextIndex = currentIndex + 1;
      setCurrentIndex(nextIndex);
      flatListRef.current?.scrollToOffset({
        offset: SCREEN_WIDTH * nextIndex,
        animated: true,
      });
    } else {
      navigation.replace('Login');
    }
  };

  // Función para saltear el onboarding directo al login
  const handleSkip = () => {
    navigation.replace('Login');
  };

  // Sincroniza el índice actual con el slide visible al terminar el swipe manual
  const onMomentumScrollEnd = (event) => {
    const index = Math.round(event.nativeEvent.contentOffset.x / SCREEN_WIDTH);
    if (index !== currentIndex) setCurrentIndex(index);
  };

  // Función que renderiza cada slide del carrusel (icono + badge + título + subtítulo)
  const renderSlide = ({ item }) => (
    <View style={styles.slide}>
      <View style={[styles.illustrationArea, { backgroundColor: item.bgColor }]}>
        <View style={[styles.iconCard, { backgroundColor: item.color }]}>
          <Ionicons name={item.icon} size={68} color="#ffffff" />
        </View>
      </View>
      <View style={styles.textArea}>
        <View style={[styles.badge, { backgroundColor: item.color + '18' }]}>
          <Text style={[styles.badgeText, { color: item.color }]}>{item.label}</Text>
        </View>
        <Text style={styles.slideTitle}>{item.title}</Text>
        <Text style={styles.slideSubtitle}>{item.subtitle}</Text>
      </View>
    </View>
  );

  const isLast = currentIndex === SLIDES.length - 1;
  const currentColor = SLIDES[currentIndex].color;

  return (
    <SafeAreaView style={styles.safe}>
      <StatusBar barStyle="dark-content" backgroundColor="#ffffff" />

      <FlatList
        ref={flatListRef}
        data={SLIDES}
        renderItem={renderSlide}
        keyExtractor={(item) => item.id}
        horizontal
        pagingEnabled
        showsHorizontalScrollIndicator={false}
        onMomentumScrollEnd={onMomentumScrollEnd}
        getItemLayout={(_, index) => ({
          length: SCREEN_WIDTH,
          offset: SCREEN_WIDTH * index,
          index,
        })}
      />

      <View style={styles.footer}>
        <View style={styles.dots}>
          {SLIDES.map((_, i) => (
            <View
              key={i}
              style={[
                styles.dot,
                i === currentIndex && [styles.dotActive, { backgroundColor: currentColor }],
              ]}
            />
          ))}
        </View>

        <View style={styles.footerActions}>
          {!isLast ? (
            <TouchableOpacity onPress={handleSkip} style={styles.skipButton}>
              <Text style={styles.skipText}>Omitir</Text>
            </TouchableOpacity>
          ) : (
            <View style={{ flex: 1 }} />
          )}

          <TouchableOpacity
            style={[
              styles.nextButton,
              { backgroundColor: currentColor },
              isLast && styles.startButton,
            ]}
            onPress={handleNext}
            activeOpacity={0.85}
          >
            <Text style={styles.nextButtonText}>{isLast ? 'COMENZAR' : 'Siguiente'}</Text>
            {!isLast && <Ionicons name="arrow-forward" size={18} color="#ffffff" />}
          </TouchableOpacity>
        </View>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: {
    flex: 1,
    backgroundColor: '#ffffff',
  },
  slide: {
    width: SCREEN_WIDTH,
    flex: 1,
  },
  illustrationArea: {
    height: SCREEN_HEIGHT * 0.42,
    alignItems: 'center',
    justifyContent: 'center',
  },
  iconCard: {
    width: 160,
    height: 160,
    borderRadius: 40,
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.15,
    shadowRadius: 20,
    elevation: 12,
  },
  textArea: {
    flex: 1,
    paddingHorizontal: 28,
    paddingTop: 32,
  },
  badge: {
    alignSelf: 'flex-start',
    paddingHorizontal: 12,
    paddingVertical: 5,
    borderRadius: 20,
    marginBottom: 16,
  },
  badgeText: {
    fontSize: 11,
    fontWeight: '700',
    letterSpacing: 0.8,
    textTransform: 'uppercase',
  },
  slideTitle: {
    fontSize: 30,
    fontWeight: '800',
    color: '#1a1a1a',
    marginBottom: 12,
    lineHeight: 36,
  },
  slideSubtitle: {
    fontSize: 15,
    color: '#6c757d',
    lineHeight: 23,
  },
  footer: {
    paddingHorizontal: 20,
    paddingBottom: 24,
    paddingTop: 12,
    backgroundColor: '#ffffff',
  },
  dots: {
    flexDirection: 'row',
    gap: 6,
    marginBottom: 20,
  },
  dot: {
    width: 8,
    height: 8,
    borderRadius: 4,
    backgroundColor: '#dee2e6',
  },
  dotActive: {
    width: 24,
  },
  footerActions: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  skipButton: {
    padding: 12,
  },
  skipText: {
    fontSize: 15,
    color: '#6c757d',
    fontWeight: '500',
  },
  nextButton: {
    borderRadius: 14,
    paddingVertical: 15,
    paddingHorizontal: 28,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  startButton: {
    flex: 1,
    justifyContent: 'center',
  },
  nextButtonText: {
    color: '#ffffff',
    fontSize: 15,
    fontWeight: '700',
    letterSpacing: 0.5,
  },
});
