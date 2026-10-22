import { TestBed } from '@angular/core/testing';
import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { ConversionApi } from './conversion-api';
import { environment } from '../../environments/environment';

describe('ConversionApi', () => {
  let api: ConversionApi;
  let http: HttpTestingController;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [provideHttpClient(), provideHttpClientTesting()],
    });
    api = TestBed.inject(ConversionApi);
    http = TestBed.inject(HttpTestingController);
  });

  afterEach(() => http.verify());

  it('envoie le fichier en multipart sur /conversions', () => {
    const file = new File(['abc'], 'vente.txt', { type: 'text/plain' });

    api.convert(file).subscribe();

    const req = http.expectOne(`${environment.apiUrl}/conversions`);
    expect(req.request.method).toBe('POST');
    expect(req.request.body instanceof FormData).toBe(true);
    expect((req.request.body as FormData).get('file')).toBe(file);
    expect(req.request.responseType).toBe('blob');
    req.flush(new Blob());
  });

  it('expose les en-têtes de la réponse', () => {
    let disposition: string | null = null;
    api.convert(new File(['x'], 'a.txt')).subscribe((res) => {
      disposition = res.headers.get('Content-Disposition');
    });

    http.expectOne(`${environment.apiUrl}/conversions`).flush(new Blob(), {
      headers: { 'Content-Disposition': 'attachment; filename="a.csv"' },
    });

    expect(disposition).toBe('attachment; filename="a.csv"');
  });

  it("propage l'erreur de l'API", () => {
    let status: number | null = null;
    api.convert(new File(['x'], 'a.txt')).subscribe({ error: (e) => (status = e.status) });

    http
      .expectOne(`${environment.apiUrl}/conversions`)
      .flush(new Blob(['{}']), { status: 422, statusText: 'Unprocessable Entity' });

    expect(status).toBe(422);
  });
});
