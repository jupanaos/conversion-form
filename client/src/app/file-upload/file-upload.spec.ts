import { InboxOutline } from '@ant-design/icons-angular/icons';
import { provideNzIcons } from 'ng-zorro-antd/icon';
import { HttpErrorResponse, HttpHeaders, HttpResponse } from '@angular/common/http';
import { TestBed } from '@angular/core/testing';
import { NzMessageService } from 'ng-zorro-antd/message';
import { Subject, of, throwError } from 'rxjs';

import { ConversionApi } from '../conversion-api/conversion-api';
import { FileUploadComponent } from './file-upload';

describe('FileUploadComponent', () => {
  const convert = vi.fn();
  const message = { error: vi.fn(), success: vi.fn() };

  function setup() {
    TestBed.configureTestingModule({
      imports: [FileUploadComponent],
      providers: [
        provideNzIcons([InboxOutline]),
        { provide: ConversionApi, useValue: { convert } },
        { provide: NzMessageService, useValue: message },
      ],
    });
    const fixture = TestBed.createComponent(FileUploadComponent);
    return { fixture, el: fixture.nativeElement as HTMLElement };
  }

  async function selectFile(fixture: ReturnType<typeof setup>['fixture'], file: File) {
    await fixture.whenStable();
    const input = fixture.nativeElement.querySelector('input[type="file"]') as HTMLInputElement;
    Object.defineProperty(input, 'files', { value: [file], configurable: true });
    input.dispatchEvent(new Event('change'));
    await fixture.whenStable();
  }

  const button = (el: HTMLElement) => el.querySelector<HTMLButtonElement>('button.convert-btn')!;

  async function clickConvert(fixture: ReturnType<typeof setup>['fixture']) {
    button(fixture.nativeElement).click();
    await fixture.whenStable();
  }

  function csvResponse(headers: Record<string, string> = {}) {
    return new HttpResponse({ body: new Blob(['a;b']), headers: new HttpHeaders(headers) });
  }

  function apiError(status: number, body?: unknown) {
    return new HttpErrorResponse({
      status,
      error: body === undefined ? null : new Blob([JSON.stringify(body)]),
    });
  }

  let click: ReturnType<typeof vi.spyOn>;
  let downloadedAs: string | undefined;

  beforeEach(() => {
    convert.mockReset();
    message.error.mockReset();
    message.success.mockReset();
    URL.createObjectURL = vi.fn(() => 'blob:fake');
    URL.revokeObjectURL = vi.fn();
    downloadedAs = undefined;
    click = vi.spyOn(HTMLAnchorElement.prototype, 'click').mockImplementation(function (
      this: HTMLAnchorElement,
    ) {
      downloadedAs = this.download;
    });
  });

  afterEach(() => click.mockRestore());

  describe('choix du fichier', () => {
    it('désactive "Convertir" tant qu\'aucun fichier n\'est choisi', async () => {
      const { fixture, el } = setup();
      await fixture.whenStable();

      expect(button(el).disabled).toBe(true);
    });

    it('affiche le nom du fichier valide et active "Convertir"', async () => {
      const { fixture, el } = setup();
      await selectFile(fixture, new File(['x'], 'vente.txt'));

      expect(el.textContent).toContain('vente.txt');
      expect(button(el).disabled).toBe(false);
      expect(message.error).not.toHaveBeenCalled();
    });

    it.each([
      ['une mauvaise extension', new File(['x'], 'vente.csv'), '.txt'],
      ['un fichier vide', new File([], 'vente.txt'), 'vide'],
      [
        'un fichier trop gros',
        new File([new Uint8Array(2 * 1024 * 1024 + 1)], 'vente.txt'),
        '2 Mo',
      ],
    ])("refuse %s sans appeler l'API", async (_label, file, expected) => {
      const { fixture, el } = setup();
      await selectFile(fixture, file);

      expect(message.error).toHaveBeenCalledWith(expect.stringContaining(expected));
      expect(button(el).disabled).toBe(true);
      expect(convert).not.toHaveBeenCalled();
    });

    it('remplace le fichier précédent par un nouveau fichier valide', async () => {
      const { fixture, el } = setup();
      await selectFile(fixture, new File(['x'], 'premier.txt'));
      await selectFile(fixture, new File(['x'], 'second.txt'));

      expect(el.textContent).toContain('second.txt');
      expect(el.textContent).not.toContain('premier.txt');
    });

    it('garde le fichier valide si le suivant est refusé', async () => {
      const { fixture, el } = setup();
      await selectFile(fixture, new File(['x'], 'vente.txt'));
      await selectFile(fixture, new File(['x'], 'vente.csv'));

      expect(el.textContent).toContain('vente.txt');
      expect(button(el).disabled).toBe(false);
    });

    it('désactive "Convertir" quand on retire le fichier', async () => {
      const { fixture, el } = setup();
      await selectFile(fixture, new File(['x'], 'vente.txt'));

      el.querySelector<HTMLElement>('button.ant-upload-list-item-card-actions-btn')!.click();
      await fixture.whenStable();

      expect(button(el).disabled).toBe(true);
    });
  });

  describe('conversion', () => {
    it('envoie le fichier et affiche le chargement', async () => {
      const pending = new Subject<HttpResponse<Blob>>();
      convert.mockReturnValue(pending);
      const { fixture, el } = setup();
      const file = new File(['x'], 'vente.txt');
      await selectFile(fixture, file);

      await clickConvert(fixture);

      expect(convert).toHaveBeenCalledExactlyOnceWith(file);
      expect(button(el).textContent).toContain('Conversion');
      expect(button(el).disabled).toBe(true);
    });

    it('ne lance pas deux conversions en parallèle', async () => {
      convert.mockReturnValue(new Subject<HttpResponse<Blob>>());
      const { fixture } = setup();
      await selectFile(fixture, new File(['x'], 'vente.txt'));

      await clickConvert(fixture);
      await clickConvert(fixture);

      expect(convert).toHaveBeenCalledTimes(1);
    });

    it('télécharge le CSV sous le nom de Content-Disposition', async () => {
      convert.mockReturnValue(
        of(csvResponse({ 'Content-Disposition': 'attachment; filename="vente.csv"' })),
      );
      const { fixture, el } = setup();
      await selectFile(fixture, new File(['x'], 'source.txt'));

      await clickConvert(fixture);

      expect(downloadedAs).toBe('vente.csv');
      expect(URL.revokeObjectURL).toHaveBeenCalledWith('blob:fake');
      expect(message.success).toHaveBeenCalledWith(expect.stringContaining('source.txt'));
      expect(button(el).textContent).toContain('Convertir');
      expect(button(el).disabled).toBe(false);
    });

    it('décode un nom de fichier accentué (filename*)', async () => {
      convert.mockReturnValue(
        of(
          csvResponse({ 'Content-Disposition': "attachment; filename*=UTF-8''%C3%A9t%C3%A9.csv" }),
        ),
      );
      const { fixture } = setup();
      await selectFile(fixture, new File(['x'], 'source.txt'));

      await clickConvert(fixture);

      expect(downloadedAs).toBe('été.csv');
    });

    it('se rabat sur le nom source en .csv sans Content-Disposition', async () => {
      convert.mockReturnValue(of(csvResponse()));
      const { fixture } = setup();
      await selectFile(fixture, new File(['x'], 'VENTE.TXT'));

      await clickConvert(fixture);

      expect(downloadedAs).toBe('VENTE.csv');
    });

    it('ne télécharge rien si la réponse est vide', async () => {
      convert.mockReturnValue(of(new HttpResponse<Blob>({ body: null })));
      const { fixture } = setup();
      await selectFile(fixture, new File(['x'], 'vente.txt'));

      await clickConvert(fixture);

      expect(click).not.toHaveBeenCalled();
    });
  });

  describe("annonce pour les lecteurs d'écran", () => {
    it("recopie le message d'erreur dans la zone role=status", async () => {
      const { fixture, el } = setup();
      await selectFile(fixture, new File(['x'], 'vente.csv'));

      expect(el.querySelector('[role="status"]')!.textContent).toContain('.txt');
    });

    it('recopie le message de succès dans la zone role=status', async () => {
      convert.mockReturnValue(of(csvResponse()));
      const { fixture, el } = setup();
      await selectFile(fixture, new File(['x'], 'vente.txt'));

      await clickConvert(fixture);

      expect(el.querySelector('[role="status"]')!.textContent).toContain('vente.txt');
    });
  });

  describe('erreurs', () => {
    it.each([
      [400, 'Le fichier est vide.'],
      [413, 'Le fichier dépasse la taille maximale autorisée (2 Mo).'],
      [415, 'Type de fichier non pris en charge (extensions acceptées : txt).'],
      [422, 'Ligne 3 : montant invalide'],
    ])("affiche le message de l'API pour une %i", async (status, text) => {
      convert.mockReturnValue(throwError(() => apiError(status, { error: { message: text } })));
      const { fixture } = setup();
      await selectFile(fixture, new File(['x'], 'vente.txt'));

      await clickConvert(fixture);
      await vi.waitFor(() => expect(message.error).toHaveBeenCalledWith(text));
    });

    it("affiche un message quand l'API ne répond pas", async () => {
      convert.mockReturnValue(throwError(() => new HttpErrorResponse({ status: 0 })));
      const { fixture } = setup();
      await selectFile(fixture, new File(['x'], 'vente.txt'));

      await clickConvert(fixture);
      await vi.waitFor(() => expect(message.error).toHaveBeenCalledWith("L'API ne répond pas."));
    });

    it('affiche une erreur interne si la réponse est illisible', async () => {
      convert.mockReturnValue(throwError(() => apiError(500)));
      const { fixture } = setup();
      await selectFile(fixture, new File(['x'], 'vente.txt'));

      await clickConvert(fixture);
      await vi.waitFor(() => expect(message.error).toHaveBeenCalledWith('Erreur interne (500).'));
    });

    it('permet de réessayer avec le même fichier après une erreur', async () => {
      convert.mockReturnValue(
        throwError(() => apiError(422, { error: { message: 'Ligne 1 : x' } })),
      );
      const { fixture, el } = setup();
      await selectFile(fixture, new File(['x'], 'vente.txt'));

      await clickConvert(fixture);
      await vi.waitFor(() => expect(message.error).toHaveBeenCalled());

      expect(el.textContent).toContain('vente.txt');
      expect(button(el).disabled).toBe(false);
      expect(button(el).textContent).toContain('Convertir');
    });
  });
});
