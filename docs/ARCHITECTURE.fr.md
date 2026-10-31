# Architecture — Agentic Knowledge Workspace™

🇬🇧 English version: [ARCHITECTURE.md](ARCHITECTURE.md)

> Document de référence technique. Il décrit **ce que fait réellement le code**,
> pas ce qu'on aimerait qu'il fasse. Les limites connues sont signalées
> explicitement (section 12).

| Métrique | Valeur |
|---|---|
| Routes web | **8** |
| Endpoints API | **20** |
| Migrations | **21** |
| Services | **22** |
| Modèles | **10** |
| Vues | **17** |
| Fichiers de test | **23** |
| Tests / assertions | **100 / 230** |
| PHP | **8.4.27** · MySQL **8.4.13** |

---

## 1. Principes d'architecture

| Principe | Mise en œuvre |
|---|---|
| **MVC strict, sans framework** | `public/index.php` route, les contrôleurs (`controllers/`) orchestrent, les modèles (`models/`) parlent SQL, les vues (`views/`) affichent |
| **PSR-4** | `App\Core\`, `App\Controllers\`, `App\Models\`, `App\Services\`, `App\Validators\`, `App\Services\Ai\` |
| **Zéro dépendance lourde** | `dompdf` + `pdfparser` uniquement ; DOCX généré en OOXML natif via `ZipArchive` |
| **Stockage hors racine web** | `storage/files/` — jamais accessible par URL directe |
| **Point d'entrée unique** | `public/` est la seule racine exposée ; tout passe par le front controller ou `public/api/*` |
| **Traçabilité** | Toute action sensible écrit dans `journal` (append-only) |

---

## 2. Cartographie des dossiers

```
ai-knowledge-workspace/
├── bootstrap.php            # Assemblage des dépendances (conteneur simple)
├── public/
│   ├── index.php            # Routeur + rendu des vues
│   ├── api/                 # 20 endpoints JSON / fichiers
│   └── assets/              # css, js, vendor (DataTables, jQuery, Bootstrap, Select2)
├── controllers/             # AiAssistantController, AuthController…
├── core/                    # Auth, Csrf, Session, Env, i18n, helpers
├── models/                  # 10 modèles PDO
├── services/                # 22 services (dont 10 dans Ai/)
├── views/
│   ├── layouts/             # app.php, auth.php, public.php
│   ├── ai/                  # question, task, translate, upload, documents, journal, profil, categories
│   ├── public/              # landing.php (page de présentation bilingue)
│   └── auth/                # login
├── migrations/              # 21 paires .up.sql / .down.sql
├── tests/                   # 23 fichiers, 100 tests (SQLite en mémoire)
├── docs/                    # cette documentation
└── storage/files/           # documents + corrections (hors web root)
```

---

## 3. Modèle de données

| Table | Rôle |
|---|---|
| `utilisateur` | Comptes, `role_global` (membre / administrateur_fonctionnel / administrateur_technique), Argon2id |
| `jeton_compte` | Jetons de vérification / réinitialisation |
| `tentative_connexion` | Verrouillage progressif par e-mail + IP |
| `espace` / `adhesion` / `historique_adhesions` | Cloisonnement multi-espaces, rôles locaux (lecteur → responsable), historique |
| `documents` | Métadonnées : titre, fichier, `langue`, `categorie_id`, confidentialité, statut |
| `version_document` | Versions immuables : numéro, empreinte SHA-256, auteur, commentaire |
| `document_chunks` | Segments indexés (texte + vecteur JSON + `chunk_index`) |
| `categorie` | Catégories métier (id auto, slug unique, couleur) |
| `ingestion_jobs` | File d'ingestion en arrière-plan (upload non bloquant) |
| `rate_limit` | Compteurs par fenêtre fixe (anti-abus) |
| **`correction_document`** | **Versions corrigées validées : langue, texte corrigé, texte source, fichier d'origine, nom, auteur** |
| `document_translations` | Cache de traduction par segment (karaoké audio) |
| `moore_corpus` | Corpus mooré (phrases de référence) |
| `journal` | Audit append-only (acteur, action, objet, résultat, IP, détails JSON) |

### Point de vigilance : collations

`correction_document` a été créée en `utf8mb4_unicode_ci` alors que `documents`
est en `utf8mb4_0900_ai_ci`. **Toute comparaison SQL entre les deux colonnes
`langue` lève `Illegal mix of collations`.** La préférence de langue est donc
décidée **en PHP**, jamais en SQL (voir `RagService::preferCorrections()`).

---

## 4. Flux d'ingestion d'un document

```
Upload (public/api/upload.php)
   ├─ contrôle de taille / MIME / extension
   ├─ version_document : empreinte SHA-256, numéro incrémental
   ├─ fichier écrit dans storage/files/
   └─ ingestion_jobs : tâche en file
        ↓ (BackgroundWorker, hors requête HTTP)
     IngestionService
        ├─ PDF          → `smalot/pdfparser`
        ├─ DOCX         → `ZipArchive` sur word/document.xml
        ├─ audio        → TranscriptionService (cascade ASR)
        ├─ image / PDF scanné → VisionService (OCR page à page, appels parallèles)
        ├─ anonymisation (Anonymiseur) si demandé
        └─ RagService::indexDocument() → découpage 800 / chevauchement 100
                                       → EmbeddingService (hashing local, 0 coût)
                                       → INSERT document_chunks
```

**Pourquoi un worker** : un PDF scanné de 40 pages prend plusieurs minutes.
La requête HTTP rend la main immédiatement, `upload_status.php` expose l'avancement.

---

## 5. Moteur de récupération (RAG++)

### 5.1 Score hybride

```
score = 0.6 × similarité cosinus (vecteurs)  +  0.4 × score lexical (recouvrement de tokens)
```

Le mode d'embedding `META_EMBEDDING_MODE=local` calcule les vecteurs par
**hashing** : l'indexation ne coûte **aucun appel API**, au prix d'une
sémantique plus grossière qu'un vrai modèle d'embedding.

### 5.2 Élargissement aux voisins

Un titre ou un en-tête se trouve souvent dans le chunk **adjacent** au meilleur
résultat. `expandWithNeighbors()` fusionne `chunk_index ± 1` **et** le premier
chunk de la même version : le modèle voit le passage complet, pas un fragment.

### 5.3 Priorité aux corrections — le cœur du module d'apprentissage

```
retrieveSimilar()
   ├─ recherche vectorielle + lexicale sur document_chunks
   ├─ correctionHits()      ← recherche lexicale DÉDIÉE sur correction_document
   │                          (une correction n'est pas dans l'index : sans cela,
   │                           une question qui ne matche que le corrigé ne
   │                           trouverait aucun document)
   └─ preferCorrections()   ← remplace TOUS les chunks d'un document corrigé
                              par le texte corrigé, un seul extrait par document
```

Règles de choix de la correction :

1. celle écrite dans la **langue du document** d'abord ;
2. à défaut, la **plus récente**.

Le contexte envoyé au modèle est étiqueté :

```
NOTE: an extract marked « VERSION CORRIGÉE » is the validated reference for that
document. Always prefer it over any other extract of the same document, and say
so when you cite it.

[Extrait 1 — Meeting6 · VERSION CORRIGÉE MO du 2026-10-31]
Ne y waoongo, nẽn y kende…
```

### 5.4 Sources

`sourcesFor()` renvoie les extraits **cités** par le modèle ; si le modèle n'a
rien cité (oubli, réponse coupée), les **trois premiers extraits** sont listés.
Une réponse ancrée ne doit **jamais** paraître sans source.

Chaque source exposée porte :

```json
{
  "num": 1, "document_id": 30, "title": "Meeting6",
  "corrige": true,
  "correction": { "id": 5, "langue": "mo", "date": "2026-10-31 16:36:45",
                  "fichier": "meeting6_corrige_v1.txt" }
}
```

---

### 5.5 Deux modes de réponse — stricte ou développée

C'est le serveur qui décide du mode ; le modèle ne le devine pas.

| Mode | Déclencheur | Forme |
|---|---|---|
| **Fermée** | combien, quel, quand, qui, oui/non, une valeur précise | **une à trois phrases**, la première étant la réponse ; pas de préambule, pas d'inventaire non demandé, puces seulement si plusieurs éléments sont demandés |
| **Ouverte** | résume, parle-moi de, explique, décris, que dit, compare, points clés | **développée mais structurée** : une phrase d'ensemble, 3 à 6 points clés, une phrase de clôture seulement si elle apporte un fait — **150 mots maximum** |

La détection est dans `RagService::isOpenQuestion()` (marqueurs français,
anglais et mooré). Le mode retenu est écrit dans le prompt comme consigne
explicite : une même question garde donc toujours la même forme.

---
## 6. Tâche agentique — plan → retrieve → draft → verify → revise

`AiAssistantController::task()` → `RagService::task()` :

| Étape | Ce qui se passe |
|---|---|
| **plan** | Le modèle décompose l'instruction en 2 à 4 requêtes de recherche |
| **retrieve** | Chaque requête interroge `retrieveSimilar()` (donc **bénéficie des corrections**), fusion par `document_id \| version_id \| chunk_index` |
| **draft** | Rédaction ancrée, avec citations `[n]` obligatoires |
| **verify** | Un second appel vérifie que chaque affirmation est **soutenue** par le contexte ; renvoie les affirmations non soutenues |
| **revise** | Si des affirmations ne sont pas soutenues, un troisième appel corrige le brouillon |
| **conformité** | Une dernière vérification demande si la sortie **répond réellement à l'instruction**. Un brouillon ancré peut être hors sujet (décrire le projet quand une analyse de risques était demandée) ; s'il est rejeté, une passe de réécriture est faite puis la vérification est relancée. Cette étape ne bloque jamais : un échec d'analyse vaut « conforme ». |

La trace complète (`steps`) est renvoyée à l'interface : c'est ce qui distingue
une tâche **agentique** d'un simple prompt.

---

### 6.1 Document ou réponse ouverte

La boucle agentique ne produit pas toujours un document administratif. La règle 7
du prompt de tâche change la forme de sortie lorsque l'instruction est une
question ou une analyse (`?`, pourquoi / comment / quel / analyse / compare /
explique / évalue / propose) :

| Instruction | Sortie |
|---|---|
| « Rédige un rapport sur… », « fais une note… » | **document** avec la structure de son genre (note, rapport, compte rendu, courrier, leçon APC/PI) |
| « Analyse les risques de… », « quels sont les obstacles ? » | **réponse ouverte** : une synthèse de 2-3 phrases, 3 à 6 sections titrées et citées, une conclusion opérationnelle — 400 mots maximum, sans « objet », sans destinataires, sans formule de politesse |

Les deux modes obéissent aux mêmes règles d'ancrage : chaque affirmation vient
d'un extrait et porte sa citation `[n]`, et la réponse se termine par
`Sources : [n]`.

Les règles 8 à 10 complètent l'ensemble : décrire un document par sa nature
réelle et sa langue d'origine, présenter une version corrigée comme une
traduction, et produire une véritable traduction quand l'instruction le demande.
---

### 6.2 Cadrage documentaire — « à base de Meeting6 »

Quand l'instruction nomme un document (« à base de Meeting6 », « d'après
Meeting6 », « from Meeting6 »), ce nom **borne toute la tâche** :

1. `documentScope()` compare l'instruction aux titres accessibles par
   l'utilisateur (le titre le plus long l'emporte) ;
2. les résultats de récupération sont filtrés sur ce document, avec repli sur
   `chunksForDocument()` si le filtre ne laisse rien ;
3. le contexte est préfixé par `SCOPE: … N'utilise aucun autre document` ;
4. une étape `scope` apparaît dans la trace.

Sans cela, le planificateur interrogeait tout le corpus : un discours « à base
de Meeting6 » citait une matrice de projet sans rapport — les extraits étaient
ancrés, mais sur le mauvais document.
---

## 7. Pipeline de traduction

### 7.1 Les six directions

`fr ↔ en`, `fr ↔ mo`, `en ↔ mo` — aucune direction privilégiée.

### 7.2 Composition du prompt (ordre d'importance)

| Couche | Source | Rôle |
|---|---|---|
| **Règles strictes** | code | sortie seule, entités/chiffres/dates préservés, noms de mois localisés, pas d'omission |
| **Glossaire métier** | `Glossaire` | 20 termes (catéchiste, suivi-évaluation, vicariat…), **désactivé pour une cible mooré** |
| **Lexique SIL** | `LexiqueMoore` | 1 349 entrées vérifiées, recherche par **racine** (les formes fléchies `rabda` matchent `rabde`) |
| **Proverbes** | `Proverbes` | 40 proverbes mooré, référence **culturelle** uniquement |
| **Référence mooré** | `MooreReference` | orthographe (ɩ ʋ ã), pronoms, négation `ka…ye`, salutations |
| **Corrections validées** | `Apprentissage` | exemples de style approuvés par l'organisation |

### 7.3 Moteur d'apprentissage (`Apprentissage`)

Ce n'est **pas** un fine-tuning, et c'est un choix assumé : un fine-tuning sur
quelques dizaines de corrections surapprendrait et serait indémontrable.

```
1. Charge jusqu'à 500 corrections validées de la langue
2. Calcule la fréquence documentaire de chaque terme de la requête
3. Score = Σ IDF(terme) + bonus de récence + bonus de nombre de termes communs
4. Un seul exemple par document, déduplication par signature, budget 2 600 caractères
5. Extrait centré sur le terme commun, 480 caractères
```

Un terme rare (« vicariat ») pèse donc bien plus qu'un terme générique
(« projet »). Vérifié par `ApprentissageTest` (6 tests).

### 7.4 Garde-fous de production

| Garde-fou | Effet |
|---|---|
| `reasoning_effort: low` | supprime le « tâtonnement » du modèle sur une tâche mécanique |
| `max_tokens: 3000` | évite les réponses tronquées (les traductions s'arrêtent d'elles-mêmes) |
| Ratio de longueur | si la sortie est < 0,35× ou > 3,5× la source, **reprise** avec consigne de réparation |
| Température 0.0 / 0.7 | 0.0 pour traduire (déterminisme), 0.7 pour reformuler (variation) |
| Aucun `catch` muet | toute erreur remonte un `translation_error` explicite |

**Latence mesurée** : 4,6 s → **2,2-3,0 s** après ces réglages, sortie **stable**
sur trois exécutions consécutives.

---

## 8. Enregistrement de réunion (transcription)

### 8.1 Choix d'architecture

Le **streaming par tranches** a été implémenté puis **retiré** : la latence
intrinsèque (ASR + traduction par tranche) rendait le temps réel instable.

```
Aujourd'hui : enregistrement → Stop → UN appel sur l'audio complet
```

Un seul appel est **mesurable et reproductible** ; le direct dépendait de la
charge du modèle.

### 8.2 Interface

| Zone | Contenu |
|---|---|
| En-tête | micro, « Enregistrement de réunion », nom du document |
| Barre | REC, chrono (gelé en pause), **niveau audio** |
| Champs | **Langue parlée** (« Détection automatique » par défaut) · **Sortie** (« Même langue » = transcription) |
| Note | « La transcription est générée à la fin de l'enregistrement… » |
| Résultat | transcription + actions **↻ Reformuler · 📄 DOCX · ⤓ PDF** |

### 8.3 Décisions de conception

- **Sortie = même langue** → transcription seule ; **autre langue** → traduction.
- **Seuil RMS, pas pic** : un pic sous 0,012 jetait 6 s de parole calme — c'était
  la cause des « mots manquants ».
- **Pause réelle** : le chronomètre se fige et aucune tranche ne part.

---

## 9. Corrections humaines — le module d'apprentissage

### 9.1 Pourquoi

Un modèle laisse toujours passer des erreurs de vocabulaire ou d'expression.
Un **locuteur natif** (ou l'auteur du document) dépose une version corrigée :
le système la réutilise ensuite comme **référence fiable**.

### 9.2 Parcours

```
Documents indexés → ✏️  →  langues + nom proposé + fichier corrigé
                        →  correction_document (+ fichier dans storage)
                        →  immédiatement utilisable par le RAG
```

### 9.3 Règles

| Règle | Raison |
|---|---|
| **Aucune approbation** | celui qui dépose maîtrise la langue ou possède le document |
| **Fichier original conservé** | `storage/files/corrections/<doc>/<sha256>.<ext>` — servi **à l'octet près** au téléchargement |
| **Formats** | `.pdf`, `.docx`, `.txt`, `.md`, `.csv` — un `.docx` **reste** un `.docx` |
| **Nom proposé** | `<document>_corrige_vN.<ext>`, **modifiable**, `N` = nombre de corrections + 1 |
| **Suppression** | auteur de la correction **ou** `document.supprimer` ; effet **immédiat** sur les prompts |
| **Liste** | visible dans le modal, avec langue, auteur, date, taille |

### 9.4 Extraction de texte

`TexteExtracteur` : `.pdf` via `smalot/pdfparser`, `.docx` via `ZipArchive`
(paragraphes → lignes), texte brut sinon. Le texte extrait sert **uniquement** à
l'apprentissage et au RAG — il ne reconstruit jamais le fichier.

---

## 10. Sécurité

| Mesure | Détail |
|---|---|
| **Mots de passe** | Argon2id, politique de complexité, verrouillage progressif |
| **Sessions** | cookie `HttpOnly`, `SameSite=Lax`, régénération à la connexion |
| **CSRF** | 11/15 endpoints à effet de serre, champ `_csrf` **ou** en-tête `X-CSRF-Token` (wrapper `fetch` global) |
| **RBAC** | 2 niveaux : global (`membre` → `administrateur_technique`), local (`lecteur` → `responsable`) |
| **Rate limiting** | `rate_limit` : `ask` 30/5 min, `summarize` 20, `translate` 30, `reformulate` 30, `correction` 30 |
| **En-têtes** | CSP, `X-Frame-Options`, `X-Content-Type-Options: nosniff`, `Referrer-Policy` |
| **Cache** | pages en `no-store` : une page ancienne ne peut plus épingler un ancien `?v=` |
| **Fichiers** | stockage hors racine web, nom aléatoire (SHA-256), `basename()` sur tout nom servi |
| **Journal** | append-only ; purge légale restreinte (RGPD), re-authentifiée et journalisée |

---

### 10.1 Ce que le journal enregistre

| Famille | Actions |
|---|---|
| **Authentification** | `connexion` (succès **et** refus, verrouillage inclus), `deconnexion` |
| **Documents** | `document.depot`, `document.consultation`, `document.suppression`, `document.export`, `document.resume` |
| **Corrections** | `document.correction`, `document.correction.consultation`, `document.correction.telechargement`, `document.correction.suppression` |
| **Usage de l'IA** | `assistant.question`, `traduction`, `reformulation` |
| **Administration** | `categorie.creation/modification/suppression`, `profil.maj`, `profil.mot_de_passe`, `journal.export`, `journal.purge` |

Les requêtes **refusées** sont enregistrées, elles aussi avec `resultat = refus` :

| Refus | Détail |
|---|---|
| **Jeton CSRF absent ou périmé (419)** | `securite.refus` · `{"raison": "csrf", "chemin": "/api/translate.php"}` |
| **Permission manquante (403)** | `securite.refus` · `{"raison": "permission", "chemin": "…"}` |
| **Mot de passe de purge erroné** | `journal.purge` · `refus` |
| **Compte verrouillé / identifiants invalides** | `connexion` · `refus` |

L'export du journal (`journal.export`) et tout fichier généré
(`document.export`) sont audités : la piste d'audit ne peut pas être exfiltrée
sans laisser de trace.

**Choix assumé :** une requête **non authentifiée** rejetée en 401 n'est *pas*
enregistrée. Un robot pourrait sinon noyer le journal en ajout seul et enterrer
les événements réels. Seuls les refus impliquant une session réelle sont tracés.
---

## 11. Tests

`vendor/bin/phpunit` — **100 tests, 230 assertions**, sans base de données ni API.

| Fichier | Ce qu'il protège |
|---|---|
| `AuthTest`, `PermissionServiceTest` | authentification, RBAC, verrouillage |
| `DocumentTest`, `DocumentServiceTest` | documents, versions, catégories |
| `CategorieTest`, `AdhesionTest` | identifiants auto, slugs portables, adhésions |
| `RateLimiterTest` | fenêtre fixe, clés séparées, réinitialisation |
| `ApprentissageTest` | **moteur d'apprentissage** : isolation par langue, IDF, plafond d'exemples, suppression effective |
| `MooreResourcesTest` | lexique chargé, recherche par racine, proverbes, glossaire bidirectionnel |
| `AnonymiseurTest`, `AudioSplitterTest` | anonymisation, découpage audio |
| `RagServiceTest`, `ExportServiceTest` | récupération, exports |

Les doubles (`FakeMetaClient`) rendent les tests **déterministes** et **gratuits**.

---

## 12. Limites connues (assumées)

| Limite | Réalité |
|---|---|
| **Mooré, langue à faible ressource** | le lexique couvre 1 349 entrées contre ~10 650 au dictionnaire SIL : les tournures complexes restent approximatives. C'est écrit sur la page de présentation. |
| **Embeddings par hashing** | rapides et gratuits, mais moins sémantiques qu'un vrai modèle d'embedding |
| **Apprentissage par récupération** | pas de fine-tuning : le modèle est **guidé**, pas réentraîné |
| **CSP `unsafe-inline`** | hérité des scripts de vue ; à durcir (nonces) |
| **Direct temps réel** | retiré au profit d'un appel unique fiable |
| **Pas de pagination** sur certaines listes | acceptable jusqu'à quelques centaines de documents |

---

## 13. Décisions d'ingénierie notables

| Décision | Alternative écartée | Raison |
|---|---|---|
| **Un appel à l'arrêt** pour l'enregistrement | streaming par tranches | latence modèle ≈ temps réel → instable |
| **Retrieval** pour apprendre des corrections | fine-tuning | instantané, réversible, explicable, sans coût |
| **DOCX natif** (ZipArchive) | bibliothèque Word | zéro dépendance, contrôle total |
| **Hors-racine web** pour les fichiers | `public/` | évite toute exécution / fuite directe |
| **SQLite en tests** | MySQL de test | tests rapides, sans serveur |
| **`no-store` sur les pages** | cache navigateur | un correctif invisible est pire qu'un chargement lent |

---

## 14. Journal des incidents résolus

| Symptôme | Cause réelle | Correctif |
|---|---|---|
| Réponses sans source | réponse tronquée (`max_tokens` 1200) → pas de citation | plafond 3 000 + repli sur les 3 premiers extraits |
| Corrections ignorées (question/tâche) | `Illegal mix of collations` avalée par un `catch` | préférence de langue décidée en PHP |
| « Are you going today? » → « Do you want it today? » | forme fléchie `rabda` ≠ entrée `rabde` | recherche par racine (4 lettres) |
| Enregistrement muet pendant 47 s | deadlock : une tranche rejetée consommait un numéro de séquence | séquence allouée à l'envoi réel |
| Mots manquants en transcription | seuil sur le **pic** au lieu du **RMS** | seuil RMS 0,0015 |
| Correctif visible seulement en vidant le cache | pages HTML non versionnées | `Cache-Control: no-store` |
| Correction `.docx` refusée | liste blanche limitée à `.txt/.md/.csv` | `.pdf` et `.docx` acceptés + extraction |
| Téléchargement en `.txt` | fichier jeté, texte seul conservé | fichier original stocké et servi tel quel |

---

## 15. Pour aller plus loin

| Sujet | Document |
|---|---|
| Sécurité et données API | `docs/SECURITE_DONNEES_API.md` |
| Marque et propriété | `docs/MARQUE_ET_PROPRIETE.md` |
| Guide de réalisation | `docs/GUIDE_DE_REALISATION.md` |
| Registre des problèmes | `docs/REGISTRE_PROBLEMES.md` |
| Démonstration et soumission | `docs/DEMO_VIDEO_ET_SOUMISSION.md` |
