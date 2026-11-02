import { StyleSheet, Text, View } from 'react-native';

import type { ReportStatus } from '../api/types';
import { colors, fonts, radius, type } from '../theme';
import { StatusBadge } from './Badges';

type Props = {
  value: number;
  label?: string;
  status?: ReportStatus;
};

// Same as the stat cards on the web dashboard: status cards show a colored badge, the total card plain text.
export function StatCard({ value, label, status }: Props) {
  return (
    <View style={styles.card}>
      <View style={styles.labelRow}>{status ? <StatusBadge status={status} /> : <Text style={styles.label}>{label}</Text>}</View>
      <Text style={styles.value}>{value.toLocaleString('id-ID')}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  card: {
    flex: 1,
    paddingHorizontal: 16,
    paddingVertical: 12,
    backgroundColor: colors.white,
    borderWidth: 1,
    borderColor: colors.gray200,
    borderRadius: radius.control,
  },
  labelRow: {
    minHeight: 22,
    justifyContent: 'center',
  },
  label: {
    ...type.caption,
    fontFamily: fonts.medium,
  },
  value: {
    marginTop: 8,
    fontFamily: fonts.semibold,
    fontSize: 24,
    lineHeight: 30,
    color: colors.gray900,
    fontVariant: ['tabular-nums'],
  },
});
