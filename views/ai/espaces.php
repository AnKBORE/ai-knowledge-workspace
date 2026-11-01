<?php

declare(strict_types=1);

/**
 * Espaces — the workspaces the user belongs to.
 *
 * @var array<int, array<string, mixed>> $mesEspaces
 * @var int|null $espaceActif
 * @var bool $canCreateSpace
 * @var string $lang
 */

$isFr = ($lang ?? 'en') === 'fr';
$t = static fn (string $fr, string $en): string => $isFr ? $fr : $en;
?>
<section class="dashboard-hero">
    <div class="dashboard-hero-copy">
        <p class="dashboard-eyebrow"><?= e(app_i18n($lang ?? 'en')['shared']['eyebrow_root']) ?> <span aria-hidden="true">/</span> <?= e($t('Espaces', 'Workspaces')) ?></p>
        <h1><?= e($t('Mes espaces de travail', 'My workspaces')) ?></h1>
        <p><?= e($t(
            'Choisissez l\'espace dans lequel vous travaillez : les documents et les dépôts suivent ce choix.',
            'Choose the workspace you are working in: documents and deposits follow that choice.'
        )) ?></p>
    </div>
</section>

<div class="row g-4">
    <div class="col-12">
        <section class="panel technical-admin-panel">
            <div class="panel-head">
                <div>
                    <p class="page-eyebrow"><?= e($t('Espace actif', 'Active workspace')) ?></p>
                    <h2 class="panel-title">
                        <?php if ($espaceActif === null): ?>
                            <?= e($t('Aucun espace sélectionné — vous voyez tout ce à quoi vous avez droit.', 'No workspace selected — you see everything you are allowed to see.')) ?>
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
                    </h2>
                </div>
                <?php if ($espaceActif !== null): ?>
                    <form method="post" action="<?= e(url('/api/espace_actif.php')) ?>">
                        <?= $csrf?->field() ?? '' ?>
                        <input type="hidden" name="espace_id" value="0">
                        <button type="submit" class="btn btn-sm btn-outline-secondary"><?= e($t('Quitter l\'espace', 'Leave the workspace')) ?></button>
                    </form>
                <?php endif; ?>
            </div>
            <div class="panel-body">
                <?php if (empty($mesEspaces)): ?>
                    <p class="technical-admin-empty"><?= e($t(
                        'Vous n\'êtes membre d\'aucun espace. Demandez à un administrateur de vous y ajouter.',
                        'You are not a member of any workspace. Ask an administrator to add you.'
                    )) ?></p>
                <?php else: ?>
                    <div class="space-table-wrap">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th scope="col"><?= e($t('Espace', 'Workspace')) ?></th>
                                    <th scope="col"><?= e($t('Visibilité', 'Visibility')) ?></th>
                                    <th scope="col"><?= e($t('Statut', 'Status')) ?></th>
                                    <th scope="col"><?= e($t('Documents', 'Documents')) ?></th>
                                    <th scope="col"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mesEspaces as $es): ?>
                                    <?php $estActif = (int) $es['id'] === $espaceActif; ?>
                                    <tr>
                                        <td><strong><?= e((string) $es['nom']) ?></strong></td>
                                        <td class="small text-muted"><?= e((string) $es['visibilite']) ?></td>
                                        <td class="small">
                                            <span class="badge <?= ($es['statut'] ?? 'actif') === 'actif' ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                                <?= e((string) ($es['statut'] ?? 'actif')) ?>
                                            </span>
                                        </td>
                                        <td class="small"><?= (int) ($es['documents'] ?? 0) ?></td>
                                        <td class="text-end">
                                            <?php if ($estActif): ?>
                                                <span class="badge text-bg-primary"><?= e($t('Espace actif', 'Active')) ?></span>
                                            <?php else: ?>
                                                <form method="post" action="<?= e(url('/api/espace_actif.php')) ?>" class="d-inline">
                                                    <?= $csrf?->field() ?? '' ?>
                                                    <input type="hidden" name="espace_id" value="<?= (int) $es['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-primary"><?= e($t('Travailler ici', 'Work here')) ?></button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
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
