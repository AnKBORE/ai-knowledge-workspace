<?php

declare(strict_types=1);

/** @var string $lang */
$T = app_i18n($lang ?? 'en');
$S = $T['shared'];
$P = $T['question'];
?>
<section class="dashboard-hero">
    <div class="dashboard-hero-copy">
        <p class="dashboard-eyebrow"><?= e($S['eyebrow_root']) ?> <span aria-hidden="true">/</span> <?= e($T['pages']['/']) ?></p>
        <h1><?= e($T['pages']['/']) ?></h1>
        <p><?= e($P['hero_sub']) ?></p>
        <?php if (is_array($stats ?? null)): ?>
            <div class="stat-row">
                <span class="stat-pill"><strong><?= (int) $stats['documents'] ?></strong> <?= e($P['stat_docs']) ?></span>
                <span class="stat-pill"><strong><?= (int) $stats['chunks'] ?></strong> <?= e($P['stat_chunks']) ?></span>
                <span class="stat-pill"><strong><?= (int) $stats['categories'] ?></strong> <?= e($P['stat_categories']) ?></span>
            </div>
        <?php endif; ?>
    </div>
    <div class="dashboard-hero-actions">
        <span class="dashboard-date"><?= e(lang_date($lang ?? 'en')) ?></span>
    </div>
</section>

<div class="row g-4">
    <div class="col-12">
        <div class="space-admin">
            <section class="panel space-list-panel technical-admin-panel panel-accent-blue" aria-labelledby="question-title">
                <div class="panel-head">
                    <div>
                        <p class="page-eyebrow"><?= e($S['assistant']) ?></p>
                        <h2 class="panel-title" id="question-title"><?= e($P['panel_title']) ?></h2>
                        <p class="panel-sub"><?= e($P['panel_sub']) ?></p>
                    </div>
                </div>
                <div class="panel-body">
                    <form id="ask-form">
                        <label for="question" class="form-label"><?= e($P['label']) ?></label>
                        <textarea id="question" class="form-control" rows="4" placeholder="<?= e($P['placeholder']) ?>" required></textarea>
                        <button type="submit" class="btn btn-primary mt-3"><?= e($P['submit']) ?></button>
                    </form>
                </div>
                <p class="technical-admin-note technical-admin-footer"><?= e($P['footer']) ?></p>
            </section>
        </div>
    </div>
</div>

<?php require __DIR__ . '/_modal.php'; ?>
