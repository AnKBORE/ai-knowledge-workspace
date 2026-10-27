<?php

declare(strict_types=1);

/** @var string $lang */
$isFr = $lang === 'fr';

$t = [
    'eyebrow' => $isFr ? 'IA documentaire · Hackathon Meta' : 'Document AI · Meta Hackathon',
    'hero_title' => $isFr ? "Votre mémoire d'entreprise, augmentée par l'IA" : 'Your company memory, augmented by AI',
    'hero_sub' => $isFr
        ? "Un espace de connaissances qui indexe vos documents, répond avec des sources vérifiables et rédige vos notes — sous gouvernance complète."
        : 'A knowledge workspace that indexes your documents, answers with verifiable sources and drafts your notes — under full governance.',
    'cta_demo' => $isFr ? 'Voir la démonstration' : 'See the demo',
    'cta_login' => $isFr ? 'Se connecter' : 'Sign in',
    'pill_1' => $isFr ? 'Réponses sourcées' : 'Sourced answers',
    'pill_2' => $isFr ? 'Tâches agentiques' : 'Agentic tasks',
    'pill_3' => $isFr ? 'Multimodal' : 'Multimodal',
    'pill_4' => $isFr ? 'Journal d’audit' : 'Audit trail',

    'problem_title' => $isFr ? 'Le problème' : 'The problem',
    'problem_text' => $isFr
        ? 'Les connaissances d’une organisation sont éparpillées : PDF, notes, scans, enregistrements. Les réponses sont lentes, non traçables et vite obsolètes.'
        : 'An organisation’s knowledge is scattered across PDFs, notes, scans and recordings. Answers are slow, untraceable and quickly outdated.',
    'solution_title' => $isFr ? 'La réponse' : 'The answer',
    'solution_text' => $isFr
        ? 'Un espace unique qui indexe, retrouve et explique : chaque réponse cite ses sources, chaque action est journalisée, chaque document est versionné.'
        : 'One workspace that indexes, retrieves and explains: every answer cites its sources, every action is logged, every document is versioned.',

    'features_title' => $isFr ? 'Ce que fait la plateforme' : 'What the platform does',
    'f1_title' => $isFr ? 'Recherche hybride et réponse citée' : 'Hybrid retrieval and cited answers',
    'f1_text' => $isFr ? 'Similarité vectorielle + recouvrement lexical, puis réponse strictement extractive avec ses extraits sources.' : 'Vector similarity + lexical overlap, then a strictly extractive answer with its source excerpts.',
    'f2_title' => $isFr ? 'Tâches agentiques' : 'Agentic tasks',
    'f2_text' => $isFr ? 'Notes, rapports et fiches structurées générés à partir des documents, avec citations.' : 'Notes, reports and structured briefs generated from documents, with citations.',
    'f3_title' => $isFr ? 'Multimodal' : 'Multimodal',
    'f3_text' => $isFr ? 'OCR des images et transcription audio par Muse Spark Open, puis indexation du texte extrait.' : 'Image OCR and audio transcription with Muse Spark Open, then indexing of the extracted text.',
    'f4_title' => $isFr ? 'Gouvernance' : 'Governance',
    'f4_text' => $isFr ? 'RBAC à deux niveaux, permissions par espace, journal d’audit en ajout seul.' : 'Two-level RBAC, per-space permissions, append-only audit journal.',
    'f5_title' => $isFr ? 'Versions immuables' : 'Immutable versions',
    'f5_text' => $isFr ? 'Chaque dépôt est daté, haché en SHA-256 et conservé : l’historique est complet.' : 'Every upload is dated, hashed with SHA-256 and kept: the history is complete.',
    'f6_title' => $isFr ? 'Export PDF et DOCX' : 'PDF and DOCX export',
    'f6_text' => $isFr ? 'Les résultats se téléchargent en PDF (dompdf) ou DOCX (OOXML natif), prêts à partager.' : 'Results download as PDF (dompdf) or DOCX (native OOXML), ready to share.',

    'stack_title' => $isFr ? 'Sous le capot' : 'Under the hood',
    'stack_text' => $isFr ? 'Un socle sobre, testé, sans dépendance superflue.' : 'A lean, tested foundation with no unnecessary dependencies.',
    'stack_php' => 'PHP 8.4 · MVC strict, PSR-4',
    'stack_mysql' => 'MySQL 8.4 · InnoDB, utf8mb4',
    'stack_model' => 'Muse Spark Open · Meta Model API',
    'stack_tests' => $isFr ? '57 tests · 122 assertions' : '57 tests · 122 assertions',
    'stack_security' => $isFr ? 'Argon2 · CSRF · CSP · audit' : 'Argon2 · CSRF · CSP · audit',

    'gov_title' => $isFr ? 'Gouvernance par conception' : 'Governance by design',
    'gov_1' => $isFr ? 'Rôles globaux : administrateur technique, fonctionnel, membre.' : 'Global roles: technical admin, functional admin, member.',
    'gov_2' => $isFr ? 'Rôles locaux par espace : lecteur, contributeur, animateur, responsable.' : 'Per-space roles: reader, contributor, facilitator, owner.',
    'gov_3' => $isFr ? 'Journal en ajout seul : rien n’échappe à la traçabilité.' : 'Append-only journal: nothing escapes traceability.',
    'gov_4' => $isFr ? 'Stockage hors racine web, empreintes SHA-256, suppressions logiques.' : 'Storage outside the web root, SHA-256 fingerprints, soft deletes.',

    'hack_title' => $isFr ? 'Hackathon Meta — Indie Developer' : 'Meta Hackathon — Indie Developer',
    'hack_text' => $isFr
        ? 'Construit pour le thème « Build for Your World », avec les modèles open-weight de Meta servis par un partenaire d’inférence.'
        : 'Built for the “Build for Your World” theme, with Meta open-weight models served by a partner inference provider.',
    'hack_1' => $isFr ? 'Modèle : Muse Spark Open' : 'Model: Muse Spark Open',
    'hack_2' => $isFr ? 'Track : Indie Developer' : 'Track: Indie Developer',
    'hack_3' => $isFr ? 'Licence MIT' : 'MIT licence',

    'final_title' => $isFr ? 'Prêt à essayer ?' : 'Ready to try it?',
    'final_text' => $isFr ? 'Connectez-vous et posez votre première question à vos documents.' : 'Sign in and ask your first question to your documents.',
];
?>
<section class="landing-hero">
    <p class="landing-eyebrow"><?= e($t['eyebrow']) ?></p>
    <h1 class="landing-title"><?= e($t['hero_title']) ?></h1>
    <p class="landing-sub"><?= e($t['hero_sub']) ?></p>
    <div class="landing-actions">
        <a class="btn btn-primary btn-lg" href="<?= e(url('/login')) ?>"><?= e($t['cta_demo']) ?></a>
        <a class="btn btn-outline-secondary btn-lg" href="<?= e(url('/login')) ?>"><?= e($t['cta_login']) ?></a>
    </div>
    <ul class="landing-pills">
        <li><?= e($t['pill_1']) ?></li>
        <li><?= e($t['pill_2']) ?></li>
        <li><?= e($t['pill_3']) ?></li>
        <li><?= e($t['pill_4']) ?></li>
    </ul>
</section>

<section class="landing-split">
    <article class="landing-card landing-card-problem">
        <h2><?= e($t['problem_title']) ?></h2>
        <p><?= e($t['problem_text']) ?></p>
    </article>
    <article class="landing-card landing-card-solution">
        <h2><?= e($t['solution_title']) ?></h2>
        <p><?= e($t['solution_text']) ?></p>
    </article>
</section>

<section class="landing-section">
    <h2 class="landing-section-title"><?= e($t['features_title']) ?></h2>
    <div class="landing-grid">
        <?php foreach ([
            ['f1_title', 'f1_text'],
            ['f2_title', 'f2_text'],
            ['f3_title', 'f3_text'],
            ['f4_title', 'f4_text'],
            ['f5_title', 'f5_text'],
            ['f6_title', 'f6_text'],
        ] as [$titleKey, $textKey]): ?>
            <article class="landing-feature">
                <h3><?= e($t[$titleKey]) ?></h3>
                <p><?= e($t[$textKey]) ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="landing-section landing-section-alt">
    <h2 class="landing-section-title"><?= e($t['stack_title']) ?></h2>
    <p class="landing-section-sub"><?= e($t['stack_text']) ?></p>
    <ul class="landing-stack">
        <li><?= e($t['stack_php']) ?></li>
        <li><?= e($t['stack_mysql']) ?></li>
        <li><?= e($t['stack_model']) ?></li>
        <li><?= e($t['stack_tests']) ?></li>
        <li><?= e($t['stack_security']) ?></li>
    </ul>
</section>

<section class="landing-section">
    <h2 class="landing-section-title"><?= e($t['gov_title']) ?></h2>
    <ul class="landing-checks">
        <li><?= e($t['gov_1']) ?></li>
        <li><?= e($t['gov_2']) ?></li>
        <li><?= e($t['gov_3']) ?></li>
        <li><?= e($t['gov_4']) ?></li>
    </ul>
</section>

<section class="landing-hack">
    <h2><?= e($t['hack_title']) ?></h2>
    <p><?= e($t['hack_text']) ?></p>
    <ul class="landing-hack-list">
        <li><?= e($t['hack_1']) ?></li>
        <li><?= e($t['hack_2']) ?></li>
        <li><?= e($t['hack_3']) ?></li>
    </ul>
</section>

<section class="landing-final">
    <h2><?= e($t['final_title']) ?></h2>
    <p><?= e($t['final_text']) ?></p>
    <a class="btn btn-primary btn-lg" href="<?= e(url('/login')) ?>"><?= e($t['cta_login']) ?></a>
</section>
