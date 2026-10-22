import { HttpErrorResponse } from '@angular/common/http';
import { errorMessageFrom } from './conversion-error';

function blobError(status: number, body: string): HttpErrorResponse {
  return new HttpErrorResponse({ status, error: new Blob([body], { type: 'application/json' }) });
}

describe('errorMessageFrom', () => {
  it("renvoie le message de l'API pour une 422", async () => {
    const body = {
      error: {
        code: 'invalid_line',
        message: 'Ligne 1 : Nombre de colonnes invalide (9 attendues)',
        line: 1,
      },
    };

    expect(await errorMessageFrom(blobError(422, JSON.stringify(body)))).toBe(
      'Ligne 1 : Nombre de colonnes invalide (9 attendues)',
    );
  });

  it.each([400, 413, 415])("renvoie le message de l'API pour une %i", async (status) => {
    const body = { error: { code: 'x', message: 'Message métier' } };

    expect(await errorMessageFrom(blobError(status, JSON.stringify(body)))).toBe('Message métier');
  });

  it('signale une API qui ne répond pas (statut 0)', async () => {
    expect(await errorMessageFrom(new HttpErrorResponse({ status: 0 }))).toBe(
      "L'API ne répond pas.",
    );
  });

  it("renvoie une erreur interne si le corps n'est pas du JSON", async () => {
    expect(await errorMessageFrom(blobError(500, '<html>Oups</html>'))).toBe(
      'Erreur interne (500).',
    );
  });

  it("renvoie une erreur interne si le JSON n'a pas de message", async () => {
    expect(await errorMessageFrom(blobError(500, '{"foo":1}'))).toBe('Erreur interne (500).');
  });

  it('ignore un message vide ou non textuel', async () => {
    expect(await errorMessageFrom(blobError(500, '{"error":{"message":""}}'))).toBe(
      'Erreur interne (500).',
    );
    expect(await errorMessageFrom(blobError(500, '{"error":{"message":42}}'))).toBe(
      'Erreur interne (500).',
    );
  });

  it("renvoie une erreur interne si le corps n'est pas un Blob", async () => {
    const error = new HttpErrorResponse({ status: 502, error: null });

    expect(await errorMessageFrom(error)).toBe('Erreur interne (502).');
  });
});
