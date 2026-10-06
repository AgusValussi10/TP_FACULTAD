import AsyncStorage from '@react-native-async-storage/async-storage';
import { useCallback, useState } from 'react';
import { useFocusEffect } from '@react-navigation/native';
import {
  ActivityIndicator,
  Alert,
  FlatList,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { SafeAreaView } from 'react-native-safe-area-context';
import { categoriasService } from '../services/api';

const NAVY = '#1C2E4A';

const formatCuota = (value) =>
  `$ ${Number(value).toLocaleString('es-AR', { minimumFractionDigits: 0 })}`;

export default function CategoriasScreen({ navigation }) {
  const [categorias, setCategorias] = useState([]);
  const [loading, setLoading] = useState(true);
  const [usuario, setUsuario] = useState(null);

  useFocusEffect(useCallback(() => {
    cargarDatos();
  }, []));

  const cargarDatos = async () => {
    try {
      const usuarioStr = await AsyncStorage.getItem('usuario');
      if (usuarioStr) setUsuario(JSON.parse(usuarioStr));
      const data = await categoriasService.getAll();
      setCategorias(data);
    } catch (e) {
      Alert.alert('Error', e.message || 'No se pudieron cargar las categorías');
    } finally {
      setLoading(false);
    }
  };

  const handleLogout = async () => {
    await AsyncStorage.multiRemove(['token', 'usuario']);
    navigation.replace('Login');
  };

  const renderCategoria = ({ item, index }) => (
    <TouchableOpacity
      style={[styles.row, index < categorias.length - 1 && styles.rowBorder]}
      activeOpacity={0.7}
      onPress={() => navigation.navigate('Jugadores', { categoriaId: item.id, categoriaNombre: item.nombre })}
    >
      <View style={styles.rowLeft}>
        <Text style={styles.rowNombre}>{item.nombre}</Text>
        <Text style={styles.rowSub}>
          DT: {item.entrenadorNombre || '—'} · {item.cantJugadores} jugadores
        </Text>
      </View>
      <View style={styles.rowRight}>
        <Text style={styles.rowCuota}>{item.anioNacimientoDesde}–{item.anioNacimientoHasta}</Text>
        <Text style={styles.rowCuotaLabel}>ver padrón ›</Text>
      </View>
    </TouchableOpacity>
  );

  return (
    <SafeAreaView style={styles.container} edges={['top']}>
      {/* Header */}
      <View style={styles.header}>
        <View>
          <Text style={styles.headerTitle}>Categorías</Text>
          <Text style={styles.headerSub}>Monto de cuota mensual por categoría</Text>
        </View>
        <TouchableOpacity style={styles.nuevaBtn} onPress={() => Alert.alert('Próximamente', 'Alta de categoría')}>
          <Text style={styles.nuevaBtnText}>+ Nueva</Text>
        </TouchableOpacity>
      </View>

      {loading ? (
        <View style={styles.center}>
          <ActivityIndicator size="large" color={NAVY} />
        </View>
      ) : (
        <>
          <View style={styles.card}>
            <FlatList
              data={categorias.filter(c => c.activo)}
              keyExtractor={(item) => item.id.toString()}
              renderItem={renderCategoria}
              ListEmptyComponent={
                <View style={styles.center}>
                  <Text style={styles.emptyText}>No hay categorías disponibles</Text>
                </View>
              }
              scrollEnabled={false}
            />
          </View>

          <Text style={styles.footerNote}>
            Tocá una categoría para ver su padrón. Desde "Editar" se cambia el monto, se asignan entrenadores o se desactiva.
          </Text>
        </>
      )}

      {/* Bottom Tab Bar */}
      <View style={styles.tabBar}>
        {[
          { label: 'Panel', icon: 'stats-chart', active: false },
          { label: 'Jugadores', icon: 'people', active: true },
          { label: 'Cuotas', icon: 'wallet', active: false },
          { label: 'Documentación', icon: 'document-text', active: false },
        ].map((tab) => (
          <TouchableOpacity key={tab.label} style={styles.tabItem}>
            <Ionicons name={tab.active ? tab.icon : `${tab.icon}-outline`} size={24} color={tab.active ? NAVY : '#9AA0A6'} style={styles.tabIcon} />
            <Text style={[styles.tabLabel, tab.active && styles.tabLabelActive]}>{tab.label}</Text>
          </TouchableOpacity>
        ))}
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F0F2F5' },

  header: {
    backgroundColor: NAVY,
    paddingHorizontal: 20,
    paddingTop: 16,
    paddingBottom: 16,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  headerTitle: { color: '#fff', fontSize: 22, fontWeight: 'bold' },
  headerSub: { color: 'rgba(255,255,255,0.65)', fontSize: 13, marginTop: 2 },
  nuevaBtn: { paddingHorizontal: 12, paddingVertical: 7, borderRadius: 8, borderWidth: 1, borderColor: 'rgba(255,255,255,0.4)' },
  nuevaBtnText: { color: '#fff', fontSize: 14, fontWeight: '600' },

  card: {
    backgroundColor: '#fff',
    marginHorizontal: 16,
    marginTop: 16,
    borderRadius: 14,
    overflow: 'hidden',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.08,
    shadowRadius: 4,
    elevation: 2,
  },
  row: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingVertical: 14,
  },
  rowBorder: { borderBottomWidth: 1, borderBottomColor: '#F0F0F0' },
  rowLeft: { flex: 1 },
  rowNombre: { fontSize: 16, fontWeight: '700', color: '#1A1A1A' },
  rowSub: { fontSize: 13, color: '#888', marginTop: 3 },
  rowRight: { alignItems: 'flex-end' },
  rowCuota: { fontSize: 15, fontWeight: '700', color: '#1A1A1A' },
  rowCuotaLabel: { fontSize: 12, color: '#999', marginTop: 2 },

  footerNote: {
    marginHorizontal: 20,
    marginTop: 14,
    fontSize: 12,
    color: '#999',
    lineHeight: 18,
  },

  center: { flex: 1, alignItems: 'center', justifyContent: 'center', paddingTop: 60 },
  emptyText: { color: '#888' },

  tabBar: {
    flexDirection: 'row',
    borderTopWidth: 1,
    borderTopColor: '#E0E0E0',
    backgroundColor: '#fff',
    paddingBottom: 8,
    paddingTop: 8,
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
  },
  tabItem: { flex: 1, alignItems: 'center', paddingVertical: 4 },
  tabIcon: { marginBottom: 2 },
  tabLabel: { fontSize: 11, color: '#999' },
  tabLabelActive: { color: NAVY, fontWeight: '700' },
});
