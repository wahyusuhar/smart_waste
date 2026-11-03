import { Image } from 'expo-image';
import { useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import type { Report } from '../api/types';
import { colors, fonts, type } from '../theme';
import { formatDateTime } from '../utils/format';
import { StatusBadge } from './Badges';

type Props = {
  report: Report;
  onPress: () => void;
};

// Photo card for the horizontally scrolling "Laporan terbaru" row on Beranda.
export function ReportCard({ report, onPress }: Props) {
  const [photoFailed, setPhotoFailed] = useState(false);

  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={`Laporan ${report.description}, ${report.status_label}`}
      onPress={onPress}
      style={({ pressed }) => [styles.card, pressed && styles.pressed]}
    >
      {photoFailed ? (
        <View style={[styles.photo, styles.photoFallback]}>
          <Text style={type.caption}>Foto tidak dapat dimuat</Text>
        </View>
      ) : (
        <Image source={{ uri: report.photo_url }} style={styles.photo} contentFit="cover" transition={150} onError={() => setPhotoFailed(true)} />
      )}
      <View style={styles.body}>
        <Text style={styles.description} numberOfLines={1}>
          {report.description}
        </Text>
        <StatusBadge status={report.status} />
        <Text style={type.caption}>{formatDateTime(report.created_at)}</Text>
      </View>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  card: {
    width: 220,
    overflow: 'hidden',
    backgroundColor: colors.white,
    borderWidth: 1,
    borderColor: colors.gray200,
    borderRadius: 12,
  },
  pressed: {
    opacity: 0.8,
  },
  photo: {
    width: '100%',
    height: 124,
    backgroundColor: colors.gray100,
  },
  photoFallback: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  body: {
    padding: 12,
    gap: 8,
  },
  description: {
    fontFamily: fonts.medium,
    fontSize: 14,
    lineHeight: 20,
    color: colors.gray900,
  },
});
