<?php

namespace App\Http\Controllers;

use App\Services\EbookPath;
use App\Services\OrderStore;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DownloadController extends Controller
{
    public function __invoke(string $token, OrderStore $orders, EbookPath $ebookPath): BinaryFileResponse
    {
        $order = $orders->findByDownloadToken($token);

        if ($order === null || ! $order->isPaid()) {
            abort(403, 'O download só fica disponível depois da confirmação do pagamento.');
        }

        $path = $ebookPath->resolve();
        if ($path === null) {
            abort(404, 'Arquivo do e-book não encontrado.');
        }

        return response()->download($path, 'Como_Emagrecer_Rapido_para_o_Verao.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
