<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Session-based authentication.
 *
 * The user is read back from the session on every request; the password hash
 * is never stored in the session.
 */
final class Auth
{
    private const KEY = 'auth_user';

    public function __construct(private readonly Session $session)
    {
    }

    public function check(): bool
    {
        return $this->session->has(self::KEY);
    }

    /** @return array<string, mixed>|null */
    public function user(): ?array
    {
        $user = $this->session->get(self::KEY);

        return is_array($user) ? $user : null;
    }

    public function id(): ?int
    {
        $user = $this->user();

        return isset($user['id']) && is_numeric($user['id']) ? (int) $user['id'] : null;
    }

    /** @param array<string, mixed> $user */
    public function login(array $user): void
    {
        unset($user['mot_de_passe']);

        $this->session->regenerate();
        $this->session->remove('_csrf_token');
        $this->session->set(self::KEY, $user);
    }

    public function logout(): void
    {
        $this->session->remove(self::KEY);
        $this->session->remove('_csrf_token');
        $this->session->regenerate();
    }
}
