<?php

declare(strict_types=1);

/** @var string $lang */
$T = app_i18n($lang ?? 'en');
$S = $T['shared'];
$P = $T['translate'];
$langs = ['fr' => $P['lang_fr'], 'en' => $P['lang_en'], 'mo' => $P['lang_mo']];
?>
<section class="dashboard-hero">
    <div class="dashboard-hero-copy">
        <p class="dashboard-eyebrow"><?= e($S['eyebrow_root']) ?> <span aria-hidden="true">/</span> <?= e($T['pages']['/traduire']) ?></p>
        <h1><?= e($T['pages']['/traduire']) ?></h1>
        <p><?= e($P['hero_sub']) ?></p>
    </div>
    <div class="dashboard-hero-actions">
        <span class="dashboard-date"><?= e(lang_date($lang ?? 'en')) ?></span>
    </div>
</section>

<div class="row g-4">
    <div class="col-12">
        <div class="space-admin">
            <section class="panel space-list-panel technical-admin-panel panel-accent-blue" aria-labelledby="translate-title">
                <div class="panel-head">
                    <div>
                        <p class="page-eyebrow"><?= e($P['eyebrow']) ?></p>
                        <h2 class="panel-title" id="translate-title"><?= e($P['panel_title']) ?></h2>
                        <p class="panel-sub"><?= e($P['panel_sub']) ?></p>
                    </div>
                </div>
                <div class="panel-body">
                    <form id="translate-form">
                        <div class="row g-3 align-items-center mb-3">
                            <div class="col-auto">
                                <label for="translate-from" class="form-label fw-semibold mb-0 me-2"><?= e($P['from_label']) ?></label>
                                <select id="translate-from" class="form-select d-inline-block" style="width:auto">
                                    <?php foreach ($langs as $code => $label): ?>
                                        <option value="<?= e($code) ?>" <?= $code === 'mo' ? 'selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-auto">
                                <button type="button" class="btn btn-outline-secondary btn-icon" id="translate-swap" title="<?= e($P['swap']) ?>" aria-label="<?= e($P['swap']) ?>">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M7 16V4m0 0L3 8m4-4 4 4"/><path d="M17 8v12m0 0 4-4m-4 4-4-4"/></svg>
                                </button>
                            </div>
                            <div class="col-auto">
                                <label for="translate-to" class="form-label fw-semibold mb-0 me-2"><?= e($P['to_label']) ?></label>
                                <select id="translate-to" class="form-select d-inline-block" style="width:auto">
                                    <?php foreach ($langs as $code => $label): ?>
                                        <option value="<?= e($code) ?>" <?= $code === 'fr' ? 'selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="translate-source" class="form-label fw-semibold"><?= e($P['source_label']) ?></label>
                                <textarea id="translate-source" class="form-control" rows="8" placeholder="<?= e($P['source_placeholder']) ?>" required></textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="translate-result" class="form-label fw-semibold"><?= e($P['target_label']) ?></label>
                                <div class="position-relative">
                                    <textarea id="translate-result" class="form-control" rows="8" readonly></textarea>
                                    <button type="button" class="btn btn-sm btn-outline-secondary position-absolute top-0 end-0 m-2" id="translate-copy" hidden><?= e($P['copy']) ?></button>
                                </div>
                            </div>
                        </div>
                        <div class="form-actions mt-3">
                            <button type="submit" class="btn btn-primary form-submit" id="translate-btn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h10M9 3v2c0 4-3 7-6 8"/><path d="M6 8c1 3 4 6 8 7"/><path d="M13 21l4-9 4 9M14.6 17.5h4.8"/></svg>
                                <?= e($P['button']) ?>
                            </button>
                        </div>
                    </form>
                    <div id="translate-status" class="small mt-2"></div>
                </div>
                <p class="technical-admin-note technical-admin-footer"><?= e($P['footer']) ?></p>
            </section>
        </div>
    </div>
</div>
