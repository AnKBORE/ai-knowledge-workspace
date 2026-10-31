<?php

declare(strict_types=1);

/** @var bool $isFr */
$isFr = $isFr ?? true;
?>
<!-- Meeting recorder: the transcript is produced when the recording stops -->
<div class="modal fade doc-modal" id="live-rec-modal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content ltr-modal">
            <div class="modal-header ltr-head">
                <div class="ltr-mic-badge" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 0 0-3 3v6a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/><path d="M19 10v1a7 7 0 0 1-14 0v-1"/><path d="M12 18v4"/></svg>
                </div>
                <div class="ltr-head-text">
                    <p class="ltr-kicker"><?= e($isFr ? 'Enregistrement de réunion' : 'Meeting recording') ?></p>
                    <h5 class="ltr-title" id="live-rec-title"><?= e($P['recorder_title'] ?? 'Recording') ?></h5>
                </div>
                <div class="ltr-eq" aria-hidden="true"><span></span><span></span><span></span><span></span><span></span></div>
                <button type="button" class="btn-close btn-close-white ltr-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body ltr-body">
                <div class="ltr-recordbar">
                    <span class="ltr-rec-badge"><span class="live-rec-dot" aria-hidden="true"></span>REC</span>
                    <span id="live-rec-timer" class="ltr-timer">00:00</span>
                    <div class="ltr-meter" aria-hidden="true">
                        <span id="live-rec-level" class="ltr-meter-fill"></span>
                    </div>
                </div>

                <div class="ltr-fields">
                    <div class="ltr-field">
                        <label for="live-rec-from" class="ltr-label"><?= e($isFr ? 'Langue parlée' : 'Spoken language') ?></label>
                        <select id="live-rec-from" class="form-select ltr-select">
                            <option value="" selected><?= e($isFr ? 'Détection automatique' : 'Automatic detection') ?></option>
                            <option value="fr">Français</option>
                            <option value="mo">Mooré</option>
                            <option value="en">English</option>
                        </select>
                        <p class="ltr-field-hint"><?= e($isFr ? 'Auto suffit pour le français et l’anglais. Indiquez Mooré pour une meilleure restitution.' : 'Auto is enough for French and English. Pick Mooré for a better result.') ?></p>
                    </div>
                    <div class="ltr-field">
                        <label for="live-rec-lang" class="ltr-label"><?= e($isFr ? 'Sortie' : 'Output') ?></label>
                        <select id="live-rec-lang" class="form-select ltr-select">
                            <option value=""><?= e($isFr ? 'Même langue (transcription)' : 'Same language (transcript)') ?></option>
                            <option value="fr" selected>Français</option>
                            <option value="en">English</option>
                            <option value="mo">Mooré</option>
                        </select>
                        <p class="ltr-field-hint"><?= e($isFr ? 'Autre langue = traduction du même enregistrement.' : 'Another language = translation of the same recording.') ?></p>
                    </div>
                </div>

                <p class="ltr-note">
                    <?= e($isFr
                        ? 'La transcription est générée à la fin de l’enregistrement : parlez normalement, puis appuyez sur le bouton d’arrêt.'
                        : 'The transcript is generated when the recording ends: speak normally, then press stop.') ?>
                </p>

                <p id="live-rec-state" class="ltr-state" hidden></p>

                <div id="live-rec-final" class="ltr-final" hidden>
                    <div class="ltr-final-head">
                        <p class="ltr-final-title"><?= e($isFr ? 'Transcription' : 'Transcript') ?></p>
                        <div class="ltr-final-actions">
                            <button type="button" class="ltr-act" id="live-rec-reformulate"
                                    title="<?= e($isFr ? 'Reformuler depuis l’audio' : 'Rephrase from the audio') ?>"
                                    aria-label="<?= e($isFr ? 'Reformuler depuis l’audio' : 'Rephrase from the audio') ?>">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-2.64-6.36M21 3v6h-6"/></svg>
                            </button>
                            <button type="button" class="ltr-act" id="live-rec-docx"
                                    title="<?= e($isFr ? 'Télécharger en DOCX' : 'Download as DOCX') ?>" aria-label="DOCX">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8"/></svg>
                            </button>
                            <button type="button" class="ltr-act" id="live-rec-pdf"
                                    title="<?= e($isFr ? 'Télécharger en PDF' : 'Download as PDF') ?>" aria-label="PDF">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12m0 0 4-4m-4 4-4-4"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
                            </button>
                        </div>
                    </div>
                    <div id="live-rec-final-text" class="ltr-transcript"></div>
                </div>
            </div>

            <div class="modal-footer ltr-footer">
                <button type="button" class="ltr-btn ltr-btn-ghost" id="live-rec-pause" title="<?= e($isFr ? 'Pause' : 'Pause') ?>" aria-label="Pause">
                    <svg id="live-rec-pause-ic" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 4H6v16h4zM18 4h-4v16h4z"/></svg>
                </button>
                <button type="button" class="ltr-btn ltr-btn-stop" id="live-rec-stop" title="<?= e($isFr ? 'Arrêter et transcrire' : 'Stop and transcribe') ?>" aria-label="Stop">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><rect x="6" y="6" width="12" height="12" rx="2"/></svg>
                </button>
            </div>
        </div>
    </div>
</div>
