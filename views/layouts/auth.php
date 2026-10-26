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
    <title><?= e($title ?? 'Connexion') ?> — <?= e($productName) ?></title>
    <link rel="stylesheet" href="<?= e(url('/assets/vendor/bootstrap/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('/assets/css/app.css')) ?>">
</head>
<body class="app-auth">
    <?= $content ?>
</body>
</html>
