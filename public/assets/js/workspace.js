/* Agentic Knowledge Workspace — shared client logic (one file for all pages). */
(function () {
    'use strict';

    var base = (window.WORKSPACE_BASE || '').replace(/\/+$/, '');
    var I18N = window.WORKSPACE_I18N || {};

    function t(key, fallback) {
        return I18N[key] || fallback || key;
    }

    function sprintf(template, a, b) {
        return String(template).replace('%s', a).replace('%s', b);
    }

    function apiUrl(path) {
        return base + '/api/' + path;
    }

    function highlightTerms(text, query) {
        var escaped = String(text || '').replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
        var terms = String(query || '').split(/[^\p{L}\p{N}]+/u)
            .filter(function (w) { return w.length >= 4; });
        terms.forEach(function (w) {
            var re = new RegExp('(' + w.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'giu');
            escaped = escaped.replace(re, '<mark>$1</mark>');
        });
        return escaped;
    }

    function renderSources(listEl, sources, query) {
        listEl.innerHTML = '';
        if (!Array.isArray(sources) || sources.length === 0) {
            return false;
        }
        sources.forEach(function (source, i) {
            var li = document.createElement('li');
            var strong = document.createElement('strong');
            strong.textContent = (source.num || (i + 1)) + '. ' + (source.title || 'Document');
            var span = document.createElement('span');
            span.className = 'text-muted d-block';
            span.innerHTML = query ? highlightTerms(source.excerpt || '', query) : highlightTerms(source.excerpt || '', '');
            li.appendChild(strong);
            li.appendChild(span);
            if (source.document_id) {
                var link = document.createElement('a');
                link.href = apiUrl('download_document.php?document_id=' + encodeURIComponent(source.document_id));
                link.className = 'small d-inline-block mt-1';
                link.textContent = t('download_source');
                li.appendChild(link);
            }
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
            + '<span>' + t('generating', 'Génération en cours…') + '</span></div>';
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
        var qInput = document.getElementById('question');
        resultSources.hidden = !renderSources(resultSourcesList, sources, qInput ? qInput.value : '');
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
            alert(t('export_failed', 'Export impossible.'));
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

            showLoading(t('answer_badge'), t('answer_badge'), t('answer_loading'));
            try {
                var data = await postJson(apiUrl('ask.php'), { question: question, mode: 'answer' });
                if (data.error) { showError(data.error); return; }
                showResult(t('answer_badge'), t('answer_badge'), t('answer_sub'), data.answer || '', data.sources);
            } catch (err) {
                showError(t('network_error_long'));
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

            showLoading(t('draft_badge'), t('draft_badge'), t('draft_loading'));
            try {
                var data = await postJson(apiUrl('ask.php'), { instruction: instruction, mode: 'task' });
                if (data.error) { showError(data.error); return; }
                showResult(t('draft_badge'), t('draft_badge'), t('draft_sub'), data.draft || '', data.sources);
            } catch (err) {
                showError(t('network_error_long'));
            }
        });
    }

    /* ---------------- Upload ---------------- */

    var uploadForm = document.getElementById('upload-form');
    if (uploadForm) {
        var fileInput = document.getElementById('file');
        var dropzone = fileInput ? fileInput.closest('.upload-dropzone') : null;
        var hint = dropzone ? dropzone.querySelector('.upload-dropzone-hint') : null;

        if (fileInput && hint) {
            fileInput.addEventListener('change', function () {
                var name = fileInput.files && fileInput.files[0] ? fileInput.files[0].name : '';
                hint.textContent = name !== '' ? name : 'Glissez un fichier ou cliquez pour parcourir';
                dropzone.classList.toggle('has-file', name !== '');
            });
        }

        uploadForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            var status = document.getElementById('upload-status');
            status.textContent = t('sending');
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
                status.textContent = sprintf(t('indexed'), data.title, data.chunks);
                status.className = 'small text-success mt-2';
                uploadForm.reset();
                if (hint) {
                    hint.textContent = t('dropzone_hint');
                    dropzone.classList.remove('has-file');
                }
                if (document.getElementById('documents-list')) {
                    await refreshDocuments();
                }
            } catch (err) {
                status.textContent = t('network_error');
                status.className = 'small text-danger mt-2';
            }
        });
    }

    /* ---------------- Documents list ---------------- */

    async function runSummarize(documentId, documentTitle) {
        showLoading(t('summary_badge'), t('summary_prefix') + documentTitle, t('summary_loading'));

        try {
            var data = await postJson(apiUrl('summarize.php'), {
                document_id: Number(documentId)
            });
            if (data.error) { showError(data.error); return; }
            showResult(t('summary_badge'), t('summary_prefix') + documentTitle, t('summary_sub'), data.summary || '', data.sources);
        } catch (err) {
            showError(t('network_error'));
        }
    }

    /* Generic confirmation modal: buttons/links with .doc-confirm declare
       data-confirm-action (download|summarize) and data-confirm-* texts. */
    function bindDocConfirm() {
        var modalEl = document.getElementById('doc-confirm-modal');
        if (!modalEl) { return; }

        var titleEl = document.getElementById('doc-confirm-title');
        var textEl = document.getElementById('doc-confirm-text');
        var confirmBtn = document.getElementById('doc-confirm-btn');
        var pending = null;

        confirmBtn.addEventListener('click', async function () {
            if (pending === null) { return; }
            var action = pending;
            pending = null;
            bootstrap.Modal.getOrCreateInstance(modalEl).hide();

            if (action.type === 'download') {
                window.location.href = action.href;
            } else if (action.type === 'summarize') {
                await runSummarize(action.id, action.title);
            }
        });

        document.querySelectorAll('.doc-confirm').forEach(function (el) {
            if (el.dataset.confirmBound === '1') { return; }
            el.dataset.confirmBound = '1';

            el.addEventListener('click', function (event) {
                event.preventDefault();
                titleEl.textContent = el.getAttribute('data-confirm-title') || t('confirm_default_title');
                textEl.textContent = el.getAttribute('data-confirm-text') || t('confirm_default_text');
                pending = el.getAttribute('data-confirm-action') === 'download'
                    ? { type: 'download', href: el.getAttribute('href') }
                    : { type: 'summarize', id: el.getAttribute('data-document-id'), title: el.getAttribute('data-document-title') || 'Document' };
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            });
        });
    }

    function bindSummarizeButtons() {
        document.querySelectorAll('.summarize-btn').forEach(function (button) {
            if (button.dataset.bound === '1' || button.classList.contains('doc-confirm')) {
                return;
            }
            button.dataset.bound = '1';

            button.addEventListener('click', async function () {
                var documentTitle = button.getAttribute('data-document-title') || 'Document';
                await runSummarize(button.getAttribute('data-document-id'), documentTitle);
            });
        });
    }

    function bindDeleteButtons() {
        var modalEl = document.getElementById('doc-delete-modal');
        var form = document.getElementById('doc-delete-form');
        var passwordInput = document.getElementById('doc-delete-password');
        var errorSlot = document.getElementById('doc-delete-error');
        var nameSlot = document.getElementById('doc-delete-name');
        var pendingId = null;

        if (form) {
            form.addEventListener('submit', async function (event) {
                event.preventDefault();
                if (pendingId === null) { return; }

                errorSlot.hidden = true;
                try {
                    var response = await fetch(apiUrl('delete_document.php'), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ document_id: pendingId, password: passwordInput.value })
                    });
                    var data = await response.json();
                    if (data.error) {
                        errorSlot.textContent = data.error;
                        errorSlot.hidden = false;
                        passwordInput.select();
                        return;
                    }
                    bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                    if (document.getElementById('documents-list')) {
                        await refreshDocuments();
                    } else {
                        window.location.reload();
                    }
                } catch (err) {
                    errorSlot.textContent = t('network_error');
                    errorSlot.hidden = false;
                }
            });
        }

        document.querySelectorAll('.delete-btn').forEach(function (button) {
            if (button.dataset.bound === '1') {
                return;
            }
            button.dataset.bound = '1';

            button.addEventListener('click', function () {
                if (!modalEl) {
                    return;
                }
                pendingId = Number(button.getAttribute('data-document-id'));
                var title = button.getAttribute('data-document-title') || t('this_document');
                nameSlot.textContent = sprintf(t('delete_text'), title);
                passwordInput.value = '';
                errorSlot.hidden = true;
                var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
                modalEl.addEventListener('shown.bs.modal', function focusPassword() {
                    modalEl.removeEventListener('shown.bs.modal', focusPassword);
                    passwordInput.focus();
                });
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
            row.className = 'doc-item';

            var icon = document.createElement('span');
            icon.className = 'doc-item-icon';
            icon.setAttribute('aria-hidden', 'true');
            icon.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9Z"/><path d="M14 3v6h6"/></svg>';

            var body = document.createElement('div');
            body.className = 'doc-item-body';

            var title = document.createElement('div');
            title.className = 'doc-item-title';
            title.textContent = doc.title || '';

            var meta = document.createElement('div');
            meta.className = 'doc-item-meta';

            var file = document.createElement('span');
            file.className = 'doc-item-file';
            file.textContent = doc.filename || '';

            var versionCount = Number(doc.versions || 0);
            var badge = document.createElement('span');
            badge.className = 'doc-item-badge';
            badge.textContent = versionCount + ' version' + (versionCount > 1 ? 's' : '');

            meta.appendChild(file);
            meta.appendChild(badge);
            body.appendChild(title);
            body.appendChild(meta);

            var actions = document.createElement('div');
            actions.className = 'doc-item-actions';

            var summarizeButton = document.createElement('button');
            summarizeButton.type = 'button';
            summarizeButton.className = 'doc-action summarize-btn';
            summarizeButton.title = 'Résumer';
            summarizeButton.setAttribute('data-document-id', String(doc.id));
            summarizeButton.setAttribute('data-document-title', doc.title || 'Document');
            summarizeButton.setAttribute('aria-label', 'Résumer ' + (doc.title || 'le document'));
            summarizeButton.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v3M12 18v3M3 12h3M18 12h3M5.6 5.6l2.1 2.1M16.3 16.3l2.1 2.1M18.4 5.6l-2.1 2.1M7.7 16.3l-2.1 2.1"/></svg>';

            var downloadLink = document.createElement('a');
            downloadLink.className = 'doc-action';
            downloadLink.title = 'Télécharger la dernière version';
            downloadLink.href = apiUrl('download_document.php') + '?document_id=' + doc.id;
            downloadLink.setAttribute('aria-label', 'Télécharger ' + (doc.title || 'le document'));
            downloadLink.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12M7 10l5 5 5-5M5 21h14"/></svg>';

            var versionsModal = document.getElementById('versions-' + doc.id);
            if (versionsModal) {
                var versionsButton = document.createElement('button');
                versionsButton.type = 'button';
                versionsButton.className = 'doc-action';
                versionsButton.title = 'Historique des versions';
                versionsButton.setAttribute('data-bs-toggle', 'modal');
                versionsButton.setAttribute('data-bs-target', '#versions-' + doc.id);
                versionsButton.setAttribute('aria-label', 'Historique des versions de ' + (doc.title || 'ce document'));
                versionsButton.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>';
                actions.appendChild(versionsButton);
            }

            var deleteButton = document.createElement('button');
            deleteButton.type = 'button';
            deleteButton.className = 'doc-action doc-action-danger delete-btn';
            deleteButton.title = 'Supprimer';
            deleteButton.setAttribute('data-document-id', String(doc.id));
            deleteButton.setAttribute('aria-label', 'Supprimer ' + (doc.title || 'le document'));
            deleteButton.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M6 7l1 13a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-13"/><path d="M10 11v6M14 11v6"/></svg>';

            actions.appendChild(downloadLink);
            actions.appendChild(summarizeButton);
            actions.appendChild(deleteButton);

            row.appendChild(icon);
            row.appendChild(body);
            row.appendChild(actions);
            list.appendChild(row);
        });

        bindSummarizeButtons();
        bindDeleteButtons();
        bindDocConfirm();
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
    bindDeleteButtons();
    bindDocConfirm();
})();
