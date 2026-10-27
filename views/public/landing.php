<?php

declare(strict_types=1);

/** @var string $lang */
$isFr = $lang === 'fr';

$t = [
    'eyebrow' => $isFr ? 'Hackathon Meta · Track Indie Developer' : 'Meta Hackathon · Indie Developer track',
    'hero_title' => $isFr ? 'Un espace de travail collaboratif, augmenté par l’IA' : 'A collaborative workspace, augmented by AI',
    'hero_sub' => $isFr
        ? "Pour les organisations, établissements d’enseignement, ONG et entreprises : gestion documentaire sécurisée, espaces d’équipe et partage des connaissances — avec une IA qui répond en langage naturel, cite ses sources et rédige vos synthèses."
        : 'For organizations, educational institutions, NGOs and companies: secure document management, team spaces and knowledge sharing — with an AI that answers in natural language, cites its sources and drafts your summaries.',
    'cta_demo' => $isFr ? 'Voir la démonstration' : 'See the demo',
    'cta_login' => $isFr ? 'Se connecter' : 'Sign in',
    'pill_1' => $isFr ? 'Gestion documentaire' : 'Document management',
    'pill_2' => $isFr ? 'Espaces d’équipe' : 'Team spaces',
    'pill_3' => $isFr ? 'IA en langage naturel' : 'Natural-language AI',
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

    'features_title' => $isFr ? 'Ce que fait la plateforme aujourd’hui' : 'What the platform does today',
    'f1_title' => $isFr ? 'Gestion documentaire sécurisée' : 'Secure document management',
    'f1_text' => $isFr ? 'Stockage hors racine web, empreinte SHA-256, versions immuables et suppressions logiques.' : 'Storage outside the web root, SHA-256 fingerprint, immutable versions and soft deletes.',
    'f2_title' => $isFr ? 'Espaces d’équipe et rôles' : 'Team spaces and roles',
    'f2_text' => $isFr ? 'Chaque espace a ses rôles (lecteur, contributeur, animateur, responsable) et ses documents.' : 'Each space has its own roles (reader, contributor, facilitator, owner) and its documents.',
    'f3_title' => $isFr ? 'IA en langage naturel' : 'Natural-language AI',
    'f3_text' => $isFr ? 'Posez une question à vos documents : l’assistant répond avec des sources citées, sans inventer.' : 'Ask your documents a question: the assistant answers with cited sources, without inventing.',
    'f4_title' => $isFr ? 'Synthèses et rapports' : 'Summaries and reports',
    'f4_text' => $isFr ? 'Résumé d’un document ou rédaction d’une note structurée, avec citations.' : 'Document summary or structured note drafting, with citations.',
    'f5_title' => $isFr ? 'Multimodal' : 'Multimodal',
    'f5_text' => $isFr ? 'OCR des images scannées et transcription audio (Muse Spark Open), puis indexation.' : 'OCR of scanned images and audio transcription (Muse Spark Open), then indexing.',
    'f6_title' => $isFr ? 'Export PDF et DOCX' : 'PDF and DOCX export',
    'f6_text' => $isFr ? 'Chaque résultat se télécharge en PDF (dompdf) ou DOCX (OOXML natif).' : 'Every result downloads as PDF (dompdf) or DOCX (native OOXML).',

    'origin_title' => $isFr ? 'Une vraie histoire : GES-CATECHESE' : 'A real story: GES-CATECHESE',
    'origin_text' => $isFr
        ? 'Cette plateforme prolonge GES-CATECHESE, une application de gestion de la catéchèse catholique dans les paroisses du Burkina Faso : catéchistes, apprenants, activités, rapports et documents. La prochaine étape : des assistants de connaissances alimentés par l’IA, capables de répondre aux questions, de générer des rapports et de synthétiser les documents institutionnels.'
        : 'This platform extends GES-CATECHESE, a Catholic catechesis management application for parishes in Burkina Faso: catechists, learners, activities, reports and documents. The next step: AI-powered knowledge assistants able to answer questions, generate reports and synthesize institutional documents.',
    'origin_tag_1' => $isFr ? 'Paroisses' : 'Parishes',
    'origin_tag_2' => $isFr ? 'Écoles' : 'Schools',
    'origin_tag_3' => $isFr ? 'ONG' : 'NGOs',
    'origin_tag_4' => $isFr ? 'Entreprises' : 'Companies',

    'roadmap_title' => $isFr ? 'La suite' : 'What comes next',
    'roadmap_1' => $isFr ? 'Collaboration d’équipe avancée (fils, mentions, notifications).' : 'Advanced team collaboration (threads, mentions, notifications).',
    'roadmap_2' => $isFr ? 'Suivi de projet et tableaux de bord décisionnels.' : 'Project tracking and decision dashboards.',
    'roadmap_3' => $isFr ? 'Recommandations intelligentes et détection des connaissances obsolètes.' : 'Smart recommendations and stale-knowledge detection.',

    'stack_title' => $isFr ? 'Sous le capot' : 'Under the hood',
    'stack_text' => $isFr ? 'Un socle sobre, testé, sans dépendance superflue.' : 'A lean, tested foundation with no unnecessary dependencies.',
    'stack_php' => 'PHP 8.4 · MVC strict, PSR-4',
    'stack_mysql' => 'MySQL 8.4 · InnoDB, utf8mb4',
    'stack_model' => 'Muse Spark Open · partenaire Meta',
    'stack_tests' => $isFr ? '57 tests · 122 assertions' : '57 tests · 122 assertions',
    'stack_security' => $isFr ? 'Argon2 · CSRF · CSP · audit' : 'Argon2 · CSRF · CSP · audit',

    'author_title' => $isFr ? 'L’auteur' : 'The author',
    'author_name' => 'Anatole KABORE',
    'author_role' => $isFr ? 'Ingénieur logiciel indépendant · spécialiste en cybersécurité · Ouagadougou, Burkina Faso' : 'Independent software engineer · cybersecurity specialist · Ouagadougou, Burkina Faso',
    'author_text' => $isFr
        ? 'Je conçois et développe des applications web, des systèmes d’information et des plateformes numériques sécurisées. Ce projet est né de GES-CATECHESE et de la volonté de rendre les connaissances institutionnelles interrogeables.'
        : 'I design and build web applications, information systems and secure digital platforms. This project grew out of GES-CATECHESE and the will to make institutional knowledge queryable.',

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

<section class="landing-domain">
    <h2><?= e($t['origin_title']) ?></h2>
    <p><?= e($t['origin_text']) ?></p>
    <ul class="landing-domain-tags">
        <li><?= e($t['origin_tag_1']) ?></li>
        <li><?= e($t['origin_tag_2']) ?></li>
        <li><?= e($t['origin_tag_3']) ?></li>
        <li><?= e($t['origin_tag_4']) ?></li>
    </ul>
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
    <h2 class="landing-section-title"><?= e($t['roadmap_title']) ?></h2>
    <ul class="landing-checks">
        <li><?= e($t['roadmap_1']) ?></li>
        <li><?= e($t['roadmap_2']) ?></li>
        <li><?= e($t['roadmap_3']) ?></li>
    </ul>
</section>

<section class="landing-author">
    <div class="landing-author-avatar" aria-hidden="true">AK</div>
    <div>
        <h2><?= e($t['author_title']) ?> — <?= e($t['author_name']) ?></h2>
        <p class="landing-author-role"><?= e($t['author_role']) ?></p>
        <p><?= e($t['author_text']) ?></p>
    </div>
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
