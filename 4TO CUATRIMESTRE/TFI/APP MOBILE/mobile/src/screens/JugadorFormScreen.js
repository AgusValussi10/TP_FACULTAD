import { useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { categoriasService, jugadoresService } from '../services/api';

const NAVY = '#1C2E4A';

// ISO (yyyy-mm-dd...) -> DD/MM/AAAA
const isoAFecha = (iso) => {
  if (!iso) return '';
  const [y, m, d] = iso.slice(0, 10).split('-');
  return `${d}/${m}/${y}`;
};

// DD/MM/AAAA -> yyyy-mm-dd (o null si es inválida)
const fechaAIso = (txt) => {
  const m = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(txt.trim());
  if (!m) return null;
  const [, d, mo, y] = m;
  const dt = new Date(Number(y), Number(mo) - 1, Number(d));
  if (dt.getFullYear() !== Number(y) || dt.getMonth() !== Number(mo) - 1 || dt.getDate() !== Number(d)) return null;
  return `${y}-${mo}-${d}`;
};

// Auto-inserta las barras mientras se escribe
const formatearFecha = (txt) => {
  const n = txt.replace(/\D/g, '').slice(0, 8);
  if (n.length <= 2) return n;
  if (n.length <= 4) return `${n.slice(0, 2)}/${n.slice(2)}`;
  return `${n.slice(0, 2)}/${n.slice(2, 4)}/${n.slice(4)}`;
};

export default function JugadorFormScreen({ route, navigation }) {
  const { categoriaId: categoriaInicial, jugador } = route.params;
  const editando = !!jugador;
  const teniaGrupo = editando && !!jugador.grupoFamiliarId;

  const [nombre, setNombre] = useState(jugador?.nombre ?? '');
  const [apellido, setApellido] = useState(jugador?.apellido ?? '');
  const [dni, setDni] = useState(jugador?.dni ?? '');
  const [fecha, setFecha] = useState(isoAFecha(jugador?.fechaNacimiento));
  const [categoriaId, setCategoriaId] = useState(jugador?.categoriaId ?? categoriaInicial);
  const [categorias, setCategorias] = useState([]);

  // Vínculo familiar: 'ninguno' | 'nuevo' | 'hermano' | 'actual' (solo al editar con grupo)
  const [modoGrupo, setModoGrupo] = useState(teniaGrupo ? 'actual' : 'ninguno');
  const [contacto, setContacto] = useState('');
  const [telefono, setTelefono] = useState('');
  const [email, setEmail] = useState('');
  const [busqGrupo, setBusqGrupo] = useState('');
  const [grupos, setGrupos] = useState([]);
  const [grupoSel, setGrupoSel] = useState(null);
  const [buscando, setBuscando] = useState(false);
  const [guardando, setGuardando] = useState(false);

  useEffect(() => {
    categoriasService.getAll().then((d) => setCategorias(d.filter((c) => c.activo))).catch(() => {});
  }, []);

  useEffect(() => {
    if (modoGrupo !== 'hermano') return;
    setBuscando(true);
    const t = setTimeout(async () => {
      try {
        setGrupos(await jugadoresService.getGrupos(busqGrupo.trim()));
      } catch {
        setGrupos([]);
      } finally {
        setBuscando(false);
      }
    }, 300);
    return () => clearTimeout(t);
  }, [modoGrupo, busqGrupo]);

  const guardar = async () => {
    if (!nombre.trim() || !apellido.trim() || !dni.trim()) {
      return Alert.alert('Faltan datos', 'Nombre, apellido y DNI son obligatorios');
    }
    const iso = fechaAIso(fecha);
    if (!iso) return Alert.alert('Fecha inválida', 'Usá el formato DD/MM/AAAA');

    const payload = {
      nombre: nombre.trim(),
      apellido: apellido.trim(),
      dni: dni.trim(),
      fechaNacimiento: iso,
      categoriaId,
    };

    if (modoGrupo === 'nuevo') {
      if (!contacto.trim()) return Alert.alert('Faltan datos', 'Ingresá el nombre del contacto familiar');
      payload.grupoFamiliar = { nombreContacto: contacto.trim(), telefono: telefono.trim(), email: email.trim() };
    } else if (modoGrupo === 'hermano') {
      if (!grupoSel) return Alert.alert('Elegí un hermano', 'Seleccioná la familia con la que vincular al jugador');
      payload.grupoFamiliarId = grupoSel.grupoId;
    }

    setGuardando(true);
    try {
      if (editando) await jugadoresService.actualizar(jugador.id, payload);
      else await jugadoresService.crear(payload);
      navigation.goBack();
    } catch (e) {
      Alert.alert('No se pudo guardar', e.message);
    } finally {
      setGuardando(false);
    }
  };

  const modos = [
    teniaGrupo ? { key: 'actual', label: 'Mantener' } : { key: 'ninguno', label: 'Sin vínculo' },
    { key: 'hermano', label: 'Es hermano de…' },
    { key: 'nuevo', label: 'Familia nueva' },
  ];

  return (
    <SafeAreaView style={styles.container} edges={['top']}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()}>
          <Text style={styles.backText}>‹ Cancelar</Text>
        </TouchableOpacity>
        <Text style={styles.headerTitle}>{editando ? 'Editar jugador' : 'Nuevo jugador'}</Text>
        <View style={{ width: 70 }} />
      </View>

      <KeyboardAvoidingView style={{ flex: 1 }} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
        <ScrollView contentContainerStyle={styles.body} keyboardShouldPersistTaps="handled">
          <Text style={styles.section}>DATOS DEL JUGADOR</Text>
          <View style={styles.card}>
            <Text style={styles.label}>Nombre</Text>
            <TextInput style={styles.input} value={nombre} onChangeText={setNombre} placeholder="Nombre" />
            <Text style={styles.label}>Apellido</Text>
            <TextInput style={styles.input} value={apellido} onChangeText={setApellido} placeholder="Apellido" />
            <Text style={styles.label}>DNI</Text>
            <TextInput style={styles.input} value={dni} onChangeText={(t) => setDni(t.replace(/\D/g, ''))}
              keyboardType="number-pad" placeholder="Sin puntos" />
            <Text style={styles.label}>Fecha de nacimiento</Text>
            <TextInput style={styles.input} value={fecha} onChangeText={(t) => setFecha(formatearFecha(t))}
              keyboardType="number-pad" placeholder="DD/MM/AAAA" />
            <Text style={styles.label}>Categoría</Text>
            <ScrollView horizontal showsHorizontalScrollIndicator={false}>
              {categorias.map((c) => (
                <TouchableOpacity key={c.id} onPress={() => setCategoriaId(c.id)}
                  style={[styles.chip, categoriaId === c.id && styles.chipActive]}>
                  <Text style={[styles.chipText, categoriaId === c.id && styles.chipTextActive]}>
                    {c.nombre} ({c.anioNacimientoDesde}–{c.anioNacimientoHasta})
                  </Text>
                </TouchableOpacity>
              ))}
            </ScrollView>
          </View>

          <Text style={styles.section}>VÍNCULO FAMILIAR</Text>
          <View style={styles.card}>
            {teniaGrupo && (
              <Text style={styles.hint}>Familia actual: {jugador.grupoFamiliarContacto}</Text>
            )}
            <View style={styles.segment}>
              {modos.map((m) => (
                <TouchableOpacity key={m.key} onPress={() => setModoGrupo(m.key)}
                  style={[styles.segBtn, modoGrupo === m.key && styles.segBtnActive]}>
                  <Text style={[styles.segText, modoGrupo === m.key && styles.segTextActive]}>{m.label}</Text>
                </TouchableOpacity>
              ))}
            </View>

            {modoGrupo === 'nuevo' && (
              <>
                <Text style={styles.label}>Contacto (madre/padre/tutor)</Text>
                <TextInput style={styles.input} value={contacto} onChangeText={setContacto} placeholder="Nombre y apellido" />
                <Text style={styles.label}>Teléfono</Text>
                <TextInput style={styles.input} value={telefono} onChangeText={setTelefono} keyboardType="phone-pad" placeholder="Opcional" />
                <Text style={styles.label}>Email</Text>
                <TextInput style={styles.input} value={email} onChangeText={setEmail} keyboardType="email-address"
                  autoCapitalize="none" placeholder="Opcional" />
              </>
            )}

            {modoGrupo === 'hermano' && (
              <>
                <Text style={styles.label}>Buscar hermano o familia</Text>
                <TextInput style={styles.input} value={busqGrupo} onChangeText={setBusqGrupo}
                  placeholder="Nombre, apellido, DNI o contacto" />
                {buscando && <ActivityIndicator color={NAVY} style={{ marginTop: 10 }} />}
                {!buscando && grupos.length === 0 && <Text style={styles.hint}>No se encontraron familias.</Text>}
                {grupos.map((g) => (
                  <TouchableOpacity key={g.grupoId} onPress={() => setGrupoSel(g)}
                    style={[styles.grupoRow, grupoSel?.grupoId === g.grupoId && styles.grupoRowActive]}>
                    <Text style={styles.grupoNombre}>{g.nombreContacto}</Text>
                    <Text style={styles.grupoSub}>{g.integrantes}</Text>
                  </TouchableOpacity>
                ))}
                {grupoSel && <Text style={styles.okMsg}>Se vinculará con: {grupoSel.nombreContacto} (10% desc. hermanos)</Text>}
              </>
            )}
          </View>

          <TouchableOpacity style={[styles.saveBtn, guardando && { opacity: 0.6 }]} onPress={guardar} disabled={guardando}>
            {guardando ? <ActivityIndicator color="#fff" /> : <Text style={styles.saveText}>{editando ? 'Guardar cambios' : 'Registrar jugador'}</Text>}
          </TouchableOpacity>
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F0F2F5' },
  header: { backgroundColor: NAVY, paddingHorizontal: 16, paddingVertical: 14, flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  backText: { color: 'rgba(255,255,255,0.75)', fontSize: 14, width: 70 },
  headerTitle: { color: '#fff', fontSize: 18, fontWeight: 'bold' },
  body: { padding: 16, paddingBottom: 40 },
  section: { fontSize: 12, fontWeight: '700', color: '#888', marginBottom: 6, marginTop: 8, letterSpacing: 0.5 },
  card: { backgroundColor: '#fff', borderRadius: 14, padding: 14, marginBottom: 12 },
  label: { fontSize: 12, color: '#666', marginTop: 10, marginBottom: 4 },
  input: { borderWidth: 1, borderColor: '#E0E0E0', borderRadius: 10, paddingHorizontal: 12, paddingVertical: 10, fontSize: 15, color: '#1A1A1A' },
  chip: { paddingHorizontal: 12, paddingVertical: 8, borderRadius: 18, borderWidth: 1, borderColor: '#D0D5DD', marginRight: 8 },
  chipActive: { backgroundColor: NAVY, borderColor: NAVY },
  chipText: { fontSize: 13, color: '#444' },
  chipTextActive: { color: '#fff', fontWeight: '600' },
  segment: { flexDirection: 'row', backgroundColor: '#EEF0F4', borderRadius: 10, padding: 3 },
  segBtn: { flex: 1, paddingVertical: 8, borderRadius: 8, alignItems: 'center' },
  segBtnActive: { backgroundColor: NAVY },
  segText: { fontSize: 12, color: '#555', fontWeight: '600' },
  segTextActive: { color: '#fff' },
  hint: { fontSize: 13, color: '#888', marginVertical: 8 },
  okMsg: { fontSize: 13, color: '#2E7D32', marginTop: 10, fontWeight: '600' },
  grupoRow: { borderWidth: 1, borderColor: '#E0E0E0', borderRadius: 10, padding: 10, marginTop: 8 },
  grupoRowActive: { borderColor: NAVY, backgroundColor: '#EAF0FA' },
  grupoNombre: { fontSize: 14, fontWeight: '700', color: '#1A1A1A' },
  grupoSub: { fontSize: 12, color: '#777', marginTop: 2 },
  saveBtn: { backgroundColor: NAVY, borderRadius: 12, paddingVertical: 14, alignItems: 'center', marginTop: 8 },
  saveText: { color: '#fff', fontSize: 16, fontWeight: '700' },
});
