import { HttpErrorResponse, HttpResponse } from '@angular/common/http';
import { ChangeDetectionStrategy, Component, computed, inject, signal } from '@angular/core';
import { finalize } from 'rxjs';

import { NzIconModule } from 'ng-zorro-antd/icon';
import { NzMessageService } from 'ng-zorro-antd/message';
import { NzUploadModule, type NzUploadFile } from 'ng-zorro-antd/upload';

import { ConversionApi } from '../conversion-api/conversion-api';
import { errorMessageFrom } from './conversion-error';
import { validateFile } from './file-validation';

@Component({
  selector: 'app-file-upload',
  imports: [NzIconModule, NzUploadModule],
  templateUrl: './file-upload.html',
  styleUrl: './file-upload.css',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class FileUploadComponent {
  private readonly api = inject(ConversionApi);
  private readonly messageService = inject(NzMessageService);

  protected readonly fileList = signal<NzUploadFile[]>([]);
  protected readonly loading = signal(false);
  protected readonly status = signal('');
  protected readonly canConvert = computed(() => this.fileList().length > 0 && !this.loading());

  protected readonly beforeUpload = (file: NzUploadFile): boolean => {
    const error = validateFile(file as unknown as File);
    if (error) {
      this.notify('error', error);
      return false;
    }
    this.fileList.set([file]);
    return false;
  };

  protected readonly onRemove = (): boolean => {
    this.fileList.set([]);
    return true;
  };

  protected convert(): void {
    const item = this.fileList()[0];
    if (!item || this.loading()) return;
    const file = (item.originFileObj ?? item) as unknown as File;

    this.loading.set(true);
    this.api
      .convert(file)
      .pipe(finalize(() => this.loading.set(false)))
      .subscribe({
        next: (response) => {
          this.download(response, file.name);
          this.notify('success', `Le fichier ${file.name} a été converti avec succès.`);
        },
        error: async (error: HttpErrorResponse) => {
          this.notify('error', await errorMessageFrom(error));
        },
      });
  }

  private notify(type: 'success' | 'error', text: string): void {
    this.status.set(text);
    this.messageService[type](text);
  }

  private download(response: HttpResponse<Blob>, sourceName: string): void {
    const blob = response.body;
    if (!blob) return;

    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = this.filenameFrom(response) ?? sourceName.replace(/\.txt$/i, '.csv');
    link.click();
    URL.revokeObjectURL(url);
  }

  private filenameFrom(response: HttpResponse<Blob>): string | null {
    const header = response.headers.get('Content-Disposition') ?? '';
    const encoded = /filename\*=UTF-8''([^;]+)/i.exec(header)?.[1];
    if (encoded) return decodeURIComponent(encoded);
    return /filename="?([^";]+)"?/i.exec(header)?.[1] ?? null;
  }
}
