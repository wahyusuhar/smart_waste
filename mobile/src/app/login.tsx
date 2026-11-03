import { useRouter } from 'expo-router';
import { useRef, useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';

import { ApiError, SERVER_ERROR_MESSAGE } from '../api/client';
import { useAuth } from '../auth/AuthContext';
import { AuthLayout } from '../components/AuthLayout';
import { Button } from '../components/Button';
import { FormError } from '../components/States';
import { TextField, type TextFieldRef } from '../components/TextField';
import { colors, fonts, radius, type } from '../theme';

export default function LoginScreen() {
  const { signIn } = useAuth();
  const router = useRouter();
  const passwordRef = useRef<TextFieldRef>(null);

  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
  const [formError, setFormError] = useState<string | null>(null);

  async function submit() {
    setSubmitting(true);
    setFieldErrors({});
    setFormError(null);

    try {
      // On success the root layout switches to the tabs by itself (Stack.Protected).
      await signIn(email.trim(), password);
    } catch (error) {
      if (error instanceof ApiError && error.status === 422) {
        setFieldErrors(error.fieldErrors);
      } else {
        setFormError(error instanceof ApiError ? error.message : SERVER_ERROR_MESSAGE);
      }
      setSubmitting(false);
    }
  }

  return (
    <AuthLayout>
      <View>
        <View style={styles.titleRow}>
          <Text style={type.title}>Masuk ke Akun</Text>
          <Text style={styles.chip}>Warga & Admin</Text>
        </View>
        <Text style={[type.caption, styles.subtitle]}>Gunakan email dan password akun Anda.</Text>
      </View>

      {formError ? <FormError message={formError} /> : null}

      <TextField
        label="Email"
        icon="mail-outline"
        value={email}
        onChangeText={setEmail}
        error={fieldErrors.email}
        keyboardType="email-address"
        autoCapitalize="none"
        autoComplete="email"
        textContentType="emailAddress"
        returnKeyType="next"
        onSubmitEditing={() => passwordRef.current?.focus()}
        editable={!submitting}
      />
      <TextField
        ref={passwordRef}
        label="Password"
        icon="lock-closed-outline"
        value={password}
        onChangeText={setPassword}
        error={fieldErrors.password}
        secureTextEntry
        autoComplete="current-password"
        textContentType="password"
        returnKeyType="go"
        onSubmitEditing={submit}
        editable={!submitting}
      />

      <View style={styles.actions}>
        <Button title="Masuk Sekarang" icon="log-in-outline" loadingTitle="Memproses…" loading={submitting} onPress={submit} />
        <Button
          title="Daftar Akun Baru"
          icon="person-add-outline"
          variant="soft"
          disabled={submitting}
          onPress={() => router.push('/register')}
        />
      </View>
    </AuthLayout>
  );
}

const styles = StyleSheet.create({
  titleRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 8,
  },
  chip: {
    paddingHorizontal: 10,
    paddingVertical: 3,
    borderRadius: radius.pill,
    backgroundColor: colors.accentSoft,
    fontFamily: fonts.semibold,
    fontSize: 12,
    color: colors.accent,
    overflow: 'hidden',
  },
  subtitle: {
    marginTop: 4,
  },
  actions: {
    gap: 12,
    marginTop: 4,
  },
});
