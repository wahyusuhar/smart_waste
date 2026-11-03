import { StyleSheet, Text, View } from 'react-native';

import type { ReportStatus } from '../api/types';
import { colors, fonts, radius } from '../theme';
import { StatusBadge } from './Badges';

type Props = {
  status: ReportStatus;
  value: number;
};

// Small fixed-width card for the horizontally scrolling status row on Beranda.
export function StatCard({ status, value }: Props) {
  return (
    <View style={styles.card}>
      <StatusBadge status={status} />
      <Text style={styles.value}>{value.toLocaleString('id-ID')}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  card: {
    width: 120,
    paddingHorizontal: 14,
    paddingVertical: 12,
    gap: 8,
    backgroundColor: colors.white,
    borderWidth: 1,
    borderColor: colors.gray200,
    borderRadius: radius.control,
  },
  value: {
    fontFamily: fonts.semibold,
    fontSize: 24,
    lineHeight: 30,
    color: colors.gray900,
    fontVariant: ['tabular-nums'],
  },
});
