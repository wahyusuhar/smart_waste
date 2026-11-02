import { useState } from 'react';
import { Alert, ScrollView, StyleSheet, Text, View } from 'react-native';

import { useAuth } from '../../auth/AuthContext';
import { Button } from '../../components/Button';
import { Card } from '../../components/Card';
import { colors, type } from '../../theme';

export default function ProfileScreen() {
  const { user, signOut } = useAuth();
  const [signingOut, setSigningOut] = useState(false);

  function confirmSignOut() {
    Alert.alert('Keluar', 'Keluar dari akun ini?', [
      { text: 'Batal', style: 'cancel' },
      {
        text: 'Keluar',
        style: 'destructive',
        onPress: async () => {
          setSigningOut(true);
          // After signOut the root layout returns to the login screen by itself.
          await signOut();
        },
      },
    ]);
  }

  return (
    <ScrollView contentContainerStyle={styles.content}>
      <Card title="Akun">
        <Row label="Nama" value={user?.name} />
        <Row label="Email" value={user?.email} />
        <Row label="Nomor HP" value={user?.phone ?? '—'} last />
      </Card>

      <Button title="Keluar" variant="danger" loading={signingOut} loadingTitle="Keluar…" onPress={confirmSignOut} />
    </ScrollView>
  );
}

function Row({ label, value, last = false }: { label: string; value?: string; last?: boolean }) {
  return (
    <View style={[styles.row, !last && styles.rowBorder]}>
      <Text style={type.caption}>{label}</Text>
      <Text style={[type.body, styles.value]}>{value}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  content: {
    padding: 16,
    gap: 16,
  },
  row: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    gap: 16,
    paddingHorizontal: 16,
    paddingVertical: 12,
  },
  rowBorder: {
    borderBottomWidth: 1,
    borderBottomColor: colors.gray100,
  },
  value: {
    flexShrink: 1,
    textAlign: 'right',
  },
});
