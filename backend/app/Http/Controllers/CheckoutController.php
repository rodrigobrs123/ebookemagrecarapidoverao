<?php

namespace App\Http\Controllers;

use App\Services\AsaasCheckout;
use App\Services\OrderStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;

class CheckoutController extends Controller
{
    public function store(Request $request, AsaasCheckout $asaas, OrderStore $orders): JsonResponse
    {
        $order = $orders->create([
            'public_id' => (string) Str::uuid(),
            'download_token' => Str::random(48),
            'amount' => 12.99,
            'status' => 'pending',
        ]);

        try {
            $checkout = $asaas->create($order, $request->string('origin')->toString() ?: null);
        } catch (RuntimeException $exception) {
            $order->delete();

            return response()->json([
                'message' => $this->mensagemCliente($exception->getMessage()),
            ], 422);
        }

        $order->asaas_checkout_id = $checkout['id'];
        $order->save();

        $link = $checkout['link'] ?? 'https://sandbox.asaas.com/checkoutSession/show/'.$checkout['id'];

        return response()->json([
            'publicId' => $order->public_id,
            'checkoutUrl' => $link,
        ]);
    }

    public function show(string $publicId, OrderStore $orders): JsonResponse
    {
        $order = $orders->findByPublicId($publicId);
        if ($order === null) {
            abort(404);
        }

        return response()->json([
            'publicId' => $order->public_id,
            'status' => $order->status,
            'paid' => $order->isPaid(),
            'downloadPath' => $order->isPaid()
                ? '/api/downloads/'.$order->download_token
                : null,
        ]);
    }

    private function mensagemCliente(string $message): string
    {
        if ($message === '' || preg_match('/ngrok|webhook|localhost|URL pública/i', $message) === 1) {
            return 'Não foi possível abrir o pagamento. Tente de novo.';
        }

        return $message;
    }
}
