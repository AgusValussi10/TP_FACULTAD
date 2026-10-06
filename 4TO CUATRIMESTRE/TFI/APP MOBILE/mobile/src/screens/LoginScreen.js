import AsyncStorage from '@react-native-async-storage/async-storage';
import { useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Image,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';
import { authService } from '../services/api';

const NAVY = '#1C2E4A';
const NAVY_LIGHT = '#243B55';

export default function LoginScreen({ navigation }) {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);

  const handleLogin = async () => {
    if (!email.trim() || !password.trim()) {
      Alert.alert('Error', 'Completá todos los campos');
      return;
    }
    setLoading(true);
    try {
      const data = await authService.login(email.trim(), password);
      await AsyncStorage.setItem('token', data.token);
      await AsyncStorage.setItem('usuario', JSON.stringify({ nombre: data.nombre, rol: data.rol }));
      navigation.replace('Categorias');
    } catch (e) {
      Alert.alert('Error', e.message || 'No se pudo iniciar sesión');
    } finally {
      setLoading(false);
    }
  };

  const loginRapido = (emailRol) => {
    setEmail(emailRol);
    setPassword('Admin123!');
  };

  return (
    <KeyboardAvoidingView
      style={styles.container}
      behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
    >
      <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
        {/* Logo */}
        <View style={styles.logoContainer}>
          <Image source={require('../../assets/logo.png')} style={styles.logo} resizeMode="contain" />
          <Text style={styles.clubName}>Unión del Sur App</Text>
          <Text style={styles.subtitle}>Fundación Unión del Sur · Fútbol formativo</Text>
        </View>

        {/* Formulario */}
        <View style={styles.form}>
          <Text style={styles.label}>Correo electrónico</Text>
          <TextInput
            style={styles.input}
            placeholder="laura.gomez@correo.com"
            placeholderTextColor="#BDBDBD"
            value={email}
            onChangeText={setEmail}
            keyboardType="email-address"
            autoCapitalize="none"
            autoCorrect={false}
          />

          <Text style={styles.label}>Contraseña</Text>
          <TextInput
            style={styles.input}
            placeholder="••••••••"
            placeholderTextColor="#BDBDBD"
            value={password}
            onChangeText={setPassword}
            secureTextEntry
          />

          <TouchableOpacity
            style={[styles.btn, loading && styles.btnDisabled]}
            onPress={handleLogin}
            disabled={loading}
          >
            {loading ? (
              <ActivityIndicator color="#fff" />
            ) : (
              <Text style={styles.btnText}>Ingresar</Text>
            )}
          </TouchableOpacity>

          <Text style={styles.hint}>
            Cada cuenta tiene un único rol; la app muestra solo las secciones de ese rol.
          </Text>
        </View>

        {/* Acceso rápido para prototipo */}
        <View style={styles.protoBox}>
          <Text style={styles.protoTitle}>Solo prototipo · entrar como:</Text>
          <View style={styles.protoChips}>
            {[
              { label: 'Administración', email: 'admin@uniondelsur.com' },
              { label: 'Entrenador', email: 'lucas@uniondelsur.com' },
              { label: 'Familia', email: 'juan@mail.com' },
            ].map((r) => (
              <TouchableOpacity
                key={r.label}
                style={styles.chip}
                onPress={() => loginRapido(r.email)}
              >
                <Text style={styles.chipText}>{r.label}</Text>
              </TouchableOpacity>
            ))}
          </View>
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F0F2F5' },
  scroll: { flexGrow: 1, justifyContent: 'center', paddingHorizontal: 24, paddingVertical: 40 },

  logoContainer: { alignItems: 'center', marginBottom: 32 },
  logo: { width: 96, height: 96, marginBottom: 16 },
  clubName: { fontSize: 22, fontWeight: 'bold', color: '#1A1A1A', marginBottom: 6 },
  subtitle: { fontSize: 13, color: '#777', textAlign: 'center' },

  form: {
    backgroundColor: '#fff',
    borderRadius: 16,
    padding: 20,
    marginBottom: 16,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.08,
    shadowRadius: 4,
    elevation: 2,
  },
  label: { fontSize: 13, color: '#555', marginBottom: 6, marginTop: 4 },
  input: {
    borderWidth: 1,
    borderColor: '#E0E0E0',
    borderRadius: 10,
    paddingHorizontal: 14,
    paddingVertical: 12,
    fontSize: 15,
    color: '#1A1A1A',
    backgroundColor: '#FAFAFA',
    marginBottom: 14,
  },
  btn: {
    backgroundColor: NAVY,
    borderRadius: 10,
    paddingVertical: 14,
    alignItems: 'center',
    marginTop: 4,
    marginBottom: 14,
  },
  btnDisabled: { opacity: 0.6 },
  btnText: { color: '#fff', fontSize: 16, fontWeight: '700' },
  hint: { fontSize: 12, color: '#999', textAlign: 'center', lineHeight: 18 },

  protoBox: {
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 16,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.06,
    shadowRadius: 3,
    elevation: 1,
  },
  protoTitle: { fontSize: 13, color: '#888', marginBottom: 10 },
  protoChips: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  chip: {
    backgroundColor: '#EEF0F5',
    borderRadius: 20,
    paddingHorizontal: 14,
    paddingVertical: 7,
  },
  chipText: { fontSize: 13, color: NAVY, fontWeight: '600' },
});
