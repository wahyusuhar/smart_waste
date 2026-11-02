import { Inter_400Regular, Inter_500Medium, Inter_600SemiBold, useFonts } from '@expo-google-fonts/inter';
import { Stack } from 'expo-router';
import * as SplashScreen from 'expo-splash-screen';
import { StatusBar } from 'expo-status-bar';
import { useEffect } from 'react';

import { AuthProvider, useAuth } from '../auth/AuthContext';
import { colors, fonts } from '../theme';

// Keep the splash screen up until the fonts and the saved session are loaded.
SplashScreen.preventAutoHideAsync();

export default function RootLayout() {
  const [fontsLoaded, fontError] = useFonts({ Inter_400Regular, Inter_500Medium, Inter_600SemiBold });

  return (
    <AuthProvider>
      {/* If the font fails to load the app still starts, with the system font. */}
      <RootNavigator fontsReady={fontsLoaded || fontError !== null} />
    </AuthProvider>
  );
}

function RootNavigator({ fontsReady }: { fontsReady: boolean }) {
  const { status } = useAuth();
  const ready = fontsReady && status !== 'loading';

  useEffect(() => {
    if (ready) {
      SplashScreen.hideAsync();
    }
  }, [ready]);

  if (!ready) {
    return null;
  }

  const signedIn = status === 'signedIn';

  return (
    <>
      <StatusBar style="dark" />
      <Stack
        screenOptions={{
          headerShown: false,
          contentStyle: { backgroundColor: colors.gray50 },
          headerStyle: { backgroundColor: colors.white },
          headerTitleStyle: { fontFamily: fonts.semibold, fontSize: 17, color: colors.gray900 },
          headerTintColor: colors.gray900,
          headerShadowVisible: false,
        }}
      >
        {/* When the guard turns false (logout or a 401), the router moves to the screens that are still allowed. */}
        <Stack.Protected guard={signedIn}>
          <Stack.Screen name="(tabs)" />
          <Stack.Screen name="reports/new" options={{ headerShown: true, title: 'Buat Laporan' }} />
          <Stack.Screen name="reports/[id]" options={{ headerShown: true, title: 'Detail Laporan' }} />
        </Stack.Protected>

        <Stack.Protected guard={!signedIn}>
          <Stack.Screen name="login" />
        </Stack.Protected>
      </Stack>
    </>
  );
}
