<?php

namespace App\Security;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    private $email;

    /**
     * @var list<string> The user roles
     */
    private $roles = [];

    private string $apiToken;

    private float $balance = 0.0;

    private string $refreshToken; // Добавлено свойство refreshToken

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // гарантируем, что каждый пользователь имеет хотя бы роль ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // Если вы храните какие-либо временные, чувствительные данные на пользователе, очистите их здесь
        // $this->plainPassword = null;
    }

    /**
     * Получить API токен (JWT) пользователя.
     */
    public function getApiToken(): ?string
    {
        return $this->apiToken;
    }

    /**
     * Установить API токен (JWT) для пользователя.
     */
    public function setApiToken(string $apiToken): static
    {
        $this->apiToken = $apiToken;
        return $this;
    }

    public function getPassword(): ?string
    {
        return null;
    }

    // Метод для возврата соли (если требуется, но в вашем случае не нужен)
    public function getSalt(): ?string
    {
        return null;
    }

    // Метод для получения баланса
    public function getBalance(): float
    {
        return $this->balance;
    }

    public function setBalance(float $balance): static
    {
        $this->balance = round($balance, 2); // Округляем баланс
        return $this;
    }

    // Добавляем методы для работы с refreshToken

    /**
     * Получить refresh token пользователя.
     */
    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    /**
     * Установить refresh token пользователя.
     */
    public function setRefreshToken(string $refreshToken): static
    {
        $this->refreshToken = $refreshToken;
        return $this;
    }
}