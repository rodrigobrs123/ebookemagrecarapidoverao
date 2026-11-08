import { Routes } from '@angular/router';
import { LandingComponent } from './landing.component';
import { NoticeComponent } from './notice.component';
import { PaymentComponent } from './payment.component';

export const routes: Routes = [
  { path: '', component: LandingComponent },
  {
    path: 'pagamento/cancelado',
    component: NoticeComponent,
    data: {
      titulo: 'Pagamento cancelado',
      texto: 'O checkout foi cancelado. Nenhum arquivo foi liberado.',
    },
  },
  {
    path: 'pagamento/expirado',
    component: NoticeComponent,
    data: {
      titulo: 'Checkout expirado',
      texto: 'O tempo do PIX acabou. O e-book continua bloqueado.',
    },
  },
  { path: 'pagamento/:id', component: PaymentComponent },
];
