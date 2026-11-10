<?php

namespace App\Services\Payment;

interface PaymentGatewayInterface
{
    /**
     * Generate dynamic QRIS code for an amount.
     */
    public function generateQris(string $orderId, float $amount, array $customer = []): array;

    /**
     * Check transaction status at the gateway.
     */
    public function checkStatus(string $orderId): array;

    /**
     * Cancel pending transaction at gateway.
     */
    public function cancelTransaction(string $orderId): bool;

    /**
     * Verify and parse incoming webhook payload.
     */
    public function parseWebhook(array $payload, ?string $signature = null): array;
}
