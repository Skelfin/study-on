<?php

namespace App\Service;

use App\Entity\Course;

class CourseBillingClient
{
    private string $billingUrl;
    private HttpClient $httpClient;

    public function __construct(string $billingUrl, HttpClient $httpClient)
    {
        $this->billingUrl = $billingUrl;
        $this->httpClient = $httpClient;
    }

    public function payCourse(string $courseCode, string $token, string $type): array
    {
        $url = $this->billingUrl . '/api/v1/courses/' . $courseCode . '/pay';
        $data = json_encode(['type' => $type]);

        return $this->httpClient->makeRequest('POST', $url, $token, $data, 200, 'Не удалось оплатить курс.', [
            406 => 'Недостаточно средств.',
        ]);
    }

    public function createCourseInBilling(Course $course, string $token): void
    {
        $url = $this->billingUrl . '/api/v1/courses/new';
        $data = $this->prepareCourseData($course);

        $this->httpClient->makeRequest('POST', $url, $token, $data, 201, 'Не удалось создать курс в биллинге.');
    }

    public function updateCourseInBilling(Course $course, string $token): void
    {
        $url = $this->billingUrl . '/api/v1/courses/' . $course->getCode() . '/edit';
        $data = $this->prepareCourseData($course);

        $this->httpClient->makeRequest('POST', $url, $token, $data, 200, 'Не удалось обновить курс в биллинге.');
    }

    public function deleteCourseInBilling(string $courseCode, string $token): void
    {
        $url = $this->billingUrl . '/api/v1/courses/' . $courseCode . '/delete';

        $this->httpClient->makeRequest('DELETE', $url, $token, null, 200, 'Не удалось удалить курс в биллинге.');
    }

    private function prepareCourseData(Course $course): string
    {
        $typeMapping = [
            Course::TYPE_RENT => 'rent',
            Course::TYPE_BUY  => 'buy',
            Course::TYPE_FREE => 'free',
        ];

        $type = $typeMapping[$course->getType()] ?? 'unknown';

        return json_encode([
            'code' => $course->getCode(),
            'type' => $type,
            'name' => $course->getName(),
            'price' => $course->getPrice(),
        ]);
    }
}