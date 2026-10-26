<?php

declare(strict_types=1);

/** @var string $title */
/** @var array<int, array<string, mixed>> $documents */
?>
<section class="container py-5">
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h1 class="h4 mb-1">Assistant de connaissances</h1>
                    <p class="text-muted mb-4">Posez une question sur vos documents — l'assistant répond en citant ses sources.</p>

                    <form id="ask-form" class="mb-3">
                        <label for="question" class="form-label">Votre question</label>
                        <textarea id="question" class="form-control" rows="3" placeholder="Quels sont les principaux risques du dernier trimestre ?" required></textarea>
                        <button type="submit" class="btn btn-primary mt-3">Demander</button>
                    </form>

                    <div id="answer" class="border rounded-3 p-3 bg-light" hidden>
                        <strong class="d-block mb-2">Réponse</strong>
                        <div id="answer-text" class="mb-0"></div>
                    </div>
                    <div id="answer-error" class="alert alert-danger mt-3" hidden></div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-4">
                    <h2 class="h6 text-uppercase text-muted mb-3">Ajouter un document</h2>
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

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h2 class="h6 text-uppercase text-muted mb-3">Documents indexés</h2>
                    <?php if (empty($documents)): ?>
                        <p class="text-muted small mb-0">Aucun document pour l'instant.</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($documents as $doc): ?>
                                <li class="list-group-item px-0">
                                    <strong><?= e($doc['title'] ?? '') ?></strong>
                                    <span class="text-muted small d-block"><?= e($doc['filename'] ?? '') ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
(function () {
    const askForm = document.getElementById('ask-form');
    const answer = document.getElementById('answer');
    const answerText = document.getElementById('answer-text');
    const answerError = document.getElementById('answer-error');

    askForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        answer.hidden = true;
        answerError.hidden = true;
        answerError.textContent = '';

        const question = document.getElementById('question').value.trim();
        if (!question) { return; }

        try {
            const response = await fetch('<?= e(url('/api/ask.php')) ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ question: question })
            });
            const data = await response.json();
            if (data.error) {
                answerError.textContent = data.error;
                answerError.hidden = false;
                return;
            }
            answerText.textContent = data.answer || '';
            answer.hidden = false;
        } catch (err) {
            answerError.textContent = 'Erreur réseau. Vérifiez le serveur.';
            answerError.hidden = false;
        }
    });

    const uploadForm = document.getElementById('upload-form');
    const uploadStatus = document.getElementById('upload-status');

    uploadForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        uploadStatus.textContent = 'Envoi en cours…';
        uploadStatus.className = 'small text-muted mt-2';

        try {
            const response = await fetch('<?= e(url('/api/upload.php')) ?>', {
                method: 'POST',
                body: new FormData(uploadForm)
            });
            const data = await response.json();
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
})();
</script>
