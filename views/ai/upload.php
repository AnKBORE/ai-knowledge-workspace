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
                    <div class="recorder-actions" title="<?= e($P['recorder_sub']) ?>">
                        <button type="button" class="btn btn-outline-danger btn-icon" id="record-btn" aria-label="<?= e($P['recorder_start']) ?>" title="<?= e($P['recorder_title']) ?>">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 15a4 4 0 0 0 4-4V6a4 4 0 0 0-8 0v5a4 4 0 0 0 4 4Z"/><path d="M19 11a7 7 0 0 1-14 0M12 18v3"/></svg>
                        </button>
                        <button type="button" class="btn btn-danger btn-icon" id="record-stop" aria-label="<?= e($P['recorder_stop']) ?>" title="<?= e($P['recorder_stop']) ?>" hidden>
                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="7" y="7" width="10" height="10" rx="2"/></svg>
                        </button>
                        <span id="record-timer" class="fw-semibold text-danger"></span>
                        <span class="record-level-track" aria-hidden="true"><span id="record-level"></span></span>
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
                    <div id="record-status"></div>
                    <div id="upload-status" class="small mt-2"></div>
                </div>
                <p class="technical-admin-note technical-admin-footer"><?= e($P['footer']) ?></p>
            </section>
        </div>
    </div>
</div>

<!-- Live transcription modal (meeting recorder) -->
<div class="modal fade doc-modal" id="live-rec-modal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content ltr-modal">
            <div class="modal-header ltr-head">
                <div class="ltr-mic-badge" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 0 0-3 3v6a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/><path d="M19 10v1a7 7 0 0 1-14 0v-1"/><path d="M12 18v4"/></svg>
                </div>
                <div class="ltr-head-text">
                    <p class="ltr-kicker"><?= e(($isFr ?? true) ? 'Transcription en direct' : 'Live transcription') ?></p>
                    <h5 class="ltr-title" id="live-rec-title"><?= e($P['recorder_title'] ?? 'Recording') ?></h5>
                </div>
                <div class="ltr-eq" aria-hidden="true"><span></span><span></span><span></span><span></span><span></span></div>
                <button type="button" class="btn-close btn-close-white ltr-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body ltr-body">
                <div class="ltr-statusbar">
                    <span class="ltr-rec-badge"><span class="live-rec-dot" aria-hidden="true"></span>REC</span>
                    <span id="live-rec-timer" class="ltr-timer">00:00</span>
                    <select id="live-rec-from" class="form-select form-select-sm ltr-lang" aria-label="<?= e(($isFr ?? true) ? 'Langue de l\'audio' : 'Audio language') ?>">
                        <option value=""><?= e(($isFr ?? true) ? 'Audio : auto' : 'Audio: auto') ?></option>
                        <option value="mo">Mooré</option>
                        <option value="fr">Français</option>
                        <option value="en">English</option>
                    </select>
                    <select id="live-rec-lang" class="form-select form-select-sm ltr-lang" aria-label="<?= e($P['translate_to'] ?? 'Translate to') ?>">
                        <option value=""><?= e(($isFr ?? true) ? 'Transcription brute' : 'Raw transcript') ?></option>
                        <option value="fr">Français</option>
                        <option value="en">English</option>
                        <option value="mo">Mooré</option>
                    </select>
                    <span id="live-rec-state" class="ltr-state"></span>
                </div>
                <div id="live-rec-text" class="live-rec-transcript ltr-transcript" aria-live="polite"></div>
            </div>
            <div class="modal-footer ltr-footer">
                <button type="button" class="ltr-btn ltr-btn-ghost" id="live-rec-pause" title="<?= e(($isFr ?? true) ? 'Pause' : 'Pause') ?>" aria-label="Pause">
                    <svg id="live-rec-pause-ic" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 4H6v16h4zM18 4h-4v16h4z"/></svg>
                </button>
                <button type="button" class="ltr-btn ltr-btn-stop" id="live-rec-stop" title="<?= e(($isFr ?? true) ? 'Arrêter' : 'Stop') ?>" aria-label="Stop">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><rect x="6" y="6" width="12" height="12" rx="2"/></svg>
                </button>
            </div>
        </div>
    </div>
</div>
