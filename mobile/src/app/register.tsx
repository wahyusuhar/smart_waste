import { useRouter } from 'expo-router';
import { useRef, useState } from 'react';
import { Alert, StyleSheet, Text, View } from 'react-native';

import { ApiError, SERVER_ERROR_MESSAGE } from '../api/client';
import { useAuth } from '../auth/AuthContext';
import { AuthLayout } from '../components/AuthLayout';
import { Button } from '../components/Button';
import { FormError } from '../components/States';
import { TextField, type TextFieldRef } from '../components/TextField';
import { type } from '../theme';
import { firstName } from '../utils/format';

export default function RegisterScreen() {
  const { signUp } = useAuth();
  const router = useRouter();
  const emailRef = useRef<TextFieldRef>(null);
  const phoneRef = useRef<TextFieldRef>(null);
  const passwordRef = useRef<TextFieldRef>(null);
  const confirmationRef = useRef<TextFieldRef>(null);

  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
  const [formError, setFormError] = useState<string | null>(null);

  async function submit() {
    setSubmitting(true);
    setFieldErrors({});
    setFormError(null);

    try {
      // A new account is signed in straight away; the root layout then opens the tabs.
      await signUp({
        name: name.trim(),
        email: email.trim(),
        phone: phone.trim() || undefined,
        password,
        password_confirmation: passwordConfirmation,
      });
      // A system dialog, so it stays visible while the router switches to Beranda underneath.
      Alert.alert('Pendaftaran berhasil', `Akun Anda sudah dibuat. Selamat datang, ${firstName(name)}.`);
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
        <Text style={type.title}>Daftar Akun Baru</Text>
        <Text style={[type.caption, styles.subtitle]}>Buat akun untuk mengirim dan memantau laporan sampah.</Text>
      </View>

      {formError ? <FormError message={formError} /> : null}

      <TextField
        label="Nama"
        icon="person-outline"
        value={name}
        onChangeText={setName}
        error={fieldErrors.name}
        autoCapitalize="words"
        autoComplete="name"
        textContentType="name"
        returnKeyType="next"
        onSubmitEditing={() => emailRef.current?.focus()}
        editable={!submitting}
      />
      <TextField
        ref={emailRef}
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
        onSubmitEditing={() => phoneRef.current?.focus()}
        editable={!submitting}
      />
      <TextField
        ref={phoneRef}
        label="Nomor HP (opsional)"
        icon="call-outline"
        value={phone}
        onChangeText={setPhone}
        error={fieldErrors.phone}
        keyboardType="phone-pad"
        autoComplete="tel"
        textContentType="telephoneNumber"
        maxLength={20}
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
        hint="Minimal 8 karakter."
        secureTextEntry
        autoComplete="new-password"
        textContentType="newPassword"
        returnKeyType="next"
        onSubmitEditing={() => confirmationRef.current?.focus()}
        editable={!submitting}
      />
      <TextField
        ref={confirmationRef}
        label="Konfirmasi password"
        icon="lock-closed-outline"
        value={passwordConfirmation}
        onChangeText={setPasswordConfirmation}
        error={fieldErrors.password_confirmation}
        secureTextEntry
        autoComplete="new-password"
        textContentType="newPassword"
        returnKeyType="go"
        onSubmitEditing={submit}
        editable={!submitting}
      />

      <View style={styles.actions}>
        <Button title="Daftar" icon="person-add-outline" loadingTitle="Memproses…" loading={submitting} onPress={submit} />
        <Button
          title="Sudah punya akun? Masuk"
          icon="log-in-outline"
          variant="soft"
          disabled={submitting}
          onPress={() => router.dismissTo('/login')}
        />
      </View>
    </AuthLayout>
  );
}

const styles = StyleSheet.create({
  subtitle: {
    marginTop: 4,
  },
  actions: {
    gap: 12,
    marginTop: 4,
  },
});
