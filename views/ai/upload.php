<?php

declare(strict_types=1);

/** @var string $lang */
$T = app_i18n($lang ?? 'en');
$S = $T['shared'];
$P = $T['upload'];
?>
<section class="dashboard-hero">
    <div class="dashboard-hero-copy">
        <p class="dashboard-eyebrow"><?= e($S['eyebrow_root']) ?> <span aria-hidden="true">/</span> <?= e($T['pages']['/ajouter']) ?></p>
        <h1><?= e($T['pages']['/ajouter']) ?></h1>
        <p><?= e($P['hero_sub']) ?></p>
    </div>
    <div class="dashboard-hero-actions">
        <span class="dashboard-date"><?= e(lang_date($lang ?? 'en')) ?></span>
    </div>
</section>

<div class="row g-4">
    <div class="col-12">
        <div class="space-admin">
            <section class="panel space-list-panel technical-admin-panel panel-accent-green" aria-labelledby="upload-title">
                <div class="panel-head">
                    <div>
                        <p class="page-eyebrow"><?= e($P['eyebrow']) ?></p>
                        <h2 class="panel-title" id="upload-title"><?= e($P['panel_title']) ?></h2>
                        <p class="panel-sub"><?= e($P['panel_sub']) ?></p>
                    </div>
                </div>
                <div class="panel-body">
                    <form id="upload-form" class="upload-form form-card" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="title" class="form-label fw-semibold"><?= e($P['label_title']) ?> <span class="field-required" aria-hidden="true">*</span><span class="visually-hidden"><?= e($S['obligatoire']) ?></span></label>
                            <input id="title" name="title" class="form-control" placeholder="<?= e($P['placeholder_title']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="categorie_id" class="form-label fw-semibold"><?= e($P['label_category']) ?> <span class="field-required" aria-hidden="true">*</span><span class="visually-hidden"><?= e($S['obligatoire']) ?></span></label>
                            <select id="categorie_id" name="categorie_id" class="form-select" required>
                                <option value="" disabled selected><?= e($P['choose_category']) ?></option>
                                <?php foreach (($categories ?? []) as $categorie): ?>
                                    <option value="<?= (int) ($categorie['id'] ?? 0) ?>"><?= e((string) ($categorie['nom'] ?? '')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="file" class="form-label fw-semibold"><?= e($P['label_file']) ?> <span class="field-required" aria-hidden="true">*</span><span class="visually-hidden"><?= e($S['obligatoire']) ?></span></label>
                            <div class="upload-dropzone">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 16V4m0 0 4 4m-4-4-4 4"/><path d="M4 15v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/></svg>
                                <input id="file" name="file" type="file" class="form-control" required>
                                <p class="upload-dropzone-hint"><?= e($P['dropzone']) ?></p>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary form-submit">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 16V4m0 0 4 4m-4-4-4 4"/><path d="M4 15v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/></svg>
                                <?= e($P['submit']) ?>
                            </button>
                        </div>
                    </form>
                    <div id="upload-status" class="small mt-2"></div>
                </div>
                <p class="technical-admin-note technical-admin-footer"><?= e($P['footer']) ?></p>
            </section>
        </div>
    </div>
</div>
