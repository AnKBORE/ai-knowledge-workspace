<?php

declare(strict_types=1);
?>
<section class="dashboard-hero">
    <div class="dashboard-hero-copy">
        <p class="dashboard-eyebrow">Espace de connaissances <span aria-hidden="true">/</span> Question</p>
        <h1>Question</h1>
        <p>Interrogez vos documents : l'assistant répond à partir de vos sources et les cite.</p>
    </div>
    <div class="dashboard-hero-actions">
        <span class="dashboard-date"><?= e(date('d/m/Y')) ?></span>
    </div>
</section>

<div class="row g-4">
    <div class="col-12">
        <div class="space-admin">
            <section class="panel space-list-panel technical-admin-panel" aria-labelledby="question-title">
                <div class="panel-head">
                    <div>
                        <p class="page-eyebrow">Assistant documentaire</p>
                        <h2 class="panel-title" id="question-title">Votre question</h2>
                        <p class="panel-sub">L'assistant répond en citant ses sources. Reformulez si la réponse manque de précision.</p>
                    </div>
                </div>
                <div class="panel-body">
                    <form id="ask-form">
                        <label for="question" class="form-label">Votre question</label>
                        <textarea id="question" class="form-control" rows="4" placeholder="Quels sont les principaux risques du dernier trimestre ?" required></textarea>
                        <button type="submit" class="btn btn-primary mt-3">Demander</button>
                    </form>
                </div>
                <p class="technical-admin-note technical-admin-footer">Chaque question est journalisée (mode, question, nombre de sources) et la réponse cite ses sources.</p>
            </section>
        </div>
    </div>
</div>

<?php require __DIR__ . '/_modal.php'; ?>
