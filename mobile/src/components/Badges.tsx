import { StyleSheet, Text, View } from 'react-native';

import type { ReportCategory, ReportStatus } from '../api/types';
import { categoryLabels, colors, fonts, radius, statusColors, statusLabels } from '../theme';

export function StatusBadge({ status }: { status: ReportStatus }) {
  const palette = statusColors[status];

  return (
    <View style={[styles.badge, { backgroundColor: palette.background, borderColor: palette.border }]}>
      <Text style={[styles.text, { color: palette.text }]}>{statusLabels[status]}</Text>
    </View>
  );
}

// Neutral on purpose: in this app color is reserved for report status.
export function CategoryBadge({ category }: { category: ReportCategory | null }) {
  if (!category) {
    return <Text style={styles.empty}>Belum ditentukan</Text>;
  }

  return (
    <View style={[styles.badge, styles.category]}>
      <Text style={[styles.text, { color: colors.gray700 }]}>{categoryLabels[category]}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  badge: {
    alignSelf: 'flex-start',
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderWidth: 1,
    borderRadius: radius.badge,
  },
  category: {
    backgroundColor: colors.gray50,
    borderColor: colors.gray200,
  },
  text: {
    fontFamily: fonts.medium,
    fontSize: 12,
    lineHeight: 16,
  },
  empty: {
    fontFamily: fonts.regular,
    fontSize: 13,
    color: colors.gray400,
  },
});
