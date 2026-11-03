import Ionicons from '@expo/vector-icons/Ionicons';
import { useState, type ComponentRef, type Ref } from 'react';
import { Pressable, StyleSheet, Text, TextInput, View, type TextInputProps } from 'react-native';

import { colors, fonts, radius, type } from '../theme';

export type TextFieldRef = ComponentRef<typeof TextInput>;

type Props = TextInputProps & {
  label: string;
  error?: string;
  hint?: string;
  // Only used on Masuk and Daftar (PRD §8 exception).
  icon?: keyof typeof Ionicons.glyphMap;
  ref?: Ref<TextFieldRef>;
};

export function TextField({ label, error, hint, icon, secureTextEntry, style, onFocus, onBlur, ref, ...props }: Props) {
  const [focused, setFocused] = useState(false);
  const [revealed, setRevealed] = useState(false);

  return (
    <View style={styles.wrapper}>
      <Text style={type.label}>{label}</Text>
      <View>
        {icon ? <Ionicons name={icon} size={18} color={colors.gray400} style={styles.icon} pointerEvents="none" /> : null}
        <TextInput
          ref={ref}
          accessibilityLabel={label}
          placeholderTextColor={colors.gray400}
          secureTextEntry={secureTextEntry && !revealed}
          {...props}
          onFocus={(event) => {
            setFocused(true);
            onFocus?.(event);
          }}
          onBlur={(event) => {
            setFocused(false);
            onBlur?.(event);
          }}
          style={[
            styles.input,
            { borderColor: error ? colors.danger : focused ? colors.accent : colors.gray300 },
            focused && styles.focused,
            icon && (focused ? styles.withIconFocused : styles.withIcon),
            secureTextEntry && styles.withToggle,
            style,
          ]}
        />
        {secureTextEntry ? (
          <Pressable
            accessibilityRole="button"
            accessibilityLabel={revealed ? 'Sembunyikan password' : 'Tampilkan password'}
            hitSlop={8}
            onPress={() => setRevealed((value) => !value)}
            style={styles.toggle}
          >
            <Ionicons name={revealed ? 'eye-off-outline' : 'eye-outline'} size={20} color={colors.gray500} />
          </Pressable>
        ) : null}
      </View>
      {error ? <Text style={styles.error}>{error}</Text> : hint ? <Text style={type.caption}>{hint}</Text> : null}
    </View>
  );
}

const styles = StyleSheet.create({
  wrapper: {
    gap: 6,
  },
  input: {
    minHeight: 48,
    paddingHorizontal: 12,
    paddingVertical: 10,
    borderWidth: 1,
    borderRadius: radius.button,
    backgroundColor: colors.white,
    fontFamily: fonts.regular,
    fontSize: 15,
    color: colors.gray900,
  },
  // A thicker border on focus; padding shrinks by 1px so the text does not jump.
  focused: {
    borderWidth: 2,
    paddingHorizontal: 11,
  },
  withIcon: {
    paddingLeft: 40,
  },
  withIconFocused: {
    paddingLeft: 39,
  },
  withToggle: {
    paddingRight: 44,
  },
  icon: {
    position: 'absolute',
    left: 13,
    top: 15,
    zIndex: 1,
  },
  toggle: {
    position: 'absolute',
    right: 12,
    top: 14,
  },
  error: {
    fontFamily: fonts.regular,
    fontSize: 13,
    lineHeight: 18,
    color: colors.danger,
  },
});
