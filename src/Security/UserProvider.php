<?php

namespace App\Security;

use App\Security\User;
use App\Exception\BillingUnavailableException;
use App\Service\BillingClient;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class UserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    private BillingClient $billingClient;

    public function __construct(BillingClient $billingClient)
    {
        $this->billingClient = $billingClient;
    }

    /**
     * Загружает пользователя по email (идентификатору).
     * @throws UserNotFoundException если пользователь не найден.
     */
    public function loadUserByIdentifier($identifier): UserInterface
    {
        try {
            $userData = $this->billingClient->authenticate($identifier, 'password_placeholder');
        } catch (BillingUnavailableException $e) {
            throw new UserNotFoundException('Сервис биллинга временно недоступен.');
        }

        if (!$userData) {
            throw new UserNotFoundException(sprintf('Пользователь с email "%s" не найден.', $identifier));
        }

        $user = new User();
        $user->setEmail($userData['email']);
        $user->setRoles($userData['roles']);
        $user->setApiToken($userData['token']);

        return $user;
    }

    /**
     * @deprecated since Symfony 5.3, используйте loadUserByIdentifier() вместо этого метода.
     */
    public function loadUserByUsername($username): UserInterface
    {
        return $this->loadUserByIdentifier($username);
    }

    /**
     * Обновляет данные пользователя при каждом запросе.
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Неподдерживаемый класс пользователя "%s".', get_class($user)));
        }

        $token = $user->getApiToken();

        if ($this->isTokenExpired($token)) {
            $refreshToken = $user->getRefreshToken();
            if ($refreshToken) {
                try {
                    $newTokenData = $this->billingClient->refreshToken($refreshToken);
                    $user->setApiToken($newTokenData['token']);
                    $user->setRefreshToken($newTokenData['refresh_token']);
                } catch (\Exception $e) {
                    // Если не удалось обновить токен, выбрасываем исключение
                    throw new AuthenticationException('Сессия истекла, пожалуйста, войдите снова.');
                }
            } else {
                throw new AuthenticationException('Сессия истекла, пожалуйста, войдите снова.');
            }
        }

        return $user;
    }

    private function isTokenExpired(string $token): bool
    {
        $payload = explode('.', $token)[1];
        $decoded = json_decode(base64_decode($payload), true);

        if (isset($decoded['exp'])) {
            $expTime = (int) $decoded['exp'];
            return $expTime < time();
        }

        // Если нет поля exp, считаем, что токен истек
        return true;
    }

    /**
     * Проверяет, поддерживает ли этот провайдер данную сущность пользователя.
     */
    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }

    /**
     * Обновляет хэшированный пароль пользователя.
     * Можно использовать для обновления хеша пароля, если требуется.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void {}
}