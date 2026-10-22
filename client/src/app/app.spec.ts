import { InboxOutline } from '@ant-design/icons-angular/icons';
import { provideNzIcons } from 'ng-zorro-antd/icon';
import { TestBed } from '@angular/core/testing';
import { provideHttpClient } from '@angular/common/http';
import { App } from './app';

describe('App', () => {
  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [App],
      providers: [provideHttpClient(), provideNzIcons([InboxOutline])],
    }).compileComponents();
  });

  it('should create the app', () => {
    const fixture = TestBed.createComponent(App);
    expect(fixture.componentInstance).toBeTruthy();
  });

  it('affiche le titre', async () => {
    const fixture = TestBed.createComponent(App);
    await fixture.whenStable();
    const compiled = fixture.nativeElement as HTMLElement;
    expect(compiled.querySelector('h1')?.textContent).toContain('Ajoutez un fichier');
  });

  it("affiche le composant d'upload", async () => {
    const fixture = TestBed.createComponent(App);
    await fixture.whenStable();
    const compiled = fixture.nativeElement as HTMLElement;
    expect(compiled.querySelector('app-file-upload')).not.toBeNull();
  });

  describe('switch du thème', () => {
    afterEach(() => delete document.documentElement.dataset['theme']);

    it('est associé à un libellé visible', async () => {
      const fixture = TestBed.createComponent(App);
      await fixture.whenStable();
      const el = fixture.nativeElement as HTMLElement;

      const label = el.querySelector<HTMLLabelElement>('label[for="theme-switch"]');
      expect(label?.textContent).toContain('Mode sombre');
      expect(el.querySelector('#theme-switch')).not.toBeNull();
    });

    it('force le thème sombre puis clair sur <html>', async () => {
      const fixture = TestBed.createComponent(App);
      await fixture.whenStable();
      const toggle = (fixture.nativeElement as HTMLElement).querySelector<HTMLElement>(
        '#theme-switch',
      )!;

      toggle.click();
      await fixture.whenStable();
      expect(document.documentElement.dataset['theme']).toBe('dark');

      toggle.click();
      await fixture.whenStable();
      expect(document.documentElement.dataset['theme']).toBe('light');
    });
  });
});
