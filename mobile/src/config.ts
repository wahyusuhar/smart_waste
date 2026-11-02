// Set in mobile/.env (EXPO_PUBLIC_API_URL). Expo inlines EXPO_PUBLIC_* variables when the app is bundled.
export const API_URL = (process.env.EXPO_PUBLIC_API_URL ?? '').replace(/\/+$/, '');
