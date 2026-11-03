import { useFocusEffect, useRouter } from 'expo-router';
import { useCallback, useRef, useState } from 'react';
import { ActivityIndicator, FlatList, RefreshControl, StyleSheet, View } from 'react-native';

import { ApiError, SERVER_ERROR_MESSAGE, getReports } from '../../api/client';
import type { Report } from '../../api/types';
import { Button } from '../../components/Button';
import { ReportListItem } from '../../components/ReportListItem';
import { EmptyState, ErrorState, FormError, LoadingState } from '../../components/States';
import { colors } from '../../theme';

function errorMessage(error: unknown): string | null {
  // A 401 is handled globally (back to login), so there is nothing to show here.
  if (error instanceof ApiError && error.status === 401) return null;
  return error instanceof ApiError ? error.message : SERVER_ERROR_MESSAGE;
}

export default function ReportsScreen() {
  const router = useRouter();

  const [reports, setReports] = useState<Report[] | null>(null);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [loadingMore, setLoadingMore] = useState(false);
  const [moreError, setMoreError] = useState<string | null>(null);
  // Guards against firing the next page twice while one is still loading.
  const fetchingMore = useRef(false);

  const loadFirstPage = useCallback(async () => {
    try {
      const result = await getReports({ page: 1 });
      setReports(result.data);
      setPage(result.meta.current_page);
      setLastPage(result.meta.last_page);
      setError(null);
      setMoreError(null);
    } catch (e) {
      setError(errorMessage(e));
    }
  }, []);

  // Back to page 1 every time the tab is opened, so a report sent a moment ago is at the top.
  useFocusEffect(
    useCallback(() => {
      loadFirstPage().finally(() => setLoading(false));
    }, [loadFirstPage]),
  );

  async function refresh() {
    setRefreshing(true);
    await loadFirstPage();
    setRefreshing(false);
  }

  async function loadMore() {
    if (fetchingMore.current || page >= lastPage || !reports) return;

    fetchingMore.current = true;
    setLoadingMore(true);
    setMoreError(null);
    try {
      const result = await getReports({ page: page + 1 });
      // Skip items already shown, in case a new report shifted the pages in between.
      setReports((current) => {
        const seen = new Set((current ?? []).map((report) => report.id));
        return [...(current ?? []), ...result.data.filter((report) => !seen.has(report.id))];
      });
      setPage(result.meta.current_page);
      setLastPage(result.meta.last_page);
    } catch (e) {
      setMoreError(errorMessage(e));
    } finally {
      fetchingMore.current = false;
      setLoadingMore(false);
    }
  }

  if (loading && !reports) {
    return <LoadingState />;
  }

  if (!reports) {
    return (
      <ErrorState
        message={error ?? SERVER_ERROR_MESSAGE}
        onRetry={() => {
          setLoading(true);
          loadFirstPage().finally(() => setLoading(false));
        }}
      />
    );
  }

  return (
    <FlatList
      data={reports}
      keyExtractor={(report) => String(report.id)}
      renderItem={({ item }) => <ReportListItem report={item} onPress={() => router.push(`/reports/${item.id}`)} />}
      ItemSeparatorComponent={Separator}
      contentContainerStyle={reports.length === 0 ? styles.emptyContent : styles.content}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={refresh} colors={[colors.accent]} tintColor={colors.accent} />}
      onEndReached={loadMore}
      onEndReachedThreshold={0.5}
      ListHeaderComponent={
        error ? (
          <View style={styles.banner}>
            <FormError message={error} />
          </View>
        ) : undefined
      }
      ListEmptyComponent={
        <EmptyState message="Anda belum pernah membuat laporan.">
          <Button title="Buat Laporan" onPress={() => router.push('/reports/new')} />
        </EmptyState>
      }
      ListFooterComponent={
        loadingMore ? (
          <ActivityIndicator style={styles.footer} color={colors.accent} />
        ) : moreError ? (
          <View style={styles.banner}>
            <FormError message={moreError} />
            <Button title="Coba lagi" variant="secondary" onPress={loadMore} />
          </View>
        ) : undefined
      }
    />
  );
}

function Separator() {
  return <View style={styles.separator} />;
}

const styles = StyleSheet.create({
  content: {
    padding: 16,
  },
  emptyContent: {
    flexGrow: 1,
    justifyContent: 'center',
  },
  // The list content already has 16 px side padding.
  banner: {
    paddingVertical: 12,
    gap: 12,
  },
  separator: {
    height: 12,
  },
  footer: {
    paddingVertical: 16,
  },
});
