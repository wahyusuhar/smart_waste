import { Image } from 'expo-image';
import { useRouter } from 'expo-router';
import { StyleSheet, Text, View, useWindowDimensions } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { Button } from '../components/Button';
import { FocusAwareStatusBar } from '../components/FocusAwareStatusBar';
import { colors, fonts } from '../theme';

// Illustration from undraw.co (free license, see README).
const illustration = require('../../assets/welcome.png');

export default function WelcomeScreen() {
  const router = useRouter();
  const { width, height } = useWindowDimensions();

  // A plain teal block whose bottom edge is one wide arc: the block is wider than the screen and
  // its bottom corners are rounded by half that width, so only the middle of the curve is visible.
  const blockWidth = width * 1.6;
  const blockHeight = height * 0.5;

  return (
    <View style={styles.screen}>
      <FocusAwareStatusBar style="light" />
      <View
        style={[
          styles.block,
          {
            width: blockWidth,
            height: blockHeight,
            left: (width - blockWidth) / 2,
            borderBottomLeftRadius: blockWidth / 2,
            borderBottomRightRadius: blockWidth / 2,
          },
        ]}
      />

      <SafeAreaView style={styles.content}>
        <View style={[styles.illustrationArea, { height: blockHeight * 0.85 }]}>
          <Image source={illustration} style={styles.illustration} contentFit="contain" accessibilityIgnoresInvertColors />
        </View>

        <View style={styles.body}>
          <View>
            <Text style={styles.title}>Laporkan tumpukan sampah di sekitar Anda</Text>
            <Text style={styles.description}>Kirim foto dan lokasi GPS, kategori sampah dideteksi otomatis.</Text>
          </View>

          <View style={styles.actions}>
            <Button title="Masuk" onPress={() => router.push('/login')} />
            <Button title="Daftar" variant="secondary" onPress={() => router.push('/register')} />
          </View>
        </View>
      </SafeAreaView>
    </View>
  );
}

const styles = StyleSheet.create({
  screen: {
    flex: 1,
    backgroundColor: colors.white,
  },
  block: {
    position: 'absolute',
    top: 0,
    backgroundColor: colors.accent,
  },
  content: {
    flex: 1,
  },
  illustrationArea: {
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 32,
  },
  illustration: {
    width: '100%',
    height: '80%',
  },
  body: {
    flex: 1,
    justifyContent: 'space-between',
    paddingHorizontal: 24,
    paddingTop: 16,
    paddingBottom: 24,
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
