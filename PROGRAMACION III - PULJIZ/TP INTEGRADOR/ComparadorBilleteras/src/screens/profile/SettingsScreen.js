import { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  Switch,
  StatusBar,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';

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

// Componente que renderiza el título de una sección de configuración
function SectionHeader({ title }) {
  return <Text style={styles.sectionHeader}>{title}</Text>;
}

// Componente que renderiza una fila de configuración, como navegación (con valor y flecha) o como switch
function SettingRow({ label, value, onPress, isToggle, toggleValue, onToggle }) {
  return (
    <TouchableOpacity
      style={styles.row}
      onPress={isToggle ? undefined : onPress}
      activeOpacity={isToggle ? 1 : 0.7}
    >
      <Text style={styles.rowLabel}>{label}</Text>
      {isToggle ? (
        <Switch
          value={toggleValue}
          onValueChange={onToggle}
          trackColor={{ false: colors.border, true: colors.primary }}
          thumbColor="#ffffff"
        />
      ) : (
        <View style={styles.rowRight}>
          {value ? <Text style={styles.rowValue}>{value}</Text> : null}
          <Ionicons name="chevron-forward" size={16} color={colors.textMuted} />
        </View>
      )}
    </TouchableOpacity>
  );
}

// Pantalla que muestra los toggles de notificaciones, tema, idioma y datos de cuenta (mockup local)
export default function SettingsScreen({ navigation }) {
  const [alertasEnabled, setAlertasEnabled] = useState(true);
  const [resumenEnabled, setResumenEnabled] = useState(false);
  const [novedadesEnabled, setNovedadesEnabled] = useState(true);

  return (
    <SafeAreaView style={styles.safe}>
      <StatusBar barStyle="dark-content" backgroundColor={colors.background} />

      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
          <Ionicons name="arrow-back" size={22} color={colors.textPrimary} />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Configuración</Text>
        <View style={styles.headerSpacer} />
      </View>

      <ScrollView
        style={styles.scroll}
        showsVerticalScrollIndicator={false}
        contentContainerStyle={styles.scrollContent}
      >
        <SectionHeader title="Cuenta" />
        <View style={styles.section}>
          <SettingRow label="Moneda predeterminada" value="ARS" />
          <View style={styles.rowDivider} />
          <SettingRow label="País de residencia" value="Argentina" />
        </View>

        <SectionHeader title="Notificaciones" />
        <View style={styles.section}>
          <SettingRow
            label="Alertas de precio"
            isToggle
            toggleValue={alertasEnabled}
            onToggle={setAlertasEnabled}
          />
          <View style={styles.rowDivider} />
          <SettingRow
            label="Resumen semanal"
            isToggle
            toggleValue={resumenEnabled}
            onToggle={setResumenEnabled}
          />
          <View style={styles.rowDivider} />
          <SettingRow
            label="Novedades de la app"
            isToggle
            toggleValue={novedadesEnabled}
            onToggle={setNovedadesEnabled}
          />
        </View>

        <SectionHeader title="Apariencia" />
        <View style={styles.section}>
          <SettingRow label="Tema" value="Claro" />
          <View style={styles.rowDivider} />
          <SettingRow label="Idioma" value="Español" />
        </View>

        <SectionHeader title="Acerca de" />
        <View style={styles.section}>
          <SettingRow label="Versión" value="1.0.0" />
          <View style={styles.rowDivider} />
          <SettingRow label="Términos y condiciones" />
          <View style={styles.rowDivider} />
          <SettingRow label="Política de privacidad" />
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: {
    flex: 1,
    backgroundColor: colors.surface,
  },
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
  backBtn: {
    padding: 4,
    width: 36,
  },
  headerTitle: {
    fontSize: 18,
    fontWeight: '700',
    color: colors.textPrimary,
  },
  headerSpacer: {
    width: 36,
  },
  scroll: {
    flex: 1,
  },
  scrollContent: {
    paddingBottom: 32,
  },
  sectionHeader: {
    fontSize: 13,
    fontWeight: '600',
    color: colors.textSecondary,
    textTransform: 'uppercase',
    letterSpacing: 0.5,
    paddingHorizontal: 20,
    paddingTop: 24,
    paddingBottom: 8,
  },
  section: {
    backgroundColor: colors.background,
    borderTopWidth: 1,
    borderBottomWidth: 1,
    borderColor: colors.divider,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 20,
    paddingVertical: 14,
  },
  rowLabel: {
    fontSize: 16,
    color: colors.textPrimary,
  },
  rowRight: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  rowValue: {
    fontSize: 16,
    color: colors.textSecondary,
  },
  rowDivider: {
    height: 1,
    backgroundColor: colors.divider,
    marginLeft: 20,
  },
});
