import { ActivityIndicator, Pressable, StyleSheet, Text, type PressableProps, type StyleProp, type ViewStyle } from 'react-native';

import { colors, fonts, radius } from '../theme';

type Props = Omit<PressableProps, 'style' | 'children'> & {
  title: string;
  variant?: 'primary' | 'secondary' | 'danger';
  loading?: boolean;
  loadingTitle?: string;
  style?: StyleProp<ViewStyle>;
};

export function Button({ title, variant = 'primary', loading = false, loadingTitle, disabled, style, ...props }: Props) {
  const isDisabled = disabled || loading;
  const palette = variants[variant];

  return (
    <Pressable
      accessibilityRole="button"
      accessibilityState={{ disabled: isDisabled, busy: loading }}
      disabled={isDisabled}
      style={({ pressed }) => [
        styles.base,
        { backgroundColor: pressed ? palette.pressed : palette.background, borderColor: palette.border },
        isDisabled && styles.disabled,
        style,
      ]}
      {...props}
    >
      {loading && <ActivityIndicator size="small" color={palette.text} />}
      <Text style={[styles.text, { color: palette.text }]}>{loading && loadingTitle ? loadingTitle : title}</Text>
    </Pressable>
  );
}

const variants = {
  primary: { background: colors.accent, pressed: colors.accentPressed, border: colors.accent, text: colors.white },
  secondary: { background: colors.white, pressed: colors.gray50, border: colors.gray300, text: colors.gray700 },
  danger: { background: colors.white, pressed: colors.gray50, border: colors.gray300, text: colors.danger },
};

const styles = StyleSheet.create({
  base: {
    minHeight: 44,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    paddingHorizontal: 16,
    borderRadius: radius.control,
    borderWidth: 1,
  },
  text: {
    fontFamily: fonts.medium,
    fontSize: 15,
  },
  disabled: {
    opacity: 0.6,
  },
});
