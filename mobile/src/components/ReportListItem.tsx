import { Pressable, StyleSheet, Text, View } from 'react-native';

import type { Report } from '../api/types';
import { colors, type } from '../theme';
import { formatDateTime } from '../utils/format';
import { CategoryBadge, StatusBadge } from './Badges';

type Props = {
  report: Report;
  onPress: () => void;
};

// Same stacked layout as the report list on the website at phone width.
export function ReportListItem({ report, onPress }: Props) {
  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={`Laporan ${report.description}, ${report.status_label}`}
      onPress={onPress}
      style={({ pressed }) => [styles.item, pressed && styles.pressed]}
    >
      <Text style={type.body} numberOfLines={2}>
        {report.description}
      </Text>
      <View style={styles.meta}>
        <Text style={type.caption}>{formatDateTime(report.created_at)}</Text>
        <CategoryBadge category={report.category} />
        <StatusBadge status={report.status} />
      </View>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  item: {
    paddingHorizontal: 16,
    paddingVertical: 12,
    gap: 8,
    backgroundColor: colors.white,
  },
  pressed: {
    backgroundColor: colors.gray50,
  },
  meta: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    alignItems: 'center',
    columnGap: 12,
    rowGap: 6,
  },
});
