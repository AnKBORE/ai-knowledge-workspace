# Espace de connaissances agentique et multimodal

Un espace de connaissances agentique et multimodal pour les organisations : il ingère des documents texte, des archives papier scannées et des enregistrements vocaux, puis exécute des tâches en plusieurs étapes — répondre avec sources citées, préparer des notes de réunion, rédiger des rapports et signaler les projets en retard.

Réalisé pour le **Meta Global AI Developer Hackathon** (Thème : *Build for Your World* · Parcours : *développeur indépendant*).

## Vue d'ensemble

Les organisations accumulent pendant des années des rapports, des comptes rendus, des procédures, des archives scannées et des enregistrements de réunions. L'information est dispersée et, au bout d'un moment, plus personne ne la retrouve. **Espace de connaissances agentique et multimodal** permet à une équipe de :

1. Ingérer des documents texte (PDF, rapports, comptes rendus), des archives papier scannées et des enregistrements vocaux.
2. Laisser l'IA les transformer en une base de connaissances privée et multimodale.
3. Poser une question ou demander une tâche en langage naturel.
4. Obtenir une réponse ancrée — ou un brouillon prêt à l'emploi — avec chaque affirmation citée à sa source.

## Fonctionnalités (MVP)

- Authentification sécurisée et contrôle d'accès basé sur les rôles (RBAC)
- Gestion documentaire avec versionnement
- Téléversement et indexation de PDF
- Archives papier scannées via SAM 3.1
- Notes vocales et transcription de réunions via Muse Voice Transcribe
- Base de connaissances
- Assistant agentique : tâches en plusieurs étapes avec sources citées
- Résumé automatique de documents
- Génération automatique de rapports
- Recherche intelligente / sémantique
- Journalisation des actions (piste d'audit)

## IA (API Meta Model)

| Modèle | Rôle |
|---|---|
| Muse Spark | Assistant conversationnel, questions/réponses, génération de contenu, analyse documentaire |
| Muse Wonder | Génération de rapports, planification, aide à la décision |
| Muse Image | Visuels et illustrations de projet |
| Muse Voice Transcribe | Réunions et notes vocales → texte interrogeable |
| SAM 3.1 | Traitement des documents scannés, reconnaissance visuelle |
| Muse Code | Agent de codage |
| Muse Glimmer / Muse Spark Open | Modèles Meta à pondération ouverte (déploiement privé via Baseten, Modal, Fireworks) |

La génération augmentée par récupération (RAG) ancre chaque réponse dans les documents de l'organisation ; la phase 2 étend cela à la recherche vectorielle sur toutes les modalités.

## Pile technique

| Composant | Version |
|---|---|
| PHP | 8.4.27 |
| MySQL | 8.4.13 (InnoDB, utf8mb4) |
| Serveur web | Apache 2.4 |
| Frontend | HTML5, CSS3, Bootstrap 5, JavaScript ES6 |
| IA | API Meta Model (REST) |

## Structure du projet

```
public/       contrôleur frontal + assets (seul dossier exposé)
config/       configuration par environnement et routes
core/         HTTP, routage, session, sécurité, PDO
controllers/  contrôleurs MVC
models/       accès aux données
services/     logique métier (+ Ai/ pour l'intégration de l'API Meta)
validators/   validation des entrées
views/        gabarits et pages
storage/      journaux, fichiers, sauvegardes, cache (jamais exposé)
migrations/   schéma versionné
tests/        PHPUnit
```

## Installation

```powershell
# 1. Dépendances
composer install

# 2. Configuration locale
Copy-Item .env.example .env

# 3. Base de données
php tools/migrate.php migrate

# 4. Lancement local
php -S 127.0.0.1:8080 -t public
```

En production, le document root doit pointer sur `public/`.

Sous Windows, `.\setup.ps1` (ou `setup.bat`) exécute ces étapes automatiquement avec PHP 8.4.27.

## Démarrage rapide — démo IA

```powershell
# 1. Dépendances (crée vendor/autoload.php)
composer install

# 2. Créer le schéma
php tools/migrate.php migrate

# 3. Configurer .env avec vos clés API Meta (voir .env.example)

# 4. Indexer un document (.txt, .md, .pdf)
php tools/index_document.php "Rapport annuel" docs/sample-report.txt

# 5. Lancer le serveur local
php -S 127.0.0.1:8080 -t public

# 6. Poser une question (dans un autre terminal)
curl.exe -X POST http://127.0.0.1:8080/api/ask.php -H "Content-Type: application/json" -d "{\"question\":\"Quels sont les principaux risques ?\"}"
```

## Authentification

L'espace de travail est protégé par une connexion par session (protection CSRF, verrouillage anti-brute-force, hachage des mots de passe).

Créez le premier compte administrateur :

```bash
php tools/seed_admin.php
```

Les identifiants se règlent avec `ADMIN_EMAIL` / `ADMIN_PASSWORD` (voir `tools/seed_admin.php`). Ouvrez ensuite <http://127.0.0.1:8080/login>.

## Interface web

L'espace de travail est une page rendue côté serveur (Bootstrap 5 + le design system du socle) :

- `GET /` — téléverser des documents, poser des questions **avec sources citées** et lancer des **tâches agentiques** (rédiger une note/un rapport).
- `POST /api/upload.php` — indexer un fichier `.txt/.md/.pdf/image/audio`.
- `POST /api/ask.php` — `{"question": "..."}` → réponse + sources ; `{"instruction": "...", "mode": "task"}` → brouillon + sources.

Lancez le serveur puis ouvrez <http://127.0.0.1:8080/>.

## Documentation

- [docs/GUIDE_DE_REALISATION.md](docs/GUIDE_DE_REALISATION.md) — feuille de route d'exécution (phases, checklists, jalons).
- [docs/REGISTRE_PROBLEMES.md](docs/REGISTRE_PROBLEMES.md) — registre des problèmes et journal des résolutions.
- [docs/DEMO_VIDEO_ET_SOUMISSION.md](docs/DEMO_VIDEO_ET_SOUMISSION.md) — script de la vidéo de démo et checklist de soumission.

## Tests

```powershell
php vendor/phpunit/phpunit/phpunit
```

15 tests couvrent le découpage RAG, la similarité cosinus, le chargeur `.env` et le pipeline RAG complet — sans serveur de base de données ni API.

## Sécurité

Requêtes SQL préparées · protection CSRF · hachage des mots de passe · RBAC vérifié sur chaque objet ciblé · refus par défaut · fichiers hors de la racine web · aucun secret dans le code ni les journaux · HTTPS et en-têtes de sécurité · journalisation des opérations sensibles.

## Auteur

**Anatole KABORE** — Ingénieur logiciel indépendant et spécialiste en cybersécurité, Burkina Faso.

- LinkedIn : <https://www.linkedin.com/in/anatole-kabore-73b076342>

## Licence

Propriétaire — projet de démonstration pour le Meta Global AI Developer Hackathon.
