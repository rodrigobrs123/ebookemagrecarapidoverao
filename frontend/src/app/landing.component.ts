import { HttpErrorResponse } from '@angular/common/http';
import { Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { CheckoutService } from './checkout.service';

@Component({
  selector: 'app-landing',
  imports: [FormsModule],
  templateUrl: './landing.component.html',
})
export class LandingComponent {
  private readonly checkout = inject(CheckoutService);

  nome = '';
  email = '';
  cpf = '';
  enviando = signal(false);
  erro = signal('');

  pagar(): void {
    this.erro.set('');
    this.enviando.set(true);
    this.checkout.criar({
      name: this.nome,
      email: this.email,
      cpfCnpj: this.cpf,
    }).subscribe({
      next: (resposta) => {
        window.location.href = resposta.checkoutUrl;
      },
      error: (erro: HttpErrorResponse) => {
        this.enviando.set(false);
        this.erro.set(erro.error?.message ?? 'Não foi possível iniciar o pagamento.');
      },
    });
  }
}
