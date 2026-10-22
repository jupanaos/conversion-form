import type { HttpErrorResponse } from '@angular/common/http';

export async function errorMessageFrom(error: HttpErrorResponse): Promise<string> {
  if (error.status === 0) {
    return "L'API ne répond pas.";
  }
  const body = error.error instanceof Blob ? await new Response(error.error).json().catch(() => null) : null;
  const message = body?.error?.message;
  return typeof message === 'string' && message !== '' ? message : `Erreur interne (${error.status}).`;
}
