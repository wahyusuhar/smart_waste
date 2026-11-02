import * as SecureStore from 'expo-secure-store';
import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from 'react';

import * as api from '../api/client';
import type { RegisterInput, User } from '../api/types';

const TOKEN_KEY = 'auth_token';
const USER_KEY = 'auth_user';

type AuthStatus = 'loading' | 'signedOut' | 'signedIn';

type AuthContextValue = {
  status: AuthStatus;
  user: User | null;
  signIn: (email: string, password: string) => Promise<void>;
  signUp: (input: RegisterInput) => Promise<void>;
  signOut: () => Promise<void>;
};

const AuthContext = createContext<AuthContextValue | null>(null);

async function clearStoredSession(): Promise<void> {
  await Promise.all([SecureStore.deleteItemAsync(TOKEN_KEY), SecureStore.deleteItemAsync(USER_KEY)]);
}

export function AuthProvider({ children }: { children: ReactNode }) {
  const [status, setStatus] = useState<AuthStatus>('loading');
  const [user, setUser] = useState<User | null>(null);

  const endSession = useCallback(async () => {
    api.setToken(null);
    await clearStoredSession();
    setUser(null);
    setStatus('signedOut');
  }, []);

  useEffect(() => {
    // Any 401 from the API (expired or revoked token) sends the user back to the login screen.
    api.setUnauthorizedHandler(() => {
      void endSession();
    });

    (async () => {
      const [token, storedUser] = await Promise.all([
        SecureStore.getItemAsync(TOKEN_KEY),
        SecureStore.getItemAsync(USER_KEY),
      ]);

      if (!token || !storedUser) {
        setStatus('signedOut');
        return;
      }

      // Open the app straight away with the saved profile, then refresh it in the background.
      // If the token is no longer valid, getMe() gets a 401 and the handler above logs out.
      api.setToken(token);
      setUser(JSON.parse(storedUser) as User);
      setStatus('signedIn');

      try {
        const fresh = await api.getMe();
        setUser(fresh);
        await SecureStore.setItemAsync(USER_KEY, JSON.stringify(fresh));
      } catch {
        // Offline at start-up: keep the saved profile.
      }
    })();

    return () => api.setUnauthorizedHandler(null);
  }, [endSession]);

  const startSession = useCallback(async ({ user: signedInUser, token }: { user: User; token: string }) => {
    await Promise.all([
      SecureStore.setItemAsync(TOKEN_KEY, token),
      SecureStore.setItemAsync(USER_KEY, JSON.stringify(signedInUser)),
    ]);
    api.setToken(token);
    setUser(signedInUser);
    setStatus('signedIn');
  }, []);

  const signIn = useCallback(
    async (email: string, password: string) => startSession(await api.login(email, password)),
    [startSession],
  );

  const signUp = useCallback(async (input: RegisterInput) => startSession(await api.register(input)), [startSession]);

  const signOut = useCallback(async () => {
    try {
      await api.logout();
    } catch {
      // The local session is cleared even if the server cannot be reached.
    }
    await endSession();
  }, [endSession]);

  const value = useMemo(() => ({ status, user, signIn, signUp, signOut }), [status, user, signIn, signUp, signOut]);

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used inside AuthProvider');
  }
  return context;
}
