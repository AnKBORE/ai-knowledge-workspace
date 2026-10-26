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
 * Login is protected against brute force (progressive lockout), does not
 * reveal whether the email exists, and is journalised.
 */
final class AuthentificationService
{
    public function __construct(
        private readonly Utilisateur $users,
        private readonly TentativeConnexion $tentatives,
        private readonly Auth $auth,
        private readonly JournalService $journal,
    ) {
    }

    public function login(string $courriel, string $motDePasse): bool
    {
        $courriel = strtolower(trim($courriel));

        if ($this->tentatives->isLocked($courriel)) {
            $this->journal->log(null, 'connexion', 'utilisateur', null, 'refus', ['raison' => 'verrouille']);

            return false;
        }

        $user = $this->users->findByEmail($courriel);

        if ($user === null || !PolitiqueMotDePasse::verify($motDePasse, (string) $user['mot_de_passe'])) {
            $this->tentatives->recordFailure($courriel);
            $this->journal->log(null, 'connexion', 'utilisateur', null, 'refus', [
                'courriel_empreinte' => hash('sha256', $courriel),
            ]);

            return false;
        }

        if ((string) $user['statut'] !== 'actif') {
            $this->journal->log((int) $user['id'], 'connexion', 'utilisateur', (int) $user['id'], 'refus', [
                'raison' => 'compte_inactif',
            ]);

            return false;
        }

        $this->tentatives->reset($courriel);
        $this->users->updateLastLogin((int) $user['id']);
        $this->auth->login($user);
        $this->journal->log((int) $user['id'], 'connexion', 'utilisateur', (int) $user['id'], 'succes');

        return true;
    }

    public function logout(): void
    {
        $id = $this->auth->id();
        $this->auth->logout();
        $this->journal->log($id, 'deconnexion', 'utilisateur', $id, 'succes');
    }
}
