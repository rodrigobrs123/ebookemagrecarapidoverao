<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DownloadController extends Controller
{
    public function __invoke(string $token): BinaryFileResponse
    {
        $order = Order::query()->where('download_token', $token)->first();

        if ($order === null || ! $order->isPaid()) {
            abort(403, 'O download só fica disponível depois da confirmação do pagamento.');
        }

        $path = (string) config('services.asaas.ebook_path');
        if (! is_file($path)) {
            abort(404, 'Arquivo do e-book não encontrado.');
        }

        return response()->download($path, 'Como_Emagrecer_Rapido_para_o_Verao.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
