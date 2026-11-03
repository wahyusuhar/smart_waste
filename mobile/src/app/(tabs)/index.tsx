import Ionicons from '@expo/vector-icons/Ionicons';
import { Image } from 'expo-image';
import { useFocusEffect, useRouter } from 'expo-router';
import { useCallback, useState, type ReactNode } from 'react';
import { Pressable, RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { ApiError, SERVER_ERROR_MESSAGE, getDashboard, getReports } from '../../api/client';
import type { DashboardSummary, Report, ReportStatus } from '../../api/types';
import { useAuth } from '../../auth/AuthContext';
import { Button } from '../../components/Button';
import { ReportCard } from '../../components/ReportCard';
import { StatCard } from '../../components/StatCard';
import { EmptyState, ErrorState, FormError, LoadingState } from '../../components/States';
import { WasteTypes } from '../../components/WasteTypes';
import { colors, fonts, type } from '../../theme';
import { firstName } from '../../utils/format';

const illustration = require('../../../assets/welcome.png');

const STATUSES: ReportStatus[] = ['pending', 'in_progress', 'completed', 'rejected'];
const LATEST_COUNT = 5;

export default function DashboardScreen() {
  const { user } = useAuth();
  const router = useRouter();
  const insets = useSafeAreaInsets();

  const [summary, setSummary] = useState<DashboardSummary | null>(null);
  const [latest, setLatest] = useState<Report[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const load = useCallback(async () => {
    try {
      const [dashboard, reports] = await Promise.all([getDashboard(), getReports({ page: 1 })]);
      setSummary(dashboard);
      setLatest(reports.data.slice(0, LATEST_COUNT));
      setError(null);
    } catch (e) {
      // A 401 is handled globally (back to login), so there is nothing to show here.
      if (e instanceof ApiError && e.status === 401) return;
      setError(e instanceof ApiError ? e.message : SERVER_ERROR_MESSAGE);
    }
  }, []);

  // Reload every time the tab is opened, e.g. after sending a new report.
  useFocusEffect(
    useCallback(() => {
      load().finally(() => setLoading(false));
    }, [load]),
  );

  async function refresh() {
    setRefreshing(true);
    await load();
    setRefreshing(false);
  }

  function retry() {
    setLoading(true);
    load().finally(() => setLoading(false));
  }

  return (
    <ScrollView
      style={styles.screen}
      contentContainerStyle={[styles.content, { paddingTop: insets.top + 12 }]}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={refresh} colors={[colors.accent]} tintColor={colors.accent} />}
    >
      {/* The header uses only the signed-in user, so it shows even while the data loads or fails. */}
      <View style={styles.header}>
        <View style={styles.headerText}>
          <Text style={type.title}>Halo, {user ? firstName(user.name) : ''}</Text>
          <Text style={type.caption}>Ringkasan laporan Anda</Text>
        </View>
        <Pressable
          accessibilityRole="button"
          accessibilityLabel="Buka profil"
          hitSlop={8}
          onPress={() => router.navigate('/profile')}
          style={({ pressed }) => pressed && styles.pressed}
        >
          <Ionicons name="person-circle-outline" size={34} color={colors.gray700} />
        </Pressable>
      </View>

      <View style={styles.banner}>
        <View style={styles.bannerText}>
          <Text style={styles.bannerTitle}>Ada tumpukan sampah?</Text>
          <Text style={styles.bannerBody}>Foto, kirim lokasinya, dan pantau penanganannya.</Text>
          <Button title="Buat Laporan" variant="inverse" style={styles.bannerButton} onPress={() => router.push('/reports/new')} />
        </View>
        <Image source={illustration} style={styles.bannerIllustration} contentFit="contain" accessibilityIgnoresInvertColors />
      </View>

      {loading && !summary ? (
        <View style={styles.stateBox}>
          <LoadingState />
        </View>
      ) : !summary ? (
        <View style={styles.stateBox}>
          <ErrorState message={error ?? SERVER_ERROR_MESSAGE} onRetry={retry} />
        </View>
      ) : (
        <>
          {/* Data is still shown when a refresh fails; the error appears above it. */}
          {error ? <FormError message={error} /> : null}

          <View>
            <SectionHeader title="Status laporan">
              <Text style={type.caption}>{summary.total.toLocaleString('id-ID')} total</Text>
            </SectionHeader>
            <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.strip} contentContainerStyle={styles.stripContent}>
              {STATUSES.map((status) => (
                <StatCard key={status} status={status} value={summary[status]} />
              ))}
            </ScrollView>
          </View>
        </>
      )}

      <WasteTypes />

      {summary ? (
        <View>
          <SectionHeader title="Laporan terbaru">
            {latest.length > 0 ? (
              <Pressable accessibilityRole="link" hitSlop={8} onPress={() => router.navigate('/reports')}>
                <Text style={styles.link}>Lihat semua</Text>
              </Pressable>
            ) : null}
          </SectionHeader>
          {latest.length === 0 ? (
            <View style={styles.emptyBox}>
              <EmptyState message="Anda belum pernah membuat laporan." />
            </View>
          ) : (
            <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.strip} contentContainerStyle={styles.stripContent}>
              {latest.map((report) => (
                <ReportCard key={report.id} report={report} onPress={() => router.push(`/reports/${report.id}`)} />
              ))}
            </ScrollView>
          )}
        </View>
      ) : null}
    </ScrollView>
  );
}

function SectionHeader({ title, children }: { title: string; children?: ReactNode }) {
  return (
    <View style={styles.sectionHeader}>
      <Text style={type.heading}>{title}</Text>
      {children}
    </View>
  );
}

const styles = StyleSheet.create({
  screen: {
    flex: 1,
    backgroundColor: colors.white,
  },
  content: {
    paddingHorizontal: 16,
    paddingBottom: 24,
    gap: 24,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 16,
  },
  headerText: {
    flex: 1,
  },
  pressed: {
    opacity: 0.6,
  },
  banner: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    padding: 20,
    borderRadius: 16,
    backgroundColor: colors.accent,
  },
  bannerText: {
    flex: 1,
    gap: 6,
  },
  bannerTitle: {
    fontFamily: fonts.semibold,
    fontSize: 18,
    lineHeight: 24,
    color: colors.white,
  },
  bannerBody: {
    fontFamily: fonts.regular,
    fontSize: 13,
    lineHeight: 18,
    color: colors.accentTint,
  },
  bannerButton: {
    alignSelf: 'flex-start',
    marginTop: 8,
  },
  bannerIllustration: {
    width: 112,
    // Same ratio as the 800 x 560 source image.
    aspectRatio: 800 / 560,
  },
  stateBox: {
    minHeight: 160,
  },
  sectionHeader: {
    flexDirection: 'row',
    alignItems: 'baseline',
    justifyContent: 'space-between',
  },
  // Bleeds to the screen edges so cards scroll out of view instead of being cut at the page padding.
  strip: {
    marginTop: 12,
    marginHorizontal: -16,
  },
  stripContent: {
    paddingHorizontal: 16,
    gap: 12,
  },
  emptyBox: {
    marginTop: 12,
    borderWidth: 1,
    borderColor: colors.gray200,
    borderRadius: 12,
  },
  link: {
    fontFamily: fonts.medium,
    fontSize: 14,
    color: colors.accent,
  },
});
