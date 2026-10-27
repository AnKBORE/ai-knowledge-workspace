# Espace de travail collaboratif IA

> **Une plateforme collaborative de gestion des connaissances, propulsée par l'IA**, destinée aux **établissements d'enseignement, ONG, organisations confessionnelles et entreprises en Afrique**.
>
> Il ingère des documents, des scans et des notes vocales, puis **agit** — répond avec des sources citées, rédige des notes et des rapports, et résume des documents.
>
> Réalisé pour le **Meta Global AI Developer Hackathon** — Thème : **Build for Your World** · Piste : **Indie Developer** · Date limite : **4 novembre 2026, 21 h ET**.

---

## Le problème résolu

- Les documents sont dispersés entre dossiers, e-mails et papier.
- Les connaissances institutionnelles se perdent avec les départs.
- Les nouveaux membres ont du mal à retrouver l'information.
- La recherche d'information prend un temps considérable.

La plateforme transforme tout cela en **une base de connaissances unique, sécurisée et interrogeable par IA** : chaque réponse cite ses sources, chaque action est journalisée, chaque document est versionné.

---

## Pourquoi ce n'est PAS une « basic RAG application »

Le hackathon bannit explicitement les *basic RAG applications* (voir le Participant Guide, §4). Ce projet dépasse volontairement la boucle « question → réponse » :

| Un « basic RAG » | Ce projet |
|---|---|
| PDF texte uniquement | **Texte + PDF + images scannées + audio** (ingestion multimodale) |
| Une question → une réponse | **Tâches agentiques** : rédiger une note, un rapport, une fiche pédagogique (multi-étapes) |
| Aucune traçabilité | **Sources numérotées et citées** sur chaque réponse et chaque brouillon |
| Aucune gouvernance | **RBAC**, **versions** immuables des documents (SHA-256), **journal d'audit** en ajout seul |
| Aucun export | **Export PDF / DOCX** de chaque résultat généré |
| Chatbot générique | **Un domaine réel** : paroisses, ONG et écoles au Burkina Faso |

---

## Construit pendant le hackathon

Le dépôt contient deux couches **clairement séparées** :

- **Nouveau — construit pendant le hackathon**
  - la couche IA : pipeline RAG (`services/Ai/`), `answer()`, `task()`, `summarize()`, sources citées ;
  - l'export PDF / DOCX (`ExportService`) ;
  - l'espace web multi-pages (Question, Tâche agentique, Documents, Ajouter) ;
  - la couche de gouvernance : comptes, RBAC (`PermissionService`), versions de documents, journal d'audit.
- **Préexistant — socle réutilisé**
  - le **design system** visuel (Bootstrap 5 + CSS) et les conventions MVC du projet antérieur `espace_collaboratif` de l'auteur.
  - Aucun code ou donnée client propriétaire n'est inclus.

> Chaque fonctionnalité montrée dans la vidéo de démonstration a été **écrite pendant le hackathon**.

---

## Ce qui fonctionne aujourd'hui

| Fonctionnalité | Statut |
|---|---|
| Ingestion texte / PDF + chunking + indexation | ✅ |
| Recherche sémantique (similarité cosinus) | ✅ |
| Réponses ancrées **avec sources citées** | ✅ |
| Rédaction agentique (notes, rapports, fiches pédagogiques) | ✅ |
| Résumé automatique de documents | ✅ |
| Export PDF / DOCX des résultats | ✅ |
| Authentification par session (CSRF, anti-brute-force) | ✅ |
| RBAC (rôles globaux + par espace) | ✅ |
| Versions immuables des documents (SHA-256) | ✅ |
| Journal d'audit en ajout seul | ✅ |
| Images scannées (OCR via la vision de Muse Spark Open) | ✅ (testé en live) |
| Voix (audio via Muse Spark Open) | ✅ (testé en live avec de la parole) |
| SAM 3.1 / Muse Voice Transcribe (endpoints dédiés) | 🔜 optionnel (Meta Model API) |

---

## IA (modèles Meta)

Le modèle de génération est **Muse Spark Open**, servi par un **partenaire d'inférence Meta** (Baseten) via l'API compatible OpenAI. L'architecture laisse la porte ouverte à la Meta Model API (`/v1/responses`) et aux autres modèles Muse.

| Modèle | Rôle | Où |
|---|---|---|
| **Muse Spark Open** | Réponses, brouillons, résumés | Baseten (`meta-models/muse-spark-open`) |
| Muse Wonder | Génération de rapports longs (option) | Meta Model API |
| Muse Voice Transcribe | Réunions et notes vocales → texte interrogeable | Feuille de route |
| SAM 3.1 | Traitement des documents scannés | Feuille de route |
| Muse Glimmer / Muse Spark Open | Modèles open-weight Meta | Baseten / Modal / Fireworks |

Deux styles d'API sont pris en charge :

```dotenv
# Meta Model API
META_API_STYLE=meta
META_MODEL_API_BASE_URL=https://api.meta.ai
META_MODEL_NAME=muse-wonder-1.0

# Partenaire d'inférence Meta (compatible OpenAI)
META_API_STYLE=openai
META_MODEL_API_BASE_URL=https://inference.baseten.co
META_MODEL_NAME=meta-models/muse-spark-open
```

---

## Stack technique

| Composant | Version |
|---|---|
| PHP | **8.4.27** |
| MySQL | **8.4.13** (InnoDB, utf8mb4) |
| Frontend | HTML5, CSS3, Bootstrap 5, JavaScript ES6 |
| IA | Muse Spark Open via Baseten (compatible OpenAI) |
| PDF | `dompdf` |
| DOCX | OOXML natif (`ZipArchive`) |

---

## Structure du projet

```
public/        contrôleur frontal + assets (seul dossier exposé)
config/        configuration d'environnement
core/          HTTP, routage, session, CSRF, auth, PDO
controllers/   contrôleurs MVC
models/        accès aux données (utilisateurs, espaces, documents, versions)
services/      logique métier (+ Ai/ pour l'intégration des modèles)
validators/    validation des entrées
views/         gabarits et pages
storage/       fichiers, journaux, sauvegardes (jamais exposés)
migrations/    schéma versionné
tests/         PHPUnit
```

---

## Installation

```powershell
# 1. Dépendances
php tools/composer.phar install

# 2. Configuration locale
Copy-Item .env.example .env

# 3. Base de données
php tools/migrate.php migrate

# 4. Premier administrateur + espace par défaut
php tools/seed_admin.php

# 5. Lancement local
php -S 127.0.0.1:8080 -t public
```

Puis ouvrez <http://127.0.0.1:8080/login>.

En production, la racine web doit pointer vers `public/`.

Sous Windows, `.\setup.ps1` (ou `setup.bat`) exécute ces étapes automatiquement avec PHP 8.4.27.

---

## Interface web

L'espace est découpé en **quatre pages navigables**, pilotées par le menu vertical :

| Page | URL | Rôle |
|---|---|---|
| **Question** | `/` | Poser une question, obtenir une réponse ancrée avec sources citées |
| **Tâche agentique** | `/tache` | Demander une note / un rapport / une fiche ; le brouillon s'ouvre dans une modal |
| **Documents** | `/documents` | Liste des documents indexés, résumé en un clic |
| **Ajouter** | `/ajouter` | Indexer un fichier texte, PDF, image ou audio |

Chaque résultat s'ouvre dans une **modal** avec les boutons **PDF** et **DOCX**.

Endpoints API (protégés par session) :

- `POST /api/upload.php` — indexer un fichier (multipart)
- `POST /api/ask.php` — `{"question": "..."}` → réponse + sources ; `{"instruction": "...", "mode": "task"}` → brouillon + sources
- `POST /api/summarize.php` — `{"document_id": 3}` → résumé + sources
- `POST /api/export.php` — `{"title", "content", "format": "pdf|docx"}` → fichier
- `GET /api/documents.php` — documents récents (JSON)

---

## Tests

```powershell
php vendor/phpunit/phpunit/phpunit
```

**50 tests, 104 assertions** — couvrant l'authentification, le RBAC, les versions de documents, l'audit, le RAG, les exports et les services multimodaux. Aucun serveur de base ni API requis (SQLite en mémoire + doubles déterministes).

---

## Sécurité

Requêtes préparées · CSRF sur chaque POST · hachage des mots de passe (Argon2/bcrypt) · verrouillage anti-brute-force progressif · RBAC résolu centralement (`PermissionService`) · refus par défaut · fichiers hors racine web · aucun secret dans le code ou les journaux · journal d'audit en ajout seul · en-têtes de sécurité.

---

## Documentation

- [docs/GUIDE_DE_REALISATION.md](docs/GUIDE_DE_REALISATION.md) — feuille de route d'exécution (phases, checklists, jalons)
- [docs/REGISTRE_PROBLEMES.md](docs/REGISTRE_PROBLEMES.md) — registre des problèmes et journal des résolutions
- [docs/DEMO_VIDEO_ET_SOUMISSION.md](docs/DEMO_VIDEO_ET_SOUMISSION.md) — script de la vidéo et checklist de soumission

---

## Auteur

**Anatole KABORE** — Ingénieur logiciel indépendant et spécialiste en cybersécurité, Burkina Faso.

- LinkedIn : <https://www.linkedin.com/in/anatole-kabore-73b076342>
- GitHub : <https://github.com/AnKBORE>

---

## Licence

**MIT** — voir [LICENSE](LICENSE).
