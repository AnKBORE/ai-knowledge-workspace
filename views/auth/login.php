<?php

declare(strict_types=1);

/** @var \App\Core\Csrf $csrf */
/** @var string|null $error */
$productName = (string) config('app.name', 'Agentic Knowledge Workspace');
?>
<main class="auth">
    <section class="auth-panel" aria-labelledby="auth-titre">
        <div class="auth-form-wrap">
            <a class="auth-brand" href="<?= e(url('/presentation?lang=fr')) ?>">
                <?= brand_mark() ?>
                <span><?= e($productName) ?></span>
            </a>

            <div class="auth-head">
                <h1 id="auth-titre">Connexion</h1>
                <p>Accédez à votre espace de connaissances.</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="auth-alert" role="status"><?= e($error) ?></div>
            <?php endif; ?>

            <form class="auth-form" method="post" action="<?= e(url('/login')) ?>">
                <?= $csrf->field() ?>

                <div class="field">
                    <label for="courriel">Adresse e-mail</label>
                    <input class="form-control" id="courriel" name="courriel" type="email"
                           autocomplete="username" required
                           placeholder="prenom.nom@organisation.org" autofocus>
                </div>

                <div class="field">
                    <label for="mot-de-passe">Mot de passe</label>
                    <div class="field-control">
                        <input class="form-control field-control-password" id="mot-de-passe" name="mot_de_passe"
                               type="password" autocomplete="current-password" required placeholder="Votre mot de passe">
                        <button class="field-toggle" type="button" data-password-toggle="mot-de-passe"
                                aria-label="Afficher le mot de passe" aria-pressed="false">
                            <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.6"/></svg>
                            <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4l16 16"/><path d="M9.9 5.2A10.8 10.8 0 0 1 12 5c6 0 9.5 7 9.5 7a17 17 0 0 1-3.2 3.9M6.1 7.4A17 17 0 0 0 2.5 12s3.5 7 9.5 7a10.6 10.6 0 0 0 4.2-.9"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                        </button>
                    </div>
                </div>

                <button class="btn btn-block" type="submit">
                    Se connecter
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </button>
            </form>

            <p class="auth-presentation">
                <a href="<?= e(url('/presentation?lang=fr')) ?>">Découvrir le projet</a>
                <span aria-hidden="true">→</span>
            </p>

            <footer class="auth-legal">
                <span>© <?= e(date('Y')) ?> <?= e($productName) ?></span>
                <span>Meta Global AI Developer Hackathon</span>
            </footer>
        </div>
    </section>
</main>
