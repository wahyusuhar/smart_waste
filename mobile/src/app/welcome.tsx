import { Image } from 'expo-image';
import { useRouter } from 'expo-router';
import { StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { Button } from '../components/Button';
import { colors, fonts } from '../theme';

// Original illustration drawn by docs/welcome_illustration.py.
const illustration = require('../../assets/welcome.png');

export default function WelcomeScreen() {
  const router = useRouter();

  return (
    <SafeAreaView style={styles.screen}>
      <View style={styles.illustrationArea}>
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
  );
}

const styles = StyleSheet.create({
  screen: {
    flex: 1,
    backgroundColor: colors.white,
  },
  illustrationArea: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 24,
  },
  illustration: {
    width: '100%',
    // Same ratio as the 800 x 560 source image.
    aspectRatio: 800 / 560,
  },
  body: {
    gap: 32,
    paddingHorizontal: 24,
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
