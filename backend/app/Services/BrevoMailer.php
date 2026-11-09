<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class BrevoMailer
{
    private const PRODUCT_NAME = 'Como emagrecer rápido para o verão';

    private const PRODUCT_PRICE = 'R$ 12,99';

    private const PDF_FILENAME = 'Como_Emagrecer_Rapido_para_o_Verao.pdf';

    public function sendPurchaseEmail(Order $order): void
    {
        if (! $order->isPaid()) {
            return;
        }

        if ($order->email_sent_at !== null) {
            return;
        }

        $email = trim((string) $order->email);
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $name = trim((string) ($order->customer_name ?: 'Cliente'));
        $attachment = $this->pdfAttachment();

        $this->send(
            toEmail: $email,
            toName: $name,
            subject: 'Compra realizada — seu e-book está em anexo',
            html: $this->html($name),
            text: $this->text($name),
            attachment: $attachment,
        );

        $order->email_sent_at = now();
        $order->save();
    }

    private function send(
        string $toEmail,
        string $toName,
        string $subject,
        string $html,
        string $text,
        array $attachment,
    ): void {
        $apiKey = (string) config('services.brevo.key');
        if ($apiKey === '') {
            throw new RuntimeException('BREVO_API_KEY não configurada.');
        }

        $response = Http::withHeaders([
            'api-key' => $apiKey,
            'Accept' => 'application/json',
        ])->acceptJson()->timeout(60)->post('https://api.brevo.com/v3/smtp/email', [
            'sender' => [
                'name' => (string) config('services.brevo.sender_name'),
                'email' => (string) config('services.brevo.sender_email'),
            ],
            'to' => [[
                'email' => $toEmail,
                'name' => $toName,
            ]],
            'subject' => $subject,
            'htmlContent' => $html,
            'textContent' => $text,
            'attachment' => [$attachment],
        ]);

        if ($response->failed()) {
            $message = (string) ($response->json('message') ?? $response->body());
            Log::warning('Falha no envio Brevo', [
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);

            throw new RuntimeException($message !== '' ? $message : 'Não foi possível enviar o e-mail pelo Brevo.');
        }
    }

    private function pdfAttachment(): array
    {
        $path = app(EbookPath::class)->resolve();
        if ($path === null) {
            throw new RuntimeException('Arquivo do e-book não encontrado para anexo.');
        }

        $contents = file_get_contents($path);
        if ($contents === false || $contents === '') {
            throw new RuntimeException('Não foi possível ler o PDF do e-book.');
        }

        return [
            'name' => self::PDF_FILENAME,
            'content' => base64_encode($contents),
        ];
    }

    private function html(string $name): string
    {
        $safeName = e($name);
        $product = e(self::PRODUCT_NAME);
        $price = e(self::PRODUCT_PRICE);
        $file = e(self::PDF_FILENAME);

        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Compra realizada</title>
</head>
<body style="margin:0;padding:0;background:#f4ebe3;font-family:Georgia,'Times New Roman',serif;color:#1b140f;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4ebe3;padding:36px 14px;">
    <tr>
      <td align="center">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:24px;overflow:hidden;border:1px solid #ead3c3;">
          <tr>
            <td style="background:linear-gradient(135deg,#123049 0%,#1c4666 100%);padding:34px 36px 30px;">
              <p style="margin:0 0 10px;font-family:Arial,Helvetica,sans-serif;color:#ffb089;font-size:11px;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;">Loja Virtual RBS</p>
              <h1 style="margin:0 0 10px;color:#ffffff;font-size:30px;line-height:1.2;font-weight:700;">Compra realizada</h1>
              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;color:#d7e6f2;font-size:15px;line-height:1.5;">Seu pagamento foi confirmado e o e-book já está com você.</p>
            </td>
          </tr>
          <tr>
            <td style="padding:30px 36px 8px;font-family:Arial,Helvetica,sans-serif;">
              <p style="margin:0 0 16px;font-size:16px;line-height:1.6;color:#1b140f;">Olá, <strong>{$safeName}</strong>.</p>
              <p style="margin:0 0 22px;font-size:16px;line-height:1.65;color:#3d2f28;">Obrigado pela confiança. Sua compra foi concluída com sucesso. O e-book <strong>{$product}</strong> está <strong>em anexo neste e-mail</strong> — abra o arquivo PDF para começar.</p>

              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 22px;background:#fff8f2;border:1px solid #f0d4c2;border-radius:18px;">
                <tr>
                  <td style="padding:20px 22px;">
                    <p style="margin:0 0 8px;color:#e15a24;font-size:11px;font-weight:700;letter-spacing:0.14em;text-transform:uppercase;">Resumo da compra</p>
                    <p style="margin:0 0 8px;font-size:20px;line-height:1.35;color:#123049;font-weight:700;">{$product}</p>
                    <p style="margin:0 0 16px;font-size:14px;line-height:1.65;color:#6d564a;">Guia prático em PDF para homens. Quatro passos claros: mindset, roupas, alimentação e treino — para chegar ao verão mais leve, sem dieta de prisão e sem rotina impossível.</p>
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #f0d4c2;">
                      <tr>
                        <td style="padding:14px 0 0;font-size:14px;color:#123049;"><strong>Valor pago</strong></td>
                        <td align="right" style="padding:14px 0 0;font-size:14px;color:#123049;font-weight:700;">{$price} · PIX</td>
                      </tr>
                      <tr>
                        <td style="padding:8px 0 0;font-size:14px;color:#6d564a;"><strong>Anexo</strong></td>
                        <td align="right" style="padding:8px 0 0;font-size:13px;color:#6d564a;">{$file}</td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>

              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 8px;background:#123049;border-radius:16px;">
                <tr>
                  <td style="padding:18px 22px;">
                    <p style="margin:0 0 6px;color:#ffb089;font-size:11px;font-weight:700;letter-spacing:0.14em;text-transform:uppercase;">Importante</p>
                    <p style="margin:0;color:#ffffff;font-size:15px;line-height:1.6;">O e-book está em anexo neste e-mail. Baixe o PDF anexado para acessar o conteúdo completo.</p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="padding:18px 36px 30px;font-family:Arial,Helvetica,sans-serif;">
              <p style="margin:0;padding-top:18px;border-top:1px solid #f0d4c2;font-size:12px;line-height:1.55;color:#8a7367;">Material educativo. Não substitui orientação médica, nutricional ou de educação física.</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
    }

    private function text(string $name): string
    {
        return "Olá, {$name}.\n\n"
            ."Compra realizada com sucesso.\n\n"
            .'Produto: '.self::PRODUCT_NAME."\n"
            ."Descrição: Guia prático em PDF para homens (mindset, roupas, alimentação e treino).\n"
            .'Valor pago: '.self::PRODUCT_PRICE." no PIX\n\n"
            ."O e-book está em anexo neste e-mail.\n"
            .'Arquivo: '.self::PDF_FILENAME."\n\n"
            ."Baixe o PDF anexado para acessar o conteúdo completo.\n";
    }
}
