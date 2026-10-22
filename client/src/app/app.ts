import { DOCUMENT } from '@angular/common';
import { Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NzSwitchModule } from 'ng-zorro-antd/switch';
import { FileUploadComponent } from './file-upload/file-upload';

@Component({
  imports: [FileUploadComponent, FormsModule, NzSwitchModule],
  selector: 'app-root',
  styleUrl: './app.css',
  templateUrl: './app.html',
})
export class App {
  private readonly document = inject(DOCUMENT);

  protected readonly dark = signal(
    this.document.defaultView?.matchMedia?.('(prefers-color-scheme: dark)').matches ?? false,
  );

  protected setDark(value: boolean): void {
    this.dark.set(value);
    this.document.documentElement.dataset['theme'] = value ? 'dark' : 'light';
  }
}
