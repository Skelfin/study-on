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

        // Проверяем наличие токена
        if (!$user || !$user->getApiToken()) {
            throw new AuthenticationException('Пользователь не авторизован.');
        }

        try {
            // Получаем данные пользователя из биллинга
            $userData = $this->billingClient->getCurrentUser($user->getApiToken());

            // Обновляем баланс пользователя
            $user->setBalance($userData['balance']);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Ошибка при получении данных профиля: ' . $e->getMessage());
            return $this->redirectToRoute('app_login');
        }

        // Рендерим шаблон профиля и передаем данные в него
        return $this->render('profile/profile.html.twig', [
            'email' => $userData['username'],
            'roles' => $userData['roles'],
            'balance' => $userData['balance'],
        ]);
    }
}