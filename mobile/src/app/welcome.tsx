import { Image } from 'expo-image';
import { useRouter } from 'expo-router';
import { StyleSheet, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { Button } from '../components/Button';
import { FocusAwareStatusBar } from '../components/FocusAwareStatusBar';
import { colors, fonts } from '../theme';

// Original illustration drawn by docs/welcome_illustration.py, in colors meant for a teal background.
const illustration = require('../../assets/welcome.png');

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

        <View style={styles.actions}>
          <Button title="Mulai" onPress={() => router.push('/how-it-works')} />
          <Button title="Saya sudah punya akun" variant="secondary" onPress={() => router.push('/login')} />
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
  hero: {
    flex: 55,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 24,
    paddingBottom: SHEET_RADIUS,
  },
  illustration: {
    width: '100%',
    // Same ratio as the 800 x 560 source image.
    aspectRatio: 800 / 560,
  },
  sheet: {
    flex: 45,
    justifyContent: 'space-between',
    gap: 24,
    marginTop: -SHEET_RADIUS,
    paddingHorizontal: 24,
    paddingTop: 28,
    backgroundColor: colors.white,
    borderTopLeftRadius: SHEET_RADIUS,
    borderTopRightRadius: SHEET_RADIUS,
  },
  title: {
    fontFamily: fonts.semibold,
    fontSize: 24,
    lineHeight: 32,
    color: colors.gray900,
  },
  description: {
    marginTop: 8,
    fontFamily: fonts.regular,
    fontSize: 15,
    lineHeight: 22,
    color: colors.gray600,
  },
  actions: {
    gap: 12,
  },
});
