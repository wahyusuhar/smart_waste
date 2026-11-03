import Ionicons from '@expo/vector-icons/Ionicons';
import { StyleSheet, Text, View } from 'react-native';

import type { ReportCategory, ReportStatus } from '../api/types';
import { categoryLabels, colors, fonts, radius, statusColors, statusIcons, statusLabels } from '../theme';

export function StatusBadge({ status }: { status: ReportStatus }) {
  const palette = statusColors[status];

  return (
    <View style={[styles.badge, { backgroundColor: palette.background, borderColor: palette.border }]}>
      <Ionicons name={statusIcons[status]} size={13} color={palette.text} />
      <Text style={[styles.text, { color: palette.text }]}>{statusLabels[status]}</Text>
    </View>
  );
}

// Neutral on purpose: in this app color is reserved for report status. On a photo it is solid white
// so it stays readable whatever the picture behind it.
export function CategoryBadge({ category, onPhoto = false }: { category: ReportCategory | null; onPhoto?: boolean }) {
  if (!category && !onPhoto) {
    return <Text style={styles.empty}>Belum ditentukan</Text>;
  }

  return (
    <View style={[styles.badge, onPhoto ? styles.onPhoto : styles.category]}>
      <Ionicons name="sparkles-outline" size={13} color={colors.accent} />
      <Text style={[styles.text, { color: colors.gray700 }]}>{category ? categoryLabels[category] : 'Belum ditentukan'}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  badge: {
    alignSelf: 'flex-start',
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderWidth: 1,
    borderRadius: radius.pill,
  },
  category: {
    backgroundColor: colors.gray50,
    borderColor: colors.gray200,
  },
  onPhoto: {
    backgroundColor: colors.white,
    borderColor: colors.white,
  },
  text: {
    fontFamily: fonts.semibold,
    fontSize: 12,
    lineHeight: 16,
  },
  empty: {
    fontFamily: fonts.regular,
    fontSize: 13,
    color: colors.gray400,
  },
});
