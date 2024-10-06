<?php

namespace App\Service;

use App\Entity\Course;

class BillingClient
{
    private UserBillingClient $userBillingClient;
    private CourseBillingClient $courseBillingClient;
    private TransactionBillingClient $transactionBillingClient;

    public function __construct(
        UserBillingClient $userBillingClient,
        CourseBillingClient $courseBillingClient,
        TransactionBillingClient $transactionBillingClient
    ) {
        $this->userBillingClient = $userBillingClient;
        $this->courseBillingClient = $courseBillingClient;
        $this->transactionBillingClient = $transactionBillingClient;
    }

    public function authenticate(string $email, string $password): array
    {
        return $this->userBillingClient->authenticate($email, $password);
    }

    public function getCurrentUser(string $token): array
    {
        return $this->userBillingClient->getCurrentUser($token);
    }

    public function registerUserInBilling(string $email, string $password): array
    {
        return $this->userBillingClient->registerUserInBilling($email, $password);
    }

    public function refreshToken(string $refreshToken): array
    {
        return $this->userBillingClient->refreshToken($refreshToken);
    }

    public function getTransactionHistory(string $token, array $filters = []): array
    {
        return $this->transactionBillingClient->getTransactionHistory($token, $filters);
    }

    public function payCourse(string $courseCode, string $token, string $type): array
    {
        return $this->courseBillingClient->payCourse($courseCode, $token, $type);
    }

    public function createCourseInBilling(Course $course, string $token): void
    {
        $this->courseBillingClient->createCourseInBilling($course, $token);
    }

    public function updateCourseInBilling(Course $course, string $token): void
    {
        $this->courseBillingClient->updateCourseInBilling($course, $token);
    }

    public function deleteCourseInBilling(string $courseCode, string $token): void
    {
        $this->courseBillingClient->deleteCourseInBilling($courseCode, $token);
    }
}