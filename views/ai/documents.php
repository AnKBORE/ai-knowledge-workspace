<?php

declare(strict_types=1);

/** @var array<int, array<string, mixed>> $documents */
/** @var string $lang */
$T = app_i18n($lang ?? 'en');
$S = $T['shared'];
$P = $T['documents'];
$search = (string) ($search ?? '');
$versionsLabel = static fn (int $n): string => $n . ' ' . $P[$n > 1 ? 'versions' : 'version'];
?>
<section class="dashboard-hero">
    <div class="dashboard-hero-copy">
        <p class="dashboard-eyebrow"><?= e($S['eyebrow_root']) ?> <span aria-hidden="true">/</span> <?= e($T['pages']['/documents']) ?></p>
        <h1><?= e($T['pages']['/documents']) ?></h1>
        <p><?= e($P['hero_sub']) ?></p>
    </div>
    <div class="dashboard-hero-actions">
        <span class="dashboard-date"><?= e(lang_date($lang ?? 'en')) ?></span>
    </div>
</section>

<div class="row g-4">
    <div class="col-12">
        <div class="space-admin">
            <section class="panel space-list-panel technical-admin-panel panel-accent-teal" aria-labelledby="documents-title">
                <div class="panel-head">
                    <div>
                        <p class="page-eyebrow"><?= e($P['eyebrow']) ?></p>
                        <h2 class="panel-title" id="documents-title"><?= e($P['panel_title']) ?> <span class="technical-admin-count"><?= count($documents) ?></span></h2>
                        <p class="panel-sub" id="documents-help"><?= e($P['panel_sub']) ?></p>
                    </div>
                </div>

                <?php if (empty($documents)): ?>
                    <p class="technical-admin-empty">
                        <?= $search !== '' ? e(sprintf($P['empty_search'], $search)) : e($P['empty']) ?>
                    </p>
                <?php else: ?>
                    <div class="space-table-wrap">
                        <table class="spaces-data-table technical-admin-table documents-table table table-hover align-middle w-100" id="documents-table" aria-describedby="documents-help">
                            <thead>
                                <tr>
                                    <th scope="col">N°</th>
                                    <th scope="col"><?= e($P['th_title']) ?></th>
                                    <th scope="col"><?= e($P['th_category']) ?></th>
                                    <th scope="col"><?= e($P['th_file']) ?></th>
                                    <th scope="col"><?= e($P['th_versions']) ?></th>
                                    <th scope="col"><?= e($P['th_actions']) ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($documents as $index => $doc): $docId = (int) ($doc['id'] ?? 0); $docVersions = $versions[$docId] ?? [];
                                    $docMime = (string) ($doc['mime_type'] ?? '');
                                    $docExt = strtolower((string) pathinfo((string) ($doc['filename'] ?? ''), PATHINFO_EXTENSION));
                                    $docIsAudio = str_starts_with($docMime, 'audio/')
                                        || in_array($docExt, ['wav', 'mp3', 'm4a', 'ogg', 'oga', 'webm', 'flac', 'aac'], true); ?>
                                    <tr>
                                        <td class="table-row-number dtr-control">
                                            <button class="dtr-control-btn" type="button" aria-label="<?= e($P['expand_row']) ?>" aria-expanded="false"><span aria-hidden="true">+</span></button>
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
                                        <td><span class="doc-item-badge"><?= e($versionsLabel((int) ($doc['versions'] ?? 0))) ?></span></td>
                                        <td>
                                            <div class="doc-item-actions">
                                                <?php if ($docIsAudio): ?>
                                                    <button type="button" class="doc-action audio-play-btn" title="<?= e($P['play_title']) ?>"
                                                            data-bs-toggle="modal" data-bs-target="#doc-audio-modal"
                                                            data-document-id="<?= $docId ?>"
                                                            data-document-title="<?= e((string) ($doc['title'] ?? '')) ?>"
                                                            aria-label="<?= e($P['play_title']) ?>">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 5 6 9H3v6h3l5 4V5Z"/><path d="M15.5 8.5a5 5 0 0 1 0 7M18.5 5.5a9 9 0 0 1 0 13"/></svg>
                                                    </button>
                                                <?php endif; ?>
                                                <a class="doc-action doc-confirm" title="<?= e($P['dl_title']) ?>"
                                                   href="<?= e(url('/api/download_document.php?document_id=' . $docId)) ?>"
                                                   data-confirm-action="download"
                                                   data-confirm-title="<?= e($P['dl_confirm_title']) ?>"
                                                   data-confirm-text="<?= e(sprintf($P['dl_confirm_text'], (string) ($doc['title'] ?? ''))) ?>"
                                                   aria-label="<?= e($P['dl_title']) ?>">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12M7 10l5 5 5-5M5 21h14"/></svg>
                                                </a>
                                                <button type="button" class="doc-action summarize-btn doc-confirm" title="<?= e($P['sum_title']) ?>"
                                                        data-document-id="<?= $docId ?>"
                                                        data-document-title="<?= e($doc['title'] ?? 'Document') ?>"
                                                        data-confirm-action="summarize"
                                                        data-confirm-title="<?= e($P['sum_confirm_title']) ?>"
                                                        data-confirm-text="<?= e(sprintf($P['sum_confirm_text'], (string) ($doc['title'] ?? ''))) ?>"
                                                        aria-label="<?= e($P['sum_title']) ?>">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v3M12 18v3M3 12h3M18 12h3M5.6 5.6l2.1 2.1M16.3 16.3l2.1 2.1M18.4 5.6l-2.1 2.1M7.7 16.3l-2.1 2.1"/></svg>
                                                </button>
                                                <?php if ($docVersions !== []): ?>
                                                    <button type="button" class="doc-action" title="<?= e($P['versions_title']) ?>"
                                                            data-bs-toggle="modal" data-bs-target="#versions-<?= $docId ?>"
                                                            aria-label="<?= e($P['versions_title']) ?>">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                                                    </button>
                                                <?php endif; ?>
                                                <button type="button" class="doc-action doc-action-danger delete-btn" title="<?= e($P['delete_title']) ?>"
                                                        data-document-id="<?= $docId ?>"
                                                        data-document-title="<?= e((string) ($doc['title'] ?? '')) ?>"
                                                        aria-label="<?= e($P['delete_title']) ?>">
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

                <p class="technical-admin-note technical-admin-footer"><?= e($P['footer']) ?></p>
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
                            <span class="badge text-bg-light mb-2"><?= e($P['versions_title']) ?></span>
                            <h2 class="modal-title fs-5"><?= e($doc['title'] ?? '') ?></h2>
                            <p class="small mb-0 opacity-75"><?= e(sprintf($P['versions_sub'], count($docVersions), count($docVersions) > 1 ? 's' : '', count($docVersions) > 1 ? 's' : '')) ?></p>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="<?= e($S['close']) ?>"></button>
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
                                        <?= (int) ($version['taille_octets'] ?? 0) ?> <?= e($P['bytes']) ?> ·
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

<div class="modal fade space-create-modal profil-modal" id="doc-audio-modal" tabindex="-1" aria-labelledby="doc-audio-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered profil-modal-dialog">
        <div class="modal-content">
            <div class="space-form">
                <div class="modal-header space-create-header text-bg-primary border-bottom border-2 border-primary">
                    <span class="space-create-head-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M11 5 6 9H3v6h3l5 4V5Z"/><path d="M15.5 8.5a5 5 0 0 1 0 7M18.5 5.5a9 9 0 0 1 0 13"/></svg>
                    </span>
                    <div class="space-create-head-copy">
                        <p class="space-modal-eyebrow"><?= e($P['play_modal_title']) ?></p>
                        <h2 class="modal-title" id="doc-audio-title"><?= e($P['play_modal_title']) ?></h2>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="<?= e($S['close']) ?>"></button>
                </div>
                <div class="modal-body space-create-body">
                    <audio id="doc-audio-player" controls preload="metadata" class="w-100"></audio>
                    <div class="doc-live-translate mt-3">
                        <div class="lt-bar mb-2">
                            <div class="lt-field">
                                <label for="doc-audio-from"><?= e($P['audio_in'] ?? 'Audio in') ?></label>
                                <select id="doc-audio-from" class="form-select">
                                    <option value="">Auto</option>
                                    <option value="mo">Mooré</option>
                                    <option value="fr">Français</option>
                                    <option value="en">English</option>
                                </select>
                            </div>
                            <div class="lt-swap" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3l4 4-4 4"/><path d="M21 7H8a4 4 0 0 0-4 4v1"/><path d="m7 21-4-4 4-4"/><path d="M3 17h13a4 4 0 0 0 4-4v-1"/></svg>
                            </div>
                            <div class="lt-field">
                                <label for="doc-audio-lang"><?= e($P['translate_to']) ?></label>
                                <select id="doc-audio-lang" class="form-select">
                                    <option value="">—</option>
                                    <option value="fr">Français</option>
                                    <option value="en">English</option>
                                    <option value="mo">Mooré</option>
                                </select>
                            </div>
                            <div class="lt-actions lt-disabled" id="doc-audio-exports">
                                <button type="button" class="btn btn-sm btn-outline-danger btn-icon doc-audio-export" data-format="pdf" title="<?= e($S['download_pdf'] ?? 'PDF') ?>" aria-label="<?= e($S['download_pdf'] ?? 'PDF') ?>">
                                    <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 13h2a1.5 1.5 0 0 0 0-3H9v6"/><path d="M13 10v6h1.5a2 2 0 0 0 0-6z"/><path d="M17.2 16h1.6"/><path d="M17.2 10v6"/></svg>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary btn-icon doc-audio-export" data-format="docx" title="<?= e($S['download_docx'] ?? 'DOCX') ?>" aria-label="<?= e($S['download_docx'] ?? 'DOCX') ?>">
                                    <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="m8 11 2 8 2-5 2 5 2-8"/></svg>
                                </button>
                            </div>
                        </div>
                        <div id="doc-audio-translate-status" class="small text-muted"></div>
                        <div id="doc-audio-translation" class="doc-live-translation" hidden></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade space-create-modal profil-modal" id="doc-delete-modal" tabindex="-1" aria-labelledby="doc-delete-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered profil-modal-dialog">
        <div class="modal-content">
            <form class="space-form" id="doc-delete-form">
                <div class="modal-header space-create-header text-bg-danger border-bottom border-2 border-danger">
                    <span class="space-create-head-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M6 7l1 13a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-13"/><path d="M10 11v6M14 11v6"/></svg>
                    </span>
                    <div class="space-create-head-copy">
                        <p class="space-modal-eyebrow"><?= e($P['delete_eyebrow']) ?></p>
                        <h2 class="modal-title" id="doc-delete-title"><?= e($P['delete_modal_title']) ?></h2>
                        <p id="doc-delete-name"><?= e($P['delete_modal_hint']) ?></p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="<?= e($S['close']) ?>"></button>
                </div>
                <div class="modal-body space-create-body">
                    <div class="space-create-fields">
                        <label class="profil-field-full"><?= e($S['password_label']) ?>
                            <input id="doc-delete-password" type="password" required autocomplete="current-password" placeholder="<?= e($P['delete_password_ph']) ?>">
                        </label>
                    </div>
                    <p id="doc-delete-error" class="doc-delete-error" hidden></p>
                </div>
                <div class="modal-footer space-create-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= e($S['cancel']) ?></button>
                    <button type="submit" class="btn btn-danger space-create-submit" id="doc-delete-submit">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M6 7l1 13a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-13"/><path d="M10 11v6M14 11v6"/></svg>
                        <?= e($P['delete_btn']) ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade space-create-modal profil-modal" id="doc-confirm-modal" tabindex="-1" aria-labelledby="doc-confirm-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered profil-modal-dialog">
        <div class="modal-content">
            <div class="space-form">
                <div class="modal-header space-create-header text-bg-primary border-bottom border-2 border-primary">
                    <span class="space-create-head-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.01"/></svg>
                    </span>
                    <div class="space-create-head-copy">
                        <p class="space-modal-eyebrow"><?= e($P['confirm_eyebrow']) ?></p>
                        <h2 class="modal-title" id="doc-confirm-title"><?= e($P['confirm_title']) ?></h2>
                        <p id="doc-confirm-text"><?= e($P['confirm_text']) ?></p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="<?= e($S['close']) ?>"></button>
                </div>
                <div class="modal-footer space-create-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= e($S['cancel']) ?></button>
                    <button type="button" class="btn btn-primary space-create-submit" id="doc-confirm-btn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                        <?= e($S['confirm']) ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

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
