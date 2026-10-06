import AsyncStorage from '@react-native-async-storage/async-storage';
import { useCallback, useState } from 'react';
import { useFocusEffect } from '@react-navigation/native';
import {
  ActivityIndicator,
  Alert,
  FlatList,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { jugadoresService } from '../services/api';

const NAVY = '#1C2E4A';

const calcularEdad = (fechaNacimiento) => {
  const hoy = new Date();
  const nac = new Date(fechaNacimiento);
  let edad = hoy.getFullYear() - nac.getFullYear();
  const m = hoy.getMonth() - nac.getMonth();
  if (m < 0 || (m === 0 && hoy.getDate() < nac.getDate())) edad--;
  return edad;
};

const iniciales = (nombre, apellido) =>
  `${nombre?.[0] ?? ''}${apellido?.[0] ?? ''}`.toUpperCase();

export default function JugadoresScreen({ route, navigation }) {
  const { categoriaId, categoriaNombre } = route.params;
  const [jugadores, setJugadores] = useState([]);
  const [busqueda, setBusqueda] = useState('');
  const [loading, setLoading] = useState(true);

  useFocusEffect(useCallback(() => {
    cargarJugadores();
  }, []));

  const cargarJugadores = async () => {
    try {
      const data = await jugadoresService.getPorCategoria(categoriaId);
      setJugadores(data);
    } catch (e) {
      Alert.alert('Error', e.message || 'No se pudieron cargar los jugadores');
    } finally {
      setLoading(false);
    }
  };

  const jugadoresFiltrados = jugadores.filter(j =>
    busqueda.trim() === '' ||
    `${j.nombre} ${j.apellido}`.toLowerCase().includes(busqueda.toLowerCase()) ||
    j.dni?.includes(busqueda)
  );

  const renderJugador = ({ item, index }) => (
    <TouchableOpacity
      style={[styles.row, index < jugadoresFiltrados.length - 1 && styles.rowBorder]}
      activeOpacity={0.7}
      onPress={() => navigation.navigate('JugadorDetalle', { jugadorId: item.id })}
    >
      <View style={styles.avatar}>
        <Text style={styles.avatarText}>{iniciales(item.nombre, item.apellido)}</Text>
      </View>
      <View style={styles.info}>
        <Text style={styles.nombre}>{item.apellido}, {item.nombre}</Text>
        <Text style={styles.sub}>DNI {item.dni} · {calcularEdad(item.fechaNacimiento)} años{item.cantHermanos > 0 ? ` · 👥 ${item.cantHermanos} hermano${item.cantHermanos > 1 ? 's' : ''}` : ''}</Text>
      </View>
      <Text style={styles.flecha}>›</Text>
    </TouchableOpacity>
  );

  return (
    <SafeAreaView style={styles.container} edges={['top']}>
      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity style={styles.backBtn} onPress={() => navigation.goBack()}>
          <Text style={styles.backText}>‹ Categorías</Text>
        </TouchableOpacity>
        <View>
          <Text style={styles.headerTitle}>{categoriaNombre}</Text>
          <Text style={styles.headerSub}>
            {loading ? '...' : `${jugadores.length} jugador${jugadores.length !== 1 ? 'es' : ''}`}
          </Text>
        </View>
        <TouchableOpacity style={styles.addBtn} onPress={() => navigation.navigate('JugadorForm', { categoriaId })}>
          <Text style={styles.addText}>+ Nuevo</Text>
        </TouchableOpacity>
      </View>

      {/* Buscador */}
      <View style={styles.searchContainer}>
        <TextInput
          style={styles.searchInput}
          placeholder="Buscar por nombre o DNI..."
          placeholderTextColor="#BDBDBD"
          value={busqueda}
          onChangeText={setBusqueda}
          clearButtonMode="while-editing"
        />
      </View>

      {loading ? (
        <View style={styles.center}>
          <ActivityIndicator size="large" color={NAVY} />
        </View>
      ) : (
        <FlatList
          data={jugadoresFiltrados}
          keyExtractor={(item) => item.id.toString()}
          renderItem={renderJugador}
          contentContainerStyle={styles.list}
          ListEmptyComponent={
            <View style={styles.center}>
              <Text style={styles.emptyText}>
                {busqueda ? 'Sin resultados para esa búsqueda' : 'No hay jugadores en esta categoría'}
              </Text>
            </View>
          }
        />
      )}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F0F2F5' },

  header: {
    backgroundColor: NAVY,
    paddingHorizontal: 16,
    paddingTop: 12,
    paddingBottom: 16,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  backBtn: { paddingRight: 8 },
  backText: { color: 'rgba(255,255,255,0.75)', fontSize: 14 },
  headerTitle: { color: '#fff', fontSize: 20, fontWeight: 'bold', textAlign: 'center' },
  headerSub: { color: 'rgba(255,255,255,0.65)', fontSize: 13, textAlign: 'center', marginTop: 2 },
  addBtn: { paddingHorizontal: 10, paddingVertical: 6, borderRadius: 8, borderWidth: 1, borderColor: 'rgba(255,255,255,0.4)' },
  addText: { color: '#fff', fontSize: 13, fontWeight: '600' },

  searchContainer: { paddingHorizontal: 16, paddingVertical: 12 },
  searchInput: {
    backgroundColor: '#fff',
    borderRadius: 10,
    paddingHorizontal: 14,
    paddingVertical: 10,
    fontSize: 15,
    color: '#1A1A1A',
    borderWidth: 1,
    borderColor: '#E0E0E0',
  },

  list: { paddingHorizontal: 16, paddingBottom: 24 },
  row: {
    backgroundColor: '#fff',
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 14,
    paddingVertical: 12,
  },
  rowBorder: { borderBottomWidth: 1, borderBottomColor: '#F0F0F0' },

  avatar: {
    width: 42,
    height: 42,
    borderRadius: 21,
    backgroundColor: NAVY,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 12,
  },
  avatarText: { color: '#fff', fontSize: 14, fontWeight: 'bold' },

  info: { flex: 1 },
  nombre: { fontSize: 15, fontWeight: '700', color: '#1A1A1A' },
  sub: { fontSize: 13, color: '#888', marginTop: 2 },
  flecha: { fontSize: 20, color: '#CCC', paddingLeft: 8 },

  center: { flex: 1, alignItems: 'center', justifyContent: 'center', paddingTop: 60 },
  emptyText: { color: '#888', fontSize: 15, textAlign: 'center' },
});
