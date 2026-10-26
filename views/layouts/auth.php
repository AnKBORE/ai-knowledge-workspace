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
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title ?? 'Connexion') ?> — <?= e($productName) ?></title>
    <link rel="stylesheet" href="<?= e(url('/assets/vendor/bootstrap/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('/assets/css/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('/assets/css/auth.css')) ?>">
</head>
<body class="auth-body">
    <?= $content ?>
    <script src="<?= e(url('/assets/js/auth.js')) ?>" defer></script>
    <script src="<?= e(url('/assets/js/scroll-top.js')) ?>" defer></script>
</body>
</html>
