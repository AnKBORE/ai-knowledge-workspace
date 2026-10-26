# Guide de réalisation — Agentic Multimodal Knowledge Workspace

> Feuille de route d'exécution, de l'état actuel jusqu'à la soumission au **Meta Global AI Developer Hackathon**.
> Piste : **Indie Developer** · Thème : **Build for Your World** · Deadline : **4 novembre 2026, 21 h ET**.
> **Statut (2026-10-26)** : socle + RAG + assistant agentique (sources + tâches) validés ; MVP **partiel** (auth/RBAC, versions, audit à construire).

---

## 0. Vision en une phrase

**Un espace de travail agentique et multimodal** qui transforme les documents, scans et enregistrements vocaux d'une organisation en connaissances exploitables, avec un assistant IA qui **répond, rédige et agit — en citant ses sources**.

### Ce qui le distingue d'un « Basic RAG Application » (explicitement à éviter)

| Un « basic RAG » | Notre projet |
|---|---|
| PDF texte uniquement | **Texte + PDF + scans (SAM 3.1) + voix (Muse Voice Transcribe)** |
| « Question → réponse » | **Tâches multi-étapes** (note, rapport, liste des retards) |
| Réponse sans traçabilité | **Sources citées** + brouillon prêt à l'emploi |
| Chatbot générique | **Domaine concret** : paroisses, ONG, écoles, associations en Afrique |
| Aucune gouvernance | **RBAC, versions immuables, journal d'audit** (repris du socle `espace_collaboratif`) |

> ⚠️ Ne jamais présenter le projet comme « un chatbot qui répond aux questions sur des documents ». Toujours insister sur **agentique + multimodal + traçabilité + gouvernance**.

---

## 1. État actuel (point de départ vérifié)

### ✅ Déjà réalisé

- Scaffold MVC (PHP 8.4.27 / MySQL 8.4.13), PSR-4, `.env`, autoload.
- **RAG** : `chunk → embed → retrieve → generate`, embedding **local** (hashing) par défaut.
- **Client API** compatible **Meta** (`/v1/responses`, `muse-wonder-1.0`) **et partenaires** (`/v1/chat/completions`, `META_API_STYLE=openai`).
- **Assistant agentique** : `answer()` avec **sources citées**, `task()` pour **rédiger un brouillon**.
- **UI** : upload + question + sources + tâche agentique.
- **Upload PDF** (`smalot/pdfparser`) testé ; audio (transcription) et image (vision) branchés côté code.
- **21 tests PHPUnit** verts ; scripts `setup` / `serve` / `migrate` / `index_document`.
- Docs : README EN/FR, guide, registre, licence MIT, script vidéo/soumission.
- Dépôt **public** : `https://github.com/AnKBORE/ai-knowledge-workspace`.

### ❌ Manquant pour le MVP complet (vérifié dans le code)

| Fonctionnalité MVP | État | À faire |
|---|---|---|
| Auth sécurisée + RBAC | ❌ | Tables `utilisateur`/`adhesion`, `Auth`, `PermissionService` |
| Gestion documentaire + versions | ❌ | `document` + `version_document` (SHA-256, versions immuables) |
| Scans via SAM 3.1 | ⚠️ | Confirmer l'endpoint, tester |
| Voix via Muse Voice Transcribe | ⚠️ | Confirmer l'endpoint, tester |
| Résumé automatique dédié | ⚠️ | Endpoint/bouton « Résumer » |
| Journalisation (audit) | ❌ | Table `journal` append-only + `JournalService` |

---

## 2. Architecture cible (alignée sur `espace_collaboratif`)

```
                        ┌──────────────────────────────┐
   Navigateur ────────► │  public/index.php (front)     │
                        └──────────────┬───────────────┘
                                       ▼
                        ┌──────────────────────────────┐
                        │  core/  Router · Request      │
                        │  Session · Csrf · Auth        │
                        │  SecurityHeaders · View       │
                        └──────────────┬───────────────┘
                                       ▼
                        ┌──────────────────────────────┐
                        │  controllers/                 │
                        │  AuthController · Document…   │
                        │  AiAssistantController        │
                        └──────────────┬───────────────┘
                                       ▼
              ┌────────────────────────┴────────────────────────┐
              ▼                        ▼                        ▼
   services/ (métier)        services/Ai/ (IA)          models/ (PDO)
   AuthentificationService   MetaClient · RagService    Utilisateur
   PermissionService         EmbeddingService           Document
   DocumentService           TranscriptionService       VersionDocument
   JournalService            VisionService              Journal
              │                        │                        │
              └────────────────────────┴────────────────────────┘
                                       ▼
                        MySQL 8.4.13 (InnoDB, utf8mb4)
```

### Stack

| Couche | Technologie |
|---|---|
| Backend | PHP 8.4.27 (MVC strict, PSR-4) |
| Base | MySQL 8.4.13 (InnoDB, utf8mb4) |
| IA | Meta (Muse Wonder / Muse Spark Open) **ou** partenaire (Baseten/Modal) |
| Frontend | Bootstrap 5 + design system du socle (Poppins, `app.css`) |
| Tests | PHPUnit 11 |

### Règles d'architecture (héritées du socle)

1. **Une seule porte d'entrée** : `public/index.php` ; tout le reste hors racine web.
2. **Service = métier** ; **Modèle = données** ; **Contrôleur = HTTP** ; jamais de SQL dans une vue.
3. **Permissions résolues uniquement par `PermissionService`** — jamais dans les vues.
4. **Journal d'audit en ajout seul** — jamais de `UPDATE`/`DELETE` sur `journal`.
5. **Versions immuables** — un dépôt crée une ligne, jamais une mise à jour.
6. **Secrets** : `.env` uniquement, jamais dans le code, les logs ou le dépôt.

---

## 3. Modèle de données cible

### 3.1 Comptes & sécurité

| Table | Colonnes clés | Règle |
|---|---|---|
| `utilisateur` | `id`, `tenant_id`, `courriel` (unique), `nom`, `prenom`, `mot_de_passe` (hash), `role_global`, `statut`, `derniere_connexion_le`, `cree_le`, `maj_le`, `supprime_le` | **Suppression logique** uniquement |
| `jeton_compte` | `id`, `utilisateur_id`, `usage_jeton` (`activation`/`reinitialisation`), `empreinte` (SHA-256), `expire_le`, `utilise_le` | Jeton **brut jamais stocké** |
| `tentative_connexion` | `empreinte_courriel`, `echecs`, `verrouille_jusqua`, `maj_le` | Verrouillage **progressif** |
| `securite_compte` | `utilisateur_id`, `secret_mfa` (chiffré), `empreinte_session` | MFA optionnel |

### 3.2 Espaces & RBAC

| Table | Colonnes clés | Règle |
|---|---|---|
| `espace` | `id`, `tenant_id`, `parent_id`, `nom`, `visibilite` (`public_interne`/`restreint`/`prive`), `statut`, `supprime_le` | Arbre ; `prive` coupe l'héritage |
| `adhesion` | `id`, `tenant_id`, `espace_id`, `utilisateur_id`, `role_local`, `debut_le`, `fin_le` | **Rôle local par espace**, daté |
| `historique_adhesions` | `id`, `adhesion_id`, `…` | Trace des fins d'adhésion |

### 3.3 Documents

| Table | Colonnes clés | Règle |
|---|---|---|
| `document` | `id`, `tenant_id`, `espace_id`, `titre`, `resume`, `type`, `langue`, `confidentialite`, `statut` (`brouillon`/`publie`/`archive`), `proprietaire_id`, `supprime_le` | Confidentialité ≤ visibilité de l'espace |
| `version_document` | `id`, `document_id`, `numero` (unique par document), `fichier` (hors web), `nom_original`, `type_mime`, `taille_octets`, `empreinte` (SHA-256), `auteur_id`, `commentaire`, `cree_le` | **Immuable** : chaque dépôt = 1 ligne |

### 3.4 Audit

| Table | Colonnes clés | Règle |
|---|---|---|
| `journal` | `id`, `acteur_id` (NULL = système), `action`, `objet_type`, `objet_id`, `resultat` (`succes`/`refus`/`erreur`), `adresse_ip`, `details` (JSON), `horodatage` | **Append-only** : jamais de modification |

### 3.5 IA

| Table | Colonnes clés | Règle |
|---|---|---|
| `document_chunks` | `id`, `version_document_id`, `chunk_index`, `content` (TEXT), `embedding` (JSON) | 1 chunk = 1 version de document |
| `conversations` | `id`, `utilisateur_id`, `cree_le` | Historique des échanges |
| `messages` | `id`, `conversation_id`, `role`, `content`, `sources` (JSON), `cree_le` | Sources citées stockées |

---

## 4. Modèle de sécurité

### 4.1 Authentification

- **Mot de passe** : hash `PASSWORD_ARGON2ID` (ou `PASSWORD_DEFAULT`), politique `PolitiqueMotDePasse` (longueur ≥ 12, complexité).
- **Session** : cookie `HttpOnly`, `SameSite=Lax`, `Secure` en production ; **régénération** à la connexion.
- **CSRF** : jeton par session, vérifié sur **tous** les POST.
- **Anti-brute-force** : `tentative_connexion` → verrouillage **progressif** (ex. 5 échecs → 15 min).
- **Jetons** : activation/réinitialisation → **SHA-256** stocké, usage unique, expiration.
- **MFA** (optionnel) : `securite_compte.secret_mfa` chiffré.

### 4.2 RBAC — 2 niveaux

**Rôles globaux** (`utilisateur.role_global`) :

| Rôle | Permissions |
|---|---|
| `administrateur_technique` | `compte.inviter`, `journal.lire`, `dashboard.systeme` |
| `administrateur_fonctionnel` | `compte.inviter`, `espace.administrer`, `espace.creer`, `adhesion.gerer`, `espace.archiver` |
| `membre` | aucune permission globale ; tout vient des rôles locaux |

**Rôles locaux** (`adhesion.role_local`, par espace) :

| Rôle | Cumul |
|---|---|
| `lecteur` | `espace.consulter`, `document.consulter`, `assistant.interroger` |
| `contributeur` | lecteur + `document.deposer`, `assistant.tacher` |
| `animateur` | contributeur + `fil.moderer`, `annonce.publier` |
| `responsable` | animateur + `adhesion.gerer`, `espace.archiver` |

**Résolution** (jamais dans les vues) :

```
peut(acteur, action, espace?) :
1. role_global permet l'action ?            → accordé
2. sinon, résoudre le rôle local :
   adhésion valide sur l'espace ? oui → role_local
   sinon remonter l'arbre des parents (sauf espace `prive`)
3. role_local contient l'action ?           → accordé
4. sinon → refus + journal('permission_refusee')
```

### 4.3 Défense en profondeur

| Mesure | Implémentation |
|---|---|
| Injections SQL | **Requêtes préparées** (PDO) partout |
| XSS | Échappement `e()` ; jamais de HTML brut utilisateur |
| Upload | **Type MIME réel**, taille max, extension whitelist, stockage **hors web** |
| Sessions | `Session::regenerate()`, expiration, `HttpOnly` |
| En-têtes | `SecurityHeaders` (CSP, X-Frame-Options, nosniff…) |
| Secrets | `.env` (ignoré), aucun secret dans les logs |
| Audit | `journal` append-only + refus journalisés |

---

## 5. Phases de réalisation

> Chaque phase a un **objectif**, des **actions** et un **critère de sortie**. On ne passe à la suivante que lorsque le critère est atteint.

### Phase 0 — Fondations *(fait)*

**Objectif** : socle technique prêt.

- Scaffold MVC, PSR-4, `.env`, `bootstrap.php`, `View`, `Router`.
- Base `ai_knowledge_workspace` + migrations.
- `serve.bat`, `setup.bat`, `tools/migrate.php`.

**Critère de sortie** : `GET /` répond, la base est connectée, `php -v` = 8.4.27.

---

### Phase 1 — Comptes & authentification

**Objectif** : un compte sécurisé, avec session et CSRF.

1. **Migrations** : `001_utilisateur`, `002_jeton_compte`, `003_tentative_connexion`, `004_securite_compte`.
2. **Modèles** : `Utilisateur`, `SecuriteCompte`, `JetonCompte`.
3. **Services** : `AuthentificationService` (inscription, connexion, déconnexion, réinitialisation), `PolitiqueMotDePasse`.
4. **Core** : `Session`, `Csrf`, `Auth` (contrat), `SecurityHeaders`.
5. **Contrôleurs** : `AuthController` (login, logout), `MfaController` (optionnel).
6. **Vues** : `views/auth/login.php`, `views/auth/request-reset.php`.
7. **Seed** : un compte `administrateur_technique` initial (CLI).

**Critère de sortie** : login/logout fonctionnels ; mot de passe hashé ; CSRF actif ; verrouillage après 5 échecs ; tests `AuthentificationServiceTest`.

---

### Phase 2 — RBAC (espaces & adhésions)

**Objectif** : les droits sont résolus centralement.

1. **Migrations** : `005_espace`, `006_adhesion`, `007_historique_adhesions`.
2. **Modèles** : `Espace`, `Adhesion`.
3. **Service** : `PermissionService` (matrices GLOBAL/LOCAL, héritage d'arbre, espaces privés, journalisation des refus).
4. **Middleware** : `Controller::requirePermission()`.
5. **Tests** : `PermissionServiceTest` (héritage, privé, refus).

**Critère de sortie** : `peut()` respecte la matrice ; un `membre` sans adhésion est refusé ; refus journalisé.

---

### Phase 3 — Documents & versions

**Objectif** : gestion documentaire versionnée, sécurisée.

1. **Migrations** : `008_document`, `009_version_document`.
2. **Modèle** : `Document`, `VersionDocument`.
3. **Service** : `DocumentService` (création, dépôt d'une version, SHA-256, stockage hors web, suppression logique).
4. **Contrôleur** : `DocumentController` (liste, upload, versions).
5. **Vues** : `views/documents/index.php`.
6. **Validation** : type MIME réel, taille, extension.

**Critère de sortie** : un document a plusieurs versions immuables ; l'empreinte SHA-256 est stockée ; aucun fichier en racine web.

---

### Phase 4 — Base de connaissances & RAG

**Objectif** : retrouver les bons passages, version par version.

1. **Migration** : `010_document_chunks` (rattaché à `version_document`).
2. **Service** : `RagService` (`indexDocument`, `answer`, `task`, `retrieveSimilar`).
3. **Embedding** : local (défaut) ; `META_EMBEDDING_MODE=api` si endpoint confirmé.
4. **Tests** : `RagServiceTest`, `RagServiceIntegrationTest`.

**Critère de sortie** : une question renvoie les passages pertinents **de la version courante** + une réponse.

---

### Phase 5 — Assistant agentique

**Objectif** : le différenciateur.

1. **Sources citées** : `answer()` renvoie `{answer, sources}` ; affichage UI.
2. **Tâches** : `task()` (note, rapport, liste des retards) ; brouillon structuré.
3. **Résumé dédié** : endpoint « Résumer ce document ».
4. **Prompt système** : répondre **uniquement** à partir des extraits, citer, ne pas inventer.
5. **Tests** : `answer()` avec sources ; `task()` avec brouillon.

**Critère de sortie** : la démo montre une **tâche rédigée** avec **sources**.

---

### Phase 6 — Multimodal (scans & voix)

**Objectif** : traiter images et audio.

1. **Vision** : `VisionService` → SAM 3.1 ; confirmer l'endpoint ; test sur un scan.
2. **Voix** : `TranscriptionService` → Muse Voice Transcribe ; test sur un audio.
3. **Pipeline** : image/audio → texte → `indexDocument`.
4. **UI** : accepter `.pdf/.txt/.md/.png/.jpg/.mp3/.wav`.

**Critère de sortie** : un scan et un audio produisent du texte indexé et interrogeable.

---

### Phase 7 — Audit & sécurité

**Objectif** : traçabilité complète.

1. **Migration** : `011_journal` (append-only).
2. **Service** : `JournalService` (`log(acteur, action, objet, resultat, details)`).
3. **Instrumentation** : connexion, upload, suppression, question, refus.
4. **En-têtes** : `SecurityHeaders` (CSP, nosniff, frame-options).
5. **Tests** : `JournalServiceTest` ; vérifier l'absence de `UPDATE`/`DELETE` sur `journal`.

**Critère de sortie** : chaque action sensible crée une ligne `journal` ; aucun secret dans `details`.

---

### Phase 8 — Tests & recette

**Objectif** : un projet fiable.

1. `phpunit` → **tous verts** (unitaires + intégration).
2. `php -l` sur les fichiers modifiés.
3. Revue sécurité : `.env` ignoré, uploads validés, aucune clé dans le code.
4. Test « clone propre » (voir `DEMO_VIDEO_ET_SOUMISSION.md`).

**Critère de sortie** : tests verts + `git status` propre.

---

### Phase 9 — Vidéo & soumission

**Objectif** : livrer dans les délais.

1. **Vidéo** (≤ 3 min) : voir `DEMO_VIDEO_ET_SOUMISSION.md`.
2. **Dépôt public** : README, LICENSE, pas de secret.
3. **Formulaire** : repo + vidéo + description.
4. **Soumettre avant le 4 nov. 21 h ET**.

**Critère de sortie** : confirmation de soumission.

---

## 6. Correspondance MVP → phases

| Fonctionnalité MVP | Phase |
|---|---|
| Auth sécurisée + RBAC | **1 + 2** |
| Gestion documentaire + versions | **3** |
| Upload + indexation PDF | **3 + 4** |
| Scans via SAM 3.1 | **6** |
| Voix via Muse Voice Transcribe | **6** |
| Base de connaissances | **4** |
| Assistant agentique + sources | **5** |
| Résumé automatique | **5** |
| Génération de rapports | **5** |
| Recherche sémantique | **4** |
| Journalisation (audit) | **7** |

---

## 7. Alignement avec la notation (Indie Developer)

| Critère | Ce que le projet apporte | Preuve |
|---|---|---|
| **Créativité** | Agentique + multimodal + gouvernance pour des organisations africaines | Démo : scan + voix + tâche |
| **Ambition technique** | RAG réel, RBAC, versions, audit, API Meta | Dépôt + code + tests |
| **Fonctionnalité** | MVP qui marche de bout en bout | Vidéo + démo live |

> Stage 1 : le projet doit **correspondre au thème** et **utiliser l'API Meta / les modèles ouverts**.

---

## 8. Calendrier

| Date | Jalon |
|---|---|
| **26 oct.** | Kickoff ; comptes, crédits, clé API |
| **27–29 oct.** | Phases 1–2 (auth, RBAC) |
| **30–31 oct.** | Phase 3 (documents/versions) |
| **1–2 nov.** | Phases 4–5 (RAG, agentique) |
| **3 nov.** | Phases 6–7 (multimodal, audit) + vidéo |
| **4 nov. 21 h ET** | **Soumission** |
| **5–8 nov.** | Jugement |
| **9 nov.** | Annonce |

---

## 9. Risques et parades

| Risque | Parade |
|---|---|
| Auth/RBAC trop long | Commencer par le **minimum** (login + `PermissionService`) ; enrichir ensuite |
| Endpoints SAM 3.1 / voix incertains | Confirmer la doc ; sinon démontrer texte + PDF et documenter la roadmap |
| API qui échoue en démo | Jeu de données local + **Plan B Baseten** |
| Perçu comme « basic RAG » | Insister sur **agentique + multimodal + sources + gouvernance** |
| Fuite de secret | `.env` ignoré ; audit `details` sans secret |
| Dépôt privé | Vérifier **Public** avant soumission |

---

## 10. Commandes utiles

```powershell
# Serveur
cd "C:\wamp64\www\projet hackaton\ai-knowledge-workspace"
.\serve.bat

# Migrations
& "C:\wamp64\bin\php\php8.4.27\php.exe" tools/migrate.php migrate

# Indexer un document
& "C:\wamp64\bin\php\php8.4.27\php.exe" tools/index_document.php "Rapport" docs/sample-report.txt

# Tests
& "C:\wamp64\bin\php\php8.4.27\php.exe" vendor/phpunit/phpunit/phpunit

# Git
git add -A ; git commit -m "…" ; git push
```

---

## 11. Checklist finale

- [ ] Auth + RBAC fonctionnels (login, `peut()`, refus journalisés)
- [ ] Document + versions immuables (SHA-256)
- [ ] Upload PDF + chunking + indexation
- [ ] RAG (retrieve + answer + sources)
- [ ] Tâche agentique (brouillon)
- [ ] Multimodal (scan + audio) — si endpoints confirmés
- [ ] Journal d'audit append-only
- [ ] Tests PHPUnit verts
- [ ] README EN/FR, LICENSE, aucun secret
- [ ] Dépôt **public**
- [ ] Vidéo **≤ 3 min**
- [ ] Soumis **avant le 4 nov. 21 h ET**

---

## 12. Suivi des problèmes & résolutions

Tous les blocages et écarts sont suivis dans **[REGISTRE_PROBLEMES.md](REGISTRE_PROBLEMES.md)** :
ID (O1, O2…), statut, résolution datée, journal.

> Règle : **aucun problème ne disparaît sans trace**.

---

## 13. Plan B — Inférence partenaire

Si l'API Meta est bloquée (ex. carte prépayée refusée) :

```dotenv
META_API_STYLE=openai
META_MODEL_API_BASE_URL=https://inference.baseten.co
META_MODEL_API_KEY=<clé Baseten>
META_MODEL_NAME=meta-models/muse-spark-open
META_EMBEDDING_MODE=local
```

> Les embeddings restent **locaux** : seul le chat bascule sur le partenaire.

*Ce guide est un document vivant : coche chaque critère de sortie avant de passer à la phase suivante.*
