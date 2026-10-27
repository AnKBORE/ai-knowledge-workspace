<?php

declare(strict_types=1);

/** @var array<string, mixed>|null $currentUser */
/** @var list<array<string, mixed>> $memberships */
$roleGlobal = (string) ($currentUser['role_global'] ?? 'membre');
$roleLabels = [
    'administrateur_technique' => 'Administrateur technique',
    'administrateur_fonctionnel' => 'Administrateur fonctionnel',
    'membre' => 'Membre',
];
$localLabels = [
    'lecteur' => 'Lecteur',
    'contributeur' => 'Contributeur',
    'animateur' => 'Animateur',
    'responsable' => 'Responsable',
];
$displayName = trim((string) ($currentUser['prenom'] ?? '') . ' ' . (string) ($currentUser['nom'] ?? ''));
?>
<section class="dashboard-hero">
    <div class="dashboard-hero-copy">
        <p class="dashboard-eyebrow">Espace de connaissances <span aria-hidden="true">/</span> Mon profil</p>
        <h1>Mon profil</h1>
        <p>Votre identité, votre rôle global et vos accès aux espaces de travail.</p>
    </div>
    <div class="dashboard-hero-actions">
        <span class="dashboard-date"><?= e(date('d/m/Y')) ?></span>
    </div>
</section>

<div class="row g-4">
    <div class="col-12">
        <div class="space-admin">
            <div class="landing-grid">
                <section class="panel space-list-panel technical-admin-panel" aria-labelledby="profil-identite">
                    <div class="panel-head">
                        <div>
                            <p class="page-eyebrow">Identité</p>
                            <h2 class="panel-title" id="profil-identite"><?= e($displayName !== '' ? $displayName : 'Utilisateur') ?></h2>
                            <p class="panel-sub"><?= e((string) ($currentUser['courriel'] ?? '')) ?></p>
                        </div>
                    </div>
                    <div class="panel-body">
                        <dl class="profil-details">
                            <div><dt>Prénom</dt><dd><?= e((string) ($currentUser['prenom'] ?? '')) ?></dd></div>
                            <div><dt>Nom</dt><dd><?= e((string) ($currentUser['nom'] ?? '')) ?></dd></div>
                            <div><dt>Adresse e-mail</dt><dd><?= e((string) ($currentUser['courriel'] ?? '')) ?></dd></div>
                            <div><dt>Rôle global</dt><dd><span class="badge text-bg-primary"><?= e($roleLabels[$roleGlobal] ?? 'Membre') ?></span></dd></div>
                        </dl>
                    </div>
                </section>

                <section class="panel space-list-panel technical-admin-panel" aria-labelledby="profil-espaces">
                    <div class="panel-head">
                        <div>
                            <p class="page-eyebrow">Accès</p>
                            <h2 class="panel-title" id="profil-espaces">Mes espaces</h2>
                            <p class="panel-sub"><?= count($memberships) ?> espace<?= count($memberships) > 1 ? 's' : '' ?> actif<?= count($memberships) > 1 ? 's' : '' ?></p>
                        </div>
                    </div>
                    <div class="panel-body">
                        <?php if ($memberships === []): ?>
                            <p class="text-muted">Aucune adhésion active.</p>
                        <?php else: ?>
                            <ul class="profil-spaces">
                                <?php foreach ($memberships as $membership): ?>
                                    <li>
                                        <strong><?= e((string) ($membership['espace_nom'] ?? '')) ?></strong>
                                        <span class="badge text-bg-light"><?= e($localLabels[(string) ($membership['role_local'] ?? '')] ?? 'Lecteur') ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                    <p class="technical-admin-note technical-admin-footer">Les rôles sont gérés par l'administrateur de l'espace. Toute modification est journalisée.</p>
                </section>
            </div>
        </div>
    </div>
</div>
