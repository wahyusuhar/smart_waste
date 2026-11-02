import { Pressable, StyleSheet, Text, View } from 'react-native';

import type { ReportCategory } from '../api/types';
import { categoryLabels, colors, fonts, radius, type } from '../theme';

const CATEGORIES: ReportCategory[] = ['organic', 'plastic', 'paper'];

type Props = {
  value: ReportCategory | null;
  onChange: (category: ReportCategory) => void;
  error?: string;
  disabled?: boolean;
};

// Three options fit on one row, so a segmented choice is quicker than a dropdown here.
export function CategoryPicker({ value, onChange, error, disabled = false }: Props) {
  return (
    <View style={styles.wrapper}>
      <Text style={type.label}>Kategori</Text>
      <View accessibilityRole="radiogroup" style={styles.row}>
        {CATEGORIES.map((category) => {
          const selected = value === category;
          return (
            <Pressable
              key={category}
              accessibilityRole="radio"
              accessibilityState={{ selected, disabled }}
              disabled={disabled}
              onPress={() => onChange(category)}
              style={({ pressed }) => [
                styles.option,
                selected && styles.selected,
                pressed && !selected && styles.pressed,
                disabled && styles.disabled,
              ]}
            >
              <Text style={[styles.text, selected && styles.selectedText]}>{categoryLabels[category]}</Text>
            </Pressable>
          );
        })}
      </View>
      {error ? <Text style={styles.error}>{error}</Text> : null}
    </View>
  );
}

const styles = StyleSheet.create({
  wrapper: {
    gap: 6,
  },
  row: {
    flexDirection: 'row',
    gap: 8,
  },
  option: {
    flex: 1,
    minHeight: 44,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 8,
    borderWidth: 1,
    borderColor: colors.gray300,
    borderRadius: radius.control,
    backgroundColor: colors.white,
  },
  selected: {
    borderColor: colors.accent,
    backgroundColor: colors.accentSoft,
  },
  pressed: {
    backgroundColor: colors.gray50,
  },
  disabled: {
    opacity: 0.6,
  },
  text: {
    fontFamily: fonts.medium,
    fontSize: 14,
    color: colors.gray700,
  },
  selectedText: {
    color: colors.accent,
  },
  error: {
    fontFamily: fonts.regular,
    fontSize: 13,
    lineHeight: 18,
    color: colors.danger,
  },
});
