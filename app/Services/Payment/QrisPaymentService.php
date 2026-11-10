<?php

namespace App\Services\Payment;

use App\Models\PaymentTransaction;
use App\Models\PaymentWebhookEvent;
use App\Models\Sale;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class QrisPaymentService
{
    public function __construct(
        protected MidtransPaymentAdapter $adapter
    ) {}

    /**
     * Inisiasi transaksi QRIS Dinamis.
     */
    public function initiateQris(float $amount, ?int $userId = null, ?string $customerName = null, array $metadata = []): PaymentTransaction
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal pembayaran QRIS harus lebih dari Rp 0.',
            ]);
        }

        $orderId = 'QRIS-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(6));
        $expiresAt = now()->addMinutes(15);

        // Panggil adapter untuk request QRIS
        $gatewayResult = $this->adapter->generateQris($orderId, $amount, [
            'name' => $customerName ?: 'Pelanggan Umum',
        ]);

        return PaymentTransaction::create([
            'order_id' => $orderId,
            'user_id' => $userId,
            'payment_method' => 'qris',
            'provider' => $gatewayResult['provider'] ?? 'midtrans',
            'gross_amount' => $amount,
            'status' => 'pending',
            'qr_string' => $gatewayResult['qr_string'] ?? null,
            'qr_image_url' => $gatewayResult['qr_image_url'] ?? null,
            'customer_name' => $customerName ?: 'Pelanggan Umum',
            'expires_at' => $expiresAt,
            'payload' => $gatewayResult['raw'] ?? null,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Periksa status pembayaran transaksi QRIS (ke gateway atau database).
     */
    public function checkStatus(string $orderId): PaymentTransaction
    {
        $transaction = PaymentTransaction::where('order_id', $orderId)->firstOrFail();

        // Jika sudah lunas atau sudah expire/batal, tidak perlu request gateway lagi
        if (!$transaction->isPending()) {
            return $transaction;
        }

        // Periksa apakah waktu sudah kedaluwarsa
        if ($transaction->expires_at && $transaction->expires_at->isPast()) {
            $transaction->update([
                'status' => 'expire',
            ]);
            return $transaction;
        }

        // Request ke gateway jika status masih pending
        $gatewayStatus = $this->adapter->checkStatus($orderId);
        $newStatus = $gatewayStatus['status'];

        if ($newStatus === 'settlement' || $newStatus === 'paid') {
            $transaction->update([
                'status' => 'settlement',
                'paid_at' => $gatewayStatus['paid_at'] ? Carbon::parse($gatewayStatus['paid_at']) : now(),
                'payload' => array_merge((array) $transaction->payload, ['latest_status' => $gatewayStatus['raw']]),
            ]);
        } elseif (in_array($newStatus, ['cancelled', 'expire', 'failed'])) {
            $transaction->update([
                'status' => $newStatus,
                'payload' => array_merge((array) $transaction->payload, ['latest_status' => $gatewayStatus['raw']]),
            ]);
        }

        return $transaction->fresh();
    }

    /**
     * Simulasi pembayaran QRIS sukses (untuk pengujian & demo kasir).
     */
    public function simulatePayment(string $orderId): PaymentTransaction
    {
        $transaction = PaymentTransaction::where('order_id', $orderId)->firstOrFail();

        if ($transaction->isPaid()) {
            return $transaction;
        }

        if ($transaction->isExpired() && $transaction->status === 'expire') {
            throw ValidationException::withMessages([
                'status' => 'Transaksi QRIS ini sudah kedaluwarsa.',
            ]);
        }

        $transaction->update([
            'status' => 'settlement',
            'paid_at' => now(),
            'payload' => array_merge((array) $transaction->payload, [
                'simulated' => true,
                'simulated_at' => now()->toIso8601String(),
            ]),
        ]);

        return $transaction->fresh();
    }

    /**
     * Batalkan transaksi QRIS yang masih berstatus pending.
     */
    public function cancelPayment(string $orderId): PaymentTransaction
    {
        $transaction = PaymentTransaction::where('order_id', $orderId)->firstOrFail();

        if ($transaction->isPaid()) {
            throw ValidationException::withMessages([
                'status' => 'Transaksi QRIS ini sudah dibayar dan tidak dapat dibatalkan.',
            ]);
        }

        $this->adapter->cancelTransaction($orderId);

        $transaction->update([
            'status' => 'cancelled',
        ]);

        return $transaction->fresh();
    }

    /**
     * Kaitkan transaksi pembayaran dengan Sale ID setelah checkout POS selesai.
     */
    public function attachSale(string $orderId, int $saleId): PaymentTransaction
    {
        $transaction = PaymentTransaction::where('order_id', $orderId)->firstOrFail();
        $transaction->update([
            'sale_id' => $saleId,
        ]);

        return $transaction->fresh();
    }

    /**
     * Tangani incoming webhook dari Payment Gateway (Midtrans).
     */
    public function handleWebhook(array $payload, ?string $signature = null): array
    {
        return DB::transaction(function () use ($payload, $signature) {
            $parsed = $this->adapter->parseWebhook($payload, $signature);
            $orderId = $parsed['order_id'];

            // Log event webhook untuk audit trail
            $event = PaymentWebhookEvent::create([
                'provider' => 'midtrans',
                'order_id' => $orderId,
                'event_type' => $parsed['payment_type'] ?? 'qris',
                'payload' => $payload,
                'signature' => $signature,
                'processed' => false,
            ]);

            if (!$parsed['is_valid']) {
                $event->update([
                    'error_message' => 'Invalid webhook signature',
                ]);
                return ['success' => false, 'message' => 'Invalid signature'];
            }

            if ($orderId) {
                $transaction = PaymentTransaction::where('order_id', $orderId)->first();

                if ($transaction) {
                    $newStatus = $parsed['status'];

                    $updateData = [
                        'status' => $newStatus,
                        'payload' => array_merge((array) $transaction->payload, ['webhook' => $payload]),
                    ];

                    if ($newStatus === 'settlement' && !$transaction->paid_at) {
                        $updateData['paid_at'] = $parsed['settlement_time'] ? Carbon::parse($parsed['settlement_time']) : now();
                    }

                    $transaction->update($updateData);

                    $event->update([
                        'processed' => true,
                        'processed_at' => now(),
                    ]);

                    return [
                        'success' => true,
                        'message' => 'Transaction updated from webhook',
                        'status' => $newStatus,
                    ];
                }
            }

            $event->update([
                'processed' => true,
                'processed_at' => now(),
                'error_message' => 'Order ID not found in system',
            ]);

            return ['success' => true, 'message' => 'Event logged, order not found'];
        });
    }
}
