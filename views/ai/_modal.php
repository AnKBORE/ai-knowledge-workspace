<div class="modal fade doc-modal" id="result-modal" tabindex="-1" aria-labelledby="result-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary bg-gradient text-white border-0">
                <div>
                    <span class="badge text-bg-light mb-2" id="result-modal-badge">Résultat</span>
                    <h2 class="modal-title fs-5" id="result-modal-title">Résultat</h2>
                    <p class="small mb-0 opacity-75" id="result-modal-sub">Généré à partir de vos documents.</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body p-4">
                <div id="result-content"></div>
                <div id="result-sources" class="mt-4" hidden>
                    <p class="text-uppercase small fw-semibold text-muted mb-2">Sources</p>
                    <ul id="result-sources-list" class="small mb-0 ps-3"></ul>
                </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-outline-primary" id="download-docx">Télécharger DOCX</button>
                <button type="button" class="btn btn-primary" id="download-pdf">Télécharger PDF</button>
            </div>
        </div>
    </div>
</div>
