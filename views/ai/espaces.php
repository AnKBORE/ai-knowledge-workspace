<?php

declare(strict_types=1);

/**
 * Espaces — the workspaces the user belongs to.
 *
 * Same visual language as the indexed-documents page: dashboard hero, one
 * panel with a head and a body, DataTables inventory with row numbers,
 * doc-item-badge pills and doc-action icon buttons.
 *
 * @var array<int, array<string, mixed>> $mesEspaces
 * @var int|null $espaceActif
 * @var bool $canCreateSpace
 * @var string $lang
 */

$T = app_i18n($lang ?? 'en');
$S = $T['shared'];
$isFr = ($lang ?? 'en') === 'fr';
$t = static fn (string $fr, string $en): string => $isFr ? $fr : $en;

$expandLabel = $t('Afficher les informations complémentaires', 'Show additional details');
$workTitle = $t('Travailler ici', 'Work here');
?>
<section class="dashboard-hero">
    <div class="dashboard-hero-copy">
        <p class="dashboard-eyebrow"><?= e($S['eyebrow_root']) ?> <span aria-hidden="true">/</span> <?= e($t('Espaces', 'Workspaces')) ?></p>
        <h1><?= e($t('Mes espaces de travail', 'My workspaces')) ?></h1>
        <p><?= e($t(
            'Choisissez l\'espace dans lequel vous travaillez : les documents et les dépôts suivent ce choix.',
            'Choose the workspace you are working in: documents and deposits follow that choice.'
        )) ?></p>
    </div>
    <div class="dashboard-hero-actions">
        <span class="dashboard-date"><?= e(lang_date($lang ?? 'en')) ?></span>
    </div>
</section>

<div class="row g-4">
    <div class="col-12">
        <div class="space-admin">
            <section class="panel space-list-panel technical-admin-panel panel-accent-blue" aria-labelledby="espaces-title">
                <div class="panel-head">
                    <div>
                        <p class="page-eyebrow"><?= e($t('Espace actif', 'Active workspace')) ?></p>
                        <h2 class="panel-title" id="espaces-title">
                            <?php if ($espaceActif === null): ?>
                                <?= e($t('Aucun espace sélectionné', 'No workspace selected')) ?>
                            <?php else: ?>
                                <?php
                                $nomActif = $t('Espace inconnu', 'Unknown workspace');
                                foreach (($mesEspaces ?? []) as $es) {
                                    if ((int) $es['id'] === $espaceActif) {
                                        $nomActif = (string) $es['nom'];
                                    }
                                }
                                ?>
                                <?= e($nomActif) ?>
                            <?php endif; ?>
                            <span class="technical-admin-count"><?= count($mesEspaces ?? []) ?></span>
                        </h2>
                        <p class="panel-sub"><?= e($t(
                            'Aucun espace sélectionné — vous voyez tout ce à quoi vous avez droit.',
                            'No workspace selected — you see everything you are allowed to see.'
                        )) ?></p>
                    </div>
                    <?php if ($espaceActif !== null): ?>
                        <form method="post" action="<?= e(url('/api/espace_actif.php')) ?>">
                            <?= $csrf?->field() ?? '' ?>
                            <input type="hidden" name="espace_id" value="0">
                            <button type="submit" class="btn btn-sm btn-outline-secondary"><?= e($t('Quitter l\'espace', 'Leave the workspace')) ?></button>
                        </form>
                    <?php endif; ?>
                </div>

                <?php if (empty($mesEspaces)): ?>
                    <p class="technical-admin-empty"><?= e($t(
                        'Vous n\'êtes membre d\'aucun espace. Demandez à un administrateur de vous y ajouter.',
                        'You are not a member of any workspace. Ask an administrator to add you.'
                    )) ?></p>
                <?php else: ?>
                    <div class="space-table-wrap">
                        <table class="spaces-data-table technical-admin-table admin-inventory-table espaces-table table table-hover align-middle w-100">
                            <thead>
                                <tr>
                                    <th scope="col">N°</th>
                                    <th scope="col"><?= e($t('Espace', 'Workspace')) ?></th>
                                    <th scope="col"><?= e($t('Visibilité', 'Visibility')) ?></th>
                                    <th scope="col"><?= e($t('Statut', 'Status')) ?></th>
                                    <th scope="col"><?= e($t('Documents', 'Documents')) ?></th>
                                    <th scope="col" class="text-end"><?= e($t('Action', 'Action')) ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mesEspaces as $es): ?>
                                    <?php $estActif = (int) $es['id'] === $espaceActif; ?>
                                    <tr>
                                        <td class="table-row-number dtr-control">
                                            <button class="dtr-control-btn" type="button" aria-label="<?= e($expandLabel) ?>" aria-expanded="false"><span aria-hidden="true">+</span></button>
                                            <span class="row-number-value"></span>
                                        </td>
                                        <td><strong><?= e((string) $es['nom']) ?></strong></td>
                                        <td><span class="doc-item-badge"><?= e((string) $es['visibilite']) ?></span></td>
                                        <td class="small">
                                            <span class="badge <?= ($es['statut'] ?? 'actif') === 'actif' ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                                <?= e((string) ($es['statut'] ?? 'actif')) ?>
                                            </span>
                                        </td>
                                        <td><span class="doc-item-badge"><?= (int) ($es['documents'] ?? 0) ?></span></td>
                                        <td class="text-end">
                                            <?php if ($estActif): ?>
                                                <span class="badge text-bg-primary"><?= e($t('Espace actif', 'Active')) ?></span>
                                            <?php else: ?>
                                                <form method="post" action="<?= e(url('/api/espace_actif.php')) ?>" class="d-inline">
                                                    <?= $csrf?->field() ?? '' ?>
                                                    <input type="hidden" name="espace_id" value="<?= (int) $es['id'] ?>">
                                                    <button type="submit" class="doc-action" title="<?= e($workTitle) ?>" aria-label="<?= e($workTitle) ?>">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m10 8 5 4-5 4V8z"/></svg>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <p class="technical-admin-note technical-admin-footer"><?= e($t(
                    'L\'espace actif filtre la liste des documents et la destination de vos dépôts.',
                    'The active workspace filters the document list and the destination of your uploads.'
                )) ?></p>
            </section>
        </div>
    </div>
</div>

<link rel="stylesheet" href="<?= e(asset('/assets/css/spaces.css')) ?>">
<script src="<?= e(url('/assets/vendor/jquery/jquery.min.js')) ?>"></script>
<script src="<?= e(url('/assets/vendor/datatables/js/dataTables.min.js')) ?>"></script>
<script src="<?= e(url('/assets/vendor/datatables/js/dataTables.bootstrap5.min.js')) ?>"></script>
<script src="<?= e(url('/assets/vendor/datatables/js/dataTables.responsive.min.js')) ?>"></script>
<script src="<?= e(url('/assets/vendor/datatables/js/responsive.bootstrap5.min.js')) ?>"></script>
<script src="<?= e(asset('/assets/js/spaces-tables.js')) ?>"></script>
