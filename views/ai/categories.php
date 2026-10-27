<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $categories */
?>
<section class="dashboard-hero">
    <div class="dashboard-hero-copy">
        <p class="dashboard-eyebrow">Espace de connaissances <span aria-hidden="true">/</span> Catégories</p>
        <h1>Catégories</h1>
        <p>Classez vos documents par catégorie (identifiant unique automatique). Les catégories sont réutilisables à l'indexation.</p>
    </div>
    <div class="dashboard-hero-actions">
        <span class="dashboard-date"><?= e(date('d/m/Y')) ?></span>
    </div>
</section>

<div class="row g-4">
    <div class="col-12">
        <div class="space-admin">
            <div class="landing-grid">
                <section class="panel space-list-panel technical-admin-panel" aria-labelledby="categorie-form-title">
                    <div class="panel-head">
                        <div>
                            <p class="page-eyebrow">Nouvelle catégorie</p>
                            <h2 class="panel-title" id="categorie-form-title">Ajouter une catégorie</h2>
                            <p class="panel-sub">Le nom, la description et la couleur seront utilisés comme badge sur la liste des documents.</p>
                        </div>
                    </div>
                    <div class="panel-body">
                        <form id="categorie-form" method="post" action="<?= e(url('/api/categorie_save.php')) ?>">
                            <input type="hidden" name="_csrf" value="<?= e($csrf->token()) ?>">
                            <div class="mb-3">
                                <label for="categorie-nom" class="form-label">Nom</label>
                                <input id="categorie-nom" name="nom" class="form-control" placeholder="Rapports" required maxlength="120">
                            </div>
                            <div class="mb-3">
                                <label for="categorie-description" class="form-label">Description (optionnelle)</label>
                                <input id="categorie-description" name="description" class="form-control" placeholder="Rapports d'activité, bilans…" maxlength="255">
                            </div>
                            <div class="mb-3">
                                <label for="categorie-couleur" class="form-label">Couleur</label>
                                <input id="categorie-couleur" name="couleur" type="color" class="form-control form-control-color" value="#4a63c8">
                            </div>
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                        </form>
                        <div id="categorie-status" class="small mt-2"></div>
                    </div>
                    <p class="technical-admin-note technical-admin-footer">Chaque création ou suppression de catégorie est journalisée.</p>
                </section>

                <section class="panel space-list-panel technical-admin-panel" aria-labelledby="categories-title">
                    <div class="panel-head">
                        <div>
                            <p class="page-eyebrow">Classification</p>
                            <h2 class="panel-title" id="categories-title">Catégories <span class="technical-admin-count"><?= count($categories) ?></span></h2>
                            <p class="panel-sub">Identifiant unique automatique · réutilisables à l'indexation.</p>
                        </div>
                    </div>
                    <div class="panel-body">
                        <?php if ($categories === []): ?>
                            <p class="text-muted">Aucune catégorie pour l'instant.</p>
                        <?php else: ?>
                            <ul class="categorie-list" id="categorie-list">
                                <?php foreach ($categories as $categorie): ?>
                                    <li>
                                        <span class="categorie-dot" style="background: <?= e((string) ($categorie['couleur'] ?? '#4a63c8')) ?>"></span>
                                        <div class="categorie-copy">
                                            <strong><?= e((string) ($categorie['nom'] ?? '')) ?></strong>
                                            <small><?= (int) ($categorie['documents'] ?? 0) ?> document<?= ((int) ($categorie['documents'] ?? 0)) > 1 ? 's' : '' ?> · <?= e((string) ($categorie['slug'] ?? '')) ?></small>
                                        </div>
                                        <button type="button" class="doc-action doc-action-danger categorie-delete" data-categorie-id="<?= (int) ($categorie['id'] ?? 0) ?>" title="Supprimer" aria-label="Supprimer <?= e((string) ($categorie['nom'] ?? 'la catégorie')) ?>">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M6 7l1 13a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-13"/><path d="M10 11v6M14 11v6"/></svg>
                                        </button>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var form = document.getElementById('categorie-form');
    var status = document.getElementById('categorie-status');
    if (!form) { return; }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        status.textContent = 'Enregistrement…';
        fetch(form.action, { method: 'POST', body: new FormData(form), credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.error) { status.textContent = data.error; status.className = 'small mt-2 text-danger'; return; }
                status.textContent = 'Catégorie enregistrée.';
                status.className = 'small mt-2 text-success';
                window.location.reload();
            })
            .catch(function () { status.textContent = 'Erreur réseau.'; status.className = 'small mt-2 text-danger'; });
    });

    document.querySelectorAll('.categorie-delete').forEach(function (button) {
        button.addEventListener('click', function () {
            if (!window.confirm('Supprimer cette catégorie ? Les documents liés seront déclassés.')) { return; }
            var body = new FormData();
            body.append('_csrf', <?= json_encode($csrf->token(), JSON_UNESCAPED_UNICODE) ?>);
            body.append('categorie_id', button.getAttribute('data-categorie-id'));
            fetch(<?= json_encode(url('/api/categorie_delete.php'), JSON_UNESCAPED_UNICODE) ?>, { method: 'POST', body: body, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.error) { window.alert(data.error); return; }
                    window.location.reload();
                });
        });
    });
})();
</script>
