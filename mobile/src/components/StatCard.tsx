import Ionicons from '@expo/vector-icons/Ionicons';
import { StyleSheet, Text, View } from 'react-native';

import type { ReportStatus } from '../api/types';
import { colors, fonts, radius, statusIcons, statusLabels } from '../theme';

type Props = {
  status: ReportStatus;
  value: number;
};

// One cell of the 2 x 2 status grid on Beranda, styled like the stat cards on the website.
export function StatCard({ status, value }: Props) {
  return (
    <View style={styles.card}>
      <View style={styles.iconBox}>
        <Ionicons name={statusIcons[status]} size={20} color={colors.accent} />
      </View>
      <View style={styles.text}>
        <Text style={styles.label} numberOfLines={1}>
          {statusLabels[status]}
        </Text>
        <Text style={styles.value}>{value.toLocaleString('id-ID')}</Text>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  card: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    padding: 14,
    backgroundColor: colors.white,
    borderWidth: 1,
    borderColor: colors.gray200,
    borderRadius: radius.card,
  },
  iconBox: {
    width: 44,
    height: 44,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: colors.accentSoft,
  },
  text: {
    flex: 1,
  },
  label: {
    fontFamily: fonts.semibold,
    fontSize: 11,
    letterSpacing: 1,
    textTransform: 'uppercase',
    color: colors.accent,
  },
  value: {
    fontFamily: fonts.extrabold,
    fontSize: 24,
    lineHeight: 30,
    color: colors.gray900,
    fontVariant: ['tabular-nums'],
  },
});
