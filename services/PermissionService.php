<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

/**
 * Centralised permission resolution.
 *
 * Two levels:
 *  - the global role (`utilisateur.role_global`);
 *  - the local role, carried by a dated membership in a space.
 *
 * Permissions are NEVER resolved inside views: they always go through `peut()`.
 */
final class PermissionService
{
    /** @var array<string, list<string>> */
    private const GLOBAL = [
        'administrateur_technique' => ['compte.inviter', 'journal.lire', 'dashboard.systeme'],
        'administrateur_fonctionnel' => [
            'compte.inviter', 'espace.administrer', 'espace.creer', 'adhesion.gerer',
            'espace.archiver', 'dashboard.espaces',
        ],
    ];

    /** @var array<string, list<string>> */
    private const LOCAL = [
        'lecteur' => ['espace.consulter', 'document.consulter', 'assistant.interroger'],
        'contributeur' => [
            'espace.consulter', 'document.consulter', 'assistant.interroger',
            'document.deposer', 'assistant.tacher',
        ],
        'animateur' => [
            'espace.consulter', 'document.consulter', 'assistant.interroger',
            'document.deposer', 'assistant.tacher', 'fil.moderer', 'annonce.publier',
        ],
        'responsable' => [
            'espace.consulter', 'document.consulter', 'assistant.interroger',
            'document.deposer', 'assistant.tacher', 'fil.moderer', 'annonce.publier',
            'adhesion.gerer', 'espace.archiver', 'document.supprimer',
        ],
    ];

    public function __construct(private readonly PDO $db)
    {
    }

    public static function roleGlobalPermet(string $role, string $action): bool
    {
        return in_array($action, self::GLOBAL[$role] ?? [], true);
    }

    /**
     * Resolve a permission for an actor, optionally on a specific space.
     */
    public function peut(int $acteurId, string $action, ?int $espaceId = null): bool
    {
        $actor = $this->compteActif($acteurId);

        if ($actor === null) {
            return false;
        }

        if (self::roleGlobalPermet((string) $actor['role_global'], $action)) {
            return true;
        }

        if ($espaceId === null || $espaceId < 1 || !array_key_exists($action, $this->actionsLocales())) {
            return false;
        }

        $resolution = $this->resoudreRoleLocal($acteurId, (int) $actor['tenant_id'], $espaceId);

        if ($resolution === null) {
            return false;
        }

        if ($resolution['statut'] !== 'actif' && in_array($action, ['adhesion.gerer', 'espace.archiver'], true)) {
            return false;
        }

        return in_array($action, self::LOCAL[$resolution['role']] ?? [], true);
    }

    /**
     * True when the actor holds the action globally or in at least one space.
     *
     * Used by the HTTP endpoints, which are not tied to a single space.
     */
    public function peutAilleurs(int $acteurId, string $action): bool
    {
        $actor = $this->compteActif($acteurId);

        if ($actor === null) {
            return false;
        }

        if (self::roleGlobalPermet((string) $actor['role_global'], $action)) {
            return true;
        }

        $stmt = $this->db->prepare(
            'SELECT DISTINCT espace_id FROM adhesion
             WHERE utilisateur_id = :utilisateur
               AND debut_le <= CURRENT_DATE AND (fin_le IS NULL OR fin_le >= CURRENT_DATE)'
        );
        $stmt->execute(['utilisateur' => $acteurId]);

        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $spaceId) {
            if ($this->peut($acteurId, $action, (int) $spaceId)) {
                return true;
            }
        }

        return false;
    }

    public function roleGlobal(int $userId): ?string
    {
        $stmt = $this->db->prepare(
            'SELECT role_global FROM utilisateur
             WHERE id = :id AND statut = \'actif\' AND supprime_le IS NULL
             LIMIT 1'
        );
        $stmt->execute(['id' => $userId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : (string) $row['role_global'];
    }

    public function roleLocalPour(int $acteurId, int $espaceId): ?string
    {
        $actor = $this->compteActif($acteurId);

        if ($actor === null || $espaceId < 1) {
            return null;
        }

        return $this->resoudreRoleLocal($acteurId, (int) $actor['tenant_id'], $espaceId)['role'] ?? null;
    }

    /** @return array{id:int, tenant_id:int, role_global:string}|null */
    public function compteActif(int $userId): ?array
    {
        if ($userId < 1) {
            return null;
        }

        $stmt = $this->db->prepare(
            'SELECT id, tenant_id, role_global FROM utilisateur
             WHERE id = :id AND statut = \'actif\' AND supprime_le IS NULL
             LIMIT 1'
        );
        $stmt->execute(['id' => $userId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return [
            'id' => (int) $row['id'],
            'tenant_id' => (int) $row['tenant_id'],
            'role_global' => (string) $row['role_global'],
        ];
    }

    /**
     * Log a refused permission in the append-only audit journal.
     *
     * The refusal stays effective even if the audit table is unavailable.
     */
    public function journaliserRefus(int $acteurId, string $action, ?int $espaceId, string $ip): void
    {
        if ($acteurId < 1) {
            return;
        }

        try {
            $details = json_encode(['action_demandee' => $action], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

            $this->db->prepare(
                'INSERT INTO journal (acteur_id, action, objet_type, objet_id, resultat, adresse_ip, details)
                 VALUES (:acteur, :code, :type, :objet, :resultat, :ip, :details)'
            )->execute([
                'acteur' => $acteurId,
                'code' => 'permission_refusee',
                'type' => $espaceId === null ? null : 'espace',
                'objet' => $espaceId,
                'resultat' => 'refus',
                'ip' => filter_var($ip, FILTER_VALIDATE_IP) !== false ? $ip : '0.0.0.0',
                'details' => $details,
            ]);
        } catch (\Throwable) {
            // Refusal remains effective.
        }
    }

    /** @return array{role: string, statut: string}|null */
    private function resoudreRoleLocal(int $acteurId, int $tenantId, int $espaceId): ?array
    {
        $space = $this->chargerEspace($espaceId, $tenantId);

        if ($space === null) {
            return null;
        }

        $targetStatus = (string) $space['statut'];
        $visited = [];

        for ($depth = 0; $space !== null && $depth < 64; $depth++) {
            $currentId = (int) $space['id'];

            if (isset($visited[$currentId])) {
                return null;
            }
            $visited[$currentId] = true;

            $stmt = $this->db->prepare(
                'SELECT role_local FROM adhesion
                 WHERE tenant_id = :tenant AND espace_id = :espace AND utilisateur_id = :utilisateur
                   AND debut_le <= CURRENT_DATE AND (fin_le IS NULL OR fin_le >= CURRENT_DATE)
                 ORDER BY debut_le DESC LIMIT 1'
            );
            $stmt->execute([
                'tenant' => $tenantId,
                'espace' => $currentId,
                'utilisateur' => $acteurId,
            ]);
            $membership = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($membership !== false) {
                return ['role' => (string) $membership['role_local'], 'statut' => $targetStatus];
            }

            if ($space['visibilite'] === 'prive' || $space['parent_id'] === null) {
                return null;
            }

            $space = $this->chargerEspace((int) $space['parent_id'], $tenantId);
        }

        return null;
    }

    /** @return array<string, mixed>|null */
    private function chargerEspace(int $spaceId, int $tenantId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, parent_id, visibilite, statut FROM espace
             WHERE id = :id AND tenant_id = :tenant AND supprime_le IS NULL
             LIMIT 1'
        );
        $stmt->execute(['id' => $spaceId, 'tenant' => $tenantId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    /** @return array<string, true> */
    private function actionsLocales(): array
    {
        $actions = [];
        foreach (self::LOCAL as $permissions) {
            foreach ($permissions as $permission) {
                $actions[$permission] = true;
            }
        }

        return $actions;
    }
}
