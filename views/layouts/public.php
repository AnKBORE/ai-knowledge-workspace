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
    <link rel="stylesheet" href="<?= e(url('/assets/css/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('/assets/css/shell.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('/assets/css/spaces.css')) ?>">
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
            <a class="btn btn-sm btn-primary" href="<?= e(url('/login')) ?>"><?= e($navLabel) ?></a>
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
