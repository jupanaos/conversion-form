import type { HttpErrorResponse } from '@angular/common/http';

export async function errorMessageFrom(error: HttpErrorResponse): Promise<string> {
  if (error.status === 0) {
    return "L'API ne répond pas.";
  }
  try {
    const text = error.error instanceof Blob ? await error.error.text() : '';
    const message = JSON.parse(text)?.error?.message;
    if (typeof message === 'string' && message !== '') return message;
  } catch {
    console.error('Error while parsing API error response', error);
  }
  return `Erreur interne (${error.status}).`;
}
