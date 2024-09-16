<?php

namespace App\Service;

use Symfony\Component\Security\Core\Exception\AuthenticationException;
use App\Exception\BillingUnavailableException;

class BillingClient
{
    private string $billingUrl;

    public function __construct(string $billingUrl)
    {
        $this->billingUrl = $billingUrl;
    }

    /**
     * Метод для аутентификации пользователя.
     * Отправляет запрос к биллингу с email и паролем, возвращает данные с JWT-токеном.
     * 
     * @param string $email
     * @param string $password
     * 
     * @return array Массив с данными о пользователе (токен).
     * @throws BillingUnavailableException Если сервис недоступен или аутентификация не удалась.
     */
    public function authenticate(string $email, string $password): array
    {
        $url = $this->billingUrl . '/api/v1/auth';  // Убедитесь, что это правильный endpoint для авторизации

        $data = json_encode([
            'email' => $email,
            'password' => $password,
        ]);

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new BillingUnavailableException('Сервис биллинга временно недоступен.');
        }

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($statusCode !== 200) {
            throw new BillingUnavailableException('Неверные учетные данные.');
        }

        $responseData = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new BillingUnavailableException('Ошибка при обработке ответа от сервиса биллинга.');
        }

        return $responseData; // Возвращаем ответ с токеном
    }


    /**
     * Метод для получения данных текущего пользователя по JWT-токену.
     * 
     * @param string $token JWT токен пользователя.
     * 
     * @return array Массив с данными о пользователе (email, роли, баланс).
     * @throws BillingUnavailableException Если сервис недоступен или токен недействителен.
     */

    public function getCurrentUser(string $token): array
    {
        $url = $this->billingUrl . '/api/v1/users/current';

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Accept: application/json',
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new BillingUnavailableException('Сервис биллинга временно недоступен.');
        }

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $responseData = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Ошибка при обработке ответа от сервиса биллинга.');
        }

        if ($statusCode === 401) {
            throw new AuthenticationException('Токен истек.');
        }

        if ($statusCode !== 200) {
            throw new \Exception('Не удалось получить данные пользователя: ' . ($responseData['error'] ?? 'Неизвестная ошибка.'));
        }

        return $responseData;
    }

    public function registerUserInBilling(string $email, string $password): array
    {
        $url = $this->billingUrl . '/api/v1/register';

        $data = json_encode([
            'email' => $email,
            'password' => $password,
        ]);

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new BillingUnavailableException('Сервис биллинга временно недоступен.');
        }

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($statusCode !== 201) {
            throw new BillingUnavailableException('Ошибка при регистрации.');
        }

        $responseData = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new BillingUnavailableException('Ошибка при обработке ответа от сервиса биллинга.');
        }

        return $responseData; // Возвращаем ответ с токеном
    }

    /**
     * Метод для обновления JWT-токена по refresh_token.
     *
     * @param string $refreshToken
     * @return array
     * @throws BillingUnavailableException
     */
    public function refreshToken(string $refreshToken): array
    {
        $url = $this->billingUrl . '/api/v1/token/refresh';

        $data = json_encode([
            'refresh_token' => $refreshToken,
        ]);

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new BillingUnavailableException('Сервис биллинга временно недоступен.');
        }

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($statusCode !== 200) {
            throw new \Exception('Не удалось обновить токен.');
        }

        $responseData = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Ошибка при обработке ответа от сервиса биллинга.');
        }

        return $responseData;
    }
}