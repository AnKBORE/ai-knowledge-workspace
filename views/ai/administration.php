<?php

declare(strict_types=1);

/**
 * Administration — users, collaborative spaces and memberships.
 *
 * @var array<int, array<string, mixed>> $adminUsers
 * @var array<int, array<string, mixed>> $adminEspaces
 * @var array<int, array<string, mixed>> $adminAdhesions
 * @var array{type: string, text: string}|null $adminFlash
 * @var string $lang
 * @var bool $canInvite
 * @var bool $canCreateSpace
 * @var bool $canManageAdhesion
 */

$isFr = ($lang ?? 'en') === 'fr';
$t = static fn (string $fr, string $en): string => $isFr ? $fr : $en;
$roles = ['membre', 'administrateur_fonctionnel', 'administrateur_technique'];
$localRoles = ['lecteur', 'contributeur', 'animateur', 'responsable'];
?>
<?php if (!$canInvite && !$canCreateSpace && !$canManageAdhesion): ?>
    <div class="row g-4">
        <div class="col-12">
            <div class="space-admin">
                <section class="panel technical-admin-panel">
                    <div class="panel-body">
                        <p class="technical-admin-empty">
                            <?= e($t(
                                "Cette page est réservée aux administrateurs. Demandez à un administrateur de vous ajouter à un espace de travail.",
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
<section class="dashboard-hero">
    <div class="dashboard-hero-copy">
        <p class="dashboard-eyebrow"><?= e(app_i18n($lang ?? 'en')['shared']['eyebrow_root']) ?> <span aria-hidden="true">/</span> <?= e($t('Administration', 'Administration')) ?></p>
        <h1><?= e($t('Administration', 'Administration')) ?></h1>
        <p><?= e($t('Comptes, espaces collaboratifs et adhésions.', 'Accounts, collaborative spaces and memberships.')) ?></p>
    </div>
</section>

<?php if (!empty($adminFlash)): ?>
    <div class="alert alert-<?= e($adminFlash['type'] === 'error' ? 'danger' : 'success') ?> mb-3" role="status">
        <?= e((string) $adminFlash['text']) ?>
    </div>
<?php endif; ?>

<div class="row g-4">

    <?php if ($canInvite): ?>
        <div class="col-12 col-xl-6">
            <section class="panel technical-admin-panel panel-accent-violet">
                <div class="panel-head">
                    <div>
                        <p class="page-eyebrow"><?= e($t('Comptes', 'Accounts')) ?></p>
                        <h2 class="panel-title"><?= e($t('Créer un utilisateur', 'Create a user')) ?></h2>
                    </div>
                </div>
                <div class="panel-body">
                    <form method="post" action="<?= e(url('/api/admin_user_save.php')) ?>">
                        <?= $csrf?->field() ?? '' ?>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="u-prenom"><?= e($t('Prénom', 'First name')) ?></label>
                                <input class="form-control" id="u-prenom" name="prenom" required maxlength="100">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="u-nom"><?= e($t('Nom', 'Last name')) ?></label>
                                <input class="form-control" id="u-nom" name="nom" required maxlength="100">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="u-courriel"><?= e($t('Adresse e-mail', 'E-mail address')) ?></label>
                                <input class="form-control" type="email" id="u-courriel" name="courriel" required maxlength="191">
                            </div>
                            <div class="col-md-7">
                                <label class="form-label" for="u-mdp"><?= e($t('Mot de passe (12 caractères min.)', 'Password (12 characters min.)')) ?></label>
                                <input class="form-control" type="password" id="u-mdp" name="mot_de_passe" required minlength="12" autocomplete="new-password">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label" for="u-role"><?= e($t('Rôle global', 'Global role')) ?></label>
                                <select class="form-select" id="u-role" name="role_global">
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?= e($role) ?>"<?= $role === 'membre' ? ' selected' : '' ?>><?= e($role) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3"><?= e($t('Créer le compte', 'Create the account')) ?></button>
                    </form>
                </div>
            </section>
        </div>
    <?php endif; ?>

    <?php if ($canCreateSpace): ?>
        <div class="col-12 col-xl-6">
            <section class="panel technical-admin-panel panel-accent-slate">
                <div class="panel-head">
                    <div>
                        <p class="page-eyebrow"><?= e($t('Espaces', 'Spaces')) ?></p>
                        <h2 class="panel-title"><?= e($t('Créer un espace collaboratif', 'Create a collaborative space')) ?></h2>
                    </div>
                </div>
                <div class="panel-body">
                    <form method="post" action="<?= e(url('/api/admin_espace_save.php')) ?>">
                        <?= $csrf?->field() ?? '' ?>
                        <div class="row g-3">
                            <div class="col-md-7">
                                <label class="form-label" for="e-nom"><?= e($t("Nom de l'espace", 'Space name')) ?></label>
                                <input class="form-control" id="e-nom" name="nom" required maxlength="120" placeholder="<?= e($t('Ex. Suivi-évaluation', 'E.g. Monitoring & evaluation')) ?>">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label" for="e-vis"><?= e($t('Visibilité', 'Visibility')) ?></label>
                                <select class="form-select" id="e-vis" name="visibilite">
                                    <option value="restreint" selected><?= e($t('Restreint (sur adhésion)', 'Restricted (membership only)')) ?></option>
                                    <option value="public_interne"><?= e($t('Public interne', 'Internal public')) ?></option>
                                </select>
                            </div>
                        </div>
                        <p class="form-text"><?= e($t('Vous devenez responsable de l\'espace créé.', 'You become manager of the space you create.')) ?></p>
                        <button type="submit" class="btn btn-primary mt-2"><?= e($t("Créer l'espace", 'Create the space')) ?></button>
                    </form>
                </div>
            </section>
        </div>
    <?php endif; ?>

    <?php if ($canManageAdhesion): ?>
        <div class="col-12">
            <section class="panel technical-admin-panel panel-accent-teal">
                <div class="panel-head">
                    <div>
                        <p class="page-eyebrow"><?= e($t('Adhésions', 'Memberships')) ?></p>
                        <h2 class="panel-title"><?= e($t('Ajouter un membre à un espace', 'Add a member to a space')) ?></h2>
                        <p class="panel-sub"><?= e($t('C\'est ce qui rend un espace réellement collaboratif : sans adhésion, un membre ne voit que ses propres dépôts.', 'This is what makes a space truly collaborative: without a membership, a member only sees their own uploads.')) ?></p>
                    </div>
                </div>
                <div class="panel-body">
                    <form method="post" action="<?= e(url('/api/admin_adhesion_save.php')) ?>">
                        <?= $csrf?->field() ?? '' ?>
                        <div class="row g-3 align-items-end">
                            <div class="col-md-5">
                                <label class="form-label" for="a-user"><?= e($t('Utilisateur', 'User')) ?></label>
                                <select class="form-select" id="a-user" name="utilisateur_id" required>
                                    <?php foreach (($adminUsers ?? []) as $u): ?>
                                        <option value="<?= (int) $u['id'] ?>"><?= e(trim(($u['prenom'] ?? '') . ' ' . ($u['nom'] ?? '')) . ' — ' . (string) ($u['courriel'] ?? '')) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="a-espace"><?= e($t('Espace', 'Space')) ?></label>
                                <select class="form-select" id="a-espace" name="espace_id" required>
                                    <?php foreach (($adminEspaces ?? []) as $es): ?>
                                        <option value="<?= (int) $es['id'] ?>"><?= e((string) $es['nom']) ?> (<?= e((string) $es['visibilite']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="a-role"><?= e($t('Rôle local', 'Local role')) ?></label>
                                <select class="form-select" id="a-role" name="role_local">
                                    <?php foreach ($localRoles as $role): ?>
                                        <option value="<?= e($role) ?>"<?= $role === 'lecteur' ? ' selected' : '' ?>><?= e($role) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3"><?= e($t('Ajouter le membre', 'Add the member')) ?></button>
                    </form>
                </div>
            </section>
        </div>
    <?php endif; ?>

    <div class="col-12">
        <section class="panel technical-admin-panel">
            <div class="panel-head">
                <div>
                    <p class="page-eyebrow"><?= e($t('Inventaire', 'Inventory')) ?></p>
                    <h2 class="panel-title"><?= e($t('Comptes, espaces et adhésions', 'Accounts, spaces and memberships')) ?></h2>
                </div>
            </div>
            <div class="panel-body">
                <div class="row g-4">
                    <div class="col-md-4">
                        <h3 class="h6"><?= e($t('Comptes', 'Accounts')) ?> <span class="text-muted">(<?= count($adminUsers ?? []) ?>)</span></h3>
                        <ul class="small mb-0 ps-3">
                            <?php foreach (($adminUsers ?? []) as $u): ?>
                                <li><?= e(trim(($u['prenom'] ?? '') . ' ' . ($u['nom'] ?? ''))) ?> — <span class="text-muted"><?= e((string) ($u['courriel'] ?? '')) ?></span> · <strong><?= e((string) ($u['role_global'] ?? '')) ?></strong></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <h3 class="h6"><?= e($t('Espaces', 'Spaces')) ?> <span class="text-muted">(<?= count($adminEspaces ?? []) ?>)</span></h3>
                                                <ul class="small mb-0 ps-3">
                            <?php foreach (($adminEspaces ?? []) as $es): ?>
                                <?php $esActif = ($es['statut'] ?? 'actif') === 'actif'; ?>
                                <li class="mb-2">
                                    <?= e((string) $es['nom']) ?>
                                    — <span class="text-muted"><?= e((string) $es['visibilite']) ?></span>
                                    · <strong class="<?= $esActif ? 'text-success' : 'text-danger' ?>"><?= e((string) ($es['statut'] ?? 'actif')) ?></strong>
                                    <?php if ($canArchive): ?>
                                        <form method="post" action="<?= e(url('/api/admin_espace_statut.php')) ?>" class="d-inline">
                                            <?= $csrf?->field() ?? '' ?>
                                            <input type="hidden" name="espace_id" value="<?= (int) $es['id'] ?>">
                                            <input type="hidden" name="statut" value="<?= $esActif ? 'archive' : 'actif' ?>">
                                            <button type="submit" class="btn btn-sm btn-link p-0 align-baseline">
                                                <?= e($esActif
                                                    ? $t('Clore cet espace', 'Close this space')
                                                    : $t('Réouvrir cet espace', 'Reopen this space')) ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <h3 class="h6"><?= e($t('Adhésions', 'Memberships')) ?> <span class="text-muted">(<?= count($adminAdhesions ?? []) ?>)</span></h3>
                                                <ul class="small mb-0 ps-3">
                            <?php foreach (($adminAdhesions ?? []) as $ad): ?>
                                <li class="mb-2">
                                    <?= e(trim((string) ($ad['prenom'] ?? '') . ' ' . (string) ($ad['nom'] ?? ''))) ?>
                                    → <?= e((string) ($ad['espace'] ?? '')) ?>
                                    · <strong><?= e((string) ($ad['role_local'] ?? '')) ?></strong>
                                    <?php if ($canManageAdhesion): ?>
                                        <form method="post" action="<?= e(url('/api/admin_adhesion_end.php')) ?>" class="d-inline">
                                            <?= $csrf?->field() ?? '' ?>
                                            <input type="hidden" name="adhesion_id" value="<?= (int) $ad['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-link p-0 align-baseline text-danger">
                                                <?= e($t("Terminer l'adhésion", 'End membership')) ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
