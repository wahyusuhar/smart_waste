import { File } from 'expo-file-system';

import { API_URL } from '../config';
import type {
  AuthResult,
  Classification,
  DashboardSummary,
  NewReport,
  Paginated,
  RegisterInput,
  Report,
  ReportStatus,
  User,
} from './types';

/**
 * The only module that talks to the Laravel API. Screens call the functions at the bottom
 * and only ever deal with ApiError, never with raw fetch responses.
 */

const DEFAULT_TIMEOUT_MS = 15_000;
const UPLOAD_TIMEOUT_MS = 60_000;

export const NETWORK_ERROR_MESSAGE = 'Tidak dapat terhubung ke server. Periksa koneksi internet Anda.';
export const TIMEOUT_MESSAGE = 'Server tidak merespons. Coba lagi beberapa saat lagi.';
export const SERVER_ERROR_MESSAGE = 'Terjadi kesalahan di server. Coba lagi.';
export const MISSING_URL_MESSAGE = 'Alamat server belum diatur. Isi EXPO_PUBLIC_API_URL di file mobile/.env.';

export class ApiError extends Error {
  constructor(
    message: string,
    /** HTTP status, or 0 when the server could not be reached. */
    readonly status: number,
    /** First validation message per field, filled for 422 responses. */
    readonly fieldErrors: Record<string, string> = {},
  ) {
    super(message);
    this.name = 'ApiError';
  }
}

let token: string | null = null;
let onUnauthorized: (() => void) | null = null;

// The token itself is persisted by AuthContext (SecureStore); this module only keeps it in memory.
export function setToken(value: string | null): void {
  token = value;
}

export function setUnauthorizedHandler(handler: (() => void) | null): void {
  onUnauthorized = handler;
}

type RequestOptions = {
  method?: 'GET' | 'POST';
  body?: Record<string, unknown> | FormData;
  timeoutMs?: number;
};

async function request<T>(path: string, { method = 'GET', body, timeoutMs = DEFAULT_TIMEOUT_MS }: RequestOptions = {}): Promise<T> {
  if (!API_URL) {
    throw new ApiError(MISSING_URL_MESSAGE, 0);
  }

  const headers: Record<string, string> = { Accept: 'application/json' };
  if (token) {
    headers.Authorization = `Bearer ${token}`;
  }

  let payload: FormData | string | undefined;
  if (body instanceof FormData) {
    // No Content-Type here: fetch adds multipart/form-data with the correct boundary itself.
    payload = body;
  } else if (body) {
    headers['Content-Type'] = 'application/json';
    payload = JSON.stringify(body);
  }

  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), timeoutMs);

  let response: Response;
  try {
    response = await fetch(`${API_URL}${path}`, { method, headers, body: payload, signal: controller.signal });
  } catch (error) {
    const timedOut = error instanceof Error && error.name === 'AbortError';
    throw new ApiError(timedOut ? TIMEOUT_MESSAGE : NETWORK_ERROR_MESSAGE, 0);
  } finally {
    clearTimeout(timer);
  }

  const data = await readJson(response);

  if (response.ok) {
    return data as T;
  }

  const message = typeof data?.message === 'string' ? data.message : SERVER_ERROR_MESSAGE;

  if (response.status === 401) {
    // Only an expired or revoked token means "log out"; a failed login is answered with 422.
    if (token) {
      token = null;
      onUnauthorized?.();
    }
    throw new ApiError(message, 401);
  }

  if (response.status === 422) {
    throw new ApiError(message, 422, firstErrors(data?.errors));
  }

  // 502/503 from /classify carry a useful message ("Layanan deteksi AI sedang tidak tersedia...").
  throw new ApiError(response.status >= 500 && !data?.message ? SERVER_ERROR_MESSAGE : message, response.status);
}

async function readJson(response: Response): Promise<any> {
  try {
    return await response.json();
  } catch {
    return null;
  }
}

function firstErrors(errors: unknown): Record<string, string> {
  if (!errors || typeof errors !== 'object') {
    return {};
  }

  return Object.fromEntries(
    Object.entries(errors as Record<string, string[]>).map(([field, messages]) => [field, messages[0] ?? '']),
  );
}

// Expo's fetch rejects React Native's { uri, name, type } file parts; it needs a real Blob,
// which expo-file-system's File is (name and type come from the file itself).
function photoPart(uri: string): File {
  return new File(uri);
}

export async function login(email: string, password: string): Promise<AuthResult> {
  const result = await request<{ data: User; token: string }>('/login', { method: 'POST', body: { email, password } });
  return { user: result.data, token: result.token };
}

export async function register(input: RegisterInput): Promise<AuthResult> {
  const result = await request<{ data: User; token: string }>('/register', { method: 'POST', body: input });
  return { user: result.data, token: result.token };
}

export async function logout(): Promise<void> {
  await request('/logout', { method: 'POST' });
}

export async function getMe(): Promise<User> {
  return (await request<{ data: User }>('/me')).data;
}

export async function getDashboard(): Promise<DashboardSummary> {
  return (await request<{ data: DashboardSummary }>('/dashboard')).data;
}

export async function getReports({ page = 1, status }: { page?: number; status?: ReportStatus } = {}): Promise<Paginated<Report>> {
  const query = new URLSearchParams({ page: String(page) });
  if (status) {
    query.set('status', status);
  }
  return request<Paginated<Report>>(`/reports?${query.toString()}`);
}

export async function getReport(id: number): Promise<Report> {
  return (await request<{ data: Report }>(`/reports/${id}`)).data;
}

export async function classifyPhoto(photoUri: string): Promise<Classification> {
  const form = new FormData();
  form.append('photo', photoPart(photoUri));
  return (await request<{ data: Classification }>('/classify', { method: 'POST', body: form, timeoutMs: UPLOAD_TIMEOUT_MS })).data;
}

export async function createReport(input: NewReport): Promise<Report> {
  const form = new FormData();
  form.append('photo', photoPart(input.photoUri));
  form.append('latitude', String(input.latitude));
  form.append('longitude', String(input.longitude));
  form.append('description', input.description);
  if (input.address) form.append('address', input.address);
  if (input.category) form.append('category', input.category);
  if (input.aiLabel && input.aiConfidence !== undefined) {
    form.append('ai_label', input.aiLabel);
    form.append('ai_confidence', String(input.aiConfidence));
  }
  return (await request<{ data: Report }>('/reports', { method: 'POST', body: form, timeoutMs: UPLOAD_TIMEOUT_MS })).data;
}
