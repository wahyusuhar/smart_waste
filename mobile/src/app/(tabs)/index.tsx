import { useFocusEffect, useRouter } from 'expo-router';
import { Fragment, useCallback, useState } from 'react';
import { Pressable, RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';

import { ApiError, SERVER_ERROR_MESSAGE, getDashboard, getReports } from '../../api/client';
import type { DashboardSummary, Report, ReportStatus } from '../../api/types';
import { useAuth } from '../../auth/AuthContext';
import { Button } from '../../components/Button';
import { Card } from '../../components/Card';
import { ReportListItem } from '../../components/ReportListItem';
import { StatCard } from '../../components/StatCard';
import { EmptyState, ErrorState, FormError, LoadingState } from '../../components/States';
import { colors, fonts, type } from '../../theme';
import { firstName } from '../../utils/format';

const STATUS_ROWS: ReportStatus[][] = [
  ['pending', 'in_progress'],
  ['completed', 'rejected'],
];
const LATEST_COUNT = 5;

export default function DashboardScreen() {
  const { user } = useAuth();
  const router = useRouter();

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

  if (loading && !summary) {
    return <LoadingState />;
  }

  if (!summary) {
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

  return (
    <ScrollView
      contentContainerStyle={styles.content}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={refresh} colors={[colors.accent]} tintColor={colors.accent} />}
    >
      <View>
        <Text style={type.title}>Halo, {user ? firstName(user.name) : ''}</Text>
        <Text style={[type.caption, styles.subtitle]}>Ringkasan laporan sampah Anda.</Text>
      </View>

      {/* Data is still shown when a refresh fails; the error appears above it. */}
      {error ? <FormError message={error} /> : null}

      <Button title="Buat Laporan" onPress={() => router.push('/reports/new')} />

      {/* Same arrangement as the web dashboard on a phone: Total full width, then the statuses 2×2. */}
      <View style={styles.stats}>
        <View style={styles.statsRow}>
          <StatCard label="Total laporan" value={summary.total} />
        </View>
        {STATUS_ROWS.map((row) => (
          <View key={row.join()} style={styles.statsRow}>
            {row.map((status) => (
              <StatCard key={status} status={status} value={summary[status]} />
            ))}
          </View>
        ))}
      </View>

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
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  content: {
    padding: 16,
    gap: 16,
  },
  subtitle: {
    marginTop: 2,
  },
  stats: {
    gap: 12,
  },
  statsRow: {
    flexDirection: 'row',
    gap: 12,
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
