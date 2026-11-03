import { Image } from 'expo-image';
import { useRouter } from 'expo-router';
import { StyleSheet, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { Button } from '../components/Button';
import { FocusAwareStatusBar } from '../components/FocusAwareStatusBar';
import { colors, fonts } from '../theme';

// Original illustration drawn by docs/welcome_illustration.py, in colors meant for a teal background.
const illustration = require('../../assets/welcome.png');

const STEPS = ['Foto tumpukan sampah', 'Tandai lokasinya', 'Pantau statusnya'];

export default function WelcomeScreen() {
  const router = useRouter();
  const insets = useSafeAreaInsets();

  return (
    <View style={styles.screen}>
      <FocusAwareStatusBar style="light" />

      <View style={[styles.hero, { paddingTop: insets.top + 16 }]}>
        <Image source={illustration} style={styles.illustration} contentFit="contain" accessibilityIgnoresInvertColors />
      </View>

      {/* The white sheet overlaps the teal area a little, so its rounded corners show against it. */}
      <View style={[styles.sheet, { paddingBottom: insets.bottom + 24 }]}>
        <View>
          <Text style={styles.title}>Laporkan tumpukan sampah di sekitar Anda</Text>
          <Text style={styles.description}>Kirim foto dan lokasi GPS, kategori sampah dideteksi otomatis.</Text>
        </View>

        <View style={styles.steps}>
          {STEPS.map((step, index) => (
            <View key={step} style={styles.step}>
              <View style={styles.stepNumber}>
                <Text style={styles.stepNumberText}>{index + 1}</Text>
              </View>
              <Text style={styles.stepText}>{step}</Text>
            </View>
          ))}
        </View>

        <View style={styles.actions}>
          <Button title="Masuk ke Akun" icon="log-in-outline" onPress={() => router.push('/login')} />
          <Button title="Daftar Akun Baru" icon="person-add-outline" variant="soft" onPress={() => router.push('/register')} />
        </View>
      </View>
    </View>
  );
}

const SHEET_RADIUS = 24;

const styles = StyleSheet.create({
  screen: {
    flex: 1,
    backgroundColor: colors.accent,
  },
  // Takes whatever height the sheet leaves, so everything fits on one screen.
  hero: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 24,
    paddingBottom: SHEET_RADIUS + 8,
  },
  illustration: {
    flex: 1,
    width: '100%',
    maxHeight: 260,
  },
  sheet: {
    gap: 20,
    marginTop: -SHEET_RADIUS,
    paddingHorizontal: 24,
    paddingTop: 28,
    backgroundColor: colors.white,
    borderTopLeftRadius: SHEET_RADIUS,
    borderTopRightRadius: SHEET_RADIUS,
  },
  title: {
    fontFamily: fonts.extrabold,
    fontSize: 26,
    lineHeight: 32,
    letterSpacing: -0.5,
    color: colors.gray900,
  },
  description: {
    marginTop: 8,
    fontFamily: fonts.regular,
    fontSize: 15,
    lineHeight: 22,
    color: colors.gray600,
  },
  steps: {
    gap: 10,
  },
  step: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  stepNumber: {
    width: 32,
    height: 32,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: colors.accentSoft,
  },
  stepNumberText: {
    fontFamily: fonts.bold,
    fontSize: 14,
    color: colors.accent,
  },
  stepText: {
    fontFamily: fonts.semibold,
    fontSize: 15,
    color: colors.gray900,
  },
  actions: {
    gap: 12,
  },
});
