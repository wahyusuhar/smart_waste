import type { ReactNode } from 'react';
import { ActivityIndicator, StyleSheet, Text, View } from 'react-native';

import { colors, fonts, radius, type } from '../theme';
import { Button } from './Button';

export function LoadingState({ message = 'Memuat…' }: { message?: string }) {
  return (
    <View style={styles.center}>
      <ActivityIndicator color={colors.accent} />
      <Text style={type.caption}>{message}</Text>
    </View>
  );
}

export function ErrorState({ message, onRetry }: { message: string; onRetry?: () => void }) {
  return (
    <View style={styles.center}>
      <Text style={[type.body, styles.centerText]}>{message}</Text>
      {onRetry ? <Button title="Coba lagi" variant="secondary" onPress={onRetry} /> : null}
    </View>
  );
}

export function EmptyState({ message, children }: { message: string; children?: ReactNode }) {
  return (
    <View style={styles.empty}>
      <Text style={[type.caption, styles.centerText]}>{message}</Text>
      {children}
    </View>
  );
}

// Error box for a form (for example "Email atau password salah." or a lost connection).
export function FormError({ message }: { message: string }) {
  return (
    <View accessibilityRole="alert" style={styles.formError}>
      <Text style={styles.formErrorText}>{message}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  center: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 12,
    padding: 24,
  },
  centerText: {
    textAlign: 'center',
  },
  empty: {
    alignItems: 'center',
    gap: 16,
    paddingHorizontal: 16,
    paddingVertical: 32,
  },
  formError: {
    padding: 12,
    borderWidth: 1,
    borderRadius: radius.control,
    borderColor: '#fecaca',
    backgroundColor: '#fef2f2',
  },
  formErrorText: {
    fontFamily: fonts.regular,
    fontSize: 14,
    lineHeight: 20,
    color: '#991b1b',
  },
});
