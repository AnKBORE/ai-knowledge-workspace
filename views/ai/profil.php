<?php

declare(strict_types=1);

/** @var array<string, mixed>|null $currentUser */
/** @var list<array<string, mixed>> $memberships */
/** @var string $lang */
$T = app_i18n($lang ?? 'en');
$S = $T['shared'];
$P = $T['profil'];
$roleGlobal = (string) ($currentUser['role_global'] ?? 'membre');
$roleLabels = [
    'administrateur_technique' => $P['role_tech'],
    'administrateur_fonctionnel' => $P['role_func'],
    'membre' => $P['role_member'],
];
$localLabels = [
    'lecteur' => $P['local_lecteur'],
    'contributeur' => $P['local_contributeur'],
    'animateur' => $P['local_animateur'],
    'responsable' => $P['local_responsable'],
];
$displayName = trim((string) ($currentUser['prenom'] ?? '') . ' ' . (string) ($currentUser['nom'] ?? ''));
$initials = mb_strtoupper(
    mb_substr((string) ($currentUser['prenom'] ?? ''), 0, 1)
    . mb_substr((string) ($currentUser['nom'] ?? ''), 0, 1),
    'UTF-8'
);
$localBadgeClass = static fn (string $role): string => match ($role) {
    'responsable' => 'text-bg-primary',
    'animateur' => 'text-bg-success',
    'contributeur' => 'text-bg-info',
    default => 'text-bg-light',
};
?>
<section class="dashboard-hero">
    <div class="dashboard-hero-copy">
        <p class="dashboard-eyebrow"><?= e($S['eyebrow_root']) ?> <span aria-hidden="true">/</span> <?= e($T['pages']['/mon-profil']) ?></p>
        <h1><?= e($T['pages']['/mon-profil']) ?></h1>
        <p><?= e($P['hero_sub']) ?></p>
    </div>
    <div class="dashboard-hero-actions">
        <span class="dashboard-date"><?= e(lang_date($lang ?? 'en')) ?></span>
    </div>
</section>

<div class="row g-4">
    <div class="col-12">
        <div class="space-admin">
            <?php if (is_array($flash ?? null)): ?>
                <div class="alert alert-<?= ($flash['type'] ?? '') === 'success' ? 'success' : 'danger' ?> profil-flash" role="alert">
                    <?= e((string) ($flash['text'] ?? '')) ?>
                </div>
            <?php endif; ?>
            <div class="landing-grid">
                <section class="panel space-list-panel technical-admin-panel panel-accent-blue" aria-labelledby="profil-identite">
                    <div class="panel-head">
                        <div>
                            <p class="page-eyebrow"><?= e($P['eyebrow_identity']) ?></p>
                            <h2 class="panel-title" id="profil-identite"><?= e($displayName !== '' ? $displayName : $P['user_fallback']) ?></h2>
                            <p class="panel-sub"><?= e((string) ($currentUser['courriel'] ?? '')) ?></p>
                        </div>
                    </div>
                    <div class="panel-body">
                        <div class="profil-identity">
                            <span class="profil-avatar" aria-hidden="true"><?= e($initials !== '' ? $initials : '?') ?></span>
                            <div class="profil-identity-name">
                                <strong><?= e($displayName !== '' ? $displayName : $P['user_fallback']) ?></strong>
                                <span><?= e($roleLabels[$roleGlobal] ?? $P['role_member']) ?></span>
                            </div>
                        </div>
                        <dl class="profil-details">
                            <div><dt><?= e($P['firstname']) ?></dt><dd><?= e((string) ($currentUser['prenom'] ?? '')) ?></dd></div>
                            <div><dt><?= e($P['lastname']) ?></dt><dd><?= e((string) ($currentUser['nom'] ?? '')) ?></dd></div>
                            <div><dt><?= e($P['email']) ?></dt><dd><?= e((string) ($currentUser['courriel'] ?? '')) ?></dd></div>
                            <div><dt><?= e($P['global_role']) ?></dt><dd><span class="badge text-bg-primary"><?= e($roleLabels[$roleGlobal] ?? $P['role_member']) ?></span></dd></div>
                        </dl>
                        <div class="profil-actions">
                            <button type="button" class="btn btn-primary profil-action-btn" data-bs-toggle="modal" data-bs-target="#profil-edit-modal">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                <?= e($P['edit_profile']) ?>
                            </button>
                            <button type="button" class="btn btn-outline-primary profil-action-btn" data-bs-toggle="modal" data-bs-target="#profil-password-modal">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="11" width="16" height="9" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                                <?= e($P['change_password']) ?>
                            </button>
                        </div>
                    </div>
                </section>

                <section class="panel space-list-panel technical-admin-panel panel-accent-violet" aria-labelledby="profil-espaces">
                    <div class="panel-head">
                        <div>
                            <p class="page-eyebrow"><?= e($P['eyebrow_access']) ?></p>
                            <h2 class="panel-title" id="profil-espaces"><?= e($P['spaces_title']) ?></h2>
                            <p class="panel-sub"><?= e(sprintf($P['spaces_sub'], count($memberships), count($memberships) > 1 ? 's' : '', count($memberships) > 1 ? 's' : '')) ?></p>
                        </div>
                    </div>
                    <div class="panel-body">
                        <?php if ($memberships === []): ?>
                            <p class="text-muted"><?= e($P['no_spaces']) ?></p>
                        <?php else: ?>
                            <ul class="profil-spaces">
                                <?php foreach ($memberships as $membership): ?>
                                    <li>
                                        <span class="profil-space-icon" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="7" height="7" rx="1.6"/><rect x="13" y="4" width="7" height="7" rx="1.6"/><rect x="4" y="13" width="7" height="7" rx="1.6"/><rect x="13" y="13" width="7" height="7" rx="1.6"/></svg>
                                        </span>
                                        <strong><?= e((string) ($membership['espace_nom'] ?? '')) ?></strong>
                                        <span class="badge <?= e($localBadgeClass((string) ($membership['role_local'] ?? ''))) ?>"><?= e($localLabels[(string) ($membership['role_local'] ?? '')] ?? $P['local_lecteur']) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                    <p class="technical-admin-note technical-admin-footer"><?= e($P['spaces_footer']) ?></p>
                </section>
            </div>
        </div>
    </div>
</div>

<div class="modal fade space-create-modal profil-modal" id="profil-edit-modal" tabindex="-1" aria-labelledby="profil-edit-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered profil-modal-dialog">
        <div class="modal-content">
            <form class="space-form" method="post" action="<?= e(url('/mon-profil')) ?>">
                <?= $csrf->field() ?>
                <div class="modal-header space-create-header text-bg-primary border-bottom border-2 border-primary">
                    <span class="space-create-head-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                    </span>
                    <div class="space-create-head-copy">
                        <p class="space-modal-eyebrow"><?= e($P['edit_modal_eyebrow']) ?></p>
                        <h2 class="modal-title" id="profil-edit-title"><?= e($P['edit_modal_title']) ?></h2>
                        <p><?= e($P['edit_modal_sub']) ?></p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="<?= e($S['close']) ?>"></button>
                </div>
                <div class="modal-body space-create-body">
                    <div class="space-create-fields">
                        <label><?= e($P['firstname']) ?>
                            <input name="prenom" value="<?= e((string) ($currentUser['prenom'] ?? '')) ?>" required maxlength="80">
                        </label>
                        <label><?= e($P['lastname']) ?>
                            <input name="nom" value="<?= e((string) ($currentUser['nom'] ?? '')) ?>" required maxlength="80">
                        </label>
                        <label class="profil-field-full"><?= e($P['email']) ?>
                            <input name="courriel" type="email" value="<?= e((string) ($currentUser['courriel'] ?? '')) ?>" required maxlength="190">
                        </label>
                        <label class="profil-field-full"><?= e($S['password_label']) ?>
                            <input name="mot_de_passe_actuel" type="password" required autocomplete="current-password" placeholder="<?= e($P['edit_password_ph']) ?>">
                        </label>
                    </div>
                </div>
                <div class="modal-footer space-create-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= e($S['cancel']) ?></button>
                    <button type="submit" class="btn btn-primary space-create-submit">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                        <?= e($P['save']) ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade space-create-modal profil-modal" id="profil-password-modal" tabindex="-1" aria-labelledby="profil-password-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered profil-modal-dialog">
        <div class="modal-content">
            <form class="space-form" method="post" action="<?= e(url('/mon-profil/mot-de-passe')) ?>">
                <?= $csrf->field() ?>
                <div class="modal-header space-create-header text-bg-success border-bottom border-2 border-success">
                    <span class="space-create-head-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="11" width="16" height="9" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                    </span>
                    <div class="space-create-head-copy">
                        <p class="space-modal-eyebrow"><?= e($P['pwd_modal_eyebrow']) ?></p>
                        <h2 class="modal-title" id="profil-password-title"><?= e($P['pwd_modal_title']) ?></h2>
                        <p><?= e($P['pwd_modal_sub']) ?></p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="<?= e($S['close']) ?>"></button>
                </div>
                <div class="modal-body space-create-body">
                    <div class="space-create-fields">
                        <label class="profil-field-full"><?= e($P['pwd_current']) ?>
                            <input name="mot_de_passe_actuel" type="password" required autocomplete="current-password">
                        </label>
                        <label><?= e($P['pwd_new']) ?>
                            <input name="nouveau_mot_de_passe" type="password" required autocomplete="new-password" minlength="12">
                        </label>
                        <label><?= e($P['pwd_confirm']) ?>
                            <input name="confirmation_mot_de_passe" type="password" required autocomplete="new-password" minlength="12">
                        </label>
                    </div>
                </div>
                <div class="modal-footer space-create-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= e($S['cancel']) ?></button>
                    <button type="submit" class="btn btn-success space-create-submit">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                        <?= e($P['update']) ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
