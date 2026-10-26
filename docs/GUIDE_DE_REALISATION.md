# Guide de réalisation — Agentic Multimodal Knowledge Workspace

> Feuille de route d'exécution du projet, de l'état actuel jusqu'à la soumission au **Meta Global AI Developer Hackathon**.
> Piste : **Indie Developer** · Thème : **Build for Your World** · Deadline : **4 novembre 2026, 21 h ET**.

> **Statut (2026-10-26)** : pipeline **validé de bout en bout** via Baseten (Plan B) ; **sources citées** + **tâche agentique** livrées ; **21 tests verts**.

---

## 0. Vision en une phrase

**Un espace de travail agentique et multimodal** qui transforme les documents, scans et enregistrements vocaux d'une organisation en connaissances exploitables, avec un assistant IA qui **répond, rédige et agit — en citant ses sources**.

### Ce qui le distingue d'un « Basic RAG Application » (explicitement à éviter)

| Un « basic RAG » | Notre projet |
|---|---|
| PDF texte uniquement | **Texte + PDF + scans (SAM 3.1) + voix (Muse Voice Transcribe)** |
| « Question → réponse » | **Tâches multi-étapes** (rédiger une note, lister les retards, générer un rapport) |
| Réponse sans traçabilité | **Sources citées** + brouillon prêt à l'emploi |
| Chatbot générique | **Domaine concret** : paroisses, ONG, écoles, associations en Afrique |

> ⚠️ Ne jamais présenter le projet comme « un chatbot qui répond aux questions sur des documents ». Toujours insister sur **l'agentique + le multimodal + la traçabilité**.

---

## 1. État actuel du projet (point de départ)

### ✅ Déjà réalisé

- Scaffold MVC complet (PHP 8.4.27 / MySQL 8.4.13).
- Pipeline RAG : `chunk → embed → retrieve → generate`.
- Client API Meta aligné sur l'API réelle : `POST /v1/responses`, modèle `muse-wonder-1.0`.
- Embedding **local** (hashing) par défaut → le RAG fonctionne sans endpoint d'embeddings.
- Services multi-modaux : `TranscriptionService` (voix), `VisionService` (scans).
- Base MySQL + schéma (5 tables).
- **UI web** réutilisant le design du socle (Bootstrap 5, Poppins, `app.css`) : upload + chat.
- Endpoints : `GET /`, `POST /api/ask.php`, `POST /api/upload.php`.
- **19 tests PHPUnit** verts.
- Scripts : `setup.ps1`, `setup.bat`, `serve.bat`, `tools/migrate.php`, `tools/index_document.php`.
- README EN + FR.

### ⏳ Reste à faire (voir phases ci-dessous)

- Crédits Meta + clé API.
- Brancher les vraies appels API (test réel).
- Enrichir la couche **agentique** (tâches multi-étapes, sources dans l'UI).
- Vidéo de démonstration (3 min).
- Repo **public** + soumission.

---

## 2. Architecture cible

```
Entrées (multi-modales)
  ├─ Texte / PDF      → extraction (smalot/pdfparser)
  ├─ Scan / photo     → SAM 3.1 (vision)
  └─ Audio / réunion  → Muse Voice Transcribe
          │
          ▼
   Chunking → Embedding (local ou API) → Stockage (documents, document_chunks)
          │
          ▼
   Assistant agentique
   ├─ Question → embedding → récupération des passages → Muse Wonder (muse-wonder-1.0)
   ├─ Tâches multi-étapes (note, rapport, liste des retards)
   └─ Réponse ancrée + sources citées
```

### Stack

| Couche | Technologie |
|---|---|
| Backend | PHP 8.4.27 (MVC strict) |
| Base | MySQL 8.4.13 (InnoDB, utf8mb4) |
| IA | Meta Model API : `POST https://api.meta.ai/v1/responses`, modèle `muse-wonder-1.0` |
| Frontend | Bootstrap 5 + design system du socle (Poppins, `app.css`) |
| Tests | PHPUnit 11 |

---

## 3. Phases de réalisation

> Chaque phase a un **objectif**, des **actions**, et un **critère de sortie** (ne passe à la suivante que quand il est atteint).

### Phase 0 — Comptes, crédits et environnement

**Objectif** : tout est prêt pour coder et appeler l'API.

1. **Meta Model API** :
   - Compte sur https://dev.meta.ai/
   - **Moyen de paiement** ajouté (obligatoire).
   - **Clé API** : *API keys → Create API key* → copier **immédiatement** la clé complète (`LLM_...`).
   - **Crédits $150** : soumettre le formulaire de demande (Full Name + Email + **Team ID** + Terms).
   - **Team ID** : dans l'URL après connexion (`?team_id=...` ou `/teams/...`).
   - **Limite de dépenses** : *Facturation → Spend limit = 150 $*.
2. **Discord** : serveur Meta Global AI Developer Hackathon → **Linked Roles → Builders → Terminer**.
3. **GitHub** : compte `AnKBORE`, dépôt `ai-knowledge-workspace` (→ **public** avant la soumission).
4. **Environnement local** : PHP 8.4.27 (WAMP), MySQL 8.4.13, composer (`tools/composer.phar`).

**Critère de sortie** : la clé API est dans `.env`, les crédits sont visibles dans *Facturation → Crédits gratuits*, et `php -v` montre **8.4.27**.

> ⚠️ **Ne fais AUCUN appel API tant que les crédits ne sont pas visibles** → sinon débit réel sur la carte.

---

### Phase 1 — Socle, base de données et sécurité

**Objectif** : un socle solide et sécurisé.

1. Base `ai_knowledge_workspace` créée + `php tools/migrate.php migrate` (5 tables).
2. `.env` renseigné (DB + Meta API).
3. Vérifier la **sécurité de base** :
   - requêtes préparées (PDO),
   - échappement HTML (`e()`),
   - RBAC (si intégré au socle),
   - fichiers hors racine web,
   - aucun secret dans le code/journaux.

**Critère de sortie** : `GET /` affiche l'UI, la base répond, aucun secret versionné.

---

### Phase 2 — Ingestion multi-modale

**Objectif** : pouvoir ajouter du texte, des PDF, des scans et de l'audio.

1. Texte / PDF : déjà géré (`smalot/pdfparser`).
2. **Scans** : `VisionService` → SAM 3.1 (endpoint à confirmer selon la doc ; sinon décrire la démarche).
3. **Voix** : `TranscriptionService` → Muse Voice Transcribe (idem).
4. Tester l'ingestion depuis l'UI (`#upload-form`) et la CLI (`tools/index_document.php`).

**Critère de sortie** : un document indexé apparaît dans la liste et crée des `document_chunks`.

---

### Phase 3 — RAG (récupération)

**Objectif** : retrouver les bons passages.

1. Vérifier `chunk()` (taille/chevauchement).
2. Embedding : **local** par défaut (déjà en place) ; `META_EMBEDDING_MODE=api` si un endpoint d'embeddings est confirmé.
3. `retrieveSimilar()` : cosinus + top-k.
4. Tests : `RagServiceTest`, `RagServiceIntegrationTest`.

**Critère de sortie** : une question renvoie les passages pertinents + une réponse générée.

---

### Phase 4 — Assistant agentique (le vrai différenciateur)

**Objectif** : dépasser le « Q&A ».

1. **Sources citées** : chaque réponse doit indiquer les documents/passages utilisés.
   - Ajouter au retour de `RagService::query()` les sources (titre + extrait).
   - Afficher les sources dans l'UI.
2. **Tâches multi-étapes** (au moins 2 scénarios) :
   - « Prépare une note pour le directeur sur les activités du trimestre » → récupère, synthétise, rédige.
   - « Quels projets sont en retard ? » → croise les documents avec les dates.
3. **Prompt système** clair : répondre UNIQUEMENT à partir des extraits, citer, ne pas inventer.
4. Journaliser les actions (audit) si possible.

**Critère de sortie** : la démo montre une **tâche rédigée** avec **sources**, pas juste une réponse.

---

### Phase 5 — Interface (UI/UX)

**Objectif** : une démo convaincante et cohérente avec le socle.

1. Page unique : upload + chat + liste des documents (déjà en place).
2. Ajouter :
   - l'affichage des **sources** sous la réponse,
   - un **état de chargement**,
   - la gestion des erreurs (clé absente, fichier vide).
3. Vérifier le **responsive** (mobile) et l'accessibilité de base.

**Critère de sortie** : un utilisateur peut uploader un document et poser une question **sans aide**.

---

### Phase 6 — Qualité et sécurité

**Objectif** : un projet fiable.

1. `phpunit` → **tous les tests verts** (19+).
2. `php -l` sur les fichiers modifiés.
3. Revue sécurité : `.env` ignoré, pas de clé dans le code, validation des uploads (taille/type).
4. Nettoyer les fichiers temporaires.

**Critère de sortie** : tests verts + `git status` propre (hors fichiers ignorés).

---

### Phase 7 — Vidéo de démonstration (3 min)

**Objectif** : montrer uniquement **ce qui a été construit pendant le hackathon**.

**Script proposé (3 min)** :
1. **0:00–0:30** — Problème : documents dispersés, connaissances perdues (contexte paroisses/ONG).
2. **0:30–1:00** — Upload d'un document + un scan + un audio (multi-modal).
3. **1:00–1:45** — Question naturelle → réponse **avec sources citées**.
4. **1:45–2:30** — Tâche agentique : « prépare une note… » → brouillon généré.
5. **2:30–3:00** — Ce qui a été construit pendant le hackathon (couche IA) vs le socle préexistant.

**Règles à respecter** :
- Montrer **clairement** ce qui est nouveau (couche IA) vs le socle.
- Pas de données client réelles.
- Vidéo accessible (YouTube/Loom non listé ou lien public).

**Critère de sortie** : une vidéo de ≤ 3 min, claire, qui montre les différenciateurs.

---

### Phase 8 — Publication GitHub

**Objectif** : un dépôt public, propre et crédible.

1. **Rendre le dépôt public** : *Settings → Danger Zone → Change visibility → Public*.
2. Vérifier le **README** (EN + FR) : positionnement, fonctionnalités, modèles, installation, tests.
3. Vérifier qu'**aucun secret** n'est versionné (`.env` ignoré, pas de clé).
4. Ajouter **Topics** : `ai php mysql rag knowledge-management semantic-search meta-ai hackathon`.
5. `git push` (dernier commit).
6. (Optionnel) Tag de version : `git tag v1.0.0 && git push --tags`.

**Critère de sortie** : `https://github.com/AnKBORE/ai-knowledge-workspace` accessible **sans connexion**, avec un README à jour.

---

### Phase 9 — Soumission

**Objectif** : livrer dans les délais, sans oubli.

1. **Formulaire de soumission** : https://cerebralvalley.ai/e/meta-ai-developer-hackathon/hackathon/submit
2. Renseigner :
   - le **lien du dépôt GitHub** (public),
   - le **lien de la vidéo** (accessible),
   - les **membres** (toi),
   - la **description** du projet.
3. Vérifier que tout est **accessibles sans compte**.
4. Soumettre **avant le 4 nov. 21 h ET** (pas de soumission tardive acceptée).
5. Conserver une **copie** de la soumission (capture/email).

**Critère de sortie** : confirmation de soumission reçue.

---

## 4. Alignement avec la notation (Indie Developer)

Le jury évalue (0–10, poids égaux) : **créativité, ambition technique, fonctionnalité**.

| Critère | Ce que le projet apporte | Preuve à montrer |
|---|---|---|
| **Créativité** | Assistant **agentique + multimodal** pour des organisations africaines | Démo : scan + voix + tâche rédigée |
| **Ambition technique** | RAG réel, API Meta, pipeline complet, sécurité | Dépôt + code + tests |
| **Fonctionnalité** | MVP qui marche de bout en bout | Vidéo + démonstration live |

> Stage 1 du jury : le projet doit **correspondre au thème** et **utiliser l'API Meta / les modèles ouverts**. → Toujours montrer l'appel à `muse-wonder-1.0`.

---

## 5. Calendrier des jalons

| Date | Jalon |
|---|---|
| **26 oct.** | Kickoff ; comptes, crédits, clé API, Discord |
| **27 oct. – 2 nov.** | Phases 2 à 6 (ingestion, agentique, UI, tests) |
| **3 nov.** | Vidéo de démonstration + répétition |
| **4 nov. 21 h ET** | **Soumission** (dépôt public + vidéo) |
| **5–8 nov.** | Jugement |
| **9 nov.** | Annonce des gagnants |

---

## 6. Risques et parades

| Risque | Parade |
|---|---|
| Crédits $150 absents | Ne pas appeler l'API ; relancer via #credit-requests / support ; utiliser les crédits partenaires (Baseten/Modal/Fireworks) |
| Endpoint transcription/vision incertain | Confirmer la doc Meta ; sinon démontrer le texte + PDF et mentionner la roadmap multimodale |
| API qui échoue en démo | Préparer un **jeu de données local** + captures ; avoir un fallback vidéo |
| Perçu comme « basic RAG » | Insister sur **agentique + multimodal + sources** dans la vidéo et le README |
| Dépôt privé à la soumission | Vérifier la visibilité **Public** avant de soumettre |
| Fuite de secret | `.env` ignoré ; jamais de clé dans le code/README |

---

## 7. Commandes utiles

```powershell
# Lancer le serveur (PHP 8.4.27)
cd "C:\wamp64\www\projet hackaton\ai-knowledge-workspace"
.\serve.bat
# ou : & "C:\wamp64\bin\php\php8.4.27\php.exe" -S 127.0.0.1:8080 -t public

# Indexer un document
& "C:\wamp64\bin\php\php8.4.27\php.exe" tools/index_document.php "Rapport" docs/sample-report.txt

# Migrations
& "C:\wamp64\bin\php\php8.4.27\php.exe" tools/migrate.php migrate

# Tests
& "C:\wamp64\bin\php\php8.4.27\php.exe" vendor/phpunit/phpunit/phpunit

# Git
git add -A
git commit -m "…"
git push
```

---

## 8. Checklist finale (à cocher avant soumission)

- [ ] Crédits $150 visibles dans *Facturation → Crédits gratuits*
- [ ] Clé API dans `.env` (jamais dans le repo)
- [ ] Test réel : upload + question → réponse avec sources
- [ ] Tâche agentique fonctionnelle (note/rapport)
- [ ] Tests PHPUnit verts
- [ ] README EN + FR à jour
- [ ] Dépôt **public** et accessible
- [ ] Vidéo **≤ 3 min** accessible
- [ ] Formulaire de soumission rempli (repo + vidéo + description)
- [ ] Soumis **avant le 4 nov. 21 h ET**

---

## 9. Suivi des problèmes & résolutions

Tous les blocages et écarts sont suivis dans **[REGISTRE_PROBLEMES.md](REGISTRE_PROBLEMES.md)** :

- chaque problème a un **ID** (O1, O2…) ;
- quand il est résolu : statut **✅** + résolution datée + ligne dans le **Journal des résolutions** ;
- la **checklist ci-dessus** est mise à jour en conséquence.

> Règle : **aucun problème ne disparaît sans trace**.

## 10. Plan B — Inférence partenaire (si l'API Meta est bloquée)

Si l'ajout d'un moyen de paiement Meta est impossible (ex. **carte prépayée refusée**), on peut utiliser un **fournisseur d'inférence partenaire** avec un modèle à pondération ouverte.

Le client est déjà compatible : `META_API_STYLE=openai` bascule sur le format **OpenAI** (`/v1/chat/completions`).

### Configuration (exemple Baseten)

```dotenv
META_API_STYLE=openai
META_MODEL_API_BASE_URL=https://inference.baseten.co
META_MODEL_API_KEY=<clé Baseten>
META_MODEL_NAME=<identifiant du modèle sur Baseten>
```

### Avec Modal

```dotenv
META_API_STYLE=openai
META_MODEL_API_BASE_URL=<URL OpenAI-compatible de votre déploiement Modal>
META_MODEL_API_KEY=<clé Modal>
META_MODEL_NAME=<nom du modèle>
```

### Vérifier

1. `php tools/index_document.php "Rapport" docs/sample-report.txt` (indexation locale, sans API).
2. Poser une question dans l'UI → la réponse doit venir du modèle partenaire.

> Les embeddings restent **locaux** (`META_EMBEDDING_MODE=local`) : seul le chat bascule sur le partenaire.

*Ce guide est un document vivant : coche chaque critère de sortie avant de passer à la phase suivante.*
