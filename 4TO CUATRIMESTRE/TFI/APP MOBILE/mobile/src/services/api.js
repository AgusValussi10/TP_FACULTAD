import AsyncStorage from '@react-native-async-storage/async-storage';

// IP de la PC en la red local (Expo Go en teléfono físico)
// Para emulador Android puro: cambiar por http://10.0.2.2:5089
const BASE_URL = 'http://192.168.0.118:5089';

const getHeaders = async (withAuth = false) => {
  const headers = { 'Content-Type': 'application/json' };
  if (withAuth) {
    const token = await AsyncStorage.getItem('token');
    if (token) headers['Authorization'] = `Bearer ${token}`;
  }
  return headers;
};

export const authService = {
  login: async (email, password) => {
    const res = await fetch(`${BASE_URL}/api/auth/login`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password }),
    });
    if (!res.ok) throw new Error('Credenciales inválidas');
    return res.json();
  },

  logout: async () => {
    await AsyncStorage.multiRemove(['token', 'usuario']);
  },
};

export const categoriasService = {
  getAll: async () => {
    const headers = await getHeaders(true);
    const res = await fetch(`${BASE_URL}/api/categorias`, { headers });
    if (!res.ok) throw new Error('Error al cargar categorías');
    return res.json();
  },
};

export const jugadoresService = {
  getPorCategoria: async (categoriaId) => {
    const headers = await getHeaders(true);
    const res = await fetch(`${BASE_URL}/api/jugadores?categoriaId=${categoriaId}`, { headers });
    if (!res.ok) throw new Error('Error al cargar jugadores');
    return res.json();
  },

  getById: async (id) => {
    const headers = await getHeaders(true);
    const res = await fetch(`${BASE_URL}/api/jugadores/${id}`, { headers });
    if (!res.ok) throw new Error('Error al cargar la ficha del jugador');
    return res.json();
  },

  getGrupos: async (busqueda = '') => {
    const headers = await getHeaders(true);
    const res = await fetch(`${BASE_URL}/api/jugadores/grupos?busqueda=${encodeURIComponent(busqueda)}`, { headers });
    if (!res.ok) throw new Error('Error al buscar grupos familiares');
    return res.json();
  },

  crear: async (payload) => {
    const headers = await getHeaders(true);
    const res = await fetch(`${BASE_URL}/api/jugadores`, { method: 'POST', headers, body: JSON.stringify(payload) });
    if (!res.ok) throw new Error(await parseError(res, 'No se pudo crear el jugador'));
    return res.json();
  },

  actualizar: async (id, payload) => {
    const headers = await getHeaders(true);
    const res = await fetch(`${BASE_URL}/api/jugadores/${id}`, { method: 'PUT', headers, body: JSON.stringify(payload) });
    if (!res.ok) throw new Error(await parseError(res, 'No se pudo actualizar el jugador'));
  },

  eliminar: async (id) => {
    const headers = await getHeaders(true);
    const res = await fetch(`${BASE_URL}/api/jugadores/${id}`, { method: 'DELETE', headers });
    if (!res.ok) throw new Error(await parseError(res, 'No se pudo dar de baja al jugador'));
  },
};

async function parseError(res, fallback) {
  try {
    const body = await res.json();
    return body.message || fallback;
  } catch {
    return fallback;
  }
}
