<?php

declare(strict_types=1);

/** @var string $content */
$productName = (string) config('app.name', 'Agentic Knowledge Workspace');
$locale = (string) config('app.locale', 'fr');
$currentUser = $currentUser ?? null;
$csrf = $csrf ?? null;
$displayName = is_array($currentUser)
    ? trim((string) ($currentUser['prenom'] ?? '') . ' ' . (string) ($currentUser['nom'] ?? ''))
    : '';
$displayRole = match ($currentUser['role_global'] ?? null) {
    'administrateur_technique' => 'Administrateur technique',
    'administrateur_fonctionnel' => 'Administrateur fonctionnel',
    'membre' => 'Membre',
    default => 'Membre',
};
$initials = mb_strtoupper(
    mb_substr((string) ($currentUser['prenom'] ?? ''), 0, 1)
    . mb_substr((string) ($currentUser['nom'] ?? ''), 0, 1),
    'UTF-8'
);
$currentPath = '/' . trim((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH), '/');
$navItems = [
    ['path' => '/', 'label' => 'Question'],
    ['path' => '/tache', 'label' => 'Tâche agentique'],
    ['path' => '/documents', 'label' => 'Documents indexés'],
    ['path' => '/ajouter', 'label' => 'Ajouter un document'],
    ['path' => '/journal', 'label' => "Journal d'audit"],
];
?>
<!doctype html>
<html lang="<?= e($locale) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title ?? 'Assistant') ?> — <?= e($productName) ?></title>
    <link rel="stylesheet" href="<?= e(url('/assets/vendor/bootstrap/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('/assets/css/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('/assets/css/shell.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('/assets/css/spaces.css')) ?>">
    <script src="<?= e(url('/assets/vendor/bootstrap/bootstrap.bundle.min.js')) ?>"></script>
</head>
<body class="app-body">
<div class="app-shell">
    <aside class="sidebar" id="sidebar" aria-label="Navigation de l'application">
        <a class="sidebar-brand" href="<?= e(url('/')) ?>">
            <?= brand_mark() ?>
            <span class="sidebar-brand-text">
                <strong><?= e($productName) ?></strong>
                <span>Espace de connaissances</span>
            </span>
        </a>

        <nav class="side-nav" aria-label="Menu principal">
            <p class="side-label">Espace de travail</p>
            <?php foreach ($navItems as $item): ?>
                <?php $isActive = $currentPath === $item['path']; ?>
                <a class="side-link <?= $isActive ? 'is-active' : '' ?>" href="<?= e(url($item['path'])) ?>"<?= $isActive ? ' aria-current="page"' : '' ?>>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="4" width="7" height="7" rx="1.6"/><rect x="13" y="4" width="7" height="7" rx="1.6"/><rect x="4" y="13" width="7" height="7" rx="1.6"/><rect x="13" y="13" width="7" height="7" rx="1.6"/></svg>
                    <?= e($item['label']) ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="side-foot">
            <p class="side-version">Socle v<?= e((string) config('app.version', '1.0.0')) ?></p>
        </div>
    </aside>

    <div class="sidebar-backdrop" data-sidebar-close aria-hidden="true"></div>

    <div class="app-main">
        <header class="topbar">
            <button class="topbar-burger" type="button" data-sidebar-toggle aria-label="Ouvrir la navigation" aria-expanded="false" aria-controls="sidebar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>

            <div class="topbar-crumbs">
                <span class="crumbs"><a href="<?= e(url('/')) ?>"><?= e($productName) ?></a> / Assistant</span>
                <strong class="topbar-title"><?= e($title ?? 'Assistant') ?></strong>
            </div>

            <div class="topbar-actions">
                <div class="dropdown account-dropdown">
                    <button class="account-menu-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Ouvrir le menu du compte">
                        <span class="avatar" aria-hidden="true"><?= e($initials) ?></span>
                        <span class="account-toggle-label"><?= e($displayName) ?></span>
                        <svg class="account-menu-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end account-menu">
                        <div class="account-menu-identity">
                            <span class="avatar" aria-hidden="true"><?= e($initials) ?></span>
                            <div><strong><?= e($displayName) ?></strong><span><?= e($displayRole) ?></span></div>
                        </div>
                        <hr class="dropdown-divider">
                        <?php if ($csrf !== null): ?>
                            <form class="account-menu-logout" method="post" action="<?= e(url('/logout')) ?>">
                                <?= $csrf->field() ?>
                                <button class="dropdown-item account-menu-signout" type="submit">Déconnexion</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </header>

        <main class="app-content">
            <?= $content ?>
        </main>
    </div>
</div>

<script>window.WORKSPACE_BASE = <?= json_encode(rtrim(url('/'), '/'), JSON_UNESCAPED_SLASHES) ?>;</script>
<script src="<?= e(url('/assets/js/workspace.js')) ?>" defer></script>
<script src="<?= e(url('/assets/js/shell.js')) ?>" defer></script>
</body>
</html>
