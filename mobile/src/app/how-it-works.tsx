import Ionicons from '@expo/vector-icons/Ionicons';
import { useRouter } from 'expo-router';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { Button } from '../components/Button';
import { colors, fonts, type } from '../theme';

const STEPS = [
  {
    icon: 'camera-outline',
    title: 'Foto tumpukan sampah',
    text: 'Ambil dari kamera atau galeri. Kategori sampah dideteksi otomatis dan bisa Anda ganti.',
  },
  {
    icon: 'location-outline',
    title: 'Lokasi terisi dari GPS',
    text: 'Koordinat diambil otomatis. Anda bisa menambahkan alamat atau patokan.',
  },
  {
    icon: 'time-outline',
    title: 'Pantau statusnya',
    text: 'Petugas menindaklanjuti laporan Anda: Menunggu, Diproses, lalu Selesai.',
  },
] as const;

export default function HowItWorksScreen() {
  const router = useRouter();

  return (
    <SafeAreaView style={styles.screen} edges={['bottom']}>
      <ScrollView contentContainerStyle={styles.content}>
        {STEPS.map((step, index) => (
          <View key={step.title} style={styles.step}>
            <View style={styles.iconCircle}>
              <Ionicons name={step.icon} size={24} color={colors.accent} />
            </View>
            <View style={styles.stepText}>
              <Text style={styles.stepNumber}>Langkah {index + 1}</Text>
              <Text style={type.heading}>{step.title}</Text>
              <Text style={[type.body, styles.stepBody]}>{step.text}</Text>
            </View>
          </View>
        ))}
      </ScrollView>

      <View style={styles.footer}>
        <Button title="Daftar" onPress={() => router.push('/register')} />
        <View style={styles.switchRow}>
          <Text style={styles.switchText}>Sudah punya akun?</Text>
          <Pressable accessibilityRole="link" hitSlop={8} onPress={() => router.push('/login')}>
            <Text style={styles.switchLink}>Masuk</Text>
          </Pressable>
        </View>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  screen: {
    flex: 1,
    backgroundColor: colors.white,
  },
  content: {
    padding: 24,
    gap: 28,
  },
  step: {
    flexDirection: 'row',
    gap: 16,
  },
  iconCircle: {
    width: 48,
    height: 48,
    borderRadius: 24,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: colors.accentSoft,
  },
  stepText: {
    flex: 1,
    gap: 2,
  },
  stepNumber: {
    fontFamily: fonts.medium,
    fontSize: 13,
    lineHeight: 18,
    color: colors.accent,
  },
  stepBody: {
    marginTop: 2,
    color: colors.gray600,
  },
  footer: {
    gap: 16,
    paddingHorizontal: 24,
    paddingTop: 12,
    paddingBottom: 16,
  },
  switchRow: {
    flexDirection: 'row',
    justifyContent: 'center',
    gap: 4,
  },
  switchText: {
    fontFamily: fonts.regular,
    fontSize: 14,
    color: colors.gray600,
  },
  switchLink: {
    fontFamily: fonts.medium,
    fontSize: 14,
    color: colors.accent,
  },
});
