import { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  StatusBar,
  Linking,
  Alert,
  TextInput,
  ActivityIndicator,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { getWalletByName } from '../../data/wallets';
import ExternalRedirectModal from '../../components/modals/ExternalRedirectModal';
import { useAuth } from '../../context/AuthContext';
import {
  getFavoritos,
  addFavorito,
  removeFavorito,
  getBilletera,
  getHistorialCotizaciones,
  createResena,
} from '../../services/api';

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

// Función para formatear una fecha como día/mes corto (usada en reseñas y en el gráfico de evolución)
function formatDate(raw) {
  if (!raw) return '';
  const d = new Date(raw);
  return `${d.getDate()}/${d.getMonth() + 1}`;
}

// Componente que renderiza el título de una sección de la pantalla
function SectionTitle({ text }) {
  return <Text style={styles.sectionTitle}>{text}</Text>;
}

// Componente que renderiza un rating como fila de estrellas (solo lectura)
function Stars({ rating, size = 13 }) {
  return (
    <View style={styles.starsRow}>
      {[1, 2, 3, 4, 5].map(i => (
        <Ionicons
          key={i}
          name={i <= Math.round(rating) ? 'star' : 'star-outline'}
          size={size}
          color="#f59e0b"
        />
      ))}
    </View>
  );
}

// Componente que renderiza un selector de estrellas interactivo, usado en el form de nueva reseña
function StarPicker({ value, onChange }) {
  return (
    <View style={styles.starPickerRow}>
      {[1, 2, 3, 4, 5].map(i => (
        <TouchableOpacity key={i} onPress={() => onChange(i)} activeOpacity={0.7}>
          <Ionicons
            name={i <= value ? 'star' : 'star-outline'}
            size={32}
            color="#f59e0b"
          />
        </TouchableOpacity>
      ))}
    </View>
  );
}

// Componente que renderiza una reseña individual (autor, estrellas, fecha, comentario)
function ReviewCard({ review }) {
  return (
    <View style={styles.reviewCard}>
      <View style={styles.reviewHeader}>
        <View style={styles.reviewAvatar}>
          <Text style={styles.reviewAvatarText}>{review.autor_nombre.charAt(0).toUpperCase()}</Text>
        </View>
        <View style={styles.reviewMeta}>
          <Text style={styles.reviewUser}>{review.autor_nombre}</Text>
          <Stars rating={review.calificacion} />
        </View>
        <Text style={styles.reviewDate}>{formatDate(review.fecha_resena)}</Text>
      </View>
      {!!review.comentario && (
        <Text style={styles.reviewComment}>{review.comentario}</Text>
      )}
    </View>
  );
}

// Componente que renderiza el gráfico de barras puro (sin librerías) de evolución de la tasa, con rango min/max
function TasaChart({ data }) {
  if (!data || data.length < 2) {
    return <Text style={styles.chartEmpty}>Sin datos de historial aún</Text>;
  }
  // Usa como máximo los últimos 20 registros de tasa
  const points = data.slice(-20);
  const tasas = points.map(p => parseFloat(p.tasa));
  const min = Math.min(...tasas);
  const max = Math.max(...tasas);
  const MAX_H = 64;
  const MIN_H = 8;

  return (
    <View>
      <View style={styles.chartBars}>
        {points.map((p, i) => {
          const h = max === min
            ? MAX_H
            : MIN_H + ((parseFloat(p.tasa) - min) / (max - min)) * (MAX_H - MIN_H);
          return (
            <View key={i} style={styles.chartBarWrapper}>
              <View style={[styles.chartBar, { height: h }]} />
            </View>
          );
        })}
      </View>
      <View style={styles.chartAxis} />
      <View style={styles.chartFooter}>
        <Text style={styles.chartDate}>{formatDate(points[0].registrado_en)}</Text>
        <View style={styles.chartRangeCenter}>
          <Text style={styles.chartRange}>
            {min.toFixed(2)} – {max.toFixed(2)} ARS/BRL
          </Text>
        </View>
        <Text style={styles.chartDate}>{formatDate(points[points.length - 1].registrado_en)}</Text>
      </View>
    </View>
  );
}

// Pantalla que muestra el perfil completo de una billetera: descripción, comisiones, pros/contras, requisitos, países, gráfico de evolución de tasa y reseñas
export default function WalletProfileScreen({ route, navigation }) {
  const { walletName } = route.params;
  const wallet = getWalletByName(walletName);
  const { apiToken } = useAuth();

  const [modalVisible, setModalVisible] = useState(false);
  const [isFavorite, setIsFavorite] = useState(false);
  const [apiWallet, setApiWallet] = useState(null);
  const [historial, setHistorial] = useState([]);
  const [showReviewForm, setShowReviewForm] = useState(false);
  const [reviewRating, setReviewRating] = useState(5);
  const [reviewComment, setReviewComment] = useState('');
  const [submittingReview, setSubmittingReview] = useState(false);

  // Chequea si esta billetera ya está en los favoritos del usuario logueado
  useEffect(() => {
    if (!apiToken || !wallet) return;
    getFavoritos(apiToken)
      .then(favs => setIsFavorite(favs.some(f => String(f.billetera_id) === String(wallet.id))))
      .catch(() => {});
  }, [apiToken, wallet]);

  // Carga el detalle completo (con reseñas) y el historial de tasa de la billetera desde la API
  useEffect(() => {
    if (!wallet?.id) return;
    getBilletera(wallet.id).then(setApiWallet).catch(() => {});
    getHistorialCotizaciones(wallet.id).then(setHistorial).catch(() => {});
  }, [wallet?.id]);

  // Función para refrescar el detalle de la billetera (usada tras enviar una reseña, para traer el rating actualizado)
  const reloadApiWallet = () => {
    if (!wallet?.id) return;
    getBilletera(wallet.id).then(setApiWallet).catch(() => {});
  };

  // Función para agregar o quitar la billetera de favoritos, persistiendo en la API
  const toggleFavorite = async () => {
    if (!apiToken) return;
    try {
      if (isFavorite) {
        await removeFavorito(apiToken, wallet.id);
        setIsFavorite(false);
      } else {
        await addFavorito(apiToken, wallet.id);
        setIsFavorite(true);
      }
    } catch (err) {
      Alert.alert('Error', err.message ?? 'No se pudo actualizar favoritos.');
    }
  };

  // Función para validar y enviar una reseña nueva, y refrescar el rating del hero tras guardarla
  const handleSubmitReview = async () => {
    if (!reviewComment.trim()) {
      Alert.alert('Falta el comentario', 'Escribí algo antes de enviar.');
      return;
    }
    setSubmittingReview(true);
    try {
      await createResena(apiToken, {
        billetera_id: wallet.id,
        calificacion: reviewRating,
        comentario: reviewComment.trim(),
      });
      setShowReviewForm(false);
      setReviewComment('');
      setReviewRating(5);
      reloadApiWallet();
    } catch (err) {
      Alert.alert('Error', err.message ?? 'No se pudo enviar la reseña.');
    } finally {
      setSubmittingReview(false);
    }
  };

  if (!wallet) {
    return (
      <SafeAreaView style={styles.safe}>
        <View style={styles.header}>
          <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
            <Ionicons name="arrow-back" size={24} color={colors.textPrimary} />
          </TouchableOpacity>
          <Text style={styles.headerTitle}>Perfil</Text>
          <View style={styles.headerRight} />
        </View>
        <View style={styles.notFound}>
          <Text style={styles.notFoundText}>Billetera no encontrada</Text>
        </View>
      </SafeAreaView>
    );
  }

  const displayRating = apiWallet?.rating_promedio ?? wallet.rating;
  const displayRatingCount = apiWallet?.cantidad_resenas ?? wallet.ratingCount;
  const resenas = apiWallet?.resenas ?? [];

  return (
    <SafeAreaView style={styles.safe}>
      <StatusBar barStyle="dark-content" backgroundColor={colors.background} />

      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
          <Ionicons name="arrow-back" size={24} color={colors.textPrimary} />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>{wallet.name}</Text>
        <TouchableOpacity onPress={toggleFavorite} style={styles.starBtn}>
          <Ionicons
            name={isFavorite ? 'star' : 'star-outline'}
            size={22}
            color={isFavorite ? '#f59e0b' : colors.textMuted}
          />
        </TouchableOpacity>
      </View>

      <ScrollView
        style={styles.scroll}
        contentContainerStyle={styles.scrollContent}
        showsVerticalScrollIndicator={false}
      >
        {/* Hero */}
        <View style={styles.hero}>
          <View style={[styles.heroLogo, { backgroundColor: wallet.color }]}>
            <Text style={styles.heroLogoText}>{wallet.initials}</Text>
          </View>
          <Text style={styles.heroName}>{wallet.name}</Text>
          <View style={styles.heroRatingRow}>
            <Stars rating={displayRating} />
            <Text style={styles.heroRatingText}>
              {Number(displayRating).toFixed(1)} · {Number(displayRatingCount).toLocaleString('es-AR')} opiniones
            </Text>
          </View>
        </View>

        {/* Acerca de */}
        <View style={styles.section}>
          <SectionTitle text="Acerca de" />
          <Text style={styles.bodyText}>{wallet.description}</Text>
        </View>

        <View style={styles.divider} />

        {/* Comisiones */}
        <View style={styles.section}>
          <SectionTitle text="Comisiones" />
          <View style={styles.card}>
            <View style={styles.feeRow}>
              <Text style={styles.feeLabel}>Comisión base</Text>
              <Text style={[styles.feeValue, { color: colors.success }]}>{wallet.commission}</Text>
            </View>
            <View style={styles.rowDivider} />
            <View style={styles.feeRow}>
              <Text style={styles.feeLabel}>Límite diario</Text>
              <Text style={styles.feeValue}>{wallet.dailyLimit}</Text>
            </View>
            <View style={styles.rowDivider} />
            <View style={styles.feeRow}>
              <Text style={styles.feeLabel}>Límite mensual</Text>
              <Text style={styles.feeValue}>{wallet.monthlyLimit}</Text>
            </View>
            <View style={styles.rowDivider} />
            <View style={styles.feeRow}>
              <Text style={styles.feeLabel}>Tiempo estimado</Text>
              <Text style={styles.feeValue}>{wallet.estimatedTime}</Text>
            </View>
          </View>
          <Text style={styles.feeNote}>{wallet.commissionDetail}</Text>
        </View>

        <View style={styles.divider} />

        {/* Pros y contras */}
        <View style={styles.section}>
          <SectionTitle text="Pros y contras" />
          <View style={styles.prosConsContainer}>
            <View style={styles.prosCol}>
              {wallet.pros.map((p, i) => (
                <View key={i} style={styles.bulletRow}>
                  <Ionicons name="checkmark-circle" size={16} color={colors.success} />
                  <Text style={styles.proText}>{p}</Text>
                </View>
              ))}
            </View>
            <View style={styles.prosConsGap} />
            <View style={styles.consCol}>
              {wallet.cons.map((c, i) => (
                <View key={i} style={styles.bulletRow}>
                  <Ionicons name="close-circle" size={16} color="#ef4444" />
                  <Text style={styles.conText}>{c}</Text>
                </View>
              ))}
            </View>
          </View>
        </View>

        <View style={styles.divider} />

        {/* Requisitos */}
        <View style={styles.section}>
          <SectionTitle text="Requisitos para abrir cuenta" />
          <View style={styles.card}>
            {wallet.requirements.map((req, i) => (
              <View key={i}>
                {i > 0 && <View style={styles.rowDivider} />}
                <View style={styles.requirementRow}>
                  <Ionicons name="checkmark-outline" size={16} color={colors.primary} />
                  <Text style={styles.requirementText}>{req}</Text>
                </View>
              </View>
            ))}
          </View>
        </View>

        <View style={styles.divider} />

        {/* Países */}
        <View style={styles.section}>
          <SectionTitle text="Países disponibles" />
          <View style={styles.flagsGrid}>
            {wallet.countryFlags.map((flag, i) => (
              <View key={i} style={styles.flagItem}>
                <Text style={styles.flagEmoji}>{flag}</Text>
              </View>
            ))}
          </View>
        </View>

        <View style={styles.divider} />

        {/* Evolución de la tasa */}
        <View style={styles.section}>
          <SectionTitle text="Evolución de la tasa" />
          <TasaChart data={historial} />
        </View>

        <View style={styles.divider} />

        {/* Opiniones */}
        <View style={styles.section}>
          <View style={styles.opinionesTitleRow}>
            <SectionTitle text="Opiniones de usuarios" />
            {apiToken && !showReviewForm && (
              <TouchableOpacity
                onPress={() => setShowReviewForm(true)}
                style={styles.writeReviewBtn}
                activeOpacity={0.75}
              >
                <Ionicons name="create-outline" size={14} color={colors.primary} />
                <Text style={styles.writeReviewText}>Escribir reseña</Text>
              </TouchableOpacity>
            )}
          </View>

          {showReviewForm && (
            <View style={styles.reviewForm}>
              <Text style={styles.reviewFormLabel}>Tu calificación</Text>
              <StarPicker value={reviewRating} onChange={setReviewRating} />
              <TextInput
                style={styles.reviewInput}
                placeholder="Contá tu experiencia..."
                placeholderTextColor={colors.textMuted}
                value={reviewComment}
                onChangeText={setReviewComment}
                multiline
                numberOfLines={3}
                maxLength={500}
              />
              <View style={styles.reviewFormActions}>
                <TouchableOpacity
                  onPress={() => { setShowReviewForm(false); setReviewComment(''); setReviewRating(5); }}
                  style={styles.reviewCancelBtn}
                  activeOpacity={0.75}
                >
                  <Text style={styles.reviewCancelText}>Cancelar</Text>
                </TouchableOpacity>
                <TouchableOpacity
                  onPress={handleSubmitReview}
                  style={[styles.reviewSubmitBtn, submittingReview && { opacity: 0.6 }]}
                  activeOpacity={0.75}
                  disabled={submittingReview}
                >
                  {submittingReview
                    ? <ActivityIndicator size="small" color="#fff" />
                    : <Text style={styles.reviewSubmitText}>Enviar</Text>
                  }
                </TouchableOpacity>
              </View>
            </View>
          )}

          {resenas.length === 0 ? (
            <View style={styles.emptyReviews}>
              <Ionicons name="chatbubble-outline" size={32} color={colors.textMuted} />
              <Text style={styles.emptyReviewsText}>Sé el primero en opinar</Text>
            </View>
          ) : (
            <View style={styles.reviewsList}>
              {resenas.map((review, i) => (
                <ReviewCard key={i} review={review} />
              ))}
            </View>
          )}
        </View>

        {/* Botón ir a la app */}
        <TouchableOpacity
          style={styles.goToAppBtn}
          onPress={() => setModalVisible(true)}
          activeOpacity={0.85}
        >
          <Text style={styles.goToAppText}>IR A LA APP DE {wallet.name.toUpperCase()}</Text>
          <Ionicons name="arrow-forward" size={18} color="#ffffff" />
        </TouchableOpacity>
      </ScrollView>

      <ExternalRedirectModal
        visible={modalVisible}
        walletName={wallet.name}
        onCancel={() => setModalVisible(false)}
        onConfirm={async () => {
          setModalVisible(false);
          if (wallet.appUrl) {
            try { await Linking.openURL(wallet.appUrl); } catch { /* silent */ }
          }
        }}
      />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: colors.surface },
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
  backBtn: { width: 36, height: 36, alignItems: 'center', justifyContent: 'center' },
  headerTitle: { fontSize: 18, fontWeight: '700', color: colors.textPrimary },
  headerRight: { width: 36 },
  starBtn: { width: 36, height: 36, alignItems: 'center', justifyContent: 'center' },
  scroll: { flex: 1 },
  scrollContent: { paddingBottom: 32 },
  hero: {
    alignItems: 'center',
    paddingVertical: 28,
    backgroundColor: colors.background,
    gap: 8,
  },
  heroLogo: {
    width: 80,
    height: 80,
    borderRadius: 20,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 4,
  },
  heroLogoText: { color: '#ffffff', fontSize: 28, fontWeight: '800', letterSpacing: 1 },
  heroName: { fontSize: 24, fontWeight: '700', color: colors.textPrimary },
  heroRatingRow: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  starsRow: { flexDirection: 'row', gap: 2 },
  heroRatingText: { fontSize: 13, color: colors.textSecondary },
  divider: { height: 8, backgroundColor: colors.surface },
  section: {
    paddingHorizontal: 20,
    paddingVertical: 20,
    backgroundColor: colors.background,
    gap: 12,
  },
  sectionTitle: { fontSize: 16, fontWeight: '700', color: colors.textPrimary },
  bodyText: { fontSize: 15, color: colors.textSecondary, lineHeight: 22 },
  card: {
    backgroundColor: colors.surface,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: colors.border,
    overflow: 'hidden',
  },
  feeRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingVertical: 13,
  },
  feeLabel: { fontSize: 15, color: colors.textPrimary },
  feeValue: { fontSize: 15, fontWeight: '700', color: colors.textPrimary },
  rowDivider: { height: 1, backgroundColor: colors.divider, marginHorizontal: 16 },
  feeNote: { fontSize: 13, color: colors.textMuted, lineHeight: 18 },
  prosConsContainer: { flexDirection: 'row', gap: 12 },
  prosCol: { flex: 1, gap: 8 },
  consCol: { flex: 1, gap: 8 },
  prosConsGap: { width: 1, backgroundColor: colors.divider },
  bulletRow: { flexDirection: 'row', alignItems: 'flex-start', gap: 6 },
  proText: { flex: 1, fontSize: 13, color: colors.textPrimary, lineHeight: 18 },
  conText: { flex: 1, fontSize: 13, color: colors.textPrimary, lineHeight: 18 },
  requirementRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    paddingHorizontal: 16,
    paddingVertical: 12,
  },
  requirementText: { flex: 1, fontSize: 14, color: colors.textPrimary },
  flagsGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 10 },
  flagItem: {
    width: 44,
    height: 44,
    borderRadius: 10,
    backgroundColor: colors.surface,
    borderWidth: 1,
    borderColor: colors.border,
    alignItems: 'center',
    justifyContent: 'center',
  },
  flagEmoji: { fontSize: 22 },
  chartBars: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    height: 72,
    gap: 3,
    paddingHorizontal: 4,
  },
  chartBarWrapper: { flex: 1, alignItems: 'center', justifyContent: 'flex-end' },
  chartBar: { width: '100%', backgroundColor: colors.primary, borderRadius: 3, opacity: 0.8 },
  chartAxis: { height: 1, backgroundColor: colors.divider, marginTop: 4 },
  chartFooter: { flexDirection: 'row', alignItems: 'center', marginTop: 6 },
  chartDate: { fontSize: 11, color: colors.textMuted },
  chartRangeCenter: { flex: 1, alignItems: 'center' },
  chartRange: { fontSize: 11, color: colors.textSecondary, fontWeight: '600' },
  chartEmpty: { fontSize: 14, color: colors.textMuted, textAlign: 'center', paddingVertical: 16 },
  opinionesTitleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  writeReviewBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    paddingHorizontal: 10,
    paddingVertical: 5,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: colors.primary,
  },
  writeReviewText: { fontSize: 12, fontWeight: '600', color: colors.primary },
  reviewForm: {
    backgroundColor: colors.surface,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: colors.border,
    padding: 14,
    gap: 10,
  },
  reviewFormLabel: { fontSize: 13, fontWeight: '600', color: colors.textPrimary },
  starPickerRow: { flexDirection: 'row', gap: 8 },
  reviewInput: {
    backgroundColor: colors.background,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: colors.border,
    paddingHorizontal: 12,
    paddingVertical: 10,
    fontSize: 14,
    color: colors.textPrimary,
    minHeight: 72,
    textAlignVertical: 'top',
  },
  reviewFormActions: { flexDirection: 'row', gap: 8, justifyContent: 'flex-end' },
  reviewCancelBtn: {
    paddingHorizontal: 14,
    paddingVertical: 8,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: colors.border,
  },
  reviewCancelText: { fontSize: 13, fontWeight: '600', color: colors.textSecondary },
  reviewSubmitBtn: {
    paddingHorizontal: 18,
    paddingVertical: 8,
    borderRadius: 8,
    backgroundColor: colors.primary,
    minWidth: 72,
    alignItems: 'center',
  },
  reviewSubmitText: { fontSize: 13, fontWeight: '700', color: '#ffffff' },
  reviewsList: { gap: 10 },
  reviewCard: {
    backgroundColor: colors.surface,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: colors.border,
    padding: 14,
    gap: 8,
  },
  reviewHeader: { flexDirection: 'row', alignItems: 'center', gap: 10 },
  reviewAvatar: {
    width: 32,
    height: 32,
    borderRadius: 16,
    backgroundColor: colors.primary,
    alignItems: 'center',
    justifyContent: 'center',
  },
  reviewAvatarText: { fontSize: 14, fontWeight: '700', color: '#ffffff' },
  reviewMeta: { flex: 1, gap: 2 },
  reviewUser: { fontSize: 14, fontWeight: '600', color: colors.textPrimary },
  reviewDate: { fontSize: 12, color: colors.textMuted },
  reviewComment: { fontSize: 14, color: colors.textSecondary, lineHeight: 20 },
  emptyReviews: { alignItems: 'center', paddingVertical: 20, gap: 8 },
  emptyReviewsText: { fontSize: 14, color: colors.textMuted },
  goToAppBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    backgroundColor: colors.primary,
    borderRadius: 12,
    marginHorizontal: 20,
    marginTop: 24,
    paddingVertical: 16,
  },
  goToAppText: { fontSize: 14, fontWeight: '700', color: '#ffffff', letterSpacing: 0.5 },
  notFound: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  notFoundText: { fontSize: 16, color: colors.textSecondary },
});
