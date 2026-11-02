# Journal de construction — Agentic Knowledge Workspace™

🇬🇧 English version: [JOURNAL_DE_CONSTRUCTION.md](JOURNAL_DE_CONSTRUCTION.fr.md)

> **Document de traçabilité du travail réellement effectué**, du 26 octobre 2026
> (ouverture du hackathon) à la soumission.
>
> Ce journal n'est pas une présentation : c'est une **preuve**. Chaque ligne
> renvoie à des commits réels, vérifiables par n'importe qui avec `git log`.

---

## 0. Comment ce document est tenu

| Règle | Application |
|---|---|
| **Une entrée par jour travaillé** | ajoutée en fin de journée, jamais réécrite ensuite |
| **Chiffres, pas d'adjectifs** | nombre de commits, lignes, tests, fichiers |
| **Les erreurs sont écrites** | un incident résolu vaut mieux qu'un incident caché |
| **Vérifiable** | chaque affirmation renvoie à un hash, un fichier ou une commande |

**Pour compléter ce journal :** ajouter une section datée dans la chronologie
(§3), puis une ligne dans le tableau de suivi (§8). Ne jamais modifier une
entrée passée — les corrections se font par une nouvelle entrée.

---

## 1. État au 2 novembre 2026

### Volume produit

| Métrique | Valeur | Vérification |
|---|---|---|
| **Commits** | **264** (dont **254** depuis l'ouverture) | `git rev-list --count HEAD` |
| **Lignes ajoutées** | **+56 241** (dont +52651 depuis le 26/10) | `git log --shortstat` |
| **Lignes supprimées** | **−7 624** | idem |
| **Période couverte** | 24 octobre → 2 novembre (9 jours de travail) | `git log --date=short` |

### Le produit

| Domaine | Volume |
|---|---|
| Pages web | **10** routes |
| Endpoints API | **31** |
| Migrations SQL | **25** |
| Services métier | **22** (dont 10 dans `Ai/`) |
| Modèles | **10** |
| Vues | **19** |
| Fichiers de test | **25** |
| **Tests automatisés** | **121 tests · 281 assertions** |
| Documentation | **7 paires EN/FR + README · ≈ 300 Ko** |

### La pile

```
PHP 8.4.27 · MySQL 8.4.13 · MVC strict · PSR-4 · aucun framework
RAG hybride (0,6 vectoriel + 0,4 lexical) · embeddings locaux
Modèle : Muse Spark Open (Baseten) · ASR : cascade Baseten + whisper.cpp local
PDF : smalot/pdfparser · DOCX : ZipArchive (OOXML natif) · PDF : dompdf
```

---

## 2. Les 9 jours en un tableau

| Date | Commits | Lignes | Ce qui a été construit |
|---|---|---|---|
| **24/10** | 10 | +3 590 / −33 | *Avant l'ouverture* — socle : MVC, couche IA, démo RAG, extraction PDF |
| **26/10** | 53 | +15 828 / −1 184 | Repositionnement « agentique multimodal », UI web, premiers services multimodaux |
| **27/10** | 51 | +4 079 / −1 984 | Gouvernance : journal d'audit, CSP, pagination, versions, export CSV |
| **28/10** | 3 | +3 187 / −519 | Moteur RAG++ et interface bilingue EN/FR |
| **29/10** | 8 | +1 677 / −299 | Modèle de hackathon branché, ASR robuste, OCR des PDF scannés |
| **30/10** | 6 | +2 137 / −61 | Mooré : page de traduction, référence linguistique, banque de données |
| **31/10** | 71 | +15 676 / −1 326 | Sécurité, lexique, corrections humaines, boucle agentique complète |
| **01/11** | 18 | +1 706 / −739 | Livrable propre, i18n des dates, documentation, finitions UI |
| **02/11** | 22 | +3 712 / −416 | Audit complet, N‑N document↔espaces, comptes protégés, CSP par nonce, documentation bilingue |

---

## 3. Chronologie détaillée

### 24 octobre — le socle (avant l'ouverture)

**10 commits · +3 590 / −33**

Le hackathon n'est pas encore ouvert. Le travail commence par les fondations :
`Initial commit` (scaffold PHP 8.4 / MySQL 8.4 structuré en MVC), `README.fr.md`,
versions épinglées (**PHP 8.4.27**, **MySQL 8.4.13**), puis la couche IA :
`MetaClient`, service d'embeddings, service RAG, contrôleur assistant.

La journée se termine par une **démo RAG de bout en bout** : chargement `.env`,
`bootstrap.php`, endpoint `ask.php`, CLI d'indexation, runner de migrations,
extraction PDF.

> **Pourquoi c'est noté ici** : ces 10 commits sont antérieurs à l'ouverture.
> Ils sont mentionnés pour l'honnêteté du décompte, pas pour gonfler le total.

---

### 26 octobre — le repositionnement (ouverture)

**53 commits · +15 828 / −1 184**

Le positionnement initial (« RAG sur des documents ») est **abandonné le premier
jour** : le règlement exclut explicitement les applications RAG basiques.

Décision : devenir un **espace de travail agentique multimodal**.

| Commit représentatif | Ce qu'il change |
|---|---|
| `Reposition as agentic multimodal workspace` | le produit change de nature |
| `Add Muse Voice Transcribe + SAM 3.1 services` | première brique audio et vision |
| `Add web UI reusing the foundation design system` | upload + chat fonctionnels |
| `Add serve.bat` | serveur de développement reproductible |
| `Align MetaClient with live /v1/responses API` | le client parle la vraie API |
| `Add detailed phased execution guide` | la feuille de route est écrite avant le code |

**Volume** : 15 828 lignes en une journée — c'est la journée la plus lourde du
projet, avant même le travail sur la langue.

---

### 27 octobre — la gouvernance

**51 commits · +4 079 / −1 984**

Une application qui répond sur des documents d'organisation doit **prouver** ce
qu'elle fait. Cette journée construit la couche de confiance.

| Commit représentatif | Ce qu'il change |
|---|---|
| `Audit journal page (/journal), CSP and security headers` | la piste d'audit devient consultable |
| `Documents pagination (20 per page) with total count` | la base tient dans l'interface |
| `Performance indexes migration + Document model tests` | **les premiers tests** |
| `Document version history` (date, taille, SHA-256) | chaque dépôt est immuable et traçable |
| `Journal CSV export (journal.lire permission)` | l'audit devient exportable |

Le solde de lignes supprimées (−1 984) est le signe d'un **premier nettoyage**,
pas d'un ajout.

---

### 28 octobre — la précision

**3 commits · +3 187 / −519**

Peu de commits, gros volume : la journée est consacrée à un seul sujet, le
**moteur de récupération**.

| Commit | Ce qu'il apporte |
|---|---|
| `Precision RAG engine + bilingual EN/FR UI (68 tests)` | score hybride, interface bilingue, **68 tests** |
| `RAG++ hardening: vector cache, not-found retry, title boost, whisper.cpp` | la récupération devient robuste |
| `Stats widget, query-term highlighting` | la réponse devient lisible |

C'est le passage de « ça répond » à « ça répond **et on peut vérifier** ».

---

### 29 octobre — le modèle réel et l'ASR

**8 commits · +1 677 / −299**

| Commit | Ce qu'il apporte |
|---|---|
| `Connect Baseten hackathon model muse-spark-open-1026` | le modèle du hackathon est réellement utilisé |
| `Audio transcription back on Baseten Muse Spark Open; reject guardrail refusals` | l'audio entre dans le périmètre |
| `Local whisper.cpp re-enabled with auto language, resilient ASR fallback cascade` | **deux** moteurs ASR, avec repli |
| `OCR for scanned PDFs: page image extraction, downscaling, parallel vision calls` | les scans deviennent lisibles |
| `Fresh-clone install fixes: auto-create database, portable composer` | le dépôt devient installable par un tiers |

Ce dernier point compte : **un projet que le jury ne peut pas installer n'existe
pas.**

---

### 30 octobre — le mooré

**6 commits · +2 137 / −61**

La journée la plus déterminante sur le plan du différenciateur.

| Commit | Ce qu'il apporte |
|---|---|
| `Mooré translation page with SIL/Peace Corps linguistic reference` | une page de traduction dédiée, avec un socle linguistique **sourcé** |
| `Live karaoke audio translation in documents modal` | l'audio devient explorable segment par segment |
| `Mooré data bank: corpus+lexicon tables, corpus-aware prompts` | la langue devient une **donnée**, pas un prompt |
| `LAN-accessible server (0.0.0.0)` | testable depuis un téléphone |

**Le choix structurant** : ne pas « espérer » qu'un modèle généraliste sache le
mooré, mais **construire une banque de données** et l'injecter.

---

### 31 octobre — la sécurité, puis l'apprentissage

**71 commits · +15 676 / −1 326**

La journée la plus longue : **deux chantiers menés en parallèle**.

**Matin — durcissement**

| Commit | Ce qu'il apporte |
|---|---|
| `Security, tests and Mooré enrichment: CSRF on state-changing endpoints, MIT license` | CSRF généralisé, licence |
| `Rate limit: fixed-window limiter (table, service, 3 tests)` | protection anti-abus |
| `Mooré lexicon: merge the recent full SIL dictionary (+734 entries)` | **1 349 entrées** vérifiées |
| `Mooré proverbs: ship 40 proverbs (KABORÉ/MooreBurkina)` | référence culturelle |
| `Landing: transparency note on the low-resource limit` | **la limite est écrite dans le produit** |

**Après-midi — le module de corrections humaines**

Le constat : un modèle laisse toujours passer des erreurs. Plutôt que d'espérer
un modèle parfait, **capter la correction humaine**.

| Fonction livrée | Effet |
|---|---|
| Dépôt d'une version corrigée (PDF, DOCX, texte) | le natif corrige le système |
| Priorité dans la récupération | le corrigé remplace le texte brut |
| Badge + téléchargement du fichier d'origine | la source est vérifiable |
| Moteur d'apprentissage (`Apprentissage`) | pondération IDF, un exemple par document |
| Liste et suppression | la correction est réversible |

**Soir — la boucle agentique complète**

| Étape ajoutée | Question à laquelle elle répond |
|---|---|
| `scope` | « sur **quels** documents ? » |
| `conformity` | « la sortie **répond-elle** à la demande ? » |
| Mode réponse ouverte | « une analyse n'est pas un rapport » |

---

### 1ᵉʳ novembre — le livrable

**18 commits · +1 706 / −739**

Le produit savait répondre ; il ne savait pas encore **livrer**.

| Commit | Ce qu'il apporte |
|---|---|
| `Result modal gains a clean toggle` | une réponse devient un texte prononçable |
| `The clean toggle strips the TEXT, not the DOM` | correction d'un vrai défaut (§5) |
| `Language-aware wording and dates` | « 31 octobre 2026 », jamais `2026-10-31` |
| `Documentation update across the board` | 8 documents alignés |
| `Task page: source-document picker` (5 commits) | sélection explicite du périmètre |
| `Translate page no longer exhausts its quota` | la traduction à la frappe respecte le quota |

---

### 2 novembre 2026 — audits, cloisonnement des espaces, durcissement

**Point de départ.** Le MVP fonctionnait, mais un audit systématique (syntaxe, pages, données, en-têtes, endpoints, documentation) a révélé des écarts entre ce que l'interface montrait et ce que le serveur autorisait.

**Faille d'accès fermée.** La liste des documents et le téléchargement par identifiant ne vérifiaient que la permission **globale** `document.consulter`. Un membre pouvait donc lister et télécharger les documents d'un autre espace. La règle de visibilité a été extraite dans `PermissionService::documentsVisibles()`, partagée par la liste, la recherche, le téléchargement et le moteur de récupération. Vérifié avec un second compte réel : 0 document listé, téléchargement en 404.

**Journal d'audit fermé.** La page `/journal` était lisible par tout membre authentifié. Elle exige désormais `journal.lire` : 403 et refus journalisé.

**Gouvernance rendue visible.** Page `/administration` (comptes, espaces, adhésions), cycle de vie des espaces (clore / réouvrir), adhésions datées avec révocation immédiate, page `/espaces` pour choisir l'espace actif, menu filtré par les permissions.

**Document ↔ espaces en N-N.** `documents.espace_id` ne pouvait porter qu'un seul espace. Migrations 025 (`document_espace`) et 026 (`ingestion_jobs.espace_ids`) : un document est classé dans plusieurs espaces, l'accès suit l'**union**, le propriétaire garde toujours l'accès. Vérifié : dépôt dans deux espaces, retrait de l'un, un membre de l'autre voit le document puis ne le voit plus.

**Comptes protégés.** Le modèle est passé d'un siège unique à un **ensemble** : `accorderSuperAdmin()` / `retirerSuperAdmin()`, protection désactivable, journalisation (`compte.super_ajout`, `.retrait`, `.desactivation`), bouton couronne dans l'interface, `--grant` / `--revoke` en ligne de commande.

**Interface.** Inventaire d'administration en trois **onglets** DataTables (recalcul des largeurs à l'affichage), badges d'espaces et filtre par espace dans la liste des documents, modale de contrôle (espace + confidentialité) protégée par mot de passe.

**Durcissement.** CSP par **nonce** (plus de `'unsafe-inline'` pour les scripts), négociation de contenu sur les endpoints (401 JSON au lieu d'une redirection), garde de `/categories`, liaison **déléguée** du bouton supprimer, indépendante de l'ordre d'initialisation.

**Vérifications au 2 novembre.**

| Contrôle | Résultat |
|---|---|
| `git rev-list --count HEAD` | **262** |
| `phpunit` | **121 tests · 281 assertions** |
| `tools/smoke_admin_endpoints.php` | **27 vérifications** (9 endpoints × 3 profils) |
| `tools/smoke_endpoints_anonymous.php` | **31 endpoints × 2 méthodes** : tout refusé, aucune donnée |
| `tools/lint_docs.php` | aucun défaut |
| Base de démonstration | 3 comptes (2 protégés) · 3 espaces · 6 adhésions · 9 documents · 25 migrations |

**Ce que cet audit a changé dans la méthode.** Trois des défauts trouvés venaient de mes propres scripts de modification (insertion dans une cellule de tableau, commentaire contenant des `\n` littéraux, substitution annoncée mais non appliquée). Chaque correction est désormais suivie d'une **vérification du rendu** : comptage `<th>`/`<td>`, nonce par page, statut HTTP réel.

---
## 4. Ce qui a été produit, par domaine

### Ingestion

PDF, DOCX, CSV, Markdown, images, **PDF scannés (OCR page à page)**, **audio
(ASR)**. Chaque dépôt crée une **version immuable** avec empreinte SHA-256.

### Recherche

Score hybride `0,6 × cosinus + 0,4 × lexical`, expansion aux segments voisins,
détection d'intention « catalogue », recherche élargie en cas d'échec,
**priorité aux corrections**.

### Agentique

`plan → retrieve → scope → draft → verify → conformity`, avec trace exposée dans
l'interface. Forme de sortie adaptée à l'instruction (document ou réponse
ouverte).

### Langue

6 directions FR ↔ EN ↔ MO. Quatre couches injectées : référence linguistique,
glossaire métier, lexique SIL (1 349 entrées), proverbes. **Aucun fine-tuning** :
apprentissage par récupération d'exemples validés.

### Audio

Enregistreur intégré (WAV navigateur, vu-mètre, anti-silence), transcription en
un appel unique à l'arrêt, traduction, export DOCX/PDF, reformulation **à partir
de l'audio**.

### Gouvernance

Journal **en ajout seul** (21 types d'actions, refus compris), RBAC à 2 niveaux,
CSRF, rate limiting, CSP, stockage hors racine web, purge légale sous conditions
strictes.

### Livrable

Bascule ✦ : retire citations, sources et annotations de travail, puis export
DOCX/PDF contenant **le texte seul**.

---

## 4bis. Inventaire détaillé — ce qui a été créé, et quand

Cette section ne cite pas des intentions : elle liste **les fichiers qui existent
aujourd'hui dans le dépôt**, avec la date de leur premier commit. Vérifiable par
`git log --diff-filter=A --format=%ad -- <chemin>`.

### 24 octobre — socle technique (9 fichiers)

| Type | Fichiers |
|---|---|
| API | `ask.php` |
| SQL | `001_ai_rag` |
| Services | `MetaClient`, `EmbeddingService`, `RagService` |
| Tests | `EmbeddingServiceTest`, `EnvTest`, `RagServiceTest`, `RagServiceIntegrationTest` |

### 26 octobre — ouverture : gouvernance et multimodal (43 fichiers)

| Type | Fichiers créés |
|---|---|
| **API (5)** | `upload`, `documents`, `delete_document`, `export`, `summarize` |
| **SQL (11)** | `utilisateur`, `jeton_compte`, `tentative_connexion`, `securite_compte`, `espace`, `adhesion`, `historique_adhesions`, `journal`, `document`, `version_document`, `document_chunks_version` |
| **Modèles (7)** | `Utilisateur`, `JetonCompte`, `TentativeConnexion`, `Espace`, `Adhesion`, `Document`, `VersionDocument` |
| **Services (8)** | `AuthentificationService`, `PermissionService`, `DocumentService`, `JournalService`, `ExportService`, `TranscriptionService`, `VisionService`, `AudioSplitter` |
| **Tests (11)** | `AuthentificationServiceTest`, `PermissionServiceTest`, `PolitiqueMotDePasseTest`, `DocumentServiceTest`, `ExportServiceTest`, `JournalServiceTest`, `MultiModalServicesTest`, `AudioSplitterTest`, `FakeMetaClient`… |

C'est la journée la plus dense en **structure** : le modèle de données complet
(utilisateurs, espaces, adhésions, documents, versions, journal) est posé en une
seule journée, avec ses tests.

### 27 octobre — catégories, exports, anonymisation (10 fichiers)

| Type | Fichiers |
|---|---|
| API | `categorie_save`, `categorie_delete`, `download_document`, `journal_export` |
| SQL | `014_indexes`, `015_categories` |
| Modèle | `Categorie` |
| Service | `Anonymiseur` |
| Tests | `AnonymiseurTest`, `DocumentTest` |

### 28 octobre — précision de la récupération (2 fichiers)

| Type | Fichiers |
|---|---|
| API | `journal_purge` |
| Tests | `RagServiceScopeTest` |

Peu de fichiers nouveaux : la journée porte sur la **réécriture** du moteur de
récupération, pas sur de nouvelles surfaces. C'est le sens des 3 187 lignes
ajoutées pour seulement 3 commits.

### 29 octobre — ingestion en arrière-plan et ASR (7 fichiers)

| Type | Fichiers |
|---|---|
| API | `upload_status` |
| SQL | `016_ingestion_jobs` |
| Modèle | `IngestionJob` |
| Services | `IngestionService`, `BackgroundWorker` |
| Tests | `AgentTaskTest`, `IngestionServiceTest` |

### 30 octobre — le mooré entre dans le produit (6 fichiers)

| Type | Fichiers |
|---|---|
| API | `translate`, `translate_document` |
| SQL | `017_document_translations`, `018_moore_corpus` |
| Services | `DocumentTranslationService`, `MooreReference` |

### 31 octobre — sécurité, corrections, apprentissage (24 fichiers)

| Type | Fichiers |
|---|---|
| **API (6)** | `correction_save`, `correction_list`, `correction_delete`, `correction_download`, `reformulate`, `transcribe_chunk` |
| **SQL (4)** | `016_rate_limit`, `017_corrections`, `018_correction_filename`, `019_correction_file` |
| **Modèle (1)** | `Correction` |
| **Services (6)** | `Apprentissage`, `LexiqueMoore`, `Glossaire`, `Proverbes`, `RateLimiter`, `TexteExtracteur` |
| **Tests (5)** | `ApprentissageTest`, `MooreResourcesTest`, `RateLimiterTest`, `CategorieTest`, `AdhesionTest` |

### Récapitulatif par catégorie

| Catégorie | Total | Répartition par jour |
|---|---|---|
| **Endpoints API** | 20 | 1 · 5 · 4 · 1 · 1 · 2 · 6 |
| **Migrations SQL** | 21 | 1 · 11 · 2 · — · 1 · 2 · 4 |
| **Modèles** | 10 | — · 7 · 1 · — · 1 · — · 1 |
| **Services** | 22 | 3 · 8 · 1 · — · 2 · 2 · 6 |
| **Fichiers de test** | 23 | 4 · 11 · 2 · 1 · 2 · — · 5 |

> **Note honnête sur les migrations** : trois numéros sont utilisés deux fois
> (`016`, `017`, `018`), car deux chantiers distincts ont été menés le même jour.
> Le runner applique les fichiers par ordre alphabétique : les 21 migrations
> passent, mais la numérotation n'est pas propre.

### Ce que l'inventaire ne dit pas

Un fichier créé le 31 octobre n'est pas né en une fois : `RagService.php` a été
modifié **une trentaine de fois** entre le 24 octobre et le 1ᵉʳ novembre. La date
de création donne l'apparition d'une surface, pas son état final.
---

### Ajouts du 2 novembre

| Fichier | Rôle |
|---|---|
| `public/api/admin_user_save.php` | création de compte (`compte.inviter`) |
| `admin_user_statut.php`, `admin_user_delete.php`, `admin_user_role.php` | suspension, suppression logique, changement de rôle |
| `admin_user_super.php` | accorder / retirer la protection Super Admin |
| `admin_espace_save.php`, `admin_espace_statut.php` | création d'espace, clôture et réouverture |
| `admin_adhesion_save.php`, `admin_adhesion_end.php` | adhésion, révocation immédiate |
| `document_controle.php` | espace et confidentialité d'un document existant |
| `espace_actif.php` | choix de l'espace de travail actif |
| `views/ai/administration.php`, `views/ai/espaces.php` | administration en onglets, sélection d'espace |
| `migrations/023` → `026` | super admin, contexte d'ingestion, liaison document‑espace |
| `tools/smoke_admin_endpoints.php`, `smoke_endpoints_anonymous.php`, `lint_docs.php` | vérifications exécutables |
| `tests/PermissionServiceDocumentsVisiblesTest.php`, `UtilisateurSuperTest.php` | visibilité par union, comptes protégés |
## 4ter. Audit du MVP — 1ᵉʳ novembre 2026

**Méthode** : chaque route et chaque endpoint a été appelé réellement avec une
session authentifiée, puis une matrice statique (méthode HTTP, CSRF, limite de
débit) a été construite pour les 20 endpoints. Des constats, pas des impressions.

### Ce qui passe

| Contrôle | Résultat |
|---|---|
| **9 pages** (`/`, `/tache`, `/traduire`, `/ajouter`, `/documents`, `/journal`, `/mon-profil`, `/categories`, `/presentation`) | **200** |
| **6 endpoints de lecture** (documents, liste/téléchargement de correction, statut d'upload, téléchargement, CSV du journal) | **200** |
| **Endpoints d'écriture** (question, tâche, traduction, reformulation, export, résumé) | **200** avec un contenu réel |
| **Refus** (mot de passe erroné, fenêtre de purge trop large, `document_id` absent) | **403 / 422**, jamais un plantage |
| **CSRF** sur chaque endpoint à effet de bord | présent |
| **Journalisation** des écritures et des refus | présente |
| **TODO / FIXME laissés dans le code** | **aucun** |

### Ce qui était cassé, et corrigé le jour même

| # | Constat | Risque | Correctif |
|---|---|---|---|
| 1 | `categorie_save` / `categorie_delete` ne rejetaient un GET que par le CSRF | fragile : la garde dépendait d'un autre mécanisme | **405 POST-only** explicite |
| 2 | `delete_document` exigeait le mot de passe mais **sans limite de débit** | force brute du mot de passe avec une session volée | 10 / 5 min |
| 3 | `export` sans limite de débit | la génération PDF coûte du CPU | 30 / 5 min |
| 4 | `transcribe_chunk` sans limite de débit | abus de coût ASR | 60 / 5 min |
| 5 | `translate_document` sans limite de débit | abus de coût de traduction | 300 / 5 min |
| 6 | `upload` sans limite de débit | saturation du disque | 20 / 5 min |

Vérifié après correction : `GET /api/categorie_save.php` → **405** ; les endpoints
d'écriture répondent toujours **200** ; les 100 tests restent verts.

### Limites connues, mesurées et non supposées

| Limite | Réalité |
|---|---|
| **Couverture de test des endpoints** | **14 endpoints sur 20** ne sont jamais mentionnés dans un fichier de test. Les 100 tests couvrent les services et les règles métier, pas la couche HTTP |
| **Ingestion en arrière-plan** | `forking is not supported on this platform` sous Windows : un dépôt lourd est traité en synchrone |
| **Aucune mesure de qualité** | pas de précision@k, pas de score de traduction — rien ne mesure la qualité des réponses sur un jeu de référence |
| **JavaScript** | zéro test automatisé ; la bascule de nettoyage, le sélecteur et l'enregistreur ont été vérifiés à la main |
---

## 5. Les erreurs, écrites

Un journal de construction sans erreur est un journal faux. Les plus
significatives :

| Incident | Cause réelle | Correctif |
|---|---|---|
| Corrections ignorées dans les réponses | `Illegal mix of collations` avalée par un `catch` | préférence de langue décidée en PHP |
| Réponses tronquées, sans sources | plafond `max_tokens` à 1 200 | 3 000 + repli sur les 3 premiers extraits |
| Discours citant un autre projet | plan cherchant dans **tout** le corpus | `documentScope()` + sélection explicite |
| Le corrigé **remplaçait** l'identité du document | substitution destructive | identité + original + corrigé = **trois** extraits |
| Nettoyage laissant les titres | traitement du **HTML** au lieu du **texte** | nettoyage sur `currentResult.content` |
| Barre de défilement dans le sélecteur | `max-height` + `overflow-y: auto` | hauteur libre, étiquettes sur plusieurs lignes |
| « Trop de requêtes » sur la traduction | traduction à chaque frappe, quota inadapté | silence 1,2 s + texte inchangé ignoré + pause après 429 |
| Correctif invisible après déploiement | onglet exécutant l'ancien JavaScript | pages en `no-store`, `Ctrl+F5` documenté |

| Suppression inerte dans les documents | liaison posée en **fin** de script | délégation sur `document` + liaison précoce et idempotente |
| Tableau des comptes désaligné (erreur DataTables) | bloc inséré dans la cellule du **nom**, avalant e-mail/rôle/statut | ligne reconstruite, 6 colonnes pour 6 en-têtes |
| Les dépôts échouaient sur un `TypeError` | `\n` littéraux dans un commentaire : l'affectation `$result` était commentée | commentaire rétabli, balayage des `\n` littéraux |
| `/categories` lisible par un lecteur | aucune garde sur la page | 403 + message, refus journalisé |
| Trois endpoints répondant 302 | `$respond` ne lisant que `X-Requested-With` | négociation de contenu, 401 JSON |
| Script inline bloqué par la CSP | nonce absent sur le script du journal (substitution non appliquée) | nonce posé, vérifié page par page |Détail complet : `docs/REGISTRE_PROBLEMES.md` (65 entrées : 22 résolues, 12 ouvertes, 31 incidents de phase).

---

## 6. Preuves vérifiables par le jury

```powershell
# Les vérifications ajoutées le 2 novembre
php tools/smoke_admin_endpoints.php        # 27 vérifications, 9 endpoints d'administration
php tools/smoke_endpoints_anonymous.php    # 31 endpoints × 2 méthodes, sans session
php tools/lint_docs.php                    # structure de la documentation
```

```powershell
# Volume et rythme
git rev-list --count HEAD
git log --date=short --pretty=format:'%ad' | Group-Object | Sort-Object Name

# Une journée précise
git log --since="2026-10-31 00:00" --until="2026-11-01 00:00" --oneline

# Le détail d'une décision
git show <hash>

# L'état de la suite de tests
C:\wamp64\bin\php\php8.4.27\php.exe -d memory_limit=1G vendor\phpunit\phpunit\phpunit --configuration phpunit.xml --no-coverage
```

---

## 7. Ce que le journal ne prétend pas

| Affirmation | Réalité |
|---|---|
| « 8 jours » | 8 jours **calendaires**, avec des journées de 3 à 71 commits ; le 28 et le 30 comptent peu de commits mais de gros volumes |
| « 100 tests » | ils couvrent la logique métier, **pas** le JavaScript ni les endpoints de correction |
| « Traduction mooré » | lexique limité à 1 349 entrées ; les tournures complexes restent approximatives |
| « RAG sémantique » | embeddings par **hachage**, moins sémantiques qu'un vrai modèle |
| « Production ready » | projet de hackathon : CSP à durcir, pagination partielle, pas de mesure qualité métier |

---

## 8. Suivi des mises à jour de ce document

| Date | Événement | Commit |
|---|---|---|
| 01/11 | Création du journal (rétrospectif 24/10 → 01/11) | `8c453f6` |
| 01/11 | Inventaire détaillé, fichier par fichier | `38dd424` |
| 01/11 | Version anglaise, français renommé en `.fr.md` | `92407bd` |
| 01/11 | Démo & soumission et marque traduites | `f14e7e6` |
| 01/11 | Note de sécurité traduite (18 sections) | `f554194` |
| 01/11 | Registre des problèmes traduit (40 incidents) | `e5629d0` |
| 01/11 | Guide de réalisation traduit (13 sections) | `ca57984` |
| 01/11 | Audit des liens : les docs FR renvoient aux versions FR | `dcb6d1f` |
| 02/11 | Audit complet, N-N document↔espaces, comptes protégés, CSP par nonce | `ffeef40` |
| | | |

---

## 9. À remplir jusqu'à la soumission

| Étape | Échéance | État |
|---|---|---|
| Répétition chronométrée de la démonstration | 1ᵉʳ novembre | ☐ |
| Seconde répétition + prise de secours | 2 novembre | ☐ |
| **Enregistrement de la vidéo** | 3 novembre | ☐ |
| **Soumission** | 4 novembre, 21 h ET | ☐ |
| Confirmation reçue | — | ☐ |

---

*Dernière mise à jour : 2 novembre 2026.*
