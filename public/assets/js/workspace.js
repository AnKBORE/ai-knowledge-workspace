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
            } else if (step.step === 'verify') {
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
        resultContent.innerHTML = escapeThenBold(content);
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
    if (retryBtn) {
        retryBtn.addEventListener('click', async function () {
            if (lastRequest === null) { return; }
            showLoading(lastRequest.badge, lastRequest.badge, lastRequest.loading);
            try {
                var data = await postJson(apiUrl('ask.php'), lastRequest.payload);
                if (data.error) { showError(data.error); return; }
                showResult(lastRequest.badge, lastRequest.badge, lastRequest.sub,
                    data.answer || data.draft || '', data.sources, data.steps);
            } catch (err) {
                showError(t('network_error_long'));
            }
        });
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

            lastRequest = {
                payload: { instruction: instruction, mode: 'task' },
                badge: t('draft_badge'), loading: t('draft_loading'), sub: t('draft_sub')
            };
            showLoading(t('draft_badge'), t('draft_badge'), t('draft_loading'));
            try {
                var data = await postJson(apiUrl('ask.php'), { instruction: instruction, mode: 'task' });
                if (data.error) { showError(data.error); return; }
                showResult(t('draft_badge'), t('draft_badge'), t('draft_sub'), data.draft || '', data.sources, data.steps);
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

        // Live translation: debounce typing so the result refreshes without
        // pressing the Translate button.
        var trDebounce = null;
        trSource.addEventListener('input', function () {
            clearTimeout(trDebounce);
            if (!trSource.value.trim()) {
                trResult.value = '';
                trCopy.hidden = true;
                trStatus.textContent = '';
                return;
            }
            trDebounce = setTimeout(function () { translateForm.requestSubmit(); }, 700);
        });

        translateForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            trStatus.textContent = t('translating', 'Translating…');
            trStatus.className = 'small mt-2 text-muted';
            try {
                var data = await postJson(apiUrl('translate.php'), {
                    text: document.getElementById('translate-source').value,
                    from: document.getElementById('translate-from').value,
                    to: document.getElementById('translate-to').value
                });
                if (data.error) {
                    trStatus.textContent = data.error;
                    trStatus.className = 'small mt-2 text-danger';
                    return;
                }
                trResult.value = data.translation || '';
                trStatus.textContent = '';
                trCopy.hidden = !trResult.value;
            } catch (err) {
                trStatus.textContent = t('network_error');
                trStatus.className = 'small mt-2 text-danger';
            }
        });

        trCopy.addEventListener('click', async function () {
            await navigator.clipboard.writeText(trResult.value);
            trCopy.textContent = t('copied', 'Copied');
            setTimeout(function () { trCopy.textContent = t('copy', 'Copy'); }, 1500);
        });
    }

    /* ---------------- Meeting recorder (microphone → WAV → upload) ---------------- */

    var recBtn = document.getElementById('record-btn');
    if (recBtn) {
        var recStop = document.getElementById('record-stop');
        var recTimer = document.getElementById('record-timer');
        var recStatus = document.getElementById('record-status');
        var mediaRecorder = null, streamRef = null, chunks = [], recInterval = null, recStart = 0;
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
            var s = Math.floor((Date.now() - recStart) / 1000);
            recTimer.textContent = String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0');
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
                chunks = [];
                mediaRecorder.ondataavailable = function (e) { if (e.data && e.data.size) { chunks.push(e.data); } };
                mediaRecorder.start();
                var track = streamRef.getAudioTracks()[0];
                if (track && track.muted) {
                    recStatus.textContent = t('rec_muted');
                    recStatus.className = 'small mt-2 text-warning';
                }
                recStart = Date.now();
                recInterval = setInterval(function () { recTick(); updateLevel(); }, 300);
                recBtn.hidden = true; recStop.hidden = false;
                recStatus.textContent = '';
            } catch (err) {
                recStatus.textContent = t('rec_mic');
                recStatus.className = 'small mt-2 text-danger';
            }
        });

        recStop.addEventListener('click', function () {
            clearInterval(recInterval);
            recBtn.hidden = false; recStop.hidden = true;
            var duration = recTimer.textContent;

            mediaRecorder.onstop = async function () {
                streamRef.getTracks().forEach(function (trk) { trk.stop(); });
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

    /* -------- Audio playback + live transcript translation in documents -------- */

    var audioModal = document.getElementById('doc-audio-modal');
    if (audioModal) {
        var audioPlayer = document.getElementById('doc-audio-player');
        var ltLang = document.getElementById('doc-audio-lang');
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
            if (ltExports) ltExports.hidden = true;
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
        function renderSubtitles() {
            if (!ltTimed) return;
            var pos = (audioPlayer.currentTime || 0) * 1000;
            for (var i = 0; i < ltSegs.length; i++) {
                var seg = ltSegs[i], line = ltLines[i];
                if (!seg || !line) continue;
                if (seg.text === null) {
                    line.textContent = '…';
                    line.className = 'lt-segment lt-pending';
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

        // Timed mode: whisper timestamps + per-segment Muse translations,
        // fetched in parallel batches so segments arrive fast, with one retry
        // per segment — a single failure never stops the stream.
        async function fetchTimedSegment(docId, lang, i) {
            for (var attempt = 0; attempt < 2; attempt++) {
                try {
                    var r = await fetch(apiUrl('translate_document.php')
                        + '?document_id=' + encodeURIComponent(docId)
                        + '&to=' + encodeURIComponent(lang)
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
            if (ltExports) ltExports.hidden = received === 0;
        }

        // Fetch and display the translation — timed (karaoke) when whisper
        // timestamps are available, otherwise progressive text segments.
        // ltRun guards against stale replies after a language change or close.
        async function translateLive(docId, lang, run) {
            if (!ltBox || !ltStatus) return;
            ltBox.hidden = false;
            ltStatus.textContent = t('lt_preparing');
            ltStatus.className = 'small text-muted';

            try {
                var probe = await fetch(apiUrl('translate_document.php')
                    + '?document_id=' + encodeURIComponent(docId)
                    + '&to=' + encodeURIComponent(lang) + '&mode=timed',
                    { headers: { 'Accept': 'application/json' } });
                var pd = await probe.json();
                if (run !== ltRun) return;
                if (pd.mode === 'timed' && pd.total > 0) {
                    await runTimed(docId, lang, run, pd.timings, pd.total);
                    return;
                }
            } catch (err) { /* fall through to text mode */ }

            // Text mode fallback (no timestamps available): parallel batches,
            // one retry per segment, failures never stop the stream.
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
                    if (total === 0) total = data.total || 1;
                    received++;
                    appendLtSegment(data.translation);
                    ltText += data.translation + '\n\n';
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
                if (ltExports) ltExports.hidden = false;
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
