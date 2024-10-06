<?php

namespace App\Security;

use App\Exception\BillingUnavailableException;
use App\Service\BillingClient;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class BillingAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
        private BillingClient $billingClient,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $email = $request->request->get('email', '');
        $password = $request->request->get('password', '');

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        try {
            $user = $this->authenticateUser($email, $password);
        } catch (\Exception $e) {
            throw new CustomUserMessageAuthenticationException($e->getMessage());
        }

        return new SelfValidatingPassport(
            new UserBadge($email, fn() => $user),
            [
                new CsrfTokenBadge('authenticate', $request->get('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    private function authenticateUser(string $email, string $password): User
    {
        try {
            $authResponse = $this->billingClient->authenticate($email, $password);
            $jwtPayload = json_decode(base64_decode(explode('.', $authResponse['token'])[1]), true);

            if (!isset($jwtPayload['roles'], $jwtPayload['username'])) {
                throw new CustomUserMessageAuthenticationException('Неверный формат токена.');
            }

            return (new User())
                ->setEmail($jwtPayload['username'])
                ->setRoles($jwtPayload['roles'])
                ->setApiToken($authResponse['token'])
                ->setRefreshToken($authResponse['refresh_token'] ?? throw new CustomUserMessageAuthenticationException('Refresh token отсутствует в ответе сервиса биллинга.'));
        } catch (BillingUnavailableException) {
            throw new CustomUserMessageAuthenticationException('Сервис временно недоступен. Попробуйте позже.');
        } catch (\Exception) {
            throw new CustomUserMessageAuthenticationException('Неверные учетные данные.');
        }
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?RedirectResponse
    {
        $targetPath = $this->getTargetPath($request->getSession(), $firewallName);
        return new RedirectResponse($targetPath ?: $this->urlGenerator->generate('course_index'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}