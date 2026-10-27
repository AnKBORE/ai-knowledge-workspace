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
    <div class="col-12">
        <div class="space-admin">
        <section class="panel space-list-panel technical-admin-panel">
            <div class="panel-head">
                <div>
                    <p class="page-eyebrow">Base de connaissances</p>
                    <h2 class="panel-title">Documents</h2>
                    <p class="panel-sub" id="documents-count"><?= count($documents) ?> document<?= count($documents) > 1 ? 's' : '' ?><?= ($search ?? '') !== '' ? ' · recherche « ' . e($search) . ' »' : '' ?></p>
                </div>
                <form class="doc-search" method="get" action="<?= e(url('/documents')) ?>" role="search">
                    <input type="search" name="q" class="form-control form-control-sm" placeholder="Rechercher un document…" value="<?= e($search ?? '') ?>" aria-label="Rechercher un document">
                    <button class="btn btn-sm btn-primary" type="submit">Rechercher</button>
                    <?php if (($search ?? '') !== ''): ?>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/documents')) ?>">Effacer</a>
                    <?php endif; ?>
                </form>
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
                    <?php foreach ($documents as $doc): $docId = (int) ($doc['id'] ?? 0); $docVersions = $versions[$docId] ?? []; ?>
                        <div class="doc-item">
                            <span class="doc-item-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9Z"/><path d="M14 3v6h6"/></svg>
                            </span>
                            <div class="doc-item-body">
                                <div class="doc-item-title"><?= e($doc['title'] ?? '') ?></div>
                                <div class="doc-item-meta">
                                    <span class="doc-item-file"><?= e($doc['filename'] ?? '') ?></span>
                                    <span class="doc-item-badge"><?= (int) ($doc['versions'] ?? 0) ?> version<?= ((int) ($doc['versions'] ?? 0)) > 1 ? 's' : '' ?></span>
                                </div>
                            </div>
                            <div class="doc-item-actions">
                                <a class="doc-action" title="Télécharger la dernière version"
                                   href="<?= e(url('/api/download_document.php?document_id=' . $docId)) ?>"
                                   aria-label="Télécharger <?= e($doc['title'] ?? 'le document') ?>">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12M7 10l5 5 5-5M5 21h14"/></svg>
                                </a>
                                <button type="button" class="doc-action summarize-btn" title="Résumer"
                                        data-document-id="<?= (int) ($doc['id'] ?? 0) ?>"
                                        data-document-title="<?= e($doc['title'] ?? 'Document') ?>"
                                        aria-label="Résumer <?= e($doc['title'] ?? 'le document') ?>">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v3M12 18v3M3 12h3M18 12h3M5.6 5.6l2.1 2.1M16.3 16.3l2.1 2.1M18.4 5.6l-2.1 2.1M7.7 16.3l-2.1 2.1"/></svg>
                                </button>
                                <?php if ($docVersions !== []): ?>
                                    <button type="button" class="doc-action" title="Historique des versions"
                                            data-bs-toggle="modal" data-bs-target="#versions-<?= $docId ?>"
                                            aria-label="Historique des versions de <?= e($doc['title'] ?? 'ce document') ?>">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                                    </button>
                                <?php endif; ?>
                                <button type="button" class="doc-action doc-action-danger delete-btn" title="Supprimer"
                                        data-document-id="<?= (int) ($doc['id'] ?? 0) ?>"
                                        aria-label="Supprimer <?= e($doc['title'] ?? 'le document') ?>">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M6 7l1 13a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-13"/><path d="M10 11v6M14 11v6"/></svg>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php if (($totalPages ?? 1) > 1): ?>
                <nav class="d-flex justify-content-between align-items-center px-4 pb-4" aria-label="Pagination des documents">
                    <a class="btn btn-sm btn-outline-secondary <?= ($pageNum ?? 1) <= 1 ? 'disabled' : '' ?>"
                       href="<?= e(url('/documents?page=' . max(1, ($pageNum ?? 1) - 1))) ?>">Précédent</a>
                    <span class="small text-muted">Page <?= (int) ($pageNum ?? 1) ?> / <?= (int) $totalPages ?> · <?= (int) ($totalDocuments ?? 0) ?> document(s)</span>
                    <a class="btn btn-sm btn-outline-secondary <?= ($pageNum ?? 1) >= $totalPages ? 'disabled' : '' ?>"
                       href="<?= e(url('/documents?page=' . min($totalPages, ($pageNum ?? 1) + 1))) ?>">Suivant</a>
                </nav>
            <?php endif; ?>
        </section>
        </div>
    </div>
</div>

<?php foreach ($documents as $doc): $docId = (int) ($doc['id'] ?? 0); $docVersions = $versions[$docId] ?? []; ?>
    <?php if ($docVersions !== []): ?>
        <div class="modal fade doc-modal" id="versions-<?= $docId ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-primary bg-gradient text-white border-0">
                        <div>
                            <span class="badge text-bg-light mb-2">Historique des versions</span>
                            <h2 class="modal-title fs-5"><?= e($doc['title'] ?? '') ?></h2>
                            <p class="small mb-0 opacity-75"><?= count($docVersions) ?> version<?= count($docVersions) > 1 ? 's' : '' ?> immuable<?= count($docVersions) > 1 ? 's' : '' ?> · chaque dépôt est daté et signé.</p>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body p-0">
                        <ul class="list-group list-group-flush">
                            <?php foreach ($docVersions as $version): ?>
                                <li class="list-group-item px-4 py-3">
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <span class="badge text-bg-primary">v<?= (int) ($version['numero'] ?? 0) ?></span>
                                        <strong class="text-break flex-grow-1"><?= e($version['nom_original'] ?? '') ?></strong>
                                    </div>
                                    <small class="text-muted d-block mt-1">
                                        <?= e($version['cree_le'] ?? '') ?> ·
                                        <?= (int) ($version['taille_octets'] ?? 0) ?> octets ·
                                        SHA-256 : <code><?= e(substr((string) ($version['empreinte'] ?? ''), 0, 16)) ?>…</code>
                                    </small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endforeach; ?>

<?php require __DIR__ . '/_modal.php'; ?>
