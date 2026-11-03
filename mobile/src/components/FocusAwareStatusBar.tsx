import { useIsFocused } from 'expo-router';
import { StatusBar, type StatusBarStyle } from 'expo-status-bar';

// Tab screens stay mounted after they lose focus, and the last mounted StatusBar wins. Rendering it only
// while focused lets the root layout's dark status bar come back on every other screen.
export function FocusAwareStatusBar({ style }: { style: StatusBarStyle }) {
  return useIsFocused() ? <StatusBar style={style} /> : null;
}
