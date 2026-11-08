import { Component, OnDestroy, inject, signal } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { CheckoutService, OrderStatus } from './checkout.service';

@Component({
  selector: 'app-payment',
  imports: [RouterLink],
  templateUrl: './payment.component.html',
})
export class PaymentComponent implements OnDestroy {
  private readonly route = inject(ActivatedRoute);
  private readonly checkout = inject(CheckoutService);
  private timer = 0;

  pedido = signal<OrderStatus | null>(null);
  erro = signal('');

  constructor() {
    const id = this.route.snapshot.paramMap.get('id') ?? '';
    const consultar = () => {
      this.checkout.consultar(id).subscribe({
        next: (pedido) => {
          this.pedido.set(pedido);
          if (pedido.paid) {
            window.clearInterval(this.timer);
          }
        },
        error: () => this.erro.set('Não encontrei esse pedido.'),
      });
    };
    consultar();
    this.timer = window.setInterval(consultar, 4000);
  }

  ngOnDestroy(): void {
    window.clearInterval(this.timer);
  }
}
