<?php

declare(strict_types=1);

/** @var string $content */
/** @var string $lang */
$productName = (string) config('app.name', 'Agentic Knowledge Workspace');
$isFr = $lang === 'fr';
$navLabel = $isFr ? 'Connexion' : 'Sign in';
?>
<!doctype html>
<html lang="<?= e($lang) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= e($isFr ? 'Espace de connaissances agentique : réponses sourcées, tâches rédigées, gouvernance complète.' : 'Agentic knowledge workspace: sourced answers, drafted tasks, full governance.') ?>">
    <title><?= e($title ?? $productName) ?></title>
    <link rel="stylesheet" href="<?= e(url('/assets/vendor/bootstrap/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('/assets/css/app.css')) ?>?v=<?= (int) @filemtime(dirname(__DIR__, 2) . '/public/assets/css/app.css') ?>">
    <link rel="stylesheet" href="<?= e(url('/assets/css/shell.css')) ?>?v=<?= (int) @filemtime(dirname(__DIR__, 2) . '/public/assets/css/shell.css') ?>">
    <link rel="stylesheet" href="<?= e(url('/assets/css/spaces.css')) ?>?v=<?= (int) @filemtime(dirname(__DIR__, 2) . '/public/assets/css/spaces.css') ?>">
</head>
<body class="landing-body">
    <header class="landing-header">
        <a class="landing-brand" href="<?= e(url('/presentation?lang=' . $lang)) ?>">
            <span class="landing-brand-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 3 8l9 5 9-5-9-5Z"/><path d="m3 13 9 5 9-5"/></svg>
            </span>
            <span class="landing-brand-text">
                <strong><?= e($productName) ?></strong>
                <small><?= e($isFr ? 'Espace de connaissances' : 'Knowledge workspace') ?></small>
            </span>
        </a>
        <nav class="landing-nav" aria-label="<?= e($isFr ? 'Navigation principale' : 'Main navigation') ?>">
            <div class="landing-lang" role="group" aria-label="Langue / Language">
                <a class="landing-lang-link <?= $lang === 'fr' ? 'is-active' : '' ?>" href="<?= e(url('/presentation?lang=fr')) ?>">FR</a>
                <a class="landing-lang-link <?= $lang === 'en' ? 'is-active' : '' ?>" href="<?= e(url('/presentation?lang=en')) ?>">EN</a>
            </div>
            <a class="btn btn-icon landing-login-btn" href="<?= e(url('/login')) ?>" title="<?= e($navLabel) ?>" aria-label="<?= e($navLabel) ?>">
                <svg viewBox="0 0 16 16" width="34" height="34" fill="currentColor" aria-hidden="true"><path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/><path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z"/></svg>
            </a>
        </nav>
    </header>

    <main class="landing-main">
        <?= $content ?>
    </main>

    <footer class="landing-footer">
        <p><?= e($productName) ?> — <?= e($isFr ? 'projet open source sous licence MIT.' : 'open-source project under the MIT licence.') ?></p>
        <p class="landing-footer-meta">
            <span>PHP 8.4.27</span><span>MySQL 8.4.13</span><span>Muse Spark Open</span>
            <span><?= e($isFr ? 'Hackathon Meta — Indie Developer' : 'Meta Hackathon — Indie Developer') ?></span>
        </p>
    </footer>
</body>
</html>
