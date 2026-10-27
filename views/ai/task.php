<?php

declare(strict_types=1);
?>
<section class="dashboard-hero">
    <div class="dashboard-hero-copy">
        <p class="dashboard-eyebrow">Espace de connaissances <span aria-hidden="true">/</span> Tâche agentique</p>
        <h1>Tâche agentique</h1>
        <p>Rédigez une note ou un rapport à partir de vos documents : l'assistant structure le contenu et cite ses sources.</p>
    </div>
    <div class="dashboard-hero-actions">
        <span class="dashboard-date"><?= e(date('d/m/Y')) ?></span>
    </div>
</section>

<div class="row g-4">
    <div class="col-12">
        <div class="space-admin">
            <section class="panel space-list-panel technical-admin-panel" aria-labelledby="task-title">
                <div class="panel-head">
                    <div>
                        <p class="page-eyebrow">Assistant documentaire</p>
                        <h2 class="panel-title" id="task-title">Votre demande</h2>
                        <p class="panel-sub">Décrivez le document attendu (note, rapport, fiche pédagogique…). L'assistant structure le contenu et cite ses sources.</p>
                    </div>
                </div>
                <div class="panel-body">
                    <form id="task-form">
                        <label for="instruction" class="form-label">Instruction</label>
                        <textarea id="instruction" class="form-control" rows="4" placeholder="Ex. « Prépare une note pour le directeur sur les activités du trimestre »" required></textarea>
                        <button type="submit" class="btn btn-primary mt-3">Générer un brouillon</button>
                    </form>
                </div>
                <p class="technical-admin-note technical-admin-footer">Chaque demande est journalisée (mode, instruction, nombre de sources) et le document généré cite ses sources.</p>
            </section>
        </div>
    </div>
</div>

<?php require __DIR__ . '/_modal.php'; ?>
