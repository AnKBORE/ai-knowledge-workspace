# Registre des problèmes & résolutions

> **Document vivant** : suivi de tous les blocages et écarts du projet.
> Chaque problème reçoit un **ID**. Quand il est résolu :
> 1. passer le **Statut** à ✅,
> 2. renseigner **Résolution** (date + note),
> 3. ajouter une ligne dans le **Journal des résolutions**.
>
> C'est la **source de vérité** pour savoir « où on en est ».

## Légende

| Symbole | Signification |
|---|---|
| 🔴 | Ouvert (bloquant ou à faire) |
| 🟡 | En cours |
| ✅ | Résolu |

---

## 1. Problèmes ouverts (à résoudre)

| ID | Problème | Impact | Priorité | Statut | Résolution |
|---|---|---|---|---|---|
| **O4** | Endpoints `transcription` (voix) et `vision` (scan) non confirmés par la doc Meta | Le multimodal voix/scan n'est pas testé | Moyenne | 🔴 | — |
| **O9** | **Vidéo de démonstration** (3 min) non enregistrée | Élément central de notation | 🔥 Critique | 🟡 | Script + checklist prêts (`docs/DEMO_VIDEO_ET_SOUMISSION.md`) |
| **O10** | **Soumission** au hackathon non faite | Sans soumission => pas de prix | 🔥 Critique | 🟡 | Checklist prête ; formulaire à remplir |
| **O11** | `php` absent du PATH Windows | Commandes `php …` en erreur | Faible | 🟡 | Contournement : `serve.bat` / chemin complet |

---

## 2. Problèmes résolus (historique)

| ID | Problème | Résolution | Date |
|---|---|---|---|
| **R1** | Versions PHP/MySQL imprécises | Épinglées sur **PHP 8.4.27 / MySQL 8.4.13** | 2026-10-24 |
| **R2** | Pas de base ni de schéma | Base `ai_knowledge_workspace` + 5 tables via `tools/migrate.php` | 2026-10-26 |
| **R3** | Pas de pipeline RAG | `RagService` (chunk, index, retrieve, query) implémenté | 2026-10-26 |
| **R4** | Endpoint API supposé (`/v1/chat/completions`) | Aligné sur **`POST /v1/responses`**, modèle `muse-wonder-1.0` | 2026-10-26 |
| **R5** | Dépendance à un endpoint d'embeddings incertain | **Embedding local (hashing)** par défaut, `META_EMBEDDING_MODE=api` en option | 2026-10-26 |
| **R6** | Pas d'interface | UI web (design du socle) : upload + chat + liste | 2026-10-26 |
| **R7** | Upload PDF fonctionnel | `smalot/pdfparser` + chunking + stockage (ex. « Offre technique », 150 chunks) | 2026-10-26 |
| **R8** | Pas de tests | **19 tests PHPUnit** verts | 2026-10-26 |
| **R9** | Commandes manuelles complexes | `setup.ps1` / `setup.bat` / `serve.bat` | 2026-10-26 |
| **R10** | Erreurs PSR-4 (`services/ai` vs `Ai`) | Dossier renommé `services/Ai`, autoload régénéré | 2026-10-26 |
| **R11** | Pas de feuille de route | `docs/GUIDE_DE_REALISATION.md` (9 phases) | 2026-10-26 |
| **R12** | Erreurs API opaques | Mode **`APP_DEBUG=1`** → message d'erreur réel | 2026-10-26 |
| **O3** | Team ID / formulaire de crédits | Formulaire de crédits **soumis** (Team ID renseigné) | 2026-10-26 |
| **O8** | Dépôt GitHub privé | Dépôt passé en **public** | 2026-10-26 |
| **O2** | Crédits $150 non visibles | **Crédits actifs** : « Free credits in use: $150,00 » | 2026-10-26 |
| **O1** | Clé API / chat | Clé **Baseten** configurée (Plan B) ; réponse `[OK]` | 2026-10-26 |
| **O7** | Document client indexé | Supprimé de la base ; démo sur document neutre | 2026-10-26 |
| **O12** | Test réel de l'API | **Pipeline RAG complet validé** (retrieve + Muse Spark Open) | 2026-10-26 |
| **O5** | Sources citées absentes | `RagService::answer()` renvoie les **sources** ; affichées dans l'UI | 2026-10-26 |
| **O6** | Tâches agentiques | `RagService::task()` + section « Tâche agentique » dans l'UI | 2026-10-26 |
| **R13** | Auth / RBAC / versions / audit | **Phase 1 livrée** : `utilisateur`, `Session`, `Csrf`, `Auth`, `AuthentificationService`, login/logout, 30 tests verts | 2026-10-26 |
| **R14** | RBAC (espaces & adhésions) | **Phase 2 livrée** : `espace`, `adhesion`, `historique_adhesions`, `journal`, `PermissionService` (global/local, héritage, espaces privés), 37 tests verts | 2026-10-26 |
| **R15** | Documents & versions | **Phase 3 livrée** : `document` enrichi, `version_document` (SHA-256, numéros uniques), `DocumentService` (stockage hors web), upload live validé, 41 tests verts | 2026-10-26 |
| **R16** | Journalisation (audit) | **Phase 7 livrée** : `JournalService` append-only + instrumentation (connexion, déconnexion, dépôt, question), journal live validé, 45 tests verts | 2026-10-26 |
| **R17** | Résumé automatique | **Livré** : `RagService::summarize()` + `/api/summarize.php` + bouton « Résumer » dans l'UI, résumé live validé (sources incluses), 47 tests verts | 2026-10-26 |
| **R18** | Crédits Baseten (partenaires) | **Demande soumise** (100 $ + 2M TPM) ; sélection par cas d'usage, réponse par e-mail | 2026-10-26 |
| **R19** | RBAC effectif, documents rattachés, versions, suppression | **Livré** : RBAC appliqué aux 4 endpoints (200 responsable / 403 membre sans adhésion), `espace_id` renseigné, badge versions, suppression + journal, 52 tests | 2026-10-26 |
| **R20** | Recherche sémantique faible | **Livré** : recherche **hybride** (cosinus 60 % + recouvrement lexical 40 %), `search()` exposé, test de classement, 53 tests | 2026-10-26 |
| **R21** | Multimodal non démontré | **Livré** : `describeImage()` route vers **Muse Spark Open** (`image_url`) quand `api_style=openai` ; OCR testé en live (« BONJOUR LE MONDE ») ; audio via `input_audio` (code prêt) | 2026-10-26 |

---

## 3. Journal des résolutions

> Une ligne par problème résolu (ordre chronologique). C'est ici qu'on « notifie » une résolution.

| Date | ID | Notification |
|---|---|---|
| 2026-10-24 | R1 | Versions sécurisées PHP 8.4.27 / MySQL 8.4.13 intégrées (docs + code). |
| 2026-10-26 | R2–R12 | Socle, base, RAG, API réelle, UI, tests, scripts et guide livrés. |
| 2026-10-26 | — | **Constat** : l'upload fonctionne (150 chunks) ; le chat échoue car `META_MODEL_API_KEY` vide (**O1**). |
| 2026-10-26 | O3 | Formulaire de crédits **soumis** (Team ID renseigné). |
| 2026-10-26 | O8 | Dépôt GitHub passé en **public**. |
| 2026-10-26 | O2 | Crédits $150 activés (« Free credits in use: $150,00 »). |
| 2026-10-26 | O1 | Clé Baseten configurée (Plan B) ; appel test `[OK]`. |
| 2026-10-26 | O7 | Document client supprimé ; démo sur `docs/sample-report.txt`. |
| 2026-10-26 | O12 | Pipeline RAG complet validé (réponse sourcée sur le rapport neutre). |
| 2026-10-26 | O5 | Sources citées affichées sous la réponse. |
| 2026-10-26 | O6 | Tâche agentique (« Générer un brouillon ») fonctionnelle. |
| 2026-10-26 | R13 | Phase 1 (auth, CSRF, anti-brute-force) livrée ; 30 tests verts. |
| 2026-10-26 | R14 | Phase 2 (espaces, adhésions, PermissionService) livrée ; 37 tests verts. |
| 2026-10-26 | R15 | Phase 3 (documents versionnés, SHA-256, stockage hors web) livrée ; 41 tests verts. |
| 2026-10-26 | R16 | Phase 7 (journal d'audit append-only) livrée ; 45 tests verts. |
| 2026-10-26 | R17 | Résumé automatique livré (sources incluses) ; 47 tests verts. |
| 2026-10-26 | R18 | Demande de crédits Baseten soumise (100 $ + 2M TPM) ; réponse par e-mail si sélectionné. |
| 2026-10-26 | R19 | RBAC appliqué aux endpoints (403 vérifié) ; documents rattachés à un espace ; versions visibles ; suppression de document. |
| 2026-10-26 | R20 | Recherche hybride (sémantique + lexicale) livrée ; bruit de retrieval supprimé ; 53 tests verts. |
| 2026-10-26 | R21 | Vision via Muse Spark Open validée en live (OCR) ; audio (`input_audio`) branché. |

---

## 4. Convention de notification (à respecter)

Quand un problème est résolu :

1. Dans le tableau **« Problèmes ouverts »** : passer le **Statut** à **✅** et remplir **Résolution** (date + note courte).
2. **Déplacer** la ligne vers **« Problèmes résolus »** (ou la marquer ✅).
3. Ajouter une ligne au **Journal des résolutions** :
   ```
   | AAAA-MM-JJ | O<num> | <ce qui a été fait, en une phrase> |
   ```
4. Mettre à jour la **checklist finale** du guide (`GUIDE_DE_REALISATION.md`).
5. Si applicable, mettre à jour le **README** (si le problème concernait la doc).

> Règle d'or : **aucun problème ne disparaît sans trace** — il est toujours résolu + journalisé.
