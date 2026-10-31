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
                    <form id="translate-form" class="translate-form">
                        <div class="translate-langs">
                            <div class="translate-lang">
                                <label for="translate-from" class="form-label"><?= e($P['from_label']) ?></label>
                                <select id="translate-from" class="form-select">
                                    <?php foreach ($langs as $code => $label): ?>
                                        <option value="<?= e($code) ?>" <?= $code === 'en' ? 'selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="button" class="btn translate-swap" id="translate-swap" title="<?= e($P['swap']) ?>" aria-label="<?= e($P['swap']) ?>">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 16V4m0 0L3 8m4-4 4 4"/><path d="M17 8v12m0 0 4-4m-4 4-4-4"/></svg>
                            </button>
                            <div class="translate-lang">
                                <label for="translate-to" class="form-label"><?= e($P['to_label']) ?></label>
                                <select id="translate-to" class="form-select">
                                    <?php foreach ($langs as $code => $label): ?>
                                        <option value="<?= e($code) ?>" <?= $code === 'mo' ? 'selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="translate-panes">
                            <div class="translate-pane">
                                <div class="translate-pane-head">
                                    <label for="translate-source" class="form-label"><?= e($P['source_label']) ?></label>
                                </div>
                                <textarea id="translate-source" class="form-control" rows="8" placeholder="<?= e($P['source_placeholder']) ?>" required></textarea>
                            </div>
                            <div class="translate-pane">
                                <div class="translate-pane-head">
                                    <label for="translate-result" class="form-label"><?= e($P['target_label']) ?></label>
                                    <button type="button" class="btn btn-outline-secondary translate-copy" id="translate-copy" title="<?= e($P['copy']) ?>" aria-label="<?= e($P['copy']) ?>" hidden>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg>
                                    </button>
                                </div>
                                <textarea id="translate-result" class="form-control" rows="8" readonly></textarea>
                            </div>
                        </div>

                        <div class="translate-cta">
                            <button type="submit" class="btn btn-primary form-submit" id="translate-btn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 5h10M9 3v2c0 4-3 7-6 8"/><path d="M6 8c1 3 4 6 8 7"/><path d="M13 21l4-9 4 9M14.6 17.5h4.8"/></svg>
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
