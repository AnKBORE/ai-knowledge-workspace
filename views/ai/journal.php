<?php

declare(strict_types=1);

/** @var array<int, array<string, mixed>> $entries */
/** @var string $lang */
$T = app_i18n($lang ?? 'en');
$S = $T['shared'];
$P = $T['journal'];
$labels = [
    'succes' => 'success',
    'refus' => 'danger',
    'erreur' => 'warning',
];
?>
<section class="dashboard-hero">
    <div class="dashboard-hero-copy">
        <p class="dashboard-eyebrow"><?= e($S['eyebrow_root']) ?> <span aria-hidden="true">/</span> <?= e($T['pages']['/journal']) ?></p>
        <h1><?= e($T['pages']['/journal']) ?></h1>
        <p><?= e($P['hero_sub']) ?></p>
    </div>
    <div class="dashboard-hero-actions">
        <span class="dashboard-date"><?= e(lang_date($lang ?? 'en')) ?></span>
    </div>
</section>

<div class="row g-4">
    <div class="col-12">
        <div class="space-admin">
            <section class="panel space-list-panel technical-admin-panel panel-accent-slate" aria-labelledby="journal-title">
                <div class="panel-head">
                    <div>
                        <p class="page-eyebrow"><?= e($P['eyebrow']) ?></p>
                        <h2 class="panel-title" id="journal-title"><?= e($P['panel_title']) ?> <span class="technical-admin-count"><?= count($entries) ?></span></h2>
                        <p class="panel-sub" id="journal-help"><?= e($P['panel_sub']) ?></p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#journal-purge-modal">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M6 7l1 13a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-13"/><path d="M10 11v6M14 11v6"/></svg>
                            <?= e($P['purge']) ?>
                        </button>
                        <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/api/journal_export.php')) ?>"><?= e($P['export']) ?></a>
                    </div>
                </div>

                <?php if (empty($entries)): ?>
                    <p class="technical-admin-empty"><?= e($P['empty']) ?></p>
                <?php else: ?>
                    <div class="space-table-wrap">
                        <table class="spaces-data-table technical-admin-table journal-table table table-hover align-middle w-100" id="journal-table" aria-describedby="journal-help">
                            <thead>
                                <tr>
                                    <th scope="col"><?= e($P['th_num']) ?></th>
                                    <th scope="col"><?= e($P['th_date']) ?></th>
                                    <th scope="col"><?= e($P['th_actor']) ?></th>
                                    <th scope="col"><?= e($P['th_action']) ?></th>
                                    <th scope="col"><?= e($P['th_object']) ?></th>
                                    <th scope="col"><?= e($P['th_detail']) ?></th>
                                    <th scope="col"><?= e($P['th_result']) ?></th>
                                    <th scope="col"><?= e($P['th_ip']) ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($entries as $index => $entry): ?>
                                    <?php $tone = $labels[(string) ($entry['resultat'] ?? '')] ?? 'secondary'; ?>
                                    <tr>
                                        <td class="table-row-number dtr-control">
                                            <button class="dtr-control-btn" type="button" aria-label="<?= e($P['expand_row']) ?>" aria-expanded="false"><span aria-hidden="true">+</span></button>
                                            <span class="row-number-value"><?= (int) $index + 1 ?></span>
                                        </td>
                                        <td class="small text-muted" data-order="<?= e((string) ($entry['horodatage'] ?? '')) ?>"><?= e($entry['horodatage'] ?? '') ?></td>
                                        <td>
                                            <?= e(trim((string) ($entry['prenom'] ?? '') . ' ' . (string) ($entry['nom'] ?? '')) ?: $P['system']) ?>
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

                <p class="technical-admin-note technical-admin-footer"><?= e($P['footer']) ?></p>
            </section>
        </div>
    </div>
</div>

<div class="modal fade space-create-modal profil-modal" id="journal-purge-modal" tabindex="-1" aria-labelledby="journal-purge-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered profil-modal-dialog">
        <div class="modal-content">
            <form class="space-form" id="journal-purge-form" action="<?= e(url('/api/journal_purge.php')) ?>">
                <div class="modal-header space-create-header text-bg-danger border-bottom border-2 border-danger">
                    <span class="space-create-head-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 16.5v.01"/><path d="M10.3 3.6 2.6 17a2 2 0 0 0 1.7 3h15.4a2 2 0 0 0 1.7-3L13.7 3.6a2 2 0 0 0-3.4 0Z"/></svg>
                    </span>
                    <div class="space-create-head-copy">
                        <p class="space-modal-eyebrow"><?= e($P['purge_eyebrow']) ?></p>
                        <h2 class="modal-title" id="journal-purge-title"><?= e($P['purge_title']) ?></h2>
                        <p><?= e($P['purge_sub']) ?></p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="<?= e($S['close']) ?>"></button>
                </div>
                <div class="modal-body space-create-body">
                    <div class="space-create-fields">
                        <label><?= e($P['purge_from']) ?>
                            <input id="journal-purge-debut" type="date" required>
                            <small class="field-hint"><?= e($P['purge_from_hint']) ?></small>
                        </label>
                        <label><?= e($P['purge_to']) ?>
                            <input id="journal-purge-fin" type="date">
                            <small class="field-hint"><?= e($P['purge_to_hint']) ?></small>
                        </label>
                        <label class="profil-field-full"><?= e($S['password_label']) ?>
                            <input id="journal-purge-password" type="password" required autocomplete="current-password" placeholder="<?= e($P['purge_password_ph']) ?>">
                        </label>
                    </div>
                    <p id="journal-purge-error" class="doc-delete-error" hidden></p>
                </div>
                <div class="modal-footer space-create-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= e($S['cancel']) ?></button>
                    <button type="submit" class="btn btn-danger space-create-submit" id="journal-purge-submit">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M6 7l1 13a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-13"/><path d="M10 11v6M14 11v6"/></svg>
                        <?= e($P['purge_btn']) ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    var form = document.getElementById('journal-purge-form');
    if (!form) { return; }
    var debut = document.getElementById('journal-purge-debut');
    var fin = document.getElementById('journal-purge-fin');
    var password = document.getElementById('journal-purge-password');
    var errorSlot = document.getElementById('journal-purge-error');

    var maxEnd = new Date();
    maxEnd.setDate(maxEnd.getDate() - 10);
    var maxEndStr = maxEnd.toISOString().slice(0, 10);
    debut.max = maxEndStr;
    fin.max = maxEndStr;
    fin.value = maxEndStr;

    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        errorSlot.hidden = true;

        try {
            var response = await fetch(form.action, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    date_debut: debut.value,
                    date_fin: fin.value,
                    password: password.value
                })
            });
            var data = await response.json();
            if (data.error) {
                errorSlot.textContent = data.error;
                errorSlot.hidden = false;
                password.select();
                return;
            }
            window.alert(<?= json_encode($P['purge_done'], JSON_UNESCAPED_UNICODE) ?>.replace('%d', data.deleted));
            window.location.reload();
        } catch (err) {
            errorSlot.textContent = <?= json_encode($P['network_error'], JSON_UNESCAPED_UNICODE) ?>;
            errorSlot.hidden = false;
        }
    });
})();
</script>

<link rel="stylesheet" href="<?= e(url('/assets/vendor/datatables/css/dataTables.bootstrap5.min.css')) ?>">
<link rel="stylesheet" href="<?= e(url('/assets/vendor/datatables/css/responsive.bootstrap5.min.css')) ?>">
<link rel="stylesheet" href="<?= e(url('/assets/css/spaces.css')) ?>">
<script src="<?= e(url('/assets/vendor/jquery/jquery.min.js')) ?>"></script>
<script src="<?= e(url('/assets/vendor/datatables/js/dataTables.min.js')) ?>"></script>
<script src="<?= e(url('/assets/vendor/datatables/js/dataTables.bootstrap5.min.js')) ?>"></script>
<script src="<?= e(url('/assets/vendor/datatables/js/dataTables.responsive.min.js')) ?>"></script>
<script src="<?= e(url('/assets/vendor/datatables/js/responsive.bootstrap5.min.js')) ?>"></script>
<script src="<?= e(url('/assets/js/spaces-tables.js')) ?>"></script>
