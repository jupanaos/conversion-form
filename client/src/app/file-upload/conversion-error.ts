import type { HttpErrorResponse } from '@angular/common/http';

async function apiMessageFrom(body: unknown): Promise<string | null> {
  try {
    const text = body instanceof Blob ? await body.text() : '';
    const message = JSON.parse(text)?.error?.message;
    return typeof message === 'string' && message !== '' ? message : null;
  } catch {
    return null;
  }
}

export async function errorMessageFrom(error: HttpErrorResponse): Promise<string> {
  if (error.status === 0) {
    return "L'API ne répond pas.";
  }
  const message = await apiMessageFrom(error.error);
  return message ?? `Erreur interne (${error.status}).`;
}
