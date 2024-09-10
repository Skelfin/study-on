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
            // Аутентифицируем пользователя и получаем данные
            $userData = $this->billingClient->authenticate($identifier, 'password_placeholder'); // Пароль должен передаваться в запросе
        } catch (BillingUnavailableException $e) {
            throw new UserNotFoundException('Сервис биллинга временно недоступен.');
        }

        if (!$userData) {
            throw new UserNotFoundException(sprintf('Пользователь с email "%s" не найден.', $identifier));
        }

        // Создаем объект User и заполняем его данными.
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

        // Здесь вы можете добавить логику для обновления данных пользователя
        // Например, можно обновить роли или токен, если это необходимо.

        return $user; // Если нет изменений, возвращаем пользователя без модификаций
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