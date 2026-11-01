# Journal de construction — Agentic Knowledge Workspace™

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

## 1. État au 1ᵉʳ novembre 2026

### Volume produit

| Métrique | Valeur | Vérification |
|---|---|---|
| **Commits** | **220** (dont **210** depuis l'ouverture) | `git rev-list --count HEAD` |
| **Lignes ajoutées** | **+47 880** (dont +44 290 depuis le 26/10) | `git log --shortstat` |
| **Lignes supprimées** | **−6 145** | idem |
| **Période couverte** | 24 → 1ᵉʳ novembre (8 jours de travail) | `git log --date=short` |

### Le produit

| Domaine | Volume |
|---|---|
| Pages web | **8** routes |
| Endpoints API | **20** |
| Migrations SQL | **21** |
| Services métier | **22** (dont 10 dans `Ai/`) |
| Modèles | **10** |
| Vues | **17** |
| Fichiers de test | **23** |
| **Tests automatisés** | **100 tests · 230 assertions** |
| Documentation | **8 fichiers · ≈ 160 Ko** |

### La pile

```
PHP 8.4.27 · MySQL 8.4.13 · MVC strict · PSR-4 · aucun framework
RAG hybride (0,6 vectoriel + 0,4 lexical) · embeddings locaux
Modèle : Muse Spark Open (Baseten) · ASR : cascade Baseten + whisper.cpp local
PDF : smalot/pdfparser · DOCX : ZipArchive (OOXML natif) · PDF : dompdf
```

---

## 2. Les 8 jours en un tableau

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

Détail complet : `docs/REGISTRE_PROBLEMES.md` (40 entrées).

---

## 6. Preuves vérifiables par le jury

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
| 01/11 | Création du journal (rétrospectif 24/10 → 01/11) | *(à compléter)* |
| | | |
| | | |
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

*Dernière mise à jour : 1ᵉʳ novembre 2026.*
