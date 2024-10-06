<?php

namespace App\Service;

class UserBillingClient
{
    private string $billingUrl;
    private HttpClient $httpClient;

    public function __construct(string $billingUrl, HttpClient $httpClient)
    {
        $this->billingUrl = $billingUrl;
        $this->httpClient = $httpClient;
    }

    public function authenticate(string $email, string $password): array
    {
        $url = $this->billingUrl . '/api/v1/auth';
        $data = json_encode([
            'email' => $email,
            'password' => $password,
        ]);

        return $this->httpClient->makeRequest('POST', $url, null, $data, 200, 'Неверные учетные данные.');
    }

    public function getCurrentUser(string $token): array
    {
        $url = $this->billingUrl . '/api/v1/users/current';

        return $this->httpClient->makeRequest('GET', $url, $token, null, 200, 'Не удалось получить данные пользователя.', [401 => 'Токен истек.']);
    }

    public function registerUserInBilling(string $email, string $password): array
    {
        $url = $this->billingUrl . '/api/v1/register';
        $data = json_encode([
            'email' => $email,
            'password' => $password,
        ]);

        return $this->httpClient->makeRequest('POST', $url, null, $data, 201, 'Ошибка при регистрации.');
    }

    public function refreshToken(string $refreshToken): array
    {
        $url = $this->billingUrl . '/api/v1/token/refresh';
        $data = json_encode(['refresh_token' => $refreshToken]);

        return $this->httpClient->makeRequest('POST', $url, null, $data, 200, 'Не удалось обновить токен.');
    }
}