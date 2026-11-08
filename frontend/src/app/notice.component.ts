import { Component, inject } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';

@Component({
  selector: 'app-notice',
  imports: [RouterLink],
  templateUrl: './notice.component.html',
})
export class NoticeComponent {
  private readonly route = inject(ActivatedRoute);
  readonly titulo = this.route.snapshot.data['titulo'] as string;
  readonly texto = this.route.snapshot.data['texto'] as string;
}
