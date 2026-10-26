<?php

declare(strict_types=1);

/** @var string $content */
$productName = (string) config('app.name', 'Agentic Knowledge Workspace');
$locale = (string) config('app.locale', 'fr');
?>
<!doctype html>
<html lang="<?= e($locale) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Agentic multimodal knowledge workspace: turn documents, scans and voice into decision-ready knowledge.">
    <title><?= e($title ?? 'Workspace') ?> — <?= e($productName) ?></title>
    <link rel="stylesheet" href="<?= e(url('/assets/vendor/bootstrap/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('/assets/css/app.css')) ?>">
</head>
<body class="app-public">
<a class="skip-link" href="#contenu">Aller au contenu principal</a>

<header class="site-header">
    <nav class="navbar navbar-expand-lg container" aria-label="Navigation principale">
        <a class="brand" href="<?= e(url('/')) ?>">
            <?= brand_mark() ?>
            <span class="brand-text"><?= e($productName) ?></span>
        </a>

        <div class="navbar-nav ms-auto align-items-lg-center gap-2">
            <a class="nav-link" href="<?= e(url('/')) ?>">Assistant</a>
            <?php if (!empty($currentUser) && isset($csrf)): ?>
                <span class="nav-link disabled">
                    <?= e(trim((string) ($currentUser['prenom'] ?? '') . ' ' . (string) ($currentUser['nom'] ?? ''))) ?>
                </span>
                <form method="post" action="<?= e(url('/logout')) ?>" class="m-0">
                    <?= $csrf->field() ?>
                    <button type="submit" class="btn btn-sm btn-outline-light">Déconnexion</button>
                </form>
            <?php else: ?>
                <a class="nav-link" href="<?= e(url('/login')) ?>">Connexion</a>
            <?php endif; ?>
        </div>
    </nav>
</header>

<main id="contenu">
    <?= $content ?>
</main>

<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <a class="brand" href="<?= e(url('/')) ?>">
                    <?= brand_mark() ?>
                    <span class="brand-text"><?= e($productName) ?></span>
                </a>
                <p>Documents, scans et voix réunis dans un assistant qui répond, rédige et cite ses sources.</p>
            </div>
            <div class="footer-meta">
                <p>PHP 8.4.27 · MySQL 8.4.13</p>
                <p>Meta Global AI Developer Hackathon</p>
            </div>
        </div>
    </div>
</footer>

<script src="<?= e(url('/assets/vendor/bootstrap/bootstrap.bundle.min.js')) ?>" defer></script>
</body>
</html>
