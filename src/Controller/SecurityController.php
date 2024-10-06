<?php

namespace App\Controller;

use App\Form\RegistrationFormType;
use App\Security\BillingAuthenticator;
use App\Security\User;
use App\Service\BillingClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    private BillingClient $billingClient;

    public function __construct(BillingClient $billingClient)
    {
        $this->billingClient = $billingClient;
    }

    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('profile');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
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
                $responseData = $this->billingClient->registerUserInBilling($data['email'], $data['password']);

                $user = new User();
                $user->setEmail($data['email']);
                $user->setRoles(['ROLE_USER']);
                $user->setApiToken($responseData['token']);

                if (isset($responseData['refresh_token'])) {
                    $user->setRefreshToken($responseData['refresh_token']);
                } else {
                    $this->addFlash('error', 'Не получен refresh token от сервиса биллинга.');
                    return $this->redirectToRoute('app_register');
                }

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