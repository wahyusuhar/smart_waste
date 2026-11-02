import { StyleSheet, View } from 'react-native';

import { EmptyState } from './States';

/**
 * Temporary stand-in for screens built in Tahap B of Fase 4 (Riwayat, Detail, Buat Laporan),
 * so that navigation from the dashboard can already be tried. Removed in Tahap B.
 */
export function NotBuiltYet() {
  return (
    <View style={styles.container}>
      <EmptyState message="Layar ini dikerjakan di tahap berikutnya." />
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    justifyContent: 'center',
  },
});
