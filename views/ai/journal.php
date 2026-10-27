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
        <p>Traçabilité des actions : connexions, dépôts de documents, questions, refus de permission et suppressions. Le journal est en <strong>ajout seul</strong>.</p>
    </div>
    <div class="dashboard-hero-actions">
        <span class="dashboard-date"><?= e(date('d/m/Y')) ?></span>
    </div>
</section>

<div class="row g-4">
    <div class="col-lg-10 mx-auto">
        <section class="panel">
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">Dernières opérations</h2>
                    <p class="panel-sub"><?= count($entries) ?> entrée<?= count($entries) > 1 ? 's' : '' ?></p>
                </div>
                <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/api/journal_export.php')) ?>">Exporter CSV</a>
            </div>
            <div class="panel-body">
                <?php if (empty($entries)): ?>
                    <div class="dashboard-empty">
                        <div class="dashboard-empty-copy">
                            <p class="dashboard-empty-label">Journal vide</p>
                            <p>Les actions seront tracées ici au fur et à mesure.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Date</th>
                                    <th scope="col">Acteur</th>
                                    <th scope="col">Action</th>
                                    <th scope="col">Objet</th>
                                    <th scope="col">Résultat</th>
                                    <th scope="col">IP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($entries as $entry): ?>
                                    <?php $tone = $labels[(string) ($entry['resultat'] ?? '')] ?? 'secondary'; ?>
                                    <tr>
                                        <td class="small text-muted"><?= e($entry['horodatage'] ?? '') ?></td>
                                        <td>
                                            <?= e(trim((string) ($entry['prenom'] ?? '') . ' ' . (string) ($entry['nom'] ?? '')) ?: 'Système') ?>
                                        </td>
                                        <td><code><?= e($entry['action'] ?? '') ?></code></td>
                                        <td class="small">
                                            <?= e($entry['objet_type'] ?? '') ?>
                                            <?= $entry['objet_id'] !== null ? '#' . (int) $entry['objet_id'] : '' ?>
                                        </td>
                                        <td><span class="badge text-bg-<?= e($tone) ?>"><?= e($entry['resultat'] ?? '') ?></span></td>
                                        <td class="small text-muted"><?= e($entry['adresse_ip'] ?? '') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>
