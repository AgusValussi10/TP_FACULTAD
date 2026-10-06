import { useCallback, useState } from 'react';
import { ActivityIndicator, Alert, ScrollView, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { jugadoresService } from '../services/api';

const NAVY = '#1C2E4A';

const fmtFecha = (iso) => (iso ? iso.slice(0, 10).split('-').reverse().join('/') : '—');

export default function JugadorDetalleScreen({ route, navigation }) {
  const { jugadorId } = route.params;
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);

  const cargar = useCallback(async () => {
    try {
      setData(await jugadoresService.getById(jugadorId));
    } catch (e) {
      Alert.alert('Error', e.message);
    } finally {
      setLoading(false);
    }
  }, [jugadorId]);

  useFocusEffect(useCallback(() => { cargar(); }, [cargar]));

  const darDeBaja = () => {
    Alert.alert('Dar de baja', `¿Dar de baja a ${data.jugador.nombre} ${data.jugador.apellido}?`, [
      { text: 'Cancelar', style: 'cancel' },
      {
        text: 'Dar de baja',
        style: 'destructive',
        onPress: async () => {
          try {
            await jugadoresService.eliminar(jugadorId);
            navigation.goBack();
          } catch (e) {
            Alert.alert('Error', e.message);
          }
        },
      },
    ]);
  };

  if (loading || !data) {
    return (
      <SafeAreaView style={styles.container} edges={['top']}>
        <ActivityIndicator size="large" color={NAVY} style={{ marginTop: 80 }} />
      </SafeAreaView>
    );
  }

  const { jugador: j, hermanos = [], cuotas = [], seguro, carnet } = data;

  return (
    <SafeAreaView style={styles.container} edges={['top']}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()}>
          <Text style={styles.backText}>‹ Volver</Text>
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Ficha del jugador</Text>
        <TouchableOpacity onPress={() => navigation.navigate('JugadorForm', { jugador: j })}>
          <Text style={styles.editText}>Editar</Text>
        </TouchableOpacity>
      </View>

      <ScrollView contentContainerStyle={styles.body}>
        <View style={styles.card}>
          <Text style={styles.nombre}>{j.apellido}, {j.nombre}</Text>
          <Text style={styles.sub}>{j.categoriaNombre}</Text>
          <Fila k="DNI" v={j.dni} />
          <Fila k="Nacimiento" v={fmtFecha(j.fechaNacimiento)} />
          <Fila k="Alta" v={fmtFecha(j.fechaAlta)} />
        </View>

        <Text style={styles.section}>FAMILIA</Text>
        <View style={styles.card}>
          {j.grupoFamiliarId ? (
            <>
              <Fila k="Contacto" v={j.grupoFamiliarContacto} />
              <Fila k="Teléfono" v={j.grupoFamiliarTelefono || '—'} />
              <Fila k="Email" v={j.grupoFamiliarEmail || '—'} />
              <Text style={styles.subSection}>Hermanos vinculados {hermanos.length > 0 ? '· 10% desc.' : ''}</Text>
              {hermanos.length === 0 && <Text style={styles.muted}>No tiene hermanos registrados en el club.</Text>}
              {hermanos.map((h) => (
                <TouchableOpacity key={h.id} style={styles.hermano}
                  onPress={() => navigation.push('JugadorDetalle', { jugadorId: h.id })}>
                  <Text style={styles.hermanoNombre}>{h.apellido}, {h.nombre}</Text>
                  <Text style={styles.hermanoSub}>{h.categoriaNombre} ›</Text>
                </TouchableOpacity>
              ))}
            </>
          ) : (
            <Text style={styles.muted}>Sin grupo familiar. Usá "Editar" para vincularlo con un hermano.</Text>
          )}
        </View>

        <Text style={styles.section}>ESTADO</Text>
        <View style={styles.card}>
          <Fila k="Seguro" v={seguro ? `${seguro.estado} · hasta ${fmtFecha(seguro.vigenteHasta)}` : 'Sin seguro'} />
          <Fila k="Carnet liga" v={carnet ? `${carnet.estado} · ${carnet.temporada}` : 'Sin carnet'} />
          <Fila k="Última cuota" v={cuotas[0] ? `${cuotas[0].periodoMes}/${cuotas[0].periodoAnio} · ${cuotas[0].estado}` : '—'} />
        </View>

        <TouchableOpacity style={styles.bajaBtn} onPress={darDeBaja}>
          <Text style={styles.bajaText}>Dar de baja</Text>
        </TouchableOpacity>
      </ScrollView>
    </SafeAreaView>
  );
}

const Fila = ({ k, v }) => (
  <View style={styles.fila}>
    <Text style={styles.k}>{k}</Text>
    <Text style={styles.v}>{v}</Text>
  </View>
);

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F0F2F5' },
  header: { backgroundColor: NAVY, paddingHorizontal: 16, paddingVertical: 14, flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  backText: { color: 'rgba(255,255,255,0.75)', fontSize: 14, width: 60 },
  editText: { color: '#fff', fontSize: 14, fontWeight: '700', width: 60, textAlign: 'right' },
  headerTitle: { color: '#fff', fontSize: 18, fontWeight: 'bold' },
  body: { padding: 16, paddingBottom: 40 },
  section: { fontSize: 12, fontWeight: '700', color: '#888', marginBottom: 6, marginTop: 8, letterSpacing: 0.5 },
  card: { backgroundColor: '#fff', borderRadius: 14, padding: 14, marginBottom: 12 },
  nombre: { fontSize: 20, fontWeight: 'bold', color: '#1A1A1A' },
  sub: { fontSize: 14, color: '#888', marginBottom: 8 },
  fila: { flexDirection: 'row', justifyContent: 'space-between', paddingVertical: 6 },
  k: { color: '#888', fontSize: 14 },
  v: { color: '#1A1A1A', fontSize: 14, fontWeight: '600', flexShrink: 1, textAlign: 'right' },
  subSection: { fontSize: 13, fontWeight: '700', color: NAVY, marginTop: 12, marginBottom: 4 },
  muted: { color: '#888', fontSize: 13, paddingVertical: 4 },
  hermano: { flexDirection: 'row', justifyContent: 'space-between', paddingVertical: 10, borderTopWidth: 1, borderTopColor: '#F0F0F0' },
  hermanoNombre: { fontSize: 14, fontWeight: '600', color: '#1A1A1A' },
  hermanoSub: { fontSize: 13, color: '#888' },
  bajaBtn: { borderWidth: 1, borderColor: '#C62828', borderRadius: 12, paddingVertical: 12, alignItems: 'center', marginTop: 8 },
  bajaText: { color: '#C62828', fontWeight: '700', fontSize: 15 },
});
