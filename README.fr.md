# Agentic Knowledge Workspace™

> **Gestion collaborative des connaissances, réinventée par l'IA.**
>
> Pour les **administrations publiques et privées, les entreprises et les organisations de développement** : centralisez vos documents, préservez votre mémoire institutionnelle et transformez vos connaissances en un **actif stratégique**, interrogeable en langage naturel grâce à l'intelligence artificielle.
>
> Réalisé pour le **Meta Global AI Developer Hackathon** — Thème : **Build for Your World** · Piste : **Indie Developer** · Modèle : **Muse Spark Open** · Licence : **MIT**.

---

## La problématique

Dans de nombreuses organisations, l'information critique est **dispersée entre dossiers papier, e-mails, partages de fichiers et ordinateurs personnels**.

**Conséquences :**

- Perte de connaissances lors des départs de collaborateurs.
- Difficulté d'intégration des nouveaux membres.
- Temps considérable consacré à la recherche d'informations.
- Risques de duplication, d'erreurs et de manque de traçabilité.

**Les connaissances existent, mais restent difficiles à exploiter.**

---

## La solution intelligente

**Agentic Knowledge Workspace** est une plateforme de gestion des connaissances assistée par l'IA qui permet aux organisations de **centraliser, sécuriser et exploiter** leur patrimoine documentaire.

Au lieu de rechercher manuellement dans des centaines de documents, les utilisateurs peuvent simplement poser une question :

> *« Peux-tu me présenter les rapports d'activités de 2025 concernant le renforcement des capacités de l'équipe de suivi-évaluation des projets ? »*

L'assistant IA analyse les documents **autorisés**, produit une **réponse synthétique** et **cite précisément ses sources**.

Puis, pour aller plus loin, l'utilisateur demande une **tâche agentique** :

> *« Génère une synthèse avec les dates, objectifs, participants, résultats obtenus et recommandations concernant ces dits rapports. »*

Le résultat est un **document structuré**, exportable en **PDF ou DOCX**, avec ses sources.

**Chaque document, chaque réponse et chaque action restent traçables et vérifiables.**

---

## Plus qu'une simple application RAG

Le règlement du hackathon **exclut explicitement les applications RAG basiques**. Notre approche va au-delà du simple modèle *question → recherche → réponse* : la plateforme adopte une **logique agentique orientée tâches métier**.

| RAG basique | Agentic Knowledge Workspace |
|---|---|
| Réponse à une question | Exécution de tâches documentaires complètes |
| PDF textuels uniquement | PDF, images scannées et contenus multimédias |
| Réponse unique | Rapports, fiches, synthèses et notes structurées |
| Faible traçabilité | Citations numérotées liées aux extraits exacts |
| Contexte limité au top-K | Expansion voisins + premier chunk, intention catalogue |
| Sortie monolingue | Réponses dans la langue de la question, sources traduites |
| Aucun contrôle organisationnel | RBAC, versions et audit |
| Chatbot générique | Solution métier adaptée au terrain africain |

---

## Fonctionnalités clés — **MVP**

### Authentification sécurisée
- Argon2 pour le hachage des mots de passe
- Protection CSRF
- Gestion sécurisée des sessions
- Verrouillage progressif contre les attaques

### Gouvernance et contrôle d'accès
- RBAC multi-niveaux
- Permissions globales
- Permissions par espace de travail

### Gestion documentaire avancée
- Stockage hors racine web
- Empreintes SHA-256
- Versions immuables
- Suppression logique sécurisée

### Assistant IA contextuel
- Réponses en langage naturel
- Fondées exclusivement sur les documents autorisés
- Sources automatiquement citées

### Recherche intelligente
- Recherche hybride vectorielle et lexicale
- Compréhension du contexte utilisateur
- Recherche sémantique au-delà des mots-clés

### Boucle d'agent — planifier → rechercher → rédiger → vérifier → corriger
Le mode tâche n'est pas une simple recherche suivie d'une génération. `RagService::task()` exécute une boucle d'agent, et chaque étape est montrée à l'utilisateur (panneau « Étapes de l'agent » dans la fenêtre de résultat) :

| Étape | Ce que fait l'agent |
|---|---|
| **1. Planifier** | Muse Spark Open découpe la consigne en 2 à 4 recherches ciblées, en connaissant **les titres des documents que l'utilisateur a le droit de consulter** (il traduit les recherches dans la langue des documents : consigne en anglais → recherches en français) |
| **2. Rechercher** | Chaque recherche passe par le moteur filtré par permissions ; les résultats sont fusionnés et dédoublonnés par extrait |
| **3. Rédiger** | Le document est rédigé à partir des extraits fusionnés et numérotés, avec une structure adaptée au genre demandé (note, rapport, compte rendu, courrier, leçon…) |
| **4. Vérifier** | Une passe de vérification contrôle chaque affirmation sourcée (chiffre, date, nom, citation) contre l'extrait cité |
| **5. Corriger** | Les affirmations non étayées sont corrigées ou retirées automatiquement (une passe) ; la trace indique combien ont été corrigées |

Mesuré sur la vraie base : consigne en anglais → 4 recherches planifiées (3 en français, dont une ciblant le bon titre de document) → 12 extraits issus de 2 documents → rédaction → **14 affirmations contrôlées, 1 non étayée, corrigée automatiquement** (~50 s de bout en bout). Si la planification ou la vérification échoue, l'agent revient au comportement à recherche unique : la tâche n'échoue jamais à cause d'elles. Couvert par `AgentTaskTest` avec un modèle scripté.

*Limite connue :* la vérification contrôle que les affirmations correspondent aux extraits ; elle ne peut pas garantir la qualité d'une traduction depuis une langue peu dotée comme le mooré — ces passages traduits doivent être relus par un locuteur.

### Ingestion en arrière-plan — uploads non bloquants
Les fichiers lents demandent plusieurs minutes de traitement IA (ASR pour l'audio, OCR pour les images et PDF scannés). Au lieu de garder la requête HTTP ouverte, ils sont mis en file d'attente :

```
upload.php ──(texte / PDF texte)──► extraction → stockage → index → 200        (synchrone, inchangé)
     │
     └─(audio / image / PDF scanné)──► ligne ingestion_jobs + worker détaché → 202 {job_id}   (~1 s)
                                            │
                tools/ingest_worker.php ◄───┘  claim (atomique) → ASR/OCR → stockage → index → audit
                                            │
     upload_status.php?job_id ◄── l'interface interroge toutes les 3 s (temps écoulé, puis « Indexé : N segments » ou une erreur traduite)
```

- **L'application reste fluide** : le worker est un processus séparé, donc le serveur `php -S` (mono-thread) continue de servir les pages (mesuré : 20–80 ms par page pendant un OCR de 105 s)
- **Pas de demi-documents** : la ligne `documents` n'est créée qu'après une extraction réussie — le retrieval et les listes n'exposent jamais un contenu non traité
- **Sûr par conception** : `claim()` atomique (une tâche n'est jamais traitée deux fois), statut lisible uniquement par l'auteur de l'upload, fichiers en file supprimés après traitement, tâches bloquées (> 30 min) marquées en échec, succès et échecs journalisés
- **Suivi repris** : les tâches en cours sont gardées dans `sessionStorage` — le suivi reprend si l'utilisateur quitte puis revient sur la page d'upload
- `IngestionService` partagé par les deux chemins (extraction, stockage, indexation, messages d'erreur traduits) — couvert par des tests unitaires

### Documents scannés — pipeline OCR
- Les **images** (png/jpg/tiff/webp) et les **PDF scannés** (sans couche texte) sont lus par la vision de Muse Spark Open
- Un PDF dont l'extraction de texte est vide est détecté automatiquement : ses images de pages JPEG sont extraites et passées à l'OCR, puis concaténées avec des marqueurs `[Page n / N]` — vérifié sur un recueil de chants scanné de 4 pages en mooré
- Les pages sont envoyées **en parallèle** (`curl_multi`) : le temps total est à peu près celui de la page la plus lente ; une page en échec est relancée seule sans perdre les autres
- Les scans volumineux sont réduits à 1600 px (GD) avant envoi : le texte reste lisible et on évite les délais dépassés ; `META_VISION_TIMEOUT` (300 s par défaut) couvre les pages denses
- **Goulot mesuré :** sur une page dense, 82 % des tokens générés étaient le raisonnement interne du modèle (2 466 sur 2 996). Les appels OCR utilisent donc `reasoning_effort=low` (`META_VISION_REASONING_EFFORT`) : −64 % de tokens générés pour une transcription équivalente, donc moins de crédits

### Mooré — un pipeline pour langue peu dotée
Pensé pour le terrain : au Burkina Faso, la connaissance institutionnelle réelle est souvent **dite** en mooré, pas écrite en français. La plateforme la traite comme une langue à part entière, pas comme une case cochée.

- **De bout en bout** — une réunion en mooré enregistrée dans le navigateur est transcrite, traduite automatiquement (les deux versions indexées), retrouvée par une question en mooré et répondue **en mooré avec citations** ; les tâches agentiques en mooré produisent des documents en mooré — tout est vérifié en réel
- **Référence linguistique dédiée** (`services/Ai/MooreReference.php`) — un glossaire compact injecté dans les prompts, compilé depuis le dictionnaire mooré-français-anglais du SIL (U. Niggli, 10 650 entrées), le manuel Peace Corps et un lexique de terrain : orthographe officielle (ɩ ʋ, nasales ãẽĩõũ, voyelles longues doublées, suffixe défini -ã, marqueur déclaratif -me), pronoms, règles SVO/négation, salutations et ~80 termes de base
- **Reconstruction phonétique ASR** — les moteurs de transcription déforment les langues peu dotées (whisper a entendu *« Ibe kibali kye mame zakalamba »* pour *« Yibeoog kibare, f zak ramba »*). Le traducteur reçoit l'ordre de reconstituer d'abord le mooré voulu phonétiquement, **puis** de traduire : ce transcript déformé donne désormais *« Comment s'est passée ta matinée ? Et les gens de la maison ? »* — le bon sens (un salut demandant des nouvelles de la matinée et de la famille)
- **Ancrage few-shot** — jusqu'à 3 vrais extraits mooré issus des documents indexés de l'utilisateur sont injectés comme référence de style et d'orthographe dès que le mooré est en jeu
- **Page Traduction** (`/traduire`) — FR ⇄ EN ⇄ MO dans les deux sens, copie en un clic, chaque traduction journalisée
- **Honnêteté assumée** — la sortie mooré utilise l'orthographe mossi standard et est fiable sur le vocabulaire courant ; l'interface précise que les textes officiels doivent être relus par un locuteur

### Transcription audio — double pipeline
- **Enregistreur de réunion** — la page Ajouter enregistre directement le micro (WAV dans le navigateur), joint le fichier au formulaire et laisse le pipeline en arrière-plan transcrire puis indexer comme un document classique. Un vu-mètre et une détection de silence empêchent d'indexer un enregistrement vide
- **Traduction automatique des transcripts** — `META_ASR_TRANSLATE_TO=French` ajoute une version traduite à chaque transcription : la langue originale et la traduction sont indexées côte à côte (transcript mooré + version française)
- **Baseten — Muse Spark Open (par défaut, en premier)** — l'audio est envoyé à `meta-models/muse-spark-open-1026` via `/v1/chat/completions` sous forme de partie standard `input_audio` (base64 brut + format). Mesuré sur un compte rendu de réunion en français : **transcription exacte (chiffres, noms propres) en 8 à 21 s, 5 essais sur 5**, contre ~2 min pour whisper.cpp sur CPU. L'ancien format `audio_url` (data URL) prenait 56 s et renvoyait des messages vides
- **Déploiement Baseten Whisper Large V3 Turbo (optionnel)** — déploiement GPU dédié appelé via l'API predict de Baseten (`whisper_input.audio.audio_b64` → `segments[].text`) ; verbatim, sans garde-fous LLM (paroles chantées comprises). Activé via `META_BASETEN_WHISPER_URL` (+ `META_BASETEN_WHISPER_KEY`, `META_BASETEN_WHISPER_LANGUAGE`) ; facturé à la minute GPU sur l'organisation propriétaire
- **Endpoint ASR façon OpenAI (optionnel)** — `/v1/audio/transcriptions` façon Whisper (multipart `file` + `model`), activé via `META_TRANSCRIPTION_MODEL`/`META_TRANSCRIPTION_ENDPOINT`
- **whisper.cpp local (optionnel)** — `whisper-cli.exe` + un modèle GGML, entièrement hors ligne et verbatim (chants compris) ; utilisé en secours dès que `META_WHISPER_BIN`/`META_WHISPER_MODEL` sont définis
- Les audios longs sont découpés automatiquement en segments calibrés fournisseur (`AudioSplitter`, MP3 aligné sur les frames / WAV à en-tête régénéré), chacun transcrit puis concaténé avec des marqueurs `[Segment n/N]`
- **Cascade résiliente :** Muse Spark Open → whisper.cpp local → déploiement Whisper Baseten → endpoint façon OpenAI (`META_ASR_PRIORITY=local` inverse l'ordre). Un backend en échec (refus, binaire absent, crash, sortie vide) est journalisé et le suivant est essayé. Le modèle n'est pas déterministe (1 réponse de type assistant sur 3 appels identiques, mesuré) : ces réponses sont relancées jusqu'à `META_AUDIO_ATTEMPTS` (3) fois à température 0 — sauf les refus pour droit d'auteur, délibérés, transmis directement à whisper.cpp (les chants restent transcrits)
- *Historique :* whisper.cpp local a servi pendant le développement, faute de crédits Baseten gratuits ; avec les crédits hackathon (100 $ sur Muse Spark Open), la transcription repasse sur Baseten et le backend local reste disponible comme option hors ligne
- *Sécurité garde-fous :* le modèle peut refuser de transcrire mot à mot des paroles chantées protégées. Les refus sont détectés et rejetés avec un message clair et traduit — ils ne sont **jamais indexés** comme contenu du document (les enregistrements parlés ne sont pas concernés)

### Moteur de récupération de précision (RAG++)
- **Retrieval filtré par permissions** — chaque chunk scoré appartient à un document que l'acteur est autorisé à lire (tenant, adhésion d'espace, confidentialité, propriété, suppression logique), résolu en une passe par requête
- **Scoring hybride** — `0,6 × similarité cosinus + 0,4 × recouvrement lexical` conserve un bon rappel même avec les embeddings locaux
- **Découpage à la limite de mot** — les coupes à taille fixe reculent au dernier espace : aucun extrait ne se termine en plein mot
- **Expansion de contexte** — chaque extrait retenu est enrichi de ses chunks voisins ±1 **et du premier chunk du document**, là où vivent titres et en-têtes ; une question sur un titre est donc toujours répondable
- **Détection d'intention catalogue** — « liste tous les documents » contourne le retrieval de chunks et alimente le modèle avec l'inventaire réel filtré par permissions (titre · catégorie · date d'indexation · fichier)
- **Citations vérifiables** — le modèle cite `[n]` ; seuls les extraits **cités** sont affichés comme sources, avec leur numéro exact de contexte (`Sources : [3]` ⇢ entrée *3*). Pas de citation, pas de liste de sources
- **Pipeline multilingue complet** — la réponse suit la **langue de la question** ; les citations littérales sont traduites avec l'original entre parenthèses ; les **extraits de sources sont traduits** aussi via une passe dédiée à basse température
- **Téléchargement direct** — chaque source citée porte un lien `document_id` vers sa dernière version
- **Surlignage des termes** — les mots de la question sont surlignés dans les extraits de sources affichés

### Génération de contenu
- Résumés automatiques
- Notes de synthèse
- Rapports structurés
- Citations et références intégrées

### Audit et conformité
- Journalisation complète des actions
- Horodatage systématique
- Historique immuable des opérations

### Export professionnel
- PDF
- DOCX
- Partage simplifié des résultats

---

## Une innovation issue du terrain

L'idée d'**Agentic Knowledge Workspace** est née de l'expérience acquise avec **GES-SACREMENTS-KTO-BF**, une plateforme déjà déployée dans certaines paroisses du Burkina Faso pour la gestion de la **formation**, du **suivi-évaluation des catéchumènes** et des **sacrements des fidèles chrétiens**.

Malgré la digitalisation des activités, les documents, rapports, **procédures** et connaissances institutionnelles demeurent souvent **dispersés entre plusieurs supports et systèmes**, rendant leur accès, leur exploitation et leur valorisation difficiles.

Le même constat s'observe dans les **administrations, les ONG et les entreprises**, où la mémoire institutionnelle se trouve fragmentée entre plusieurs systèmes et supports.

**Agentic Knowledge Workspace** répond à ce défi en transformant les documents et les données organisationnelles en une **base de connaissances intelligente, centralisée et interrogeable en langage naturel**.

---

## Cas d'usage

| Public | Ce que ça résout |
|---|---|
| **Administrations publiques et privées** | Retrouver rapidement règlements, procédures, archives administratives et rapports officiels, sans dépendre d'une personne clé. |
| **Entreprises** | Réduire le temps de recherche documentaire, capitaliser les savoir-faire et améliorer le partage des connaissances entre équipes. |
| **Organisations de développement** | Capitaliser les connaissances des projets, faciliter le transfert d'expérience et produire des rapports fiables et sourcés. |
| **Organisations confessionnelles** | Préserver les archives pastorales, administratives et de formation, comme le fait déjà GES-SACREMENTS-KTO-BF dans les paroisses. |

---

## Sous le capot

Une architecture volontairement sobre, robuste et maîtrisée.

| Composant | Version |
|---|---|
| **PHP** | **8.4.27** — MVC strict (PSR-4) |
| **MySQL** | **8.4.13** — InnoDB / UTF8MB4 |
| **Modèle IA** | **Muse Spark Open** (Meta, servi par un partenaire d'inférence) |
| **Tests** | **93 tests automatisés · 217 assertions** |
| **Sécurité** | Argon2 · CSP · CSRF · journal d'audit |
| **UI** | Bilingue EN/FR (EN par défaut) — mooré géré pour le contenu (réponses, tâches, traduction), Bootstrap + DataTables + Select2 |

---

## Feuille de route — **pour le futur**

> Ces fonctionnalités **ne font pas partie du MVP soumis** : elles prolongent la plateforme et s'appuient sur ses fondations existantes.

**Collaboration avancée** — mentions · commentaires · notifications · discussions contextualisées.

**Pilotage organisationnel** — tableaux de bord décisionnels · indicateurs de performance · suivi de projets.

**IA avancée** — recommandations documentaires · détection des connaissances obsolètes · assistants spécialisés par métier.

---

## Installation

**Prérequis :** PHP 8.4 (extensions `pdo_mysql`, `curl`, `zip`, `mbstring`), MySQL 8 démarré, [Composer](https://getcomposer.org/).

**En un clic (Windows/WAMP) :** `setup.bat` exécute toutes les étapes ci-dessous (il télécharge `composer.phar` si Composer n'est pas installé).

```powershell
# 1. Dépendances (composer.phar n'est pas versionné : utiliser un Composer global)
composer install

# 2. Configuration locale — puis renseigner DB_* et META_MODEL_API_KEY
Copy-Item .env.example .env

# 3. Base de données — créée automatiquement si absente, puis toutes les migrations
php tools/migrate.php migrate

# 4. Premier administrateur + espace par défaut (idempotent)
php tools/seed_admin.php

# 5. Lancer en local
.\serve.bat
```

L'administrateur est créé à partir de `ADMIN_EMAIL` / `ADMIN_PASSWORD` (variables d'environnement optionnelles, sinon les valeurs par défaut de `tools/seed_admin.php`) — **changer le mot de passe après la première connexion**.

*Vérifié sur un `git clone` neuf avec une base vide : dépendances, migrations, seed, toute la suite de tests et connexion OK.*

Puis ouvrir <http://127.0.0.1:8080/presentation?lang=fr> (présentation publique) ou <http://127.0.0.1:8080/login>.

En production, la racine web doit pointer vers `public/`.

---

## Interface web

L'espace de travail est découpé en **sept pages navigables**, pilotées par le menu vertical — toutes bilingues EN/FR (EN par défaut, bascule par session) :

| Page | URL | Rôle |
|---|---|---|
| **Question** | `/` | Poser une question, obtenir une réponse sourcée à citations numérotées (téléchargeables) |
| **Tâche agentique** | `/tache` | Demander une note / un rapport / une fiche ; le brouillon s'ouvre en modal |
| **Documents** | `/documents` | Liste des documents indexés (versions, résumé, téléchargement, suppression par mot de passe) |
| **Ajouter un document** | `/ajouter` | Indexer un texte, un PDF, une image ou un audio (catégorie requise) |
| **Catégories** | `/categories` | Créer et gérer les catégories documentaires |
| **Journal d'audit** | `/journal` | Traçabilité (DataTables, export CSV, purge de période ≥10 jours par mot de passe) |
| **Mon profil** | `/mon-profil` | Identité, adhésions ; modifications confirmées par mot de passe |

Chaque résultat s'ouvre dans un **modal** avec téléchargement **PDF** et **DOCX**.

Endpoints API (protégés par session) :

- `POST /api/upload.php` — indexer un fichier (multipart)
- `POST /api/ask.php` — `{"question": "..."}` → réponse + sources ; `{"instruction": "...", "mode": "task"}` → brouillon + sources
- `POST /api/summarize.php` — `{"document_id": 3}` → résumé + sources
- `POST /api/export.php` — `{"title", "content", "format": "pdf|docx"}` → fichier
- `GET /api/documents.php` — documents récents (JSON)
- `GET /api/download_document.php` — télécharger une version (journalisé)
- `GET /api/journal_export.php` — journal d'audit en CSV
- `POST /api/journal_purge.php` — purge du journal confirmée par mot de passe (entrées de plus de 10 jours ; la purge est elle-même journalisée)

---

## Tests

```powershell
php vendor/phpunit/phpunit/phpunit
```

**93 tests, 217 assertions** — couvrant l'authentification, le RBAC, les versions de documents, l'audit, le retrieval RAG filtré par permissions, les exports, les services multimodaux, le pipeline d'ingestion en arrière-plan et la boucle d'agent. Aucun serveur de base de données ni API requis (SQLite en mémoire + doubles déterministes).

---

## Sécurité

Requêtes SQL préparées · protection CSRF sur chaque POST · hachage des mots de passe (Argon2/bcrypt) · verrouillage progressif anti-force brute · RBAC résolu centralement (`PermissionService`) · refus par défaut · fichiers stockés hors racine web · aucun secret dans le code ou les logs · journal d'audit (ajout seul ; purge légale restreinte, re-authentifiée et elle-même journalisée) · en-têtes de sécurité (CSP).

---

## Documentation

- [docs/GUIDE_DE_REALISATION.md](docs/GUIDE_DE_REALISATION.md) — feuille de route d'exécution (phases, checklists, jalons)
- [docs/REGISTRE_PROBLEMES.md](docs/REGISTRE_PROBLEMES.md) — registre des problèmes et résolutions
- [docs/MARQUE_ET_PROPRIETE.md](docs/MARQUE_ET_PROPRIETE.md) — marque et propriété intellectuelle
- [docs/SECURITE_DONNEES_API.md](docs/SECURITE_DONNEES_API.md) — sécurité et confidentialité des données de l'API
- [docs/DEMO_VIDEO_ET_SOUMISSION.md](docs/DEMO_VIDEO_ET_SOUMISSION.md) — script de la vidéo et checklist de soumission

---

## Auteur

**Anatole KABORE**

**Consultant en systèmes d'information · Développeur Full Stack · Spécialiste en cybersécurité**

Basé à Ouagadougou (Burkina Faso), je suis un professionnel des technologies de l'information spécialisé dans la conception, l'architecture et le développement de solutions numériques sécurisées. J'accompagne les administrations, entreprises, ONG et organisations confessionnelles dans la transformation digitale de leurs processus métier à travers la mise en œuvre de plateformes web robustes, évolutives et centrées sur les besoins des utilisateurs.

Mon expertise couvre le développement Full Stack, l'architecture logicielle, la cybersécurité, la gestion des données et l'intégration de solutions d'intelligence artificielle appliquées à des problématiques concrètes. J'accorde une attention particulière à la sécurité, à la gouvernance des données, à la traçabilité des actions et à la pérennisation des connaissances organisationnelles.

À travers mes réalisations, je cherche à concevoir des outils capables de répondre aux défis réels rencontrés sur le terrain, notamment la gestion de l'information, la collaboration, le suivi-évaluation et la valorisation du patrimoine documentaire des organisations africaines.

- LinkedIn : <https://www.linkedin.com/in/anatole-kabore-73b076342>
- GitHub : <https://github.com/AnKBORE>

---

## Meta Global AI Developer Hackathon

- **Thème :** Build for Your World
- **Piste :** Indie Developer
- **Modèle IA :** Muse Spark Open
- **Licence :** MIT

---

## Licence

**MIT** — voir [LICENSE](LICENSE).
