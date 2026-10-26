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

<div class="modal fade" id="result-modal" tabindex="-1" aria-labelledby="result-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5" id="result-modal-title">Résultat</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div id="result-content"></div>
                <div id="result-sources" class="mt-3" hidden>
                    <strong class="d-block mb-1">Sources</strong>
                    <ul id="result-sources-list" class="small mb-0"></ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" id="download-docx">Télécharger DOCX</button>
                <button type="button" class="btn btn-outline-secondary" id="download-pdf">Télécharger PDF</button>
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Fermer</button>
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
    var resultTitle = document.getElementById('result-modal-title');
    var resultContent = document.getElementById('result-content');
    var resultSources = document.getElementById('result-sources');
    var resultSourcesList = document.getElementById('result-sources-list');
    var currentResult = { title: 'Résultat', content: '' };

    function showResult(title, content, sources) {
        currentResult = { title: title, content: content };
        resultTitle.textContent = title;
        resultContent.innerHTML = escapeThenBold(content);
        resultSources.hidden = !renderSources(resultSourcesList, sources);
        resultModal.show();
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

        try {
            var data = await postJson('<?= e(url('/api/ask.php')) ?>', { question: question, mode: 'answer' });
            if (data.error) {
                askError.textContent = data.error;
                askError.hidden = false;
                return;
            }
            showResult('Réponse', data.answer || '', data.sources);
        } catch (err) {
            askError.textContent = 'Erreur réseau. Vérifiez le serveur.';
            askError.hidden = false;
        }
    });

    var taskForm = document.getElementById('task-form');
    var taskError = document.getElementById('task-error');

    taskForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        taskError.hidden = true;

        var instruction = document.getElementById('instruction').value.trim();
        if (!instruction) { return; }

        try {
            var data = await postJson('<?= e(url('/api/ask.php')) ?>', { instruction: instruction, mode: 'task' });
            if (data.error) {
                taskError.textContent = data.error;
                taskError.hidden = false;
                return;
            }
            showResult('Brouillon', data.draft || '', data.sources);
        } catch (err) {
            taskError.textContent = 'Erreur réseau. Vérifiez le serveur.';
            taskError.hidden = false;
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

            try {
                var data = await postJson('<?= e(url('/api/summarize.php')) ?>', {
                    document_id: Number(button.getAttribute('data-document-id'))
                });
                if (data.error) {
                    alert(data.error);
                    return;
                }
                showResult('Résumé — ' + documentTitle, data.summary || '', data.sources);
            } catch (err) {
                alert('Erreur réseau.');
            }
        });
    });
})();
</script>
