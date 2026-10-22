import { MAX_SIZE_BYTES, validateFile } from './file-validation';

describe('validateFile', () => {
  it('accepte un .txt de taille normale', () => {
    expect(validateFile({ name: 'vente.txt', size: 100 })).toBeNull();
  });

  it('accepte une extension en majuscules', () => {
    expect(validateFile({ name: 'VENTE.TXT', size: 100 })).toBeNull();
  });

  it('accepte un fichier pile à la limite', () => {
    expect(validateFile({ name: 'vente.txt', size: MAX_SIZE_BYTES })).toBeNull();
  });

  it('refuse une autre extension', () => {
    expect(validateFile({ name: 'vente.csv', size: 100 })).toContain('.txt');
  });

  it('refuse un fichier vide', () => {
    expect(validateFile({ name: 'vente.txt', size: 0 })).toContain('vide');
  });

  it('refuse un fichier au-dessus de la limite', () => {
    expect(validateFile({ name: 'vente.txt', size: MAX_SIZE_BYTES + 1 })).toContain('2 Mo');
  });

  it('refuse un fichier sans extension', () => {
    expect(validateFile({ name: 'vente', size: 100 })).toContain('.txt');
  });

  it('refuse une double extension qui ne finit pas par .txt', () => {
    expect(validateFile({ name: 'vente.txt.exe', size: 100 })).toContain('.txt');
  });
});
