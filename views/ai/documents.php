<?php

declare(strict_types=1);

/** @var array<int, array<string, mixed>> $documents */
$search = (string) ($search ?? '');
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
            <section class="panel space-list-panel technical-admin-panel" aria-labelledby="documents-title">
                <div class="panel-head">
                    <div>
                        <p class="page-eyebrow">Base de connaissances</p>
                        <h2 class="panel-title" id="documents-title">Documents <span class="technical-admin-count"><?= count($documents) ?></span></h2>
                        <p class="panel-sub" id="documents-help">Recherche, tri et pagination par lots de 50. Chaque dépôt est versionné (SHA-256) et journalisé.</p>
                    </div>
                    <form class="doc-filter" method="get" action="<?= e(url('/documents')) ?>" role="search">
                        <label class="visually-hidden" for="filtre-categorie">Filtrer par catégorie</label>
                        <select id="filtre-categorie" name="categorie" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Toutes les catégories</option>
                            <?php foreach (($categories ?? []) as $categorie): ?>
                                <option value="<?= (int) ($categorie['id'] ?? 0) ?>" <?= ((int) ($filtreCategorie ?? 0) === (int) ($categorie['id'] ?? 0)) ? 'selected' : '' ?>>
                                    <?= e((string) ($categorie['nom'] ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>

                <?php if (empty($documents)): ?>
                    <p class="technical-admin-empty">
                        <?= $search !== '' ? 'Aucun document ne correspond à « ' . e($search) . ' ».' : 'Aucun document : ajoutez un premier fichier pour commencer.' ?>
                    </p>
                <?php else: ?>
                    <div class="space-table-wrap">
                        <table class="spaces-data-table technical-admin-table documents-table table table-hover align-middle w-100" id="documents-table" aria-describedby="documents-help">
                            <thead>
                                <tr>
                                    <th scope="col">N°</th>
                                    <th scope="col">Titre</th>
                                    <th scope="col">Catégorie</th>
                                    <th scope="col">Fichier</th>
                                    <th scope="col">Versions</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($documents as $index => $doc): $docId = (int) ($doc['id'] ?? 0); $docVersions = $versions[$docId] ?? []; ?>
                                    <tr>
                                        <td class="table-row-number dtr-control">
                                            <button class="dtr-control-btn" type="button" aria-label="Afficher les informations complémentaires" aria-expanded="false"><span aria-hidden="true">+</span></button>
                                            <span class="row-number-value"><?= (int) $index + 1 ?></span>
                                        </td>
                                        <td><strong><?= e($doc['title'] ?? '') ?></strong></td>
                                        <td>
                                            <?php if (($doc['categorie_nom'] ?? null) !== null): ?>
                                                <span class="categorie-badge" style="border-color: <?= e((string) $doc['categorie_couleur']) ?>; color: <?= e((string) $doc['categorie_couleur']) ?>"><?= e((string) $doc['categorie_nom']) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted small">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small text-muted"><?= e($doc['filename'] ?? '') ?></td>
                                        <td><span class="doc-item-badge"><?= (int) ($doc['versions'] ?? 0) ?> version<?= ((int) ($doc['versions'] ?? 0)) > 1 ? 's' : '' ?></span></td>
                                        <td>
                                            <div class="doc-item-actions">
                                                <a class="doc-action" title="Télécharger la dernière version"
                                                   href="<?= e(url('/api/download_document.php?document_id=' . $docId)) ?>"
                                                   aria-label="Télécharger <?= e($doc['title'] ?? 'le document') ?>">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12M7 10l5 5 5-5M5 21h14"/></svg>
                                                </a>
                                                <button type="button" class="doc-action summarize-btn" title="Résumer"
                                                        data-document-id="<?= $docId ?>"
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
                                                        data-document-id="<?= $docId ?>"
                                                        aria-label="Supprimer <?= e($doc['title'] ?? 'le document') ?>">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M6 7l1 13a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-13"/><path d="M10 11v6M14 11v6"/></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <p class="technical-admin-note technical-admin-footer">Chaque document est stocké hors racine web, haché en SHA-256 et versionné ; les dépôts et consultations sont journalisés.</p>
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

<link rel="stylesheet" href="<?= e(url('/assets/vendor/datatables/css/dataTables.bootstrap5.min.css')) ?>">
<link rel="stylesheet" href="<?= e(url('/assets/vendor/datatables/css/responsive.bootstrap5.min.css')) ?>">
<link rel="stylesheet" href="<?= e(url('/assets/css/spaces.css')) ?>">
<script src="<?= e(url('/assets/vendor/jquery/jquery.min.js')) ?>"></script>
<script src="<?= e(url('/assets/vendor/datatables/js/dataTables.min.js')) ?>"></script>
<script src="<?= e(url('/assets/vendor/datatables/js/dataTables.bootstrap5.min.js')) ?>"></script>
<script src="<?= e(url('/assets/vendor/datatables/js/dataTables.responsive.min.js')) ?>"></script>
<script src="<?= e(url('/assets/vendor/datatables/js/responsive.bootstrap5.min.js')) ?>"></script>
<script src="<?= e(url('/assets/js/spaces-tables.js')) ?>"></script>
