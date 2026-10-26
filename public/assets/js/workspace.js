/* Agentic Knowledge Workspace — shared client logic (one file for all pages). */
(function () {
    'use strict';

    var base = (window.WORKSPACE_BASE || '').replace(/\/+$/, '');

    function apiUrl(path) {
        return base + '/api/' + path;
    }

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

    /* ---------------- Result modal ---------------- */

    var modalEl = document.getElementById('result-modal');
    var modal = modalEl ? new bootstrap.Modal(modalEl) : null;
    var resultBadge = document.getElementById('result-modal-badge');
    var resultTitle = document.getElementById('result-modal-title');
    var resultSub = document.getElementById('result-modal-sub');
    var resultContent = document.getElementById('result-content');
    var resultSources = document.getElementById('result-sources');
    var resultSourcesList = document.getElementById('result-sources-list');
    var currentResult = { title: 'Résultat', content: '' };

    function showLoading(badge, title, sub) {
        if (!modal) { return; }
        resultBadge.textContent = badge;
        resultTitle.textContent = title;
        resultSub.textContent = sub;
        resultContent.innerHTML = '<div class="d-flex align-items-center gap-2 text-muted">'
            + '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>'
            + '<span>Génération en cours…</span></div>';
        resultSources.hidden = true;
        modal.show();
    }

    function showResult(badge, title, sub, content, sources) {
        if (!modal) { return; }
        currentResult = { title: title, content: content };
        resultBadge.textContent = badge;
        resultTitle.textContent = title;
        resultSub.textContent = sub;
        resultContent.innerHTML = escapeThenBold(content);
        resultSources.hidden = !renderSources(resultSourcesList, sources);
    }

    function showError(message) {
        if (!modal) {
            alert(message);
            return;
        }
        resultContent.innerHTML = '<div class="alert alert-danger mb-0">' + escapeThenBold(message) + '</div>';
        resultSources.hidden = true;
    }

    async function downloadResult(format) {
        try {
            var response = await fetch(apiUrl('export.php'), {
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

    if (modalEl) {
        document.getElementById('download-pdf').addEventListener('click', function () { downloadResult('pdf'); });
        document.getElementById('download-docx').addEventListener('click', function () { downloadResult('docx'); });
    }

    /* ---------------- Question ---------------- */

    var askForm = document.getElementById('ask-form');
    if (askForm) {
        askForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            var question = document.getElementById('question').value.trim();
            if (!question) { return; }

            showLoading('Réponse', 'Réponse', 'Recherche dans vos documents…');
            try {
                var data = await postJson(apiUrl('ask.php'), { question: question, mode: 'answer' });
                if (data.error) { showError(data.error); return; }
                showResult('Réponse', 'Réponse', 'Réponse ancrée dans vos documents.', data.answer || '', data.sources);
            } catch (err) {
                showError('Erreur réseau. Vérifiez le serveur.');
            }
        });
    }

    /* ---------------- Agentic task ---------------- */

    var taskForm = document.getElementById('task-form');
    if (taskForm) {
        taskForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            var instruction = document.getElementById('instruction').value.trim();
            if (!instruction) { return; }

            showLoading('Brouillon', 'Brouillon', 'Rédaction à partir de vos documents…');
            try {
                var data = await postJson(apiUrl('ask.php'), { instruction: instruction, mode: 'task' });
                if (data.error) { showError(data.error); return; }
                showResult('Brouillon', 'Brouillon', 'Brouillon prêt à être relu.', data.draft || '', data.sources);
            } catch (err) {
                showError('Erreur réseau. Vérifiez le serveur.');
            }
        });
    }

    /* ---------------- Upload ---------------- */

    var uploadForm = document.getElementById('upload-form');
    if (uploadForm) {
        uploadForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            var status = document.getElementById('upload-status');
            status.textContent = 'Envoi en cours…';
            status.className = 'small text-muted mt-2';

            try {
                var response = await fetch(apiUrl('upload.php'), {
                    method: 'POST',
                    body: new FormData(uploadForm)
                });
                var data = await response.json();
                if (data.error) {
                    status.textContent = data.error;
                    status.className = 'small text-danger mt-2';
                    return;
                }
                status.textContent = 'Indexé : ' + data.title + ' (' + data.chunks + ' segments).';
                status.className = 'small text-success mt-2';
                uploadForm.reset();
                if (document.getElementById('documents-list')) {
                    await refreshDocuments();
                }
            } catch (err) {
                status.textContent = 'Erreur réseau.';
                status.className = 'small text-danger mt-2';
            }
        });
    }

    /* ---------------- Documents list ---------------- */

    function bindSummarizeButtons() {
        document.querySelectorAll('.summarize-btn').forEach(function (button) {
            if (button.dataset.bound === '1') {
                return;
            }
            button.dataset.bound = '1';

            button.addEventListener('click', async function () {
                var documentTitle = button.getAttribute('data-document-title') || 'Document';
                showLoading('Résumé', 'Résumé — ' + documentTitle, 'Synthèse du document…');

                try {
                    var data = await postJson(apiUrl('summarize.php'), {
                        document_id: Number(button.getAttribute('data-document-id'))
                    });
                    if (data.error) { showError(data.error); return; }
                    showResult('Résumé', 'Résumé — ' + documentTitle, 'Synthèse générée à partir du document.', data.summary || '', data.sources);
                } catch (err) {
                    showError('Erreur réseau.');
                }
            });
        });
    }

    function renderDocuments(documents) {
        var list = document.getElementById('documents-list');
        if (!list) { return; }
        var count = document.getElementById('documents-count');
        if (count) {
            count.textContent = documents.length + ' document' + (documents.length > 1 ? 's' : '');
        }

        if (documents.length === 0) {
            list.innerHTML = '<div class="dashboard-empty"><div class="dashboard-empty-copy">'
                + '<p class="dashboard-empty-label">Aucun document</p>'
                + '<p>Ajoutez un premier document pour commencer.</p></div></div>';
            return;
        }

        list.innerHTML = '';

        documents.forEach(function (doc) {
            var row = document.createElement('div');
            row.className = 'dashboard-document-row';

            var icon = document.createElement('span');
            icon.className = 'doc-icon';
            icon.setAttribute('aria-hidden', 'true');
            icon.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9Z"/><path d="M14 3v6h6"/></svg>';

            var copy = document.createElement('div');
            copy.className = 'dashboard-document-copy';
            var strong = document.createElement('strong');
            strong.textContent = doc.title || '';
            var span = document.createElement('span');
            span.textContent = doc.filename || '';
            copy.appendChild(strong);
            copy.appendChild(span);

            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn btn-sm btn-outline-primary summarize-btn';
            button.setAttribute('data-document-id', String(doc.id));
            button.setAttribute('data-document-title', doc.title || 'Document');
            button.textContent = 'Résumer';

            row.appendChild(icon);
            row.appendChild(copy);
            row.appendChild(button);
            list.appendChild(row);
        });

        bindSummarizeButtons();
    }

    async function refreshDocuments() {
        try {
            var response = await fetch(apiUrl('documents.php'), { headers: { 'Accept': 'application/json' } });
            var data = await response.json();
            if (Array.isArray(data.documents)) {
                renderDocuments(data.documents);
            }
        } catch (err) {
            // Silent: the list keeps its previous state.
        }
    }

    bindSummarizeButtons();
})();
