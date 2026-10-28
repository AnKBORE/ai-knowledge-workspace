<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $categories */
/** @var string $lang */
$T = app_i18n($lang ?? 'en');
$S = $T['shared'];
$P = $T['categories'];
?>
<section class="dashboard-hero">
    <div class="dashboard-hero-copy">
        <p class="dashboard-eyebrow"><?= e($S['eyebrow_root']) ?> <span aria-hidden="true">/</span> <?= e($T['pages']['/categories']) ?></p>
        <h1><?= e($T['pages']['/categories']) ?></h1>
        <p><?= e($P['hero_sub']) ?></p>
    </div>
    <div class="dashboard-hero-actions">
        <span class="dashboard-date"><?= e(lang_date($lang ?? 'en')) ?></span>
    </div>
</section>

<div class="row g-4">
    <div class="col-12">
        <div class="space-admin">
            <div class="landing-grid">
                <section class="panel space-list-panel technical-admin-panel panel-accent-green" aria-labelledby="categorie-form-title">
                    <div class="panel-head">
                        <div>
                            <p class="page-eyebrow"><?= e($P['form_eyebrow']) ?></p>
                            <h2 class="panel-title" id="categorie-form-title"><?= e($P['form_title']) ?></h2>
                            <p class="panel-sub"><?= e($P['form_sub']) ?></p>
                        </div>
                    </div>
                    <div class="panel-body">
                        <form id="categorie-form" class="category-form form-card" method="post" action="<?= e(url('/api/categorie_save.php')) ?>">
                            <input type="hidden" name="_csrf" value="<?= e($csrf->token()) ?>">
                            <div class="mb-3">
                                <label for="categorie-nom" class="form-label fw-semibold"><?= e($P['label_name']) ?></label>
                                <input id="categorie-nom" name="nom" class="form-control" placeholder="<?= e($P['placeholder_name']) ?>" required maxlength="120">
                            </div>
                            <div class="mb-3">
                                <label for="categorie-description" class="form-label fw-semibold"><?= e($P['label_desc']) ?></label>
                                <input id="categorie-description" name="description" class="form-control" placeholder="<?= e($P['placeholder_desc']) ?>" maxlength="255">
                            </div>
                            <div class="mb-3">
                                <label for="categorie-couleur" class="form-label fw-semibold"><?= e($P['label_color']) ?></label>
                                <input id="categorie-couleur" name="couleur" type="color" class="form-control form-control-color categorie-color" value="#4a63c8">
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary form-submit">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                                    <?= e($P['submit']) ?>
                                </button>
                            </div>
                        </form>
                        <div id="categorie-status" class="small mt-2"></div>
                    </div>
                    <p class="technical-admin-note technical-admin-footer"><?= e($P['form_footer']) ?></p>
                </section>

                <section class="panel space-list-panel technical-admin-panel panel-accent-violet" aria-labelledby="categories-title">
                    <div class="panel-head">
                        <div>
                            <p class="page-eyebrow"><?= e($P['list_eyebrow']) ?></p>
                            <h2 class="panel-title" id="categories-title"><?= e($P['list_title']) ?> <span class="technical-admin-count"><?= count($categories) ?></span></h2>
                            <p class="panel-sub"><?= e($P['list_sub']) ?></p>
                        </div>
                    </div>
                    <div class="panel-body">
                        <?php if ($categories === []): ?>
                            <p class="text-muted"><?= e($P['empty']) ?></p>
                        <?php else: ?>
                            <ul class="categorie-list" id="categorie-list">
                                <?php foreach ($categories as $categorie): ?>
                                    <li>
                                        <span class="categorie-dot" style="background: <?= e((string) ($categorie['couleur'] ?? '#4a63c8')) ?>"></span>
                                        <div class="categorie-copy">
                                            <strong><?= e((string) ($categorie['nom'] ?? '')) ?></strong>
                                            <small><?= e(sprintf($P['doc_count'], (int) ($categorie['documents'] ?? 0), ((int) ($categorie['documents'] ?? 0)) > 1 ? 's' : '', (string) ($categorie['slug'] ?? ''))) ?></small>
                                        </div>
                                        <button type="button" class="doc-action doc-action-danger categorie-delete" data-categorie-id="<?= (int) ($categorie['id'] ?? 0) ?>" title="<?= e($P['delete_label']) ?>" aria-label="<?= e($P['delete_label']) ?> <?= e((string) ($categorie['nom'] ?? '')) ?>">
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
        status.textContent = <?= json_encode($P['saving'], JSON_UNESCAPED_UNICODE) ?>;
        fetch(form.action, { method: 'POST', body: new FormData(form), credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.error) { status.textContent = data.error; status.className = 'small mt-2 text-danger'; return; }
                status.textContent = <?= json_encode($P['saved'], JSON_UNESCAPED_UNICODE) ?>;
                status.className = 'small mt-2 text-success';
                window.location.reload();
            })
            .catch(function () { status.textContent = <?= json_encode($P['network_error'], JSON_UNESCAPED_UNICODE) ?>; status.className = 'small mt-2 text-danger'; });
    });

    document.querySelectorAll('.categorie-delete').forEach(function (button) {
        button.addEventListener('click', function () {
            if (!window.confirm(<?= json_encode($P['delete_confirm'], JSON_UNESCAPED_UNICODE) ?>)) { return; }
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
