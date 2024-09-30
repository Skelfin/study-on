<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\BillingClient;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class ProfileController extends AbstractController
{
    private BillingClient $billingClient;

    public function __construct(BillingClient $billingClient)
    {
        $this->billingClient = $billingClient;
    }

    #[Route('/profile', name: 'profile')]
    public function profile(): Response
    {
        /** @var \App\Security\User $user */
        $user = $this->getUser();

        if (!$user || !$user->getApiToken()) {
            throw new AuthenticationException('Пользователь не авторизован.');
        }

        try {
            $userData = $this->billingClient->getCurrentUser($user->getApiToken());

            $user->setBalance($userData['balance']);
        } catch (\Exception $e) {
            return $this->render('error500.html.twig', [
                'message' => 'Не удалось получить данные профиля. Пожалуйста, попробуйте позже.',
            ]);
        }

        return $this->render('profile/profile.html.twig', [
            'email' => $userData['username'],
            'roles' => $userData['roles'],
            'balance' => $userData['balance'],
        ]);
    }

    #[Route('/profile/transactions', name: 'profile_transactions')]
    public function transactionHistory(): Response
    {
        /** @var \App\Security\User $user */
        $user = $this->getUser();

        if (!$user || !$user->getApiToken()) {
            throw new AuthenticationException('Пользователь не авторизован.');
        }

        try {
            $transactions = $this->billingClient->getTransactionHistory($user->getApiToken());
        } catch (\Exception $e) {
            return $this->render('error500.html.twig', [
                'message' => 'Не удалось получить данные транзакций. Пожалуйста, попробуйте позже.',
            ]);
        }

        return $this->render('profile/transaction_history.html.twig', [
            'transactions' => $transactions,
        ]);
    }
}