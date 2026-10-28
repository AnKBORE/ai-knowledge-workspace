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
$initials = mb_strtoupper(
    mb_substr((string) ($currentUser['prenom'] ?? ''), 0, 1)
    . mb_substr((string) ($currentUser['nom'] ?? ''), 0, 1),
    'UTF-8'
);
$currentPath = '/' . trim((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH), '/');
$lang = ($lang ?? 'en') === 'en' ? 'en' : 'fr';
$T = [
    'fr' => [
        'workspace' => 'Espace de connaissances',
        'nav_label' => 'Espace de travail',
        'crumb' => 'Assistant',
        'signout' => 'Déconnexion',
        'nav' => ['/', 'Tâche agentique', 'Documents indexés', 'Ajouter un document', 'Catégories', "Journal d'audit", 'Mon profil'],
        'logout_title' => 'Se déconnecter',
        'logout_text' => 'Voulez-vous vraiment fermer votre session ?',
        'cancel' => 'Annuler',
        'open_nav' => 'Ouvrir la navigation',
        'open_account' => 'Ouvrir le menu du compte',
        'scroll_top' => 'Retour en haut',
        'role' => match ($currentUser['role_global'] ?? null) {
            'administrateur_technique' => 'Administrateur technique',
            'administrateur_fonctionnel' => 'Administrateur fonctionnel',
            default => 'Membre',
        },
    ],
    'en' => [
        'workspace' => 'Knowledge workspace',
        'nav_label' => 'Workspace',
        'crumb' => 'Assistant',
        'signout' => 'Sign out',
        'nav' => ['Question', 'Agentic task', 'Indexed documents', 'Add a document', 'Categories', 'Audit journal', 'My profile'],
        'logout_title' => 'Sign out',
        'logout_text' => 'Are you sure you want to close your session?',
        'cancel' => 'Cancel',
        'open_nav' => 'Open navigation',
        'open_account' => 'Open account menu',
        'scroll_top' => 'Back to top',
        'role' => match ($currentUser['role_global'] ?? null) {
            'administrateur_technique' => 'Technical administrator',
            'administrateur_fonctionnel' => 'Functional administrator',
            default => 'Member',
        },
    ],
][$lang];
$navItems = [
    ['path' => '/', 'icon' => '<circle cx="12" cy="12" r="9"/><path d="M9.2 9.2a2.8 2.8 0 0 1 5.5.7c0 1.8-2.7 2.3-2.7 3.8"/><path d="M12 16.8v.01"/>'],
    ['path' => '/tache', 'icon' => '<path d="M12 3l1.7 4.6L18 9l-4.3 1.4L12 15l-1.7-4.6L6 9l4.3-1.4Z"/><path d="M18.5 15.5l.8 2.2 2.2.8-2.2.8-.8 2.2-.8-2.2-2.2-.8 2.2-.8Z"/>'],
    ['path' => '/documents', 'icon' => '<path d="M4 6a2 2 0 0 1 2-2h4l2 2h6a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2Z"/>'],
    ['path' => '/ajouter', 'icon' => '<path d="M12 16V4m0 0 4 4m-4-4-4 4"/><path d="M4 15v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/>'],
    ['path' => '/categories', 'icon' => '<path d="M4 4h6l2 2v6l-8 8Z"/><circle cx="7.5" cy="7.5" r="1"/>'],
    ['path' => '/journal', 'icon' => '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 9h6M9 13h6M9 17h4"/>'],
    ['path' => '/mon-profil', 'icon' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c1.5-3.5 4.5-5 8-5s6.5 1.5 8 5"/>'],
];
foreach ($navItems as $i => $item) {
    $navItems[$i]['label'] = $T['nav'][$i];
}
$displayRole = $T['role'];
?>
<!doctype html>
<html lang="<?= e($lang) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title ?? 'Assistant') ?> — <?= e($productName) ?></title>
    <link rel="stylesheet" href="<?= e(url('/assets/vendor/bootstrap/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('/assets/vendor/select2/css/select2.min.css')) ?>">
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
                <span><?= e($T['workspace']) ?></span>
            </span>
        </a>

        <nav class="side-nav" aria-label="<?= e($T['nav_label']) ?>">
            <p class="side-label"><?= e($T['nav_label']) ?></p>
            <?php foreach ($navItems as $item): ?>
                <?php $isActive = $currentPath === $item['path']; ?>
                <a class="side-link <?= $isActive ? 'is-active' : '' ?>" href="<?= e(url($item['path'])) ?>"<?= $isActive ? ' aria-current="page"' : '' ?>>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $item['icon'] ?></svg>
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
            <button class="topbar-burger" type="button" data-sidebar-toggle aria-label="<?= e($T['open_nav']) ?>" aria-expanded="false" aria-controls="sidebar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>

            <div class="topbar-crumbs">
                <span class="crumbs"><a href="<?= e(url('/')) ?>"><?= e($productName) ?></a> / <?= e($T['crumb']) ?></span>
                <strong class="topbar-title"><?= e($title ?? $T['crumb']) ?></strong>
            </div>

            <div class="topbar-actions">
                <div class="landing-lang" role="group" aria-label="Langue / Language">
                    <a class="landing-lang-link <?= $lang === 'fr' ? 'is-active' : '' ?>" href="<?= e(url($currentPath . '?lang=fr')) ?>">FR</a>
                    <a class="landing-lang-link <?= $lang === 'en' ? 'is-active' : '' ?>" href="<?= e(url($currentPath . '?lang=en')) ?>">EN</a>
                </div>
                <div class="dropdown account-dropdown">
                    <button class="account-menu-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="<?= e($T['open_account']) ?>">
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
                            <button class="dropdown-item account-menu-signout" type="button" data-bs-toggle="modal" data-bs-target="#logout-confirm-modal"><?= e($T['signout']) ?></button>
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

<?php if ($csrf !== null): ?>
<div class="modal fade space-create-modal profil-modal" id="logout-confirm-modal" tabindex="-1" aria-labelledby="logout-confirm-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered profil-modal-dialog" style="max-width: 440px;">
        <div class="modal-content">
            <div class="space-form">
                <div class="modal-header space-create-header text-bg-dark border-bottom border-2 border-dark">
                    <span class="space-create-head-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h3"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></svg>
                    </span>
                    <div class="space-create-head-copy">
                        <p class="space-modal-eyebrow">Session</p>
                        <h2 class="modal-title" id="logout-confirm-title"><?= e($T['logout_title']) ?></h2>
                        <p><?= e($T['logout_text']) ?></p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <form class="modal-footer space-create-footer" method="post" action="<?= e(url('/logout')) ?>">
                    <?= $csrf->field() ?>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= e($T['cancel']) ?></button>
                    <button type="submit" class="btn btn-dark space-create-submit">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h3"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></svg>
                        <?= e($T['signout']) ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<a href="#" class="scroll-top d-flex align-items-center justify-content-center" aria-label="<?= e($T['scroll_top']) ?>" title="<?= e($T['scroll_top']) ?>">
    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19V5M6 11l6-6 6 6"/></svg>
</a>
<script>window.WORKSPACE_BASE = <?= json_encode(rtrim(url('/'), '/'), JSON_UNESCAPED_SLASHES) ?>;
window.WORKSPACE_I18N = <?= json_encode(app_i18n($lang)['js'], JSON_UNESCAPED_UNICODE) ?>;</script>
<script src="<?= e(url('/assets/vendor/jquery/jquery.min.js')) ?>" defer></script>
<script src="<?= e(url('/assets/vendor/select2/js/select2.min.js')) ?>" defer></script>
<script src="<?= e(url('/assets/js/select2-init.js')) ?>" defer></script>
<script src="<?= e(url('/assets/js/workspace.js')) ?>" defer></script>
<script src="<?= e(url('/assets/js/shell.js')) ?>" defer></script>
<script src="<?= e(url('/assets/js/scroll-top.js')) ?>" defer></script>
</body>
</html>
