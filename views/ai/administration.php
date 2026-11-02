<?php

declare(strict_types=1);

/**
 * Administration — users, collaborative spaces and memberships.
 *
 * Same visual language as the indexed-documents page: dashboard hero, action
 * buttons opening creation modals (one per concern), and DataTables
 * inventories styled exactly like the documents table. Every action button
 * asks for the actor's password in a confirmation modal before submitting.
 *
 * @var array<int, array<string, mixed>> $adminUsers
 * @var array<int, array<string, mixed>> $adminEspaces
 * @var array<int, array<string, mixed>> $adminAdhesions
 * @var array{type: string, text: string}|null $adminFlash
 * @var string $lang
 * @var bool $canInvite
 * @var bool $canCreateSpace
 * @var bool $canManageAdhesion
 * @var bool $canArchive
 */

$T = app_i18n($lang ?? 'en');
$S = $T['shared'];
$isFr = ($lang ?? 'en') === 'fr';
$t = static fn (string $fr, string $en): string => $isFr ? $fr : $en;
$roles = ['membre', 'administrateur_fonctionnel', 'administrateur_technique'];
$localRoles = ['lecteur', 'contributeur', 'animateur', 'responsable'];
$aucunDroit = !$canInvite && !$canCreateSpace && !$canManageAdhesion;

$expandLabel = $t('Afficher les informations complémentaires', 'Show additional details');
$rowNumberCell = static fn (): string => '<td class="table-row-number dtr-control">'
    . '<button class="dtr-control-btn" type="button" aria-label="' . e($expandLabel) . '" aria-expanded="false"><span aria-hidden="true">+</span></button>'
    . '<span class="row-number-value"></span></td>';
?>
<section class="dashboard-hero">
    <div class="dashboard-hero-copy">
        <p class="dashboard-eyebrow"><?= e($S['eyebrow_root']) ?> <span aria-hidden="true">/</span> <?= e($t('Administration', 'Administration')) ?></p>
        <h1><?= e($t('Administration', 'Administration')) ?></h1>
        <p><?= e($t('Comptes, espaces collaboratifs et adhésions — chaque action est journalisée.', 'Accounts, collaborative spaces and memberships — every action is journaled.')) ?></p>
    </div>
    <div class="dashboard-hero-actions">
        <span class="dashboard-date"><?= e(lang_date($lang ?? 'en')) ?></span>
    </div>
</section>

<?php if (!empty($adminFlash)): ?>
    <div class="alert alert-<?= e($adminFlash['type'] === 'error' ? 'danger' : 'success') ?>" role="status">
        <?= e((string) $adminFlash['text']) ?>
    </div>
<?php endif; ?>

<?php if ($aucunDroit): ?>
    <div class="row g-4">
        <div class="col-12">
            <div class="space-admin">
                <section class="panel space-list-panel technical-admin-panel panel-accent-slate">
                    <div class="panel-body">
                        <p class="technical-admin-empty">
                            <?= e($t(
                                'Cette page est réservée aux administrateurs. Demandez à un administrateur de vous ajouter à un espace de travail.',
                                'This page is reserved to administrators. Ask an administrator to add you to a workspace.'
                            )) ?>
                        </p>
                    </div>
                </section>
            </div>
        </div>
    </div>
    <?php return; ?>
<?php endif; ?>

<div class="space-admin">

    <div class="row g-3 admin-open-row">
        <?php if ($canInvite): ?>
            <div class="col-12 col-md-4">
                <button type="button" class="btn admin-open-btn admin-open-violet w-100" data-bs-toggle="modal" data-bs-target="#admin-user-modal">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/></svg>
                    <span>
                        <strong><?= e($t('Comptes', 'Accounts')) ?></strong>
                        <small><?= e($t('Créer un utilisateur', 'Create a user')) ?></small>
                    </span>
                </button>
            </div>
        <?php endif; ?>
        <?php if ($canCreateSpace): ?>
            <div class="col-12 col-md-4">
                <button type="button" class="btn admin-open-btn admin-open-teal w-100" data-bs-toggle="modal" data-bs-target="#admin-space-modal">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                    <span>
                        <strong><?= e($t('Espaces', 'Workspaces')) ?></strong>
                        <small><?= e($t('Créer un espace collaboratif', 'Create a collaborative workspace')) ?></small>
                    </span>
                </button>
            </div>
        <?php endif; ?>
        <?php if ($canManageAdhesion): ?>
            <div class="col-12 col-md-4">
                <button type="button" class="btn admin-open-btn admin-open-blue w-100" data-bs-toggle="modal" data-bs-target="#admin-adhesion-modal">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="m17 11 2 2 4-4"/></svg>
                    <span>
                        <strong><?= e($t('Adhésions', 'Memberships')) ?></strong>
                        <small><?= e($t('Ajouter un membre à un espace', 'Add a member to a workspace')) ?></small>
                    </span>
                </button>
            </div>
        <?php endif; ?>
    </div>

    <div class="row g-4">
        <div class="col-12">
            <section class="panel space-list-panel technical-admin-panel panel-accent-slate" aria-labelledby="admin-inventory-title">
                <div class="panel-head">
                    <div>
                        <p class="page-eyebrow"><?= e($t('Inventaire', 'Inventory')) ?></p>
                        <h2 class="panel-title" id="admin-inventory-title">
                            <?= e($t('Comptes, espaces et adhésions', 'Accounts, workspaces and memberships')) ?>
                            <span class="technical-admin-count"><?= count($adminUsers ?? []) + count($adminEspaces ?? []) + count($adminAdhesions ?? []) ?></span>
                        </h2>
                        <p class="panel-sub"><?= e($t("Vue d'ensemble de la gouvernance en place.", 'Overview of the governance in place.')) ?></p>
                    </div>
                </div>

                <h3 class="h6 admin-inv-title"><?= e($t('Comptes', 'Accounts')) ?> <span class="technical-admin-count"><?= count($adminUsers ?? []) ?></span></h3>
                <div class="space-table-wrap">
                    <table class="spaces-data-table technical-admin-table admin-inventory-table inv-users table table-hover align-middle w-100">
                        <thead>
                            <tr>
                                <th scope="col">N°</th>
                                <th scope="col"><?= e($t('Nom', 'Name')) ?></th>
                                <th scope="col"><?= e($t('Adresse e-mail', 'E-mail')) ?></th>
                                <th scope="col"><?= e($t('Rôle global', 'Global role')) ?></th>
                                <th scope="col"><?= e($t('Statut', 'Status')) ?></th>
                                <th scope="col" class="text-end"><?= e($t('Actions', 'Actions')) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (($adminUsers ?? []) as $u): ?>
                                <tr>
                                    <?= $rowNumberCell() ?>
                                    <td><strong><?= e(trim(($u['prenom'] ?? '') . ' ' . ($u['nom'] ?? ''))) ?></strong><?php if (!empty($u['compte_super'])): ?> <span class="doc-item-badge super-badge" title="<?= e($t('Compte protégé (un seul à la fois) : non supprimable, non suspendable, non rétrogradable ; son profil reste modifiable', 'Protected account (only one at a time): cannot be deleted, suspended or demoted; its profile stays editable')) ?>">★ <?= e($t('Super Admin', 'Super Admin')) ?></span><?php endif; ?></td>
                                    <td class="small text-muted"><?= e((string) ($u['courriel'] ?? '')) ?></td>
                                    <td><span class="doc-item-badge"><?= e((string) ($u['role_global'] ?? '')) ?></span></td>
                                                                        <td class="small">
                                        <span class="badge <?= ($u['statut'] ?? 'actif') === 'actif' ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= e((string) ($u['statut'] ?? 'actif')) ?></span>
                                    </td>
                                    <td class="text-end">
                                        <?php if (!empty($u['compte_super'])): ?>
                                            <span class="doc-action doc-action-locked"
                                                  title="<?= e($t('Compte d\'amorçage protégé : ni suppression, ni suspension, ni rétrogradation', 'Protected bootstrap account: no deletion, suspension or demotion')) ?>"
                                                  aria-label="<?= e($t('Compte protégé', 'Protected account')) ?>">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="11" width="16" height="9" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                                            </span>
                                        <?php else: ?>
                                            <?php $estActifCompte = ($u['statut'] ?? 'actif') === 'actif'; ?>
                                            <form method="post" action="<?= e(url('/api/admin_user_statut.php')) ?>" class="d-inline">
                                                <?= $csrf?->field() ?? '' ?>
                                                <input type="hidden" name="utilisateur_id" value="<?= (int) $u['id'] ?>">
                                                <input type="hidden" name="statut" value="<?= $estActifCompte ? 'suspendu' : 'actif' ?>">
                                                <button type="submit" class="doc-action <?= $estActifCompte ? 'doc-action-danger' : '' ?> admin-confirm"
                                                        title="<?= e($estActifCompte ? $t('Suspendre ce compte', 'Suspend this account') : $t('Réactiver ce compte', 'Reinstate this account')) ?>"
                                                        aria-label="<?= e($estActifCompte ? $t('Suspendre ce compte', 'Suspend this account') : $t('Réactiver ce compte', 'Reinstate this account')) ?>"
                                                        data-confirm-title="<?= e($estActifCompte ? $t('Suspendre ce compte ?', 'Suspend this account?') : $t('Réactiver ce compte ?', 'Reinstate this account?')) ?>"
                                                        data-confirm-text="<?= e($estActifCompte
                                                            ? $t('Le compte ne pourra plus se connecter jusqu\'à réactivation. Action journalisée.', 'The account will not be able to sign in until reinstated. Journaled action.')
                                                            : $t('Le compte pourra de nouveau se connecter. Action journalisée.', 'The account will be able to sign in again. Journaled action.')) ?>">
                                                    <?php if ($estActifCompte): ?>
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 19a6 6 0 0 0-12 0"/><circle cx="9" cy="7" r="4"/><path d="M14 11h6"/></svg>
                                                    <?php else: ?>
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 19a6 6 0 0 0-12 0"/><circle cx="9" cy="7" r="4"/><path d="M17 8v6"/><path d="M14 11h6"/></svg>
                                                    <?php endif; ?>
                                                </button>
                                            </form>
                                            <form method="post" action="<?= e(url('/api/admin_user_delete.php')) ?>" class="d-inline ms-1">
                                                <?= $csrf?->field() ?? '' ?>
                                                <input type="hidden" name="utilisateur_id" value="<?= (int) $u['id'] ?>">
                                                <button type="submit" class="doc-action doc-action-danger admin-confirm" data-confirm-tone="danger"
                                                        title="<?= e($t('Supprimer ce compte', 'Delete this account')) ?>"
                                                        aria-label="<?= e($t('Supprimer ce compte', 'Delete this account')) ?>"
                                                        data-confirm-title="<?= e($t('Supprimer ce compte ?', 'Delete this account?')) ?>"
                                                        data-confirm-text="<?= e($t('Suppression logique : le compte disparaît, l\'historique est conservé. Action journalisée.', 'Soft delete: the account disappears, its history is kept. Journaled action.')) ?>">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16"/><path d="M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/><path d="M6 7l1 13h10l1-13"/></svg>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <h3 class="h6 admin-inv-title"><?= e($t('Espaces', 'Workspaces')) ?> <span class="technical-admin-count"><?= count($adminEspaces ?? []) ?></span></h3>
                <div class="space-table-wrap">
                    <table class="spaces-data-table technical-admin-table admin-inventory-table inv-espaces table table-hover align-middle w-100">
                        <thead>
                            <tr>
                                <th scope="col">N°</th>
                                <th scope="col"><?= e($t('Espace', 'Workspace')) ?></th>
                                <th scope="col"><?= e($t('Visibilité', 'Visibility')) ?></th>
                                <th scope="col"><?= e($t('Statut', 'Status')) ?></th>
                                <th scope="col" class="text-end"><?= e($t('Cycle de vie', 'Lifecycle')) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (($adminEspaces ?? []) as $es): ?>
                                <?php $esActif = ($es['statut'] ?? 'actif') === 'actif'; ?>
                                <tr>
                                    <?= $rowNumberCell() ?>
                                    <td><strong><?= e((string) $es['nom']) ?></strong></td>
                                    <td><span class="doc-item-badge"><?= e((string) $es['visibilite']) ?></span></td>
                                    <td class="small">
                                        <span class="badge <?= $esActif ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= e((string) ($es['statut'] ?? 'actif')) ?></span>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($canArchive): ?>
                                            <form method="post" action="<?= e(url('/api/admin_espace_statut.php')) ?>" class="d-inline">
                                                <?= $csrf?->field() ?? '' ?>
                                                <input type="hidden" name="espace_id" value="<?= (int) $es['id'] ?>">
                                                <input type="hidden" name="statut" value="<?= $esActif ? 'archive' : 'actif' ?>">
                                                <button type="submit" class="doc-action <?= $esActif ? 'doc-action-danger' : '' ?> admin-confirm" data-confirm-tone="danger"
                                                        title="<?= e($esActif ? $t('Clore', 'Close') : $t('Réouvrir', 'Reopen')) ?>"
                                                        aria-label="<?= e($esActif ? $t('Clore', 'Close') : $t('Réouvrir', 'Reopen')) ?>"
                                                        data-confirm-title="<?= e($esActif ? $t('Clore cet espace ?', 'Close this workspace?') : $t('Réouvrir cet espace ?', 'Reopen this workspace?')) ?>"
                                                        data-confirm-text="<?= e($esActif
                                                            ? $t('Aucun nouveau dépôt ne sera accepté ; les documents restent lisibles. Action journalisée.', 'No new uploads will be accepted; documents stay readable. Journaled action.')
                                                            : $t('Les dépôts redeviennent possibles dans cet espace. Action journalisée.', 'Uploads become possible again in this workspace. Journaled action.')) ?>">
                                                    <?php if ($esActif): ?>
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="11" width="16" height="9" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                                                    <?php else: ?>
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 11 18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/></svg>
                                                    <?php endif; ?>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <h3 class="h6 admin-inv-title"><?= e($t('Adhésions', 'Memberships')) ?> <span class="technical-admin-count"><?= count($adminAdhesions ?? []) ?></span></h3>
                <div class="space-table-wrap">
                    <table class="spaces-data-table technical-admin-table admin-inventory-table inv-adhesions table table-hover align-middle w-100">
                        <thead>
                            <tr>
                                <th scope="col">N°</th>
                                <th scope="col"><?= e($t('Membre', 'Member')) ?></th>
                                <th scope="col"><?= e($t('Espace', 'Workspace')) ?></th>
                                <th scope="col"><?= e($t('Rôle local', 'Local role')) ?></th>
                                <th scope="col" class="text-end"><?= e($t('Action', 'Action')) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (($adminAdhesions ?? []) as $ad): ?>
                                <tr>
                                    <?= $rowNumberCell() ?>
                                    <td><strong><?= e(trim((string) ($ad['prenom'] ?? '') . ' ' . (string) ($ad['nom'] ?? ''))) ?></strong></td>
                                    <td class="small text-muted"><?= e((string) ($ad['espace'] ?? '')) ?></td>
                                    <td><span class="doc-item-badge"><?= e((string) ($ad['role_local'] ?? '')) ?></span></td>
                                    <td class="text-end">
                                        <form method="post" action="<?= e(url('/api/admin_adhesion_end.php')) ?>" class="d-inline">
                                            <?= $csrf?->field() ?? '' ?>
                                            <input type="hidden" name="adhesion_id" value="<?= (int) $ad['id'] ?>">
                                            <button type="submit" class="doc-action doc-action-danger admin-confirm" data-confirm-tone="danger"
                                                    title="<?= e($t("Terminer l'adhésion", 'End membership')) ?>"
                                                    aria-label="<?= e($t("Terminer l'adhésion", 'End membership')) ?>"
                                                    data-confirm-title="<?= e($t("Terminer cette adhésion ?", 'End this membership?')) ?>"
                                                    data-confirm-text="<?= e($t('Le membre perdra l\'accès aux documents de l\'espace. Action journalisée.', 'The member will lose access to the workspace documents. Journaled action.')) ?>">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8h5"/></svg>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <p class="technical-admin-note technical-admin-footer">
                    <?= e($t('Chaque création, clôture et révocation est inscrite au journal d\'audit.', 'Every creation, closure and revocation is recorded in the audit journal.')) ?>
                </p>
            </section>
        </div>
    </div>
</div>

<?php if ($canInvite): ?>
    <div class="modal fade space-create-modal profil-modal admin-dark-modal" id="admin-user-modal" tabindex="-1" aria-labelledby="admin-user-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered profil-modal-dialog">
            <div class="modal-content">
                <form class="space-form" method="post" action="<?= e(url('/api/admin_user_save.php')) ?>">
                    <?= $csrf?->field() ?? '' ?>
                    <div class="modal-header space-create-header admin-head-violet border-bottom border-2">
                        <span class="space-create-head-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/></svg>
                        </span>
                        <div class="space-create-head-copy">
                            <p class="space-modal-eyebrow"><?= e($t('Comptes', 'Accounts')) ?></p>
                            <h2 class="modal-title" id="admin-user-modal-title"><?= e($t('Créer un utilisateur', 'Create a user')) ?></h2>
                            <p><?= e($t('Le mot de passe doit contenir au moins 12 caractères.', 'The password must be at least 12 characters long.')) ?></p>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="<?= e($S['close']) ?>"></button>
                    </div>
                    <div class="modal-body space-create-body">
                        <div class="space-create-fields">
                            <label><?= e($t('Prénom', 'First name')) ?>
                                <input name="prenom" required maxlength="100" autocomplete="off">
                            </label>
                            <label><?= e($t('Nom', 'Last name')) ?>
                                <input name="nom" required maxlength="100" autocomplete="off">
                            </label>
                            <label class="profil-field-full"><?= e($t('Adresse e-mail', 'E-mail address')) ?>
                                <input name="courriel" type="email" required maxlength="191" autocomplete="off" placeholder="prenom.nom@organisation.org">
                            </label>
                            <label><?= e($t('Mot de passe', 'Password')) ?>
                                <input name="mot_de_passe" type="password" required minlength="12" autocomplete="new-password">
                            </label>
                            <label class="profil-field-full"><?= e($t('Rôle global', 'Global role')) ?>
                                <select name="role_global" data-placeholder="<?= e($t('Choisir un rôle', 'Choose a role')) ?>">
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?= e($role) ?>"<?= $role === 'membre' ? ' selected' : '' ?>><?= e($role) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <p class="form-text mb-0"><?= e($t('12 caractères minimum.', 'At least 12 characters.')) ?></p>
                        </div>
                    </div>
                    <div class="modal-footer space-create-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= e($S['cancel']) ?></button>
                        <button type="submit" class="btn btn-primary space-create-submit admin-confirm"
                                data-confirm-title="<?= e($t('Créer ce compte ?', 'Create this account?')) ?>"
                                data-confirm-text="<?= e($t('Le compte sera créé avec le rôle global choisi. Action journalisée.', 'The account will be created with the chosen global role. Journaled action.')) ?>">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                            <?= e($t('Créer le compte', 'Create the account')) ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($canCreateSpace): ?>
    <div class="modal fade space-create-modal profil-modal admin-dark-modal" id="admin-space-modal" tabindex="-1" aria-labelledby="admin-space-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered profil-modal-dialog">
            <div class="modal-content">
                <form class="space-form" method="post" action="<?= e(url('/api/admin_espace_save.php')) ?>">
                    <?= $csrf?->field() ?? '' ?>
                    <div class="modal-header space-create-header admin-head-teal border-bottom border-2">
                        <span class="space-create-head-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                        </span>
                        <div class="space-create-head-copy">
                            <p class="space-modal-eyebrow"><?= e($t('Espaces', 'Workspaces')) ?></p>
                            <h2 class="modal-title" id="admin-space-modal-title"><?= e($t('Créer un espace collaboratif', 'Create a collaborative workspace')) ?></h2>
                            <p><?= e($t("Un espace porte un travail précis : on l'ouvre, on le clôt, on le réouvre.", 'A workspace carries a specific piece of work: opened, closed, reopened.')) ?></p>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="<?= e($S['close']) ?>"></button>
                    </div>
                    <div class="modal-body space-create-body">
                        <div class="space-create-fields">
                            <label class="profil-field-full"><?= e($t("Nom de l'espace", 'Workspace name')) ?>
                                <input name="nom" required maxlength="120" autocomplete="off" placeholder="<?= e($t('Ex. Suivi-évaluation 2026', 'E.g. Monitoring & evaluation 2026')) ?>">
                            </label>
                            <label class="profil-field-full"><?= e($t('Visibilité', 'Visibility')) ?>
                                <select name="visibilite" data-placeholder="<?= e($t('Choisir une visibilité', 'Choose a visibility')) ?>">
                                    <option value="restreint" selected><?= e($t('Restreint — sur adhésion', 'Restricted — members only')) ?></option>
                                    <option value="public_interne"><?= e($t("Public interne — toute l'organisation", 'Internal public — whole organisation')) ?></option>
                                </select>
                            </label>
                            <p class="form-text mb-0"><?= e($t("Vous devenez responsable de l'espace que vous créez.", 'You become manager of the workspace you create.')) ?></p>
                        </div>
                    </div>
                    <div class="modal-footer space-create-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= e($S['cancel']) ?></button>
                        <button type="submit" class="btn btn-primary space-create-submit admin-confirm"
                                data-confirm-title="<?= e($t("Créer cet espace ?", 'Create this workspace?')) ?>"
                                data-confirm-text="<?= e($t("Vous deviendrez responsable de l'espace. Action journalisée.", 'You will become manager of the workspace. Journaled action.')) ?>">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                            <?= e($t("Créer l'espace", 'Create the workspace')) ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($canManageAdhesion): ?>
    <div class="modal fade space-create-modal profil-modal admin-dark-modal" id="admin-adhesion-modal" tabindex="-1" aria-labelledby="admin-adhesion-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered profil-modal-dialog">
            <div class="modal-content">
                <form class="space-form" method="post" action="<?= e(url('/api/admin_adhesion_save.php')) ?>">
                    <?= $csrf?->field() ?? '' ?>
                    <div class="modal-header space-create-header admin-head-blue border-bottom border-2">
                        <span class="space-create-head-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="m17 11 2 2 4-4"/></svg>
                        </span>
                        <div class="space-create-head-copy">
                            <p class="space-modal-eyebrow"><?= e($t('Adhésions', 'Memberships')) ?></p>
                            <h2 class="modal-title" id="admin-adhesion-modal-title"><?= e($t('Ajouter un membre à un espace', 'Add a member to a workspace')) ?></h2>
                            <p><?= e($t("Sans adhésion, un membre ne voit que ses propres dépôts. C'est ce formulaire qui rend un espace collaboratif.", 'Without a membership, a member only sees their own uploads. This form is what makes a workspace collaborative.')) ?></p>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="<?= e($S['close']) ?>"></button>
                    </div>
                    <div class="modal-body space-create-body">
                        <div class="space-create-fields">
                            <label class="profil-field-full"><?= e($t('Utilisateur', 'User')) ?>
                                <select name="utilisateur_id" required data-placeholder="<?= e($t('Choisir un utilisateur', 'Choose a user')) ?>">
                                    <?php foreach (($adminUsers ?? []) as $u): ?>
                                        <option value="<?= (int) $u['id'] ?>"><?= e(trim(($u['prenom'] ?? '') . ' ' . ($u['nom'] ?? '')) . ' — ' . (string) ($u['courriel'] ?? '')) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label class="profil-field-full"><?= e($t('Espace', 'Workspace')) ?>
                                <select name="espace_id" required data-placeholder="<?= e($t('Choisir un espace', 'Choose a workspace')) ?>">
                                    <?php foreach (($adminEspaces ?? []) as $es): ?>
                                        <option value="<?= (int) $es['id'] ?>"><?= e((string) $es['nom']) ?> (<?= e((string) $es['visibilite']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label class="profil-field-full"><?= e($t('Rôle local', 'Local role')) ?>
                                <select name="role_local" data-placeholder="<?= e($t('Choisir un rôle', 'Choose a role')) ?>">
                                    <?php foreach ($localRoles as $role): ?>
                                        <option value="<?= e($role) ?>"<?= $role === 'lecteur' ? ' selected' : '' ?>><?= e($role) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer space-create-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= e($S['cancel']) ?></button>
                        <button type="submit" class="btn btn-primary space-create-submit admin-confirm"
                                data-confirm-title="<?= e($t('Ajouter ce membre ?', 'Add this member?')) ?>"
                                data-confirm-text="<?= e($t("L'utilisateur aura le rôle local choisi dans l'espace sélectionné. Action journalisée.", 'The user will get the chosen local role in the selected workspace. Journaled action.')) ?>">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                            <?= e($t('Ajouter le membre', 'Add the member')) ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="modal fade space-create-modal profil-modal" id="admin-confirm-modal" tabindex="-1" aria-labelledby="admin-confirm-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered profil-modal-dialog">
        <div class="modal-content">
            <div class="space-form">
                <div class="modal-header space-create-header text-bg-primary border-bottom border-2 border-primary">
                    <span class="space-create-head-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.01"/></svg>
                    </span>
                    <div class="space-create-head-copy">
                        <p class="space-modal-eyebrow"><?= e($t('Confirmation', 'Confirmation')) ?></p>
                        <h2 class="modal-title" id="admin-confirm-title"><?= e($t('Confirmer l\'action', 'Confirm the action')) ?></h2>
                        <p id="admin-confirm-text"></p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="<?= e($S['close']) ?>"></button>
                </div>
                <div class="modal-body space-create-body">
                    <div class="space-create-fields">
                        <label class="profil-field-full"><?= e($t('Mot de passe administrateur', 'Administrator password')) ?>
                            <input id="admin-confirm-password" type="password" required autocomplete="current-password" placeholder="<?= e($t('Votre mot de passe confirme l\'action', 'Your password confirms the action')) ?>">
                        </label>
                    </div>
                    <p id="admin-confirm-error" class="doc-delete-error" hidden></p>
                </div>
                <div class="modal-footer space-create-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= e($S['cancel']) ?></button>
                    <button type="button" class="btn btn-primary space-create-submit" id="admin-confirm-btn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                        <?= e($S['confirm'] ?? $t('Confirmer', 'Confirm')) ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="<?= e(asset('/assets/css/spaces.css')) ?>">
<script src="<?= e(url('/assets/vendor/jquery/jquery.min.js')) ?>"></script>
<script src="<?= e(url('/assets/vendor/datatables/js/dataTables.min.js')) ?>"></script>
<script src="<?= e(url('/assets/vendor/datatables/js/dataTables.bootstrap5.min.js')) ?>"></script>
<script src="<?= e(url('/assets/vendor/datatables/js/dataTables.responsive.min.js')) ?>"></script>
<script src="<?= e(url('/assets/vendor/datatables/js/responsive.bootstrap5.min.js')) ?>"></script>
<script src="<?= e(asset('/assets/js/spaces-tables.js')) ?>"></script>
