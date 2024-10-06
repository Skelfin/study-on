<?php

namespace App\Service;

class TransactionBillingClient
{
    private string $billingUrl;
    private HttpClient $httpClient;

    public function __construct(string $billingUrl, HttpClient $httpClient)
    {
        $this->billingUrl = $billingUrl;
        $this->httpClient = $httpClient;
    }

    public function getTransactionHistory(string $token, array $filters = []): array
    {
        $url = $this->billingUrl . '/api/v1/transactions';

        if (!empty($filters)) {
            $url .= '?' . http_build_query(['filter' => $filters]);
        }

        return $this->httpClient->makeRequest('GET', $url, $token, null, 200, 'Не удалось получить транзакции.');
    }
}