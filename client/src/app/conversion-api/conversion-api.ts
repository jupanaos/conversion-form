import { HttpClient, HttpResponse } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';

@Injectable({ providedIn: 'root' })
export class ConversionApi {
  private readonly http = inject(HttpClient);

  convert(file: File): Observable<HttpResponse<Blob>> {
    const formData = new FormData();
    formData.append('file', file);

    return this.http.post(`${environment.apiUrl}/conversions`, formData, {
      observe: 'response',
      responseType: 'blob',
    });
  }
}
