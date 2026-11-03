import Ionicons from '@expo/vector-icons/Ionicons';
import { useFocusEffect, useRouter } from 'expo-router';
import { Fragment, useCallback, useState } from 'react';
import { Pressable, RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { ApiError, SERVER_ERROR_MESSAGE, getDashboard, getReports } from '../../api/client';
import type { DashboardSummary, Report, ReportStatus } from '../../api/types';
import { useAuth } from '../../auth/AuthContext';
import { Button } from '../../components/Button';
import { Card } from '../../components/Card';
import { FocusAwareStatusBar } from '../../components/FocusAwareStatusBar';
import { ReportListItem } from '../../components/ReportListItem';
import { StatCard } from '../../components/StatCard';
import { EmptyState, ErrorState, FormError, LoadingState } from '../../components/States';
import { WasteTypes } from '../../components/WasteTypes';
import { colors, fonts, type } from '../../theme';
import { firstName } from '../../utils/format';

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
      contentContainerStyle={styles.scroll}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={refresh} colors={[colors.accent]} tintColor={colors.white} />}
    >
      <FocusAwareStatusBar style="light" />

      {/* The header uses only the signed-in user, so it shows even while the data loads or fails. */}
      <View style={[styles.header, { paddingTop: insets.top + 16 }]}>
        <View style={styles.headerText}>
          <Text style={styles.greeting}>Halo, {user ? firstName(user.name) : ''}</Text>
          <Text style={styles.headerSubtitle}>Ringkasan laporan Anda</Text>
        </View>
        <Pressable
          accessibilityRole="button"
          accessibilityLabel="Buka profil"
          hitSlop={8}
          onPress={() => router.navigate('/profile')}
          style={({ pressed }) => pressed && styles.pressed}
        >
          <Ionicons name="person-circle-outline" size={32} color={colors.white} />
        </Pressable>
      </View>

      <View style={styles.body}>
        <Card>
          <View style={styles.callToAction}>
            <Text style={type.heading}>Ada tumpukan sampah?</Text>
            <Button title="Buat Laporan" onPress={() => router.push('/reports/new')} />
          </View>
        </Card>

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
              <View style={styles.sectionHeader}>
                <Text style={type.heading}>Status laporan</Text>
                <Text style={type.caption}>{summary.total.toLocaleString('id-ID')} total</Text>
              </View>
              <ScrollView
                horizontal
                showsHorizontalScrollIndicator={false}
                style={styles.statusStrip}
                contentContainerStyle={styles.statusStripContent}
              >
                {STATUSES.map((status) => (
                  <StatCard key={status} status={status} value={summary[status]} />
                ))}
              </ScrollView>
            </View>
          </>
        )}

        <WasteTypes />

        {summary ? (
          <Card title="Laporan terbaru">
            {latest.length === 0 ? (
              <EmptyState message="Anda belum pernah membuat laporan." />
            ) : (
              <>
                {latest.map((report, index) => (
                  <Fragment key={report.id}>
                    {index > 0 ? <View style={styles.separator} /> : null}
                    <ReportListItem report={report} onPress={() => router.push(`/reports/${report.id}`)} />
                  </Fragment>
                ))}
                <Pressable accessibilityRole="link" onPress={() => router.navigate('/reports')} style={styles.footerLink}>
                  <Text style={styles.footerLinkText}>Lihat semua riwayat</Text>
                </Pressable>
              </>
            )}
          </Card>
        ) : null}
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  scroll: {
    paddingBottom: 16,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 16,
    paddingHorizontal: 16,
    paddingBottom: 24,
    backgroundColor: colors.accent,
  },
  headerText: {
    flex: 1,
  },
  greeting: {
    fontFamily: fonts.semibold,
    fontSize: 22,
    lineHeight: 30,
    color: colors.white,
  },
  headerSubtitle: {
    marginTop: 2,
    fontFamily: fonts.regular,
    fontSize: 14,
    lineHeight: 20,
    color: colors.accentTint,
  },
  pressed: {
    opacity: 0.7,
  },
  body: {
    padding: 16,
    gap: 20,
  },
  callToAction: {
    padding: 16,
    gap: 12,
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
  statusStrip: {
    marginTop: 12,
    marginHorizontal: -16,
  },
  statusStripContent: {
    paddingHorizontal: 16,
    gap: 8,
  },
  separator: {
    height: 1,
    backgroundColor: colors.gray100,
  },
  footerLink: {
    paddingVertical: 12,
    alignItems: 'center',
    borderTopWidth: 1,
    borderTopColor: colors.gray200,
  },
  footerLinkText: {
    fontFamily: fonts.medium,
    fontSize: 14,
    color: colors.accent,
  },
});
