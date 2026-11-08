import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';

export interface CheckoutResponse {
  publicId: string;
  checkoutUrl: string;
}

export interface OrderStatus {
  publicId: string;
  status: string;
  paid: boolean;
  downloadPath: string | null;
}

@Injectable({ providedIn: 'root' })
export class CheckoutService {
  private readonly http = inject(HttpClient);

  criar(dados: { name: string; email: string; cpfCnpj: string }): Observable<CheckoutResponse> {
    return this.http.post<CheckoutResponse>('/api/checkout', dados);
  }

  consultar(publicId: string): Observable<OrderStatus> {
    return this.http.get<OrderStatus>(`/api/orders/${publicId}`);
  }
}
