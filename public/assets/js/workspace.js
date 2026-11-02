/* Send the CSRF token with every fetch (defence in depth for AJAX endpoints). */
(function () {
    var meta = document.querySelector('meta[name="csrf-token"]');
    if (!meta) { return; }
    var token = meta.getAttribute('content') || '';
    if (!token) { return; }
    var originalFetch = window.fetch;
    window.fetch = function (input, init) {
        init = init || {};
        var headers = new Headers(init.headers || {});
        if (!headers.has('X-CSRF-Token')) { headers.set('X-CSRF-Token', token); }
        init.headers = headers;
        return originalFetch.call(this, input, init);
    };
})();
/* Agentic Knowledge Workspace — shared client logic (one file for all pages). */
(function () {
    'use strict';

    var base = (window.WORKSPACE_BASE || '').replace(/\/+$/, '');
    var I18N = window.WORKSPACE_I18N || {};

    // Delegation flag, declared before the early bind so it is never reset.
    var deleteBindingDone = false;

    // Bound before anything else: a later init error must never disable
    // deleting a document.
    bindDeleteButtons();

    // The server-rendered rows carry .doc-confirm (summarize, download):
    // binding them only after a dynamic refresh left the buttons inert on load.
    bindDocConfirm();
    /* Category deletion asks for confirmation through a data attribute: inline
       handlers are forbidden by the CSP. */
    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form || !form.classList || !form.classList.contains('categorie-delete-form')) { return; }

        var message = form.getAttribute('data-confirm');
        if (message && !window.confirm(message)) { event.preventDefault(); }
    });


    /* ------------------------------------------------------------------
       Admin confirmation + password gate — delegated on document and
       registered FIRST so it survives any error in later init code.
       ------------------------------------------------------------------ */
    (function () {
        var modalEl = document.getElementById('admin-confirm-modal');
        if (!modalEl) { return; }

        var titleEl = document.getElementById('admin-confirm-title');
        var textEl = document.getElementById('admin-confirm-text');
        var confirmBtn = document.getElementById('admin-confirm-btn');
        var passwordInput = document.getElementById('admin-confirm-password');
        var errorEl = document.getElementById('admin-confirm-error');
        var pendingForm = null;
        var pendingOrigin = null;
        var submitting = false;

        /* Stacked modals render unreliably on small screens: the origin
           modal is closed while the password modal is open, restored on
           cancel with its fields intact. */
        modalEl.addEventListener('hidden.bs.modal', function () {
            if (pendingOrigin && !submitting) {
                window.bootstrap.Modal.getOrCreateInstance(pendingOrigin).show();
            }
            pendingOrigin = null;
            submitting = false;
        });

        if (confirmBtn) {
            confirmBtn.addEventListener('click', function () {
                if (!pendingForm) { return; }
                var password = (passwordInput.value || '').trim();
                if (password === '') {
                    errorEl.textContent = 'Mot de passe requis. / Password required.';
                    errorEl.hidden = false;
                    passwordInput.focus();
                    return;
                }
                var form = pendingForm;
                submitting = true;
                pendingForm = null;
                var stamp = document.createElement('input');
                stamp.type = 'hidden';
                stamp.name = 'mot_de_passe_admin';
                stamp.value = password;
                form.appendChild(stamp);
                window.bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                form.submit();
            });
        }

        document.addEventListener('click', function (event) {
            var el = event.target.closest ? event.target.closest('.admin-confirm') : null;
            if (!el) { return; }
            event.preventDefault();
            var form = el.closest('form');
            /* Required fields are validated first: the modal only opens once
               the form itself is complete. */
            if (form && !form.checkValidity()) {
                form.reportValidity();
                return;
            }
            pendingForm = form;
            pendingOrigin = el.closest('.modal');
            if (passwordInput) { passwordInput.value = ''; }
            if (errorEl) { errorEl.hidden = true; }
            titleEl.textContent = el.getAttribute('data-confirm-title') || 'Confirmer / Confirm';
            textEl.textContent = el.getAttribute('data-confirm-text') || '';
            var danger = el.getAttribute('data-confirm-tone') === 'danger';
            var head = modalEl.querySelector('.modal-header');
            head.classList.toggle('text-bg-danger', danger);
            head.classList.toggle('border-danger', danger);
            head.classList.toggle('text-bg-primary', !danger);
            head.classList.toggle('border-primary', !danger);
            confirmBtn.classList.toggle('btn-danger', danger);
            confirmBtn.classList.toggle('btn-primary', !danger);
            if (pendingOrigin) {
                window.bootstrap.Modal.getOrCreateInstance(pendingOrigin).hide();
            }
            window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
            if (passwordInput) { passwordInput.focus(); }
        });
    })();

    /* Document control modal: fill the selects from the clicked row. */
    (function () {
        var modal = document.getElementById('doc-control-modal');
        if (!modal) { return; }

        var idField = document.getElementById('doc-control-id');
        var titleEl = document.getElementById('doc-control-title');
        var espace = document.getElementById('doc-control-espaces');
        var conf = document.getElementById('doc-control-confidentialite');

        modal.addEventListener('show.bs.modal', function (event) {
            var trigger = event.relatedTarget;
            if (!trigger) { return; }

            if (idField) { idField.value = trigger.getAttribute('data-document-id') || ''; }
            if (titleEl) { titleEl.textContent = trigger.getAttribute('data-document-title') || ''; }

            if (espace) {
                var espaces = (trigger.getAttribute('data-document-espaces') || '')
                    .split(',')
                    .map(function (value) { return value.trim(); })
                    .filter(function (value) { return value !== ''; });

                if (window.jQuery && window.jQuery.fn.select2) {
                    window.jQuery(espace).val(espaces).trigger('change');
                } else {
                    Array.prototype.forEach.call(espace.options, function (option) {
                        option.selected = espaces.indexOf(option.value) !== -1;
                    });
                }
            }

            if (conf) {
                conf.value = trigger.getAttribute('data-document-confidentialite') || 'restreint';
                if (window.jQuery && window.jQuery.fn.select2) {
                    window.jQuery(conf).val(conf.value).trigger('change');
                }
            }
        });
    })();

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

            // A corrected source is the reliable reference: say so and let the
            // user download the exact text that was used.
            var isCorrected = !!(source.corrige || (source.correction && source.correction.id));

            if (isCorrected) {
                var badge = document.createElement('span');
                badge.className = 'src-badge-corrected';
                var when = (source.correction && source.correction.date) ? ' · ' + String(source.correction.date).slice(0, 10) : '';
                var lang = (source.correction && source.correction.langue) ? String(source.correction.langue).toUpperCase() : '';
                badge.textContent = (document.documentElement.lang === 'fr' ? 'Version corrigée' : 'Corrected version')
                    + (lang ? ' ' + lang : '') + when;
                strong.appendChild(badge);
            }

            var span = document.createElement('span');
            span.className = 'text-muted d-block';
            span.innerHTML = query ? highlightTerms(source.excerpt || '', query) : highlightTerms(source.excerpt || '', '');
            li.appendChild(strong);
            li.appendChild(span);

            if (isCorrected && source.correction && source.correction.id) {
                var corr = document.createElement('a');
                corr.href = apiUrl('correction_download.php?id=' + encodeURIComponent(source.correction.id));
                corr.className = 'small d-inline-block mt-1 src-corrected-dl';
                corr.textContent = document.documentElement.lang === 'fr'
                    ? 'Télécharger la version corrigée'
                    : 'Download the corrected version';
                li.appendChild(corr);
            }

            if (source.document_id) {
                var link = document.createElement('a');
                link.href = apiUrl('download_document.php?document_id=' + encodeURIComponent(source.document_id));
                link.className = 'small d-inline-block mt-1 ms-3';
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

        // Emphasis is a few words, not a paragraph. Models often wrap whole
        // sentences in **..**, which turns the answer into a bold wall and makes
        // it unreadable: short spans stay bold, long ones lose their markers.
        return escaped
            .replace(/\*\*([^*\n]{1,60})\*\*/g, '<strong>$1</strong>')
            .replace(/\*\*([\s\S]{61,}?)\*\*/g, '$1')
            .replace(/\*\*/g, '')
            .replace(/\n/g, '<br>');
    }

    /**
     * Render an answer or a summary as readable HTML.
     *
     * The model writes light Markdown: **short emphasis**, "* item" and
     * "1. item" lines. Showing those markers literally made every result look
     * like a draft, so they become real lists. Anything that is not recognised
     * stays escaped text — never HTML.
     */
    function renderRich(text) {
        var escaped = String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');

        // Emphasis is a few words, not a paragraph.
        var inline = function (line) {
            return line
                .replace(/\*\*([^*\n]{1,60})\*\*/g, '<strong>$1</strong>')
                .replace(/\*\*([\s\S]{61,}?)\*\*/g, '$1')
                .replace(/\*\*/g, '');
        };

        var lines = escaped.split('\n');
        var out = [];
        var list = null;

        var closeList = function () {
            if (list !== null) {
                out.push('</' + list + '>');
                list = null;
            }
        };

        for (var i = 0; i < lines.length; i++) {
            var line = lines[i].trim();

            if (line === '') {
                closeList();
                continue;
            }

            var bullet = line.match(/^[*\-•]\s+(.*)$/);
            var numbered = line.match(/^\d{1,2}[.)]\s+(.*)$/);

            if (bullet) {
                if (list !== 'ul') {
                    closeList();
                    out.push('<ul class="mb-2 ps-4">');
                    list = 'ul';
                }
                out.push('<li>' + inline(bullet[1]) + '</li>');
                continue;
            }

            if (numbered) {
                if (list !== 'ol') {
                    closeList();
                    out.push('<ol class="mb-2 ps-4">');
                    list = 'ol';
                }
                out.push('<li>' + inline(numbered[1]) + '</li>');
                continue;
            }

            closeList();

            // A line that is only a title stays as a title, not as a paragraph.
            var heading = line.match(/^#{1,6}\s+(.*)$/) || line.match(/^\*\*([^*]{1,60})\*\*$/);

            if (heading) {
                out.push('<p class="mb-2"><strong>' + inline(heading[1]) + '</strong></p>');
                continue;
            }

            out.push('<p class="mb-2">' + inline(line) + '</p>');
        }

        closeList();

        return out.join('\n');
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
    var currentResult = { title: window.UI('result_title', 'Result'), content: '' };

    function showLoading(badge, title, sub) {
        if (!modal) { return; }

        syncRetry();
        resultBadge.textContent = badge;
        resultTitle.textContent = title;
        resultSub.textContent = sub;
        resultContent.innerHTML = '<div class="d-flex align-items-center gap-2 text-muted">'
            + '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>'
            + '<span>' + t('generating', 'Génération en cours…') + '</span></div>';
        resultSources.hidden = true;
        if (resultSteps) { resultSteps.hidden = true; }
        modal.show();
    }

    var resultSteps = document.getElementById('result-steps');
    var resultStepsList = document.getElementById('result-steps-list');

    // Agent trace (plan → retrieve → draft → verify): makes the agent's work
    // auditable instead of a black box.
    function renderSteps(steps) {
        if (!resultSteps) { return; }
        resultStepsList.innerHTML = '';
        if (!Array.isArray(steps) || steps.length === 0) {
            resultSteps.hidden = true;
            return;
        }
        steps.forEach(function (step) {
            var li = document.createElement('li');
            var text = '';
            var details = [];
            if (step.step === 'plan') {
                text = sprintf(t('step_plan'), (step.queries || []).length);
                details = (step.queries || []).slice(1);
            } else if (step.step === 'retrieve') {
                text = sprintf(t('step_retrieve'), step.extracts, step.documents);
            } else if (step.step === 'draft') {
                text = sprintf(t('step_draft'), step.citations);
            } else if (step.step === 'scope') {
                var frScope = document.documentElement.lang === 'fr';
                text = step.document_id
                    ? (frScope ? 'Cadrage : tâche limitée au document demandé' : 'Scope: task restricted to the requested document')
                    : (frScope ? 'Aucun document nommé — recherche sur tout le corpus' : 'No document named — searching the whole corpus');            } else if (step.step === 'conformity') {
                var frUi = document.documentElement.lang === 'fr';
                text = !step.available
                    ? (frUi ? 'Conformité non vérifiée' : 'Conformity not checked')
                    : step.conforme
                        ? (frUi ? 'Conformité vérifiée : le résultat répond à l\'instruction' : 'Conformity checked: the output answers the instruction')
                        : (frUi ? 'Hors sujet détecté — résultat réécrit' : 'Off-topic detected — output rewritten');            } else if (step.step === 'verify') {
                text = !step.available ? t('step_verify_na')
                    : step.unsupported > 0 ? sprintf(t('step_verify_fixed'), step.checked, step.unsupported)
                    : sprintf(t('step_verify_ok'), step.checked);
            }
            li.textContent = text;
            if (details.length) {
                var ul = document.createElement('ul');
                ul.className = 'text-muted';
                details.forEach(function (d) {
                    var item = document.createElement('li');
                    item.textContent = '« ' + d + ' »';
                    ul.appendChild(item);
                });
                li.appendChild(ul);
            }
            resultStepsList.appendChild(li);
        });
        document.getElementById('result-steps-summary').textContent = t('agent_trace');
        resultSteps.hidden = false;
    }

    function showResult(badge, title, sub, content, sources, steps) {
        if (!modal) { return; }
        currentResult = { title: title, content: content };
        resultBadge.textContent = badge;
        resultTitle.textContent = title;
        resultSub.textContent = sub;
        resultContent.innerHTML = renderRich(content);
        var qInput = document.getElementById('question');
        resultSources.hidden = !renderSources(resultSourcesList, sources, qInput ? qInput.value : '');
        renderSteps(steps);
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

    /* ---------------- Retry (replays the last question/instruction) ---------------- */

    var lastRequest = null;
    var retryBtn = document.getElementById('retry-result');

    // The button only means something when a request was memorised. Replaying a
    // summary through ask.php was wrong anyway: each action keeps its endpoint.
    function syncRetry() {
        if (!retryBtn) { return; }
        var armed = lastRequest !== null;
        retryBtn.disabled = !armed;
        retryBtn.setAttribute('aria-disabled', armed ? 'false' : 'true');
        retryBtn.title = armed
            ? (retryBtn.getAttribute('data-label') || retryBtn.title)
            : (document.documentElement.lang === 'fr'
                ? 'Aucune requête à rejouer'
                : 'Nothing to replay');
    }

    if (retryBtn) {
        // Remember the original label so the tooltip can be restored.
        retryBtn.setAttribute('data-label', retryBtn.title || '');

        retryBtn.addEventListener('click', async function () {
            if (lastRequest === null) { return; }

            showLoading(lastRequest.badge, lastRequest.badge, lastRequest.loading);

            try {
                var data = await postJson(apiUrl(lastRequest.url || 'ask.php'), lastRequest.payload);

                if (data.error) { showError(data.error); return; }

                showResult(lastRequest.badge, lastRequest.badge, lastRequest.sub,
                    data.answer || data.draft || data.summary || '', data.sources, data.steps);
            } catch (err) {
                showError(t('network_error_long'));
            }
        });

        syncRetry();
    }

    /* ---------------- Question ---------------- */

    var askForm = document.getElementById('ask-form');
    if (askForm) {
        askForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            var question = document.getElementById('question').value.trim();
            if (!question) { return; }

            lastRequest = {
                payload: { question: question, mode: 'answer' },
                badge: t('answer_badge'), loading: t('answer_loading'), sub: t('answer_sub')
            };
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

            // Documents explicitly chosen in the form bound the task.
            var taskDocs = document.getElementById('task-docs');
            var taskDocIds = taskDocs
                ? Array.prototype.slice.call(taskDocs.selectedOptions || [])
                    .map(function (option) { return parseInt(option.value, 10); })
                    .filter(function (id) { return id > 0; })
                : [];

            lastRequest = {
                payload: { instruction: instruction, mode: 'task', document_ids: taskDocIds },
                badge: t('draft_badge'), loading: t('draft_loading'), sub: t('draft_sub')
            };
            showLoading(t('draft_badge'), t('draft_badge'), t('draft_loading'));
            try {
                var data = await postJson(apiUrl('ask.php'), { instruction: instruction, mode: 'task', document_ids: taskDocIds });
                if (data.error) { showError(data.error); return; }
                showResult(t('draft_badge'), t('draft_badge'), t('draft_sub'), data.draft || '', data.sources, data.steps);
            } catch (err) {
                showError(t('network_error_long'));
            }
        });
    }

    /* ---------------- Clean the result (sources removed) ---------------- */

    var cleanResult = document.getElementById('clean-result');

    if (cleanResult) {
        // A draft is often well written but unusable as a deliverable because it
        // ends with "Sources : [1], [2]" and carries inline [n] markers. This
        // toggle removes them and hides the source panel, so the DOCX / PDF
        // export contains the answer and nothing else. A second click restores
        // everything, so nothing is lost.
        var cleanBackup = null;

        // Produces a deliverable: what a person would actually read out. It
        // removes the citations, the "Sources : [n]" line, the bracketed working
        // markers, the method note, the documentary-reference section and the
        // horizontal rules — but never a sentence that carries meaning.
        var stripCitations = function (html) {
            var text = html
                .replace(/\s*(?:<strong>)?\s*Sources?\s*:\s*(?:\[\d{1,2}\]\s*(?:,\s*)?)+(?:<\/strong>)?\s*$/i, '')
                .replace(/\s*\[\d{1,2}(?:\s*,\s*\d{1,2})*\]/g, '')
                .replace(/\*?[ \t]*\[(?:Proposition|Élément issu|À compléter|Formule d|Adressez|Annoncer|Rappeler|Note de)[^\]]*\][ \t]*\*?[ \t]*:?[ \t]*/gi, '')
                // A meta suffix in the title: "Discours — Proposition à partir de …"
                .replace(/[ \t]*[-–—][ \t]*Proposition[ \t]+(?:à|a)[ \t]+partir[^\n]*$/gim, '');

            var scaffold = /^(?:#{1,6}[ \t]*)?\**\s*(?:référence documentaire|références?|note de méthode|notes de méthode)\b/i;
            var lines = text.split('\n');
            var out = [];
            var skipping = false;

            for (var i = 0; i < lines.length; i++) {
                var trimmed = lines[i].trim();

                if (scaffold.test(trimmed)) {
                    skipping = true;
                    continue;
                }

                if (skipping) {
                    if (trimmed === '') {
                        skipping = false;
                        out.push('');
                    }
                    continue;
                }

                if (/^-{3,}$/.test(trimmed) || /^\*{3,}$/.test(trimmed)) {
                    continue;
                }

                out.push(lines[i]);
            }

            return out.join('\n')
                .replace(/[ \t]{2,}/g, ' ')
                .replace(/\n{3,}/g, '\n\n')
                .trim();
        };

        cleanResult.addEventListener('click', function () {
            if (!resultContent) { return; }

            if (cleanBackup === null) {
                cleanBackup = {
                    html: resultContent.innerHTML,
                    content: currentResult ? currentResult.content : '',
                    sourcesHidden: resultSources ? resultSources.hidden : true,
                    stepsHidden: resultSteps ? resultSteps.hidden : true
                };

                // Work on the TEXT, not the HTML: headings wrapped in <strong>
                // escaped the line-based patterns when the DOM was stripped.
                var cleaned = stripCitations(String(currentResult ? currentResult.content : resultContent.textContent || ''));

                if (currentResult) {
                    currentResult.content = cleaned;
                }

                resultContent.innerHTML = renderRich(cleaned);

                if (resultSources) { resultSources.hidden = true; }
                if (resultSteps) { resultSteps.hidden = true; }

                cleanResult.setAttribute('aria-pressed', 'true');
                cleanResult.classList.remove('btn-outline-warning');
                cleanResult.classList.add('btn-warning');

                var hint = document.createElement('p');
                hint.id = 'clean-result-hint';
                hint.className = 'small text-muted mt-2 mb-0';
                hint.textContent = document.documentElement.lang === 'fr'
                    ? 'Sources retirées : le fichier exporté ne contient que la réponse.'
                    : 'Sources removed: the exported file contains the answer only.';
                // Outside #result-content: the notice must never end up in
                // the downloaded document.
                if (resultContent.parentNode) {
                    resultContent.parentNode.insertBefore(hint, resultContent.nextSibling);
                }

                return;
            }

            var staleHint = document.getElementById('clean-result-hint');
            if (staleHint) { staleHint.remove(); }

            resultContent.innerHTML = cleanBackup.html;

            if (currentResult) { currentResult.content = cleanBackup.content; }
            if (resultSources) { resultSources.hidden = cleanBackup.sourcesHidden; }
            if (resultSteps) { resultSteps.hidden = cleanBackup.stepsHidden; }

            cleanBackup = null;
            cleanResult.setAttribute('aria-pressed', 'false');
            cleanResult.classList.remove('btn-warning');
            cleanResult.classList.add('btn-outline-warning');
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

        var uploadStatus = document.getElementById('upload-status');
        var JOBS_KEY = 'akw_ingestion_jobs';

        function setUploadStatus(text, tone) {
            uploadStatus.textContent = text;
            uploadStatus.className = 'small mt-2 text-' + tone;
        }

        function resetUploadForm() {
            uploadForm.reset();
            if (hint) {
                hint.textContent = t('dropzone_hint');
                dropzone.classList.remove('has-file');
            }
        }

        function pendingJobs() {
            try { return JSON.parse(sessionStorage.getItem(JOBS_KEY) || '[]'); } catch (e) { return []; }
        }

        function setPendingJobs(ids) {
            sessionStorage.setItem(JOBS_KEY, JSON.stringify(ids));
        }

        function formatElapsed(seconds) {
            var m = Math.floor(seconds / 60);
            var s = seconds % 60;
            return (m > 0 ? m + ' min ' : '') + s + ' s';
        }

        // Background ingestion (audio / image / scanned PDF): poll the job until
        // it is indexed or fails; the user can keep working in the meantime.
        async function trackJob(jobId) {
            while (true) {
                var data;
                try {
                    var response = await fetch(apiUrl('upload_status.php?job_id=' + encodeURIComponent(jobId)), { cache: 'no-store' });
                    data = await response.json();
                } catch (err) {
                    await new Promise(function (r) { setTimeout(r, 5000); });
                    continue;
                }

                if (data.status === 'termine' || data.status === 'echec' || data.error && !data.status) {
                    setPendingJobs(pendingJobs().filter(function (id) { return id !== jobId; }));
                    if (data.status === 'termine') {
                        setUploadStatus(sprintf(t('indexed'), data.title, data.chunks), 'success');
                        if (document.getElementById('documents-list')) {
                            await refreshDocuments();
                        }
                    } else {
                        setUploadStatus(data.error || t('network_error'), 'danger');
                    }
                    return;
                }

                setUploadStatus(sprintf(t('processing_bg'), data.title, formatElapsed(data.elapsed || 0)), 'muted');
                await new Promise(function (r) { setTimeout(r, 3000); });
            }
        }

        pendingJobs().forEach(trackJob);

        uploadForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            setUploadStatus(t('sending'), 'muted');

            try {
                var response = await fetch(apiUrl('upload.php'), {
                    method: 'POST',
                    body: new FormData(uploadForm)
                });
                var data = await response.json();
                if (data.error) {
                    setUploadStatus(data.error, 'danger');
                    return;
                }
                resetUploadForm();
                if (data.job_id) {
                    setPendingJobs(pendingJobs().concat([data.job_id]));
                    trackJob(data.job_id);
                    return;
                }
                setUploadStatus(sprintf(t('indexed'), data.title, data.chunks), 'success');
                if (document.getElementById('documents-list')) {
                    await refreshDocuments();
                }
            } catch (err) {
                setUploadStatus(t('network_error'), 'danger');
            }
        });
    }

    /* ---------------- Translation (fr / en / mooré) ---------------- */

    var translateForm = document.getElementById('translate-form');
    if (translateForm) {
        var trStatus = document.getElementById('translate-status');
        var trResult = document.getElementById('translate-result');
        var trCopy = document.getElementById('translate-copy');

        var trFrom = document.getElementById('translate-from');
        var trTo = document.getElementById('translate-to');
        var trSource = document.getElementById('translate-source');
        var trForce = document.getElementById('translate-force');
        var trBtn = document.getElementById('translate-btn');
        var trBusy = false;
        var prevFrom = trFrom.value, prevTo = trTo.value;

        function retranslateIfShown() {
            if (trResult.value && trSource.value.trim()) {
                translateForm.requestSubmit();
            }
        }

        // Select2 wraps these selects: programmatic changes must go through
        // jQuery .trigger('change') so the widget redraws. trBusy prevents the
        // change listeners below from reacting to our own updates.
        function setLang(sel, val) {
            if (window.jQuery && jQuery(sel).hasClass('select2-hidden-accessible')) {
                jQuery(sel).val(val).trigger('change');
            } else {
                sel.value = val;
            }
        }

        function applyLang(fromVal, toVal) {
            trBusy = true;
            setLang(trFrom, fromVal);
            setLang(trTo, toVal);
            prevFrom = fromVal;
            prevTo = toVal;
            trBusy = false;
            retranslateIfShown();
        }

        // Keep From ≠ To in real time: choosing the same language on one side
        // swaps the other to the side's previous value (a true swap).
        // These selects are Select2-wrapped: bind via jQuery so Select2's
        // programmatic 'change' events reach the handlers too.
        var trOnFrom = function () {
            if (trBusy) return;
            if (trFrom.value === trTo.value) {
                var altFrom = prevFrom !== trFrom.value ? prevFrom : (trFrom.value === 'fr' ? 'en' : 'fr');
                applyLang(trFrom.value, altFrom);
                return;
            }
            prevFrom = trFrom.value;
            retranslateIfShown();
        };
        var trOnTo = function () {
            if (trBusy) return;
            if (trTo.value === trFrom.value) {
                var altTo = prevTo !== trTo.value ? prevTo : (trTo.value === 'fr' ? 'en' : 'fr');
                applyLang(altTo, trTo.value);
                return;
            }
            prevTo = trTo.value;
            retranslateIfShown();
        };
        if (window.jQuery) {
            jQuery(trFrom).on('change', trOnFrom);
            jQuery(trTo).on('change', trOnTo);
        } else {
            trFrom.addEventListener('change', trOnFrom);
            trTo.addEventListener('change', trOnTo);
        }

        document.getElementById('translate-swap').addEventListener('click', function () {
            applyLang(trTo.value, trFrom.value);
        });

        // Live translation: one call per stabilised phrase, never per keystroke.
        //  - 1.2 s of silence before firing;
        //  - nothing to do if the text and the languages did not change;
        //  - after a 429, automatic translation pauses for the announced delay
        //    instead of hammering the endpoint.
        var trDebounce = null;
        var trSeq = 0;
        var trPausedUntil = 0;
        var lastTranslated = { text: '', from: '', to: '' };

        function trPauseMessage(seconds) {
            var fr = document.documentElement.lang === 'fr';
            return fr
                ? 'Traduction automatique en pause (' + seconds + ' s) — quota atteint.'
                : 'Automatic translation paused (' + seconds + ' s) — quota reached.';
        }

        trSource.addEventListener('input', function () {
            clearTimeout(trDebounce);

            if (!trSource.value.trim()) {
                trResult.value = '';
                trCopy.hidden = true;
                trStatus.textContent = '';
                lastTranslated = { text: '', from: '', to: '' };
                return;
            }

            trForce.value = '0';

            if (Date.now() < trPausedUntil) {
                trStatus.textContent = trPauseMessage(Math.ceil((trPausedUntil - Date.now()) / 1000));
                trStatus.className = 'small mt-2 text-muted';
                return;
            }

            var text = trSource.value.trim();

            if (text === lastTranslated.text
                && trFrom.value === lastTranslated.from
                && trTo.value === lastTranslated.to) {
                return;
            }

            trDebounce = setTimeout(function () { translateForm.requestSubmit(); }, 1200);
        });

        translateForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            if (trBtn) { trBtn.classList.add('is-busy'); }
            trStatus.textContent = trForce.value === '1'
                ? t('reformulating', 'Generating another wording…')
                : t('translating', 'Translating…');
            trStatus.className = 'small mt-2 text-muted';
            var seq = ++trSeq;
            var sentText = trSource.value.trim();
            var sentFrom = trFrom.value;
            var sentTo = trTo.value;

            try {
                var data = await postJson(apiUrl('translate.php'), {
                    text: trSource.value,
                    from: sentFrom,
                    to: sentTo,
                    force: trForce.value === '1',
                    previous: trForce.value === '1' ? trResult.value : ''
                });

                // A newer request has been fired: ignore this late answer.
                if (seq !== trSeq) { return; }

                trForce.value = '0';

                if (data.rate_limited) {
                    var wait = Number(data.retry_after) > 0 ? Number(data.retry_after) : 60;
                    trPausedUntil = Date.now() + wait * 1000;
                    trStatus.textContent = trPauseMessage(wait);
                    trStatus.className = 'small mt-2 text-muted';
                    return;
                }

                if (data.error) {
                    trStatus.textContent = data.error;
                    trStatus.className = 'small mt-2 text-danger';
                    return;
                }

                // The pane is a plain textarea: markdown markers would show
                // as literal asterisks, so they are removed.
                trResult.value = String(data.translation || '').replace(/\*\*/g, '');
                trStatus.textContent = '';
                trCopy.hidden = !trResult.value;
                lastTranslated = { text: sentText, from: sentFrom, to: sentTo };
            } catch (err) {
                trStatus.textContent = t('network_error');
                trStatus.className = 'small mt-2 text-danger';
            } finally {
                if (trBtn) { trBtn.classList.remove('is-busy'); }
            }
        });

        trBtn.addEventListener('click', function () {
            if (!trSource.value.trim()) { trSource.focus(); return; }
            trForce.value = '1';
            trStatus.textContent = t('reformulating', 'Generating another wording…');
            trStatus.className = 'small mt-2 text-muted';
            translateForm.requestSubmit();
        });

        trCopy.addEventListener('click', async function () {
            await navigator.clipboard.writeText(trResult.value);
            trCopy.textContent = t('copied', 'Copied');
            setTimeout(function () { trCopy.textContent = t('copy', 'Copy'); }, 1500);
        });
    }

    /* ---------------- Audit journal: legal purge ---------------- */

    var purgeForm = document.getElementById('journal-purge-form');
    if (purgeForm) {
        var purgeError = document.getElementById('journal-purge-error');
        var purgeSubmit = document.getElementById('journal-purge-submit');
        var purgeDebut = document.getElementById('journal-purge-debut');
        var purgeFin = document.getElementById('journal-purge-fin');
        var purgePassword = document.getElementById('journal-purge-password');

        var purgeShowError = function (message) {
            if (!purgeError) { return; }
            if (!message) {
                purgeError.hidden = true;
                purgeError.textContent = '';
                return;
            }
            purgeError.hidden = false;
            purgeError.textContent = message;
        };

        // Keep the range coherent: the end can never precede the start.
        if (purgeDebut && purgeFin) {
            purgeDebut.addEventListener('change', function () {
                if (purgeDebut.value) { purgeFin.min = purgeDebut.value; } else { purgeFin.removeAttribute('min'); }
            });
        }

        // The form previously had no method and no handler: the browser sent a
        // GET to the endpoint and displayed a raw 405 page. It is now an AJAX
        // POST, so failures stay inside the modal.
        purgeForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            purgeShowError(null);

            if (purgeSubmit) { purgeSubmit.disabled = true; }

            try {
                var response = await fetch(apiUrl('journal_purge.php'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({
                        date_debut: purgeDebut ? purgeDebut.value : '',
                        date_fin: purgeFin ? purgeFin.value : '',
                        password: purgePassword ? purgePassword.value : ''
                    })
                });

                var data = {};
                try { data = await response.json(); } catch (e) { data = {}; }

                if (response.ok && typeof data.deleted !== 'undefined') {
                    window.location.reload();
                    return;
                }

                purgeShowError(data.error || t('purge_failed', 'Purge impossible. Vérifiez la période et le mot de passe.'));
            } catch (err) {
                purgeShowError(t('network_error'));
            } finally {
                if (purgeSubmit) { purgeSubmit.disabled = false; }
            }
        });
    }
    /* ---------------- Corrected version of an indexed document ---------------- */

    var correctionButtons = document.querySelectorAll('.correction-btn');
    if (correctionButtons.length) {
        var correctionModalEl = document.getElementById('correction-modal');
        var correctionModal = (window.bootstrap && correctionModalEl) ? new bootstrap.Modal(correctionModalEl) : null;
        var correctionForm = document.getElementById('correction-form');
        var correctionDocId = document.getElementById('correction-document-id');
        var correctionTitle = document.getElementById('correction-document-title');
        var correctionLangue = document.getElementById('correction-langue');
        var correctionText = document.getElementById('correction-text');
        var correctionNom = document.getElementById('correction-nom');
        var correctionNomValue = document.getElementById('correction-nom-value');
        var correctionDocTitle = '';
        var correctionFile = document.getElementById('correction-file');
        var correctionStatus = document.getElementById('correction-status');
        var correctionSubmit = document.getElementById('correction-submit');

        correctionButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (!correctionModal) { return; }
                correctionDocId.value = btn.getAttribute('data-document-id') || '';
                correctionDocTitle = btn.getAttribute('data-document-title') || '';
                correctionTitle.textContent = correctionDocTitle;
                var lang = btn.getAttribute('data-document-langue') || 'fr';
                if (correctionLangue) { correctionLangue.value = ['fr', 'en', 'mo'].indexOf(lang) >= 0 ? lang : 'fr'; }
                if (correctionText) { correctionText.value = ''; }
                if (correctionFile) { correctionFile.value = ''; }
                if (correctionStatus) { correctionStatus.textContent = ''; correctionStatus.className = 'small mt-2'; }
                if (correctionNom) { delete correctionNom.dataset.touched; }
                correctionProposeName(1);
                correctionModal.show();
            });
        });

        var correctionExisting = document.getElementById('correction-existing');
        var correctionList = document.getElementById('correction-list');

        function correctionBadge(langue) {
            var map = { fr: 'FR', en: 'EN', mo: 'MO' };
            return '<span class="correction-lang">' + (map[langue] || langue.toUpperCase()) + '</span>';
        }

        // "Meeting6.pdf" + version 2  ->  "meeting6_corrige_v2.txt"
        function correctionSlug(value) {
            var base = (value || 'document').toLowerCase()
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .replace(/\.[a-z0-9]{1,5}$/i, '')
                .replace(/[^a-z0-9]+/g, '_')
                .replace(/^_+|_+$/g, '');
            return base === '' ? 'document' : base;
        }

        function correctionProposeName(version) {
            if (!correctionNom) { return; }
            if (correctionNom.dataset.touched === '1' && correctionNom.value.trim() !== '') { return; }
            var name = correctionSlug(correctionDocTitle) + '_corrige_v' + version + '.txt';
            correctionNom.value = name;
            if (correctionNomValue) { correctionNomValue.value = name; }
        }
        // The proposed name must follow the file the user actually picked:
        // choosing a .docx cannot suggest a .txt name.
        function correctionSyncExtension() {
            if (!correctionFile || !correctionNom) { return; }
            var file = correctionFile.files && correctionFile.files[0];
            if (!file) { return; }
            var match = /\.([a-z0-9]{1,5})$/i.exec(file.name || '');
            if (!match) { return; }
            var ext = match[1].toLowerCase();
            var base = correctionNom.value.trim() || (correctionSlug(correctionDocTitle) + '_corrige_v1');
            base = base.replace(/\.[a-z0-9]{1,5}$/i, '');
            correctionNom.value = base + '.' + ext;
            if (correctionNomValue) { correctionNomValue.value = correctionNom.value; }
            delete correctionNom.dataset.touched;
        }
        if (correctionNom) {
            correctionNom.addEventListener('input', function () {
                correctionNom.dataset.touched = '1';
                if (correctionNomValue) { correctionNomValue.value = correctionNom.value.trim(); }
            });
        }

        if (correctionFile) {
            correctionFile.addEventListener('change', correctionSyncExtension);
        }

        async function correctionRefresh() {
            if (!correctionList || !correctionDocId) { return; }
            var docId = correctionDocId.value;
            if (!docId) { return; }

            try {
                var r = await fetch(apiUrl('correction_list.php?document_id=' + encodeURIComponent(docId)), {
                    headers: { 'Accept': 'application/json' }
                });
                var d = await r.json();
                var rows = (d && d.corrections) ? d.corrections : [];
                correctionProposeName(rows.length + 1);

                if (!rows.length) {
                    correctionExisting.hidden = true;
                    correctionList.innerHTML = '';
                    return;
                }

                correctionExisting.hidden = false;
                correctionList.innerHTML = rows.map(function (row) {
                    var del = row.peut_supprimer
                        ? '<button type="button" class="correction-del" data-id="' + row.id + '" title="' + t('delete', 'Supprimer') + '" aria-label="' + t('delete', 'Supprimer') + '">'
                          + '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M6 7l1 13a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-13"/></svg>'
                          + '</button>'
                        : '';

                    return '<div class="correction-item">'
                        + correctionBadge(row.langue)
                        + '<div class="correction-body">'
                        + '<p class="correction-text">' + row.extrait.replace(/[<>&]/g, '') + '</p>'
                        + '<p class="correction-meta">' + row.auteur + ' · ' + row.cree_le + ' · ' + row.chars + ' ' + t('chars', 'caractères') + '</p>'
                        + '</div>'
                        + del
                        + '</div>';
                }).join('');

                correctionList.querySelectorAll('.correction-del').forEach(function (btn) {
                    btn.addEventListener('click', async function () {
                        btn.disabled = true;
                        try {
                            var rr = await fetch(apiUrl('correction_delete.php'), {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ id: parseInt(btn.getAttribute('data-id'), 10) })
                            });
                            if (rr.ok) {
                                correctionStatus.textContent = t('correction_deleted', 'Correction supprimée.');
                                correctionStatus.className = 'small mt-2 text-success';
                                await correctionRefresh();
                            } else {
                                var err = await rr.json();
                                correctionStatus.textContent = err.error || t('correction_failed', 'Suppression impossible.');
                                correctionStatus.className = 'small mt-2 text-danger';
                            }
                        } catch (e) {
                            correctionStatus.textContent = t('correction_failed', 'Suppression impossible.');
                            correctionStatus.className = 'small mt-2 text-danger';
                        } finally {
                            btn.disabled = false;
                        }
                    });
                });
            } catch (e) {
                correctionExisting.hidden = true;
            }
        }

        if (correctionModalEl) {
            correctionModalEl.addEventListener('shown.bs.modal', correctionRefresh);
        }
        if (correctionForm) {
            correctionForm.addEventListener('submit', async function (event) {
                event.preventDefault();

                var text = correctionText ? correctionText.value.trim() : '';
                var hasFile = correctionFile && correctionFile.files && correctionFile.files.length > 0;

                if (text === '' && !hasFile) {
                    correctionStatus.textContent = t('correction_need_input', 'Ajoutez un fichier ou collez le texte corrigé.');
                    correctionStatus.className = 'small mt-2 text-danger';
                    return;
                }

                correctionSubmit.disabled = true;
                correctionStatus.textContent = t('correction_saving', 'Enregistrement…');
                correctionStatus.className = 'small mt-2 text-muted';

                try {
                    var fd = new FormData();
                    fd.append('document_id', correctionDocId.value);
                    fd.append('langue', correctionLangue.value);
                    if (text !== '') { fd.append('texte_corrige', text); }
                    if (hasFile) { fd.append('fichier', correctionFile.files[0]); }

                    var r = await fetch(apiUrl('correction_save.php'), {
                        method: 'POST', body: fd, headers: { 'Accept': 'application/json' }
                    });
                    var d = await r.json();

                    if (r.ok && d.id) {
                        correctionStatus.textContent = t('correction_saved', 'Correction enregistrée. Le système enrichit son vocabulaire.')
                            + ' (' + (d.chars || 0) + ' ' + t('chars', 'caractères') + ')';
                        correctionStatus.className = 'small mt-2 text-success';
                        correctionForm.reset();
                        correctionRefresh();
                        setTimeout(function () { if (correctionModal) { correctionModal.hide(); } }, 1400);
                    } else {
                        correctionStatus.textContent = d.error || t('correction_failed', 'Enregistrement impossible.');
                        correctionStatus.className = 'small mt-2 text-danger';
                    }
                } catch (err) {
                    correctionStatus.textContent = t('correction_failed', 'Enregistrement impossible.');
                    correctionStatus.className = 'small mt-2 text-danger';
                } finally {
                    correctionSubmit.disabled = false;
                }
            });
        }
    }
    /* ---------------- Meeting recorder (microphone → WAV → upload) ---------------- */

    var recBtn = document.getElementById('record-btn');
    if (recBtn) {
        var recStop = document.getElementById('record-stop');
        var recTimer = document.getElementById('record-timer');
        var recStatus = document.getElementById('record-status');
        var mediaRecorder = null, streamRef = null, chunks = [], recInterval = null, recStart = 0, recElapsed = 0, recPausedAt = 0;
        var analyserCtx = null, analyser = null, levelEl = document.getElementById('record-level');

        function updateLevel() {
            if (!analyser || !levelEl) { return; }
            var data = new Uint8Array(analyser.frequencyBinCount);
            analyser.getByteTimeDomainData(data);
            var peak = 0;
            for (var i = 0; i < data.length; i++) { peak = Math.max(peak, Math.abs(data[i] - 128) / 128); }
            levelEl.style.width = Math.min(100, Math.round(peak * 250)) + '%';
        }

        // PCM Float32 → 16-bit mono WAV blob
        function wavBlob(pcm, rate) {
            var buf = new ArrayBuffer(44 + pcm.length * 2), v = new DataView(buf);
            function str(o, s) { for (var i = 0; i < s.length; i++) { v.setUint8(o + i, s.charCodeAt(i)); } }
            str(0, 'RIFF'); v.setUint32(4, 36 + pcm.length * 2, true); str(8, 'WAVEfmt ');
            v.setUint32(16, 16, true); v.setUint16(20, 1, true); v.setUint16(22, 1, true);
            v.setUint32(24, rate, true); v.setUint32(28, rate * 2, true);
            v.setUint16(32, 2, true); v.setUint16(34, 16, true);
            str(36, 'data'); v.setUint32(40, pcm.length * 2, true);
            for (var i = 0; i < pcm.length; i++) {
                var s = Math.max(-1, Math.min(1, pcm[i]));
                v.setInt16(44 + i * 2, s < 0 ? s * 0x8000 : s * 0x7FFF, true);
            }
            return new Blob([buf], { type: 'audio/wav' });
        }

        // Decode the MediaRecorder output, downmix to mono, resample to 16 kHz
        // (whisper's native rate, ~2 MB/min).
        function toMono16k(audioBuffer) {
            var channels = audioBuffer.numberOfChannels;
            var ratio = audioBuffer.sampleRate / 16000;
            var outLen = Math.floor(audioBuffer.length / ratio);
            var out = new Float32Array(outLen);
            for (var i = 0; i < outLen; i++) {
                var idx = Math.floor(i * ratio), sum = 0;
                for (var c = 0; c < channels; c++) { sum += audioBuffer.getChannelData(c)[idx]; }
                out[i] = sum / channels;
            }
            return out;
        }

        function recTick() {
            // While paused the clock must freeze too, not keep counting.
            var s = Math.floor(((livePaused ? recPausedAt : Date.now()) - recStart) / 1000);
            var ts = String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0');
            recTimer.textContent = ts;
            if (liveTimer) liveTimer.textContent = ts;
        }

        /* ---- Rolling live transcription: every ~8 s the new audio slice is
           re-decoded from the full chunk buffer, sent to the ASR endpoint and
           appended to the live modal. ASR stays accurate (clean 16 k WAV per
           slice) while the transcript follows speech in near real time. ---- */
        var liveModalEl = document.getElementById('live-rec-modal');
        var liveModal = (window.bootstrap && liveModalEl) ? new bootstrap.Modal(liveModalEl) : null;
        var liveText = document.getElementById('live-rec-text');
        var liveTimer = document.getElementById('live-rec-timer');
        var liveState = document.getElementById('live-rec-state');
        var liveLevel = document.getElementById('live-rec-level');
        var liveHint = document.getElementById('live-rec-hint');
        var liveAudioSent = 0;   // slices actually sent to the ASR
        var liveWav = null;      // the recording itself: the source for reformulation
        var liveAudioDone = 0;   // slices whose reply came back
        var liveLangPrev = '';   // previous spoken language (output follows it)
        var liveLangFollow = '';
        var liveCursor = 0;          // samples already transcribed
        var liveBusy = false;        // in-flight chunk
        var liveRunning = false;
        var livePushTimer = null;
        var livePcm = [];            // raw Float32 buffers captured by ScriptProcessor
        var liveRate = 48000;        // analyserCtx.sampleRate, set on start
        var liveProc = null;         // ScriptProcessorNode for PCM capture
        var liveLast = '';           // last received transcript — continuity hint
        var liveInFlight = 0;        // concurrent ASR calls (max 2 → pipeline)
        var liveSeq = 0;             // slice issue order
        var liveFlush = 0;           // next slice index to render
        var livePending = {};        // seq → transcript, flushed in order

        // Drop the buffers already transcribed: a long meeting would otherwise
        // grow an unbounded array and slow every slice down.
        function livePrune() {
            var drop = 0, acc = 0;
            while (drop < livePcm.length && acc + livePcm[drop].length <= liveCursor) {
                acc += livePcm[drop].length;
                drop++;
            }
            if (drop > 0) {
                livePcm = livePcm.slice(drop);
                liveCursor -= acc;
            }
        }

        function liveRender(txt) {
            if (!liveText || !txt) return;
            if (/^[\s*\(\[]|bruits?|musique|applaudissements/i.test(txt)) return;
            var empty = liveText.querySelector('.live-empty');
            if (empty) empty.remove();
            var line = document.createElement('span');
            line.className = 'live-chunk';
            line.textContent = txt + ' ';
            liveText.appendChild(line);
            liveText.scrollTop = liveText.scrollHeight;
        }

        async function livePush() {
            if (liveInFlight >= 3 || !liveRunning || livePaused || !livePcm.length) return;
            liveInFlight++;
            var seq = -1;   // allocated only when a slice is really sent
            try {
                var total = 0;
                for (var k = 0; k < livePcm.length; k++) total += livePcm[k].length;
                var newSamples = total - liveCursor;
                if (newSamples < liveRate * 7) {
                    liveInFlight--;
                    if (liveState) liveState.textContent = '…';
                    return;
                }

                // Concatenate the not-yet-transcribed span.
                var span = new Float32Array(newSamples), off = 0, skip = liveCursor;
                for (k = 0; k < livePcm.length; k++) {
                    var buf = livePcm[k];
                    if (skip >= buf.length) { skip -= buf.length; continue; }
                    var s = skip; skip = 0;
                    span.set(buf.subarray(s), off); off += buf.length - s;
                }

                // VAD: close the slice on a natural pause after 4 s, so a word is
                // never split between two ASR calls (a split word is a lost word).
                var cut = span.length;
                if (newSamples > liveRate * 6) {
                    var win = Math.floor(liveRate * 0.14);
                    var from = Math.max(Math.floor(liveRate * 3), Math.floor(span.length * 0.5));
                    for (var ci = from; ci + win <= span.length; ci += win >> 1) {
                        var e = 0, n = 0;
                        for (var j = ci; j < ci + win; j += 4) { e += span[j] * span[j]; n++; }
                        if (e / n < 0.0006) { cut = ci + win; break; }
                    }
                }
                if (cut < Math.floor(liveRate * 2)) { liveInFlight--; return; }

                // Downsample → 16 kHz.
                var ratio = liveRate / 16000;
                var n16 = Math.floor(cut / ratio);
                var pcm16 = new Float32Array(n16);
                var sum2 = 0;
                for (var i = 0; i < n16; i++) {
                    var v = span[Math.floor(i * ratio)] || 0;
                    sum2 += v * v;
                    pcm16[i] = v;
                }

                // RMS, not peak: quiet speech used to fall under the old threshold
                // and the whole slice was discarded — that was the "missing words".
                var rms = Math.sqrt(sum2 / Math.max(1, n16));
                if (liveLevel) {
                    liveLevel.style.width = Math.min(100, Math.round(rms * 900)) + '%';
                    liveLevel.className = 'ltr-meter-fill' + (rms > 0.12 ? ' is-hot' : '');
                }
                if (rms < 0.0015) {
                    liveCursor += cut;
                    liveInFlight--;
                    if (liveHint) {
                        liveHint.hidden = false;
                        liveHint.textContent = document.documentElement.lang === 'fr'
                            ? 'Silence détecté. Parlez plus près du micro si rien ne s’écrit.'
                            : 'Silence detected. Move closer to the microphone if nothing appears.';
                    }
                    return;
                }

                liveCursor += cut;
                livePrune();

                var fd = new FormData();
                fd.append('audio', wavBlob(pcm16, 16000), 'chunk.wav');
                var liveLangEl = document.getElementById('live-rec-lang');
                if (liveLangEl && liveLangEl.value) fd.append('to', liveLangEl.value);
                var liveFromEl = document.getElementById('live-rec-from');
                if (liveFromEl && liveFromEl.value) fd.append('from', liveFromEl.value);
                if (liveLast !== '') fd.append('ctx', liveLast.slice(-300));

                seq = liveSeq++;
                liveAudioSent++;
            if (liveState) liveState.textContent = '⏳ ' + liveAudioDone + '/' + liveAudioSent;
                if (liveHint) { liveHint.hidden = true; }
                if (liveState) { liveState.textContent = '⏳'; liveState.className = 'ltr-state'; }

                var ctrl = new AbortController();
                var killer = setTimeout(function () { ctrl.abort(); }, 20000);
                var r;
                try {
                    r = await fetch(apiUrl('transcribe_chunk.php'), {
                        method: 'POST', body: fd,
                        headers: { 'Accept': 'application/json' },
                        signal: ctrl.signal
                    });
                } finally {
                    clearTimeout(killer);
                }

                var data = await r.json();
                liveAudioDone++;
                var txt = (data.text || '').trim();
                if (data.error && liveState) {
                    liveState.textContent = data.error;
                    liveState.className = 'ltr-state is-error';
                } else if (data.translation_error && liveState) {
                    liveState.textContent = 'texte brut (traduction indisponible)';
                    liveState.className = 'ltr-state is-error';
                }
                livePending[seq] = txt;
            } catch (err) {
                if (seq >= 0) { livePending[seq] = ''; liveAudioDone++; }
                if (liveState) liveState.textContent = '⏳ ' + liveAudioDone + '/' + liveAudioSent;
                if (liveState) {
                    liveState.textContent = 'ASR ' + (err && err.message ? err.message : 'error');
                    liveState.className = 'ltr-state is-error';
                }
            }

            // Flush completed slices strictly in order (never leaves a hole: an
            // abandoned slice always writes an empty slot).
            while (livePending[liveFlush] !== undefined) {
                var out = livePending[liveFlush];
                delete livePending[liveFlush];
                liveFlush++;
                if (out !== '') {
                    liveLast = out;
                    liveRender(out);
                    if (liveState) { liveState.textContent = '✓'; liveState.className = 'ltr-state is-ok'; }
                } else if (liveState && liveAudioSent > 0) {
                    liveState.textContent = '…';
                    liveState.className = 'ltr-state';
                }
            }
            liveInFlight--;
        }

        // Warn as soon as both lists point at the same language: the server
        // then has nothing to translate and returns the raw transcript.
        // The output language follows the spoken language:
        //   spoken FR  -> output FR (transcript)   — pick another to translate
        //   spoken MO  -> output MO
        //   spoken EN  -> output EN
        // Whenever the spoken language changes, an output that was tracking the
        // previous spoken language follows it, so "speak X -> get X" always holds.
        function liveSyncLangs() {
            var f = document.getElementById('live-rec-from');
            var t = document.getElementById('live-rec-lang');
            if (!f || !t || !liveHint) return;

            liveLangFollow = f.value;

            if (t.value !== '' && t.value === liveLangPrev && f.value !== '') {
                t.value = f.value;
            }

            liveLangPrev = f.value;

            if (t.value === '') {
                liveHint.hidden = false;
                liveHint.textContent = document.documentElement.lang === 'fr'
                    ? 'Transcription dans la langue parlée. Choisissez une autre langue pour traduire.'
                    : 'Transcript in the spoken language. Pick another language to translate.';
            } else if (t.value === f.value) {
                liveHint.hidden = false;
                liveHint.textContent = document.documentElement.lang === 'fr'
                    ? 'Langue parlée = langue de sortie : transcription seule.'
                    : 'Spoken language = output language: transcript only.';
            } else {
                liveHint.hidden = true;
                liveHint.textContent = '';
            }
        }
        function startLiveTranscription() {
            if (!liveModal) return;
            liveCursor = 0; liveRunning = true; liveBusy = false;
            liveInFlight = 0; liveSeq = 0; liveFlush = 0; livePending = {}; liveLast = '';
            liveAudioSent = 0;
            if (liveHint) { liveHint.hidden = true; liveHint.textContent = ''; }
            if (liveLevel) { liveLevel.style.width = '0'; liveLevel.className = 'ltr-meter-fill'; }

            if (liveState) liveState.textContent = '';
            liveModal.show();
            var __lf = document.getElementById('live-rec-from');
            var __lt = document.getElementById('live-rec-lang');
            if (__lf) __lf.addEventListener('change', liveSyncLangs);
            if (__lt) __lt.addEventListener('change', liveSyncLangs);
            liveSyncLangs();
            // No background slicing: one reliable call is made when the user stops.
            setTimeout(function () {
                if (liveAudioSent === 0 && liveHint) {
                    liveHint.hidden = false;
                    liveHint.textContent = document.documentElement.lang === 'fr'
                        ? 'Aucune tranche audio n’a pu être envoyée : le micro ne délivre pas de son exploitable.'
                        : 'No audio slice could be sent: the microphone is not delivering usable sound.';
                }
            }, 18000);
            setTimeout(livePush, 8000); // first slice: 8 s of speech
        }

        // keepOpen=true means "we are about to show the full transcript":
        // the modal must stay visible instead of closing and reopening.
        function stopLiveTranscription(keepOpen) {
            liveRunning = false;
            clearInterval(livePushTimer);
            if (keepOpen) {
                if (liveState) { liveState.textContent = ''; liveState.className = 'ltr-state'; }
                return;
            }
            if (liveModal) liveModal.hide();
        }

        var liveStopBtn = document.getElementById('live-rec-stop');
        if (liveStopBtn) liveStopBtn.addEventListener('click', function () { if (recStop && !recStop.hidden) recStop.click(); });

        // Pause/resume (icon toggles play↔pause); rolling loop freezes too.
        var livePaused = false;
        var livePauseBtn = document.getElementById('live-rec-pause');
        var livePauseIc = document.getElementById('live-rec-pause-ic');
        var liveRecDot = liveModalEl ? liveModalEl.querySelector('.live-rec-dot') : null;
        if (livePauseBtn) livePauseBtn.addEventListener('click', function () {
            if (!mediaRecorder) return;
            livePaused = !livePaused;
            try { livePaused ? mediaRecorder.pause() : mediaRecorder.resume(); } catch (e) { livePaused = !livePaused; return; }
            if (livePaused) { recPausedAt = Date.now(); }
            else { recStart += Date.now() - recPausedAt; } // shift start → elapsed freezes
            if (livePauseIc) livePauseIc.innerHTML = livePaused
                ? '<path d="M8 5v14l11-7z" fill="currentColor" stroke="none"/>'
                : '<path d="M10 4H6v16h4zM18 4h-4v16h4z"/>';
            if (liveRecDot) liveRecDot.style.animationPlayState = livePaused ? 'paused' : 'running';
            if (liveState) liveState.textContent = livePaused ? '⏸' : '';
        });

        /* ---- Result panel: rephrase + DOCX + PDF ---- */
        var liveRecFinal = document.getElementById('live-rec-final-text');
        var liveRecReform = document.getElementById('live-rec-reformulate');
        var liveRecDocx = document.getElementById('live-rec-docx');
        var liveRecPdf = document.getElementById('live-rec-pdf');

        function liveRecSetBusy(on) {
            [liveRecReform, liveRecDocx, liveRecPdf].forEach(function (b) {
                if (b) { b.disabled = !!on; }
            });
        }

        async function liveRecDownload(format) {
            var text = liveRecFinal ? liveRecFinal.textContent.trim() : '';
            if (!text) { return; }
            liveRecSetBusy(true);
            try {
                var r = await fetch(apiUrl('export.php'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': '*/*' },
                    body: JSON.stringify({ title: 'Transcription', content: text, format: format })
                });
                if (!r.ok) { throw new Error('export'); }
                var blob = await r.blob();
                var a = document.createElement('a');
                a.href = URL.createObjectURL(blob);
                a.download = 'transcription.' + (format === 'pdf' ? 'pdf' : 'docx');
                document.body.appendChild(a);
                a.click();
                a.remove();
                setTimeout(function () { URL.revokeObjectURL(a.href); }, 4000);
            } catch (e) {
                if (liveState) {
                    liveState.textContent = document.documentElement.lang === 'fr' ? 'téléchargement impossible' : 'download failed';
                    liveState.className = 'ltr-state is-error';
                }
            } finally {
                liveRecSetBusy(false);
            }
        }

        if (liveRecDocx) { liveRecDocx.addEventListener('click', function () { liveRecDownload('docx'); }); }
        if (liveRecPdf) { liveRecPdf.addEventListener('click', function () { liveRecDownload('pdf'); }); }

        if (liveRecReform) {
            liveRecReform.addEventListener('click', async function () {
                var current = liveRecFinal ? liveRecFinal.textContent.trim() : '';
                if (!liveWav && current === '') { return; }

                liveRecSetBusy(true);
                if (liveState) {
                    liveState.hidden = false;
                    liveState.textContent = document.documentElement.lang === 'fr' ? 'reformulation depuis l’audio…' : 'rephrasing from the audio…';
                    liveState.className = 'ltr-state';
                }

                try {
                    var fd = new FormData();
                    var selFrom = document.getElementById('live-rec-from');
                    var selTo = document.getElementById('live-rec-lang');

                    if (liveWav) {
                        fd.append('audio', liveWav, 'recording.wav');
                    } else {
                        fd.append('text', current);
                    }
                    if (selFrom && selFrom.value) { fd.append('from', selFrom.value); }
                    if (selTo && selTo.value) { fd.append('to', selTo.value); }
                    if (current !== '') { fd.append('previous', current); }

                    var r = await fetch(apiUrl('reformulate.php'), {
                        method: 'POST', body: fd, headers: { 'Accept': 'application/json' }
                    });
                    var d = await r.json();

                    if (d.text) {
                        liveRecFinal.textContent = d.text;
                        if (liveState) { liveState.textContent = '✓'; liveState.className = 'ltr-state is-ok'; }
                    } else if (liveState) {
                        liveState.textContent = d.error || 'reformulation impossible';
                        liveState.className = 'ltr-state is-error';
                    }
                } catch (e) {
                    if (liveState) {
                        liveState.textContent = document.documentElement.lang === 'fr' ? 'reformulation impossible' : 'rephrase failed';
                        liveState.className = 'ltr-state is-error';
                    }
                } finally {
                    liveRecSetBusy(false);
                }
            });
        }
        recBtn.addEventListener('click', async function () {
            try {
                if (!window.MediaRecorder) { throw new Error('MediaRecorder'); }
                streamRef = await navigator.mediaDevices.getUserMedia({ audio: true });
                var mime = ['audio/webm;codecs=opus', 'audio/webm', 'audio/ogg']
                    .find(function (m) { return MediaRecorder.isTypeSupported(m); }) || '';
                mediaRecorder = new MediaRecorder(streamRef, mime ? { mimeType: mime } : {});
                // Live level meter: if it stays flat while you speak, the mic
                // is physically silent (wrong device / muted / OS privacy).
                analyserCtx = new (window.AudioContext || window.webkitAudioContext)();
                if (analyserCtx.state === 'suspended') { await analyserCtx.resume(); }
                analyser = analyserCtx.createAnalyser();
                analyserCtx.createMediaStreamSource(streamRef).connect(analyser);
                // An unconnected analyser never processes audio in Chrome —
                // pull it through a muted gain so the meter is truthful.
                var pull = analyserCtx.createGain();
                pull.gain.value = 0;
                analyser.connect(pull);
                pull.connect(analyserCtx.destination);

                // Raw PCM capture for live transcription — no blob decoding.
                livePcm = []; liveCursor = 0; liveRate = analyserCtx.sampleRate;
                liveProc = analyserCtx.createScriptProcessor(4096, 1, 1);
                analyserCtx.createMediaStreamSource(streamRef).connect(liveProc);
                liveProc.onaudioprocess = function (ev) {
                    if (!livePaused) livePcm.push(new Float32Array(ev.inputBuffer.getChannelData(0)));
                };
                var liveSink = analyserCtx.createGain();
                liveSink.gain.value = 0;
                liveProc.connect(liveSink); liveSink.connect(analyserCtx.destination);
                chunks = [];
                mediaRecorder.ondataavailable = function (e) { if (e.data && e.data.size) { chunks.push(e.data); } };
                mediaRecorder.start(4000); // 4 s timeslice → chunks flow live
                startLiveTranscription();
                var track = streamRef.getAudioTracks()[0];
                if (track && track.muted) {
                    recStatus.textContent = t('rec_muted');
                    recStatus.className = 'small mt-2 text-warning';
                }
                recStart = Date.now(); recElapsed = 0; livePaused = false;
                recInterval = setInterval(function () { recTick(); updateLevel(); }, 300);
                recBtn.hidden = true; recStop.hidden = false;
                recStatus.textContent = '';
            } catch (err) {
                recStatus.textContent = t('rec_mic');
                recStatus.className = 'small mt-2 text-danger';
            }
        });

        recStop.addEventListener('click', function () {
            stopLiveTranscription(true);
            clearInterval(recInterval);
            recBtn.hidden = false; recStop.hidden = true;
            var duration = recTimer.textContent;

            mediaRecorder.onstop = async function () {
                streamRef.getTracks().forEach(function (trk) { trk.stop(); });
                if (liveProc) { try { liveProc.disconnect(); } catch (e) {} liveProc = null; }
                if (analyserCtx) { analyserCtx.close(); analyserCtx = null; analyser = null; }
                if (levelEl) { levelEl.style.width = '0'; }
                try {
                    var blob = new Blob(chunks, { type: chunks[0] ? chunks[0].type : 'audio/webm' });
                    var ctx = new (window.AudioContext || window.webkitAudioContext)();
                    var decoded = await ctx.decodeAudioData(await blob.arrayBuffer());
                    ctx.close();

                    // Silence check: a flat recording means a device problem —
                    // attaching it would index an empty transcript.
                    var mono = toMono16k(decoded);
                    var peak = 0;
                    for (var i = 0; i < mono.length; i++) { peak = Math.max(peak, Math.abs(mono[i])); }
                    if (peak < 0.01) {
                        recStatus.textContent = t('rec_silent');
                        recStatus.className = 'small mt-2 text-danger';
                        recTimer.textContent = '';
                        return;
                    }
                    var wav = wavBlob(mono, 16000);
                    liveWav = wav;   // reformulation re-listens to this, never to the translation
                    // Guaranteed result: whatever the live slices managed to do,
                    // transcribe the whole recording in one call and show it.
                    try {
                        var ffd = new FormData();
                        ffd.append('audio', wav, 'full.wav');
                        var fl = document.getElementById('live-rec-from');
                        if (fl && fl.value) ffd.append('from', fl.value);
                        var tl = document.getElementById('live-rec-lang');
                        if (tl && tl.value) ffd.append('to', tl.value);
                        if (liveState) {
                            liveState.textContent = document.documentElement.lang === 'fr' ? 'transcription…' : 'transcribing…';
                            liveState.className = 'ltr-state';
                        }
                        var fin = document.getElementById('live-rec-final');
                        var fint = document.getElementById('live-rec-final-text');
                        if (fin && fint) {
                            fin.hidden = false;
                            fint.textContent = document.documentElement.lang === 'fr' ? 'Transcription en cours…' : 'Transcribing…';
                        }
                        var r2 = await fetch(apiUrl('transcribe_chunk.php'), {
                            method: 'POST', body: ffd, headers: { 'Accept': 'application/json' }
                        });
                        var d2 = await r2.json();
                        if (fint) {
                            var t2 = (d2.text || '').trim();
                            fint.textContent = t2 !== '' ? t2 : (document.documentElement.lang === 'fr' ? '(aucun texte reconnu)' : '(no speech recognised)');
                        }
                        if (liveState) { liveState.textContent = '✓'; liveState.className = 'ltr-state is-ok'; }
                    } catch (e) { /* the file is still attached below */ }

                    var stamp = new Date().toISOString().slice(0, 16).replace(/[T:]/g, '-');
                    var file = new File([wav], 'enregistrement-' + stamp + '.wav', { type: 'audio/wav' });
                    var dt = new DataTransfer();
                    dt.items.add(file);
                    var input = document.getElementById('file');
                    input.files = dt.files;
                    input.dispatchEvent(new Event('change', { bubbles: true }));

                    var title = document.getElementById('title');
                    if (title && title.value.trim() === '') {
                        title.value = sprintf(t('rec_title'), new Date().toLocaleString());
                    }
                    recStatus.textContent = sprintf(t('rec_done'), duration);
                    recStatus.className = 'small mt-2 text-success';
                } catch (err) {
                    recStatus.textContent = t('rec_mic');
                    recStatus.className = 'small mt-2 text-danger';
                }
                recTimer.textContent = '';
            };
            mediaRecorder.stop();
        });
    }

    /* ---------------- Documents list ---------------- */

    async function runSummarize(documentId, documentTitle) {
        lastRequest = {
            url: 'summarize.php',
            payload: { document_id: Number(documentId) },
            badge: t('summary_badge'),
            loading: t('summary_loading'),
            sub: t('summary_prefix') + documentTitle
        };

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

    /* Generic form-confirmation modal: submit buttons with .admin-confirm
       declare data-confirm-title / data-confirm-text; the wrapped form is

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

    /**
     * Suppression: delegated on document and bound early.
     *
     * Binding the rows directly worked until an unrelated initialisation error
     * ran before the end of this file: the buttons then stayed inert with no
     * visible symptom. Delegation plus an early call makes it independent of
     * what happens later.
     */
    function bindDeleteButtons() {
        if (deleteBindingDone) { return; }
        deleteBindingDone = true;

        document.addEventListener('click', function (event) {
            var button = event.target.closest ? event.target.closest('.delete-btn') : null;
            if (!button) { return; }

            var modalEl = document.getElementById('doc-delete-modal');
            var form = document.getElementById('doc-delete-form');
            var passwordInput = document.getElementById('doc-delete-password');
            var errorSlot = document.getElementById('doc-delete-error');
            var nameSlot = document.getElementById('doc-delete-name');

            if (!modalEl || !form) { return; }

            form.setAttribute('data-document-id', String(Number(button.getAttribute('data-document-id')) || 0));

            if (nameSlot) {
                nameSlot.textContent = sprintf(t('delete_text'), button.getAttribute('data-document-title') || t('this_document'));
            }
            if (passwordInput) { passwordInput.value = ''; }
            if (errorSlot) { errorSlot.hidden = true; }

            var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
            modalEl.addEventListener('shown.bs.modal', function focusPassword() {
                modalEl.removeEventListener('shown.bs.modal', focusPassword);
                if (passwordInput) { passwordInput.focus(); }
            });
        });

        document.addEventListener('submit', async function (event) {
            var form = event.target;
            if (!form || form.id !== 'doc-delete-form') { return; }
            event.preventDefault();

            var modalEl = document.getElementById('doc-delete-modal');
            var passwordInput = document.getElementById('doc-delete-password');
            var errorSlot = document.getElementById('doc-delete-error');
            var documentId = Number(form.getAttribute('data-document-id') || 0);

            if (!documentId || !passwordInput) { return; }
            if (errorSlot) { errorSlot.hidden = true; }

            try {
                var response = await fetch(apiUrl('delete_document.php'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ document_id: documentId, password: passwordInput.value })
                });
                var data = await response.json();

                if (data.error) {
                    if (errorSlot) {
                        errorSlot.textContent = data.error;
                        errorSlot.hidden = false;
                    }
                    passwordInput.select();
                    return;
                }

                if (modalEl) { bootstrap.Modal.getOrCreateInstance(modalEl).hide(); }

                if (document.getElementById('documents-list')) {
                    await refreshDocuments();
                } else {
                    window.location.reload();
                }
            } catch (err) {
                if (errorSlot) {
                    errorSlot.textContent = t('network_error');
                    errorSlot.hidden = false;
                }
            }
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
            summarizeButton.title = window.UI('doc_summarize', 'Summarise');
            summarizeButton.setAttribute('data-document-id', String(doc.id));
            summarizeButton.setAttribute('data-document-title', doc.title || 'Document');
            summarizeButton.setAttribute('aria-label', window.UI('doc_summarize_of', 'Summarise ') + (doc.title || window.UI('doc_default', 'the document')));
            summarizeButton.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v3M12 18v3M3 12h3M18 12h3M5.6 5.6l2.1 2.1M16.3 16.3l2.1 2.1M18.4 5.6l-2.1 2.1M7.7 16.3l-2.1 2.1"/></svg>';

            var downloadLink = document.createElement('a');
            downloadLink.className = 'doc-action';
            downloadLink.title = window.UI('doc_download', 'Download the latest version');
            downloadLink.href = apiUrl('download_document.php') + '?document_id=' + doc.id;
            downloadLink.setAttribute('aria-label', window.UI('doc_download_of', 'Download ') + (doc.title || window.UI('doc_default', 'the document')));
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

    /* -------- Audio playback + live transcript translation in documents -------- */

    var audioModal = document.getElementById('doc-audio-modal');
    if (audioModal) {
        var audioPlayer = document.getElementById('doc-audio-player');
        var ltLang = document.getElementById('doc-audio-lang');
        var ltFrom = document.getElementById('doc-audio-from');
        function ltFromVal() { return (ltFrom && ltFrom.value) ? ltFrom.value : ''; }
        var ltStatus = document.getElementById('doc-audio-translate-status');
        var ltBox = document.getElementById('doc-audio-translation');
        var ltExports = document.getElementById('doc-audio-exports');
        var ltDocId = null, ltDocTitle = '', ltRun = 0, ltText = '';

        function ltReset() {
            ltRun++;
            ltTimed = false;
            ltSegs = [];
            ltLines = [];
            if (ltBox) { ltBox.hidden = true; ltBox.innerHTML = ''; }
            ltText = '';
            if (ltExports) ltExports.classList.add('lt-disabled');
            if (ltStatus) ltStatus.textContent = '';
        }

        function appendLtSegment(text) {
            if (!ltBox) return;
            var p = document.createElement('p');
            p.className = 'lt-segment';
            p.textContent = text;
            ltBox.appendChild(p);
            ltBox.scrollTop = ltBox.scrollHeight;
        }

        var ltLines = [];       // <p> per timed segment
        var ltSegs = [];        // {start,end,text|null}
        var ltTimed = false;

        // Karaoke reveal: on each playback tick, finished segments show fully
        // (dimmed), the current one reveals words proportionally to position.
        // Estimated segments (seg.est) get proportional timings from the audio
        // duration — instant subtitles even before whisper timings exist.
        function renderSubtitles() {
            if (!ltTimed) return;
            var pos = (audioPlayer.currentTime || 0) * 1000;
            var dur = (audioPlayer.duration || 0) * 1000;
            for (var i = 0; i < ltSegs.length; i++) {
                var seg = ltSegs[i], line = ltLines[i];
                if (!seg || !line) continue;
                if (seg.est) {
                    seg.start = dur * seg.i / seg.total;
                    seg.end = dur * (seg.i + 1) / seg.total;
                }
                if (seg.text === null) {
                    line.textContent = '…';
                    line.className = 'lt-segment lt-pending';
                } else if (dur <= 0 && seg.est) {
                    line.textContent = seg.text;   // duration unknown: show all
                    line.className = 'lt-segment lt-done';
                } else if (pos >= seg.end) {
                    line.textContent = seg.text;
                    line.className = 'lt-segment lt-done';
                } else if (pos >= seg.start) {
                    var words = seg.text.split(/\s+/);
                    var frac = Math.min(1, (pos - seg.start) / Math.max(1, seg.end - seg.start));
                    var n = Math.max(1, Math.ceil(frac * words.length));
                    line.textContent = words.slice(0, n).join(' ');
                    line.className = 'lt-segment lt-active';
                } else {
                    line.textContent = '…';
                    line.className = 'lt-segment lt-pending';
                }
            }
        }
        audioPlayer.addEventListener('timeupdate', renderSubtitles);
        audioPlayer.addEventListener('seeked', renderSubtitles);
        audioPlayer.addEventListener('loadedmetadata', renderSubtitles);

        // Timed mode: whisper timestamps + per-segment Muse translations,
        // fetched in parallel batches so segments arrive fast, with one retry
        // per segment — a single failure never stops the stream.
        async function fetchTimedSegment(docId, lang, i) {
            for (var attempt = 0; attempt < 2; attempt++) {
                try {
                    var r = await fetch(apiUrl('translate_document.php')
                        + '?document_id=' + encodeURIComponent(docId)
                        + '&to=' + encodeURIComponent(lang)
                        + '&from=' + encodeURIComponent(ltFromVal())
                        + '&mode=timed&segment=' + i, { headers: { 'Accept': 'application/json' } });
                    var data = await r.json();
                    if (!data.error) return data;
                    if (data.error === 'timed_unavailable') return data;
                } catch (err) { /* retry */ }
            }
            return { error: 'failed' };
        }

        async function runTimed(docId, lang, run, timings, total) {
            ltTimed = true;
            ltSegs = timings.map(function (tm) { return { start: tm.start, end: tm.end, text: null }; });
            ltLines = ltSegs.map(function () {
                var p = document.createElement('p');
                p.className = 'lt-segment lt-pending';
                p.textContent = '…';
                ltBox.appendChild(p);
                return p;
            });
            renderSubtitles();

            var received = 0, failed = 0, next = 0;
            var workers = [];
            var CONCURRENCY = 3;

            async function worker() {
                while (next < total) {
                    var i = next++;
                    var data = await fetchTimedSegment(docId, lang, i);
                    if (run !== ltRun) return;
                    if (data.error) {
                        failed++;
                        ltSegs[i].text = null;
                        ltLines[i].className = 'lt-segment lt-pending';
                    } else {
                        received++;
                        ltSegs[i].text = data.translation;
                        ltText += data.translation + '\n\n';
                    }
                    renderSubtitles();
                    ltStatus.textContent = sprintf(t('lt_progress'), received, total);
                    ltStatus.className = 'small text-muted';
                }
            }

            for (var w = 0; w < CONCURRENCY; w++) workers.push(worker());
            await Promise.all(workers);
            if (run !== ltRun) return;

            if (failed > 0) {
                ltStatus.textContent = sprintf(t('lt_done'), received) + ' (' + failed + ' ' + t('lt_error') + ')';
                ltStatus.className = 'small text-warning';
            } else {
                ltStatus.textContent = sprintf(t('lt_done'), total);
                ltStatus.className = 'small text-success';
            }
            if (ltExports) ltExports.classList.toggle('lt-disabled', received === 0);
        }

        // Fetch and display the translation — timed (karaoke) when whisper
        // timestamps are available, otherwise progressive text segments.
        // ltRun guards against stale replies after a language change or close.
        async function translateLive(docId, lang, run) {
            if (!ltBox || !ltStatus) return;
            ltBox.hidden = false;
            ltStatus.textContent = t('lt_preparing');
            ltStatus.className = 'small text-muted';

            // Timed probe: cached timings answer instantly; a cold whisper run
            // is aborted after 12 s so the UI never looks frozen (the server
            // still finishes it in the background — next open is cached).
            var ctrl = new AbortController();
            var probeTimer = setTimeout(function () { ctrl.abort(); }, 12000);
            try {
                var probe = await fetch(apiUrl('translate_document.php')
                    + '?document_id=' + encodeURIComponent(docId)
                    + '&to=' + encodeURIComponent(lang) + '&from=' + encodeURIComponent(ltFromVal()) + '&mode=timed',
                    { headers: { 'Accept': 'application/json' }, signal: ctrl.signal });
                var pd = await probe.json();
                if (run !== ltRun) return;
                if (pd.mode === 'timed' && pd.total > 0) {
                    clearTimeout(probeTimer);
                    await runTimed(docId, lang, run, pd.timings, pd.total);
                    return;
                }
                // A validated correction exists for this language: it is the
                // reliable reference, so it is shown as-is instead of the model output.
                if (pd.mode === 'corrected' && pd.translation) {
                    clearTimeout(probeTimer);
                    ltTimed = false;
                    ltText = pd.translation;
                    ltBox.textContent = pd.translation;
                    ltBox.hidden = false;
                    var when = (pd.correction && pd.correction.date) ? ' · ' + String(pd.correction.date).slice(0, 10) : '';
                    ltStatus.textContent = (document.documentElement.lang === 'fr' ? 'Version corrigée' : 'Corrected version') + when;
                    ltStatus.className = 'small text-success';
                    return;
                }
            } catch (err) { /* timeout or unavailable → estimated karaoke */ }
            clearTimeout(probeTimer);

            // Estimated karaoke: text segments already indexed translate fast;
            // they are revealed proportionally to the audio position, so the
            // subtitles still follow speech without whisper timings.
            ltTimed = true;
            var total = 0, next = 0, received = 0, failed = 0;
            async function textWorker() {
                for (;;) {
                    var i = next++;
                    if (total > 0 && i >= total) return;
                    var data = null;
                    for (var attempt = 0; attempt < 2; attempt++) {
                        try {
                            var r = await fetch(apiUrl('translate_document.php')
                                + '?document_id=' + encodeURIComponent(docId)
                                + '&to=' + encodeURIComponent(lang)
                                + '&from=' + encodeURIComponent(ltFromVal())
                                + '&segment=' + i, { headers: { 'Accept': 'application/json' } });
                            var d = await r.json();
                            if (!d.error) { data = d; break; }
                        } catch (err) { /* retry */ }
                    }
                    if (run !== ltRun) return;
                    if (data === null) {
                        failed++;
                        if (total === 0 || (failed + received) >= total) return;
                        continue;
                    }
                    if (total === 0) {
                        total = data.total || 1;
                        for (var k = 0; k < total; k++) {
                            ltSegs[k] = { est: true, i: k, total: total, start: 0, end: 0, text: null };
                            var p = document.createElement('p');
                            p.className = 'lt-segment lt-pending';
                            p.textContent = '…';
                            ltBox.appendChild(p);
                            ltLines[k] = p;
                        }
                    }
                    received++;
                    ltSegs[i].text = data.translation;
                    ltText += data.translation + '\n\n';
                    renderSubtitles();
                    ltStatus.textContent = sprintf(t('lt_progress'), received, total);
                    ltStatus.className = 'small text-muted';
                    if (received + failed >= total) return;
                }
            }
            var workers = [];
            for (var w = 0; w < 3; w++) workers.push(textWorker());
            await Promise.all(workers);
            if (run !== ltRun) return;
            if (received === 0) {
                ltStatus.textContent = t('lt_error');
                ltStatus.className = 'small text-danger';
            } else {
                ltStatus.textContent = sprintf(t('lt_done'), received)
                    + (failed > 0 ? ' (' + failed + ' ' + t('lt_error') + ')' : '');
                ltStatus.className = failed > 0 ? 'small text-warning' : 'small text-success';
                if (ltExports) ltExports.classList.remove('lt-disabled');
            }
        }

        audioModal.addEventListener('show.bs.modal', function (event) {
            var btn = event.relatedTarget;
            if (!btn) return;
            ltDocId = btn.getAttribute('data-document-id');
            ltDocTitle = btn.getAttribute('data-document-title') || '';
            audioPlayer.src = apiUrl('download_document.php')
                + '?document_id=' + encodeURIComponent(ltDocId)
                + '&inline=1';
            var titleEl = document.getElementById('doc-audio-title');
            if (titleEl) titleEl.textContent = ltDocTitle || titleEl.textContent;
            ltReset();
            if (window.jQuery && jQuery(ltLang).hasClass('select2-hidden-accessible')) {
                jQuery(ltLang).val('').trigger('change.select2');
            } else if (ltLang) {
                ltLang.value = '';
            }
        });
        audioModal.addEventListener('hidden.bs.modal', function () {
            audioPlayer.pause();
            audioPlayer.removeAttribute('src');
            audioPlayer.load();
            ltReset();
        });

        // Select2 wraps this select when the modal opens and fires 'change'
        // through jQuery — a native addEventListener never sees it. Bind via
        // jQuery so both native and Select2-driven changes reach the handler.
        if (ltLang) {
            var ltOnLang = function () {
                ltReset();
                if (ltLang.value && ltDocId) {
                    ltStatus.textContent = sprintf(t('lt_progress'), 1, '…');
                    ltStatus.className = 'small text-muted';
                    translateLive(ltDocId, ltLang.value, ltRun);
                }
            };
            if (window.jQuery) {
                jQuery(ltLang).on('change', ltOnLang);
            } else {
                ltLang.addEventListener('change', ltOnLang);
            }
        }

        // Source-language hint (Mooré…): re-run translation when it changes.
        if (ltFrom) {
            var ltOnFrom = function () {
                if (ltLang && ltLang.value && ltDocId) {
                    ltReset();
                    translateLive(ltDocId, ltLang.value, ltRun);
                }
            };
            if (window.jQuery) {
                jQuery(ltFrom).on('change', ltOnFrom);
            } else {
                ltFrom.addEventListener('change', ltOnFrom);
            }
        }

        // Rephrase the audio transcript: the server restarts from the document's
        // own transcript (the ASR of the sound), never from the translation.
        var ltReform = document.getElementById('doc-audio-reformulate');
        if (ltReform) {
            ltReform.addEventListener('click', async function () {
                if (!ltDocId) { return; }
                ltReform.disabled = true;
                ltStatus.textContent = t('reformulating', 'Reformulation…');
                ltStatus.className = 'small text-muted';
                try {
                    var response = await fetch(apiUrl('reformulate.php'), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            document_id: ltDocId,
                            to: ltLang ? ltLang.value : '',
                            from: ltFrom ? ltFrom.value : '',
                            previous: ltText
                        })
                    });
                    var data = await response.json();
                    if (data.text) {
                        ltText = data.text;
                        var box = document.getElementById('doc-audio-translation');
                        if (box) {
                            box.textContent = data.text;
                            box.hidden = false;
                        }
                        ltStatus.textContent = t('reformulated', 'Nouvelle formulation.');
                        ltStatus.className = 'small text-success';
                    } else {
                        ltStatus.textContent = data.error || t('reformulate_failed', 'Reformulation impossible.');
                        ltStatus.className = 'small text-danger';
                    }
                } catch (err) {
                    ltStatus.textContent = t('reformulate_failed', 'Reformulation impossible.');
                    ltStatus.className = 'small text-danger';
                } finally {
                    ltReform.disabled = false;
                }
            });
        }
        if (ltExports) ltExports.querySelectorAll('.doc-audio-export').forEach(function (btn) {
            btn.addEventListener('click', async function () {
                var format = btn.getAttribute('data-format');
                try {
                    var response = await fetch(apiUrl('export.php'), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            title: ltDocTitle + ' — ' + ltLang.options[ltLang.selectedIndex].text,
                            content: ltText.trim(),
                            format: format
                        })
                    });
                    if (!response.ok) throw new Error('export');
                    var blob = await response.blob();
                    var url = URL.createObjectURL(blob);
                    var link = document.createElement('a');
                    link.href = url;
                    link.download = 'traduction-' + ltDocId + '.' + format;
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                    URL.revokeObjectURL(url);
                } catch (err) {
                    ltStatus.textContent = t('export_failed', 'Export impossible.');
                    ltStatus.className = 'small text-danger';
                }
            });
        });
    }

    bindSummarizeButtons();
    bindDeleteButtons();
    bindDocConfirm();
})();
