<?php

namespace App\Service;

use App\Exception\BillingUnavailableException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class HttpClient
{
    public function makeRequest(
        string $method,
        string $url,
        ?string $token,
        ?string $data,
        int $expectedStatusCode,
        string $errorMessage,
        array $statusCodeMessages = []
    ): array {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        $headers = ['Accept: application/json', 'Content-Type: application/json'];
        if ($token) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new BillingUnavailableException('Сервис биллинга временно недоступен.');
        }

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $responseData = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new BillingUnavailableException('Ошибка при обработке ответа от сервиса биллинга.');
        }

        if ($statusCode !== $expectedStatusCode) {
            $message = $statusCodeMessages[$statusCode] ?? $responseData['message'] ?? $errorMessage;
            if ($statusCode === 401) {
                throw new AuthenticationException($message);
            }
            throw new \Exception($message, $statusCode);
        }

        return $responseData;
    }
}