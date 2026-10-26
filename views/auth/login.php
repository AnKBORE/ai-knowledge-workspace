<?php

declare(strict_types=1);

/** @var \App\Core\Csrf $csrf */
/** @var string|null $error */
?>
<section class="container py-5" style="max-width: 460px;">
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h1 class="h4 mb-1">Connexion</h1>
            <p class="text-muted mb-4">Accédez à votre espace de connaissances.</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= e(url('/login')) ?>">
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
        </div>
    </div>
</section>
