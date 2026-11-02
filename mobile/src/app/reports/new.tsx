import { Image } from 'expo-image';
import * as ImagePicker from 'expo-image-picker';
import { useRouter } from 'expo-router';
import { useCallback, useEffect, useRef, useState } from 'react';
import { ActivityIndicator, KeyboardAvoidingView, Linking, Platform, ScrollView, StyleSheet, Text, View } from 'react-native';

import { ApiError, SERVER_ERROR_MESSAGE, classifyPhoto, createReport } from '../../api/client';
import type { Classification, ReportCategory } from '../../api/types';
import { Button } from '../../components/Button';
import { Card } from '../../components/Card';
import { CategoryPicker } from '../../components/CategoryPicker';
import { FormError } from '../../components/States';
import { TextField } from '../../components/TextField';
import { categoryLabels, colors, fonts, radius, type } from '../../theme';
import { formatCoordinate, formatPercent } from '../../utils/format';
import { getCurrentLocation, type LocationResult } from '../../utils/location';
import { compressPhoto } from '../../utils/photo';

type PhotoSource = 'camera' | 'gallery';

type AiState =
  | { status: 'idle' }
  | { status: 'loading' }
  | { status: 'done'; result: Classification }
  | { status: 'error'; message: string };

// null while the position is still being read.
type LocationState = LocationResult | null;

const LOCATION_MESSAGES = {
  denied: 'Izin lokasi ditolak. Lokasi dibutuhkan agar petugas tahu di mana tumpukan sampah berada. Ketuk Coba lagi untuk mengizinkan.',
  blocked: 'Izin lokasi diblokir. Izinkan lokasi untuk Smart Waste di Pengaturan, lalu ketuk Coba lagi.',
  disabled: 'Layanan lokasi (GPS) di HP Anda mati. Nyalakan GPS, lalu ketuk Coba lagi.',
  failed: 'Lokasi tidak dapat dibaca. Pastikan GPS menyala dan coba di tempat terbuka, lalu ketuk Coba lagi.',
};

function apiMessage(error: unknown): string {
  return error instanceof ApiError ? error.message : SERVER_ERROR_MESSAGE;
}

export default function NewReportScreen() {
  const router = useRouter();

  const [photoUri, setPhotoUri] = useState<string | null>(null);
  const [preparingPhoto, setPreparingPhoto] = useState(false);
  const [photoNotice, setPhotoNotice] = useState<{ message: string; openSettings: boolean } | null>(null);
  const [ai, setAi] = useState<AiState>({ status: 'idle' });
  const [category, setCategory] = useState<ReportCategory | null>(null);
  const [location, setLocation] = useState<LocationState>(null);
  const [description, setDescription] = useState('');
  const [address, setAddress] = useState('');

  const [submitting, setSubmitting] = useState(false);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
  const [formError, setFormError] = useState<string | null>(null);

  // When the user picks another photo while the AI is still busy, the older answer is ignored.
  const classifyRequest = useRef(0);

  const applyLocation = useCallback((result: LocationResult) => {
    setLocation(result);
    if (result.ok) {
      setFieldErrors(({ location: _location, ...rest }) => rest);
    }
  }, []);

  // The location is read as soon as the screen opens, so it is ready by the time the form is filled.
  useEffect(() => {
    getCurrentLocation().then(applyLocation);
  }, [applyLocation]);

  function locate() {
    setLocation(null);
    getCurrentLocation().then(applyLocation);
  }

  async function pickPhoto(source: PhotoSource) {
    setPhotoNotice(null);

    if (source === 'camera') {
      const permission = await ImagePicker.requestCameraPermissionsAsync();
      if (!permission.granted) {
        setPhotoNotice(
          permission.canAskAgain
            ? { message: 'Izin kamera ditolak. Ketuk Ambil Foto untuk mencoba lagi, atau pilih foto dari galeri.', openSettings: false }
            : { message: 'Izin kamera diblokir. Izinkan kamera di Pengaturan, atau pilih foto dari galeri.', openSettings: true },
        );
        return;
      }
    }

    try {
      // No permission prompt for the gallery: the system photo picker only returns the photo the user chose.
      const options: ImagePicker.ImagePickerOptions = { mediaTypes: ['images'], quality: 1 };
      const result = source === 'camera' ? await ImagePicker.launchCameraAsync(options) : await ImagePicker.launchImageLibraryAsync(options);
      if (result.canceled || !result.assets[0]) {
        return;
      }

      setPreparingPhoto(true);
      const uri = await compressPhoto(result.assets[0]);
      setPhotoUri(uri);
      setFieldErrors(({ photo: _photo, ...rest }) => rest);
      setPreparingPhoto(false);
      await classify(uri);
    } catch {
      setPreparingPhoto(false);
      setPhotoNotice({ message: 'Foto tidak dapat dibuka. Coba ambil ulang atau pilih foto lain.', openSettings: false });
    }
  }

  async function classify(uri: string) {
    const requestId = ++classifyRequest.current;
    setAi({ status: 'loading' });

    try {
      const result = await classifyPhoto(uri);
      if (requestId !== classifyRequest.current) return;
      setAi({ status: 'done', result });
      // The AI result is only a suggestion: it fills the category, which the user may still change.
      setCategory(result.label);
    } catch (error) {
      if (requestId !== classifyRequest.current) return;
      // A 401 is handled globally (back to login).
      if (error instanceof ApiError && error.status === 401) return;
      setAi({ status: 'error', message: apiMessage(error) });
    }
  }

  async function submit() {
    const errors: Record<string, string> = {};
    if (!photoUri) errors.photo = 'Ambil atau pilih foto tumpukan sampah terlebih dahulu.';
    if (!location?.ok) errors.location = 'Lokasi belum didapat. Laporan membutuhkan lokasi GPS.';
    if (!description.trim()) errors.description = 'Deskripsi wajib diisi.';

    setFormError(null);
    setFieldErrors(errors);
    if (!photoUri || !location?.ok || Object.keys(errors).length > 0) {
      setFormError('Periksa kembali isian yang ditandai.');
      return;
    }

    setSubmitting(true);
    try {
      const aiResult = ai.status === 'done' ? ai.result : null;
      const report = await createReport({
        photoUri,
        latitude: location.latitude,
        longitude: location.longitude,
        description: description.trim(),
        address: address.trim() || undefined,
        category: category ?? undefined,
        aiLabel: aiResult?.label,
        aiConfidence: aiResult?.confidence,
      });
      // Replace this form with the new report, so Back returns to where the user came from.
      router.replace(`/reports/${report.id}`);
    } catch (error) {
      setSubmitting(false);
      if (error instanceof ApiError && error.status === 401) return;
      if (error instanceof ApiError && error.status === 422) {
        const { latitude, longitude, ...rest } = error.fieldErrors;
        setFieldErrors({ ...rest, ...(latitude || longitude ? { location: latitude ?? longitude } : {}) });
        setFormError(error.message);
        return;
      }
      setFormError(apiMessage(error));
    }
  }

  const busy = submitting || preparingPhoto;

  return (
    <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
        <Text style={type.caption}>Foto tumpukan sampah, lalu lengkapi deskripsi dan lokasinya.</Text>

        <Card title="Foto">
          <View style={styles.cardBody}>
            {preparingPhoto ? (
              <View style={[styles.photo, styles.photoPlaceholder]}>
                <ActivityIndicator color={colors.accent} />
                <Text style={type.caption}>Menyiapkan foto…</Text>
              </View>
            ) : photoUri ? (
              <Image source={{ uri: photoUri }} style={styles.photo} contentFit="contain" accessibilityLabel="Pratinjau foto" />
            ) : (
              <View style={[styles.photo, styles.photoPlaceholder, fieldErrors.photo && styles.photoPlaceholderError]}>
                <Text style={type.caption}>Belum ada foto.</Text>
              </View>
            )}
            {fieldErrors.photo ? <Text style={styles.fieldError}>{fieldErrors.photo}</Text> : null}

            <View style={styles.buttonRow}>
              <Button title="Ambil Foto" variant="secondary" style={styles.flex} disabled={busy} onPress={() => pickPhoto('camera')} />
              <Button title="Pilih dari Galeri" variant="secondary" style={styles.flex} disabled={busy} onPress={() => pickPhoto('gallery')} />
            </View>

            {photoNotice ? (
              <View style={styles.notice}>
                <FormError message={photoNotice.message} />
                {photoNotice.openSettings ? <Button title="Buka Pengaturan" variant="secondary" onPress={() => Linking.openSettings()} /> : null}
              </View>
            ) : null}

            <AiSuggestion state={ai} onRetry={photoUri ? () => classify(photoUri) : undefined} />
          </View>
        </Card>

        <Card title="Detail">
          <View style={styles.cardBody}>
            <CategoryPicker value={category} onChange={setCategory} error={fieldErrors.category} disabled={submitting} />
            <TextField
              label="Deskripsi"
              value={description}
              onChangeText={setDescription}
              error={fieldErrors.description}
              hint="Jelaskan kondisi tumpukan sampah. Maksimal 1000 karakter."
              placeholder="Contoh: Kantong plastik dan botol menumpuk di samping halte sejak tiga hari lalu."
              multiline
              maxLength={1000}
              textAlignVertical="top"
              style={styles.textArea}
              editable={!submitting}
            />
          </View>
        </Card>

        <Card title="Lokasi">
          <View style={styles.cardBody}>
            <View>
              <LocationStatus state={location} onRetry={locate} disabled={submitting} />
              {fieldErrors.location ? <Text style={styles.fieldError}>{fieldErrors.location}</Text> : null}
            </View>
            <TextField
              label="Alamat/patokan (opsional)"
              value={address}
              onChangeText={setAddress}
              error={fieldErrors.address}
              placeholder="Contoh: Samping halte Terminal Mendolo"
              maxLength={255}
              editable={!submitting}
            />
          </View>
        </Card>

        {formError ? <FormError message={formError} /> : null}

        <Button
          title="Kirim Laporan"
          loadingTitle="Mengirim…"
          loading={submitting}
          disabled={preparingPhoto || ai.status === 'loading'}
          onPress={submit}
        />
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

function AiSuggestion({ state, onRetry }: { state: AiState; onRetry?: () => void }) {
  if (state.status === 'idle') {
    return null;
  }

  if (state.status === 'loading') {
    return (
      <View style={styles.aiBox}>
        <ActivityIndicator size="small" color={colors.accent} />
        <Text style={type.caption}>Mendeteksi kategori…</Text>
      </View>
    );
  }

  if (state.status === 'error') {
    return (
      <View style={styles.notice}>
        <FormError message={state.message} />
        {onRetry ? <Button title="Deteksi ulang" variant="secondary" onPress={onRetry} /> : null}
      </View>
    );
  }

  return (
    <View style={[styles.aiBox, styles.aiResult]}>
      <Text style={styles.aiTitle}>
        Saran AI: {categoryLabels[state.result.label]} <Text style={styles.aiConfidence}>({formatPercent(state.result.confidence)})</Text>
      </Text>
      <Text style={[type.caption, styles.aiHint]}>Kategori di bawah sudah diisi sesuai saran AI. Ganti jika tidak sesuai.</Text>
    </View>
  );
}

function LocationStatus({ state, onRetry, disabled }: { state: LocationState; onRetry: () => void; disabled: boolean }) {
  if (!state) {
    return (
      <View style={styles.locationRow}>
        <ActivityIndicator size="small" color={colors.accent} />
        <Text style={type.caption}>Mengambil lokasi…</Text>
      </View>
    );
  }

  if (!state.ok) {
    return (
      <View style={styles.notice}>
        <FormError message={LOCATION_MESSAGES[state.reason]} />
        <View style={styles.buttonRow}>
          <Button title="Coba lagi" variant="secondary" style={styles.flex} disabled={disabled} onPress={onRetry} />
          {state.reason === 'blocked' ? (
            <Button title="Buka Pengaturan" variant="secondary" style={styles.flex} onPress={() => Linking.openSettings()} />
          ) : null}
        </View>
      </View>
    );
  }

  return (
    <View style={styles.notice}>
      <View>
        <Text style={type.label}>Koordinat</Text>
        <Text style={[type.body, styles.coordinates]}>
          {formatCoordinate(state.latitude)}, {formatCoordinate(state.longitude)}
        </Text>
      </View>
      <Button title="Perbarui lokasi" variant="secondary" disabled={disabled} onPress={onRetry} />
    </View>
  );
}

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  content: {
    padding: 16,
    gap: 16,
  },
  cardBody: {
    padding: 16,
    gap: 16,
  },
  photo: {
    width: '100%',
    height: 220,
    borderRadius: radius.control,
    backgroundColor: colors.gray100,
  },
  photoPlaceholder: {
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    borderWidth: 1,
    borderStyle: 'dashed',
    borderColor: colors.gray300,
  },
  photoPlaceholderError: {
    borderColor: colors.danger,
  },
  buttonRow: {
    flexDirection: 'row',
    gap: 8,
  },
  notice: {
    gap: 12,
  },
  aiBox: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    paddingHorizontal: 16,
    paddingVertical: 12,
    borderWidth: 1,
    borderColor: colors.gray200,
    borderRadius: radius.control,
    backgroundColor: colors.gray50,
  },
  aiResult: {
    flexDirection: 'column',
    alignItems: 'flex-start',
    gap: 4,
  },
  aiTitle: {
    fontFamily: fonts.medium,
    fontSize: 14,
    lineHeight: 20,
    color: colors.gray900,
  },
  aiConfidence: {
    fontFamily: fonts.regular,
    color: colors.gray500,
  },
  aiHint: {
    color: colors.gray600,
  },
  textArea: {
    minHeight: 104,
  },
  locationRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  coordinates: {
    marginTop: 4,
    fontVariant: ['tabular-nums'],
  },
  fieldError: {
    marginTop: 4,
    fontFamily: fonts.regular,
    fontSize: 13,
    lineHeight: 18,
    color: colors.danger,
  },
});
