<?php

declare(strict_types=1);

/** @var string $lang */
$isFr = $lang === 'fr';

$t = [
    'eyebrow' => $isFr ? 'Hackathon Meta · Track Indie Developer' : 'Meta Hackathon · Indie Developer track',
    'hero_title' => $isFr ? 'Gestion collaborative des connaissances, réinventée par l’IA' : 'Collaborative knowledge management, reinvented by AI',
    'hero_sub' => $isFr
        ? 'Pour les établissements d’enseignement, les ONG, les organisations confessionnelles et les entreprises africaines. Centralisez vos documents, préservez votre mémoire institutionnelle et transformez vos connaissances en une ressource vivante, consultable en langage naturel grâce à l’intelligence artificielle.'
        : 'For educational institutions, NGOs, faith-based organizations and African enterprises. Centralize your documents, preserve your institutional memory and turn your knowledge into a living resource, searchable in natural language thanks to artificial intelligence.',
    'cta_demo' => $isFr ? 'Voir la démonstration' : 'See the demo',
    'cta_login' => $isFr ? 'Se connecter' : 'Sign in',
    'pill_1' => $isFr ? 'Gestion documentaire sécurisée' : 'Secure document management',
    'pill_2' => $isFr ? 'Assistant IA contextuel' : 'Contextual AI assistant',
    'pill_3' => $isFr ? 'Recherche intelligente hybride' : 'Hybrid smart search',
    'pill_4' => $isFr ? 'Journal d’audit et traçabilité' : 'Audit trail and traceability',

    'problem_title' => $isFr ? 'Problématique' : 'The challenge',
    'problem_intro' => $isFr
        ? 'Dans de nombreuses organisations africaines, l’information critique est dispersée entre dossiers papier, e-mails, partages de fichiers et ordinateurs personnels.'
        : 'In many African organizations, critical information is scattered across paper files, emails, file shares and personal computers.',
    'problem_lead' => $isFr ? 'Conséquences :' : 'Consequences:',
    'problem_1' => $isFr ? 'Perte de connaissances lors des départs de collaborateurs.' : 'Knowledge is lost when people leave.',
    'problem_2' => $isFr ? 'Difficulté d’intégration des nouveaux membres.' : 'New members struggle to get up to speed.',
    'problem_3' => $isFr ? 'Temps considérable consacré à la recherche d’informations.' : 'Considerable time spent searching for information.',
    'problem_4' => $isFr ? 'Risques de duplication, d’erreurs et de manque de traçabilité.' : 'Risks of duplication, errors and lack of traceability.',
    'problem_outro' => $isFr ? 'Les connaissances existent, mais restent difficiles à exploiter.' : 'The knowledge exists, but remains hard to exploit.',

    'solution_title' => $isFr ? 'Solution intelligente' : 'Smart solution',
    'solution_p1' => $isFr
        ? 'Agentic Knowledge Workspace est une plateforme de gestion des connaissances assistée par l’IA qui permet aux organisations de centraliser, sécuriser et exploiter leur patrimoine documentaire.'
        : 'Agentic Knowledge Workspace is an AI-assisted knowledge management platform that lets organizations centralize, secure and exploit their documentary heritage.',
    'solution_p2' => $isFr
        ? 'Au lieu de rechercher manuellement dans des centaines de documents, les utilisateurs peuvent simplement poser une question :'
        : 'Instead of manually searching hundreds of documents, users simply ask a question:',
    'solution_quote' => $isFr
        ? '« Peux-tu me présenter les rapports d’activités de 2025 concernant le renforcement des capacités de l’équipe de suivi-évaluation des projets ? »'
        : '“Can you present the 2025 activity reports about capacity building for the project monitoring and evaluation team?”',
    'solution_p3' => $isFr
        ? 'L’assistant IA analyse les documents autorisés, produit une réponse synthétique et cite précisément ses sources.'
        : 'The AI assistant analyses the authorized documents, produces a synthetic answer and cites its sources precisely.',
    'solution_task_intro' => $isFr
        ? 'Puis, pour aller plus loin, l’utilisateur demande une tâche agentique :'
        : 'Then, to go further, the user requests an agentic task:',
    'solution_task_quote' => $isFr
        ? '« Génère une synthèse avec les dates, objectifs, participants, résultats obtenus et recommandations concernant ces dits rapports. »'
        : '“Generate a synthesis with the dates, objectives, participants, results obtained and recommendations for those reports.”',
    'solution_task_result' => $isFr
        ? 'Le résultat est un document structuré, exportable en PDF ou DOCX, avec ses sources.'
        : 'The result is a structured document, exportable to PDF or DOCX, with its sources.',
    'solution_p4' => $isFr
        ? 'Chaque document, chaque réponse et chaque action restent traçables et vérifiables.'
        : 'Every document, every answer and every action remain traceable and verifiable.',

    'why_title' => $isFr ? 'Plus qu’une simple application RAG' : 'More than a simple RAG application',
    'why_sub' => $isFr
        ? 'Le règlement du hackathon exclut explicitement les applications RAG basiques. Notre approche va au-delà du simple modèle question → recherche → réponse : la plateforme adopte une logique agentique orientée tâches métier.'
        : 'The hackathon rules explicitly exclude basic RAG applications. Our approach goes beyond the question → search → answer pattern: the platform adopts an agentic logic oriented towards business tasks.',
    'basic' => $isFr ? 'RAG basique' : 'Basic RAG',
    'ours' => $isFr ? 'Agentic Knowledge Workspace' : 'Agentic Knowledge Workspace',
    'cmp_1a' => $isFr ? 'Réponse à une question' : 'Answer to a question',
    'cmp_1b' => $isFr ? 'Exécution de tâches documentaires complètes' : 'Execution of complete document tasks',
    'cmp_2a' => $isFr ? 'PDF textuels uniquement' : 'Text PDFs only',
    'cmp_2b' => $isFr ? 'PDF, images scannées et contenus multimédias' : 'PDFs, scanned images and multimedia content',
    'cmp_3a' => $isFr ? 'Réponse unique' : 'Single answer',
    'cmp_3b' => $isFr ? 'Rapports, fiches, synthèses et notes structurées' : 'Reports, briefs, summaries and structured notes',
    'cmp_4a' => $isFr ? 'Faible traçabilité' : 'Weak traceability',
    'cmp_4b' => $isFr ? 'Sources citées et vérifiables' : 'Cited and verifiable sources',
    'cmp_5a' => $isFr ? 'Aucun contrôle organisationnel' : 'No organizational control',
    'cmp_5b' => $isFr ? 'RBAC, versions et audit' : 'RBAC, versions and audit',
    'cmp_6a' => $isFr ? 'Chatbot générique' : 'Generic chatbot',
    'cmp_6b' => $isFr ? 'Solution métier adaptée au terrain africain' : 'Business solution adapted to the African context',

    'features_title' => $isFr ? 'Fonctionnalités clés' : 'Key features',
    'f1_title' => $isFr ? 'Authentification sécurisée' : 'Secure authentication',
    'f1_1' => $isFr ? 'Argon2 pour le hachage des mots de passe' : 'Argon2 password hashing',
    'f1_2' => $isFr ? 'Protection CSRF' : 'CSRF protection',
    'f1_3' => $isFr ? 'Gestion sécurisée des sessions' : 'Secure session management',
    'f1_4' => $isFr ? 'Verrouillage progressif contre les attaques' : 'Progressive lockout against attacks',
    'f2_title' => $isFr ? 'Gouvernance et contrôle d’accès' : 'Governance and access control',
    'f2_1' => $isFr ? 'RBAC multi-niveaux' : 'Multi-level RBAC',
    'f2_2' => $isFr ? 'Permissions globales' : 'Global permissions',
    'f2_3' => $isFr ? 'Permissions par espace de travail' : 'Per-workspace permissions',
    'f3_title' => $isFr ? 'Gestion documentaire avancée' : 'Advanced document management',
    'f3_1' => $isFr ? 'Stockage hors racine web' : 'Storage outside the web root',
    'f3_2' => $isFr ? 'Empreintes SHA-256' : 'SHA-256 fingerprints',
    'f3_3' => $isFr ? 'Versions immuables' : 'Immutable versions',
    'f3_4' => $isFr ? 'Suppression logique sécurisée' : 'Secure soft delete',
    'f4_title' => $isFr ? 'Assistant IA contextuel' : 'Contextual AI assistant',
    'f4_1' => $isFr ? 'Réponses en langage naturel' : 'Natural-language answers',
    'f4_2' => $isFr ? 'Fondées exclusivement sur les documents autorisés' : 'Grounded exclusively in authorized documents',
    'f4_3' => $isFr ? 'Sources automatiquement citées' : 'Automatically cited sources',
    'f5_title' => $isFr ? 'Recherche intelligente' : 'Smart search',
    'f5_1' => $isFr ? 'Recherche hybride vectorielle et lexicale' : 'Hybrid vector and lexical search',
    'f5_2' => $isFr ? 'Compréhension du contexte utilisateur' : 'Understanding of user context',
    'f5_3' => $isFr ? 'Recherche sémantique au-delà des mots-clés' : 'Semantic search beyond keywords',
    'f6_title' => $isFr ? 'Génération de contenu' : 'Content generation',
    'f6_1' => $isFr ? 'Résumés automatiques' : 'Automatic summaries',
    'f6_2' => $isFr ? 'Notes de synthèse' : 'Synthesis notes',
    'f6_3' => $isFr ? 'Rapports structurés' : 'Structured reports',
    'f6_4' => $isFr ? 'Citations et références intégrées' : 'Integrated citations and references',
    'f7_title' => $isFr ? 'Audit et conformité' : 'Audit and compliance',
    'f7_1' => $isFr ? 'Journalisation complète des actions' : 'Complete action logging',
    'f7_2' => $isFr ? 'Horodatage systématique' : 'Systematic timestamping',
    'f7_3' => $isFr ? 'Historique immuable des opérations' : 'Immutable operation history',
    'f8_title' => $isFr ? 'Export professionnel' : 'Professional export',
    'f8_1' => $isFr ? 'PDF' : 'PDF',
    'f8_2' => $isFr ? 'DOCX' : 'DOCX',
    'f8_3' => $isFr ? 'Partage simplifié des résultats' : 'Simplified result sharing',

    'origin_title' => $isFr ? 'Une innovation issue du terrain' : 'An innovation rooted in the field',
    'origin_text' => $isFr
        ? 'Ce projet s’appuie sur l’expérience acquise avec GES-CATECHESE, une plateforme de gestion de la catéchèse déjà utilisée dans les paroisses du Burkina Faso.'
        : 'This project builds on the experience gained with GES-CATECHESE, a catechesis management platform already used in parishes of Burkina Faso.',
    'origin_text2' => $isFr
        ? 'La prochaine étape consiste à transformer les documents institutionnels en une base de connaissances intelligente capable de :'
        : 'The next step is to turn institutional documents into an intelligent knowledge base able to:',
    'origin_1' => $isFr ? 'répondre aux questions ;' : 'answer questions;',
    'origin_2' => $isFr ? 'assister la prise de décision ;' : 'assist decision-making;',
    'origin_3' => $isFr ? 'générer automatiquement des rapports ;' : 'generate reports automatically;',
    'origin_4' => $isFr ? 'préserver durablement la mémoire organisationnelle.' : 'preserve organizational memory over time.',

    'usecases_title' => $isFr ? 'Cas d’usage' : 'Use cases',
    'uc1_title' => $isFr ? 'Établissements d’enseignement' : 'Educational institutions',
    'uc1_text' => $isFr ? 'Retrouver rapidement règlements, procédures, archives académiques et supports pédagogiques.' : 'Quickly find regulations, procedures, academic archives and teaching materials.',
    'uc2_title' => $isFr ? 'ONG' : 'NGOs',
    'uc2_text' => $isFr ? 'Capitaliser les connaissances des projets et faciliter le transfert d’expérience.' : 'Capitalize project knowledge and ease experience transfer.',
    'uc3_title' => $isFr ? 'Organisations confessionnelles' : 'Faith-based organizations',
    'uc3_text' => $isFr ? 'Préserver les archives pastorales, administratives et de formation.' : 'Preserve pastoral, administrative and training archives.',
    'uc4_title' => $isFr ? 'Entreprises' : 'Enterprises',
    'uc4_text' => $isFr ? 'Réduire le temps de recherche documentaire et améliorer le partage du savoir.' : 'Reduce document search time and improve knowledge sharing.',

    'stack_title' => $isFr ? 'Sous le capot' : 'Under the hood',
    'stack_sub' => $isFr ? 'Une architecture volontairement sobre, robuste et maîtrisée.' : 'A deliberately lean, robust and controlled architecture.',
    'stack_1' => 'PHP 8.4 · MVC strict (PSR-4)',
    'stack_2' => 'MySQL 8.4 · InnoDB / UTF8MB4',
    'stack_3' => 'Muse Spark Open (Meta)',
    'stack_4' => $isFr ? '57 tests automatisés · 122 assertions' : '57 automated tests · 122 assertions',
    'stack_5' => 'Argon2 · CSP · CSRF · ' . ($isFr ? 'journal d’audit' : 'audit journal'),

    'roadmap_title' => $isFr ? 'Feuille de route' : 'Roadmap',
    'rm1_title' => $isFr ? 'Collaboration avancée' : 'Advanced collaboration',
    'rm1_1' => $isFr ? 'Mentions' : 'Mentions',
    'rm1_2' => $isFr ? 'Commentaires' : 'Comments',
    'rm1_3' => $isFr ? 'Notifications' : 'Notifications',
    'rm1_4' => $isFr ? 'Discussions contextualisées' : 'Contextual discussions',
    'rm2_title' => $isFr ? 'Pilotage organisationnel' : 'Organizational steering',
    'rm2_1' => $isFr ? 'Tableaux de bord décisionnels' : 'Decision dashboards',
    'rm2_2' => $isFr ? 'Indicateurs de performance' : 'Performance indicators',
    'rm2_3' => $isFr ? 'Suivi de projets' : 'Project tracking',
    'rm3_title' => $isFr ? 'IA avancée' : 'Advanced AI',
    'rm3_1' => $isFr ? 'Recommandations documentaires' : 'Document recommendations',
    'rm3_2' => $isFr ? 'Détection des connaissances obsolètes' : 'Stale-knowledge detection',
    'rm3_3' => $isFr ? 'Assistants spécialisés par métier' : 'Domain-specific assistants',

    'author_title' => $isFr ? 'À propos de l’auteur' : 'About the author',
    'author_name' => 'Anatole KABORE',
    'author_role' => $isFr ? 'Ingénieur logiciel indépendant et spécialiste en cybersécurité basé à Ouagadougou, Burkina Faso.' : 'Independent software engineer and cybersecurity specialist based in Ouagadougou, Burkina Faso.',
    'author_text' => $isFr
        ? 'Je conçois des applications web sécurisées, des systèmes d’information et des plateformes numériques destinées à répondre à des besoins concrets rencontrés par les organisations africaines.'
        : 'I build secure web applications, information systems and digital platforms designed to meet the concrete needs of African organizations.',
    'author_text2' => $isFr
        ? 'Agentic Knowledge Workspace est né de cette volonté : rendre les connaissances institutionnelles accessibles, exploitables et durables grâce à l’intelligence artificielle.'
        : 'Agentic Knowledge Workspace was born from this ambition: to make institutional knowledge accessible, usable and durable thanks to artificial intelligence.',

    'hack_title' => 'Meta Global AI Developer Hackathon',
    'hack_text' => $isFr ? 'Thème « Build for Your World » · Track Indie Developer.' : '“Build for Your World” theme · Indie Developer track.',
    'hack_1' => $isFr ? 'Thème : Build for Your World' : 'Theme: Build for Your World',
    'hack_2' => $isFr ? 'Track : Indie Developer' : 'Track: Indie Developer',
    'hack_3' => $isFr ? 'Modèle IA : Muse Spark Open' : 'AI model: Muse Spark Open',
    'hack_4' => $isFr ? 'Licence : MIT' : 'License: MIT',

    'final_title' => $isFr ? 'Prêt à dialoguer avec vos connaissances ?' : 'Ready to talk with your knowledge?',
    'final_text' => $isFr
        ? 'Connectez-vous, importez vos documents et posez votre première question. Agentic Knowledge Workspace transforme vos archives en un assistant intelligent capable d’informer, d’expliquer et d’agir.'
        : 'Sign in, upload your documents and ask your first question. Agentic Knowledge Workspace turns your archives into an intelligent assistant able to inform, explain and act.',
    'final_cta' => $isFr ? 'Commencer' : 'Get started',
];
?>
<section class="landing-hero">
    <p class="landing-eyebrow"><?= e($t['eyebrow']) ?></p>
    <p class="landing-brand-line">Agentic Knowledge Workspace</p>
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
    <div class="landing-split">
        <article class="landing-card landing-card-problem">
            <h2><?= e($t['problem_title']) ?></h2>
            <p><?= e($t['problem_intro']) ?></p>
            <p class="landing-lead"><?= e($t['problem_lead']) ?></p>
            <ul class="landing-problem-list">
                <li><?= e($t['problem_1']) ?></li>
                <li><?= e($t['problem_2']) ?></li>
                <li><?= e($t['problem_3']) ?></li>
                <li><?= e($t['problem_4']) ?></li>
            </ul>
            <p class="landing-outro"><?= e($t['problem_outro']) ?></p>
        </article>
        <article class="landing-card landing-card-solution">
            <h2><?= e($t['solution_title']) ?></h2>
            <p><?= e($t['solution_p1']) ?></p>
            <p><?= e($t['solution_p2']) ?></p>
            <blockquote class="landing-quote"><?= e($t['solution_quote']) ?></blockquote>
            <p><?= e($t['solution_p3']) ?></p>
            <p><?= e($t['solution_task_intro']) ?></p>
            <blockquote class="landing-quote"><?= e($t['solution_task_quote']) ?></blockquote>
            <p><?= e($t['solution_task_result']) ?></p>
            <p><?= e($t['solution_p4']) ?></p>
        </article>
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

<section class="landing-section">
    <h2 class="landing-section-title"><?= e($t['features_title']) ?></h2>
    <div class="landing-grid landing-grid-features">
        <?php
        $features = [
            ['f1_title', ['f1_1', 'f1_2', 'f1_3', 'f1_4']],
            ['f2_title', ['f2_1', 'f2_2', 'f2_3']],
            ['f3_title', ['f3_1', 'f3_2', 'f3_3', 'f3_4']],
            ['f4_title', ['f4_1', 'f4_2', 'f4_3']],
            ['f5_title', ['f5_1', 'f5_2', 'f5_3']],
            ['f6_title', ['f6_1', 'f6_2', 'f6_3', 'f6_4']],
            ['f7_title', ['f7_1', 'f7_2', 'f7_3']],
            ['f8_title', ['f8_1', 'f8_2', 'f8_3']],
        ];
        foreach ($features as [$titleKey, $bulletKeys]): ?>
            <article class="landing-feature">
                <h3><?= e($t[$titleKey]) ?></h3>
                <ul class="landing-feature-list">
                    <?php foreach ($bulletKeys as $bulletKey): ?>
                        <li><?= e($t[$bulletKey]) ?></li>
                    <?php endforeach; ?>
                </ul>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="landing-domain">
    <h2><?= e($t['origin_title']) ?></h2>
    <p><?= e($t['origin_text']) ?></p>
    <p><?= e($t['origin_text2']) ?></p>
    <ul class="landing-checks">
        <li><?= e($t['origin_1']) ?></li>
        <li><?= e($t['origin_2']) ?></li>
        <li><?= e($t['origin_3']) ?></li>
        <li><?= e($t['origin_4']) ?></li>
    </ul>
</section>

<section class="landing-section">
    <h2 class="landing-section-title"><?= e($t['usecases_title']) ?></h2>
    <div class="landing-grid">
        <?php foreach ([
            ['uc1_title', 'uc1_text'],
            ['uc2_title', 'uc2_text'],
            ['uc3_title', 'uc3_text'],
            ['uc4_title', 'uc4_text'],
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
    <p class="landing-section-sub"><?= e($t['stack_sub']) ?></p>
    <ul class="landing-stack">
        <li><?= e($t['stack_1']) ?></li>
        <li><?= e($t['stack_2']) ?></li>
        <li><?= e($t['stack_3']) ?></li>
        <li><?= e($t['stack_4']) ?></li>
        <li><?= e($t['stack_5']) ?></li>
    </ul>
</section>

<section class="landing-section">
    <h2 class="landing-section-title"><?= e($t['roadmap_title']) ?></h2>
    <div class="landing-grid">
        <?php foreach ([
            ['rm1_title', ['rm1_1', 'rm1_2', 'rm1_3', 'rm1_4']],
            ['rm2_title', ['rm2_1', 'rm2_2', 'rm2_3']],
            ['rm3_title', ['rm3_1', 'rm3_2', 'rm3_3']],
        ] as [$titleKey, $bulletKeys]): ?>
            <article class="landing-feature">
                <h3><?= e($t[$titleKey]) ?></h3>
                <ul class="landing-feature-list">
                    <?php foreach ($bulletKeys as $bulletKey): ?>
                        <li><?= e($t[$bulletKey]) ?></li>
                    <?php endforeach; ?>
                </ul>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="landing-author">
    <div class="landing-author-avatar" aria-hidden="true">AK</div>
    <div>
        <h2><?= e($t['author_title']) ?> — <?= e($t['author_name']) ?></h2>
        <p class="landing-author-role"><?= e($t['author_role']) ?></p>
        <p><?= e($t['author_text']) ?></p>
        <p><?= e($t['author_text2']) ?></p>
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
    <a class="btn btn-primary btn-lg" href="<?= e(url('/login')) ?>"><?= e($t['final_cta']) ?></a>
</section>
