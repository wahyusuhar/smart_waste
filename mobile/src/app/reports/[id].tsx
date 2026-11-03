import Ionicons from '@expo/vector-icons/Ionicons';
import { Image } from 'expo-image';
import { useLocalSearchParams } from 'expo-router';
import { useCallback, useEffect, useState, type ReactNode } from 'react';
import { RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';

import { ApiError, SERVER_ERROR_MESSAGE, getReport } from '../../api/client';
import type { Report } from '../../api/types';
import { CategoryBadge, StatusBadge } from '../../components/Badges';
import { Card } from '../../components/Card';
import { ErrorState, FormError, LoadingState } from '../../components/States';
import { categoryLabels, colors, fonts, type } from '../../theme';
import { formatCoordinate, formatDateTime, formatPercent, reportCode, reportTitle } from '../../utils/format';

export default function ReportDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();

  const [report, setReport] = useState<Report | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [photoFailed, setPhotoFailed] = useState(false);

  const load = useCallback(
    () =>
      getReport(Number(id)).then(
        (data) => {
          setReport(data);
          setError(null);
        },
        (e: unknown) => {
          // A 401 is handled globally (back to login), so there is nothing to show here.
          if (e instanceof ApiError && e.status === 401) return;
          // 403 and 404 come with their own message ("Data tidak ditemukan.").
          setError(e instanceof ApiError ? e.message : SERVER_ERROR_MESSAGE);
        },
      ),
    [id],
  );

  useEffect(() => {
    load().finally(() => setLoading(false));
  }, [load]);

  async function refresh() {
    setRefreshing(true);
    await load();
    setRefreshing(false);
  }

  if (loading && !report) {
    return <LoadingState />;
  }

  if (!report) {
    return (
      <ErrorState
        message={error ?? SERVER_ERROR_MESSAGE}
        onRetry={() => {
          setLoading(true);
          load().finally(() => setLoading(false));
        }}
      />
    );
  }

  const corrected = report.ai_label !== null && report.category !== null && report.category !== report.ai_label;

  return (
    <ScrollView
      contentContainerStyle={styles.content}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={refresh} colors={[colors.accent]} tintColor={colors.accent} />}
    >
      <View style={styles.header}>
        <Text style={type.title}>{reportTitle(report)}</Text>
        <View style={styles.place}>
          <Ionicons name="location-outline" size={14} color={colors.accent} />
          <Text style={[type.caption, styles.placeText]}>{report.address ?? 'Alamat/patokan tidak diisi'}</Text>
        </View>
        <Text style={type.caption}>
          <Text style={styles.code}>ID #{reportCode(report.id)}</Text> · Dikirim {formatDateTime(report.created_at)}
        </Text>
        <View style={styles.badges}>
          <CategoryBadge category={report.category} />
          <StatusBadge status={report.status} />
        </View>
      </View>

      {error ? <FormError message={error} /> : null}

      <Card>
        {photoFailed ? (
          <View style={[styles.photo, styles.photoFallback]}>
            <Text style={type.caption}>Foto tidak dapat dimuat.</Text>
          </View>
        ) : (
          <Image
            source={{ uri: report.photo_url }}
            style={styles.photo}
            contentFit="contain"
            transition={150}
            accessibilityLabel={`Foto laporan #${reportCode(report.id)}`}
            onError={() => setPhotoFailed(true)}
          />
        )}
        <View style={styles.section}>
          <Text style={type.caption}>Deskripsi</Text>
          <Text style={[type.body, styles.sectionBody]}>{report.description}</Text>
        </View>
      </Card>

      <Card title="Status">
        <DetailRow label="Status">
          <StatusBadge status={report.status} />
        </DetailRow>
        <DetailRow label="Kategori">
          <CategoryBadge category={report.category} />
        </DetailRow>
        <DetailRow label="Terakhir diperbarui" last>
          <Text style={[type.body, styles.value]}>{formatDateTime(report.updated_at)}</Text>
        </DetailRow>
        {report.admin_note ? (
          <View style={styles.section}>
            <Text style={type.caption}>Catatan admin</Text>
            <Text style={[type.body, styles.sectionBody]}>{report.admin_note}</Text>
          </View>
        ) : null}
      </Card>

      <Card title="Lokasi">
        <DetailRow label="Koordinat">
          <Text style={[type.body, styles.value, styles.numbers]}>
            {formatCoordinate(report.latitude)}, {formatCoordinate(report.longitude)}
          </Text>
        </DetailRow>
        <DetailRow label="Alamat/patokan" last>
          <Text style={[type.body, styles.value]}>{report.address ?? '—'}</Text>
        </DetailRow>
      </Card>

      <Card title="Deteksi AI">
        {report.ai_label && report.ai_confidence !== null ? (
          <>
            <DetailRow label="Hasil">
              <Text style={[type.body, styles.value]}>{categoryLabels[report.ai_label]}</Text>
            </DetailRow>
            <DetailRow label="Confidence" last>
              <Text style={[type.body, styles.value, styles.numbers]}>{formatPercent(report.ai_confidence)}</Text>
            </DetailRow>
            {corrected ? (
              <Text style={[type.caption, styles.section]}>Kategori final berbeda dari hasil deteksi AI karena sudah dikoreksi.</Text>
            ) : null}
          </>
        ) : (
          <Text style={[type.body, styles.muted, styles.padded]}>
            Tidak ada hasil deteksi AI. Layanan deteksi tidak tersedia saat laporan dikirim.
          </Text>
        )}
      </Card>

      <Card title="Riwayat status">
        {(report.status_logs ?? []).map((log, index) => (
          <View key={log.id} style={[styles.log, index > 0 && styles.logBorder]}>
            <View style={styles.logHeader}>
              <View style={styles.logStatus}>
                <StatusBadge status={log.to_status} />
                <Text style={type.caption}>{log.from_status_label ? `dari ${log.from_status_label}` : 'Laporan dibuat'}</Text>
              </View>
              <Text style={type.caption}>{formatDateTime(log.created_at)}</Text>
            </View>
            <Text style={type.caption}>oleh {log.changed_by ?? 'akun yang sudah dihapus'}</Text>
            {log.note ? <Text style={type.body}>{log.note}</Text> : null}
          </View>
        ))}
      </Card>
    </ScrollView>
  );
}

function DetailRow({ label, last = false, children }: { label: string; last?: boolean; children: ReactNode }) {
  return (
    <View style={[styles.row, !last && styles.rowBorder]}>
      <Text style={type.caption}>{label}</Text>
      <View style={styles.rowValue}>{children}</View>
    </View>
  );
}

const styles = StyleSheet.create({
  content: {
    padding: 16,
    gap: 16,
  },
  header: {
    gap: 6,
  },
  place: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  placeText: {
    flex: 1,
  },
  code: {
    fontFamily: fonts.bold,
    color: colors.accent,
  },
  badges: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 6,
    marginTop: 2,
  },
  photo: {
    width: '100%',
    height: 260,
    backgroundColor: colors.gray100,
  },
  photoFallback: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  section: {
    paddingHorizontal: 16,
    paddingVertical: 12,
    borderTopWidth: 1,
    borderTopColor: colors.gray100,
  },
  padded: {
    paddingHorizontal: 16,
    paddingVertical: 12,
  },
  sectionBody: {
    marginTop: 4,
  },
  row: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    gap: 16,
    paddingHorizontal: 16,
    paddingVertical: 12,
  },
  rowBorder: {
    borderBottomWidth: 1,
    borderBottomColor: colors.gray100,
  },
  rowValue: {
    flexShrink: 1,
    alignItems: 'flex-end',
  },
  value: {
    textAlign: 'right',
  },
  numbers: {
    fontVariant: ['tabular-nums'],
  },
  muted: {
    color: colors.gray500,
  },
  log: {
    paddingHorizontal: 16,
    paddingVertical: 12,
    gap: 6,
  },
  logBorder: {
    borderTopWidth: 1,
    borderTopColor: colors.gray100,
  },
  logHeader: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'space-between',
    alignItems: 'center',
    gap: 8,
  },
  logStatus: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
});
