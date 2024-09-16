<?php

namespace App\Security;

use App\Exception\BillingUnavailableException;
use App\Security\User;
use App\Service\BillingClient;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class BillingAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    private BillingClient $billingClient;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(BillingClient $billingClient, UrlGeneratorInterface $urlGenerator)
    {
        $this->billingClient = $billingClient;
        $this->urlGenerator = $urlGenerator;
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email', '');
        $password = $request->request->get('password', '');

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        try {
            // Отправляем запрос на авторизацию в сервис биллинга через BillingClient
            $authResponse = $this->billingClient->authenticate($email, $password);

            // Декодируем JWT-токен и извлекаем данные
            $jwtPayload = json_decode(base64_decode(explode('.', $authResponse['token'])[1]), true);

            // Проверяем наличие необходимых данных в токене
            if (!isset($jwtPayload['roles'], $jwtPayload['username'])) {
                throw new CustomUserMessageAuthenticationException('Неверный формат токена.');
            }

            // Создаем пользователя и сохраняем его роли и токен
            $user = new User();
            $user->setEmail($jwtPayload['username']);
            $user->setRoles($jwtPayload['roles']);
            $user->setApiToken($authResponse['token']);

            // Сохраняем refresh_token
            if (isset($authResponse['refresh_token'])) {
                $user->setRefreshToken($authResponse['refresh_token']);
            } else {
                throw new CustomUserMessageAuthenticationException('Refresh token отсутствует в ответе сервиса биллинга.');
            }
        } catch (BillingUnavailableException $e) {
            throw new CustomUserMessageAuthenticationException('Сервис временно недоступен. Попробуйте позже.');
        } catch (\Exception $e) {
            throw new CustomUserMessageAuthenticationException('Неверные учетные данные.');
        }

        return new SelfValidatingPassport(
            new UserBadge($email, function () use ($user) {
                return $user;
            }),
            [
                new CsrfTokenBadge('authenticate', $request->get('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        // Редирект на главную страницу после успешной авторизации
        return new RedirectResponse($this->urlGenerator->generate('course_index')); // Страница списка курсов
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}