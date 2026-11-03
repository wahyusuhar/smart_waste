import Ionicons from '@expo/vector-icons/Ionicons';
import { Image } from 'expo-image';
import { useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import type { Report } from '../api/types';
import { colors, fonts, radius, type } from '../theme';
import { formatDateTime, reportCode, reportTitle } from '../utils/format';
import { CategoryBadge, StatusBadge } from './Badges';

type Props = {
  report: Report;
  onPress: () => void;
};

// One card in Riwayat: thumbnail on the left, the same title, badges and ID as the Beranda cards.
export function ReportListItem({ report, onPress }: Props) {
  const [photoFailed, setPhotoFailed] = useState(false);
  const title = reportTitle(report);

  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={`${title}, ${report.status_label}`}
      onPress={onPress}
      style={({ pressed }) => [styles.card, pressed && styles.pressed]}
    >
      <View style={styles.row}>
        {photoFailed ? (
          <View style={[styles.thumbnail, styles.thumbnailFallback]}>
            <Ionicons name="image-outline" size={22} color={colors.gray400} />
          </View>
        ) : (
          <Image source={{ uri: report.photo_url }} style={styles.thumbnail} contentFit="cover" transition={150} onError={() => setPhotoFailed(true)} />
        )}

        <View style={styles.text}>
          <Text style={styles.title} numberOfLines={1}>
            {title}
          </Text>
          <View style={styles.place}>
            <Ionicons name="location-outline" size={13} color={colors.accent} />
            <Text style={[type.caption, styles.placeText]} numberOfLines={1}>
              {report.address ?? 'Alamat/patokan tidak diisi'}
            </Text>
          </View>
          <View style={styles.badges}>
            <CategoryBadge category={report.category} />
            <StatusBadge status={report.status} />
          </View>
        </View>
      </View>

      <View style={styles.footer}>
        <Text style={type.caption}>{formatDateTime(report.created_at)}</Text>
        <Text style={styles.code}>ID #{reportCode(report.id)}</Text>
      </View>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: colors.white,
    borderWidth: 1,
    borderColor: colors.gray200,
    borderRadius: radius.card,
    overflow: 'hidden',
  },
  pressed: {
    backgroundColor: colors.gray50,
  },
  row: {
    flexDirection: 'row',
    gap: 12,
    padding: 12,
  },
  thumbnail: {
    width: 72,
    height: 72,
    borderRadius: 12,
    backgroundColor: colors.gray100,
  },
  thumbnailFallback: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  text: {
    flex: 1,
    gap: 4,
  },
  title: {
    fontFamily: fonts.bold,
    fontSize: 15,
    lineHeight: 20,
    color: colors.gray900,
  },
  place: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  placeText: {
    flex: 1,
  },
  badges: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    alignItems: 'center',
    gap: 6,
    marginTop: 2,
  },
  footer: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderTopWidth: 1,
    borderTopColor: colors.gray100,
  },
  code: {
    fontFamily: fonts.bold,
    fontSize: 12,
    color: colors.accent,
  },
});
