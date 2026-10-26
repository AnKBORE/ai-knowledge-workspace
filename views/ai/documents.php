<?php

declare(strict_types=1);

/** @var array<int, array<string, mixed>> $documents */
?>
<section class="dashboard-hero">
    <div class="dashboard-hero-copy">
        <p class="dashboard-eyebrow">Espace de connaissances <span aria-hidden="true">/</span> Documents indexés</p>
        <h1>Documents indexés</h1>
        <p>Retrouvez vos documents, lancez un résumé en un clic et vérifiez ce que contient votre base de connaissances.</p>
    </div>
    <div class="dashboard-hero-actions">
        <span class="dashboard-date"><?= e(date('d/m/Y')) ?></span>
    </div>
</section>

<div class="row g-4">
    <div class="col-lg-8 mx-auto">
        <section class="panel">
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">Documents</h2>
                    <p class="panel-sub" id="documents-count"><?= count($documents) ?> document<?= count($documents) > 1 ? 's' : '' ?></p>
                </div>
            </div>
            <div class="panel-body" id="documents-list">
                <?php if (empty($documents)): ?>
                    <div class="dashboard-empty">
                        <div class="dashboard-empty-copy">
                            <p class="dashboard-empty-label">Aucun document</p>
                            <p>Ajoutez un premier document pour commencer.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($documents as $doc): ?>
                        <div class="dashboard-document-row">
                            <span class="doc-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9Z"/><path d="M14 3v6h6"/></svg>
                            </span>
                            <div class="dashboard-document-copy">
                                <strong><?= e($doc['title'] ?? '') ?></strong>
                                <span><?= e($doc['filename'] ?? '') ?></span>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary summarize-btn"
                                    data-document-id="<?= (int) ($doc['id'] ?? 0) ?>"
                                    data-document-title="<?= e($doc['title'] ?? 'Document') ?>">Résumer</button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>

<?php require __DIR__ . '/_modal.php'; ?>
