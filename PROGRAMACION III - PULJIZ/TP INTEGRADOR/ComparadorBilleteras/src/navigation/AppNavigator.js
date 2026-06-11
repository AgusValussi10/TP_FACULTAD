import { NavigationContainer } from '@react-navigation/native';
import { createStackNavigator } from '@react-navigation/stack';

import SplashScreen from '../screens/SplashScreen';
import OnboardingScreen from '../screens/OnboardingScreen';
import LoginScreen from '../screens/LoginScreen';
import ForgotPasswordScreen from '../screens/ForgotPasswordScreen';
import HomeScreen from '../screens/HomeScreen';
import ResultsScreen from '../screens/ResultsScreen';
import EmptyResultsScreen from '../screens/EmptyResultsScreen';
import LoadingResultsScreen from '../screens/LoadingResultsScreen';
import HistoryScreen from '../screens/HistoryScreen';
import WalletDetailScreen from '../screens/WalletDetailScreen';
import CompareScreen from '../screens/CompareScreen';
import WalletsScreen from '../screens/WalletsScreen';
import WalletProfileScreen from '../screens/WalletProfileScreen';
import AlertsScreen from '../screens/AlertsScreen';
import CreateAlertScreen from '../screens/CreateAlertScreen';
import ProfileScreen from '../screens/ProfileScreen';
import ErrorScreen from '../screens/ErrorScreen';

const Stack = createStackNavigator();

export default function AppNavigator() {
  return (
    <NavigationContainer>
      <Stack.Navigator initialRouteName="Splash" screenOptions={{ headerShown: false }}>
        <Stack.Screen name="Splash" component={SplashScreen} />
        <Stack.Screen name="Onboarding" component={OnboardingScreen} />
        <Stack.Screen name="Login" component={LoginScreen} />
        <Stack.Screen name="ForgotPassword" component={ForgotPasswordScreen} />
        <Stack.Screen name="Home" component={HomeScreen} />
        <Stack.Screen name="Results" component={ResultsScreen} />
        <Stack.Screen name="EmptyResults" component={EmptyResultsScreen} />
        <Stack.Screen name="LoadingResults" component={LoadingResultsScreen} />
        <Stack.Screen name="WalletDetail" component={WalletDetailScreen} />
        <Stack.Screen name="Compare" component={CompareScreen} />
        <Stack.Screen name="History" component={HistoryScreen} />
        <Stack.Screen name="Wallets" component={WalletsScreen} />
        <Stack.Screen name="WalletProfile" component={WalletProfileScreen} />
        <Stack.Screen name="Alerts" component={AlertsScreen} />
        <Stack.Screen name="CreateAlert" component={CreateAlertScreen} />
        <Stack.Screen name="Profile" component={ProfileScreen} />
        <Stack.Screen name="Error" component={ErrorScreen} />
      </Stack.Navigator>
    </NavigationContainer>
  );
}
