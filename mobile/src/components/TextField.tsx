import { useState, type ComponentRef, type Ref } from 'react';
import { StyleSheet, Text, TextInput, View, type TextInputProps } from 'react-native';

import { colors, fonts, radius, type } from '../theme';

export type TextFieldRef = ComponentRef<typeof TextInput>;

type Props = TextInputProps & {
  label: string;
  error?: string;
  hint?: string;
  ref?: Ref<TextFieldRef>;
};

export function TextField({ label, error, hint, style, onFocus, onBlur, ref, ...props }: Props) {
  const [focused, setFocused] = useState(false);

  return (
    <View style={styles.wrapper}>
      <Text style={type.label}>{label}</Text>
      <TextInput
        ref={ref}
        accessibilityLabel={label}
        placeholderTextColor={colors.gray400}
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
          style,
        ]}
      />
      {error ? <Text style={styles.error}>{error}</Text> : hint ? <Text style={type.caption}>{hint}</Text> : null}
    </View>
  );
}

const styles = StyleSheet.create({
  wrapper: {
    gap: 6,
  },
  input: {
    minHeight: 44,
    paddingHorizontal: 12,
    paddingVertical: 10,
    borderWidth: 1,
    borderRadius: radius.control,
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
  error: {
    fontFamily: fonts.regular,
    fontSize: 13,
    lineHeight: 18,
    color: colors.danger,
  },
});
