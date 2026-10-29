<?php
$S ??= app_i18n($lang ?? 'en')['shared'];
?>
<div class="modal fade doc-modal" id="result-modal" tabindex="-1" aria-labelledby="result-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary bg-gradient text-white border-0">
                <div>
                    <span class="badge text-bg-light mb-2" id="result-modal-badge"><?= e($S['result']) ?></span>
                    <h2 class="modal-title fs-5" id="result-modal-title"><?= e($S['result']) ?></h2>
                    <p class="small mb-0 opacity-75" id="result-modal-sub"><?= e($S['generated_from']) ?></p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="<?= e($S['close']) ?>"></button>
            </div>
            <div class="modal-body p-4">
                <div id="result-content"></div>
                <div id="result-sources" class="mt-4" hidden>
                    <p class="text-uppercase small fw-semibold text-muted mb-2"><?= e($S['sources']) ?></p>
                    <ul id="result-sources-list" class="small mb-0 ps-3"></ul>
                </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4">
                <button type="button" class="btn btn-outline-secondary btn-icon" data-bs-dismiss="modal">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    <?= e($S['close']) ?>
                </button>
                <button type="button" class="btn btn-outline-info btn-icon" id="retry-result">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-2.64-6.36M21 3v6h-6"/></svg>
                    <?= e($S['retry']) ?>
                </button>
                <button type="button" class="btn btn-outline-primary btn-icon" id="download-docx">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8"/></svg>
                    <?= e($S['download_docx']) ?>
                </button>
                <button type="button" class="btn btn-primary btn-icon" id="download-pdf">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12m0 0 4-4m-4 4-4-4"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
                    <?= e($S['download_pdf']) ?>
                </button>
            </div>
        </div>
    </div>
</div>
