<?php

declare(strict_types=1);

/** @var string $lang */
$T = app_i18n($lang ?? 'en');
$S = $T['shared'];
$P = $T['task'];
?>
<section class="dashboard-hero">
    <div class="dashboard-hero-copy">
        <p class="dashboard-eyebrow"><?= e($S['eyebrow_root']) ?> <span aria-hidden="true">/</span> <?= e($T['pages']['/tache']) ?></p>
        <h1><?= e($T['pages']['/tache']) ?></h1>
        <p><?= e($P['hero_sub']) ?></p>
    </div>
    <div class="dashboard-hero-actions">
        <span class="dashboard-date"><?= e(lang_date($lang ?? 'en')) ?></span>
    </div>
</section>

<div class="row g-4">
    <div class="col-12">
        <div class="space-admin">
            <section class="panel space-list-panel technical-admin-panel panel-accent-violet" aria-labelledby="task-title">
                <div class="panel-head">
                    <div>
                        <p class="page-eyebrow"><?= e($S['assistant']) ?></p>
                        <h2 class="panel-title" id="task-title"><?= e($P['panel_title']) ?></h2>
                        <p class="panel-sub"><?= e($P['panel_sub']) ?></p>
                    </div>
                </div>
                <div class="panel-body">
                    <form id="task-form">
                        <label for="task-docs" class="form-label"><?= e($P['docs_label'] ?? 'Documents sources') ?></label>
                        <div class="task-docs-field">
                            <div class="task-docs-meta">
                                <span id="task-docs-count" class="task-docs-count" hidden></span>
                                <button type="button" class="task-docs-clear" id="task-docs-clear" hidden>
                                    <?= e($P['docs_clear'] ?? '') ?>
                                </button>
                            </div>
                            <select id="task-docs"
                                    class="form-select"
                                    multiple
                                    aria-describedby="task-docs-help"
                                    data-placeholder="<?= e($P['docs_placeholder'] ?? '') ?>"
                                    data-count-template="<?= e($P['docs_count'] ?? '%d') ?>"
                                    data-clear-label="<?= e($P['docs_clear'] ?? '') ?>">
                                <?php foreach (($documents ?? []) as $docOption): ?>
                                    <option value="<?= (int) $docOption['id'] ?>"><?= e((string) $docOption['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <p id="task-docs-help" class="form-text"><?= e($P['docs_help'] ?? '') ?></p>

                        <label for="instruction" class="form-label mt-3"><?= e($P['label']) ?></label>
                        <textarea id="instruction" class="form-control" rows="4" placeholder="<?= e($P['placeholder']) ?>" required></textarea>

                        <button type="submit" class="btn btn-primary mt-3"><?= e($P['submit']) ?></button>                    </form>
                </div>
                <p class="technical-admin-note technical-admin-footer"><?= e($P['footer']) ?></p>
            </section>
        </div>
    </div>
</div>

<?php require __DIR__ . '/_modal.php'; ?>
