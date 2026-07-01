import { NavigationContainer } from '@react-navigation/native';
import { createStackNavigator } from '@react-navigation/stack';

import SplashScreen from '../screens/onboarding/SplashScreen';
import OnboardingScreen from '../screens/onboarding/OnboardingScreen';
import LoadingSplashScreen from '../screens/onboarding/LoadingSplashScreen';

import LoginScreen from '../screens/auth/LoginScreen';
import RegisterScreen from '../screens/auth/RegisterScreen';
import ForgotPasswordScreen from '../screens/auth/ForgotPasswordScreen';
import EmailVerificationScreen from '../screens/auth/EmailVerificationScreen';

import HomeScreen from '../screens/home/HomeScreen';

import ResultsScreen from '../screens/results/ResultsScreen';
import EmptyResultsScreen from '../screens/results/EmptyResultsScreen';
import LoadingResultsScreen from '../screens/results/LoadingResultsScreen';

import WalletProfileScreen from '../screens/wallets/WalletProfileScreen';
import WalletCompareScreen from '../screens/wallets/WalletCompareScreen';
import WalletsScreen from '../screens/wallets/WalletsScreen';

import HistoryScreen from '../screens/profile/HistoryScreen';
import ProfileScreen from '../screens/profile/ProfileScreen';
import EditProfileScreen from '../screens/profile/EditProfileScreen';
import SettingsScreen from '../screens/profile/SettingsScreen';
import FavoritesScreen from '../screens/profile/FavoritesScreen';

import AlertsScreen from '../screens/alerts/AlertsScreen';
import CreateAlertScreen from '../screens/alerts/CreateAlertScreen';
import PushNotificationScreen from '../screens/alerts/PushNotificationScreen';

import ErrorScreen from '../screens/error/ErrorScreen';

const Stack = createStackNavigator();

export default function AppNavigator() {
  return (
    <NavigationContainer>
      <Stack.Navigator initialRouteName="Splash" screenOptions={{ headerShown: false }}>
        <Stack.Screen name="Splash" component={SplashScreen} />
        <Stack.Screen name="Onboarding" component={OnboardingScreen} />
        <Stack.Screen name="Login" component={LoginScreen} />
        <Stack.Screen name="Register" component={RegisterScreen} />
        <Stack.Screen name="ForgotPassword" component={ForgotPasswordScreen} />
        <Stack.Screen name="EmailVerification" component={EmailVerificationScreen} />
        <Stack.Screen name="Home" component={HomeScreen} />
        <Stack.Screen name="Results" component={ResultsScreen} />
        <Stack.Screen name="EmptyResults" component={EmptyResultsScreen} />
        <Stack.Screen name="LoadingResults" component={LoadingResultsScreen} />
        <Stack.Screen name="WalletProfile" component={WalletProfileScreen} />
        <Stack.Screen name="WalletCompare" component={WalletCompareScreen} />
        <Stack.Screen name="Wallets" component={WalletsScreen} />
        <Stack.Screen name="History" component={HistoryScreen} />
        <Stack.Screen name="Alerts" component={AlertsScreen} />
        <Stack.Screen name="CreateAlert" component={CreateAlertScreen} />
        <Stack.Screen name="PushNotification" component={PushNotificationScreen} />
        <Stack.Screen name="Profile" component={ProfileScreen} />
        <Stack.Screen name="EditProfile" component={EditProfileScreen} />
        <Stack.Screen name="Settings" component={SettingsScreen} />
        <Stack.Screen name="Favorites" component={FavoritesScreen} />
        <Stack.Screen name="LoadingSplash" component={LoadingSplashScreen} />
        <Stack.Screen name="Error" component={ErrorScreen} />
      </Stack.Navigator>
    </NavigationContainer>
  );
}
