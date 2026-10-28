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
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= e($S['close']) ?></button>
                <button type="button" class="btn btn-outline-primary" id="download-docx"><?= e($S['download_docx']) ?></button>
                <button type="button" class="btn btn-primary" id="download-pdf"><?= e($S['download_pdf']) ?></button>
            </div>
        </div>
    </div>
</div>
