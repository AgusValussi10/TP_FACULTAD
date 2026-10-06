import { NavigationContainer } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import { StatusBar } from 'expo-status-bar';
import LoginScreen from './src/screens/LoginScreen';
import CategoriasScreen from './src/screens/CategoriasScreen';
import JugadoresScreen from './src/screens/JugadoresScreen';
import JugadorDetalleScreen from './src/screens/JugadorDetalleScreen';
import JugadorFormScreen from './src/screens/JugadorFormScreen';

const Stack = createNativeStackNavigator();

export default function App() {
  return (
    <SafeAreaProvider>
      <StatusBar style="light" />
      <NavigationContainer>
        <Stack.Navigator screenOptions={{ headerShown: false }}>
          <Stack.Screen name="Login" component={LoginScreen} />
          <Stack.Screen name="Categorias" component={CategoriasScreen} />
          <Stack.Screen name="Jugadores" component={JugadoresScreen} />
          <Stack.Screen name="JugadorDetalle" component={JugadorDetalleScreen} />
          <Stack.Screen name="JugadorForm" component={JugadorFormScreen} />
        </Stack.Navigator>
      </NavigationContainer>
    </SafeAreaProvider>
  );
}
