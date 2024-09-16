<?php

namespace App\Controller;

use App\Form\RegistrationFormType;
use App\Security\BillingAuthenticator;
use App\Security\User; // Импортируем класс User
use App\Service\BillingClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    private BillingClient $billingClient; // Объявляем свойство

    // Инициализируем BillingClient через конструктор
    public function __construct(BillingClient $billingClient)
    {
        $this->billingClient = $billingClient; // Инициализируем свойство
    }

    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('profile');
        }

        // получить ошибку входа, если она есть
        $error = $authenticationUtils->getLastAuthenticationError();
        // последний введенный пользователем email
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void {}

    #[Route(path: '/register', name: 'app_register')]
    public function register(
        Request $request,
        UserAuthenticatorInterface $authenticator,
        BillingAuthenticator $billingAuthenticator
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('profile');
        }

        $form = $this->createForm(RegistrationFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            try {
                // Выполняем запрос на регистрацию через API биллинга
                $responseData = $this->billingClient->registerUserInBilling($data['email'], $data['password']);

                // Создаем локального пользователя, используя класс User
                $user = new User();
                $user->setEmail($data['email']);
                $user->setRoles(['ROLE_USER']); // Назначаем роль пользователя
                $user->setApiToken($responseData['token']); // Сохраняем токен

                if (isset($responseData['refresh_token'])) {
                    $user->setRefreshToken($responseData['refresh_token']);
                } else {
                    $this->addFlash('error', 'Не получен refresh token от сервиса биллинга.');
                    return $this->redirectToRoute('app_register');
                }

                // Авторизуем пользователя
                $authenticator->authenticateUser(
                    $user,
                    $billingAuthenticator,
                    $request
                );

                return $this->redirectToRoute('profile');
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('security/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}