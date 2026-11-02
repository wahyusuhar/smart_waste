import { useRouter } from 'expo-router';
import { useRef, useState } from 'react';
import { KeyboardAvoidingView, Platform, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { ApiError, SERVER_ERROR_MESSAGE } from '../api/client';
import { useAuth } from '../auth/AuthContext';
import { Button } from '../components/Button';
import { Card } from '../components/Card';
import { FormError } from '../components/States';
import { TextField, type TextFieldRef } from '../components/TextField';
import { colors, fonts, type } from '../theme';

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
    <SafeAreaView style={styles.safeArea}>
      <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
        <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
          <Text style={styles.appName}>Smart Waste</Text>

          <Card>
            <View style={styles.form}>
              <View>
                <Text style={type.title}>Daftar</Text>
                <Text style={[type.caption, styles.subtitle]}>Buat akun untuk mengirim dan memantau laporan sampah.</Text>
              </View>

              {formError ? <FormError message={formError} /> : null}

              <TextField
                label="Nama"
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

              <Button title="Daftar" loadingTitle="Memproses…" loading={submitting} onPress={submit} />
            </View>
          </Card>

          <View style={styles.switchRow}>
            <Text style={styles.switchText}>Sudah punya akun?</Text>
            <Pressable accessibilityRole="link" hitSlop={8} onPress={() => router.dismissTo('/login')} disabled={submitting}>
              <Text style={styles.switchLink}>Masuk</Text>
            </Pressable>
          </View>
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: colors.gray50,
  },
  flex: {
    flex: 1,
  },
  content: {
    flexGrow: 1,
    justifyContent: 'center',
    padding: 16,
  },
  appName: {
    marginBottom: 24,
    textAlign: 'center',
    fontFamily: fonts.semibold,
    fontSize: 14,
    color: colors.gray900,
  },
  form: {
    padding: 24,
    gap: 16,
  },
  subtitle: {
    marginTop: 4,
  },
  switchRow: {
    flexDirection: 'row',
    justifyContent: 'center',
    gap: 4,
    marginTop: 16,
  },
  switchText: {
    fontFamily: fonts.regular,
    fontSize: 14,
    color: colors.gray600,
  },
  switchLink: {
    fontFamily: fonts.medium,
    fontSize: 14,
    color: colors.accent,
  },
});
