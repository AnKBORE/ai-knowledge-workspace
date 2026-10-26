<?php

declare(strict_types=1);

/** @var string $title */
/** @var array<int, array<string, mixed>> $documents */
?>
<section class="dashboard-hero">
    <div class="dashboard-hero-copy">
        <p class="dashboard-eyebrow">Espace de connaissances <span aria-hidden="true">/</span> Assistant</p>
        <h1>Assistant de connaissances</h1>
        <p>Posez une question, générez un résumé ou rédigez une note — l'assistant s'appuie sur vos documents et cite ses sources.</p>
    </div>
    <div class="dashboard-hero-actions">
        <span class="dashboard-date"><?= e(date('d/m/Y')) ?></span>
    </div>
</section>

<div class="row g-4">
    <div class="col-lg-7">
        <section class="panel mb-4">
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">Question</h2>
                    <p class="panel-sub">Interrogez vos documents, l'assistant cite ses sources.</p>
                </div>
            </div>
            <div class="panel-body">
                <form id="ask-form">
                    <label for="question" class="form-label">Votre question</label>
                    <textarea id="question" class="form-control" rows="3" placeholder="Quels sont les principaux risques du dernier trimestre ?" required></textarea>
                    <button type="submit" class="btn btn-primary mt-3">Demander</button>
                </form>
                <div id="ask-error" class="alert alert-danger mt-3" hidden></div>
            </div>
        </section>

        <section class="panel mb-4">
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">Tâche agentique</h2>
                    <p class="panel-sub">Rédigez une note ou un rapport à partir de vos documents.</p>
                </div>
            </div>
            <div class="panel-body">
                <form id="task-form">
                    <textarea id="instruction" class="form-control" rows="3" placeholder="Ex. « Prépare une note pour le directeur sur les activités du trimestre »" required></textarea>
                    <button type="submit" class="btn btn-primary mt-3">Générer un brouillon</button>
                </form>
                <div id="task-error" class="alert alert-danger mt-3" hidden></div>
            </div>
        </section>
    </div>

    <div class="col-lg-5">
        <section class="panel mb-4">
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">Ajouter un document</h2>
                    <p class="panel-sub">Texte, PDF, image ou audio.</p>
                </div>
            </div>
            <div class="panel-body">
                <form id="upload-form" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="title" class="form-label">Titre</label>
                        <input id="title" name="title" class="form-control" placeholder="Rapport trimestriel" required>
                    </div>
                    <div class="mb-3">
                        <label for="file" class="form-label">Fichier (txt, md, pdf, image, audio)</label>
                        <input id="file" name="file" type="file" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-outline-primary">Indexer</button>
                </form>
                <div id="upload-status" class="small mt-2"></div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">Documents indexés</h2>
                    <p class="panel-sub"><?= count($documents) ?> document<?= count($documents) > 1 ? 's' : '' ?></p>
                </div>
            </div>
            <div class="panel-body">
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

<script>
(function () {
    function renderSources(listEl, sources) {
        listEl.innerHTML = '';
        if (!Array.isArray(sources) || sources.length === 0) {
            return false;
        }
        sources.forEach(function (source) {
            var li = document.createElement('li');
            var strong = document.createElement('strong');
            strong.textContent = source.title || 'Document';
            var span = document.createElement('span');
            span.className = 'text-muted d-block';
            span.textContent = source.excerpt || '';
            li.appendChild(strong);
            li.appendChild(span);
            listEl.appendChild(li);
        });
        return true;
    }

    function escapeThenBold(text) {
        var escaped = String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
        return escaped
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/\n/g, '<br>');
    }

    async function postJson(url, payload) {
        var response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        return response.json();
    }

    var resultModalEl = document.getElementById('result-modal');
    var resultModal = new bootstrap.Modal(resultModalEl);
    var resultBadge = document.getElementById('result-modal-badge');
    var resultTitle = document.getElementById('result-modal-title');
    var resultSub = document.getElementById('result-modal-sub');
    var resultContent = document.getElementById('result-content');
    var resultSources = document.getElementById('result-sources');
    var resultSourcesList = document.getElementById('result-sources-list');
    var currentResult = { title: 'Résultat', content: '' };

    function showLoading(badge, title, sub) {
        resultBadge.textContent = badge;
        resultTitle.textContent = title;
        resultSub.textContent = sub;
        resultContent.innerHTML = '<div class="d-flex align-items-center gap-2 text-muted">'
            + '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>'
            + '<span>Génération en cours…</span></div>';
        resultSources.hidden = true;
        resultModal.show();
    }

    function showResult(badge, title, sub, content, sources) {
        currentResult = { title: title, content: content };
        resultBadge.textContent = badge;
        resultTitle.textContent = title;
        resultSub.textContent = sub;
        resultContent.innerHTML = escapeThenBold(content);
        resultSources.hidden = !renderSources(resultSourcesList, sources);
    }

    function showError(message) {
        resultContent.innerHTML = '<div class="alert alert-danger mb-0">' + escapeThenBold(message) + '</div>';
        resultSources.hidden = true;
    }

    async function downloadResult(format) {
        try {
            var response = await fetch('<?= e(url('/api/export.php')) ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ title: currentResult.title, content: currentResult.content, format: format })
            });
            if (!response.ok) {
                throw new Error('export');
            }
            var blob = await response.blob();
            var url = URL.createObjectURL(blob);
            var link = document.createElement('a');
            link.href = url;
            link.download = currentResult.title.replace(/[^a-z0-9]+/gi, '-').toLowerCase() + '.' + format;
            document.body.appendChild(link);
            link.click();
            link.remove();
            URL.revokeObjectURL(url);
        } catch (err) {
            alert('Export impossible.');
        }
    }

    document.getElementById('download-pdf').addEventListener('click', function () { downloadResult('pdf'); });
    document.getElementById('download-docx').addEventListener('click', function () { downloadResult('docx'); });

    var askForm = document.getElementById('ask-form');
    var askError = document.getElementById('ask-error');

    askForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        askError.hidden = true;

        var question = document.getElementById('question').value.trim();
        if (!question) { return; }

        showLoading('Réponse', 'Réponse', 'Recherche dans vos documents…');
        try {
            var data = await postJson('<?= e(url('/api/ask.php')) ?>', { question: question, mode: 'answer' });
            if (data.error) {
                showError(data.error);
                return;
            }
            showResult('Réponse', 'Réponse', 'Réponse ancrée dans vos documents.', data.answer || '', data.sources);
        } catch (err) {
            showError('Erreur réseau. Vérifiez le serveur.');
        }
    });

    var taskForm = document.getElementById('task-form');
    var taskError = document.getElementById('task-error');

    taskForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        taskError.hidden = true;

        var instruction = document.getElementById('instruction').value.trim();
        if (!instruction) { return; }

        showLoading('Brouillon', 'Brouillon', 'Rédaction à partir de vos documents…');
        try {
            var data = await postJson('<?= e(url('/api/ask.php')) ?>', { instruction: instruction, mode: 'task' });
            if (data.error) {
                showError(data.error);
                return;
            }
            showResult('Brouillon', 'Brouillon', 'Brouillon prêt à être relu.', data.draft || '', data.sources);
        } catch (err) {
            showError('Erreur réseau. Vérifiez le serveur.');
        }
    });

    var uploadForm = document.getElementById('upload-form');
    var uploadStatus = document.getElementById('upload-status');

    uploadForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        uploadStatus.textContent = 'Envoi en cours…';
        uploadStatus.className = 'small text-muted mt-2';

        try {
            var response = await fetch('<?= e(url('/api/upload.php')) ?>', {
                method: 'POST',
                body: new FormData(uploadForm)
            });
            var data = await response.json();
            if (data.error) {
                uploadStatus.textContent = data.error;
                uploadStatus.className = 'small text-danger mt-2';
                return;
            }
            uploadStatus.textContent = 'Indexé : ' + data.title + ' (' + data.chunks + ' segments). Rechargez pour voir la liste.';
            uploadStatus.className = 'small text-success mt-2';
            uploadForm.reset();
        } catch (err) {
            uploadStatus.textContent = 'Erreur réseau.';
            uploadStatus.className = 'small text-danger mt-2';
        }
    });

    document.querySelectorAll('.summarize-btn').forEach(function (button) {
        button.addEventListener('click', async function () {
            var documentTitle = button.getAttribute('data-document-title') || 'Document';

            showLoading('Résumé', 'Résumé — ' + documentTitle, 'Synthèse du document…');
            try {
                var data = await postJson('<?= e(url('/api/summarize.php')) ?>', {
                    document_id: Number(button.getAttribute('data-document-id'))
                });
                if (data.error) {
                    showError(data.error);
                    return;
                }
                showResult('Résumé', 'Résumé — ' + documentTitle, 'Synthèse générée à partir du document.', data.summary || '', data.sources);
            } catch (err) {
                showError('Erreur réseau.');
            }
        });
    });
})();
</script>
