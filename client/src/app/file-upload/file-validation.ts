export const MAX_SIZE_BYTES = 2 * 1024 * 1024;

export function validateFile(file: { name: string; size: number }): string | null {
  if (!file.name.toLowerCase().endsWith('.txt')) {
    return 'Seuls les fichiers .txt sont acceptés.';
  }
  if (file.size === 0) {
    return 'Le fichier est vide.';
  }
  if (file.size > MAX_SIZE_BYTES) {
    return `Le fichier dépasse la taille maximale autorisée (${MAX_SIZE_BYTES / 1024 / 1024} Mo).`;
  }
  return null;
}
