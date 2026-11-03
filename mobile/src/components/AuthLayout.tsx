import { Image } from 'expo-image';
import type { ReactNode } from 'react';
import { KeyboardAvoidingView, Platform, ScrollView, StyleSheet, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { colors, fonts, radius } from '../theme';
import { FocusAwareStatusBar } from './FocusAwareStatusBar';

const logo = require('../../assets/icon_screen.png');
// Original illustration drawn by docs/welcome_illustration.py, in colors meant for a teal background.
const illustration = require('../../assets/welcome.png');

// Masuk and Daftar: teal block with the logo and illustration, the form card overlapping its bottom edge.
export function AuthLayout({ children }: { children: ReactNode }) {
  const insets = useSafeAreaInsets();

  return (
    <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <FocusAwareStatusBar style="light" />
      <ScrollView
        style={styles.screen}
        contentContainerStyle={{ paddingBottom: insets.bottom + 24 }}
        keyboardShouldPersistTaps="handled"
      >
        <View style={[styles.hero, { paddingTop: insets.top + 16 }]}>
          <View style={styles.brand}>
            {/* White tile: the logo's dark blue would disappear on teal. */}
            <View style={styles.logoTile}>
              <Image source={logo} style={styles.logo} contentFit="contain" accessibilityIgnoresInvertColors />
            </View>
            <Text style={styles.brandName}>Smart Waste</Text>
          </View>
          <Image source={illustration} style={styles.illustration} contentFit="contain" accessibilityIgnoresInvertColors />
        </View>

        <View style={styles.card}>{children}</View>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const OVERLAP = 40;

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  screen: {
    flex: 1,
    backgroundColor: colors.gray50,
  },
  hero: {
    paddingHorizontal: 20,
    paddingBottom: OVERLAP + 16,
    backgroundColor: colors.accent,
    borderBottomLeftRadius: 32,
    borderBottomRightRadius: 32,
  },
  brand: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  logoTile: {
    width: 36,
    height: 36,
    padding: 4,
    borderRadius: 10,
    backgroundColor: colors.white,
  },
  logo: {
    flex: 1,
  },
  brandName: {
    fontFamily: fonts.bold,
    fontSize: 16,
    color: colors.white,
  },
  illustration: {
    alignSelf: 'center',
    width: '70%',
    // Same ratio as the 800 x 560 source image.
    aspectRatio: 800 / 560,
    marginTop: 8,
  },
  card: {
    marginTop: -OVERLAP,
    marginHorizontal: 16,
    padding: 24,
    gap: 16,
    backgroundColor: colors.white,
    borderWidth: 1,
    borderColor: colors.gray200,
    borderRadius: radius.card,
  },
});
