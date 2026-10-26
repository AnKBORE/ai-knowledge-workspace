<?php

declare(strict_types=1);

/** @var string $title */
/** @var array<int, array<string, mixed>> $documents */
?>
<section class="workspace-hero">
    <div class="container">
        <p class="eyebrow">Agentic Multimodal Knowledge Workspace</p>
        <h1>Assistant de connaissances</h1>
        <p class="hero-lead">Posez une question, générez un résumé ou rédigez une note — l'assistant s'appuie sur vos documents et cite ses sources.</p>
        <div class="hero-chips">
            <span class="chip chip-accent">RAG ancré</span>
            <span class="chip">Sources citées</span>
            <span class="chip">Tâches agentiques</span>
            <span class="chip">Versions &amp; audit</span>
        </div>
    </div>
</section>

<section class="container py-5">
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="workspace-card mb-4">
                <div class="card-body">
                    <p class="section-eyebrow">Question</p>
                    <p class="text-muted mb-4">Posez une question sur vos documents — l'assistant répond en citant ses sources.</p>

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
            </div>

            <div class="workspace-card">
                <div class="card-body">
                    <p class="section-eyebrow">Tâche agentique</p>
                    <p class="text-muted small mb-3">Ex. « Prépare une note pour le directeur sur les activités du trimestre » ou « Liste les projets en retard ».</p>

                    <form id="task-form">
                        <textarea id="instruction" class="form-control" rows="3" placeholder="Décris la tâche à réaliser…" required></textarea>
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
            </div>

            <div id="summary-card" class="workspace-card mt-4" hidden>
                <div class="card-body">
                    <strong class="d-block mb-2">Résumé</strong>
                    <div id="summary-text"></div>
                    <div id="summary-sources" class="mt-2 small" hidden>
                        <strong class="d-block mb-1">Sources</strong>
                        <ul id="summary-sources-list" class="mb-0"></ul>
                    </div>
                    <div id="summary-error" class="alert alert-danger mt-2" hidden></div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="workspace-card mb-4">
                <div class="card-body">
                    <p class="section-eyebrow">Ajouter un document</p>
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
            </div>

            <div class="workspace-card">
                <div class="card-body">
                    <p class="section-eyebrow">Documents indexés</p>
                    <?php if (empty($documents)): ?>
                        <p class="text-muted small mb-0">Aucun document pour l'instant.</p>
                    <?php else: ?>
                        <div>
                            <?php foreach ($documents as $doc): ?>
                                <div class="doc-row">
                                    <div class="doc-meta">
                                        <div class="doc-title"><?= e($doc['title'] ?? '') ?></div>
                                        <div class="doc-file"><?= e($doc['filename'] ?? '') ?></div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary summarize-btn"
                                            data-document-id="<?= (int) ($doc['id'] ?? 0) ?>">Résumer</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

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

    async function postJson(payload) {
        var response = await fetch('<?= e(url('/api/ask.php')) ?>', {
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
            var data = await postJson({ question: question, mode: 'answer' });
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
            var data = await postJson({ instruction: instruction, mode: 'task' });
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
                var response = await fetch('<?= e(url('/api/summarize.php')) ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ document_id: Number(button.getAttribute('data-document-id')) })
                });
                var data = await response.json();
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
