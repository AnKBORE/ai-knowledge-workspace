<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\View;
use App\Services\AuthentificationService;

/**
 * HTTP entry points for authentication: login form, login submit, logout.
 */
final class AuthController
{
    public function __construct(
        private readonly AuthentificationService $auth,
        private readonly Csrf $csrf,
        private readonly View $view,
    ) {
    }

    public function showLogin(?string $error = null, string $lang = 'en'): string
    {
        return $this->view->render('auth/login', [
            'title' => app_i18n($lang)['login']['title'],
            'error' => $error,
            'csrf' => $this->csrf,
            'lang' => $lang,
        ], 'auth');
    }

    /** @param array<string, mixed> $post */
    public function login(array $post): bool
    {
        return $this->auth->login(
            (string) ($post['courriel'] ?? ''),
            (string) ($post['mot_de_passe'] ?? ''),
        );
    }

    public function logout(): void
    {
        $this->auth->logout();
    }
}
