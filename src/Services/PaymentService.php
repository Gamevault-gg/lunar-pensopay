<?php

namespace Gamevault\Pensopay\Services;

use Carbon\Carbon;
use DateTimeInterface;
use Gamevault\Pensopay\Enums\FacilitatorEnum;
use Illuminate\Http\Client\Response;
use Lunar\Models\Currency;
use Lunar\Models\Order;

class PaymentService extends BaseClient
{
    /**
     * Get single payment by id
     *
     * @param  int  $paymentId
     * @return Response
     */
    public function getPayment(int $paymentId): Response
    {
        $queryParams = [
            'payment' => $paymentId,
        ];

        return $this->pendingRequest->get($this->url(), $queryParams);
    }

    /**
     * Returns a paginated list of payments
     *
     * @param  int  $perPage
     * @param  int  $page
     * @param  string|null  $orderId
     * @param  Currency|null  $currency
     * @param  DateTimeInterface|string|null  $fromDate
     * @param  DateTimeInterface|string|null  $toDate
     * @return Response
     */
    public function getPayments(
        int $perPage = 15,
        int $page = 1,
        string $orderId = null,
        Currency $currency = null,
        DateTimeInterface|string $fromDate = null,
        DateTimeInterface|string $toDate = null
    ): Response {
        $queryParams = [
            'per_page' => $perPage,
            'page' => $page,
        ];

        if ($orderId != null) {
            $queryParams = array_merge($queryParams, [
                'order_id' => $orderId,
            ]);
        }

        if ($currency != null) {
            $queryParams = array_merge($queryParams, [
                'currency' => $currency->getAttributes()['code'],
            ]);
        }

        if ($fromDate != null) {
            $formattedDate = Carbon::parse($fromDate)->toIso8601String();

            $queryParams = array_merge($queryParams, [
                'date_from' => $formattedDate,
            ]);
        }

        if ($toDate != null) {
            $formattedDate = Carbon::parse($toDate)->toIso8601String();

            $queryParams = array_merge($queryParams, [
                'date_to' => $formattedDate,
            ]);
        }

        //Todo make class to store response.
        return $this->pendingRequest->get($this->url(), $queryParams);
    }

    /**
     * Create a new payment in the pending state, once the user has paid state will change to authorized and we'll send a callback
     *
     * @param  Order  $order
     * @param  FacilitatorEnum  $facilitator
     * @param  bool  $autoCapture
     * @param  bool  $testMode
     * @param  string|null  $successUrl
     * @param  string|null  $cancelUrl
     * @param  string|null  $callbackUrl
     * @return Response
     */
    public function createPayment(
        Order $order,
        FacilitatorEnum $facilitator,
        bool $autoCapture = false,
        bool $testMode = false,
        string $successUrl = null,
        string $cancelUrl = null,
        string $callbackUrl = null,
    ): Response {
        $payload = [
            'order_id' => $order->getAttributes()['id'],
            'facilitator' => $facilitator,
            'amount' => $order->getAttributes()['total'],
            'currency' => $order->getAttributes()['currency_code'],
            'testmode' => $testMode,
            'autocapture' => $autoCapture,
        ];

        if ($successUrl != null) {
            $payload = array_merge($payload, [
                'success_url' => $successUrl,
            ]);
        }

        if ($cancelUrl != null) {
            $payload = array_merge($payload, [
                'cancel_url' => $cancelUrl,
            ]);
        }

        if ($callbackUrl != null) {
            $payload = array_merge($payload, [
                'callback_url' => $callbackUrl,
            ]);
        }

        return $this->pendingRequest->post($this->url(), $payload);
    }

    /**
     * @return string
     */
    public function url(): string
    {
        return '/payments';
    }
}