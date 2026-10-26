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
                <form id="ask-form" class="mb-3">
                    <label for="question" class="form-label">Votre question</label>
                    <textarea id="question" class="form-control" rows="3" placeholder="Quels sont les principaux risques du dernier trimestre ?" required></textarea>
                    <button type="submit" class="btn btn-primary mt-3">Demander</button>
                </form>

                <div id="answer" class="border rounded-3 p-3 bg-light" hidden>
                    <strong class="d-block mb-2">Réponse</strong>
                    <div id="answer-text" class="mb-0"></div>
                    <div id="answer-sources" class="mt-3" hidden>
                        <strong class="d-block mb-1">Sources</strong>
                        <ul id="answer-sources-list" class="small mb-0"></ul>
                    </div>
                </div>
                <div id="answer-error" class="alert alert-danger mt-3" hidden></div>
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

                <div id="draft" class="border rounded-3 p-3 bg-light mt-3" hidden>
                    <strong class="d-block mb-2">Brouillon</strong>
                    <div id="draft-text" class="mb-0"></div>
                    <div id="draft-sources" class="mt-3" hidden>
                        <strong class="d-block mb-1">Sources</strong>
                        <ul id="draft-sources-list" class="small mb-0"></ul>
                    </div>
                </div>
                <div id="task-error" class="alert alert-danger mt-3" hidden></div>
            </div>
        </section>

        <section id="summary-card" class="panel" hidden>
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">Résumé</h2>
                    <p class="panel-sub">Synthèse du document sélectionné.</p>
                </div>
            </div>
            <div class="panel-body">
                <div id="summary-text"></div>
                <div id="summary-sources" class="mt-2 small" hidden>
                    <strong class="d-block mb-1">Sources</strong>
                    <ul id="summary-sources-list" class="mb-0"></ul>
                </div>
                <div id="summary-error" class="alert alert-danger mt-2" hidden></div>
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
                                    data-document-id="<?= (int) ($doc['id'] ?? 0) ?>">Résumer</button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
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

    var askForm = document.getElementById('ask-form');
    var answer = document.getElementById('answer');
    var answerText = document.getElementById('answer-text');
    var answerSources = document.getElementById('answer-sources');
    var answerSourcesList = document.getElementById('answer-sources-list');
    var answerError = document.getElementById('answer-error');

    askForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        answer.hidden = true;
        answerError.hidden = true;

        var question = document.getElementById('question').value.trim();
        if (!question) { return; }

        try {
            var data = await postJson('<?= e(url('/api/ask.php')) ?>', { question: question, mode: 'answer' });
            if (data.error) {
                answerError.textContent = data.error;
                answerError.hidden = false;
                return;
            }
            answerText.innerHTML = escapeThenBold(data.answer || '');
            answerSources.hidden = !renderSources(answerSourcesList, data.sources);
            answer.hidden = false;
        } catch (err) {
            answerError.textContent = 'Erreur réseau. Vérifiez le serveur.';
            answerError.hidden = false;
        }
    });

    var taskForm = document.getElementById('task-form');
    var draft = document.getElementById('draft');
    var draftText = document.getElementById('draft-text');
    var draftSources = document.getElementById('draft-sources');
    var draftSourcesList = document.getElementById('draft-sources-list');
    var taskError = document.getElementById('task-error');

    taskForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        draft.hidden = true;
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
            draftText.innerHTML = escapeThenBold(data.draft || '');
            draftSources.hidden = !renderSources(draftSourcesList, data.sources);
            draft.hidden = false;
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

    var summaryCard = document.getElementById('summary-card');
    var summaryText = document.getElementById('summary-text');
    var summarySources = document.getElementById('summary-sources');
    var summarySourcesList = document.getElementById('summary-sources-list');
    var summaryError = document.getElementById('summary-error');

    document.querySelectorAll('.summarize-btn').forEach(function (button) {
        button.addEventListener('click', async function () {
            summaryCard.hidden = false;
            summaryError.hidden = true;
            summaryText.textContent = 'Résumé en cours…';

            try {
                var data = await postJson('<?= e(url('/api/summarize.php')) ?>', {
                    document_id: Number(button.getAttribute('data-document-id'))
                });
                if (data.error) {
                    summaryError.textContent = data.error;
                    summaryError.hidden = false;
                    return;
                }
                summaryText.innerHTML = escapeThenBold(data.summary || '');
                summarySources.hidden = !renderSources(summarySourcesList, data.sources);
            } catch (err) {
                summaryError.textContent = 'Erreur réseau.';
                summaryError.hidden = false;
            }
        });
    });
})();
</script>
