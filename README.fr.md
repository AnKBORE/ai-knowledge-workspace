# AI Knowledge Workspace

Un espace de connaissances institutionnelles propulsé par l'IA, qui transforme les documents dispersés d'une organisation en une base de connaissances privée et interrogeable — et en un assistant conversationnel qui répond aux questions, génère des synthèses et rédige des rapports.

Réalisé pour le **Meta Global AI Developer Hackathon** (Thème : *Build for Your World* · Parcours : *développeur indépendant*).

## Vue d'ensemble

Les organisations accumulent pendant des années des rapports, des comptes rendus, des procédures et des règlements. L'information est dispersée et, au bout d'un moment, plus personne ne la retrouve. **AI Knowledge Workspace** permet à une équipe de :

1. Téléverser des documents (PDF, rapports, comptes rendus).
2. Laisser l'IA les indexer dans une base de connaissances privée.
3. Poser des questions en langage naturel.
4. Obtenir des réponses, des synthèses et des rapports générés directement à partir des propres documents de l'organisation.

## Fonctionnalités (MVP)

- Authentification sécurisée et contrôle d'accès basé sur les rôles (RBAC)
- Gestion documentaire avec versionnement
- Téléversement et indexation de PDF
- Base de connaissances
- Chat IA connecté aux documents (questions/réponses en langage naturel)
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
| SAM 3.1 | Vision par ordinateur, traitement des documents scannés |
| Muse Code | Agent de codage |
| Modèles à pondération ouverte | Auto-hébergés / hébergés par des partenaires (Baseten, Modal, Fireworks) |

Phase 2 : recherche vectorielle et génération augmentée par récupération (RAG).

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
php tools/migrate.php fresh --seed

# 4. Lancement local
php -S 127.0.0.1:8080 -t public
```

En production, le document root doit pointer sur `public/`.

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

## Tests

```powershell
php vendor/phpunit/phpunit/phpunit
```

12 tests couvrent le découpage RAG, la similarité cosinus et le chargeur `.env` — sans base de données ni API.

## Sécurité

Requêtes SQL préparées · protection CSRF · hachage des mots de passe · RBAC vérifié sur chaque objet ciblé · refus par défaut · fichiers hors de la racine web · aucun secret dans le code ni les journaux · HTTPS et en-têtes de sécurité · journalisation des opérations sensibles.

## Auteur

**Anatole KABORE** — Ingénieur logiciel indépendant et spécialiste en cybersécurité, Burkina Faso.

- LinkedIn : <https://www.linkedin.com/in/anatole-kabore-73b076342>

## Licence

Propriétaire — projet de démonstration pour le Meta Global AI Developer Hackathon.
