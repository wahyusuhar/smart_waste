import { useRef, useState } from 'react';
import { KeyboardAvoidingView, Platform, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { ApiError, SERVER_ERROR_MESSAGE } from '../api/client';
import { useAuth } from '../auth/AuthContext';
import { Button } from '../components/Button';
import { Card } from '../components/Card';
import { FormError } from '../components/States';
import { TextField, type TextFieldRef } from '../components/TextField';
import { colors, fonts, type } from '../theme';

export default function LoginScreen() {
  const { signIn } = useAuth();
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
    <SafeAreaView style={styles.safeArea}>
      <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
        <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
          <Text style={styles.appName}>Smart Waste</Text>

          <Card>
            <View style={styles.form}>
              <View>
                <Text style={type.title}>Masuk</Text>
                <Text style={[type.caption, styles.subtitle]}>Gunakan email dan password akun Anda.</Text>
              </View>

              {formError ? <FormError message={formError} /> : null}

              <TextField
                label="Email"
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

              <Button title="Masuk" loadingTitle="Memproses…" loading={submitting} onPress={submit} />
            </View>
          </Card>
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
});
