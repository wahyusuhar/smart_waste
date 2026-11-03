import Ionicons from '@expo/vector-icons/Ionicons';
import { Tabs } from 'expo-router';
import type { ColorValue } from 'react-native';

import { colors, fonts } from '../../theme';

type IconName = keyof typeof Ionicons.glyphMap;

// Icons appear in the tab bar, the Beranda header and the Cara Kerja steps (PRD §8 exception).
function tabIcon(name: IconName) {
  return function TabIcon({ color, size }: { color: ColorValue; size: number }) {
    // The tab bar passes one of the two tint colors set below, which are always hex strings.
    return <Ionicons name={name} size={size} color={color as string} />;
  };
}

export default function TabsLayout() {
  return (
    <Tabs
      screenOptions={{
        headerStyle: { backgroundColor: colors.white },
        headerTitleStyle: { fontFamily: fonts.semibold, fontSize: 17, color: colors.gray900 },
        headerShadowVisible: false,
        sceneStyle: { backgroundColor: colors.gray50 },
        tabBarActiveTintColor: colors.accent,
        tabBarInactiveTintColor: colors.gray500,
        tabBarLabelStyle: { fontFamily: fonts.medium, fontSize: 12 },
        tabBarStyle: { borderTopColor: colors.gray200, backgroundColor: colors.white },
      }}
    >
      {/* Beranda draws its own header with the greeting. */}
      <Tabs.Screen name="index" options={{ title: 'Beranda', headerShown: false, tabBarIcon: tabIcon('home-outline') }} />
      <Tabs.Screen name="reports" options={{ title: 'Riwayat', tabBarIcon: tabIcon('document-text-outline') }} />
      <Tabs.Screen name="profile" options={{ title: 'Profil', tabBarIcon: tabIcon('person-outline') }} />
    </Tabs>
  );
}
