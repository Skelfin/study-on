<?php

namespace App\Service;

use App\Entity\Course;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use App\Exception\BillingUnavailableException;
use App\Exception\InsufficientFundsException;

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
        $url = $this->billingUrl . '/api/v1/auth';

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

        return $responseData;
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

        return $responseData;
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

    public function getTransactionHistory(string $token, array $filters = []): array
    {
        $url = $this->billingUrl . '/api/v1/transactions';

        if (!empty($filters)) {
            $url .= '?' . http_build_query(['filter' => $filters]);
        }

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

        if ($statusCode !== 200) {
            throw new \Exception('Не удалось получить транзакции: ' . ($responseData['error'] ?? 'Неизвестная ошибка.'));
        }

        return $responseData;
    }

    public function payCourse(string $courseCode, string $token, string $type): array
    {
        $url = $this->billingUrl . '/api/v1/courses/' . $courseCode . '/pay';

        $data = json_encode(['type' => $type]);

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Accept: application/json',
            'Content-Type: application/json',
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

        if ($statusCode !== 200) {
            $message = $responseData['message'] ?? 'Не удалось оплатить курс.';
            if ($statusCode === 406) {
                throw new InsufficientFundsException($message);
            } else {
                throw new \Exception($message, $statusCode);
            }
        }

        return $responseData;
    }

    public function createCourseInBilling(Course $course, string $token): void
    {
        $url = $this->billingUrl . '/api/v1/courses/new';

        $typeMapping = [
            Course::TYPE_RENT => 'rent',
            Course::TYPE_BUY  => 'buy',
            Course::TYPE_FREE => 'free',
        ];
        $type = $typeMapping[$course->getType()] ?? 'unknown';

        $data = json_encode([
            'code' => $course->getCode(),
            'type' => $type,
            'name' => $course->getName(),
            'price' => $course->getPrice(),
        ]);

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
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
            $responseData = json_decode($response, true);
            $message = $responseData['message'] ?? 'Не удалось создать курс в биллинге.';
            throw new \Exception($message, $statusCode);
        }
    }

    public function updateCourseInBilling(Course $course, string $token): void
    {
        $url = $this->billingUrl . '/api/v1/courses/' . $course->getCode() . '/edit';

        $typeMapping = [
            Course::TYPE_RENT => 'rent',
            Course::TYPE_BUY  => 'buy',
            Course::TYPE_FREE => 'free',
        ];
        $type = $typeMapping[$course->getType()] ?? 'unknown';

        $data = json_encode([
            'type' => $type,
            'name' => $course->getName(),
            'price' => $course->getPrice(),
        ]);

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
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
            $responseData = json_decode($response, true);
            $message = $responseData['message'] ?? 'Не удалось обновить курс в биллинге.';
            throw new \Exception($message, $statusCode);
        }
    }

    public function deleteCourseInBilling(string $courseCode, string $token): void
    {
        $url = $this->billingUrl . '/api/v1/courses/' . $courseCode . '/delete';

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
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

        if ($statusCode !== 200) {
            $responseData = json_decode($response, true);
            $message = $responseData['message'] ?? 'Не удалось удалить курс в биллинге.';
            throw new \Exception($message, $statusCode);
        }
    }
}