<?php

declare(strict_types=1);

namespace App\Core;

/**
 * CSRF protection: one token per session, compared with hash_equals().
 *
 * Every state-changing POST must send the token (hidden field `_csrf`).
 */
final class Csrf
{
    private const KEY = '_csrf_token';

    public function __construct(private readonly Session $session)
    {
    }

    public function token(): string
    {
        $token = $this->session->get(self::KEY);

        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            $this->session->set(self::KEY, $token);
        }

        return $token;
    }

    public function field(): string
    {
        return '<input type="hidden" name="_csrf" value="'
            . htmlspecialchars($this->token(), ENT_QUOTES, 'UTF-8')
            . '">';
    }

    public function validate(?string $token): bool
    {
        $stored = $this->session->get(self::KEY);

        return is_string($stored) && is_string($token) && $token !== '' && hash_equals($stored, $token);
    }
}
