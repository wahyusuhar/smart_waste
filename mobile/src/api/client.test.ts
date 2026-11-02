import {
  ApiError,
  NETWORK_ERROR_MESSAGE,
  TIMEOUT_MESSAGE,
  classifyPhoto,
  getDashboard,
  getMe,
  login,
  setToken,
  setUnauthorizedHandler,
} from './client';

jest.mock('../config', () => ({ API_URL: 'http://api.test/api' }));

const fetchMock = jest.fn();
globalThis.fetch = fetchMock as unknown as typeof fetch;

function respond(status: number, body: unknown) {
  fetchMock.mockResolvedValueOnce({
    ok: status >= 200 && status < 300,
    status,
    json: async () => body,
  });
}

async function catchError(promise: Promise<unknown>): Promise<ApiError> {
  try {
    await promise;
  } catch (error) {
    return error as ApiError;
  }
  throw new Error('Expected the request to fail');
}

beforeEach(() => {
  fetchMock.mockReset();
  setToken(null);
  setUnauthorizedHandler(null);
});

test('sends the token as a Bearer header and unwraps data', async () => {
  setToken('abc123');
  respond(200, { data: { total: 3, pending: 1, in_progress: 1, completed: 1, rejected: 0 } });

  const summary = await getDashboard();

  expect(summary.total).toBe(3);
  const [url, options] = fetchMock.mock.calls[0];
  expect(url).toBe('http://api.test/api/dashboard');
  expect(options.headers).toMatchObject({ Accept: 'application/json', Authorization: 'Bearer abc123' });
});

test('login posts JSON and returns user and token', async () => {
  respond(200, { data: { id: 2, name: 'Budi' }, token: '3|xyz' });

  const result = await login('budi@smartwaste.test', 'password');

  expect(result).toEqual({ user: { id: 2, name: 'Budi' }, token: '3|xyz' });
  const [, options] = fetchMock.mock.calls[0];
  expect(options.method).toBe('POST');
  expect(options.headers['Content-Type']).toBe('application/json');
  expect(JSON.parse(options.body)).toEqual({ email: 'budi@smartwaste.test', password: 'password' });
});

test('422 becomes field errors with the first message per field', async () => {
  respond(422, {
    message: 'Email atau password salah.',
    errors: { email: ['Email atau password salah.'], password: ['Password wajib diisi.', 'lain'] },
  });

  const error = await catchError(login('budi@smartwaste.test', ''));

  expect(error).toBeInstanceOf(ApiError);
  expect(error.status).toBe(422);
  expect(error.fieldErrors).toEqual({ email: 'Email atau password salah.', password: 'Password wajib diisi.' });
});

test('401 with a token clears it and calls the unauthorized handler', async () => {
  const handler = jest.fn();
  setUnauthorizedHandler(handler);
  setToken('expired');
  respond(401, { message: 'Sesi berakhir atau belum login. Silakan login kembali.' });

  const error = await catchError(getMe());

  expect(error.status).toBe(401);
  expect(handler).toHaveBeenCalledTimes(1);

  // The next request no longer sends the old token.
  respond(200, { data: { total: 0 } });
  await getDashboard();
  expect(fetchMock.mock.calls[1][1].headers.Authorization).toBeUndefined();
});

test('401 without a token does not trigger a logout', async () => {
  const handler = jest.fn();
  setUnauthorizedHandler(handler);
  respond(401, { message: 'Sesi berakhir atau belum login. Silakan login kembali.' });

  await catchError(getMe());

  expect(handler).not.toHaveBeenCalled();
});

test('503 from classify keeps the server message about the AI service', async () => {
  setToken('abc123');
  respond(503, { message: 'Layanan deteksi AI sedang tidak tersedia. Silakan pilih kategori secara manual.' });

  const error = await catchError(classifyPhoto('file:///foto.jpg'));

  expect(error.status).toBe(503);
  expect(error.message).toBe('Layanan deteksi AI sedang tidak tersedia. Silakan pilih kategori secara manual.');
  const [url, options] = fetchMock.mock.calls[0];
  expect(url).toBe('http://api.test/api/classify');
  expect(options.body).toBeInstanceOf(FormData);
  expect(options.headers['Content-Type']).toBeUndefined();
});

test('network failure becomes status 0 with a connection message', async () => {
  fetchMock.mockRejectedValueOnce(new TypeError('Network request failed'));

  const error = await catchError(getMe());

  expect(error.status).toBe(0);
  expect(error.message).toBe(NETWORK_ERROR_MESSAGE);
});

test('aborted request (timeout) gets its own message', async () => {
  const abort = new Error('Aborted');
  abort.name = 'AbortError';
  fetchMock.mockRejectedValueOnce(abort);

  const error = await catchError(getMe());

  expect(error.status).toBe(0);
  expect(error.message).toBe(TIMEOUT_MESSAGE);
});

test('server error without a message gets a generic text', async () => {
  fetchMock.mockResolvedValueOnce({ ok: false, status: 500, json: async () => { throw new Error('not json'); } });

  const error = await catchError(getMe());

  expect(error.status).toBe(500);
  expect(error.message).toBe('Terjadi kesalahan di server. Coba lagi.');
});
