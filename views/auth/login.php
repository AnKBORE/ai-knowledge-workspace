<?php

declare(strict_types=1);

/** @var \App\Core\Csrf $csrf */
/** @var string|null $error */
$productName = (string) config('app.name', 'Agentic Knowledge Workspace');
?>
<div class="auth-shell">
    <aside class="auth-visual">
        <a class="brand" href="<?= e(url('/')) ?>">
            <?= brand_mark() ?>
            <span class="brand-text"><?= e($productName) ?></span>
        </a>

        <h1>Vos documents, transformés en réponses citées.</h1>
        <p>Un espace de travail agentique et multimodal : posez une question, générez un résumé ou rédigez une note — chaque réponse s'appuie sur vos documents et cite ses sources.</p>

        <ul class="auth-points">
            <li><span class="dot">✓</span><span>Réponses ancrées, sources citées</span></li>
            <li><span class="dot">✓</span><span>Résumés et notes générés</span></li>
            <li><span class="dot">✓</span><span>Gouvernance : rôles, versions, audit</span></li>
        </ul>

        <p class="auth-visual-foot">Meta Global AI Developer Hackathon · Build for Your World</p>
    </aside>

    <main class="auth-panel">
        <form class="auth-card" method="post" action="<?= e(url('/login')) ?>">
            <h2>Connexion</h2>
            <p class="auth-sub">Accédez à votre espace de connaissances.</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert"><?= e($error) ?></div>
            <?php endif; ?>

            <?= $csrf->field() ?>

            <div class="mb-3">
                <label for="courriel" class="form-label">Adresse e-mail</label>
                <input id="courriel" name="courriel" type="email" class="form-control" autocomplete="username" required autofocus>
            </div>

            <div class="mb-3">
                <label for="mot_de_passe" class="form-label">Mot de passe</label>
                <input id="mot_de_passe" name="mot_de_passe" type="password" class="form-control" autocomplete="current-password" required>
            </div>

            <button type="submit" class="btn btn-primary w-100">Se connecter</button>
        </form>
    </main>
</div>
