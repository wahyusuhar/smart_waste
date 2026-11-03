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

// Photo card for the horizontally scrolling "Laporan terbaru" row on Beranda, styled like the report cards
// on the website's opening page. Only the signed-in user's own reports reach it.
export function ReportCard({ report, onPress }: Props) {
  const [photoFailed, setPhotoFailed] = useState(false);
  const title = reportTitle(report);

  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={`${title}, ${report.status_label}`}
      onPress={onPress}
      style={({ pressed }) => [styles.card, pressed && styles.pressed]}
    >
      <View>
        {photoFailed ? (
          <View style={[styles.photo, styles.photoFallback]}>
            <Text style={type.caption}>Foto tidak dapat dimuat</Text>
          </View>
        ) : (
          <Image source={{ uri: report.photo_url }} style={styles.photo} contentFit="cover" transition={150} onError={() => setPhotoFailed(true)} />
        )}
        <View style={styles.overlay}>
          <CategoryBadge category={report.category} onPhoto />
          <StatusBadge status={report.status} />
        </View>
      </View>

      <View style={styles.body}>
        <View style={styles.place}>
          <Ionicons name="location-outline" size={14} color={colors.accent} />
          <Text style={[type.caption, styles.placeText]} numberOfLines={1}>
            {report.address ?? 'Alamat/patokan tidak diisi'}
          </Text>
        </View>
        <Text style={styles.title} numberOfLines={1}>
          {title}
        </Text>
        <Text style={styles.description} numberOfLines={2}>
          {report.description}
        </Text>
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
    width: 270,
    overflow: 'hidden',
    backgroundColor: colors.white,
    borderWidth: 1,
    borderColor: colors.gray200,
    borderRadius: radius.card,
  },
  pressed: {
    opacity: 0.8,
  },
  photo: {
    width: '100%',
    aspectRatio: 16 / 9,
    backgroundColor: colors.gray100,
  },
  photoFallback: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  overlay: {
    position: 'absolute',
    top: 10,
    left: 10,
    right: 10,
    flexDirection: 'row',
    justifyContent: 'space-between',
    gap: 6,
  },
  body: {
    paddingHorizontal: 14,
    paddingTop: 12,
    paddingBottom: 12,
    gap: 4,
  },
  place: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  placeText: {
    flex: 1,
  },
  title: {
    fontFamily: fonts.bold,
    fontSize: 15,
    lineHeight: 20,
    color: colors.gray900,
  },
  // Two lines are always reserved so cards in the row keep the same height.
  description: {
    minHeight: 36,
    fontFamily: fonts.regular,
    fontSize: 13,
    lineHeight: 18,
    color: colors.gray600,
  },
  footer: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 14,
    paddingVertical: 10,
    borderTopWidth: 1,
    borderTopColor: colors.gray100,
  },
  code: {
    fontFamily: fonts.bold,
    fontSize: 12,
    color: colors.accent,
  },
});
