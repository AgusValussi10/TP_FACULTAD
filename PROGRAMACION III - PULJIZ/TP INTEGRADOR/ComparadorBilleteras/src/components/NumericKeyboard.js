import { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  Modal,
  TouchableOpacity,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';

const colors = {
  primary: '#3b82f6',
  primaryDark: '#2563eb',
  background: '#ffffff',
  surface: '#f8f9fa',
  border: '#dee2e6',
  textPrimary: '#1a1a1a',
  textSecondary: '#6c757d',
  textMuted: '#adb5bd',
  keyBg: '#f1f3f5',
  keyPressed: '#e7eaf0',
};

const KEYS = [
  ['1', '2', '3'],
  ['4', '5', '6'],
  ['7', '8', '9'],
  ['.', '0', 'back'],
];

function formatDisplay(val) {
  if (!val) return '0';
  const [int, dec] = val.split('.');
  const intFormatted = parseInt(int || '0', 10).toLocaleString('es-AR');
  return dec !== undefined ? `${intFormatted},${dec}` : intFormatted;
}

export default function NumericKeyboard({
  visible,
  onClose,
  onConfirm,
  initialValue = '',
  prefix = '$',
}) {
  const [value, setValue] = useState(initialValue);

  useEffect(() => {
    if (visible) setValue(initialValue);
  }, [visible, initialValue]);

  const handleKey = (key) => {
    if (key === 'back') {
      setValue((v) => v.slice(0, -1));
      return;
    }
    if (key === '.') {
      if (value.includes('.')) return;
      setValue((v) => (v === '' ? '0.' : v + '.'));
      return;
    }
    setValue((v) => {
      if (v === '0') return key;
      const [, dec] = v.split('.');
      if (dec && dec.length >= 2) return v;
      return v + key;
    });
  };

  const handleConfirm = () => {
    const num = parseFloat(value);
    if (isNaN(num) || num <= 0) return;
    onConfirm(value);
    onClose();
  };

  const canConfirm = value !== '' && parseFloat(value) > 0;

  return (
    <Modal
      visible={visible}
      transparent
      animationType="slide"
      onRequestClose={onClose}
    >
      <TouchableOpacity
        style={styles.overlay}
        activeOpacity={1}
        onPress={onClose}
      >
        <TouchableOpacity
          activeOpacity={1}
          style={styles.sheet}
          onPress={(e) => e.stopPropagation()}
        >
          <View style={styles.handle} />

          <View style={styles.display}>
            <Text style={styles.prefix}>{prefix}</Text>
            <Text style={styles.amount}>{formatDisplay(value)}</Text>
          </View>

          <View style={styles.keyboard}>
            {KEYS.map((row, rowIdx) => (
              <View key={rowIdx} style={styles.row}>
                {row.map((key) => (
                  <TouchableOpacity
                    key={key}
                    style={styles.key}
                    onPress={() => handleKey(key)}
                    activeOpacity={0.6}
                  >
                    {key === 'back' ? (
                      <Ionicons name="backspace-outline" size={24} color={colors.textPrimary} />
                    ) : (
                      <Text style={styles.keyText}>{key}</Text>
                    )}
                  </TouchableOpacity>
                ))}
              </View>
            ))}
          </View>

          <TouchableOpacity
            style={[styles.confirmButton, !canConfirm && styles.confirmDisabled]}
            onPress={handleConfirm}
            activeOpacity={0.85}
            disabled={!canConfirm}
          >
            <Text style={styles.confirmText}>CONFIRMAR</Text>
          </TouchableOpacity>
        </TouchableOpacity>
      </TouchableOpacity>
    </Modal>
  );
}

const styles = StyleSheet.create({
  overlay: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.4)',
    justifyContent: 'flex-end',
  },
  sheet: {
    backgroundColor: colors.background,
    borderTopLeftRadius: 20,
    borderTopRightRadius: 20,
    paddingHorizontal: 20,
    paddingTop: 12,
    paddingBottom: 28,
  },
  handle: {
    width: 40,
    height: 4,
    borderRadius: 2,
    backgroundColor: colors.border,
    alignSelf: 'center',
    marginBottom: 20,
  },
  display: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 10,
    backgroundColor: colors.surface,
    borderRadius: 16,
    paddingVertical: 24,
    marginBottom: 20,
  },
  prefix: {
    fontSize: 28,
    fontWeight: '700',
    color: colors.textSecondary,
  },
  amount: {
    fontSize: 48,
    fontWeight: '700',
    color: colors.textPrimary,
    letterSpacing: -1,
  },
  keyboard: {
    gap: 10,
    marginBottom: 20,
  },
  row: {
    flexDirection: 'row',
    gap: 10,
  },
  key: {
    flex: 1,
    height: 64,
    backgroundColor: colors.keyBg,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
  },
  keyText: {
    fontSize: 26,
    fontWeight: '600',
    color: colors.textPrimary,
  },
  confirmButton: {
    backgroundColor: colors.primary,
    borderRadius: 12,
    paddingVertical: 18,
    alignItems: 'center',
  },
  confirmDisabled: {
    backgroundColor: colors.textMuted,
  },
  confirmText: {
    color: '#ffffff',
    fontSize: 16,
    fontWeight: '700',
    letterSpacing: 1,
  },
});
