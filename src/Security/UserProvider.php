<?php

namespace App\Security;

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
    public function __construct(private BillingClient $billingClient) {}

    public function loadUserByIdentifier($identifier): UserInterface
    {
        try {
            $userData = $this->billingClient->authenticate($identifier, 'password_placeholder');
        } catch (BillingUnavailableException) {
            throw new UserNotFoundException('Сервис биллинга временно недоступен.');
        }

        if (!$userData) {
            throw new UserNotFoundException(sprintf('Пользователь с email "%s" не найден.', $identifier));
        }

        return $this->mapUserDataToUser($userData);
    }

    public function loadUserByUsername($username): UserInterface
    {
        return $this->loadUserByIdentifier($username);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Неподдерживаемый класс пользователя "%s".', get_class($user)));
        }

        if ($this->isTokenExpired($user->getApiToken())) {
            $this->refreshTokenForUser($user);
        }

        return $user;
    }

    private function isTokenExpired(string $token): bool
    {
        $decoded = json_decode(base64_decode(explode('.', $token)[1]), true);
        return isset($decoded['exp']) ? ((int) $decoded['exp'] < time()) : true;
    }

    private function refreshTokenForUser(User $user): void
    {
        try {
            $newTokenData = $this->billingClient->refreshToken($user->getRefreshToken());
            $user->setApiToken($newTokenData['token']);
            $user->setRefreshToken($newTokenData['refresh_token']);
        } catch (\Exception) {
            throw new AuthenticationException('Сессия истекла, пожалуйста, войдите снова.');
        }
    }

    private function mapUserDataToUser(array $userData): User
    {
        return (new User())
            ->setEmail($userData['email'])
            ->setRoles($userData['roles'])
            ->setApiToken($userData['token']);
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void {}
}