<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Models\TentativeConnexion;
use App\Models\Utilisateur;
use App\Validators\PolitiqueMotDePasse;

/**
 * Authentication use cases: login and logout.
 *
 * Login is protected against brute force (progressive lockout) and does not
 * reveal whether the email exists.
 */
final class AuthentificationService
{
    public function __construct(
        private readonly Utilisateur $users,
        private readonly TentativeConnexion $tentatives,
        private readonly Auth $auth,
    ) {
    }

    public function login(string $courriel, string $motDePasse): bool
    {
        $courriel = strtolower(trim($courriel));

        if ($this->tentatives->isLocked($courriel)) {
            return false;
        }

        $user = $this->users->findByEmail($courriel);

        if ($user === null || !PolitiqueMotDePasse::verify($motDePasse, (string) $user['mot_de_passe'])) {
            $this->tentatives->recordFailure($courriel);

            return false;
        }

        if ((string) $user['statut'] !== 'actif') {
            return false;
        }

        $this->tentatives->reset($courriel);
        $this->users->updateLastLogin((int) $user['id']);
        $this->auth->login($user);

        return true;
    }

    public function logout(): void
    {
        $this->auth->logout();
    }
}
