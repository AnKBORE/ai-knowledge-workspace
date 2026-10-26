<?php

declare(strict_types=1);
?>
<section class="dashboard-hero">
    <div class="dashboard-hero-copy">
        <p class="dashboard-eyebrow">Espace de connaissances <span aria-hidden="true">/</span> Ajouter un document</p>
        <h1>Ajouter un document</h1>
        <p>Indexez un texte, un PDF, une image ou un audio : le document devient interrogeable par l'assistant.</p>
    </div>
    <div class="dashboard-hero-actions">
        <span class="dashboard-date"><?= e(date('d/m/Y')) ?></span>
    </div>
</section>

<div class="row g-4">
    <div class="col-lg-8 mx-auto">
        <section class="panel">
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">Nouveau document</h2>
                    <p class="panel-sub">Texte, PDF, image ou audio — 20 Mio max.</p>
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
    </div>
</div>
