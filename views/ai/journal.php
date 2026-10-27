<?php

declare(strict_types=1);

/** @var array<int, array<string, mixed>> $entries */
$labels = [
    'succes' => 'success',
    'refus' => 'danger',
    'erreur' => 'warning',
];
?>
<section class="dashboard-hero">
    <div class="dashboard-hero-copy">
        <p class="dashboard-eyebrow">Espace de connaissances <span aria-hidden="true">/</span> Journal</p>
        <h1>Journal d'audit</h1>
        <p>Traçabilité des actions : connexions, dépôts de documents, consultations, résumés, questions, refus de permission et suppressions. Le journal est en <strong>ajout seul</strong>.</p>
    </div>
    <div class="dashboard-hero-actions">
        <span class="dashboard-date"><?= e(date('d/m/Y')) ?></span>
    </div>
</section>

<div class="row g-4">
    <div class="col-lg-10 mx-auto">
        <div class="space-admin">
            <section class="panel space-list-panel technical-admin-panel" aria-labelledby="journal-title">
                <div class="panel-head">
                    <div>
                        <p class="page-eyebrow">Traçabilité</p>
                        <h2 class="panel-title" id="journal-title">Dernières opérations <span class="technical-admin-count"><?= count($entries) ?></span></h2>
                        <p class="panel-sub" id="journal-help">Connexions, dépôts, consultations, résumés, questions et suppressions — recherche, tri et pagination par lots de 50.</p>
                    </div>
                    <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/api/journal_export.php')) ?>">Exporter CSV</a>
                </div>

                <?php if (empty($entries)): ?>
                    <p class="technical-admin-empty">Journal vide : les actions seront tracées ici au fur et à mesure.</p>
                <?php else: ?>
                    <div class="space-table-wrap">
                        <table class="spaces-data-table technical-admin-table table table-hover align-middle w-100" id="journal-table" aria-describedby="journal-help">
                            <thead>
                                <tr>
                                    <th scope="col">N°</th>
                                    <th scope="col">Date</th>
                                    <th scope="col">Acteur</th>
                                    <th scope="col">Action</th>
                                    <th scope="col">Objet</th>
                                    <th scope="col">Détail</th>
                                    <th scope="col">Résultat</th>
                                    <th scope="col">IP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($entries as $index => $entry): ?>
                                    <?php $tone = $labels[(string) ($entry['resultat'] ?? '')] ?? 'secondary'; ?>
                                    <tr>
                                        <td class="table-row-number"><?= (int) $index + 1 ?></td>
                                        <td class="small text-muted" data-order="<?= e((string) ($entry['horodatage'] ?? '')) ?>"><?= e($entry['horodatage'] ?? '') ?></td>
                                        <td>
                                            <?= e(trim((string) ($entry['prenom'] ?? '') . ' ' . (string) ($entry['nom'] ?? '')) ?: 'Système') ?>
                                        </td>
                                        <td><code><?= e($entry['action'] ?? '') ?></code></td>
                                        <td class="small">
                                            <?= e($entry['objet_type'] ?? '') ?>
                                            <?= $entry['objet_id'] !== null ? '#' . (int) $entry['objet_id'] : '' ?>
                                        </td>
                                        <td class="small text-break">
                                            <?php
                                            $details = json_decode((string) ($entry['details'] ?? ''), true);
                                            if (is_array($details)) {
                                                $parts = [];
                                                foreach ($details as $key => $value) {
                                                    if (is_scalar($value)) {
                                                        $parts[] = $key . ' : ' . (is_bool($value) ? ($value ? 'oui' : 'non') : $value);
                                                    }
                                                }
                                                echo e(implode(' · ', $parts));
                                            }
                                            ?>
                                        </td>
                                        <td><span class="badge text-bg-<?= e($tone) ?>"><?= e($entry['resultat'] ?? '') ?></span></td>
                                        <td class="small text-muted"><?= e($entry['adresse_ip'] ?? '') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <p class="technical-admin-note technical-admin-footer">Chaque action est horodatée, associée à son auteur et conservée en ajout seul — rien n'est modifiable ni supprimable.</p>
            </section>
        </div>
    </div>
</div>

<link rel="stylesheet" href="<?= e(url('/assets/vendor/datatables/css/dataTables.bootstrap5.min.css')) ?>">
<link rel="stylesheet" href="<?= e(url('/assets/vendor/datatables/css/responsive.bootstrap5.min.css')) ?>">
<link rel="stylesheet" href="<?= e(url('/assets/css/spaces.css')) ?>">
<script src="<?= e(url('/assets/vendor/datatables/js/dataTables.min.js')) ?>"></script>
<script src="<?= e(url('/assets/vendor/datatables/js/dataTables.bootstrap5.min.js')) ?>"></script>
<script src="<?= e(url('/assets/vendor/datatables/js/dataTables.responsive.min.js')) ?>"></script>
<script src="<?= e(url('/assets/vendor/datatables/js/responsive.bootstrap5.min.js')) ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof DataTable === 'undefined') {
        return;
    }
    new DataTable('#journal-table', {
        pageLength: 50,
        order: [[1, 'desc']],
        responsive: true,
        language: {
            search: 'Rechercher :',
            lengthMenu: 'Afficher _MENU_ entrées',
            info: 'Affichage de _START_ à _END_ sur _TOTAL_ résultats',
            infoEmpty: 'Aucun résultat',
            infoFiltered: '(filtré sur _MAX_ entrées)',
            zeroRecords: 'Aucun résultat',
            paginate: { first: '«', previous: '‹', next: '›', last: '»' }
        }
    });
});
</script>
