import * as Location from 'expo-location';

export type LocationResult =
  | { ok: true; latitude: number; longitude: number }
  | { ok: false; reason: 'denied' | 'blocked' | 'disabled' | 'failed' };

const TIMEOUT_MS = 20_000;

/**
 * Asks for permission when needed and reads the current position.
 * 'denied' can be asked again; 'blocked' means the user chose "don't ask again"
 * and can only allow it from the phone settings.
 */
export async function getCurrentLocation(): Promise<LocationResult> {
  try {
    const permission = await Location.requestForegroundPermissionsAsync();
    if (permission.status !== 'granted') {
      return { ok: false, reason: permission.canAskAgain ? 'denied' : 'blocked' };
    }

    if (!(await Location.hasServicesEnabledAsync())) {
      return { ok: false, reason: 'disabled' };
    }

    // getCurrentPositionAsync can wait a long time for a GPS fix indoors, so give up after a while.
    const position = await Promise.race([
      Location.getCurrentPositionAsync({ accuracy: Location.Accuracy.High }),
      new Promise<null>((resolve) => setTimeout(() => resolve(null), TIMEOUT_MS)),
    ]);
    if (!position) {
      return { ok: false, reason: 'failed' };
    }

    return { ok: true, latitude: position.coords.latitude, longitude: position.coords.longitude };
  } catch {
    return { ok: false, reason: 'failed' };
  }
}
