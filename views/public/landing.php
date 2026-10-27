<?php

declare(strict_types=1);

/** @var string $lang */
$isFr = $lang === 'fr';

$t = [
    'eyebrow' => $isFr ? 'Hackathon Meta · Track Indie Developer' : 'Meta Hackathon · Indie Developer track',
    'hero_title' => $isFr ? 'Espace de connaissances agentique et multimodal' : 'Agentic, multimodal knowledge workspace',
    'hero_sub' => $isFr
        ? "Il ingère vos documents, vos registres scannés et vos notes vocales, puis agit : il répond avec des sources citées, rédige des notes et des rapports, et résume vos documents."
        : 'It ingests documents, scanned records and voice notes, then acts: it answers with cited sources, drafts notes and reports, and summarizes documents.',
    'cta_demo' => $isFr ? 'Voir la démonstration' : 'See the demo',
    'cta_login' => $isFr ? 'Se connecter' : 'Sign in',
    'pill_1' => $isFr ? 'Sources citées' : 'Cited sources',
    'pill_2' => $isFr ? 'Tâches agentiques' : 'Agentic tasks',
    'pill_3' => $isFr ? 'Multimodal' : 'Multimodal',
    'pill_4' => $isFr ? 'Gouvernance' : 'Governance',

    'why_title' => $isFr ? 'Pas une « application RAG basique »' : 'Not a “basic RAG application”',
    'why_sub' => $isFr
        ? 'Le hackathon exclut explicitement les applications RAG basiques. Ce projet va délibérément au-delà de la boucle question → réponse.'
        : 'The hackathon explicitly rules out basic RAG applications. This project deliberately goes beyond the question → answer loop.',
    'basic' => $isFr ? 'RAG basique' : 'Basic RAG',
    'ours' => $isFr ? 'Ce projet' : 'This project',
    'cmp_1a' => $isFr ? 'PDF texte uniquement' : 'Text PDFs only',
    'cmp_1b' => $isFr ? 'Texte + PDF + images scannées + audio' : 'Text + PDF + scanned images + audio',
    'cmp_2a' => $isFr ? 'Une question → une réponse' : 'One question → one answer',
    'cmp_2b' => $isFr ? 'Tâches agentiques : note, rapport, fiche' : 'Agentic tasks: note, report, brief',
    'cmp_3a' => $isFr ? 'Aucune traçabilité' : 'No traceability',
    'cmp_3b' => $isFr ? 'Sources numérotées et citées' : 'Numbered, cited sources',
    'cmp_4a' => $isFr ? 'Aucune gouvernance' : 'No governance',
    'cmp_4b' => $isFr ? 'RBAC, versions SHA-256, journal d’audit' : 'RBAC, SHA-256 versions, audit journal',
    'cmp_5a' => $isFr ? 'Aucun export' : 'No export',
    'cmp_5b' => $isFr ? 'Export PDF et DOCX' : 'PDF and DOCX export',
    'cmp_6a' => $isFr ? 'Chatbot générique' : 'Generic chatbot',
    'cmp_6b' => $isFr ? 'Un vrai terrain : paroisses, ONG, écoles' : 'A real domain: parishes, NGOs, schools',

    'features_title' => $isFr ? 'Ce que fait la plateforme' : 'What the platform does',
    'f1_title' => $isFr ? 'Réponses sourcées' : 'Grounded answers',
    'f1_text' => $isFr ? 'Recherche hybride (similarité vectorielle + recouvrement lexical), puis réponse strictement extractive avec ses extraits sources.' : 'Hybrid retrieval (vector similarity + lexical overlap), then a strictly extractive answer with its source excerpts.',
    'f2_title' => $isFr ? 'Tâches agentiques' : 'Agentic tasks',
    'f2_text' => $isFr ? 'Notes, rapports et fiches structurées générés à partir des documents, avec citations.' : 'Notes, reports and structured briefs generated from the documents, with citations.',
    'f3_title' => $isFr ? 'Multimodal' : 'Multimodal',
    'f3_text' => $isFr ? 'OCR des images scannées et transcription audio (Muse Spark Open), puis indexation du texte extrait.' : 'OCR of scanned images and audio transcription (Muse Spark Open), then indexing of the extracted text.',
    'f4_title' => $isFr ? 'Gouvernance' : 'Governance',
    'f4_text' => $isFr ? 'RBAC à deux niveaux (global + par espace), journal d’audit en ajout seul.' : 'Two-level RBAC (global + per space), append-only audit journal.',
    'f5_title' => $isFr ? 'Versions immuables' : 'Immutable versions',
    'f5_text' => $isFr ? 'Chaque dépôt est daté, haché en SHA-256 et conservé : l’historique est complet.' : 'Every upload is dated, hashed with SHA-256 and kept: the history is complete.',
    'f6_title' => $isFr ? 'Export PDF et DOCX' : 'PDF and DOCX export',
    'f6_text' => $isFr ? 'Chaque résultat se télécharge en PDF (dompdf) ou DOCX (OOXML natif), prêt à partager.' : 'Every result downloads as PDF (dompdf) or DOCX (native OOXML), ready to share.',

    'domain_title' => $isFr ? 'Un vrai terrain, pas un jouet' : 'A real domain, not a toy',
    'domain_text' => $isFr
        ? 'Conçu pour les paroisses, les ONG et les écoles du Burkina Faso : des équipes qui accumulent des rapports, des registres scannés et des notes vocales sans pouvoir les interroger.'
        : 'Designed for parishes, NGOs and schools in Burkina Faso: teams that accumulate reports, scanned registers and voice notes without being able to query them.',

    'stack_title' => $isFr ? 'Sous le capot' : 'Under the hood',
    'stack_text' => $isFr ? 'Un socle sobre, testé, sans dépendance superflue.' : 'A lean, tested foundation with no unnecessary dependencies.',
    'stack_php' => 'PHP 8.4 · MVC strict, PSR-4',
    'stack_mysql' => 'MySQL 8.4 · InnoDB, utf8mb4',
    'stack_model' => 'Muse Spark Open · partenaire Meta',
    'stack_tests' => $isFr ? '57 tests · 122 assertions' : '57 tests · 122 assertions',
    'stack_security' => $isFr ? 'Argon2 · CSRF · CSP · audit' : 'Argon2 · CSRF · CSP · audit',

    'gov_title' => $isFr ? 'Gouvernance par conception' : 'Governance by design',
    'gov_1' => $isFr ? 'Rôles globaux : administrateur technique, fonctionnel, membre.' : 'Global roles: technical admin, functional admin, member.',
    'gov_2' => $isFr ? 'Rôles locaux par espace : lecteur, contributeur, animateur, responsable.' : 'Per-space roles: reader, contributor, facilitator, owner.',
    'gov_3' => $isFr ? 'Journal en ajout seul : rien n’échappe à la traçabilité.' : 'Append-only journal: nothing escapes traceability.',
    'gov_4' => $isFr ? 'Stockage hors racine web, empreintes SHA-256, suppressions logiques.' : 'Storage outside the web root, SHA-256 fingerprints, soft deletes.',

    'hack_title' => $isFr ? 'Meta Global AI Developer Hackathon' : 'Meta Global AI Developer Hackathon',
    'hack_text' => $isFr
        ? 'Thème « Build for Your World » · Track Indie Developer. Les modèles open-weight de Meta sont servis par un partenaire d’inférence.'
        : '“Build for Your World” theme · Indie Developer track. Meta open-weight models are served by a partner inference provider.',
    'hack_1' => $isFr ? 'Modèle : Muse Spark Open' : 'Model: Muse Spark Open',
    'hack_2' => $isFr ? 'Thème : Build for Your World' : 'Theme: Build for Your World',
    'hack_3' => $isFr ? 'Track : Indie Developer' : 'Track: Indie Developer',
    'hack_4' => $isFr ? 'Licence MIT' : 'MIT licence',

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

<section class="landing-section">
    <h2 class="landing-section-title"><?= e($t['why_title']) ?></h2>
    <p class="landing-section-sub"><?= e($t['why_sub']) ?></p>
    <div class="landing-compare" role="table" aria-label="<?= e($t['why_title']) ?>">
        <div class="landing-compare-head" role="row">
            <span role="columnheader"><?= e($t['basic']) ?></span>
            <span role="columnheader"><?= e($t['ours']) ?></span>
        </div>
        <?php foreach ([
            ['cmp_1a', 'cmp_1b'],
            ['cmp_2a', 'cmp_2b'],
            ['cmp_3a', 'cmp_3b'],
            ['cmp_4a', 'cmp_4b'],
            ['cmp_5a', 'cmp_5b'],
            ['cmp_6a', 'cmp_6b'],
        ] as [$basicKey, $ourKey]): ?>
            <div class="landing-compare-row" role="row">
                <span class="landing-compare-basic" role="cell"><?= e($t[$basicKey]) ?></span>
                <span class="landing-compare-ours" role="cell"><?= e($t[$ourKey]) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
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

<section class="landing-domain">
    <h2><?= e($t['domain_title']) ?></h2>
    <p><?= e($t['domain_text']) ?></p>
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
        <li><?= e($t['hack_4']) ?></li>
    </ul>
</section>

<section class="landing-final">
    <h2><?= e($t['final_title']) ?></h2>
    <p><?= e($t['final_text']) ?></p>
    <a class="btn btn-primary btn-lg" href="<?= e(url('/login')) ?>"><?= e($t['cta_login']) ?></a>
</section>
